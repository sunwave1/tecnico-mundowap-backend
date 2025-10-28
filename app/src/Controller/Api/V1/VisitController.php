<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Cake\ORM\Exception\PersistenceFailedException;

/**
 * Api/V1/Visit Controller
 *
 */
class VisitController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('Visits');

    }

    public function index(): void {
        $this->set([
            'items' => $this->Visits->find('all')->toArray()
        ]);
    }

    public function add(): void {

        $payload = $this->request->getData();
        $entityVisit = $this->Visits->newEntity($payload);

        if($error = $entityVisit->getErrors()) {

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

            $this->Visits->saveOrFail($entityVisit);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'visit created successfully',
                'data' => $entityVisit
            ]);

        } catch (PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to save visit',
                'message' => 'database error while saving visit',
                'details' => $e->getEntity()->getErrors()
            ]);

        }
    }

    public function edit(): void {
        $id = $this->request->getParam('id');

        $entityVisit = $this->Visits->get($id);

        $payload = $this->request->getData();

        $entityVisit = $this->Visits->patchEntity($entityVisit, $payload);

        if($error = $entityVisit->getErrors()) {

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

            $this->Visits->saveOrFail($entityVisit);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'visit updated successfully',
                'data' => $entityVisit
            ]);

        } catch (PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to update visit',
                'message' => 'database error while updating visit',
                'details' => $e->getEntity()->getErrors()
            ]);

        }
    }

}
