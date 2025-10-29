<?php

namespace App\Services\Interface;

use App\Services\Utils\ResponseCep;
use App\Services\Enums\CepRequestError;

interface Cep
{
    public function getCep(): ResponseCep|CepRequestError;
};
