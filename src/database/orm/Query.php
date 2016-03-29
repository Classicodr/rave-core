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
use rave\core\exception\IncorrectQueryException;

/**
 * Class Query
 *
 * @package rave\core\database\orm
 */
class Query
{
    const CONDITIONS = 'conditions';
    const STATEMENT = 'statement';
    const VALUES = 'values';

    const CUSTOM = 0;
    const INSERT = 1;
    const SELECT = 2;
    const UPDATE = 3;
    const DELETE = 4;

    private $statement;
    private $values;

    private $query_type;

    private $build;

    /**
     * Returns a new Query instance
     * Can be used for directly define the query :
     * ```
     * newQuery("SELECT * FROM example WHERE id = :id",[':id' => 2])
     * ```
     *
     * @param null $statement
     * @param array $values
     * @return $this
     */
    public static function create($statement = null, array $values = [])
    {
        if (isset($statement)) {
            return Query::create()->setQuery($statement, $values);
        }

        return new Query;
    }

    /**
     * Custom query generator
     * $statement will not be checked
     *
     * @param string $statement
     * @param array $values [optional]
     * @return $this
     */
    public function setQuery($statement, array $values = null)
    {
        $this->query_type = self::CUSTOM;
        $this->statement = $statement;

        if (isset($values)) {
            $this->values = $values;
        }

        return $this;
    }

    /**
     * Initialize `INSERT INTO` sql declaration,
     * requires values() to work properly
     *
     * @param Model|string $model
     * @return $this
     * @throws IncorrectQueryException if the statement is already initialized
     * @see rave\core\database\orm\Query::values()
     */
    public function insertInto($model)
    {
        if (isset($this->build['insert_into'], $this->query_type)) {
            throw new IncorrectQueryException('Cannot add INSERT INTO statement');
        }

        $this->query_type = self::INSERT;
        $this->build['insert_into'] = 'INSERT INTO ';

        if (is_string($model)) {
            $this->build['insert_into'] .= $model;
        } elseif (is_subclass_of($model, Model::class, false)) {
            $this->build['insert_into'] .= $model->getTable();
        } else {
            throw new IncorrectQueryException('Incorrect class in INSERT');
        }

        return $this;
    }

    /**
     * Begins the delete statement
     * must be followed by from() and where() to work properly
     *
     * @return $this
     * @throws IncorrectQueryException
     * @see rave\core\database\orm\Query::from()
     * @see rave\core\database\orm\Query::where()
     */
    public function delete()
    {
        if (isset($this->query_type)) {
            throw new IncorrectQueryException('Cannot add DELETE statement');
        }

        $this->query_type = self::DELETE;
        $this->build['delete'] = 'DELETE ';

        return $this;
    }

    /**
     * Begins the UPDATE declaration
     * requires set() and where() to work
     *
     * @param Model|string $model
     * @return $this
     * @throws IncorrectQueryException
     * @see rave\core\database\orm\Query::set()
     * @see rave\core\database\orm\Query::where()
     */
    public function update($model)
    {
        if (isset($this->build['update'], $this->query_type)) {
            throw new IncorrectQueryException('Cannot add UPDATE statement');
        }

        $this->query_type = self::UPDATE;
        $this->build['update'] = 'UPDATE ';

        if (is_string($model)) {
            $this->build['update'] .= $model . ' ';
        } elseif (is_subclass_of($model, Model::class, false)) {
            $this->build['update'] .= $model->getTable() . ' ';
        } else {
            throw new IncorrectQueryException('Incorrect parameter on UPDATE');
        }

        return $this;
    }

    /**
     * Part of the UPDATE sql declaration
     * Can set an array
     * ```
     * ['title'=> 'Harry Potter', 'author' => 'J.K Rowling']
     * ```
     * or an Entity
     *
     * @param array|Entity $data
     * @return $this
     * @throws IncorrectQueryException
     * @see rave\core\database\orm\Query::update();
     */
    public function set($data)
    {
        if (isset($this->build['update_set']) || !isset($this->query_type) || $this->query_type !== self::UPDATE) {
            throw new IncorrectQueryException('Cannot add SET statement');
        }

        if (is_array($data)) {
            $rows = $data;
        } elseif (is_subclass_of($data, Entity::class, false)) {
            $rows = get_object_vars($data);
        } else {
            throw new IncorrectQueryException('Not an array nor an Entity during statement SET');
        }

        $columns = '';

        foreach ($rows as $key => $value) {
            if (isset($value)) {
                $columns .= $key . ' = :' . $key . ', ';
                $clean[':' . $key] = $value;
            }
        }

        $columns = rtrim($columns, ', ');

        $this->build['update_set'] = 'SET ' . $columns . ' ';

        if (isset($clean)) {
            $this->values = $clean;
        }

        return $this;
    }

    /**
     * Execute this query
     */
    public function execute()
    {
        DB::get()->execute($this);

        return true;
    }

