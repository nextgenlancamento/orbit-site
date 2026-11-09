CREATE TABLE IF NOT EXISTS participacoes (
    id TEXT PRIMARY KEY,
    txid TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    email TEXT NOT NULL,
    modalidade TEXT NOT NULL DEFAULT 'pix',
    chave_pix TEXT,
    uf CHAR(2),
    respostas JSONB NOT NULL DEFAULT '{}'::jsonb,
    valor_taxa NUMERIC(12,2) NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    pago_em TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_participacoes_status ON participacoes(status);
