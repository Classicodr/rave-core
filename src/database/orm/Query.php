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

    private $requirements = [
        self::INSERT => [
            'name' => 'INSERT',
            'require' => ['insert_into', 'insert_into_values']
        ],
        self::SELECT => [
            'name' => 'SELECT',
            'require' => ['select', 'from'],
            'uses' => ['select', 'from', 'where']
        ],
        self::UPDATE => [
            'name' => 'UPDATE',
            'require' => ['update', 'update_set', 'where']
        ],
        self::DELETE => [
            'name' => 'DELETE',
            'require' => ['delete', 'from', 'where']
        ],
    ];

    private $needs = [
        'insert_into' => 'primary_type',
        'insert_into_values' => [self::INSERT => ['insert_into']],
        'update' => 'primary_type',
        'update_set' => [self::UPDATE => ['update']],
        'select' => 'primary_type',
        'delete' => 'primary_type',
        'from' => [self::SELECT => ['select'], self::DELETE => ['delete']],
        'where' => [
            self::SELECT => ['select', 'from'],
            self::UPDATE => ['update', 'update_set'],
            self::DELETE => ['delete', 'from']
        ]
    ];

    private $statement;
    private $values;

    private $queryType;

    private $build;

    /**
     * Returns a new Query instance
     * Can be used for directly define the query :
     * ```
     * newQuery("SELECT * FROM example WHERE id = :id",[':id' => 2])
     * ```
     *
     * @param string|null $statement
     * @param array|null $values
     * @return $this
     */
    public static function create($statement = null, array $values = null)
    {
        if (isset($statement)) {
            return self::create()->setQuery($statement, $values);
        }

        return new self;
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
        $this->queryType = self::CUSTOM;
        $this->statement = $statement;

        if (isset($values)) {
            $this->values = $values;
        }

        return $this;
    }

    /**
     * @param string|Model $model
     * @param $statementId
     * @param $statementName
     * @param string $statement update or insert_into
     * @throws IncorrectQueryException
     */

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
        $this->optimiserInsertIntoAndUpdate($model, self::INSERT, 'insert_into', 'INSERT INTO');

        return $this;
    }

    /**
     * Used by Insert Into and Update to complete the statement
     *
     * @param Model|string $model
     * @param int $type self::INSERT or self::UPDATE
     * @param string $build 'insert_into' or 'update'
     * @param string $statement 'INSERT INTO' or 'UPDATE'
     * @return bool
     * @throws IncorrectQueryException
     */
    private function optimiserInsertIntoAndUpdate($model, $type, $build, $statement)
    {
        $this->checkIfCanBeAdded($build, $statement);

        $this->queryType = $type;
        $this->build[$build] = $statement . ' ' . self::getModelName($model);

        return true;
    }

    /**
     * Check if a statement can be used in the query
     *
     * @param $name
     * @param $statement
     * @return bool
     * @throws IncorrectQueryException
     */
    private function checkIfCanBeAdded($name, $statement)
    {
        $errorMsg = 'Cannot add ' . $statement;

        // If already initialized
        if (isset($this->build[$name])) {
            throw new IncorrectQueryException($errorMsg);
        }

        if ($this->needs[$name] === 'primary_type') {
            if (isset($this->queryType)) {
                throw new IncorrectQueryException($errorMsg);
            }
        } elseif (isset($this->needs[$name][$this->queryType])) {
            foreach ($this->needs[$name][$this->queryType] as $need) {
                if (!isset($this->build[$need])) {
                    throw new IncorrectQueryException($errorMsg);
                }
            }
        } else {
            throw new IncorrectQueryException($errorMsg);
        }

        return true;
    }

    /**
     * @param Model|string $model
     * @return string
     * @throws IncorrectQueryException
     */
    private static function getModelName($model)
    {
        if (is_string($model)) {
            return $model;
        } elseif (is_a($model, Model::class)) {
            return $model->getTable();
        } else {
            throw new IncorrectQueryException('Unsupported Model');
        }
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
        $this->checkIfCanBeAdded('delete', 'DELETE');

        $this->queryType = self::DELETE;
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
        $this->optimiserInsertIntoAndUpdate($model, self::UPDATE, 'update', 'UPDATE');

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
        $this->checkIfCanBeAdded('update_set', 'SET');

        $this->build['update_set'] = self::dataToString($data, 'UPDATE', $this->values);

        return $this;
    }

    /**
     * Returns a string equivalent to the statement (SET or VALUES) and populates the $clean array with values
     *
     * @param array|Entity $data
     * @param string $statement 'UPDATE' or 'INSERT'
     * @param array $clean the array of parameters to bind with pdo
     * @return string
     * @throws IncorrectQueryException
     */
    private static function dataToString($data, $statement, &$clean)
    {

        if (is_array($data)) {
            $rows = $data;
        } elseif (is_a($data, Entity::class)) {
            $rows = get_object_vars($data);
        } else {
            throw new IncorrectQueryException('Not an array nor an Entity during ' . $statement);
        }

        $columns = '';
        $values = '';

        foreach ($rows as $key => $value) {
            if (isset($value)) {
                $columns .= $key;
                $var = ':' . $key;
                $clean[$var] = $value;

                if ($statement === 'UPDATE') {
                    $columns .= ' = ' . $var;
                } else {
                    $values .= $var . ', ';
                }

                $columns .= ', ';
            }
        }

        $columns = rtrim($columns, ', ');
        $values = rtrim($values, ', ');

        if ($statement === 'UPDATE') {
            return ' SET ' . $columns . ' ';
        } else {
            return ' (' . $columns . ') VALUES (' . $values . ')';
        }

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
        if (!isset($this->queryType)) {
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
        if (!isset($this->queryType)) {
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
        $this->checkIfCanBeAdded('where', 'WHERE');

        if (isset($params[self::CONDITIONS]) && is_string($params[self::CONDITIONS])) {
            $this->build['where'] = 'WHERE ' . $params[self::CONDITIONS] . ' ';

            if (isset($params[self::VALUES])) {
                if (self::UPDATE === $this->queryType) {
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
        $this->checkIfCanBeAdded('from', 'FROM');

        $this->build['from'] = 'FROM ';

        if (is_array($model)) {
            foreach ($model as $item) {
                $this->build['from'] .= self::getModelName($item) . ', ';
            }

            $this->build['from'] = rtrim($this->build['from'], ', ') . ' ';
        } else {
            $this->build['from'] .= self::getModelName($model) . ' ';
        }

        return $this;
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
        $this->checkIfCanBeAdded('select', 'SELECT');

        $this->queryType = self::SELECT;
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
        $this->checkIfCanBeAdded('insert_into_values', '(...)VALUES(...)');

        $this->build['insert_into_values'] = self::dataToString($data, 'INSERT', $this->values);

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
        if (!isset($this->queryType)) {
            throw new IncorrectQueryException('Cannot concat inexisting statement');
        }

        if ($this->queryType === self::CUSTOM) {
            return true;
        }

        if (array_diff($this->requirements[$this->queryType]['require'], array_keys($this->build))) {
            throw new IncorrectQueryException('Incomplete ' . $this->requirements[$this->queryType]['name']
                . ' statement');
        }

        $statement = '';

        $concat = isset($this->requirements[$this->queryType]['uses']) ? 'uses' : 'require';

        foreach ($this->requirements[$this->queryType][$concat] as $use) {
            $statement .= isset($this->build[$use]) ? $this->build[$use] : null;
        }

        $statement .= isset($this->build['more']) ? $this->build['more'] : null;

        $this->statement = $statement;

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
