# CORREÇÃO: Bot não responde mensagens - Timeout da OpenAI

## Problema Identificado
O bot parava de responder devido a timeout de 60 segundos do PHP quando a OpenAI levava muito tempo para responder.

### Logs do erro:
```
[2026-01-05 19:18:39] local.ERROR: Maximum execution time of 60 seconds exceeded 
at ProcessWhatsappMessage.php:606
```

## Causa Raiz
O polling para aguardar a resposta da OpenAI estava configurado com:
- **Timeout do loop:** 60 segundos máximo
- **Intervalo entre checks:** 0.5 segundos
- **Máximo de iterações:** 60 tentativas
- **Problema:** Como o PHP tem max_execution_time padrão de 60s, quando o polling chegava perto desse tempo, o script era morto

## Soluções Implementadas

### 1. Reduzir tempo de polling ✅
**Arquivo:** `app/Jobs/ProcessWhatsappMessage.php`

**Mudanças:**
- Reduzir máximo de tentativas de 60 para 30 segundos
- Aumentar intervalo entre checks de 0.5s para 1s
- Adicionar timeout de 10s em cada requisição HTTP
- Adicionar tratamento de erros de conexão com retry (máx 3 falhas)

### 2. Aumentar timeout do PHP ✅
**Arquivo:** `public/.htaccess`

**Mudanças:**
```apache
<IfModule mod_php.c>
    php_value max_execution_time 120    # Aumentado de 60 para 120 segundos
    php_value default_socket_timeout 120
</IfModule>
```

### 3. Configuração de Segurança ✅
**Arquivo:** `app/Providers/AppServiceProvider.php`

**Mudanças:**
- Adicionar `set_time_limit(120)` no boot para garantir timeout de 120 segundos em qualquer contexto

## Resultado Esperado
Agora o bot:
- Aguarda resposta da OpenAI por até 30 segundos (ao invés de 60)
- PHP tem limite de 120 segundos total
- Mensagens que levem até 30 segundos para serem respondidas não serão interrompidas
- Melhor resiliência com retry automático em caso de falhas de conexão

## Como Testar
1. Enviar uma mensagem ao bot
2. Verificar nos logs se a resposta é recebida
3. Verificar em `storage/logs/laravel.log` se não há erro "Maximum execution time exceeded"

## Próximos Passos (Opcional)
- Se ainda houver timeout, aumentar o intervalo no polling (`usleep`) ou reduzir quantidade de tentativas
- Considerar usar jobs assíncronos ao invés de processar inline no webhook
- Implementar cache de respostas para reduzir chamadas à API
