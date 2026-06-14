<?php
declare(strict_types=1);

/**
 * Sanitiza un string para prevenir XSS en formularios y salidas.
 */
function sanitize_input(string $data): string
{
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitiza recursivamente arrays (por ejemplo $_POST) y devuelve el array limpio.
 */
function sanitize_array(array $data): array
{
    $clean = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $clean[$key] = sanitize_array($value);
        } elseif (is_string($value)) {
            $clean[$key] = sanitize_input($value);
        } else {
            $clean[$key] = $value;
        }
    }
    return $clean;
}
