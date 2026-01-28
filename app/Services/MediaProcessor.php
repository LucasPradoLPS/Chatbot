<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class MediaProcessor
{
    private ?string $openaiKey;
    private string $mediaDisk = 'public';
    private string $mediaPath = 'whatsapp_media';
    private int $maxFileSize = 50 * 1024 * 1024; // 50MB
    private bool $verifySsl;
    
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

        // SSL: em produção, verifique; em dev pode desligar via env
        $this->verifySsl = (bool) (env('WHATSAPP_VERIFY_SSL', app()->environment('production')));
    }

    public function processar(array $msgData): array
    {
        try {
            if (isset($msgData['imageMessage'])) {
                return $this->processarImagem($msgData['imageMessage']);
            } elseif (isset($msgData['documentMessage'])) {
                return $this->processarDocumento($msgData['documentMessage']);
            } elseif (isset($msgData['audioMessage'])) {
                return $this->processarAudio($msgData['audioMessage']);
            }

            return [
                'success' => false,
                'tipo_midia' => 'unknown',
                'erro' => 'Tipo de mídia não reconhecido'
            ];
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

    private function processarImagem(array $imageData): array
    {
        $url      = $imageData['url'] ?? null;
        $mimetype = $imageData['mimetype'] ?? 'image/jpeg';
        $mediaKey = $imageData['mediaKey'] ?? null;

        if (!$url) {
            return ['success' => false, 'tipo_midia' => 'image', 'erro' => 'URL da imagem não fornecida'];
        }

        if (!in_array($mimetype, self::SUPPORTED_IMAGE_TYPES, true)) {
            return ['success' => false, 'tipo_midia' => 'image', 'erro' => "Tipo de imagem não suportado: $mimetype"];
        }

        try {
            $bin = $this->baixarComCurl($url);

            if (strlen($bin) > $this->maxFileSize) {
                throw new Exception("Imagem muito grande: " . strlen($bin) . " bytes (máximo {$this->maxFileSize})");
            }

            // Se houver mediaKey, tenta descriptografar corretamente
            if ($mediaKey) {
                $dec = $this->descriptografarMidiaWhatsApp($bin, $mediaKey, 'image');
                if ($dec !== null) {
                    $bin = $dec;
                    Log::info('Imagem descriptografada com sucesso', [
                        'tamanho_apos' => strlen($bin),
                        'primeiros_bytes' => bin2hex(substr($bin, 0, 16))
                    ]);
                } else {
                    Log::warning('Falha na descriptografia da imagem, continuando com binário original', [
                        'primeiros_bytes' => bin2hex(substr($bin, 0, 16))
                    ]);
                }
            }

            // Validar formato real (corrigido)
            if (!$this->validarFormatoImagem($bin, $mimetype)) {
                Log::warning('Arquivo de imagem parece inválido/corrompido', [
                    'mimetype' => $mimetype,
                    'tamanho' => strlen($bin),
                    'head12_hex' => bin2hex(substr($bin, 0, 12)),
                ]);
                // continua mesmo assim
            }

            $filename    = uniqid('img_') . '.' . $this->getExtensao($mimetype);
            $caminhoLocal = "{$this->mediaPath}/images/{$filename}";
            Storage::disk($this->mediaDisk)->put($caminhoLocal, $bin);

            $descricao = $this->analisarImagemComOpenAI($caminhoLocal);

            return [
                'success' => true,
                'tipo_midia' => 'image',
                'conteudo_extraido' => $descricao,
                'arquivo_local' => $caminhoLocal,
                'metadados' => [
                    'tamanho_bytes' => strlen($bin),
                    'mime_type' => $mimetype,
                    'url_original' => $url
                ]
            ];
        } catch (Exception $e) {
            Log::error('Erro ao processar imagem', ['url' => $url, 'erro' => $e->getMessage()]);
            return ['success' => false, 'tipo_midia' => 'image', 'erro' => $e->getMessage()];
        }
    }

    private function processarDocumento(array $docData): array
    {
        $url      = $docData['url'] ?? null;
        $filename = $docData['filename'] ?? 'documento';
        $mimetype = $docData['mimetype'] ?? 'application/pdf';
        $mediaKey = $docData['mediaKey'] ?? null;

        if (!$url) {
            return ['success' => false, 'tipo_midia' => 'document', 'erro' => 'URL do documento não fornecida'];
        }

        try {
            $bin = $this->baixarComCurl($url);

            if (strlen($bin) > $this->maxFileSize) {
                throw new Exception("Documento muito grande: " . strlen($bin) . " bytes (máximo {$this->maxFileSize})");
            }

            // Muitos providers também mandam document criptografado
            if ($mediaKey) {
                $dec = $this->descriptografarMidiaWhatsApp($bin, $mediaKey, 'document');
                if ($dec !== null) {
                    $bin = $dec;
                    Log::info('Documento descriptografado com sucesso', [
                        'tamanho_apos' => strlen($bin),
                        'primeiros_bytes' => bin2hex(substr($bin, 0, 16))
                    ]);
                } else {
                    Log::warning('Falha na descriptografia do documento, continuando com binário original', [
                        'primeiros_bytes' => bin2hex(substr($bin, 0, 16))
                    ]);
                }
            }

            if (in_array($mimetype, self::SUPPORTED_PDF_TYPES, true)) {
                $textoExtraido = $this->extrairTextoPDF($bin);
                $tipoEspecifico = 'pdf';
            } elseif (in_array($mimetype, self::SUPPORTED_DOC_TYPES, true)) {
                $textoExtraido = $this->extrairTextoDocumento($bin, $mimetype);
                $tipoEspecifico = 'document';
            } else {
                $textoExtraido = "[Tipo de documento não suportado para extração: $mimetype]";
                $tipoEspecifico = 'document';
            }

            $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: $this->getExtensao($mimetype);
            $nomeArmazenado = uniqid('doc_') . '.' . $ext;
            $caminhoLocal = "{$this->mediaPath}/documents/{$nomeArmazenado}";
            Storage::disk($this->mediaDisk)->put($caminhoLocal, $bin);

            return [
                'success' => true,
                'tipo_midia' => $tipoEspecifico,
                'conteudo_extraido' => $textoExtraido,
                'arquivo_local' => $caminhoLocal,
                'metadados' => [
                    'tamanho_bytes' => strlen($bin),
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

            return ['success' => false, 'tipo_midia' => 'document', 'erro' => $e->getMessage()];
        }
    }

    private function processarAudio(array $audioData): array
    {
        $url      = $audioData['url'] ?? null;
        $mimetype = $audioData['mimetype'] ?? 'audio/ogg';
        $mediaKey = $audioData['mediaKey'] ?? null;

        if (!$url) {
            return ['success' => false, 'tipo_midia' => 'audio', 'erro' => 'URL do áudio não fornecida'];
        }

        try {
            $bin = $this->baixarComCurl($url);

            if (strlen($bin) > $this->maxFileSize) {
                throw new Exception("Áudio muito grande: " . strlen($bin) . " bytes (máximo {$this->maxFileSize})");
            }

            if ($mediaKey) {
                $dec = $this->descriptografarMidiaWhatsApp($bin, $mediaKey, 'audio');
                if ($dec !== null) {
                    $bin = $dec;
                    Log::info('Áudio descriptografado com sucesso', [
                        'tamanho_apos' => strlen($bin),
                    ]);
                }
            }

            $filename = uniqid('audio_') . '.' . $this->getExtensao($mimetype);
            $caminhoLocal = "{$this->mediaPath}/audio/{$filename}";
            Storage::disk($this->mediaDisk)->put($caminhoLocal, $bin);

            $descricao = "🎙️ Arquivo de áudio recebido (" . strlen($bin) . " bytes). Transcrição automática em desenvolvimento.";

            return [
                'success' => true,
                'tipo_midia' => 'audio',
                'conteudo_extraido' => $descricao,
                'arquivo_local' => $caminhoLocal,
                'metadados' => [
                    'tamanho_bytes' => strlen($bin),
                    'mime_type' => $mimetype,
                    'url_original' => $url
                ]
            ];
        } catch (Exception $e) {
            Log::error('Erro ao processar áudio', ['url' => $url, 'erro' => $e->getMessage()]);
            return ['success' => false, 'tipo_midia' => 'audio', 'erro' => $e->getMessage()];
        }
    }

    public function analisarImagemComOpenAI(string $imagemLocalPath): string
    {
        if (!$this->openaiKey) {
            return "📷 Imagem recebida. Análise de imagem com IA não configurada.";
        }

        try {
            $conteudoImagem = Storage::disk($this->mediaDisk)->get($imagemLocalPath);
            if (!$conteudoImagem) {
                return "📷 Imagem recebida. Arquivo não encontrado para análise.";
            }

            $extensao = strtolower(pathinfo($imagemLocalPath, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $mediaType = $mimeMap[$extensao] ?? 'image/jpeg';

            // limite do binário antes do base64 (base64 aumenta ~33%)
            $maxBin = 14 * 1024 * 1024; // seguro na prática
            if (strlen($conteudoImagem) > $maxBin) {
                return "📷 Imagem recebida. Arquivo muito grande para análise (reduza a resolução).";
            }

            $base64 = base64_encode($conteudoImagem);
            if (!$base64 || strlen($base64) < 100) {
                return "📷 Imagem recebida. Erro ao processar arquivo para análise.";
            }

            $response = Http::withToken($this->openaiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analise esta imagem e descreva de forma objetiva: objetos, texto visível, contexto e detalhes relevantes.'
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

            $descricao = data_get($response->json(), 'choices.0.message.content');
            if (!$descricao) {
                return "📷 Imagem recebida. Não foi possível gerar descrição.";
            }

            return "📷 **Análise de Imagem:**\n\n" . $descricao;
        } catch (Exception $e) {
            Log::error('Erro ao chamar OpenAI Vision', ['erro' => $e->getMessage()]);
            return "📷 Imagem recebida. Erro ao processar com IA: " . $e->getMessage();
        }
    }

    private function extrairTextoPDF(string $conteudoPDF): string
    {
        try {
            // Tentar usar Spatie/pdftotext primeiro
            if (class_exists(\Spatie\PdfToText\Pdf::class)) {
                try {
                    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
                    file_put_contents($tempFile, $conteudoPDF);

                    // Spatie depende do binário pdftotext instalado
                    $texto = \Spatie\PdfToText\Pdf::getText($tempFile);

                    @unlink($tempFile);
                    $texto = trim((string) $texto);

                    if ($texto !== '') {
                        return $texto;
                    }
                } catch (Exception $e) {
                    Log::warning('pdftotext não disponível, usando extração alternativa', [
                        'erro' => $e->getMessage()
                    ]);
                }
            }

            // Fallback: extração básica de texto do PDF
            return $this->extrairTextoPDFFallback($conteudoPDF);
        } catch (Exception $e) {
            Log::error('Erro ao extrair texto do PDF', ['erro' => $e->getMessage()]);
            return "📄 Erro ao processar PDF: " . $e->getMessage();
        }
    }

    private function extrairTextoPDFFallback(string $conteudoPDF): string
    {
        /**
         * Extração básica de PDF sem pdftotext
         * Procura por streams de texto no PDF
         */
        try {
            // Remover caracteres nulos e não-imprimíveis
            $pdf = str_replace(["\x00", "\r\n"], ["", " "], $conteudoPDF);
            
            // Procurar por padrões de texto em streams PDF
            $texto = '';
            
            // Padrão 1: Texto entre parênteses em BT/ET (text objects)
            if (preg_match_all('/BT\s+(.*?)\s+ET/s', $pdf, $matches)) {
                foreach ($matches[1] as $match) {
                    // Extrair strings
                    if (preg_match_all('/\((.*?)\)\s*Tj/', $match, $strings)) {
                        foreach ($strings[1] as $str) {
                            // Decodificar escape sequences básicos
                            $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
                            $texto .= $str . " ";
                        }
                    }
                }
            }
            
            // Padrão 2: Texto em arrays
            if (preg_match_all('/\[(.*?)\]\s*TJ/', $pdf, $matches)) {
                foreach ($matches[1] as $match) {
                    if (preg_match_all('/\((.*?)\)/', $match, $strings)) {
                        foreach ($strings[1] as $str) {
                            $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
                            $texto .= $str . " ";
                        }
                    }
                }
            }
            
            // Limpeza básica
            $texto = preg_replace('/\s+/', ' ', $texto); // Remover espaços múltiplos
            $texto = trim($texto);
            
            if (strlen($texto) > 20) {
                return "📄 **Conteúdo do PDF:**\n\n" . substr($texto, 0, 1000) . 
                       (strlen($texto) > 1000 ? "\n\n...(conteúdo truncado)" : "");
            }
            
            return "📄 PDF recebido mas com pouco texto extraível (pode ser PDF com imagens).";
        } catch (Exception $e) {
            Log::warning('Erro na extração fallback de PDF', ['erro' => $e->getMessage()]);
            return "📄 PDF recebido com sucesso (não foi possível extrair texto completo - pode ser PDF com imagens)";
        }
    }

    private function extrairTextoDocumento(string $conteudo, string $mimetype): string
    {
        if ($mimetype === 'text/plain') {
            $texto = mb_convert_encoding($conteudo, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
            return trim($texto) ?: "Arquivo de texto vazio";
        }

        if ($mimetype === 'text/csv') {
            $linhas = preg_split("/\r\n|\n|\r/", $conteudo);
            $preview = implode("\n", array_slice($linhas, 0, 10));
            $resto = max(0, count($linhas) - 10);

            return "📊 **Arquivo CSV recebido:**\n\n" . $preview . ($resto > 0 ? "\n...\n(+{$resto} linhas)" : "");
        }

        if (str_contains($mimetype, 'wordprocessingml')) {
            try {
                $zip = new \ZipArchive();
                $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
                file_put_contents($tempFile, $conteudo);

                if ($zip->open($tempFile) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    @unlink($tempFile);

                    if ($xml) {
                        // melhora: preserva quebras básicas
                        $xml = str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml);
                        $texto = strip_tags($xml);
                        $texto = preg_replace('/\s+/', ' ', $texto);
                        $texto = trim($texto);

                        return $texto !== '' ? $texto : "📄 Documento DOCX vazio";
                    }
                }

                @unlink($tempFile);
            } catch (Exception $e) {
                Log::warning('Erro ao processar DOCX', ['erro' => $e->getMessage()]);
            }

            return "📄 Arquivo DOCX recebido. Para extração robusta, use phpoffice/phpword.";
        }

        if (str_contains($mimetype, 'spreadsheetml')) {
            return "📊 Arquivo Excel (XLSX) recebido. Processamento requer PHPOffice/PhpSpreadsheet.";
        }

        return "📄 Tipo de documento recebido: $mimetype";
    }

    /**
     * ✅ Download robusto com cURL (SSL controlável via env WHATSAPP_VERIFY_SSL)
     */
    private function baixarComCurl(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $data === false || $data === '') {
            throw new Exception("Falha ao baixar arquivo: HTTP {$httpCode}" . ($curlError ? " ({$curlError})" : ""));
        }

        return $data;
    }

    /**
     * ✅ Validação correta de magic bytes (inclui WEBP correto)
     */
    private function validarFormatoImagem(string $conteudo, string $mimetype): bool
    {
        if ($conteudo === '') return false;

        $head12 = substr($conteudo, 0, 12);

        return match ($mimetype) {
            'image/jpeg' => substr($head12, 0, 3) === "\xFF\xD8\xFF",
            'image/png'  => substr($head12, 0, 4) === "\x89PNG",
            'image/gif'  => substr($head12, 0, 4) === "GIF8",
            'image/webp' => substr($head12, 0, 4) === "RIFF" && substr($head12, 8, 4) === "WEBP",
            default      => true,
        };
    }

    /**
     * ✅ Descriptografia WhatsApp corrigida:
     * - Corrige chamada do hash_hkdf (sua ordem estava errada)
     * - Info correto por tipo (Image/Document/Audio)
     * - Tenta MAC 10 bytes e 32 bytes (fallback)
     */
    private function descriptografarMidiaWhatsApp(string $conteudoCriptografado, string $mediaKey, string $tipo): ?string
    {
        try {
            $mediaKeyBytes = base64_decode($mediaKey, true);
            if ($mediaKeyBytes === false || strlen($mediaKeyBytes) !== 32) {
                Log::error('MediaKey inválido (deve virar 32 bytes)', [
                    'mediaKey_len' => strlen($mediaKey),
                    'decoded_len' => $mediaKeyBytes ? strlen($mediaKeyBytes) : null,
                ]);
                return null;
            }

            $info = match ($tipo) {
                'image'    => 'WhatsApp Image Keys',
                'document' => 'WhatsApp Document Keys',
                'audio'    => 'WhatsApp Audio Keys',
                default    => 'WhatsApp Image Keys',
            };

            // 112 bytes: IV (16) + cipherKey (32) + macKey (32) + refKey (32)
            if (function_exists('hash_hkdf')) {
                // ✅ assinatura correta: hash_hkdf(algo, key, length, info, salt)
                $expanded = hash_hkdf('sha256', $mediaKeyBytes, 112, $info, '');
            } else {
                // fallback simples (não ideal)
                $t = hash_hmac('sha256', $info . "\x01", $mediaKeyBytes, true);
                $expanded = $t;
                while (strlen($expanded) < 112) {
                    $t = hash_hmac('sha256', $t . $info . chr((int)(strlen($expanded)/32)+1), $mediaKeyBytes, true);
                    $expanded .= $t;
                }
                $expanded = substr($expanded, 0, 112);
            }

            $iv        = substr($expanded, 0, 16);
            $cipherKey = substr($expanded, 16, 32);
            $macKey    = substr($expanded, 48, 32);

            // Tenta remover MAC (alguns providers usam 10 bytes, outros 32)
            foreach ([10, 32] as $macLen) {
                if (strlen($conteudoCriptografado) <= $macLen + 16) {
                    continue;
                }

                $cipherPlus = substr($conteudoCriptografado, 0, -$macLen);
                $mac        = substr($conteudoCriptografado, -$macLen);

                // Se for 32 bytes, valida HMAC-SHA256 (melhor cenário)
                if ($macLen === 32) {
                    $calc = hash_hmac('sha256', $iv . $cipherPlus, $macKey, true);
                    if (!hash_equals($calc, $mac)) {
                        continue; // mac inválido, tenta próximo
                    }
                }

                $plain = openssl_decrypt($cipherPlus, 'AES-256-CBC', $cipherKey, OPENSSL_RAW_DATA, $iv);
                if ($plain !== false && $plain !== '') {
                    return $plain;
                }
            }

            return null;
        } catch (Exception $e) {
            Log::error('Erro ao descriptografar mídia WhatsApp', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
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

    /**
     * Obter extensão do arquivo baseado no MIME type
     */
    private function getExtensao($mimetype)
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
        ];

        return $mimeToExt[$mimetype] ?? 'bin';
    }
}
