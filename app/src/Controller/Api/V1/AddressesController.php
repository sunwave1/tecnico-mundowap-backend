<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Cake\ORM\Exception\PersistenceFailedException;

use App\Services\ManagerServiceCep;
use App\Services\Enums\CepRequestError;

/**
 * Api/V1/Addresses Controller
 *
 */
class AddressesController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('Addresses');

    }

    public function index(): void {
        $this->set([
            'items' => $this->Addresses->find('all')->toArray()
        ]);
    }

    public function add(): void {

        $payload = $this->request->getData();
        $entityAddress = $this->Addresses->newEntity($payload);

        if($error = $entityAddress->getErrors()) {

            $this->set([
                'status_code' => 400,
                'error' => 'validation failed',
                'message' => 'please check the provided data',
                'details' => $error
            ]);

            $this->response = $this->response->withStatus(400);

            return;
        }

        $service = new ManagerServiceCep();

        $responseServiceCep = $service->consult($payload['postal_code']);

        if($responseServiceCep instanceof CepRequestError) {

            $isCepNotFound = $responseServiceCep == CepRequestError::CepNotFound;

            $this->set([
                'status_code' => 400,
                'error' => 'cep search failed',
                'message' => $isCepNotFound ? 'please check your postal code' : 'our providers are down, wait a few minutes and search again',
                'details' => $isCepNotFound ? 'cep not found' : 'providers are down'
            ]);

            $this->response = $this->response->withStatus(400);

            return;
        }

        try {

            $cepData = $responseServiceCep
                ->toCollection()
                ->toArray();

            $entityAddress->set('postal_code', $cepData['postal_code']);
            $entityAddress->set('state', $cepData['uf']);
            $entityAddress->set('city', $cepData['city']);

            $this->Addresses->saveOrFail($entityAddress);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'address created successfully',
                'data' => $entityAddress,
            ]);

        } catch (PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to save address',
                'message' => 'database error while saving address',
                'details' => $e->getEntity()->getErrors()
            ]);

        }

    }

    public function edit(): void {
        $id = $this->request->getParam('id');

        $entityAddress = $this->Addresses->get($id);

        $payload = $this->request->getData();

        $entityAddress = $this->Addresses->patchEntity($entityAddress, $payload);

        if($error = $entityAddress->getErrors()) {

            $this->set([
                'status_code' => 400,
                'error' => 'validation failed',
                'message' => 'please check the provided data',
                'details' => $error
            ]);

            $this->response = $this->response->withStatus(400);

            return;
        }

        try {

            $this->Addresses->saveOrFail($entityAddress);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'address updated successfully',
                'data' => $entityAddress
            ]);

        } catch (PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to update address',
                'message' => 'database error while updating address',
                'details' => $e->getEntity()->getErrors()
            ]);

        }

    }
}
