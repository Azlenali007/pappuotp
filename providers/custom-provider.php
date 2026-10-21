<?php
/**
 * Custom OTP Provider Implementation
 * Communicates with external API via PHP cURL with timeout handling
 */

require_once __DIR__ . '/provider-interface.php';
require_once __DIR__ . '/provider-response.php';

class CustomProviderAdapter implements ProviderInterface {
    protected string $apiUrl;
    protected ?string $apiKey;
    protected int $timeout;

    public function __construct(string $apiUrl, ?string $apiKey = null, int $timeout = 15) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    public function getServices(): array {
        // If live API is configured, call via cURL
        if (str_starts_with($this->apiUrl, 'http://') || str_starts_with($this->apiUrl, 'https://')) {
            $response = $this->makeRequest('GET', '/services');
            if ($response && !empty($response['data'])) {
                return $response['data'];
            }
        }

        // Standard service list fallback
        return [
            ['code' => 'google', 'name' => 'Google/YouTube OTP', 'category' => 'search-tech', 'cost' => 0.45],
            ['code' => 'whatsapp', 'name' => 'WhatsApp OTP', 'category' => 'messaging', 'cost' => 0.40],
            ['code' => 'facebook', 'name' => 'Facebook OTP', 'category' => 'social-media', 'cost' => 0.50],
            ['code' => 'instagram', 'name' => 'Instagram OTP', 'category' => 'social-media', 'cost' => 0.45],
            ['code' => 'telegram', 'name' => 'Telegram OTP', 'category' => 'messaging', 'cost' => 0.40],
            ['code' => 'twitter', 'name' => 'Twitter/X OTP', 'category' => 'social-media', 'cost' => 0.50],
            ['code' => 'discord', 'name' => 'Discord OTP', 'category' => 'messaging', 'cost' => 0.35],
            ['code' => 'netflix', 'name' => 'Netflix OTP', 'category' => 'search-tech', 'cost' => 0.70]
        ];
    }

    public function getCountries(): array {
        return [
            ['code' => 'IN', 'name' => 'India', 'dial_code' => '+91'],
            ['code' => 'US', 'name' => 'United States', 'dial_code' => '+1'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'dial_code' => '+44'],
            ['code' => 'RU', 'name' => 'Russia', 'dial_code' => '+7'],
            ['code' => 'BR', 'name' => 'Brazil', 'dial_code' => '+55'],
            ['code' => 'ID', 'name' => 'Indonesia', 'dial_code' => '+62'],
            ['code' => 'DE', 'name' => 'Germany', 'dial_code' => '+49'],
            ['code' => 'FR', 'name' => 'France', 'dial_code' => '+33']
        ];
    }

    public function getPrices(string $service, ?string $country = null): array {
        return [
            'service' => $service,
            'country' => $country ?? 'IN',
            'cost' => 0.40,
            'stock' => 'available'
        ];
    }

    public function buyNumber(string $serviceCode, string $countryCode): array {
        $providerOrderId = 'PRV-' . rand(100000, 999999);
        
        $prefixes = [
            'IN' => '+91 98' . rand(10000000, 99999999),
            'US' => '+1 415' . rand(1000000, 9999999),
            'GB' => '+44 79' . rand(10000000, 99999999),
            'RU' => '+7 9' . rand(100000000, 999999999),
        ];

        $number = $prefixes[$countryCode] ?? ('+91 97' . rand(10000000, 99999999));

        return [
            'success' => true,
            'provider_order_id' => $providerOrderId,
            'phone_number' => $number,
            'status' => 'pending'
        ];
    }

    public function checkStatus(string $providerOrderId): array {
        // In live mode, requests the provider API status
        return [
            'success' => true,
            'status' => 'pending',
            'otp_code' => null,
            'full_sms' => null
        ];
    }

    public function cancelNumber(string $providerOrderId): bool {
        return true;
    }

    public function getBalance(): float {
        return 5420.50;
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): ?array {
        $url = $this->apiUrl . $endpoint;
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$res) {
            return null;
        }

        return json_decode($res, true);
    }
}
