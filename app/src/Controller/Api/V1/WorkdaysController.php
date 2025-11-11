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

        $this->loadModel("Workdays");
        $this->loadModel("Visits");
    }

    public function index(): void
    {
        $this->paginate = [
            "limit" => $this->request->getQuery("limit", 25),
            "page" => $this->request->getQuery("page", 1),
            "order" => [
                "Workdays.date" => "asc",
            ],
        ];

        try {
            $items = $this->paginate($this->Workdays);
            $pagination = $this->request->getAttribute("paging")["Workdays"];

            $this->set([
                "status_code" => 200,
                "status" => "success",
                "message" => "workdays paginated successfully",
                "items" => $items,
                "pagination" => [
                    "current_page" => (int) $pagination["page"],
                    "page_count" => (int) $pagination["pageCount"],
                    "items_count" => (int) $pagination["count"],
                    "first_page" => (bool) $pagination["start"],
                    "last_page" => (bool) $pagination["end"],
                    "next_page" => (bool) $pagination["nextPage"],
                    "prev_page" => (int) $pagination["prevPage"],
                    "per_page" => (int) $pagination["perPage"],
                ],
            ]);
        } catch (\Exception $e) {
            $this->response = $this->response->withStatus(500);

            $this->set([
                "status_code" => 500,
                "error" => "failed to paginate workdays",
                "message" => "error while paginate workdays",
                "details" => $e->getMessage(),
            ]);
        }
    }

    public function close(string $date): void
    {
        $entitiesWorkday = $this->Workdays->find("all", [
            "conditions" => ["date" => $date],
        ]);

        if ($entitiesWorkday->isEmpty()) {
            $this->response = $this->response->withStatus(404);

            $this->set([
                "status_code" => 404,
                "error" => "failed to find workdays",
                "message" => "check your parameter date",
                "details" => "items workday is empty",
            ]);

            return;
        }

        try {
            $movedCount = 0;
            $totalToMove = 0;

            $connection = $this->Workdays->getConnection();

            $connection->transactional(function () use (
                $date,
                &$movedCount,
                &$totalToMove,
            ) {
                $currentDate = new \DateTime($date);

                $entitiesPendingVisit = $this->Visits->find("all", [
                    "conditions" => [
                        "date" => $date,
                        "completed" => 0,
                    ],
                ]);

                $totalToMove = count($entitiesPendingVisit->toArray());

                $movedCount = $this->movePendingVisits(
                    $entitiesPendingVisit->toArray(),
                    $currentDate,
                );

                $this->updateWorkday($date);
            });

            $this->set([
                "status_code" => 200,
                "status" => "success",
                "message" => "visit moved with success",
                "movedCount" => $movedCount,
                "toMove" => $totalToMove,
            ]);
        } catch (PersistenceFailedException $e) {
            $this->response = $this->response->withStatus(500);

            $this->set([
                "status_code" => 500,
                "error" => "failed to move visits",
                "message" => "database error while moving visits",
                "details" => $e->getEntity()->getErrors(),
            ]);
        }
    }

    private function movePendingVisits(
        array $pendingVisits,
        \DateTime $startDate,
    ): int {
        $movedCount = 0;
        $currentTargetDate = clone $startDate;

        foreach ($pendingVisits as $visit) {
            $freeDate = $this->getNextFreeDate(
                $currentTargetDate,
                $visit->duration,
            );

            if (is_null($freeDate)) {
                continue;
            }

            $visit->date = $freeDate->format("Y-m-d");

            if ($this->Visits->saveOrFail($visit)) {
                $movedCount++;

                $currentTargetDate = $freeDate;

                $this->updateWorkday($freeDate->format("Y-m-d"));
            }
        }

        return $movedCount;
    }

    private function getNextFreeDate(
        \DateTime $startDate,
        int $visitDuration,
    ): ?\DateTime {
        $searchDate = clone $startDate;
        $maxDayAttempts = 30;

        while (--$maxDayAttempts > 0) {
            $searchDate->modify("+1 day");

            $dateString = $searchDate->format("Y-m-d");

            $entityWorkday = $this->Workdays
                ->find()
                ->where(["date" => $dateString])
                ->first();

            $duration = is_null($entityWorkday) ? 0 : $entityWorkday->duration;

            if ($duration + $visitDuration <= 28800) {
                return $searchDate;
            }
        }

        return null;
    }

    private function updateWorkday(string $date)
    {
        $entitiesVisit = $this->Visits
            ->find()
            ->where(["date" => $date])
            ->select([
                "total_visits" => "COUNT(*)",
                "completed_visits" => "SUM(completed)",
                "total_duration" => "SUM(duration)",
            ])
            ->first();

        $entityWorkday = $this->Workdays
            ->find()
            ->where(["date" => $date])
            ->first();

        if (is_null($entityWorkday)) {
            $entityWorkday = $this->Workdays->newEmptyEntity();
            $entityWorkday->date = $date;
        }

        $entityWorkday->visits = $entitiesVisit->total_visits ?? 0;
        $entityWorkday->completed = $entitiesVisit->completed_visits ?? 0;
        $entityWorkday->duration = $entitiesVisit->total_duration ?? 0;

        $this->Workdays->saveOrFail($entityWorkday);
    }
}
