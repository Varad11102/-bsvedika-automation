<?php

declare(strict_types=1);

namespace BSVedika;

final class Validation
{
    public static function registration(array $input): array
    {
        $required = [
            'name', 'age', 'sect', 'subsect', 'gothram', 'fname',
            'mobile', 'email', 'address', 'aadhar_last4', 'photo_url',
            'userid', 'consent',
        ];

        $errors = [];
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        $limits = [
            'email' => 100, 'name' => 50, 'sect' => 50, 'subsect' => 50,
            'gothram' => 50, 'fname' => 50, 'mobile' => 15, 'userid' => 100,
            'age' => 6,
        ];
        foreach ($limits as $field => $limit) {
            if (mb_strlen(trim((string) ($input[$field] ?? ''))) > $limit) {
                $errors[$field] = "Must be at most {$limit} characters.";
            }
        }

        if (mb_strlen(trim((string) ($input['address'] ?? ''))) > 1000) {
            $errors['address'] = 'Address must be at most 1000 characters.';
        }

        $age = filter_var($input['age'] ?? null, FILTER_VALIDATE_INT);
        if ($age === false || $age < 1 || $age > 120) {
            $errors['age'] = 'Age must be between 1 and 120.';
        }

        if (!preg_match('/^\+?[0-9]{10,15}$/', (string) ($input['mobile'] ?? ''))) {
            $errors['mobile'] = 'Mobile number must contain 10 to 15 digits.';
        }

        if (!filter_var((string) ($input['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (!preg_match('/^[0-9]{4}$/', (string) ($input['aadhar_last4'] ?? ''))) {
            $errors['aadhar_last4'] = 'Provide only the last four Aadhaar digits.';
        }

        $photoUrl = (string) ($input['photo_url'] ?? '');
        if (!filter_var($photoUrl, FILTER_VALIDATE_URL) || !preg_match('/^https:\/\//i', $photoUrl)) {
            $errors['photo_url'] = 'Photo must use a valid HTTPS URL.';
        }

        if (($input['consent'] ?? null) !== true) {
            $errors['consent'] = 'Consent is required.';
        }

        return $errors;
    }
}
