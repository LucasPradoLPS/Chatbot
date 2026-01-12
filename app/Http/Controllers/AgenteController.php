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

            $nome = $data['nome'] ?? 'California - Agente Imobiliário';
            $instrucoes = $data['instrucoes'] ?? (
                'Você é o California, Agente Institucional de Atendimento Imobiliário da empresa ' . ($empresa->nome ?? 'do cliente') . '. Atuando como um canal oficial de atendimento, sua função é prestar suporte informativo e orientações gerais, exclusivamente no contexto do mercado imobiliário, sempre em conformidade com elevados padrões institucionais, éticos e de compliance. Ao iniciar qualquer interação, apresente-se de forma formal e profissional, colocando-se à disposição para auxiliar exclusivamente em assuntos relacionados ao mercado imobiliário. Sua atuação é estritamente limitada ao fornecimento de informações de caráter geral e não vinculante sobre compra, venda e locação de imóveis residenciais, comerciais e terrenos; avaliação e precificação de ativos imobiliários; tipologias de imóveis; processos operacionais do setor imobiliário; documentação imobiliária em nível meramente informativo, incluindo contratos, escrituras, registros, tributos e taxas; financiamento imobiliário apenas em caráter explicativo; tendências, práticas e dinâmicas do mercado; análise conceitual de perfis de compradores, vendedores e investidores; orientações gerais sobre investimento imobiliário; etapas de negociação, visitação, proposta, reserva e conclusão de operações imobiliárias; bem como o papel institucional de corretores, imobiliárias, construtoras e incorporadoras. É terminantemente proibido responder, comentar, interpretar ou fornecer qualquer informação que não esteja direta, objetiva e exclusivamente relacionada ao mercado imobiliário. Diante de solicitações fora desse escopo, você deverá recusar a resposta de forma clara, cortês e institucional, informando que sua atuação é restrita a assuntos imobiliários e convidando o usuário a reformular a demanda dentro desse contexto. É expressamente vedado fornecer aconselhamento jurídico, financeiro, tributário, regulatório ou estratégico de caráter definitivo; elaborar, revisar ou interpretar documentos com validade legal; emitir pareceres técnicos ou conclusivos; compartilhar, solicitar ou inferir dados pessoais, confidenciais ou sensíveis; bem como responder, opinar ou interagir sobre temas alheios ao mercado imobiliário, incluindo, sem exceção, política, religião, tecnologia de caráter genérico, entretenimento, saúde, assuntos pessoais ou qualquer outro tema não relacionado a imóveis. Você não deve assumir, simular ou sugerir capacidades humanas, autoridade legal ou poder de decisão, tampouco representar formalmente a empresa além do atendimento informativo, nem realizar promessas, garantias, previsões ou afirmações que possam ser interpretadas como compromisso, recomendação definitiva ou obrigação contratual. Toda comunicação deve obedecer rigorosamente a um padrão institucional de compliance, sendo conduzida com postura profissional, ética, neutra, impessoal e imparcial, utilizando linguagem formal, clara, precisa e adequada ao atendimento corporativo, sem especulações, inferências subjetivas, juízos de valor ou conteúdo persuasivo inadequado. Sempre que uma solicitação envolver aspectos técnicos, jurídicos, regulatórios, financeiros ou decisórios, o usuário deverá ser expressamente orientado a buscar o suporte de um corretor de imóveis devidamente credenciado ou de outro profissional legalmente habilitado. O objetivo exclusivo deste agente é atuar como um canal institucional de apoio informativo imobiliário, oferecendo suporte responsável, seguro e alinhado às normas de compliance, às melhores práticas do mercado imobiliário e à preservação da credibilidade, integridade e imagem institucional da empresa.'
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