    /**
     * Executes the query
     * if `$options = 'first'` then, it will use `$this->first()`
     * if $entity_name is defined, will return the result as Entity
     *
     * @see GenericDriver::queryOne()
     * @param string|null $options [optional]
     * @param null $entity_name
     * @return array|null
     * @throws IncorrectQueryException if the query is not correct
     */
    public function find($options = null, $entity_name = null)
    {
        if (!isset($this->query_type)) {
            throw new IncorrectQueryException('The query is incorrect');
        }

        if ($options === 'first') {
            return $this->first($entity_name);
        }

        return DB::get()->query($this, $entity_name);

    }

    /**
     * Return the first matching value of the query
     *
     * if $entity_name is defined, will return the result as Entity
     *
     * @see queryOne()
     * @see find('first')
     * @param string|null $entity_name the name of the Entity class to use
     * @return mixed
     * @throws IncorrectQueryException
     */
    public function first($entity_name = null)
    {
        if (!isset($this->query_type)) {
            throw new IncorrectQueryException('The query is incorrect');
        }

        return DB::get()->queryOne($this, $entity_name);
    }

    /**
     * Appends to the sql declaration
     *
     * @param string $more
     * @return $this
     */
    public function appendSQL($more)
    {
        $this->build['more'] = $more;

        return $this;
    }

