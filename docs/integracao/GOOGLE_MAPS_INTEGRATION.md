# Integração Google Maps - Guia de Uso

## 🔑 Configuração da Chave API

### 1. Obter a Chave do Google Maps

1. Acesse [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto
3. Habilite as seguintes APIs:
   - **Maps JavaScript API**
   - **Places API**
   - **Geocoding API**
   - **Static Maps API**

4. Crie uma credencial de tipo "API Key"
5. Restrinja a chave apenas para APIs necessárias

### 2. Adicionar a Chave ao Projeto

Abra o arquivo `.env` e adicione:

```env
GOOGLE_MAPS_KEY=sua_chave_aqui
```

## 📍 Funcionalidades Disponíveis

### 1. Geocodificar um Endereço (Endereço → Coordenadas)

```php
use App\Services\GoogleMapsService;

$mapsService = new GoogleMapsService();

$resultado = $mapsService->geocodeEndereco(
    endereco: 'Avenida Paulista, 1000',
    cidade: 'São Paulo',
    estado: 'SP'
);

// Resultado:
// [
//     'latitude' => -23.5505,
//     'longitude' => -46.6333,
//     'endereco_formatado' => 'Avenida Paulista, 1000, São Paulo, SP 01311-100, Brasil'
// ]
```

### 2. Buscar Regiões Próximas

```php
$regioes = $mapsService->buscarRegioesProximas(
    latitude: -23.5505,
    longitude: -46.6333,
    raioKm: 5
);

// Resultado:
// [
//     [
//         'nome' => 'Vila Mariana',
//         'endereco' => 'São Paulo, SP',
//         'latitude' => -23.5565,
//         'longitude' => -46.6415,
//         'tipo' => 'locality'
//     ],
//     ...
// ]
```

### 3. Obter Detalhes de um Lugar

```php
$detalhes = $mapsService->obterDetalhesLugar(
    placeId: 'ChIJIQBpAG2dQpQR_6128GltTXQ'
);

// Resultado:
// [
//     'nome' => 'Avenida Paulista',
//     'endereco' => 'Avenida Paulista, São Paulo, SP',
//     'latitude' => -23.5505,
//     'longitude' => -46.6333,
//     'telefone' => '+55 11 3282-8000',
//     'avaliacao' => 4.5,
//     ...
// ]
```

### 4. Calcular Distância Entre Dois Pontos

```php
$distancia = $mapsService->calcularDistancia(
    lat1: -23.5505,
    lng1: -46.6333,
    lat2: -23.5577,
    lng2: -46.6761
);

// Resultado: 5.23 (km)
```

### 5. Gerar URL de Mapa Estático

```php
$url = $mapsService->obterMapaEstatico(
    latitude: -23.5505,
    longitude: -46.6333,
    zoom: 15,
    width: 400,
    height: 300
);

// Resultado: https://maps.googleapis.com/maps/api/staticmap?center=-23.5505,-46.6333&zoom=15&...
```

## 🔗 Integração com o Chatbot

### Exemplo: Localizar Imóvel no Mapa

```php
use App\Services\GoogleMapsService;
use App\Models\Property;

// No seu serviço de resposta do bot:
$mapsService = new GoogleMapsService();

// 1. Geocodificar endereço do imóvel
$localizacao = $mapsService->geocodeEndereco(
    endereco: $property->endereco,
    cidade: $property->cidade,
    estado: $property->estado
);

if ($localizacao) {
    // 2. Salvar coordenadas na propriedade
    $property->update([
        'maps_lat' => $localizacao['latitude'],
        'maps_lng' => $localizacao['longitude'],
        'maps_url' => $mapsService->obterMapaEstatico(
            $localizacao['latitude'],
            $localizacao['longitude']
        ),
    ]);

    // 3. Buscar regiões próximas
    $regioes = $mapsService->buscarRegioesProximas(
        $localizacao['latitude'],
        $localizacao['longitude'],
        raioKm: 3
    );

    // 4. Enviar resposta com info de localização
    $resposta = "Encontrei um imóvel para você!\n\n";
    $resposta .= "📍 {$property->titulo}\n";
    $resposta .= "📮 {$property->endereco}, {$property->cidade}\n";
    $resposta .= "💰 R$ " . number_format($property->preco, 2, ',', '.') . "\n";
    $resposta .= "[Ver no mapa](" . $property->maps_url . ")\n\n";
    $resposta .= "Regiões próximas: " . implode(", ", array_map(fn($r) => $r['nome'], $regioes));
}
```

## 🛠️ Tratamento de Erros

```php
try {
    $resultado = $mapsService->geocodeEndereco(
        'Endereco inválido',
        'Cidade inválida',
        'XX'
    );
    
    if ($resultado === null) {
        Log::warning('Endereço não encontrado no Google Maps');
    }
} catch (\Exception $e) {
    Log::error('Erro na API do Google Maps: ' . $e->getMessage());
}
```

## 💡 Dicas Importantes

1. **Limite de Requisições**: Google Maps tem limite de requisições por dia. Monitore o uso na [Google Cloud Console](https://console.cloud.google.com/apis/dashboard)

2. **Cache**: Para melhor performance, faça cache dos resultados:
```php
$resultado = Cache::remember(
    'maps_endereco_' . md5($endereco),
    now()->addDays(30),
    function() use ($mapsService, $endereco) {
        return $mapsService->geocodeEndereco($endereco);
    }
);
```

3. **Validação**: Sempre valide o endereço antes de usar:
```php
if (!empty($property->endereco) && !empty($property->cidade)) {
    $localizacao = $mapsService->geocodeEndereco(...);
}
```

## 📊 Monitoramento

Os logs da integração são salvos em `storage/logs/laravel.log`:

```bash
# Buscar logs de Google Maps
Get-Content storage\logs\laravel.log | Select-String "Google Maps"
```

## 🔐 Segurança

- ✅ A chave da API fica segura no `.env` (não em versão pública)
- ✅ Restrinja a chave apenas às APIs necessárias
- ✅ Monitore o uso para evitar abusos
- ✅ Nunca exponha a chave em frontend ou URLs públicas

---

**Necessário Google Maps API habilitada? Execute:**
```bash
php artisan tinker
>>> $maps = new App\Services\GoogleMapsService();
>>> $maps->geocodeEndereco('Rua Teste, 100', 'São Paulo', 'SP');
```
