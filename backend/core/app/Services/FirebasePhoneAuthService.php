<?php

namespace App\Services;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;
use RuntimeException;

class FirebasePhoneAuthService
{
    public function auth(): Auth
    {
        $credentials = $this->credentialsPath();

        return (new Factory())
            ->withServiceAccount($credentials)
            ->createAuth();
    }

    public function verifiedPhone(string $idToken): string
    {
        $token = $this->auth()->verifyIdToken($idToken);
        $phone = $token->claims()->get('phone_number');

        if (!is_string($phone) || $phone === '') {
            throw new RuntimeException('Firebase token does not contain a verified phone number');
        }

        return $this->normalize($phone);
    }

    public function phoneFor(object $account): string
    {
        $mobile = (string) ($account->mobile ?? $account->phone ?? '');
        $dialCode = (string) ($account->dial_code ?? '');

        if (str_starts_with(trim($mobile), '+')) {
            return $this->normalize($mobile);
        }

        $mobileDigits = preg_replace('/\D+/', '', $mobile) ?? '';
        $dialDigits = preg_replace('/\D+/', '', $dialCode) ?? '';

        if ($dialDigits !== '' && !str_starts_with($mobileDigits, $dialDigits)) {
            $mobileDigits = $dialDigits.$mobileDigits;
        }

        if ($mobileDigits === '') {
            throw new RuntimeException('The account does not have a valid phone number');
        }

        return '+'.$mobileDigits;
    }

    public function tokenMatchesAccount(string $idToken, object $account): bool
    {
        return hash_equals($this->phoneFor($account), $this->verifiedPhone($idToken));
    }

    public function findAccount(string $idToken, string $modelClass, string $phoneColumn = 'mobile'): ?object
    {
        $phone = $this->verifiedPhone($idToken);
        $digits = ltrim($phone, '+');
        $suffix = substr($digits, -9);

        return $modelClass::query()
            ->where($phoneColumn, 'like', '%'.$suffix)
            ->get()
            ->first(function ($account) use ($phone, $suffix, $phoneColumn) {
                try {
                    if (hash_equals($phone, $this->phoneFor($account))) {
                        return true;
                    }
                } catch (RuntimeException) {
                    // Fall through to accounts that store a local number only.
                }

                $stored = preg_replace('/\D+/', '', (string) ($account->{$phoneColumn} ?? '')) ?? '';
                $hasDialCode = preg_replace('/\D+/', '', (string) ($account->dial_code ?? '')) !== '';

                return !$hasDialCode && $stored !== '' && str_ends_with($stored, $suffix);
            });
    }

    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            throw new RuntimeException('Invalid phone number');
        }

        return '+'.$digits;
    }

    private function credentialsPath(): string
    {
        $relative = getFilePath('pushConfig').'/push_config.json';
        $candidates = [
            $relative,
            base_path('../'.$relative),
            public_path($relative),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Firebase service account push_config.json is not configured');
    }
}
