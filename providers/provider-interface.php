<?php
/**
 * Provider Interface
 * All OTP providers must implement this contract
 */

interface ProviderInterface {
    public function getServices(): array;
    public function getCountries(): array;
    public function getPrices(string $service, ?string $country = null): array;
    public function buyNumber(string $serviceCode, string $countryCode): array;
    public function checkStatus(string $providerOrderId): array;
    public function cancelNumber(string $providerOrderId): bool;
    public function getBalance(): float;
    public function testConnection(): array;
}
