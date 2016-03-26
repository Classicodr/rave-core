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

use rave\core\Database;
use rave\core\exception\EntityException;
use rave\core\exception\IncorrectQueryException;
use rave\core\exception\UnknownPropertyException;

abstract class Model
{
    const SQL = 'sql';
    const VALUES = 'values';
    const DRIVER = 'driver';

    protected static $table;
    protected static $primary = 'id';
    protected static $entity_name;

    /** @var Query $query */
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
        $this->query = new Query();

        return $this->query;
    }

    /**
     * Get the last inserted ID
     * @return string
     */
    public function lastInsertId()
    {
        return Database::get()->lastInsertId();
    }

    /**
     * Saves the entity (add if not exists or updates it)
     *
     * @param Entity $entity
     */
    public function save(Entity $entity)
    {
        if (isset($entity->{static::$primary})) { //si l'entité existe
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
        $this->newQuery()
            ->update(static::$table)
            ->set($entity)
            ->where([
                'conditions' => static::$primary . ' = :' . static::$primary,
                'values' => [':' . static::$primary => $entity->get(static::$primary)]
            ]);

        Database::get()->query($this->query);
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
            ->values($entity);

        Database::get()->query($this->query);
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
        $this->newQuery()
            ->delete()
            ->from(static::$table)
            ->where(['conditions' => static::$primary . ' = :' . static::$primary, 'values' => [':' . static::$primary => $entity->get(static::$primary)]]);

        Database::get()->execute($this->query);
    }

    /**
     * Gets the entity
     *
     * @param $primary
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

        $this->newQuery()
            ->select()
            ->from(static::$table)
            ->where(['conditions' => static::$primary . ' = :primary',
                'values' => [':primary' => $primary]]);

        return Database::get()->queryOne($this->query, $entity_name);
    }

    /**
     * Return the first matching value of the query
     *
     * @see queryOne()
     * @see find('first')
     * @return mixed
     * @throws IncorrectQueryException
     */
    public function first()
    {
        if (is_null($this->query)) {
            throw new IncorrectQueryException('The query is incorrect');
        }

        return Database::get()->queryOne($this->query);
    }

    /**
     * Executes the query
     *
     *<p>
     * Can either be :
     * <ul>
     * <li>a string :
     *  <ul>
     *      <li>'all' `SELECT * ` from the current table</li>
     *      <li>'first' select first matching element</li>
     *  </ul>
     * </li>
     * <li>an array :
     * <code>
     *  ['select' => ['id', 'title'],
     *      'from' =>
     *          ['articles', new BlogModel()],
     *      'where' =>
     *          [
     *              'conditions' => 'id = :id',
     *              'values' => [':id' => 2]
     *          ],
     *      'append' => 'GROUP BY id'
     *  ]
     * </code>
     * </li>
     * <ul></p>
     *
     * @see GenericDriver::queryOne()
     * @param string|array|null $options [optional]
     * @return mixed
     *
     * @throws EntityException
     * @throws IncorrectQueryException if the query is not correct
     */
    public function find($options = null)
    {
        if (is_array($options)) {
            $this->newQuery()
                ->select($options['select'])
                ->from($options['from'])
                ->where($options['where']);

            if (isset($options['append'])) {
                $this->query->appendSQL($options['append']);
            }

            return Database::get()->query($this->query);
        } elseif ('all' === $options || !isset($this->query) && null === $options) {

            $entity_name = isset(static::$entity_name) ? static::$entity_name : str_replace('model', 'entity', str_replace('Model', 'Entity', static::class));

            if (!class_exists($entity_name)) {
                throw new EntityException('There is no matching entity ' . $entity_name . 'for this model' . static::class);
            }

            $this->newQuery()
                ->select()
                ->from(static::$table);

            return Database::get()->query($this->query, $entity_name);
        } elseif (isset($this->query)) {
            return Database::get()->query($this->query);
        } elseif ('first' === $options) {
            return Database::get()->queryOne($this->query);

        }

        throw new IncorrectQueryException('The query is incorrect');
    }

}
