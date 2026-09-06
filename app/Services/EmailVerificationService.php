<?php

namespace App\Services;

class EmailVerificationService
{
    /**
     * Verifica la validez del formato y la existencia del dominio DNS/MX del correo.
     *
     * @param string $email
     * @return array
     */
    public static function verificarDominio(string $email): array
    {
        $email = trim($email);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'email' => $email,
                'domain' => '',
                'has_mx' => false,
                'has_a' => false,
                'reason' => 'invalid_format',
                'message' => 'El formato de correo electrónico es inválido.',
            ];
        }

        $parts = explode('@', $email);
        $domain = strtolower(trim(end($parts)));

        if (empty($domain)) {
            return [
                'valid' => false,
                'email' => $email,
                'domain' => '',
                'has_mx' => false,
                'has_a' => false,
                'reason' => 'missing_domain',
                'message' => 'El correo electrónico no especifica un dominio.',
            ];
        }

        // Permitir dominios locales en entornos de pruebas/desarrollo local estricto
        if (in_array($domain, ['localhost', 'example.com', 'test.com']) || str_ends_with($domain, '.test') || str_ends_with($domain, '.local')) {
            return [
                'valid' => true,
                'email' => $email,
                'domain' => $domain,
                'has_mx' => true,
                'has_a' => true,
                'reason' => 'local_dev',
                'message' => 'Dominio local o de prueba aceptado.',
            ];
        }

        // Comprobación DNS: Registros MX (Mail Exchange)
        $hasMx = false;
        if (function_exists('checkdnsrr')) {
            $hasMx = @checkdnsrr($domain, 'MX');
        }

        // Si no tiene registro MX, verificar registro A (servidor host directo)
        $hasA = false;
        if (!$hasMx && function_exists('checkdnsrr')) {
            $hasA = @checkdnsrr($domain, 'A') || @checkdnsrr($domain, 'AAAA');
        }

        $isValid = $hasMx || $hasA;

        if ($isValid) {
            return [
                'valid' => true,
                'email' => $email,
                'domain' => $domain,
                'has_mx' => $hasMx,
                'has_a' => $hasA,
                'reason' => 'ok',
                'message' => $hasMx 
                    ? "Dominio @{$domain} válido con servidores de correo (MX) activos." 
                    : "Dominio @{$domain} válido con dirección IP de host (A) activa.",
            ];
        }

        return [
            'valid' => false,
            'email' => $email,
            'domain' => $domain,
            'has_mx' => false,
            'has_a' => false,
            'reason' => 'no_dns_mx',
            'message' => "El dominio @{$domain} no existe o no cuenta con servidores de correo (MX) activos.",
        ];
    }
}
