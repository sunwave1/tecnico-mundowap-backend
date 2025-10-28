<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Cake\Controller\Controller;

class ApiController extends Controller
{

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');

        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', true);
    }
}
