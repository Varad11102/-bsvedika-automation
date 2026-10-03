<?php

declare(strict_types=1);

namespace BSVedika;

final class Validation
{
    public static function registration(array $input): array
    {
        $required = [
            'name', 'age', 'sect', 'subsect', 'gothram', 'fname',
            'mobile', 'email', 'address', 'aadhar_last4', 'photo_url', 'consent',
        ];

        $errors = [];
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        $age = filter_var($input['age'] ?? null, FILTER_VALIDATE_INT);
        if ($age === false || $age < 1 || $age > 120) {
            $errors['age'] = 'Age must be between 1 and 120.';
        }

        if (!preg_match('/^[0-9]{10}$/', (string) ($input['mobile'] ?? ''))) {
            $errors['mobile'] = 'Mobile number must contain exactly 10 digits.';
        }

        if (!filter_var((string) ($input['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (!preg_match('/^[0-9]{4}$/', (string) ($input['aadhar_last4'] ?? ''))) {
            $errors['aadhar_last4'] = 'Provide only the last four Aadhaar digits.';
        }

        if (($input['consent'] ?? null) !== true) {
            $errors['consent'] = 'Consent is required.';
        }

        return $errors;
    }
}
