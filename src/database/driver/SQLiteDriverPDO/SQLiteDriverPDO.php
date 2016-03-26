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

namespace rave\core\database\driver\SQLiteDriverPDO;

use PDO;
use PDOException;
use rave\core\database\driver\GenericDriver;
use rave\core\database\ORM\Query;
use rave\core\Error;

class SQLiteDriverPDO implements GenericDriver
{
    private $instance;

    public function __construct(PDO $pdo)
    {
        $this->instance = $pdo;
    }

    /**
     * {@inheritdoc}
     * @see queryDatabase()
     */
    public function query(Query $query, $entity_name = null)
    {
        return $this->queryDatabase($query, $entity_name, false);
    }

    /**
     * Executes the given query in the database
     *
     * @param Query $query
     * @param string $entity_name
     * @param bool $unique [optional]
     *
     * fetch only one result
     * @return array|null the result, null if failed
     * @see query()
     * @see queryOne()
     */
    private function queryDatabase(Query $query, $entity_name = null, $unique)
    {
        try {
            $sql = $this->instance->prepare($query->getStatement());
            $sql->execute($query->getValues());

            if ($unique === true) {
                if (null === $entity_name) {
                    $result = $sql->fetch(PDO::FETCH_OBJ);
                } else {
                    $result = $sql->fetch(PDO::FETCH_CLASS, $entity_name);
                }

                return $result === false ? null : $result;
            }

            if (null === $entity_name) {
                $result = $sql->fetchAll(PDO::FETCH_OBJ);
            } else {
                $result = $sql->fetchAll(PDO::FETCH_CLASS, $entity_name);

            }

            return $result === false ? null : $result;
        } catch (PDOException $pdoException) {
            Error::create($pdoException->getMessage(), 500);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @see queryDatabase()
     */
    public function queryOne(Query $query, $entity_name = null)
    {
        return $this->queryDatabase($query, $entity_name, true);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Query $query)
    {
        try {
            $sql = $this->getInstance()->prepare($query->getStatement());
            $sql->execute($query->getValues());
        } catch (PDOException $pdoException) {
            Error::create($pdoException->getMessage(), 500);
        }
    }

    public function lastInsertId()
    {
        return $this->instance->lastInsertId();
    }

}