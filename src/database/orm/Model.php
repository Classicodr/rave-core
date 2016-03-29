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

namespace rave\core\database\orm;

use rave\core\DB;
use rave\core\exception\EntityException;
use rave\core\exception\IncorrectQueryException;
use rave\core\exception\UnknownPropertyException;

abstract class Model
{
    protected static $table;

    protected static $entity_name;

    /**
     * @return string <p>the name of the table associated to the model</p>
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * Prepare the given query, to execute it, use either find() or first()
     * usage:
     * ```
     * $model->query("SELECT * FROM example",[':id' => 2])
     *      ->first();
     * ```
     *
     * @param string $statement SQL statement
     * @param array $values [optional]
     * PDO SQL injection security
     * @return Model
     * @deprecated use newQuery() instead
     * @see newQuery()
     */
    public function setQuery($statement, array $values = [])
    {
        return $this->newQuery($statement, $values);
    }

    /**
     * Returns a new Query Object
     * Can be used for directly define the query :
     * ```
     * newQuery("SELECT * FROM example WHERE id = :id",[':id' => 2 ])
     * ```
     *
     * @param null $statement
     * @param array $values
     * @return Query
     */
    public function newQuery($statement = null, array $values = null)
    {
        return Query::create($statement, $values);
    }

    /**
     * Get the last inserted ID
     *
     * @return string
     */
    public function lastInsertId()
    {
        return DB::get()->lastInsertId();
    }

    /**
     * Saves the entity (add if not exists or updates it)
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
        } else {
            if (is_array($primaries)) {
                foreach ($entity->getPrimaryKeys() as $primary_key) {
                    $conditions['AND'][] = [$primary_key, '=', $entity->$primary_key];
                }
            } else {
                throw new UnknownPropertyException('Incorrect primary key setup');
            }
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
        } else {
            if (is_array($primaries)) {
                foreach ($entity->getPrimaryKeys() as $primary_key) {
                    $conditions['AND'][] = [$primary_key, '=', $entity->$primary_key];
                }
            } else {
                throw new UnknownPropertyException('Incorrect primary key setup');
            }
        }

        $this->newQuery()
            ->delete()
            ->from(static::$table)
            ->where([$conditions])
            ->execute();
    }

    /**
     * Gets the entity (use an array : `['id'=> $value]`)
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
        $entity_name = $this->getEntityName();

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
            ->where([$statement]);

        return DB::get()->queryOne($query, $entity_name);
    }

    /**
     * returns the Entity class name
     *
     * @return mixed
     */
    public static function getEntityName()
    {
        return isset(static::$entity_name) ? static::$entity_name
            : str_replace('model', 'entity', str_replace('Model', 'Entity', static::class));
    }

    /**
     * Returns all the elements of the Table
     *
     * @return array|null
     * @throws IncorrectQueryException
     */
    public function all()
    {
        $query = $this->newQuery()
            ->select()
            ->from(static::$table);

        $entity_name = $this->getEntityName();

        return DB::get()->query($query, $entity_name);
    }
}
