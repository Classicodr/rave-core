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

namespace rave\core\database\driver;

use rave\core\database\ORM\Query;

/**
 * Interface GenericDriver
 *
 * Allows to connect different datasources
 *
 * @package rave\core\database\driver
 */
interface GenericDriver
{

    /**
     * Executes the query and returns the results
     *
     * @param Query $query
     * @param string $entity
     * @return array result of the query
     */
    public function query(Query $query, $entity = null);

    /**
     * Executes the query and returns the first element
     *
     * @param Query $query
     * @param string $entity
     * @return mixed result of the query
     */
    public function queryOne(Query $query, $entity = null);

    /**
     * Executes the query without waiting for the result
     *
     * @param Query $query
     * @return array result of the query
     *
     */
    public function execute(Query $query);

    /**
     * Return the last inserted id
     *
     * @return int|string
     */
    public function lastInsertId();

}
