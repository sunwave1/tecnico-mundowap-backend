<?php

namespace App\Services;

use App\Services\Interface\Cep;
use App\Services\Enums\CepRequestError;
use Cake\Http\Client;
use App\Services\Utils\ResponseCep;

class RepublicaVirtual implements Cep {

    public function __construct(public string $cep) {}

    public function getCep(): ResponseCep|CepRequestError
    {
        $cep = $this->cep;
        $url = "http://cep.republicavirtual.com.br/web_cep.php?cep={$cep}&formato=json";

        $client = new Client([
            'connect_timeout' => 5,
            'timeout' => 10,
        ]);

        $response = $client->get($url);

        if (!$response->isOk()) {
            return CepRequestError::RequestError;
        }

        $body = $response->getJson();

        if (is_null($body)) {
            return CepRequestError::RequestError;
        }

        if(isset($body['resultado']) && $body['resultado'] == '0') {
            return CepRequestError::CepNotFound;
        }

        return new ResponseCep(
            $body['cep'] ?? $cep,
            $body['logradouro'] ?? '',
            $body['bairro'] ?? '',
            $body['cidade'] ?? '',
            $body['uf'] ?? '',
            $body['complemento'] ?? null,
            $body['unidade'] ?? null,
            $body['estado'] ?? null,
            $body['regiao'] ?? null,
            $body['ibge'] ?? null,
            $body['gia'] ?? null,
            $body['ddd'] ?? null,
            $body['siafi'] ?? null
        );
    }

}
