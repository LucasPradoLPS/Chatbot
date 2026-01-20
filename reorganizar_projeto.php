#!/usr/bin/env php
<?php

/**
 * Script de Reorganização do Projeto
 * Copia arquivos para novas pastas mantendo originals intactos
 * Data: 20/01/2026
 */

$baseDir = __DIR__;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  📁 REORGANIZADOR DE PROJETO - Chatbot Laravel             ║\n";
echo "║     Movendo arquivos para estrutura organizada             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Mapeamento de arquivo => pasta destino
$mapping = [
    // DOCUMENTAÇÃO - GUIAS
    'DOCUMENTACAO_COMPLETA.md' => 'docs/guias/',
    'README.md' => 'docs/guias/',
    'COMECE_AQUI.md' => 'docs/guias/',
    'INDICE_DOCUMENTACAO.md' => 'docs/guias/',
    'TESTING_GUIDE.md' => 'docs/guias/',
    'QUICK_START_8_PILARES.md' => 'docs/guias/',

    // DOCUMENTAÇÃO - SETUP
    'SETUP.md' => 'docs/setup/',

    // DOCUMENTAÇÃO - INTEGRAÇÃO
    'RESUMO_8_PILARES.md' => 'docs/integracao/',
    'GUIA_INTEGRACAO_MELHORIAS.md' => 'docs/integracao/',
    'INDICE_8_PILARES.md' => 'docs/integracao/',
    'ENTREGA_FINAL_8_PILARES.md' => 'docs/integracao/',
    'GOOGLE_MAPS_INTEGRATION.md' => 'docs/integracao/',
    'OPCOES_PAGAMENTO_README.md' => 'docs/integracao/',
    'MEDIA_PROCESSOR_README.md' => 'docs/integracao/',
    'MEDIA_PROCESSOR_GUIA.md' => 'docs/integracao/',
    'MEDIA_PROCESSOR_IMPLEMENTACAO_COMPLETA.md' => 'docs/integracao/',
    'MATCHING_IMPLEMENTATION.md' => 'docs/integracao/',
    'README_VALIDACAO_CONTEXTUAL.md' => 'docs/integracao/',

    // DOCUMENTAÇÃO - PROBLEMAS
    'ANALISE_PROBLEMAS_CHATBOT.md' => 'docs/problemas/',
    'BUGS_CORRIGIDOS.md' => 'docs/problemas/',
    'DEBUG_LOGS_REPORT.md' => 'docs/problemas/',
    'BOT_STATUS_REPORT.md' => 'docs/problemas/',
    'FLUXO_PROBLEMAS_VISUAL.md' => 'docs/problemas/',
    'FIX_TIMEOUT_OPENAI.md' => 'docs/problemas/',
    'SOLUCAO_AGENTE_RESOLVIDA.md' => 'docs/problemas/',
    'MIDIA_CORRIGIDA.md' => 'docs/problemas/',
    'RESUMO_EXECUTIVO_PROBLEMAS.md' => 'docs/problemas/',
    'SISTEMA_MIDIA_ATIVO.md' => 'docs/problemas/',

    // DOCUMENTAÇÃO - EXEMPLOS
    'EXAMPLES_PRACTICAL.md' => 'docs/exemplos/',
    'MATCHING_ENGINE.md' => 'docs/exemplos/',
    'PROPOSTA_FLOW.md' => 'docs/exemplos/',
    'SAUDACAO_CODIGO_MODIFICADO.md' => 'docs/exemplos/',
    'SAUDACAO_COM_NOME.md' => 'docs/exemplos/',
    'SAUDACAO_EXECUTIVA.md' => 'docs/exemplos/',
    'SAUDACAO_EXEMPLO_PRATICO.md' => 'docs/exemplos/',
    'SAUDACAO_LOCALIZACAO_MUDANCAS.md' => 'docs/exemplos/',
    'SCORING_FORMULA.md' => 'docs/exemplos/',
    'OPCOES_PAGAMENTO_EXEMPLOS.md' => 'docs/exemplos/',
    'CATALOGO_MATCHING_README.md' => 'docs/exemplos/',
    'CATALOGO_MATCHING_CHECKLIST.md' => 'docs/exemplos/',
    'TESTE_MEDIA_RESULTADO.md' => 'docs/exemplos/',
    'TESTE_RESULTADO.md' => 'docs/exemplos/',
    'MELHORIAS_IMPLEMENTADAS.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_CHECKLIST.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_DIAGRAMAS.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_EXEMPLO_PRATICO.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_FIX.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_INDICE.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_RESUMO_MUDANCAS.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_START.md' => 'docs/exemplos/',
    'VALIDACAO_CONTEXTUAL_SUMARIO.md' => 'docs/exemplos/',
    'MEDIA_PROCESSOR_CONFIG.md' => 'docs/exemplos/',
    'MEDIA_PROCESSOR_FLUXO.md' => 'docs/exemplos/',
    'MEDIA_PROCESSOR_SUMARIO.md' => 'docs/exemplos/',
    'GUIA_TESTE_REAL.md' => 'docs/exemplos/',
    'SAUDACAO_MUDANCAS_RESUMO.md' => 'docs/exemplos/',
    'SAUDACAO_RESUMO_FINAL.md' => 'docs/exemplos/',
    'STRUCTURE_COMPLETE.md' => 'docs/exemplos/',
    'IMPLEMENTACAO_RESUMO.txt' => 'docs/exemplos/',

    // SCRIPTS - SETUP
    'setup_bot.php' => 'scripts/setup/',
    'setup_empresa.php' => 'scripts/setup/',
    'create_instance.php' => 'scripts/setup/',
    'create_assistant.php' => 'scripts/setup/',
    'ativar_instancia.php' => 'scripts/setup/',

    // SCRIPTS - DEBUG
    'debug_agente.php' => 'scripts/debug/',
    'debug_response.php' => 'scripts/debug/',
    'check_agentes_table.php' => 'scripts/debug/',
    'check_agents.php' => 'scripts/debug/',
    'check_assistant_ids.php' => 'scripts/debug/',
    'check_db.php' => 'scripts/debug/',
    'check_table_structure.php' => 'scripts/debug/',
    'verificar_instancia.php' => 'scripts/debug/',
    'verificar_thread.php' => 'scripts/debug/',
    'test_logs_debug.php' => 'scripts/debug/',
    'test_openai_api.php' => 'scripts/debug/',
    'test_openai_pure.php' => 'scripts/debug/',

    // SCRIPTS - TEST
    'test_8_pilares.php' => 'tests/unit/',
    'test_bot_correto.php' => 'tests/unit/',
    'test_http.php' => 'tests/unit/',
    'test_intervencao.php' => 'tests/unit/',
    'test_matching_engine.php' => 'tests/unit/',
    'test_media_direct.php' => 'tests/unit/',
    'test_media_processor.php' => 'tests/unit/',
    'test_media_webhook.php' => 'tests/unit/',
    'test_media.php' => 'tests/unit/',
    'test_pagamento.php' => 'tests/unit/',
    'test_saudacao.php' => 'tests/unit/',
    'test_saudacao_completo.php' => 'tests/unit/',
    'test_saudacao_com_nome.php' => 'tests/unit/',
    'test_saudacao_detect.php' => 'tests/unit/',
    'test_saudacao_n8n.php' => 'tests/unit/',
    'test_timeout_fix.php' => 'tests/unit/',
    'test_validacao_contextual.php' => 'tests/unit/',
    'testar_imagem.php' => 'tests/unit/',
    'testar_imagem_direto.php' => 'tests/unit/',
    'testar_imagem_final.php' => 'tests/unit/',
    'testar_imagem_simples.php' => 'tests/unit/',
    'testar_logica_horario.php' => 'tests/unit/',
    'testar_media_local.php' => 'tests/unit/',
    'testar_webhook.php' => 'tests/unit/',
    'testar_webhook_imagem.php' => 'tests/unit/',
    'demo_funcionamento.php' => 'tests/unit/',
    'GUIA_TESTE.php' => 'tests/unit/',
    'validar_implementacao.php' => 'tests/unit/',
    'teste_estresse_super_intenso.php' => 'tests/unit/',

    // SCRIPTS - BATCH
    'run_all.bat' => 'scripts/batch/',
    'run_all.ps1' => 'scripts/batch/',
    'start_server.bat' => 'scripts/batch/',
    'start_server.sh' => 'scripts/batch/',
    'start_worker.bat' => 'scripts/batch/',
    'test_bot.bat' => 'scripts/batch/',
    'test_bot.sh' => 'scripts/batch/',
    'test_completo.ps1' => 'scripts/batch/',
    'test_final.ps1' => 'scripts/batch/',
    'testar.bat' => 'scripts/batch/',
    'testar_horario.ps1' => 'scripts/batch/',
    'testar_saudacao.ps1' => 'scripts/batch/',
    'teste_estresse.ps1' => 'scripts/batch/',
    'enviar_mensagem.bat' => 'scripts/batch/',
    'enviar_mensagem.php' => 'scripts/batch/',
    'enviar_seu_numero.ps1' => 'scripts/batch/',
    'enviar_teste.ps1' => 'scripts/batch/',
    'fix_assistant_id.sh' => 'scripts/batch/',
    'fix_bom.ps1' => 'scripts/batch/',
    'fix_db.ps1' => 'scripts/batch/',
    'fix_assistant_id.php' => 'scripts/batch/',
    'fix_assistant_ids_db.php' => 'scripts/batch/',
    'remove_comments.ps1' => 'scripts/batch/',
    'remove_comments.py' => 'scripts/batch/',
    'relatorio_saudacoes.php' => 'scripts/batch/',
    'list_agents.php' => 'scripts/batch/',
    'resumo_horario.ps1' => 'scripts/batch/',
    'resumo_horario_simples.ps1' => 'scripts/batch/',
    'atualizar_prompt_completo.php' => 'scripts/batch/',
    'atualizar_prompt_saudacao.php' => 'scripts/batch/',
];

