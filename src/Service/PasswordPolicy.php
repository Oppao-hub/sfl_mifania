<?php

namespace App\Service;

/**
 * Central password rules for web forms and mobile API.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public const COMPLEXITY_PATTERN = '/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_])/';

    public const COMPLEXITY_MESSAGE = 'Password must contain an uppercase letter, a lowercase letter, a number, and a symbol.';

    public const MIN_LENGTH_MESSAGE = 'Password must be at least 8 characters long.';

    public function validate(string $password): ?string
    {
        if ($password === '') {
            return 'Password is required.';
        }

        if (\strlen($password) < self::MIN_LENGTH) {
            return self::MIN_LENGTH_MESSAGE;
        }

        if (!preg_match(self::COMPLEXITY_PATTERN, $password)) {
            return self::COMPLEXITY_MESSAGE;
        }

        return null;
    }

    public function assertValid(string $password): void
    {
        $message = $this->validate($password);

        if ($message !== null) {
            throw new \InvalidArgumentException($message);
        }
    }

    public function assertMatching(string $password, string $confirmPassword, string $mismatchMessage = 'The password fields must match.'): void
    {
        if ($password !== $confirmPassword) {
            throw new \InvalidArgumentException($mismatchMessage);
        }
    }
}
