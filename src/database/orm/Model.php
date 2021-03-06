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

/**
 * Class Model
 *
 * @package rave\core\database\orm
 */
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
     * Saves the entity (add if not exists or updates it)
     * If the Entity has multiple primary keys, only update will work
     *
     * @param Entity $entity
     * @throws EntityException if there is a undefined multiple primary key
     */
    public static function save(Entity $entity)
    {
        $primary = $entity->getPrimaryKeys();
        if (is_string($primary) && isset($entity->$primary)) { //si l'entité existe
            self::update($entity);
        } elseif (is_array($primary)) {
            foreach ($primary as $primary_key) {
                if (!isset($entity->$primary_key)) {
                    throw new EntityException('Cannot add an multiple primary key entity');
                }
            }

            self::update($entity);
        } else {
            self::add($entity);
        }

    }

    /**
     * Updates the entity
     *
     * @param Entity $entity
     * @throws IncorrectQueryException
     * @throws UnknownPropertyException
     */
    public static function update(Entity $entity)
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

        Query::create()
            ->update(static::$table)
            ->set($entity)
            ->where($conditions)
            ->execute();
    }

    /**
     * Adds the entity
     *
     * @param Entity $entity
     * @throws IncorrectQueryException
     */
    public static function add(Entity $entity)
    {
        self::newQuery()
            ->insertInto(static::$table)
            ->values($entity)
            ->execute();
    }

    /**
     * Returns a new Query Object
     * Can be used for directly define the query :
     * ```
     * newQuery("SELECT * FROM example WHERE id = :id",[':id' => 2 ])
     * ```
     *
     * @param null|string $statement
     * @param array $values
     * @return Query
     */
    public static function newQuery($statement = null, array $values = [])
    {
        return Query::create($statement, $values);
    }

    /**
     * Get the last inserted ID
     *
     * @return string
     */
    public static function lastInsertId()
    {
        return DB::get()->lastInsertId();
    }

    /**
     * Deletes the entity
     *
     * @param Entity $entity
     * @throws IncorrectQueryException
     * @throws UnknownPropertyException
     */
    public static function delete(Entity $entity)
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

        Query::create()
            ->delete()
            ->from(static::$table)
            ->where($conditions)
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
    public static function get($primary)
    {
        $entity_name = self::getEntityName();

        if (!class_exists($entity_name)) {
            throw new EntityException('There is no matching entity ' . $entity_name . 'for this model' . static::class);
        }
        $statement = [];

        foreach ($primary as $index => $item) {
            $statement['AND'][] = [$index, '=', $item];
        }

        $query = Query::create()
            ->select()
            ->from(static::$table)
            ->where($statement);

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
    public static function all()
    {
        $query = Query::create()
            ->select()
            ->from(static::$table);

        $entity_name = self::getEntityName();

        return DB::get()->query($query, $entity_name);
    }

    /**
     * Return the number of rows
     *
     * @return int|null
     * @throws IncorrectQueryException
     */
    public static function count()
    {
        $query = self::newQuery()->select('COUNT(*) as count')->from(static::$table)->first();

        return $query ? $query->count : null;
    }
}
