<?php
/**
 * Rave <https://github.com/Classicodr/rave-core>
 * Copyright (C) 2016 Rave Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use rave\core\Config;
use rave\core\database\DriverFactory;
use rave\core\DB;

/**
 * Some useful constants
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__));
define('TEST_APP', ROOT . DS . 'tests' . DS . 'app' . DS);

define('APP', TEST_APP);

define('WEB_ROOT', 'http://localhost');

require_once ROOT . '/vendor/autoload.php';

/**
 * Include the autoloader
 */

Config::addArray([
    'debug' => true,

    'datasources' => [
        'test' => [
            'driver' => DriverFactory::MYSQL_PDO,
            'host' => 'localhost',
            'database' => 'test',
            'login' => 'root',
            'password' => ''
        ],
    ],

    'error' => [
        '500' => '/internal-server-error',
        '404' => '/not-found',
        '403' => '/forbidden'
    ],

    'encryption' => [
        'mode' => MCRYPT_MODE_CBC,
        'cypher' => MCRYPT_RIJNDAEL_256,
        'iv' => 'CHANGEME', //TODO
        'key' => 'CHANGEME', //TODO
    ]
]);

DB::set(DriverFactory::get('test'));