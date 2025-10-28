<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Cake\ORM\Exception\PersistenceFailedException;

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

        try {

            $this->Addresses->saveOrFail($entityAddress);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'address created successfully',
                'data' => $entityAddress
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
