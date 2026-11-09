<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function pix_configured(): bool {
    return (bool)(env_value('PIX_BASE_URL') && env_value('PIX_CLIENT_KEY') && env_value('PIX_CLIENT_SECRET'));
}

function pix_request(string $url, string $method = 'GET', array $headers = [], ?string $body = null): array {
    if (!function_exists('curl_init')) throw new RuntimeException('A extensão cURL do PHP não está instalada.');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false) throw new RuntimeException('Falha de comunicação com o provedor Pix: ' . $error);
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) throw new RuntimeException('Resposta inválida do provedor Pix (HTTP ' . $status . ').');
    return [$status, $decoded];
}

function pix_auth_token(): string {
    static $token = null;
    static $expiresAt = 0;
    if ($token && time() < $expiresAt - 30) return $token;

    $base = rtrim((string)env_value('PIX_BASE_URL'), '/');
    $path = env_value('PIX_AUTH_PATH', '/oauth/token');
    $form = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => env_value('PIX_CLIENT_KEY', ''),
        'client_secret' => env_value('PIX_CLIENT_SECRET', ''),
    ]);
    [$status, $data] = pix_request($base . $path, 'POST', [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ], $form);

    if ($status < 200 || $status >= 300) throw new RuntimeException('Falha na autenticação Pix (HTTP ' . $status . ').');
    $token = $data['access_token'] ?? $data['token'] ?? null;
    if (!$token) throw new RuntimeException('O provedor não retornou token de acesso.');
    $expiresAt = time() + (int)($data['expires_in'] ?? 3600);
    return (string)$token;
}

function nested_value(array $data, array $paths): mixed {
    foreach ($paths as $path) {
        $value = $data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) { $value = null; break; }
            $value = $value[$part];
        }
        if ($value !== null && $value !== '') return $value;
    }
    return null;
}

function create_pix_charge(array $input): array {
    if (!pix_configured()) throw new RuntimeException('Configure as credenciais e a URL do provedor Pix.');
    $base = rtrim((string)env_value('PIX_BASE_URL'), '/');
    $path = env_value('PIX_CHARGE_PATH', '/v1/pix/charges');
    $amount = (float)$input['amount'];
    $amountValue = env_value('PIX_AMOUNT_IN_CENTS', '0') === '1' ? (int)round($amount * 100) : $amount;

    $payload = [
        'amount' => $amountValue,
        'description' => $input['description'],
        'external_reference' => $input['reference'],
        'payer' => ['name' => $input['payer']['name'], 'email' => $input['payer']['email']],
    ];
    [$status, $data] = pix_request($base . $path, 'POST', [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . pix_auth_token(),
    ], json_encode($payload, JSON_UNESCAPED_UNICODE));

    if ($status < 200 || $status >= 300) throw new RuntimeException('Falha ao criar cobrança Pix (HTTP ' . $status . ').');
    $code = nested_value($data, [
        'pix_copy_paste', 'pix_code', 'copy_paste', 'emv', 'brcode',
        'qr_code', 'point_of_interaction.transaction_data.qr_code',
    ]);
    $txid = nested_value($data, ['id', 'txid', 'transaction_id', 'charge_id', 'reference']);
    if (!$code || !$txid) throw new RuntimeException('Resposta de cobrança não contém código Pix e identificador.');
    return ['txid' => (string)$txid, 'code' => (string)$code];
}

function get_pix_charge(string $txid): array {
    if (!pix_configured()) throw new RuntimeException('Configure as credenciais e a URL do provedor Pix.');
    $base = rtrim((string)env_value('PIX_BASE_URL'), '/');
    $path = str_replace('{id}', rawurlencode($txid), env_value('PIX_STATUS_PATH', '/v1/pix/charges/{id}'));
    [$status, $data] = pix_request($base . $path, 'GET', [
        'Accept: application/json',
        'Authorization: Bearer ' . pix_auth_token(),
    ]);
    if ($status < 200 || $status >= 300) return ['paid' => false, 'status' => 'http_' . $status];

    $rawStatus = (string)(nested_value($data, ['status', 'state', 'situacao']) ?? 'unknown');
    $paidStatuses = ['paid', 'approved', 'confirmed', 'completed', 'received', 'succeeded'];
    return ['paid' => in_array(strtolower($rawStatus), $paidStatuses, true), 'status' => $rawStatus];
}
