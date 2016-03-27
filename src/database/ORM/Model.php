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

namespace rave\core\database\ORM;

use rave\core\DB;
use rave\core\exception\EntityException;
use rave\core\exception\IncorrectQueryException;
use rave\core\exception\UnknownPropertyException;

abstract class Model
{
    const SQL = 'sql';
    const VALUES = 'values';
    const DRIVER = 'driver';

    protected static $table;

    /**
     * @var string $primary the primary key of the table
     * @deprecated
     * @see Entity
     */
    protected static $primary = 'id';

    protected static $entity_name;

    /**
     * @var Query $query
     * @deprecated
     * @see Query
     */
    private $query;

    /**
     * @return string <p>the name of the table associated to the model</p>
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * Prepare the given query, to execute it, use either find() or first()
     *
     * usage:
     * ```
     * $model->query("SELECT * FROM example",[':id' => 2])
     *      ->first();
     * ```
     *
     * @param string $statement SQL statement
     * @param array $values [optional]
     *
     * PDO SQL injection security
     *
     * @return Model
     */
    public function query($statement, array $values = [])
    {
        $this->newQuery()->setQuery($statement, $values);

        return $this;
    }

    /**
     * Create a new Query
     *
     * @return Query
     */
    public function newQuery()
    {
        new Query();
    }

    /**
     * Get the last inserted ID
     * @return string
     */
    public function lastInsertId()
    {
        return DB::get()->lastInsertId();
    }

    /**
     * Saves the entity (add if not exists or updates it)
     *
     * If the Entity has multiple primary keys, only update will work
     *
     * @param Entity $entity
     * @throws EntityException if there is a undefined multiple primary key
     */
    public function save(Entity $entity)
    {
        if (is_string($entity->getPrimaryKeys())) { //si l'entité existe
            $this->update($entity);
        } elseif (is_array($entity->getPrimaryKeys())) {
            foreach ($entity->getPrimaryKeys() as $primary_key) {
                if (!isset($entity->$primary_key)) {
                    throw new EntityException('Cannot add an multiple primary key entity');
                }
            }

            $this->update($entity);
        } else {
            $this->add($entity);
        }

    }

    /**
     * Updates the entity
     *
     * @param Entity $entity
     * @throws IncorrectQueryException
     * @throws UnknownPropertyException
     */
    public function update(Entity $entity)
    {
        $primaries = $entity->getPrimaryKeys();
        $conditions = '';
        if (is_string($primaries)) {
            $conditions = [$primaries, '=', $entity->$primaries];
        } else if (is_array($primaries)) {
            foreach ($entity->getPrimaryKeys() as $primary_key) {
                $conditions['AND'][] = [$primary_key, '=', $entity->$primary_key];
            }
        } else {
            throw new UnknownPropertyException('Incorrect primary key setup');
        }


        $this->newQuery()
            ->update(static::$table)
            ->set($entity)
            ->where([
                'conditions' => $conditions,
            ])
            ->execute();
    }

    /**
     * Adds the entity
     *
     * @param Entity $entity
     * @throws IncorrectQueryException
     */
    public function add(Entity $entity)
    {
        $this->newQuery()
            ->insertInto(static::$table)
            ->values($entity)
            ->execute();
    }


    /**
     * Deletes the entity
     *
     *
     * @param Entity $entity
     * @throws IncorrectQueryException
     * @throws UnknownPropertyException
     */
    public function delete(Entity $entity)
    {
        $primaries = $entity->getPrimaryKeys();
        $conditions = '';

        if (is_string($primaries)) {
            $conditions = [$primaries, '=', $entity->$primaries];
        } else if (is_array($primaries)) {
            foreach ($entity->getPrimaryKeys() as $primary_key) {
                $conditions['AND'][] = [$primary_key, '=', $entity->$primary_key];
            }
        } else {
            throw new UnknownPropertyException('Incorrect primary key setup');
        }

        $this->newQuery()
            ->delete()
            ->from(static::$table)
            ->where(['conditions' => $conditions])
            ->execute();
    }

    /**
     * Gets the entity (use an array : `['id'=> $value]`)
     *
     * in case of multiple primary keys use :
     * ```
     *  [
     *      ['id_name' => $value_name],
     *      ['id_title' => $value_title]
     *  ];
     * ```
     *
     * @param string|array $primary primar(y|ies) key(s)
     * @return mixed
     * @throws EntityException
     * @throws IncorrectQueryException
     */
    public function get($primary)
    {
        $entity_name = isset(static::$entity_name) ? static::$entity_name
            : str_replace('model', 'entity', str_replace('Model', 'Entity', static::class));

        if (!class_exists($entity_name)) {
            throw new EntityException('There is no matching entity ' . $entity_name . 'for this model' . static::class);
        }
        $statement = [];

        foreach ($primary as $index => $item) {
            $statement['AND'][] = [$index, '=', $item];
        }


        $query = $this->newQuery()
            ->select()
            ->from(static::$table)
            ->where(['conditions' => $statement]);

        return DB::get()->queryOne($query, $entity_name);
    }

    public function getAll()
    {
        $query = $this->newQuery()
            ->select()
            ->from(static::$table);

        $entity_name = isset(static::$entity_name) ? static::$entity_name
            : str_replace('model', 'entity', str_replace('Model', 'Entity', static::class));

        return DB::get()->queryOne($query, $entity_name);
    }
}
