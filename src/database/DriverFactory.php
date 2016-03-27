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

use PDOException;
use rave\core\Config;
use rave\core\database\driver\GenericDriver;
use rave\core\database\driver\MySQLDriverPDO\MySQLDriverPDO;
use rave\core\database\driver\MySQLDriverPDO\PostgreSQLDriverPDO;
use rave\core\database\driver\SQLiteDriverPDO\SQLiteDriverPDO;
use rave\core\Error;
use rave\core\exception\UnknownDriverException;

/**
 * Class DriverFactory
 *
 * Creates a database instance given the datasource
 *
 * @package rave\core\database
 */
class DriverFactory
{
    /**
     * MySQL driver
     */
    const MYSQL_PDO = 'MySQLPDO';

    /**
     * SQLite driver
     */
    const SQLITE_PDO = 'SQLitePDO';

    /**
     * PostgreSQL driver
     */
    const PGSQL_PDO = 'PostgreSQLPDO';

    /**
     * Returns the configured driver
     *
     * @param string $dbname then name of the database configuration to use
     *
     * @return GenericDriver the datasource Object
     *
     * @throws UnknownDriverException
     */
    public static function get($dbname = 'default')
    {
        $datasource_config = Config::get('datasources')[$dbname];

        try {
            switch ($datasource_config['driver']) {
                case self::MYSQL_PDO:
                    return new MySQLDriverPDO($datasource_config);
                case self::SQLITE_PDO:
                    return new SQLiteDriverPDO($datasource_config);
                case self::PGSQL_PDO:
                    return new PostgreSQLDriverPDO($datasource_config);
                default:
                    throw new UnknownDriverException('Driver ' . $datasource_config['driver'] . ' does not exists');
            }
        } catch (PDOException $pdoException) {
            Error::create($pdoException->getMessage(), 500);
        }
    }

}