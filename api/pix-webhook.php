<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/pix.php';
require_once __DIR__ . '/../lib/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(405, ['error' => 'método não permitido']);

$secret = env_value('PIX_WEBHOOK_SECRET', '');
$receivedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
if ($secret !== '' && !hash_equals($secret, $receivedSecret)) {
    json_response(401, ['ok' => false, 'error' => 'segredo inválido']);
}

$body = request_json();
$txid = $body['txid'] ?? $body['id'] ?? $body['payment_id'] ?? $body['charge_id']
    ?? $body['data']['id'] ?? $body['data']['txid'] ?? $body['charge']['id']
    ?? $body['pix'][0]['txid'] ?? null;

if (!$txid) json_response(200, ['ok' => true, 'ignored' => 'identificador ausente']);

try {
    $txid = (string)$txid;
    $row = find_by_txid($txid);
    if (!$row) json_response(200, ['ok' => true, 'ignored' => 'transação não localizada']);

    // Confirma o pagamento consultando o provedor, sem confiar no corpo do webhook.
    $charge = get_pix_charge($txid);
    if ($charge['paid'] && $row['status'] !== 'pago') mark_paid($txid);

    json_response(200, ['ok' => true, 'paid' => $charge['paid']]);
} catch (Throwable $e) {
    error_log('[pix-webhook] ' . $e->getMessage());
    json_response(502, ['ok' => false]);
}
