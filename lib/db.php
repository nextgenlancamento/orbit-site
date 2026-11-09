<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function database(): ?PDO {
    static $pdo = null;
    static $initialized = false;
    if ($initialized) return $pdo;
    $initialized = true;

    $dsn = env_value('DATABASE_URL');
    if (!$dsn) return null;

    $user = env_value('DATABASE_USER', '');
    $password = env_value('DATABASE_PASSWORD', '');
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function save_charge(array $data): void {
    $pdo = database();
    if (!$pdo) throw new RuntimeException('Banco de dados não configurado.');
    $sql = "INSERT INTO participacoes
        (id, txid, nome, email, modalidade, chave_pix, uf, respostas, valor_taxa, status)
        VALUES (:id, :txid, :nome, :email, :modalidade, :chave_pix, :uf, :respostas, :valor_taxa, 'pendente')
        ON CONFLICT (id) DO UPDATE SET
        txid = EXCLUDED.txid, nome = EXCLUDED.nome, email = EXCLUDED.email,
        modalidade = EXCLUDED.modalidade, chave_pix = EXCLUDED.chave_pix,
        uf = EXCLUDED.uf, respostas = EXCLUDED.respostas,
        valor_taxa = EXCLUDED.valor_taxa, status = 'pendente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $data['id'],
        ':txid' => $data['txid'],
        ':nome' => $data['name'],
        ':email' => $data['email'],
        ':modalidade' => $data['modality'],
        ':chave_pix' => $data['pixKey'] ?: null,
        ':uf' => $data['uf'] ?: null,
        ':respostas' => json_encode($data['answers'] ?? [], JSON_UNESCAPED_UNICODE),
        ':valor_taxa' => $data['amount'],
    ]);
}

function find_by_txid(string $txid): ?array {
    $pdo = database();
    if (!$pdo) throw new RuntimeException('Banco de dados não configurado.');
    $stmt = $pdo->prepare('SELECT id, txid, modalidade, status FROM participacoes WHERE txid = :txid LIMIT 1');
    $stmt->execute([':txid' => $txid]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mark_paid(string $txid): void {
    $pdo = database();
    if (!$pdo) throw new RuntimeException('Banco de dados não configurado.');
    $stmt = $pdo->prepare("UPDATE participacoes SET status = 'pago', pago_em = NOW()
        WHERE txid = :txid AND status <> 'pago'");
    $stmt->execute([':txid' => $txid]);
}
