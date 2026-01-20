# Guia de Configuração - Chatbot Laravel

## Passos para fazer o projeto funcionar:

### 1. Instalar Dependências
```bash
composer install
```

### 2. Configurar Ambiente
O arquivo `.env` já foi criado. Configure as seguintes variáveis:

**Banco de Dados:**
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chatbot_laravel
DB_USERNAME=<seu_usuario_db>
DB_PASSWORD=<sua_senha_db>
```

**OpenAI:**
```
OPENAI_KEY=<sua_chave_openai>
```

**Evolution API:**
```
EVOLUTION_KEY=<sua_chave_evolution>
EVOLUTION_URL=<sua_url_evolution>
```

### 3. Gerar Chave da Aplicação
```bash
php artisan key:generate
```

### 4. Executar Migrations
```bash
php artisan migrate
```

Isso criará todas as tabelas necessárias:
- empresas
- instancia_whatsapps
- agentes
- agente_gerados
- mensagens_memorias
- ia_intervencoes
- threads
- mensagens
- jobs (para filas)
- cache
- sessions

### 5. Configurar Worker de Filas

O sistema usa filas para processar mensagens. Você precisa ter um worker rodando:

```bash
php artisan queue:work --queue=whatsapp
```

Ou configure um supervisor/systemd para manter o worker sempre rodando.

### 6. Configurar Webhook

Configure o webhook da Evolution API para apontar para:
```
POST http://seu-dominio.com/api/webhook/whatsapp
```

### 7. Dados Iniciais

Você precisará criar manualmente no banco de dados:
1. Uma **Empresa**
2. Uma **InstanciaWhatsapp** vinculada à empresa
3. Um **Agente** vinculado à empresa (com `ia_ativa = true`)
4. Um **AgenteGerado** com `funcao = 'atendente_ia'` e o `agente_base_id` da OpenAI

## Estrutura de Dados Esperada

### Empresa
- nome
- memoria_limite (padrão: 4)

### InstanciaWhatsapp
- instance_name (nome da instância na Evolution API)
- empresa_id

### Agente
- empresa_id
- ia_ativa (boolean)
- responder_grupo (boolean)

### AgenteGerado
- empresa_id
- funcao ('atendente_ia')
- agente_base_id (ID do Assistant na OpenAI)

## Testando

Após configurar tudo, você pode testar enviando uma requisição POST para o webhook:

```bash
curl -X POST http://localhost/api/webhook/whatsapp \
  -H "Content-Type: application/json" \
  -d '{
    "instance": "nome_da_instancia",
    "data": {
      "key": {
        "remoteJid": "5511999999999@s.whatsapp.net",
        "fromMe": false
      },
      "message": {
        "conversation": "Olá, preciso de ajuda"
      }
    }
  }'
```

## Troubleshooting

- **Erro de conexão com banco**: Verifique as credenciais no `.env`
- **Jobs não processam**: Certifique-se de que o worker está rodando
- **Erro OpenAI**: Verifique se a chave está correta e se tem créditos
- **Erro Evolution API**: Verifique URL e chave da API

## Leitura de Imagens e PDFs

- Imagens enviadas no WhatsApp agora são anexadas à IA (OpenAI Assistants v2) para interpretação visual.
- PDFs são lidos via extração de texto local com `pdftotext` se disponível.
- Documentos de texto (`.txt`, `.csv`) são lidos diretamente.
- Documentos Word (`.docx`) são convertidos para texto sem dependências externas (usa ZipArchive do PHP).
  - Para `.doc` (Word antigo), suporte opcional via utilitário `antiword` se instalado no PATH.

### Requisitos para PDF (Windows)

1. Instale o Poppler (contém `pdftotext`):
  - Baixe em: https://github.com/oschwartz10612/poppler-windows/releases/
  - Extraia e adicione o caminho da pasta `bin` ao `PATH` do Windows.
2. Reinicie o serviço/terminal para carregar o PATH.

Se `pdftotext` não estiver disponível, o bot avisará o usuário e pedirá o conteúdo em texto/imagem.

### Requisitos para DOCX/DOC

- `DOCX`: requer extensão `zip` do PHP habilitada (ZipArchive). Verifique em `php -m` se `zip` está listado.
- `DOC` (formato antigo): opcionalmente instale `antiword` e adicione ao PATH. Caso contrário, o bot pedirá o conteúdo em texto.
