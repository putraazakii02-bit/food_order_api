<?php

class Auth {
    public static function userId(): ?int {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
            return null;
        }

        $decoded = base64_decode($matches[1], true);
        if ($decoded === false) {
            return null;
        }

        $parts = explode(':', $decoded, 3);
        return isset($parts[0]) && ctype_digit($parts[0])
            ? (int)$parts[0]
            : null;
    }
}
