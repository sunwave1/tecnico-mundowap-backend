<?php

namespace App\Services\Utils;

use Cake\Collection\Collection;

class ResponseCep
{
    public function __construct(
        private string $postal_code,
        private string $logradouro,
        private string $bairro,
        private string $city,
        private string $uf,
        private ?string $complemento = null,
        private ?string $unidade = null,
        private ?string $state = null,
        private ?string $regiao = null,
        private ?string $ibge = null,
        private ?string $gia = null,
        private ?string $ddd = null,
        private ?string $siafi = null
    ) {}

    public function toCollection(): Collection {
        return new Collection([
            'postal_code' => $this->postal_code,
            'uf'          => $this->uf,
            'city'        => $this->city,
            'bairro'      => $this->bairro,
            'logradouro'  => $this->logradouro,
            'complemento' => $this->complemento,
            'unidade'     => $this->unidade,
            'state'       => $this->state,
            'regiao'      => $this->regiao,
            'ibge'        => $this->ibge,
            'gia'         => $this->gia,
            'ddd'         => $this->ddd,
            'siafi'       => $this->siafi,
        ]);
    }
}
