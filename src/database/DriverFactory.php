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

namespace rave\core\database;

use PDO;
use PDOException;
use rave\core\Config;
use rave\core\database\driver\GenericDriver;
use rave\core\database\driver\MySQLDriverPDO\MySQLDriverPDO;
use rave\core\database\driver\SQLiteDriverPDO\SQLiteDriverPDO;
use rave\core\Error;
use rave\core\exception\UnknownDriverException;

class DriverFactory
{
    const MYSQL_PDO = 'MySQLPDO';
    const SQLITE_PDO = 'SQLitePDO';

    /**
     * Returns the configured used driver
     *
     * @param string $dbname then name of the database configuration to use
     * @return GenericDriver
     * @throws UnknownDriverException
     * @throws \rave\core\exception\UnknownPropertyException
     */
    public static function get($dbname = 'database')
    {
        $database = Config::get($dbname);


        if ($database) {
            try {
                $pdo = new PDO('mysql:dbname=' . $database['name'] . ';host=' . $database['host'], $database['login'], $database['password'], [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                switch ($database['driver']) {
                    case self::MYSQL_PDO:
                        return new MySQLDriverPDO($pdo);
                    case self::SQLITE_PDO:
                        return new SQLiteDriverPDO($pdo);
                    default:
                        throw new UnknownDriverException('Driver ' . $database['driver'] . ' does not exists');
                }
            } catch (PDOException $pdoException) {
                Error::create($pdoException->getMessage(), 500);
            }
        } else {
            Error::create('the database is not configured');
        }
    }

}