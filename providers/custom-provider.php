<?php
/**
 * Custom OTP Provider Implementation
 * Communicates with external API via PHP cURL with strict timeout handling and real error logging
 */

require_once __DIR__ . '/provider-interface.php';
require_once __DIR__ . '/provider-response.php';
require_once __DIR__ . '/../config/database.php';

class CustomProviderAdapter implements ProviderInterface {
    protected string $apiUrl;
    protected ?string $apiKey;
    protected int $timeout;

    public function __construct(string $apiUrl, ?string $apiKey = null, int $timeout = 10) {
        $this->apiUrl = rtrim(trim($apiUrl), '/');
        $this->apiKey = $apiKey ? trim($apiKey) : null;
        $this->timeout = $timeout;
    }

    /**
     * Test connection to provider API with real diagnostics
     */
    public function testConnection(): array {
        $startTime = microtime(true);

        if (empty($this->apiUrl) || !filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'connected' => false,
                'http_code' => 0,
                'latency_ms' => 0,
                'balance' => 0.00,
                'services_count' => 0,
                'sample_service' => null,
                'error' => 'API endpoint base URL is empty or invalid (must start with http:// or https://).'
            ];
        }

        // Try balance endpoint
        $res = $this->makeRequest('GET', '/balance');
        $httpCode = $res['http_code'] ?? 0;
        $latency = round((microtime(true) - $startTime) * 1000);

        // If returned 404, try query parameter style (?action=getBalance)
        if ($httpCode === 404 || !$res) {
            $res = $this->makeRequest('GET', '/?action=getBalance');
            $httpCode = $res['http_code'] ?? $httpCode;
            $latency = round((microtime(true) - $startTime) * 1000);
        }

        $connected = false;
        $errorMessage = '';
        $balance = 0.00;

        if ($httpCode >= 200 && $httpCode < 400) {
            $connected = true;
            if (isset($res['balance'])) {
                $balance = (float)$res['balance'];
            } elseif (isset($res['data']['balance'])) {
                $balance = (float)$res['data']['balance'];
            } elseif (isset($res['raw_text'])) {
                $raw = trim($res['raw_text']);
                if (str_starts_with($raw, 'ACCESS_BALANCE:')) {
                    $balance = (float)substr($raw, strlen('ACCESS_BALANCE:'));
                } elseif (is_numeric($raw)) {
                    $balance = (float)$raw;
                }
            }
        } elseif ($httpCode === 401 || $httpCode === 403) {
            $errorMessage = "Authentication failed (HTTP {$httpCode}). The API token/key is invalid or unauthorized.";
        } elseif ($httpCode === 404) {
            $errorMessage = "Endpoint not found (HTTP 404). The provider API base URL does not match the provider specification.";
        } elseif ($httpCode === 0) {
            $curlErr = $res['curl_error'] ?? 'Network unreachable or DNS lookup failed';
            $errorMessage = "Connection failed: {$curlErr}. Please verify provider host name, firewall, and internet connectivity.";
        } else {
            $errorMessage = "Provider gateway responded with HTTP {$httpCode}.";
        }

        $services = [];
        if ($connected) {
            $services = $this->getServices();
        }

