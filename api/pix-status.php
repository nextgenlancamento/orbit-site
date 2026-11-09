<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/pix.php';
require_once __DIR__ . '/../lib/db.php';

$txid = trim((string)($_GET['tx'] ?? $_GET['txid'] ?? ''));
if ($txid === '') json_response(400, ['error' => 'txid ausente']);

try {
    $row = find_by_txid($txid);
    if (!$row) json_response(404, ['paid' => false, 'status' => 'not_found']);

    $charge = get_pix_charge($txid);
    if ($charge['paid'] && $row['status'] !== 'pago') mark_paid($txid);

    json_response(200, ['paid' => $charge['paid'], 'status' => $charge['status'], 'mode' => 'live']);
} catch (Throwable $e) {
    error_log('[pix-status] ' . $e->getMessage());
    json_response(502, ['paid' => false, 'status' => 'erro']);
}