    /**
     * Adds a WHERE clause to the query
     * $params MUST HAVE this structure :
     * ```
     * [
     *   'conditions' => 'id = :id AND ...',
     *   'values' => [':id'=> 2, ...]
     * ]
     * ```
     * or
     * ```
     *  [
     *      'AND'=>
     *      [
     *          ['id', '=', $id],
     *          ['title', '!=', $title],
     *          OR => [['apple','=',$red]]
     *      ]
     *  ]
     * ```
     *
     * @param array $params
     * @return $this
     * @throws IncorrectQueryException
     */
    public function where(array $params)
    {
        if (isset($this->build['where']) || !isset($this->query_type) || $this->query_type === self::INSERT) {
            throw new IncorrectQueryException('Cannot add a WHERE statement');
        }

        if (isset($params[self::CONDITIONS]) && is_string($params[self::CONDITIONS])) {
            $this->build['where'] = 'WHERE ' . $params[self::CONDITIONS] . ' ';

            if (isset($params[self::VALUES])) {
                if (self::UPDATE === $this->query_type) {
                    $this->values = array_merge($this->values, $params[self::VALUES]);
                } else {
                    $this->values = $params[self::VALUES];
                }
            }

        } elseif (is_array($params)) {
            $this->build['where'] = 'WHERE ' . $this->createWhere($params) . ' ';
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @param int $count
     * @return string
     * @throws IncorrectQueryException
     */
    private function createWhere(array $conditions, &$count = 0)
    {
        if (isset($conditions['AND'])) {
            $option = 'AND';
        } elseif (isset($conditions['OR'])) {
            $option = 'OR';
        }

        if (isset($option)) {

            foreach ($conditions[$option] as $key => $condition) {
                if (!is_int($key)) {
                    $conditions[$option][$key] = $this->createWhere([$key => $condition], $count);
                } else {
                    $conditions[$option][$key] = $this->createWhere($condition, $count);
                }
            }
            $where = '(' . implode(' ' . $option . ' ', $conditions[$option]) . ')';

        } elseif (count($conditions) === 3) {
            $where = $conditions[0] . ' ' . $conditions[1] . ' :';

            if (isset($this->values[':' . $conditions[0]])) {
                $conditions[0] .= $count;
                ++$count;
            }

            $where .= $conditions[0];
            $this->values[':' . $conditions[0]] = $conditions[2];
        } else {
            throw new IncorrectQueryException('Bad where construction');
        }

        return $where;
    }

    /**
     * adds FROM clause to the query
     * $model can either be :
     * - a string : `blog, ...`
     * - Model Class
     * - array of strings or Model Classes :
     * ```
     * [new ArticleModel(), 'article', ...]
     * ```
     *
     * @param Model|array|$model
     * @return $this
     * @throws IncorrectQueryException
     */
    public function from($model)
    {
        if (isset($this->build['from']) || !isset($this->query_type)
            || ($this->query_type !== self::SELECT && $this->query_type !== self::DELETE)
        ) {
            throw new IncorrectQueryException('Cannot add FROM statement');
        }

        $this->build['from'] = 'FROM ';

        if (is_array($model)) {
            foreach ($model as $item) {
                $this->build['from'] .= self::createFrom($item) . ', ';
            }

            $this->build['from'] = rtrim($this->build['from'], ', ') . ' ';
        } else {
            $this->build['from'] .= self::createFrom($model) . ' ';
        }

        return $this;
    }

    /**
     * @param Model|string $model
     * @return string
     * @throws IncorrectQueryException
     */
    private static function createFrom($model)
    {
        if (is_string($model)) {
            return $model;
        } elseif (is_subclass_of($model, Model::class, false)) {
            return $model->getTable();
        } else {
            throw new IncorrectQueryException('Unsupported Model value in FROM : ' . $model);
        }
    }

    /**
     * Generates the SELECT statement
     * must be folowwed by from() to work properly
     * $params can be an array:
     * ```
     * ['id', 'title', ...]
     * ```
     * or a string :`'id, title'`
     *
     * @param string|array $params [optional]
     * @return $this
     * @throws IncorrectQueryException
     * @see rave\core\database\orm\Query::from()
     */
    public function select($params = '*')
    {
        if (isset($this->query_type) || isset($this->build['select'])) {
            throw new IncorrectQueryException('Cannot add a select statement');
        }

        $this->query_type = self::SELECT;
        $this->build['select'] = 'SELECT ';

        if (is_string($params)) {
            $this->build['select'] .= $params . ' ';
        } elseif (is_array($params) && !empty($params)) {
            $this->build['select'] .= implode(', ', $params) . ' ';
        } else {
            throw new IncorrectQueryException('Incorrect SELECT');
        }

        return $this;
    }

    /**
     * VALUE statement, need to be after `insertInto()`
     * the values can either be from an Entity or an array :
     * ```
     * ['title' => 'Hello World', 'name' => 'Jackson Five']
     * ```
     *
     * @param array|Entity $data
     * @return $this
     * @throws IncorrectQueryException
     * @see insertInto()
     */
    public function values($data)
    {
        if (isset($this->build['insert_into_values']) || !isset($this->query_type)
            || $this->query_type !== self::INSERT
        ) {
            throw new IncorrectQueryException('Cannot add (...)VALUES(...) statement');
        }

        if (is_array($data)) {
            $rows = $data;
        } elseif (is_subclass_of($data, Entity::class, false)) {
            $rows = get_object_vars($data);
        } else {
            throw new IncorrectQueryException('Not an array, nor an Entity in INSERT declaration');
        }

        $columns = '';
        $values = '';

        foreach ($rows as $key => $value) {
            if (isset($value)) {
                $columns .= $key . ', ';
                $values .= ':' . $key . ', ';
                $clean[':' . $key] = $value;
            }
        }

        $columns = rtrim($columns, ', ');
        $values = rtrim($values, ', ');
        $this->build['insert_into_values'] = ' (' . $columns . ') VALUES (' . $values . ')';

        if (isset($clean)) {
            $this->values = $clean;
        }

        return $this;
    }

    /**
     * Returns the array containing the SQL query
     *
     * @return array
     * @see getStatement()
     * @see getValues()
     */
    public function getParams()
    {
        $this->concat();

        return isset($this->values) ? [self::STATEMENT => $this->statement, self::VALUES => $this->values]
            : [self::STATEMENT => $this->statement];
    }

    /**
     * Converts query parts to a string
     *
     * @throws IncorrectQueryException
     */
    private function concat()
    {
        if (!isset($this->query_type)) {
            throw new IncorrectQueryException('Cannot concat inexisting statement');
        }

        switch ($this->query_type) {
            case self::INSERT:
                $this->createInsert();
                break;

            case self::SELECT:
                $this->createSelect();
                break;

            case self::UPDATE:
                $this->createUpdate();
                break;

            case self::DELETE:
                $this->createDelete();
                break;

            default:
                break;
        }

        if (isset($this->build['more'])) {
            $this->statement .= $this->build['more'];
        }

        return true;
    }

    /**
     * Verifies and create the INSERT query, if INSERT INTO and VALUES are defined
     *
     * @throws IncorrectQueryException
     */
    private function createInsert()
    {
        if (!isset($this->build['insert_into'], $this->build['insert_into_values'])) {
            throw new IncorrectQueryException('Incomplete INSERT statement');
        }

        $this->statement = $this->build['insert_into'] . $this->build['insert_into_values'];

        return true;
    }

    /**
     * Verifies and create the SELECT query if at least SELECT and FROM are defined
     *
     * @throws IncorrectQueryException
     */
    private function createSelect()
    {
        if (!isset($this->build['select'], $this->build['from'])) {
            throw new IncorrectQueryException('Incomplete SELECT statement');
        }

        $this->statement = $this->build['select'] . $this->build['from'];

        if (isset($this->build['where'])) {
            $this->statement .= $this->build['where'];
        }

        return true;
    }

    /**
     * Verifies and create the SELECT query if UPDATE, SET and WHERE are defined
     *
     * @throws IncorrectQueryException
     */
    private function createUpdate()
    {
        if (!isset($this->build['update'], $this->build['update_set'], $this->build['where'])) {
            throw new IncorrectQueryException('Incomplete UPDATE statement');
        }

        $this->statement = $this->build['update'] . $this->build['update_set']
            . $this->build['where'];

        return true;

    }

    /**
     * Verifies and create the SELECT query if at least DELETE, FROM and WHERE are defined
     *
     * @throws IncorrectQueryException
     */
    private function createDelete()
    {
        if (!isset($this->build['delete'], $this->build['from'], $this->build['where'])) {
            throw new IncorrectQueryException('Incomplete DELETE statement');
        }

        $this->statement = $this->build['delete'] . $this->build['from'] . $this->build['where'];

        return true;

    }

    /**
     * Returns the statement
     *
     * @return string
     */
    public function getStatement()
    {
        $this->concat();

        return $this->statement;
    }

    /**
     * Returns the values
     *
     * @return array|null
     */
    public function getValues()
    {
        return isset($this->values) ? $this->values : null;
    }

}
