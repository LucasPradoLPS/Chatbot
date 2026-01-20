# Chatbot Laravel - WhatsApp + OpenAI

Sistema de chatbot integrado com WhatsApp (via Evolution API) e OpenAI Assistants API.

## Requisitos

- PHP >= 8.1
- Composer
- PostgreSQL (ou MySQL/SQLite)
- Redis (opcional, para cache e filas)

## Instalação

1. Clone o repositório
2. Instale as dependências:
```bash
composer install
```

3. Copie o arquivo `.env.example` para `.env`:
```bash
copy .env.example .env
```

4. Gere a chave da aplicação:
```bash
php artisan key:generate
```

5. Configure o arquivo `.env` com suas credenciais:
- `DB_*` - Configurações do banco de dados
- `OPENAI_KEY` - Chave da API OpenAI
- `EVOLUTION_KEY` - Chave da Evolution API
- `EVOLUTION_URL` - URL da Evolution API

6. Execute as migrations:
```bash
php artisan migrate
```

7. Configure o servidor de filas (necessário para processar mensagens):
```bash
php artisan queue:work --queue=whatsapp
```

## Estrutura

- **Models**: Empresa, InstanciaWhatsapp, Agente, AgenteGerado, Thread, MensagensMemoria, IaIntervencao, Mensagem
- **Jobs**: ProcessWhatsappMessage - Processa mensagens recebidas do WhatsApp
- **Controllers**: WhatsappWebhookController - Recebe webhooks da Evolution API

## Endpoints

- `POST /api/webhook/whatsapp` - Webhook para receber mensagens do WhatsApp

## Configuração

O sistema usa filas para processar mensagens de forma assíncrona. Certifique-se de ter um worker de fila rodando:

```bash
php artisan queue:work --queue=whatsapp
```

## Variáveis de Ambiente Importantes

- `OPENAI_KEY` - Chave da API OpenAI (obrigatória)
- `EVOLUTION_KEY` - Chave da Evolution API (obrigatória)
- `EVOLUTION_URL` - URL base da Evolution API (obrigatória)
- `QUEUE_CONNECTION` - Driver de fila (database, redis, etc)
