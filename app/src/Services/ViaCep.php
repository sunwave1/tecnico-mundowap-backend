<?php

namespace App\Services;

use App\Services\Interface\Cep;
use App\Services\Enums\CepRequestError;
use Cake\Http\Client;
use App\Services\Utils\ResponseCep;

class ViaCep implements Cep
{
    public function __construct(public string $cep) {}

    public function getCep(): ResponseCep|CepRequestError
    {
        $cep = $this->cep;
        $url = "https://viacep.com.br/ws/{$cep}/json/";

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

        if(isset($body['erro']) && $body['erro'] == 'true') {
            return CepRequestError::CepNotFound;
        }

        return new ResponseCep(
            $body['cep'] ?? '',
            $body['logradouro'] ?? '',
            $body['bairro'] ?? '',
            $body['localidade'] ?? '',
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
