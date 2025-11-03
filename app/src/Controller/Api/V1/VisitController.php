<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Cake\ORM\Exception\PersistenceFailedException;

use App\Services\ManagerServiceCep;
use App\Services\Enums\CepRequestError;
use App\Enums\ReturnValue;
use Cake\Log\Log;

/**
 * Api/V1/Visit Controller
 *
 */
class VisitController extends ApiController
{
    public function initialize(): void {
        parent::initialize();

        $this->loadModel('Visits');
        $this->loadModel('Addresses');
        $this->loadModel('Workdays');
    }

    public function index(string $date): void {
        $this->set([
            'items' => $this->Visits
                ->find('all')
                ->where(['date' => $date])
                ->toArray()
        ]);
    }

    public function add(): void {

        $payload = $this->request->getData();

        $entityVisit = $this->Visits->newEntity($payload, [
            'associated' => ['Address']
        ]);

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

            $workday = $this->Workdays
                ->find()
                ->where([ 'date' => $payload['date'] ])
                ->first();

            if(is_null($workday)) {

                $entityWorkday = $this->Workdays->newEntity([
                    'date' => $payload['date'],
                    'visits' => 1,
                    'duration' => $entityVisit->getDuration(),
                    'completed' => $entityVisit->completed === 1 ? 1 : 0
                ]);

                $this->Workdays->saveOrFail($entityWorkday);

            } else {

                $workday->visits++;
                $workday->duration += $entityVisit->getDuration();
                $workday->completed += $entityVisit->completed === 1 ? 1 : 0;

                $this->Workdays->saveOrFail($workday);

            }

            $connection = $this->Visits->getConnection();

            $entityVisit->address->set([
                'foreign_table' => 'visits',
                'state' => '',
                'city' => '',
            ]);

            $connection->transactional(function() use ($entityVisit) {
                $this->Visits->saveOrFail($entityVisit, [
                    'associated' => ['Address']
                ]);
            });

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'visit created successfully',
                'data' => $entityVisit
            ]);
        } catch(PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to create visit',
                'message' => 'database error while created visit',
                'details' => $e->getEntity()->getErrors()
            ]);
        }
    }

    public function edit(): void {
        $id = $this->request->getParam('id');

        $entityVisit = $this->Visits->get($id, [
            'contain' => ['Address']
        ]);

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

            if($entityVisit->address->isDirty('postal_code')) {

                if($this->Addresses->deleteAll(['id' => $entityVisit->address->id]) === 0) {
                    throw new PersistenceFailedException($entityVisit->address, 'failed while deleting address');
                }

                $postalCode = $entityVisit->address->postal_code;

                $service = new ManagerServiceCep();

                $responseServiceCep = $service->consult($postalCode);

                if($responseServiceCep instanceof CepRequestError) {

                    $isCepNotFound = $responseServiceCep == CepRequestError::CepNotFound;

                    $this->set([
                        'status_code' => 400,
                        'error' => sprintf('cep %s search failed', $postalCode),
                        'message' => $isCepNotFound ? 'please check your postal code' : 'our providers are down, wait a few minutes and search again',
                        'details' => $isCepNotFound ? 'cep not found' : 'providers are down'
                    ]);

                    $this->response = $this->response->withStatus(400);

                    return;
                }

                $cepData = $responseServiceCep
                    ->toCollection()
                    ->toArray();

                $entityVisit->address = $this->Addresses->newEntity([
                    'foreign_table' => 'visits',
                    'postal_code' => $cepData['postal_code'],
                    'state' => $cepData['uf'],
                    'city' => $cepData['city'],
                    'sublocality' => $payload['address']['sublocality'],
                    'street' => $payload['address']['street'],
                    'street_number' => $payload['address']['street_number'],
                ]);
            }

            if($entityVisit->isDirty('date')) {
                $this->updateWorkdayDate($entityVisit);
            }

            if($entityVisit->isDirty('forms') || $entityVisit->isDirty('products')) {

                $ret = $this->updateWorkdayDuration($entityVisit);

                if($ret != ReturnValue::NOERROR) {

                    $this->set([
                        'status_code' => 400,
                        'error' => 'failed to update duration workday',
                        'message' => 'failed while updating duration visit',
                        'details' => $ret->getMessage()
                    ]);

                    $this->response = $this->response->withStatus(400);

                    return;
                }
            }

            $connection = $this->Visits->getConnection();

            $connection->transactional(function() use ($entityVisit) {
                $this->Visits->saveOrFail($entityVisit, [
                    'associated' => ['Address']
                ]);
            });

            $this->set([
                'status_code' => 200,
                'status' => 'success',
                'message' => 'visit updated successfully',
                'data' => $entityVisit
            ]);
        } catch(PersistenceFailedException $e) {

            $this->response = $this->response->withStatus(500);

            $this->set([
                'status_code' => 500,
                'error' => 'failed to update visit',
                'message' => 'database error while updated visit',
                'details' => $e->getEntity()->getErrors()
            ]);
        }
    }

    private function updateWorkdayDuration($entityVisit): ReturnValue {

        $entityWorkday = $this->Workdays
            ->find()
            ->where([ 'date' => $entityVisit->date ])
            ->first();

        $totalDuration = $this->Visits
            ->find()
            ->select(['total' => $this->Visits->query()->func()->sum('duration')])
            ->where([
                'date' => $entityVisit->date,
                'id !=' => $entityVisit->id
            ])
            ->total + $entityVisit->getDuration();

        if($totalDuration > 28800) {
            return ReturnValue::DURATION_WORKDAYS_EXCEEDED;
        }

        $entityWorkday->duration = $totalDuration;

        $this->Workdays->saveOrFail($entityWorkday);

        return ReturnValue::NOERROR;
    }

    private function updateWorkdayDate($entityVisit) {

        $oldWorkdayEntity = $this->Workdays
            ->find()
            ->where([ 'date' => $entityVisit->getOriginal('date') ])
            ->first();

        if(!is_null($oldWorkdayEntity)) {
            $oldWorkdayEntity->duration = max(0, $oldWorkdayEntity->duration - $entityVisit->getOriginal('duration'));
            $oldWorkdayEntity->visits = max(0, $oldWorkdayEntity->visits - 1);

            if($entityVisit->getOriginal('completed') === 1) {
                $oldWorkdayEntity->completed = max(0, $oldWorkdayEntity->completed - 1);
            }

            $this->Workdays->saveOrFail($oldWorkdayEntity);
        }

        $newWorkdayEntity = $this->Workdays
            ->find()
            ->where([ 'date' => $entityVisit->date ])
            ->first();

        if(is_null($newWorkdayEntity)) {

            $newWorkdayEntity = $this->Workdays->newEntity([
                'date' => $entityVisit->date,
                'duration' => $entityVisit->getDuration(),
                'visits' => 1,
                'completed' => $entityVisit->completed === 1 ? 1 : 0
            ]);
        } else {

            $newWorkdayEntity->visits++;
            $newWorkdayEntity->duration += $entityVisit->getDuration();
            $newWorkdayEntity->completed += $entityVisit->completed === 1 ? 1 : 0;
        }

        $this->Workdays->saveOrFail($newWorkdayEntity);
    }
}
