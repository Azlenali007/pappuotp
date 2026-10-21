<?php
/**
 * Input Validation & Sanitization Helpers
 */

function sanitize_string(?string $val): string {
    if ($val === null) return '';
    return trim(strip_tags($val));
}

function sanitize_email(?string $email): string {
    if ($email === null) return '';
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL) ?: '';
}

function validate_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password(string $password, int $minLength = 8): bool {
    return strlen($password) >= $minLength;
}

function sanitize_int(mixed $val): int {
    return (int)$val;
}

function sanitize_float(mixed $val): float {
    return round((float)$val, 2);
}
