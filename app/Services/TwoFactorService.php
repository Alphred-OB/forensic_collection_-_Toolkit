<?php

namespace App\Services;

class TwoFactorService
{
    /**
     * Base32 character alphabet
     */
    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a secret key for TOTP authenticator app.
     */
    public static function generateSecretKey(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Calculate current 6-digit TOTP code for secret key at current or given timestamp.
     */
    public static function getCode(string $secret, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $timeSlice = floor($timestamp / 30);
        $secretKey = self::base32Decode($secret);

        // Pack time into 8-byte binary string (big-endian)
        $timePacked = pack('N*', 0) . pack('N*', $timeSlice);

        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $timePacked, $secretKey, true);

        // Dynamic truncation
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);

        $value = unpack('N', $hashpart);
        $value = $value[1] & 0x7FFFFFFF;

        $modulo = pow(10, 6);
        return str_pad((string)($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify user-provided 6-digit TOTP code with time drift window.
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTime = time();
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTime + ($i * 30));
            if (hash_equals($calculatedCode, trim($code))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate 8-digit emergency recovery codes array.
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = sprintf('%04d-%04d', random_int(1000, 9999), random_int(1000, 9999));
        }
        return $codes;
    }

    /**
     * Internal Base32 decoder implementation.
     */
    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        if (empty($secret)) {
            return '';
        }

        $binary = '';
        for ($i = 0; $i < strlen($secret); $i++) {
            $position = strpos(self::$base32Chars, $secret[$i]);
            if ($position !== false) {
                $binary .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
            }
        }

        $binaryArray = str_split($binary, 8);
        $result = '';
        foreach ($binaryArray as $byte) {
            if (strlen($byte) === 8) {
                $result .= chr(bindec($byte));
            }
        }

        return $result;
    }
}
