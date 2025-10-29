<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {

    $routes->prefix('Api', function (RouteBuilder $routes) {

        $routes->prefix('V1', function (RouteBuilder $routes) {

            $routes->setExtensions(['json']);

            $routes->scope('/visit', function(RouteBuilder $routes) {

                $routes
                    ->get('/', ['controller' => 'Visit', 'action' => 'index']);

                $routes
                    ->get('/{date}', ['controller' => 'Visit', 'action' => 'index'])
                    ->setPatterns(['date' => '\d{4}-\d{2}-\d{2}'])
                    ->setPass(['date']);

                $routes
                    ->post('/', ['controller' => 'Visit', 'action' => 'add']);

                $routes
                    ->connect('/{id}', ['controller' => 'Visit', 'action' => 'edit'])
                    ->setMethods(['PUT', 'PATCH']);
            });

            $routes->resources('Workdays', [
                'only' => ['create', 'update', 'index'],
            ]);

            $routes->resources('Addresses', [
                'only' => ['create', 'update', 'index'],
            ]);

        });

    });

};
