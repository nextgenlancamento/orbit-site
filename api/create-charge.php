<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/pix.php';
require_once __DIR__ . '/../lib/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(405, ['error' => 'método não permitido']);
$body = request_json();
$id = trim((string)($body['participation_id'] ?? ''));
$name = trim((string)($body['name'] ?? ''));
$email = trim((string)($body['email'] ?? ''));

if ($id === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(400, ['error' => 'dados obrigatórios ausentes ou inválidos']);
}

$fee = (float)env_value('CAMPAIGN_FEE', '0');
if ($fee <= 0) json_response(500, ['error' => 'taxa não configurada no servidor']);

try {
    $charge = create_pix_charge([
        'amount' => $fee,
        'description' => 'Taxa informada da campanha',
        'reference' => $id,
        'payer' => ['name' => $name, 'email' => $email],
    ]);

    save_charge([
        'id' => $id,
        'txid' => $charge['txid'],
        'name' => $name,
        'email' => $email,
        'modality' => (string)($body['modality'] ?? 'pix'),
        'pixKey' => (string)($body['pix_key'] ?? ''),
        'uf' => (string)($body['uf'] ?? ''),
        'answers' => $body['answers'] ?? [],
        'amount' => $fee,
    ]);

    json_response(201, [
        'txid' => $charge['txid'],
        'pix_copy_paste' => $charge['code'],
        'amount' => $fee,
    ]);
} catch (Throwable $e) {
    error_log('[pix-create] ' . $e->getMessage());
    json_response(502, ['error' => 'não foi possível gerar a cobrança Pix']);
}
