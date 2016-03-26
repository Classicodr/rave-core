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

use rave\core\exception\IncorrectQueryException;

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

    private $params;

    private $query_type;

    private $insert_into;
    private $insert_into_values;

    private $select;
    private $delete;
    private $from;
    private $update;
    private $update_set;

    private $where;
    private $more;

    public function __construct()
    {
        $this->params = [];
    }

    /**
     * @param Model|$model
     * @return Query
     * @throws IncorrectQueryException
     *
     * @see values()
     */
    public function insertInto($model)
    {
        if (isset($this->insert_into, $this->query_type)) {
            throw new IncorrectQueryException('Cannot add INSERT INTO statement');
        }

        $this->query_type = self::INSERT;
        $this->insert_into = 'INSERT INTO ';

        if (is_string($model)) {
            $this->insert_into .= $model;
        } elseif (is_subclass_of($model, Model::class, false)) {
            $this->insert_into .= $model->getTable();
        } else {
            throw new IncorrectQueryException('Incorrect class');
        }
    }

    /**
     * VALUE statement, need to be after insert into
     *
     * @param array|Entity $data
     * @return Query
     * @throws IncorrectQueryException
     * @see insertInto()
     */
    public function values($data)
    {
        if (isset($this->insert_into_values) || !isset($this->query_type) || $this->query_type !== self::INSERT) {
            throw new IncorrectQueryException('Cannot add (...)VALUES(...) statement');
        }

        $this->insert_into_values = ' (';

        if (is_array($data)) {
            $rows = $data;
        } elseif (is_subclass_of($data, Entity::class, false)) {
            $rows = get_object_vars($data);
        } else {
            throw new IncorrectQueryException('Not an array, nor an Entity during statement INSERT');
        }

        $columns = '';
        $values = '';

        foreach ($rows as $key => $value) {
            $columns .= $key . ', ';
            $values .= ':' . $key . ', ';
            stripcslashes($value);
            trim($value);
        }

        $this->params[self::VALUES] = $rows;

        $columns = rtrim($columns, ', ');
        $values = rtrim($values, ', ');

        $this->insert_into_values = $columns . ') VALUES (' . $values . ')';

        return $this;
    }

    /**
     * Generates the SELECT statement
     *
     * $params can be an array:
     *
     * ```
     * ['id', 'title', ...]
     * ```
     *
     * or a string :`'id, title'`
     *
     * @param string|array $params [optional]
     * @return Query
     * @throws IncorrectQueryException
     */
    public function select($params = '*')
    {
        if (isset($this->query_type) || isset($this->select)) {
            throw new IncorrectQueryException('Cannot add a select statement');
        }

        $this->query_type = self::SELECT;
        $this->select = 'SELECT ';

        if (is_string($params)) {
            $this->select .= $params . ' ';

            return $this;
        } elseif (is_array($params) && !empty($params)) {
            $this->select .= implode(', ', $params) . ' ';

            return $this;
        }

        throw new IncorrectQueryException('Incorrect SELECT');
    }

    /**
     * Begins the delete statement
     *
     * @return Query $this
     * @throws IncorrectQueryException
     */
    public function delete()
    {
        if (isset($this->query_type)) {
            throw new IncorrectQueryException('Cannot add DELETE statement');
        }

        $this->query_type = self::DELETE;
        $this->delete = 'DELETE ';

        return $this;
    }

    /**
     * adds FROM clause to the query
     *
     * $model can either be :
     * - a string : `blog, ...`
     * - Model Class
     * - array of strings or Model Classes :
     * ```
     * [new ArticleModel(), 'article', ...]
     * ```
     *
     *
     * @param Model|array|$model
     * @return Query
     * @throws IncorrectQueryException
     */
    public function from($model)
    {
        if (isset($this->from) || !isset($this->query_type)
            || ($this->query_type !== self::SELECT && $this->query_type !== self::DELETE)
        ) {
            throw new IncorrectQueryException('Cannot add FROM statement');
        } elseif (is_string($model)) {
            $this->from = 'FROM ' . $model . ' ';

            return $this;
        } elseif (is_subclass_of($model, Model::class, false)) {
            $this->from = 'FROM ' . $model->getTable() . ' ';
        } elseif (is_array($model)) {
            $this->from = 'FROM ';

            foreach ($model as $item) {

                if (is_subclass_of($item, Model::class, false)) {
                    /** @var Model $item */
                    $this->from .= $item->getTable() . ', ';
                } elseif (is_string($item)) {
                    $this->from .= $item . ', ';
                }
            }

            $this->from = rtrim($this->from, ', ');
            $this->from .= ' ';
        }

        return $this;
    }

    /**
     * @param Model|$model
     * @return Query
     * @throws IncorrectQueryException
     */
    public function update($model)
    {
        if (isset($this->update, $this->query_type)) {
            throw new IncorrectQueryException('Cannot add UPDATE statement');
        }

        $this->query_type = self::UPDATE;
        $this->update = 'UPDATE ';

        if (is_string($model)) {
            $this->update .= $model . ' ';
        } elseif (is_subclass_of($model, Model::class, false)) {
            $this->update .= $model->getTable() . ' ';
        } else {
            throw new IncorrectQueryException('Incorrect class');
        }

        return $this;
    }

    /**
     * @param array|Entity $data
     * @return Query
     * @throws IncorrectQueryException
     */
    public function set($data)
    {
        if (isset($this->update_set) || !isset($this->query_type) || $this->query_type !== self::UPDATE) {
            throw new IncorrectQueryException('Cannot add SET statement');
        }

        if (is_array($data)) {
            $rows = $data;
        } elseif (is_subclass_of($data, Entity::class, false)) {
            $rows = get_object_vars($data);
        } else {
            throw new IncorrectQueryException('Not an array nor an Entity during statement SET');
        }

        $set = '';

        foreach ($rows as $key => $value) {
            $set .= $key . ' = :' . $key . ', ';
            stripcslashes($value);
            trim($value);
        }

        $set = rtrim($set, ', ');

        $this->update_set = 'SET ' . $set . ' ';

        $this->params[self::VALUES] = $rows;

        return $this;
    }

    /**
     * Adds a WHERE clause to the query
     * $params MUST HAVE this structure :
     *
     * ```
     * [
     *   'conditions' => 'id = :id AND ...',
     *   'values' => [':id'=> 2, ...]
     * ]
     * ```
     *
     * @param array $params [optional]
     * @return Query
     * @throws IncorrectQueryException
     */
    public function where(array $params = [])
    {
        if (isset($this->where) || !isset($this->query_type) || $this->query_type === self::INSERT) {
            throw new IncorrectQueryException('Cannot add a WHERE statement');
        }

        if (empty($params)) {
            throw new IncorrectQueryException('Empty WHERE statement');

        }

        if (is_string($params[self::CONDITIONS])) {
            $this->where = 'WHERE ' . $params[self::CONDITIONS] . ' ';

            if (!isset($params[self::VALUES])) {
                $params[self::VALUES] = [];
            }

            if (self::UPDATE === $this->query_type) {
                $this->params[self::VALUES] = array_merge($this->params[self::VALUES], $params[self::VALUES]);
            } else {
                $this->params[self::VALUES] = $params[self::VALUES];
            }
        } elseif (is_array($params[self::CONDITIONS])) {
            $this->where = 'WHERE ' . $this->createWhere($params[self::CONDITIONS]) . ' ';
        }

        return $this;
    }

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

        } else {
            $where = $conditions[0] . ' ' . $conditions[1] . ' :';

            if (isset($this->params[self::VALUES][$conditions[0]])) {
                $conditions[0] .= $count;
                ++$count;
            }

            $where .= $conditions[0];
            $this->params[self::VALUES][$conditions[0]] = $conditions[2];
        }

        return $where;
    }

    /**
     * Appends to the sql query
     *
     * @param string $more
     * @return Query
     */
    public function appendSQL($more)
    {
        $this->more = $more;

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

        return $this->params;
    }

    /**
     * Returns the statement
     *
     * @return string
     * @throws IncorrectQueryException
     */
    private function concat()
    {
        if (!isset($this->query_type)) {
            throw new IncorrectQueryException('Cannot concat inexisting statement');
        } elseif ($this->query_type === self::CUSTOM) {
            // NE FAIT RIEN
        } elseif ($this->query_type === self::INSERT) {
            if (!isset($this->insert_into, $this->insert_into_values)) {
                throw new IncorrectQueryException('Incomplete INSERT statement');
            }

            $this->params[self::STATEMENT] = $this->insert_into . $this->insert_into_values;
        } elseif ($this->query_type === self::SELECT) {
            if (!isset($this->select, $this->from)) {
                throw new IncorrectQueryException('Incomplete SELECT statement');
            }

            $this->params[self::STATEMENT] = $this->select . $this->from . $this->where;
        } elseif ($this->query_type === self::UPDATE) {
            if (!isset($this->update, $this->update_set, $this->where)) {
                throw new IncorrectQueryException('Incomplete UPDATE statement');
            }

            $this->params[self::STATEMENT] = $this->update . $this->update_set . $this->where;
        } elseif ($this->query_type === self::DELETE) {
            if (!isset($this->delete, $this->from, $this->where)) {
                throw new IncorrectQueryException('Incomplete DELETE statement');
            }

            $this->params[self::STATEMENT] = $this->delete . $this->from . $this->where;
        }

        $this->params[self::STATEMENT] .= $this->more;
    }

    /**
     * Returns the statement
     *
     * @return string
     */
    public function getStatement()
    {
        $this->concat();

        return $this->params[self::STATEMENT];
    }

    /**
     * Returns the values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->params[self::VALUES];
    }

    /**
     * Custom query generator
     * @param string $statement
     * @param array $values [optional]
     */
    public function setQuery($statement, array $values = [])
    {
        $this->query_type = self::CUSTOM;
        $this->params[self::STATEMENT] = $statement;
        $this->params[self::VALUES] = $values;
    }
}
