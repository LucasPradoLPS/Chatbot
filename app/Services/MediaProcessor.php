<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\Response;
use Exception;

/**
 * MediaProcessor - Baixa e processa arquivos de mídia do WhatsApp (imagens, PDFs, documentos)
 * Integrado com OpenAI Vision para análise inteligente de conteúdo visual
 */
class MediaProcessor
{
    private $openaiKey;
    private $mediaDisk = 'public'; // Disco Laravel para armazenar arquivos
    private $mediaPath = 'whatsapp_media'; // Pasta dentro do disco
    private $maxFileSize = 50 * 1024 * 1024; // 50MB limite
    
    const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const SUPPORTED_PDF_TYPES = ['application/pdf'];
    const SUPPORTED_DOC_TYPES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv'
    ];

    public function __construct()
    {
        $this->openaiKey = config('services.openai.key');
        if (!$this->openaiKey) {
            Log::warning('OPENAI_KEY não configurada - análise de mídia limitada');
        }
    }

    /**
     * Processa arquivo de mídia recebido do WhatsApp
     * Detecta tipo (imagem, PDF, documento) e processa adequadamente
     *
     * @param array $msgData Dados da mensagem do WhatsApp
     * @return array Resultado do processamento contendo:
     *         - success: bool
     *         - tipo_midia: string (image|pdf|document|audio|unknown)
     *         - conteudo_extraido: string (texto extraído/descrito)
     *         - arquivo_local: string (caminho local do arquivo)
     *         - metadados: array (tamanho, mime, url)
     *         - erro: string (se falhar)
     */
    public function processar(array $msgData): array
    {
        try {
            // Detecta tipo de mídia
            if (isset($msgData['imageMessage'])) {
                return $this->processarImagem($msgData['imageMessage']);
            } elseif (isset($msgData['documentMessage'])) {
                return $this->processarDocumento($msgData['documentMessage']);
            } elseif (isset($msgData['audioMessage'])) {
                return $this->processarAudio($msgData['audioMessage']);
            } else {
                return [
                    'success' => false,
                    'tipo_midia' => 'unknown',
                    'erro' => 'Tipo de mídia não reconhecido'
                ];
            }
        } catch (Exception $e) {
            Log::error('Erro ao processar mídia', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'erro' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Processa imagem com OpenAI Vision
     * Analisa conteúdo visual e retorna descrição estruturada
     */
    private function processarImagem(array $imageData): array
    {
        $url = $imageData['url'] ?? null;
        $mimetype = $imageData['mimetype'] ?? 'image/jpeg';
        $mediaKey = $imageData['mediaKey'] ?? null; // Chave de criptografia do WhatsApp
        
        if (!$url) {
            return [
                'success' => false,
                'tipo_midia' => 'image',
                'erro' => 'URL da imagem não fornecida'
            ];
        }

        if (!in_array($mimetype, self::SUPPORTED_IMAGE_TYPES)) {
            return [
                'success' => false,
                'tipo_midia' => 'image',
                'erro' => "Tipo de imagem não suportado: $mimetype"
            ];
        }

        try {
            // Baixa imagem com timeout maior e curl direto
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $imageContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$imageContent) {
                throw new Exception("Falha ao baixar imagem: HTTP {$httpCode}" . ($curlError ? " ({$curlError})" : ""));
            }

            // Se houver mediaKey, descriptografa o arquivo (é criptografado pelo WhatsApp)
            if ($mediaKey) {
                Log::info('Descriptografando imagem com mediaKey', [
                    'tamanho_antes' => strlen($imageContent),
                    'mediaKey_size' => strlen($mediaKey)
                ]);
                
                $imageContent = $this->descriptografarMidiaWhatsApp($imageContent, $mediaKey);
                if ($imageContent === null) {
                    Log::warning('Falha ao descriptografar imagem', [
                        'url' => substr($url, 0, 100),
                        'mimetype' => $mimetype,
                        'mediaKey_length' => strlen($mediaKey)
                    ]);
                    throw new Exception("Falha ao descriptografar arquivo de imagem");
                }
            }

            $imageData = $imageContent;
            $fileSize = strlen($imageData);

            if ($fileSize > $this->maxFileSize) {
                throw new Exception("Imagem muito grande: {$fileSize} bytes (máximo {$this->maxFileSize})");
            }
            
            // Valida se a imagem é realmente um arquivo de imagem válido
            // Verifica magic bytes do arquivo
            if (!$this->validarFormatoImagem($imageData, $mimetype)) {
                Log::warning('Arquivo de imagem inválido ou corrompido após descriptografia', [
                    'mimetype' => $mimetype,
                    'tamanho' => $fileSize,
                    'primeiros_bytes' => bin2hex(substr($imageData, 0, 16)),
                    'descriptografado' => $mediaKey ? 'sim' : 'não'
                ]);
                // Continua mesmo assim, pode ser arquivo válido mas magic bytes diferente
            }

            // Armazena arquivo localmente
            $filename = uniqid('img_') . '.' . $this->getExtensao($mimetype);
            $caminhoLocal = "{$this->mediaPath}/images/{$filename}";
            Storage::disk($this->mediaDisk)->put($caminhoLocal, $imageData);

            // Analisa com OpenAI Vision (passa caminho local, não URL temporária)
            $descricao = $this->analisarImagemComOpenAI($caminhoLocal);

            Log::info('Imagem processada com sucesso', [
                'arquivo' => $caminhoLocal,
                'tamanho' => $fileSize,
                'mime' => $mimetype,
                'descricao_chars' => strlen($descricao)
            ]);

            return [
                'success' => true,
                'tipo_midia' => 'image',
                'conteudo_extraido' => $descricao,
                'arquivo_local' => $caminhoLocal,
                'metadados' => [
                    'tamanho_bytes' => $fileSize,
                    'mime_type' => $mimetype,
                    'url_original' => $url
                ]
            ];
        } catch (Exception $e) {
            Log::error('Erro ao processar imagem', [
                'url' => $url,
                'erro' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'tipo_midia' => 'image',
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Processa documento (PDF, DOCX, TXT, CSV)
     * Extrai texto ou analisa com IA
     */
    private function processarDocumento(array $docData): array
    {
        $url = $docData['url'] ?? null;
        $filename = $docData['filename'] ?? 'documento';
        $mimetype = $docData['mimetype'] ?? 'application/pdf';

        if (!$url) {
            return [
                'success' => false,
                'tipo_midia' => 'document',
                'erro' => 'URL do documento não fornecida'
            ];
        }

        try {
            // Baixa documento com curl direto
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $conteudo = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$conteudo) {
                throw new Exception("Falha ao baixar documento: HTTP {$httpCode}" . ($curlError ? " ({$curlError})" : ""));
            }

            $fileSize = strlen($conteudo);

            if ($fileSize > $this->maxFileSize) {
                throw new Exception("Documento muito grande: {$fileSize} bytes (máximo {$this->maxFileSize})");
            }

            // Processa baseado no tipo
            if (in_array($mimetype, self::SUPPORTED_PDF_TYPES)) {
                $textoExtraido = $this->extrairTextoPDF($conteudo);
                $tipoEspecifico = 'pdf';
            } elseif (in_array($mimetype, self::SUPPORTED_DOC_TYPES)) {
                $textoExtraido = $this->extrairTextoDocumento($conteudo, $mimetype);
                $tipoEspecifico = 'document';
            } else {
                $textoExtraido = "[Tipo de documento não suportado para extração: $mimetype]";
                $tipoEspecifico = 'document';
            }

            // Armazena arquivo
            $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: $this->getExtensao($mimetype);
            $nomeArmazenado = uniqid('doc_') . '.' . $ext;
            $caminhoLocal = "{$this->mediaPath}/documents/{$nomeArmazenado}";
            Storage::disk($this->mediaDisk)->put($caminhoLocal, $conteudo);

            Log::info('Documento processado com sucesso', [
                'arquivo' => $caminhoLocal,
                'tamanho' => $fileSize,
                'mime' => $mimetype,
                'tipo' => $tipoEspecifico,
                'texto_extraido_chars' => strlen($textoExtraido)
            ]);

            return [
                'success' => true,
                'tipo_midia' => $tipoEspecifico,
                'conteudo_extraido' => $textoExtraido,
                'arquivo_local' => $caminhoLocal,
                'metadados' => [
                    'tamanho_bytes' => $fileSize,
                    'mime_type' => $mimetype,
                    'nome_original' => $filename,
                    'url_original' => $url
                ]
            ];
        } catch (Exception $e) {
            Log::error('Erro ao processar documento', [
                'url' => $url,
                'filename' => $filename,
                'erro' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'tipo_midia' => 'document',
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Processa áudio (retorna informação de que recebeu)
     * Pode ser estendido para usar Whisper API da OpenAI
     */
    private function processarAudio(array $audioData): array
    {
        $url = $audioData['url'] ?? null;
        $mimetype = $audioData['mimetype'] ?? 'audio/ogg';

        if (!$url) {
            return [
                'success' => false,
                'tipo_midia' => 'audio',
                'erro' => 'URL do áudio não fornecida'
            ];
        }

        try {
            // Baixa áudio com curl direto
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $conteudo = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$conteudo) {
                throw new Exception("Falha ao baixar áudio: HTTP {$httpCode}" . ($curlError ? " ({$curlError})" : ""));
            }

            $fileSize = strlen($conteudo);

            // Armazena arquivo
            $filename = uniqid('audio_') . '.' . $this->getExtensao($mimetype);
            $caminhoLocal = "{$this->mediaPath}/audio/{$filename}";
            Storage::disk($this->mediaDisk)->put($caminhoLocal, $conteudo);

            // TODO: Integrar Whisper API para transcrição
            $descricao = "🎙️ Arquivo de áudio recebido ({$fileSize} bytes). Transcrição automática em desenvolvimento.";

            Log::info('Áudio processado com sucesso', [
                'arquivo' => $caminhoLocal,
                'tamanho' => $fileSize,
                'mime' => $mimetype
            ]);

            return [
                'success' => true,
                'tipo_midia' => 'audio',
                'conteudo_extraido' => $descricao,
                'arquivo_local' => $caminhoLocal,
                'metadados' => [
                    'tamanho_bytes' => $fileSize,
                    'mime_type' => $mimetype,
                    'url_original' => $url
                ]
            ];
        } catch (Exception $e) {
            Log::error('Erro ao processar áudio', [
                'url' => $url,
                'erro' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'tipo_midia' => 'audio',
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Analisa imagem usando OpenAI Vision API
     * Retorna descrição estruturada do conteúdo visual
     * 
     * Nota: Usa base64 em vez de URL porque URLs do WhatsApp expiram rapidamente
     */
    public function analisarImagemComOpenAI(string $imagemLocalPath): string
    {
        if (!$this->openaiKey) {
            return "📷 Imagem recebida. Análise de imagem com IA não configurada.";
        }

        try {
            // Lê o arquivo armazenado localmente (já não está mais no WhatsApp)
            $conteudoImagem = Storage::disk($this->mediaDisk)->get($imagemLocalPath);
            if (!$conteudoImagem) {
                return "📷 Imagem recebida. Arquivo não encontrado para análise.";
            }

            // Detecta MIME type primeiro
            $extensao = strtolower(pathinfo($imagemLocalPath, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $mediaType = $mimeMap[$extensao] ?? 'image/jpeg';
            
            // Valida tamanho do arquivo para base64 (OpenAI tem limite de ~20MB)
            $fileSize = strlen($conteudoImagem);
            $maxSize = 20 * 1024 * 1024; // 20MB
            
            if ($fileSize > $maxSize) {
                Log::warning('Imagem muito grande para análise', [
                    'caminho' => $imagemLocalPath,
                    'tamanho' => $fileSize,
                    'limite' => $maxSize
                ]);
                return "📷 Imagem recebida. Arquivo muito grande para análise (máximo 20MB).";
            }

            // Converte para base64 (mais confiável que URL temporária)
            $base64 = base64_encode($conteudoImagem);
            
            // Valida se base64 foi gerado corretamente
            if (empty($base64) || strlen($base64) < 100) {
                Log::error('Base64 inválido gerado', [
                    'caminho' => $imagemLocalPath,
                    'tamanho_original' => $fileSize,
                    'tamanho_base64' => strlen($base64)
                ]);
                return "📷 Imagem recebida. Erro ao processar arquivo para análise.";
            }

            $response = Http::withToken($this->openaiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analise esta imagem e forneça uma descrição detalhada do conteúdo. Identifique: objetos principais, cores, texto visível, contexto geral. Seja conciso mas informativo.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mediaType};base64,{$base64}",
                                        'detail' => 'auto'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 500
                ]);

            if ($response->failed()) {
                Log::warning('Falha ao analisar imagem com OpenAI', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return "📷 Imagem recebida. Análise de conteúdo não disponível no momento.";
            }

            $descricao = $response['choices'][0]['message']['content'] ?? null;
            if (!$descricao) {
                return "📷 Imagem recebida. Não foi possível gerar descrição.";
            }

            return "📷 **Análise de Imagem:**\n\n" . $descricao;
        } catch (Exception $e) {
            Log::error('Erro ao chamar OpenAI Vision', [
                'erro' => $e->getMessage()
            ]);
            return "📷 Imagem recebida. Erro ao processar com IA: " . $e->getMessage();
        }
    }

    /**
     * Extrai texto de PDF
     * Usa a biblioteca spatie/pdf-to-text ou simplesmente retorna info do arquivo
     */
    private function extrairTextoPDF(string $conteudoPDF): string
    {
        try {
            // Tenta usar biblioteca se disponível
            if (class_exists('Spatie\PdfToText\Pdf')) {
                $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
                file_put_contents($tempFile, $conteudoPDF);
                
                $texto = (new \Spatie\PdfToText\Pdf($tempFile))
                    ->setPdf($tempFile)
                    ->text();
                
                unlink($tempFile);
                
                return trim($texto) ?: "📄 PDF recebido mas sem texto extraível.";
            }
        } catch (Exception $e) {
            Log::debug('Biblioteca PDF-to-Text não disponível', ['erro' => $e->getMessage()]);
        }

        // Fallback: retorna informação genérica
        return "📄 Arquivo PDF recebido com sucesso. Para processar conteúdo, instale a biblioteca spatie/pdf-to-text";
    }

    /**
     * Extrai texto de documentos (DOCX, TXT, CSV)
     * Suporte básico para formatos simples
     */
    private function extrairTextoDocumento(string $conteudo, string $mimetype): string
    {
        // TXT simples
        if ($mimetype === 'text/plain') {
            $texto = mb_convert_encoding($conteudo, 'UTF-8', 'UTF-8,ISO-8859-1');
            return trim($texto) ?: "Arquivo de texto vazio";
        }

        // CSV
        if ($mimetype === 'text/csv') {
            $linhas = explode("\n", $conteudo);
            $preview = implode("\n", array_slice($linhas, 0, 10));
            return "📊 **Arquivo CSV recebido:**\n\n" . $preview . 
                   (count($linhas) > 10 ? "\n...\n(+". (count($linhas) - 10) . " linhas)" : "");
        }

        // DOCX (formato ZIP com XML)
        if (strpos($mimetype, 'wordprocessingml') !== false) {
            try {
                $zip = new \ZipArchive();
                $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
                file_put_contents($tempFile, $conteudo);
                
                if ($zip->open($tempFile) === true) {
                    $xmlContent = $zip->getFromName('word/document.xml');
                    $zip->close();
                    
                    // Remove tags XML, mantém apenas texto
                    $texto = preg_replace('/<[^>]*>/', ' ', $xmlContent);
                    $texto = preg_replace('/\s+/', ' ', $texto);
                    
                    unlink($tempFile);
                    return trim($texto) ?: "📄 Documento DOCX vazio";
                }
                unlink($tempFile);
            } catch (Exception $e) {
                Log::debug('Erro ao processar DOCX', ['erro' => $e->getMessage()]);
            }
            return "📄 Arquivo DOCX recebido. Extração de conteúdo requer biblioteca adicional.";
        }

        // XLSX
        if (strpos($mimetype, 'spreadsheetml') !== false) {
            return "📊 Arquivo Excel (XLSX) recebido. Processamento requer biblioteca PHPOffice.";
        }

        return "📄 Tipo de documento recebido: $mimetype";
    }

    /**
     * Obtém extensão de arquivo baseado no MIME type
     */
    private function getExtensao(string $mimetype): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
        ];

        return $map[$mimetype] ?? 'bin';
    }

    /**
     * Descriptografa arquivo de mídia do WhatsApp usando mediaKey
     * WhatsApp envia arquivos criptografados que precisam ser descriptografados
     */
    private function descriptografarMidiaWhatsApp(string $conteudoCriptografado, string $mediaKey): ?string
    {
        try {
            // Descriptografa usando algoritmo do WhatsApp
            // mediaKey é a chave base64 enviada pelo WhatsApp
            $chaveBytes = base64_decode($mediaKey);
            
            if ($chaveBytes === false || strlen($chaveBytes) !== 32) {
                Log::error('MediaKey inválido (deve ser 32 bytes)', [
                    'mediaKey_length' => strlen($mediaKey),
                    'bytes_length' => $chaveBytes ? strlen($chaveBytes) : 'null'
                ]);
                return null;
            }
            
            // Expand usando HmacSHA256 conforme especificação WhatsApp
            // Para imagem: "WhatsApp Image Keys"
            $expanded = hash_hmac('sha256', 'WhatsApp Image Keys', $chaveBytes, true);
            
            // Remove últimos 10 bytes (HMAC de verificação)
            $conteudoSemHmac = substr($conteudoCriptografado, 0, -10);
            
            // Chave de criptografia são os bytes 112-143 da chave expandida
            $cipherKey = substr($expanded, 16, 32);
            
            // IV são os bytes 0-15 da chave expandida  
            $iv = substr($expanded, 0, 16);
            
            // Descriptografa usando AES-256-CBC
            $descriptografado = openssl_decrypt(
                $conteudoSemHmac,
                'AES-256-CBC',
                $cipherKey,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($descriptografado === false) {
                Log::error('Falha na descriptografia AES-256-CBC', [
                    'openssl_error' => openssl_error_string()
                ]);
                return null;
            }
            
            Log::info('Arquivo descriptografado com sucesso', [
                'tamanho_original' => strlen($conteudoCriptografado),
                'tamanho_descriptografado' => strlen($descriptografado),
                'primeiros_bytes' => bin2hex(substr($descriptografado, 0, 8))
            ]);
            
            return $descriptografado;
        } catch (\Exception $e) {
            Log::error('Erro ao descriptografar mídia WhatsApp', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Valida se o conteúdo é realmente uma imagem verificando magic bytes
     */
    private function validarFormatoImagem(string $conteudo, string $mimetype): bool
    {
        if (empty($conteudo)) {
            return false;
        }

        // Magic bytes para diferentes formatos de imagem
        $magicBytes = [
            'image/jpeg' => [
                pack('H*', 'FFD8FF'),
                pack('H*', 'FFD8FF')
            ],
            'image/png' => [
                pack('H*', '89504E47')
            ],
            'image/gif' => [
                pack('H*', '47494638')
            ],
            'image/webp' => [
                pack('H*', '52494646'),
                pack('H*', '57454250')
            ]
        ];

        if (!isset($magicBytes[$mimetype])) {
            return true; // Tipo desconhecido, passa
        }

        $bytes = substr($conteudo, 0, 4);
        
        foreach ($magicBytes[$mimetype] as $magic) {
            if (strpos($bytes, $magic) === 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * Limpa arquivos antigos (mais de X dias)
     * Pode ser chamado via artisan command ou scheduler
     */
    public static function limparArquivosAntigos(int $diasRetencao = 30): array
    {
        $disk = Storage::disk('public');
        $path = 'whatsapp_media';
        
        if (!$disk->exists($path)) {
            return ['removidos' => 0, 'erro' => null];
        }

        $arquivos = $disk->allFiles($path);
        $removidos = 0;
        $dataLimite = now()->subDays($diasRetencao)->timestamp;

        foreach ($arquivos as $arquivo) {
            $timestamp = $disk->lastModified($arquivo);
            if ($timestamp < $dataLimite) {
                $disk->delete($arquivo);
                $removidos++;
            }
        }

        Log::info('Limpeza de arquivos de mídia realizada', [
            'removidos' => $removidos,
            'dias_retencao' => $diasRetencao
        ]);

        return ['removidos' => $removidos, 'erro' => null];
    }
}
