<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Agente;
use App\Models\AgenteGerado;
use App\Models\Empresa;
use App\Services\OpenAIService;

class AgenteController extends Controller
{
    /**
     * Cria um registro de agente para a empresa.
     */
    public function store(Request $request)
    {
        Log::info('AgenteController@store: request', ['body' => $request->all()]);
        try {
            $data = $request->validate([
                'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
                'ia_ativa' => ['sometimes', 'boolean'],
                'responder_grupo' => ['sometimes', 'boolean'],
            ]);

            $agente = Agente::create([
                'empresa_id' => $data['empresa_id'],
                'ia_ativa' => $data['ia_ativa'] ?? true,
                'responder_grupo' => $data['responder_grupo'] ?? false,
            ]);

            Log::info('AgenteController@store: created', ['agente' => $agente->toArray()]);
            header('Content-Type: application/json');
            header('HTTP/1.1 201 Created');
            die(json_encode($agente));
        } catch (\Throwable $e) {
            Log::error('AgenteController@store: error', ['erro' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            header('Content-Type: application/json');
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(['error' => $e->getMessage()]));
        }
    }

    /**
     * Gera um Assistant na OpenAI para atender como IA e salva em AgenteGerado.
     */
    public function generate(Request $request, OpenAIService $openAI)
    {
        Log::info('AgenteController@generate: request', ['body' => $request->all()]);
        try {
            $data = $request->validate([
                'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
                'nome' => ['sometimes', 'string'],
                'instrucoes' => ['sometimes', 'string'],
                'model' => ['sometimes', 'string'],
            ]);

            $empresa = Empresa::find($data['empresa_id']);
            if (!$empresa) {
                header('Content-Type: application/json');
                header('HTTP/1.1 404 Not Found');
                die(json_encode(['error' => 'Empresa não encontrada']));
            }

            $nome = $data['nome'] ?? 'Atendente IA';
            $instrucoes = $data['instrucoes'] ?? (
                'Você é um atendente virtual da empresa ' . ($empresa->nome ?? 'do cliente') .
                '. Responda com cordialidade, clareza e objetividade. '
                . 'Se recebido mídia, explique educadamente limitações e peça detalhes em texto. '
                . 'Priorize resolver dúvidas e encaminhar para humano quando necessário.'
            );

            $assistantId = $openAI->createAssistant($nome, $instrucoes, $data['model'] ?? null);

            $agenteGerado = AgenteGerado::create([
                'empresa_id' => $empresa->id,
                'funcao' => 'atendente_ia',
                'agente_base_id' => $assistantId,
            ]);

            Log::info('AgenteController@generate: success', ['assistant_id' => $assistantId, 'agente_gerado' => $agenteGerado->toArray()]);

            header('Content-Type: application/json');
            header('HTTP/1.1 201 Created');
            die(json_encode([
                'assistant_id' => $assistantId,
                'agente_gerado' => $agenteGerado,
            ]));
        } catch (\Throwable $e) {
            Log::error('AgenteController@generate: error', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            header('Content-Type: application/json');
            header('HTTP/1.1 500 Internal Server Error');
            die(json_encode(['error' => $e->getMessage()]));
        }
    }
}
