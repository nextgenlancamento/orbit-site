# Campanha — backend PHP com integração Pix

Este pacote converte os trechos de backend enviados para PHP e remove dependências de Node.js/Vercel.

## Estrutura

- `api/`: endpoints HTTP em PHP.
- `lib/`: integração Pix, persistência e inicialização.
- `database/schema.sql`: tabela PostgreSQL.
- `.env.example`: modelo de configuração.

## Requisitos

- PHP 8.1 ou superior
- Extensões PHP `curl`, `json`, `pdo` e `pdo_pgsql`
- PostgreSQL
- Um provedor Pix com API HTTP

## Instalação

1. Envie a pasta para uma hospedagem com PHP.
2. Copie `.env.example` para `.env`.
3. Preencha as credenciais reais no `.env` do servidor. Não envie esse arquivo ao Git nem ao frontend.
4. Configure `DATABASE_URL`, `DATABASE_USER` e `DATABASE_PASSWORD`.
5. Execute `database/schema.sql` no PostgreSQL.
6. Configure o servidor para apontar para esta pasta e bloquear acesso público a `.env`, `lib/` e `database/`. O ideal é usar `public/` como document root e configurar as rotas para os arquivos de `api/`.
7. Configure no painel do provedor o webhook para `POST /api/pix-webhook.php` e use o mesmo segredo em `PIX_WEBHOOK_SECRET`.

## Endpoints

- `GET /api/health.php`
- `POST /api/create-charge.php`
- `GET /api/pix-status.php?txid=IDENTIFICADOR`
- `POST /api/pix-webhook.php`

O corpo de `create-charge.php` aceita JSON com `participation_id`, `name`, `email`, `modality`, `pix_key`, `uf` e `answers`. O valor é definido exclusivamente no servidor por `CAMPAIGN_FEE`.

## Importante sobre o provedor

O adaptador mantém os caminhos e o formato genérico que estavam nos trechos originais (`/oauth/token`, `/v1/pix/charges` e `/v1/pix/charges/{id}`). Cada instituição pode exigir autenticação, campos, cabeçalhos, formato de valor e resposta diferentes. Portanto, **a integração só estará pronta para produção depois de adaptar e testar `lib/pix.php` com a documentação oficial do seu provedor**.

O pacote não simula pagamentos nem marca cobranças como pagas por tempo decorrido. O status pago só é reconhecido após consulta ao provedor. O webhook também consulta o provedor; não confia apenas no conteúdo recebido.

Use a campanha apenas com informações verdadeiras, preço/taxa claramente informados e sem sugerir vínculo ou endosso de uma marca sem autorização. Não prometa recompensa ou reembolso que não esteja efetivamente disponível e descrito em termos claros.

## Segurança

- As credenciais que foram coladas na conversa devem ser consideradas expostas. Revogue-as/rotacione-as no painel do provedor antes de usar credenciais novas.
- Nunca coloque credenciais Pix no JavaScript/frontend.
- Use HTTPS.
- Configure limites de requisição e proteção contra abuso na hospedagem.
- Não exponha `phpinfo()` nem mensagens internas de erro.
