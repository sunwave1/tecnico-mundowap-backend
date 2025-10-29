<?php

namespace App\Services;

use App\Services\Interface\Cep;
use App\Services\Enums\{CepRequestError, CepServices};
use App\Services\Utils\ResponseCep;
use Cake\Collection\Collection;

class ManagerServiceCep
{
    public function consult(string $cep): ResponseCep|CepRequestError {

        $republicaService = $this->createService(CepServices::RepublicaVirtual, $cep);

        $republicaResponse = $republicaService->getCep();

        if($republicaResponse instanceof CepRequestError) {

            $viaCepService = $this->createService(CepServices::ViaCep, $cep);

            $viaCepResponse = $viaCepService->getCep();

            return $viaCepResponse;

        }

        return $republicaResponse;
    }

    public function createService(CepServices $service, string $cep): Cep
    {
        return match ($service) {
            CepServices::ViaCep => new ViaCep($cep),
            CepServices::RepublicaVirtual => new RepublicaVirtual($cep),
        };
    }
}