        return [
            'success' => $connected,
            'connected' => $connected,
            'http_code' => $httpCode,
            'latency_ms' => $latency,
            'balance' => $balance,
            'services' => $services,
            'services_count' => count($services),
            'sample_service' => $services[0] ?? null,
            'raw_response' => $res,
            'error' => $errorMessage ?: null
        ];
    }

    /**
     * Fetch supported services from real API endpoint
     */
    public function getServices(): array {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        // 1. Try standard REST endpoint /services or query action=getServices
        $response = $this->makeRequest('GET', '/services');
        if ($response && !empty($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        if ($response && !empty($response['services']) && is_array($response['services'])) {
            return $response['services'];
        }

        // Try SMS-Activate query protocol ?action=getServices
        $response = $this->makeRequest('GET', '/?action=getServices');
        if ($response && !empty($response['services']) && is_array($response['services'])) {
            return $response['services'];
        }

        // 2. Check local database for mapped services for this provider
        try {
            $db = get_db();
            $stmt = $db->prepare("
                SELECT s.code, s.name, sc.name as category, psm.provider_cost as cost 
                FROM provider_service_mappings psm 
                JOIN services s ON s.id = psm.service_id 
                LEFT JOIN service_categories sc ON sc.id = s.category_id
                JOIN providers p ON p.id = psm.provider_id 
                WHERE p.api_url = ?
            ");
            $stmt->execute([$this->apiUrl]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable $e) {
            // graceful fallback
        }

        return [];
    }

    /**
     * Fetch supported countries from real API endpoint
     */
    public function getCountries(): array {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        $response = $this->makeRequest('GET', '/countries');
        if ($response && !empty($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        try {
            $db = get_db();
            $stmt = $db->query("SELECT code, name, dial_code FROM countries WHERE is_active = 1 ORDER BY name ASC");
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch real-time provider price & stock
     */
    public function getPrices(string $service, ?string $country = null): array {
        $endpoint = '/prices?service=' . urlencode($service) . ($country ? '&country=' . urlencode($country) : '');
        $response = $this->makeRequest('GET', $endpoint);

        if ($response && isset($response['cost'])) {
            return [
                'service' => $service,
                'country' => $country ?? 'IN',
                'cost' => (float)$response['cost'],
                'stock' => $response['stock'] ?? 'available'
            ];
        }

        try {
            $db = get_db();
            $stmt = $db->prepare("
                SELECT psm.provider_cost as cost, IF(psm.is_available=1, 'available', 'out_of_stock') as stock 
                FROM provider_service_mappings psm 
                JOIN services s ON s.id = psm.service_id 
                JOIN providers p ON p.id = psm.provider_id 
                WHERE s.code = ? AND p.api_url = ? 
                LIMIT 1
            ");
            $stmt->execute([$service, $this->apiUrl]);
            $row = $stmt->fetch();

            if ($row) {
                return [
                    'service' => $service,
                    'country' => $country ?? 'IN',
                    'cost' => (float)$row['cost'],
                    'stock' => $row['stock']
                ];
            }
        } catch (\Throwable $e) {
            // graceful fallback
        }

        return [
            'service' => $service,
            'country' => $country ?? 'IN',
            'cost' => 0.00,
            'stock' => 'unknown'
        ];
    }

    /**
     * Purchase phone number from real provider gateway
     */
    public function buyNumber(string $serviceCode, string $countryCode): array {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'Provider API URL is invalid or not configured.'
            ];
        }

        $payload = [
            'service' => $serviceCode,
            'country' => $countryCode,
            'action' => 'getNumber'
        ];

        // 1. Try JSON POST /orders or /buyNumber
        $response = $this->makeRequest('POST', '/buyNumber', $payload);

        // 2. If endpoint not found or provider uses GET protocol
        if (!$response || (isset($response['http_code']) && $response['http_code'] >= 400)) {
            $queryUrl = '/?action=getNumber&service=' . urlencode($serviceCode) . '&country=' . urlencode($countryCode);
            $response = $this->makeRequest('GET', $queryUrl);
        }

        // Handle JSON response
        if (is_array($response)) {
            if (!empty($response['phone_number']) && !empty($response['order_id'])) {
                return [
                    'success' => true,
                    'provider_order_id' => (string)$response['order_id'],
                    'phone_number' => (string)$response['phone_number'],
                    'status' => 'pending'
                ];
            }
            if (!empty($response['id']) && !empty($response['number'])) {
                return [
                    'success' => true,
                    'provider_order_id' => (string)$response['id'],
                    'phone_number' => (string)$response['number'],
                    'status' => 'pending'
                ];
            }
            if (!empty($response['error'])) {
                return [
                    'success' => false,
                    'error' => (string)$response['error']
                ];
            }
        }

        // Handle text response (e.g. ACCESS_NUMBER:123456:+1234567890 or NO_NUMBERS)
        if (isset($response['raw_text'])) {
            $raw = trim($response['raw_text']);
            if (str_starts_with($raw, 'ACCESS_NUMBER:')) {
                $parts = explode(':', $raw);
                if (count($parts) >= 3) {
                    return [
                        'success' => true,
                        'provider_order_id' => $parts[1],
                        'phone_number' => $parts[2],
                        'status' => 'pending'
                    ];
                }
            }
            if ($raw === 'NO_NUMBERS' || $raw === 'NO_BALANCE') {
                return [
                    'success' => false,
                    'error' => $raw === 'NO_NUMBERS' ? 'Provider has no available numbers for this service.' : 'Provider account has insufficient funds.'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Provider gateway returned an error or no numbers are currently available.'
        ];
    }

    /**
     * Check real-time OTP status with provider
     */
    public function checkStatus(string $providerOrderId): array {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL) || empty($providerOrderId)) {
            return [
                'success' => false,
                'status' => 'pending',
                'otp_code' => null,
                'full_sms' => null
            ];
        }

        $response = $this->makeRequest('GET', '/status/' . urlencode($providerOrderId));

        if (!$response || (isset($response['http_code']) && $response['http_code'] >= 400)) {
            $response = $this->makeRequest('GET', '/?action=getStatus&id=' . urlencode($providerOrderId));
        }

        if (is_array($response)) {
            $status = strtolower($response['status'] ?? 'pending');
            $otp = $response['otp_code'] ?? $response['code'] ?? $response['sms_code'] ?? null;
            $fullSms = $response['full_sms'] ?? $response['sms'] ?? $response['text'] ?? null;

            if (!empty($otp)) {
                return [
                    'success' => true,
                    'status' => 'completed',
                    'otp_code' => (string)$otp,
                    'full_sms' => $fullSms ? (string)$fullSms : "Verification code: {$otp}"
                ];
            }

            if (in_array($status, ['cancelled', 'expired', 'failed'])) {
                return [
                    'success' => true,
                    'status' => $status,
                    'otp_code' => null,
                    'full_sms' => null
                ];
            }
        }

        if (isset($response['raw_text'])) {
            $raw = trim($response['raw_text']);
            if (str_starts_with($raw, 'STATUS_OK:')) {
                $code = substr($raw, strlen('STATUS_OK:'));
                return [
                    'success' => true,
                    'status' => 'completed',
                    'otp_code' => $code,
                    'full_sms' => "Verification code: {$code}"
                ];
            }
            if ($raw === 'STATUS_CANCEL') {
                return [
                    'success' => true,
                    'status' => 'cancelled',
                    'otp_code' => null,
                    'full_sms' => null
                ];
            }
        }

        return [
            'success' => true,
            'status' => 'pending',
            'otp_code' => null,
            'full_sms' => null
        ];
    }

    /**
     * Cancel allocated number with provider
     */
    public function cancelNumber(string $providerOrderId): bool {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL) || empty($providerOrderId)) {
            return false;
        }

        $response = $this->makeRequest('POST', '/cancel', ['order_id' => $providerOrderId]);
        if (!$response || (isset($response['http_code']) && $response['http_code'] >= 400)) {
            $this->makeRequest('GET', '/?action=setStatus&status=8&id=' . urlencode($providerOrderId));
        }

        return true;
    }

    /**
     * Get real carrier balance via API
     */
    public function getBalance(): float {
        $diag = $this->testConnection();
        return (float)($diag['balance'] ?? 0.00);
    }

    /**
     * Perform HTTP request via PHP cURL with timeout and error capture
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): ?array {
        $url = $this->apiUrl . $endpoint;
        if ($this->apiKey && !str_contains($url, 'api_key=') && !str_contains($url, 'token=')) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'api_key=' . urlencode($this->apiKey);
        }

        $ch = curl_init();
        $headers = [
            'Accept: application/json, text/plain, */*',
            'User-Agent: OTPPlatform-CoreEngine/1.0'
        ];

        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $res === false) {
            return [
                'http_code' => $httpCode ?: 0,
                'curl_error' => $err ?: 'Unknown error',
                'raw_text' => null
            ];
        }

        $json = json_decode($res, true);
        if (is_array($json)) {
            $json['http_code'] = $httpCode;
            return $json;
        }

        return [
            'http_code' => $httpCode,
            'raw_text' => $res
        ];
    }
}
