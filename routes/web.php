<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

Route::post('/calculafrete', function (Request $request) {
    // Recebe os dados do JSON enviado pelo Postman
    $inputData = $request->json()->all();

    // Converte os dados recebidos para o formato esperado pelo serviço SOAP
    $skus = $inputData['skus'][0]; // Considerando apenas o primeiro SKU como exemplo
    $data = [
        'TipoServico' => 'EXP',
        'CepDestino' => $inputData['zipcode'],
        'Peso' => number_format($skus['weight'], 2, ',', ''), // Peso no formato correto (ex: 1,00)
        'ValorDeclarado' => number_format($inputData['amount'], 2, ',', ''), // Valor declarado no formato correto (ex: 120,00)
        'TipoEntrega' => 0,
        'ServicoCOD' => 0,
        'Altura' => $skus['height'],
        'Largura' => $skus['width'],
        'Profundidade' => $skus['length'],
    ];

    // Chama a função para calcular o frete
    $response = calcularFrete($data);


    // Adicione antes de formatar a resposta
    // debugFullXML($response);
    // Processa a resposta para o formato desejado
    $formattedResponse = formatResponse($response);

    // Retorna a resposta formatada para o Postman
    return response()->json($formattedResponse);
});

function calcularFrete(array $data)
{
    $client = new Client();

    $url = 'https://edi.totalexpress.com.br/webservice_calculo_frete_v2.php';
    $headers = [
        'Content-Type' => 'text/xml; charset=UTF-8',
        'REID' => '62463',
        'Authorization' => 'Basic c3VuZml0LXByb2Q6ZDN5ZUNtTFI3OA==' // Autorização Base64
    ];

    $body = <<<XML
                <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:calcularFrete">
                    <soapenv:Body>
                        <urn:calcularFrete soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                            <calcularFreteRequest xsi:type="web:calcularFreteRequest" xmlns:web="http://edi.totalexpress.com.br/soap/webservice_calculo_frete.total">
                                <TipoServico xsi:type="xsd:string">{$data['TipoServico']}</TipoServico>
                                <CepDestino xsi:type="xsd:nonNegativeInteger">{$data['CepDestino']}</CepDestino>
                                <Peso xsi:type="xsd:string">{$data['Peso']}</Peso>
                                <ValorDeclarado xsi:type="xsd:string">{$data['ValorDeclarado']}</ValorDeclarado>
                                <TipoEntrega xsi:type="xsd:nonNegativeInteger">{$data['TipoEntrega']}</TipoEntrega>
                                <ServicoCOD xsi:type="xsd:boolean">{$data['ServicoCOD']}</ServicoCOD>
                                <Altura xsi:type="xsd:nonNegativeInteger">{$data['Altura']}</Altura>
                                <Largura xsi:type="xsd:nonNegativeInteger">{$data['Largura']}</Largura>
                                <Profundidade xsi:type="xsd:nonNegativeInteger">{$data['Profundidade']}</Profundidade>
                            </calcularFreteRequest>
                        </urn:calcularFrete>
                    </soapenv:Body>
                </soapenv:Envelope>
                XML;

    try {
        $response = $client->post($url, [
            'headers' => $headers,
            'body' => $body,
        ]);

        // Retorna o corpo da resposta
        return $response->getBody()->getContents();
    } catch (\Exception $e) {
        // Trata erros
        return 'Erro: ' . $e->getMessage();
    }
}

function formatResponse($response)
{
    // Converte o encoding para UTF-8
    $response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');

    try {
        // Carrega o XML
        $xml = new SimpleXMLElement($response);

        // Registra os namespaces para XPath
        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('soapenv', $namespaces['SOAP-ENV']);
        $xml->registerXPathNamespace('ns1', $namespaces['ns1']);

        // Usa XPath para encontrar os dados necessários
        $result = $xml->xpath('//soapenv:Body/ns1:calcularFreteResponse/calcularFreteResponse/DadosFrete');

        // Verifica se encontrou o nó de DadosFrete
        if (!empty($result)) {
            $dadosFrete = $result[0];

            // Extrai os valores necessários
            $prazo = (int)$dadosFrete->Prazo;
            $valorServico = (float)str_replace(',', '.', (string)$dadosFrete->ValorServico);
            $rota = (string)$dadosFrete->Rota;

            // Determina o tipo de serviço com base na rota
            $service = explode('-', $rota)[1] ?? 'SEDEX';

            // Monta o retorno
            return [
                'quotes' => [
                    [
                        'name' => 'OPÇÃO FRETE 1',
                        'service' => $rota,
                        'price' => $valorServico,
                        'days' => $prazo,
                        'quote_id' => 1,
                    ],
                ],
            ];
        } else {
            // Retorna erro se DadosFrete não for encontrado
            return ['error' => 'Nó DadosFrete não encontrado no XML.'];
        }
    } catch (\Exception $e) {
        // Retorna erro em caso de falha
        return ['error' => 'Erro ao processar o XML de resposta: ' . $e->getMessage()];
    }
}






function debugFullXML($response)
{
    $response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');
    $xml = new SimpleXMLElement($response);

    // Registra os namespaces
    $namespaces = $xml->getNamespaces(true);
    var_dump($namespaces);

    // Inspeciona todos os filhos do XML
    foreach ($namespaces as $prefix => $namespace) {
        echo "Namespace {$prefix} => {$namespace}\n";
        $child = $xml->children($namespace);
        print_r($child);
    }
}
