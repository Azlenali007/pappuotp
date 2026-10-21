<?php
/**
 * Payment Gateway Base Class
 */
abstract class PaymentGateway {
    protected PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    abstract public function createOrder(int $userId, float $amount, string $currency = 'INR'): array;
    abstract public function verifyPayment(array $payload): array;
}
