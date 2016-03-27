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

    private $build;

    public function __construct()
    {
        $this->params = [];
    }

    /**
     * Custom query generator
     *
     * @param string $statement
     * @param array $values [optional]
     *
     * @return $this
     */
    public function setQuery($statement, array $values = [])
    {
        $this->query_type = self::CUSTOM;
        $this->params[self::STATEMENT] = $statement;
        $this->params[self::VALUES] = $values;

        return $this;
    }

    /**
     * @param Model|$model
     *
     * @return Query
     * @throws IncorrectQueryException
     *
     * @see values()
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
            throw new IncorrectQueryException('Incorrect class');
        }
    }

    /**
     * VALUE statement, need to be after insert into
     *
     * @param array|Entity $data
     *
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
        $clean = [];

        foreach ($rows as $key => $value) {
            if (!is_null($value)) {
                $columns .= $key . ', ';
                $values .= ':' . $key . ', ';
                $clean[':' . $key] = $value;
            }
        }

        $columns = rtrim($columns, ', ');
        $values = rtrim($values, ', ');
        $this->build['insert_into_values'] .= $columns . ') VALUES (' . $values . ')';
        $this->params[self::VALUES] = $clean;

        return $this;
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
        $this->build['delete'] = 'DELETE ';

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
     *
     * @return Query
     * @throws IncorrectQueryException
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

            return $this;
        } elseif (is_array($params) && !empty($params)) {
            $this->build['select'] .= implode(', ', $params) . ' ';

            return $this;
        }

        throw new IncorrectQueryException('Incorrect SELECT');
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
     *
     * @return Query
     * @throws IncorrectQueryException
     */
    public function from($model)
    {
        if (isset($this->build['from']) || !isset($this->query_type)
            || ($this->query_type !== self::SELECT && $this->query_type !== self::DELETE)
        ) {
            throw new IncorrectQueryException('Cannot add FROM statement');
        } elseif (is_string($model)) {
            $this->build['from'] = 'FROM ' . $model . ' ';

            return $this;
        } elseif (is_subclass_of($model, Model::class, false)) {
            $this->build['from'] = 'FROM ' . $model->getTable() . ' ';
        } elseif (is_array($model)) {
            $this->build['from'] = 'FROM ';

            foreach ($model as $item) {

                if (is_subclass_of($item, Model::class, false)) {
                    /** @var Model $item */
                    $this->build['from'] .= $item->getTable() . ', ';
                } elseif (is_string($item)) {
                    $this->build['from'] .= $item . ', ';
                } else {
                    throw new IncorrectQueryException('Unsupported Model value in FROM : ' . $item);
                }
            }

            $this->build['from'] = rtrim($this->build['from'], ', ');
            $this->build['from'] .= ' ';
        } else {
            throw new IncorrectQueryException('Unsupported Model value in FROM : ' . $model);
        }

        return $this;
    }

    /**
     * Begins the UPDATE Query, use set()
     * @see set()
     *
     * @param Model|string $model
     *
     * @return Query
     * @throws IncorrectQueryException
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
            throw new IncorrectQueryException('Incorrect class');
        }

        return $this;
    }

    /**
     * @param array|Entity $data
     *
     * @return Query
     * @throws IncorrectQueryException
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
        $clean = [];

        foreach ($rows as $key => $value) {
            if (!is_null($value)) {
                $columns .= $key . ' = :' . $key . ', ';
                $clean[':' . $key] = $value;
            }
        }

        $columns = rtrim($columns, ', ');

        $this->build['update_set'] = 'SET ' . $columns . ' ';

        $this->params[self::VALUES] = $clean;

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
     * or
     *
     * ```
     * [
     *   'conditions' => [
     *      'AND'=>[
     *          ['id', '=', $id],
     *          ['title', '!=', $title],
     *          OR => [['apple','=',$red]]
     *      ]
     *      ]
     * ]
     * ```
     *
     * @param array $params
     *
     * @return Query
     * @throws IncorrectQueryException
     */
    public function where(array $params = [])
    {
        if (isset($this->build['where']) || !isset($this->query_type) || $this->query_type === self::INSERT) {
            throw new IncorrectQueryException('Cannot add a WHERE statement');
        }

        if (empty($params)) {
            throw new IncorrectQueryException('Empty WHERE statement');
        }

        if (is_string($params[self::CONDITIONS])) {
            $this->build['where'] = 'WHERE ' . $params[self::CONDITIONS] . ' ';

            if (!isset($params[self::VALUES])) {
                $params[self::VALUES] = [];
            }

            if (self::UPDATE === $this->query_type) {
                $this->params[self::VALUES] = array_merge($this->params[self::VALUES], $params[self::VALUES]);
            } else {
                $this->params[self::VALUES] = $params[self::VALUES];
            }
        } elseif (is_array($params[self::CONDITIONS])) {
            $this->build['where'] = 'WHERE ' . $this->createWhere($params[self::CONDITIONS]) . ' ';
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

            if (isset($this->params[self::VALUES][':' . $conditions[0]])) {
                $conditions[0] .= $count;
                ++$count;
            }

            $where .= $conditions[0];
            $this->params[self::VALUES][':' . $conditions[0]] = $conditions[2];
        }

        return $where;
    }

    /**
     * Appends to the sql query
     *
     * @param string $more
     *
     * @return Query
     */
    public function appendSQL($more)
    {
        $this->build['more'] = $more;

        return $this;
    }

    /**
     * Execute this query
     */
    public function execute()
    {
        DB::get()->execute($this);
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
     *
     * @param string|array|null $options [optional]
     *
     * @return mixed
     *
     * @throws EntityException
     * @throws IncorrectQueryException if the query is not correct
     */
    public function find($options = null)
    {
        if (is_array($options)) {
            $this->select($options['select'])
                 ->from($options['from'])
                 ->where($options['where']);

            if (isset($options['append'])) {
                $this->appendSQL($options['append']);
            }

            return DB::get()->query($this);

        } elseif (isset($this->query_type)) {
            if ('first' === $options) {
                return DB::get()->queryOne($this);

            }

            return DB::get()->query($this);
        }
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
        if (!isset($this->query_type)) {
            throw new IncorrectQueryException('The query is incorrect');
        }

        return DB::get()->queryOne($this);
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
     * Converts query parts to a string
     *
     * checks if all the required parts are present for a specific sql query
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
                if (!isset($this->build['insert_into'], $this->build['insert_into_values'])) {
                    throw new IncorrectQueryException('Incomplete INSERT statement');
                }

                $this->params[self::STATEMENT] = $this->build['insert_into'] . $this->build['insert_into_values'];
                break;

            case self::SELECT:
                if (!isset($this->build['select'], $this->build['from'])) {
                    throw new IncorrectQueryException('Incomplete SELECT statement');
                }

                $this->params[self::STATEMENT] = $this->build['select'] . $this->build['from'];

                if (isset($this->build['where'])) {
                    $this->params[self::STATEMENT] .= $this->build['where'];
                }
                break;

            case self::UPDATE:
                if (!isset($this->build['update'], $this->build['update_set'], $this->build['where'])) {
                    throw new IncorrectQueryException('Incomplete UPDATE statement');
                }

                $this->params[self::STATEMENT] = $this->build['update'] . $this->build['update_set']
                                                 . $this->build['where'];
                break;

            case self::DELETE:
                if (!isset($this->build['delete'], $this->build['from'], $this->build['where'])) {
                    throw new IncorrectQueryException('Incomplete DELETE statement');
                }

                $this->params[self::STATEMENT] = $this->build['delete'] . $this->build['from'] . $this->build['where'];
                break;

            case self::CUSTOM:
            default:
                //Doesn't do anyting ^^
                break;
        }

        if (isset($this->build['more'])) {
            $this->params[self::STATEMENT] .= $this->build['more'];
        }
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

}
