# Orbit — landing page e backend Pix

O site público é uma landing page estática em `index.html`, pronta para deploy na Vercel. O backend Pix está em PHP e precisa ser hospedado em um serviço com PHP e PostgreSQL; a Vercel não executa esses arquivos PHP como funções nativas.

## Deploy da landing page na Vercel

1. Importe `nextgenlancamento/orbit-site` na Vercel.
2. Selecione a branch `main` e deixe o diretório raiz como `./`.
3. Use o preset `Other` (ou deixe a Vercel detectar o site estático) e não configure comandos de build ou instalação.
4. Faça um novo deploy. A página servida é `index.html`.

O arquivo `.vercelignore` exclui `api/`, `lib/`, `database/` e arquivos PHP do deploy estático. Isso evita que código PHP seja publicado como texto e não altera os arquivos no GitHub.

## Backend PHP/Pix

Os endpoints PHP deste repositório não serão executados pela Vercel. Para usar cobranças Pix, hospede o backend em um provedor compatível com PHP 8.1+, cURL, PDO PostgreSQL e PostgreSQL. Configure o document root e as rotas para publicar somente os endpoints em `api/`; não exponha `.env`, `lib/` ou `database/` na web.

Configure no ambiente do backend as variáveis de `.env.example`, incluindo `PIX_BASE_URL`, credenciais do provedor, `DATABASE_URL`, `DATABASE_USER`, `DATABASE_PASSWORD`, `PIX_WEBHOOK_SECRET` e `CAMPAIGN_FEE`. Não coloque credenciais no frontend ou nas variáveis públicas da Vercel.

Endpoints do backend:

- `GET /api/health.php`
- `POST /api/create-charge.php`
- `GET /api/pix-status.php?txid=IDENTIFICADOR`
- `POST /api/pix-webhook.php`

A integração de pagamento só funcionará depois que o backend for hospedado, configurado com credenciais reais e adaptado/testado conforme a documentação do provedor Pix. O HTML atual é uma landing page institucional e ainda não contém um formulário de cobrança conectado a esses endpoints.

## Segurança

- Credenciais Pix anteriormente compartilhadas devem ser revogadas/rotacionadas antes do uso.
- Use HTTPS e proteção contra abuso no host do backend.
- Informe preços e condições de forma verdadeira e clara.
