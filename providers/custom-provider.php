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

    public function __construct(string $apiUrl, ?string $apiKey = null, int $timeout = 15) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Fetch supported services from real API endpoint
     */
    public function getServices(): array {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        // Try standard REST endpoint /services or query action=getServices
        $response = $this->makeRequest('GET', '/services');
        if ($response && !empty($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        if ($response && !empty($response['services']) && is_array($response['services'])) {
            return $response['services'];
        }

        // Check provider_services in database for this provider
        $db = get_db();
        $stmt = $db->prepare("
            SELECT s.code, s.name, s.category, ps.provider_cost as cost 
            FROM provider_services ps 
            JOIN services s ON s.id = ps.service_id 
            JOIN providers p ON p.id = ps.provider_id 
            WHERE p.api_url = ?
        ");
        $stmt->execute([$this->apiUrl]);
        $rows = $stmt->fetchAll();
        if (!empty($rows)) {
            return $rows;
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

        $db = get_db();
        $stmt = $db->query("SELECT code, name, dial_code FROM countries WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll() ?: [];
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

        // Check from provider_services table
        $db = get_db();
        $stmt = $db->prepare("
            SELECT ps.provider_cost as cost, ps.stock_status as stock 
            FROM provider_services ps 
            JOIN services s ON s.id = ps.service_id 
            JOIN providers p ON p.id = ps.provider_id 
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

        // Send purchase request to external provider gateway
        $payload = [
            'service' => $serviceCode,
            'country' => $countryCode,
            'action' => 'getNumber'
        ];

        // 1. Try JSON POST /orders or /buyNumber
        $response = $this->makeRequest('POST', '/buyNumber', $payload);

        // 2. If endpoint not found or provider uses SMS-Activate GET protocol
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
            'error' => 'Provider gateway returned an unrecognized response or no numbers are available.'
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

        // Query status from provider
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

        // Text protocol (e.g. STATUS_OK:123456 or STATUS_WAIT_CODE or STATUS_CANCEL)
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
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return 0.00;
        }

        $response = $this->makeRequest('GET', '/balance');

        if (!$response || (isset($response['http_code']) && $response['http_code'] >= 400)) {
            $response = $this->makeRequest('GET', '/?action=getBalance');
        }

        if (is_array($response)) {
            if (isset($response['balance'])) {
                return (float)$response['balance'];
            }
            if (isset($response['data']['balance'])) {
                return (float)$response['data']['balance'];
            }
        }

        if (isset($response['raw_text'])) {
            $raw = trim($response['raw_text']);
            if (str_starts_with($raw, 'ACCESS_BALANCE:')) {
                return (float)substr($raw, strlen('ACCESS_BALANCE:'));
            }
            if (is_numeric($raw)) {
                return (float)$raw;
            }
        }

        return 0.00;
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
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
            error_log("Provider cURL failure [{$url}]: {$err}");
            return null;
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
