<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

/**
 * Api/V1/Workdays Controller
 *
 */
class WorkdaysController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('Workdays');

    }

    public function index(): void
    {
        $this->set([
            'items' => $this->Workdays->find('all')->toArray()
        ]);
    }

    public function add(): void {

        $payload = $this->request->getData();
        $entityWorkday = $this->Workdays->newEntity($payload);

        if($error = $entityWorkday->getErrors()) {

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

            $this->Workdays->saveOrFail($entityWorkday);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'workday created successfully',
                'data' => $entityWorkday
            ]);

        } catch (PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to save workday',
                'message' => 'database error while saving workday',
                'details' => $e->getEntity()->getErrors()
            ]);

        }

    }

    public function edit(): void {
        $id = $this->request->getParam('id');

        $entityWorkday = $this->Workdays->get($id);

        $payload = $this->request->getData();

        $entityWorkday = $this->Workdays->patchEntity($entityWorkday, $payload);

        if($error = $entityWorkday->getErrors()) {

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

            $this->Workdays->saveOrFail($entityWorkday);

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'workday updated successfully',
                'data' => $entityWorkday
            ]);

        } catch (PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to update workday',
                'message' => 'database error while updating workday',
                'details' => $e->getEntity()->getErrors()
            ]);

        }
    }

}
