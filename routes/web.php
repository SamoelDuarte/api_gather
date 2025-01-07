<?php

use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $data = [
        'TipoServico' => 'EXP',
        'CepDestino' => '11035040',
        'Peso' => '0,47',
        'ValorDeclarado' => '139,40',
        'TipoEntrega' => 0,
        'ServicoCOD' => 0,
        'Altura' => 100,
        'Largura' => 96,
        'Profundidade' => 10,
    ];

    $response = calcularFrete($data);

    echo $response;
});


function calcularFrete(array $data)
{
    $client = new Client();

    $url = 'https://edi.totalexpress.com.br/webservice_calculo_frete_v2.php'; // Substitua pela URL do serviço SOAP
    $headers = [
        'Content-Type' => 'text/xml; charset=utf-8',
        'SOAPAction' => 'urn:calcularFrete' // Substitua pelo SOAPAction correto, se necessário
    ];

    // Corpo da requisição com placeholders para variáveis
    $body = <<<XML
                    <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:calcularFrete">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <urn:calcularFrete soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                            <calcularFreteRequest xsi:type="web:calcularFreteRequest" xmlns:web="http://edi.totalexpress.com.br/soap/webservice_calculo_frete.total">
                            <REID xsi:type="xsd:string">62463</REID> <!-- Adicionando o campo REID -->
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
