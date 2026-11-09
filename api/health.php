<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/pix.php';
require_once __DIR__ . '/../lib/db.php';

json_response(200, [
    'ok' => true,
    'mode' => pix_configured() ? 'live' : 'not_configured',
    'hasCredentials' => (bool)(env_value('PIX_CLIENT_KEY') && env_value('PIX_CLIENT_SECRET')),
    'hasBaseUrl' => (bool)env_value('PIX_BASE_URL'),
    'hasWebhookSecret' => (bool)env_value('PIX_WEBHOOK_SECRET'),
    'hasDatabase' => (bool)env_value('DATABASE_URL'),
    'php' => PHP_VERSION,
]);