$copiedCount = 0;
$skippedCount = 0;
$errorCount = 0;

echo "📋 Iniciando cópia de arquivos...\n\n";

foreach ($mapping as $file => $destFolder) {
    $src = "$baseDir/$file";
    $dest = "$baseDir/$destFolder$file";

    // Criar pasta se não existir
    if (!is_dir("$baseDir/$destFolder")) {
        mkdir("$baseDir/$destFolder", 0755, true);
    }

    if (file_exists($src)) {
        if (!file_exists($dest)) {
            if (copy($src, $dest)) {
                echo "✅ Copiado: $file → $destFolder\n";
                $copiedCount++;
            } else {
                echo "❌ ERRO ao copiar: $file\n";
                $errorCount++;
            }
        } else {
            echo "⏭️  Já existe: $destFolder$file\n";
            $skippedCount++;
        }
    } else {
        echo "⚠️  Não encontrado: $file\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ REORGANIZAÇÃO CONCLUÍDA                                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Resumo:\n";
echo "   ✅ Copiados: $copiedCount\n";
echo "   ⏭️  Já existiam: $skippedCount\n";
echo "   ❌ Erros: $errorCount\n";

echo "\n✨ Os arquivos originais continuam na raiz do projeto!\n";
echo "   Você pode deletar manualmente quando quiser.\n\n";

echo "📁 Nova estrutura em: $baseDir\n";
echo "📖 Veja o mapa em: ESTRUTURA_PROJETO.md\n\n";
