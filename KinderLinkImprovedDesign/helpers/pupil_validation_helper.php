<?php

function normalize_pupil_text($value) {
    return trim(preg_replace('/\s+/', ' ', (string) $value));
}

function pupil_age_from_birthdate($birthdate) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $birthdate)) {
        return null;
    }

    try {
        $birth = new DateTime($birthdate);
        $today = new DateTime(date('Y-m-d'));
    } catch (Exception $e) {
        return null;
    }

    if ($birth > $today) {
        return null;
    }

    return (int) $birth->diff($today)->y;
}

function validate_pupil_payload($data) {
    $errors = [];
    $namePattern = '/^[A-Za-z ]+$/';
    $textPattern = '/^[A-Za-z0-9 ,.\\-]*$/';

    foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'guardian_name' => 'Guardian name'] as $field => $label) {
        if (($data[$field] ?? '') === '' || !preg_match($namePattern, $data[$field])) {
            $errors[] = "$label must contain letters and spaces only.";
        }
    }

    if (($data['home_address'] ?? '') === '' || !preg_match($textPattern, $data['home_address'])) {
        $errors[] = "Home address can only contain letters, numbers, spaces, comma, period, and hyphen.";
    }

    if (($data['health_notes'] ?? '') !== '' && !preg_match($textPattern, $data['health_notes'])) {
        $errors[] = "Health notes can only contain letters, numbers, spaces, comma, period, and hyphen.";
    }

    if (!in_array($data['gender'] ?? '', ['Male', 'Female'], true)) {
        $errors[] = "Please select a valid gender.";
    }

    if (!is_int($data['age']) || $data['age'] < 4 || $data['age'] > 5) {
        $errors[] = "Age must be between 4 and 5.";
    }

    $birthdateAge = pupil_age_from_birthdate($data['birthdate'] ?? '');
    if ($birthdateAge === null) {
        $errors[] = "Please enter a valid birthdate.";
    } elseif (is_int($data['age']) && $birthdateAge !== $data['age']) {
        $errors[] = "Birthdate must match the pupil's age.";
    }

    if (!preg_match('/^\d{11}$/', $data['contact_number'] ?? '')) {
        $errors[] = "Contact number must be exactly 11 digits.";
    }

    return $errors;
}

