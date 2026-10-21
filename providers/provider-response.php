<?php
/**
 * Provider Response Structure
 */

class ProviderResponse {
    public bool $success;
    public ?string $orderId;
    public ?string $phoneNumber;
    public ?string $otpCode;
    public ?string $fullSms;
    public string $status; // pending, completed, cancelled, failed
    public ?string $error;
    public array $rawData;

    public function __construct(
        bool $success,
        string $status = 'pending',
        ?string $phoneNumber = null,
        ?string $orderId = null,
        ?string $otpCode = null,
        ?string $fullSms = null,
        ?string $error = null,
        array $rawData = []
    ) {
        $this->success = $success;
        $this->status = $status;
        $this->phoneNumber = $phoneNumber;
        $this->orderId = $orderId;
        $this->otpCode = $otpCode;
        $this->fullSms = $fullSms;
        $this->error = $error;
        $this->rawData = $rawData;
    }

    public function toArray(): array {
        return [
            'success'      => $this->success,
            'status'       => $this->status,
            'phone_number' => $this->phoneNumber,
            'order_id'     => $this->orderId,
            'otp_code'     => $this->otpCode,
            'full_sms'     => $this->fullSms,
            'error'        => $this->error
        ];
    }
}
