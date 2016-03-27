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
namespace rave\tests\core\database\ORM;

use PHPUnit_Framework_TestCase;
use rave\core\database\ORM\Query;
use rave\core\exception\IncorrectQueryException;
use rave\tests\app\entity\ArticlesEntity;
use rave\tests\app\model\ArticlesModel;

class QueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedExceptionMessage  Cannot concat inexisting statement
     */
    public function testConstruct()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->getParams();
    }

    /**
     * @throws IncorrectQueryException
     * @expectedExceptionMessage  Incomplete SELECT statement
     */
    public function testInvalidSelect()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->select();
        $query->getParams();
    }

    /**
     * @throws IncorrectQueryException
     * @expectedExceptionMessage  Incorrect SELECT
     */
    public function testIncorrectSelect()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->select($query);
        $query->getParams();
    }

    /**
     * @throws IncorrectQueryException
     * @expectedExceptionMessage  Cannot add a select statement
     */
    public function testDuplicateSelect()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->select()->select();
        $query->getParams();
    }

    public function testSelect()
    {
        $query = new Query();

        $this->assertEquals(
            $query->select()
                ->from('articles')
                ->getParams(),
            ['statement' => 'SELECT * FROM articles ']);

        $query = new Query();
        $query->select(['id', 'title'])
            ->from('articles')
            ->where(['conditions' => 'id = :id', 'values' => [':id' => 2]]);

        $this->assertEquals(
            $query->getParams(),
            ['statement' => 'SELECT id, title FROM articles WHERE id = :id ', 'values' => [':id' => 2]]);

        $query = new Query();
        $articles_model = new ArticlesModel();

        $this->assertEquals(
            $query->select()
                ->from('articles')
                ->getParams(),
            ['statement' => 'SELECT * FROM articles ']
        );
    }

    /**
     * @throws IncorrectQueryException
     * @expectedExceptionMessage Cannot add FROM statement
     */
    public function testInvalidFrom()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->from('articles')
            ->getParams();
    }

    public function testFrom()
    {
        $query = new Query();

        $this->assertEquals(
            $query->select()
                ->from('articles')
                ->getParams(),
            ['statement' => 'SELECT * FROM articles ']);

        $query = new Query();
        $articles_model = new ArticlesModel();

        $this->assertEquals(
            $query->delete()
                ->from($articles_model)
                ->where(['conditions' => 'id = :id', 'values' => [':id' => 2]])
                ->getParams(),
            ['statement' => 'DELETE FROM articles WHERE id = :id ', 'values' => [':id' => 2]]
        );
    }

    /**
     * @throws IncorrectQueryException
     * @expectedExceptionMessage Empty WHERE statement
     */
    public function testEmptyWhere()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->select()->from('articles')->where();
    }

    /**
     * @throws IncorrectQueryException
     * @expectedExceptionMessage Cannot add a WHERE statement
     */
    public function testInvalidWhere()
    {
        $this->setExpectedException(IncorrectQueryException::class);
        $query = new Query();
        $query->where();
    }

    public function testWhere()
    {
        $query = new Query();
        $query->select()->from('articles')->where(['conditions' => 'id = :id', 'values' => [':id' => 2]]);
        $this->assertEquals($query->getParams(), ['statement' => 'SELECT * FROM articles WHERE id = :id ', 'values' => [':id' => 2]]);

        $query = new Query();
        $entity = new ArticlesEntity();
        $entity->title = 'Titre';
        $entity->id = 1;

        $query->update('articles')->set($entity)->where(['conditions' => ['id', '=', 2]]);
        $this->assertEquals($query->getParams(), ['statement' => 'UPDATE articles SET id = :id, title = :title, content = :content WHERE id = :id0 ',
            'values' => [':id' => 1,
                ':title' => 'Titre',
                ':content' => '',
                ':id0' => 2]
        ]);

        $query = new Query();
        $query->delete()->from('articles')->where(['conditions' => 'id = :id', 'values' => [':id' => 2]]);
        $this->assertEquals($query->getParams(), ['statement' => 'DELETE FROM articles WHERE id = :id ', 'values' => [':id' => 2]]);

        $query = new Query();

        $query->select()
            ->from('articles')
            ->where(['conditions' => [
                'AND' => [
                    ['id', '=', 2],
                    ['title', '=', 'salut les geeks'],
                    'OR' => [
                        ['id', '=', 3],
                        ['id', '=', 4],
                        ['id', '=', 5],
                    ]
                ]]
            ]);

        $this->assertEquals($query->getParams(),
            [
                'statement' =>
                    'SELECT * FROM articles WHERE (id = :id AND title = :title AND (id = :id0 OR id = :id1 OR id = :id2)) ',
                'values' => [':id' => 2,
                    ':title' => 'salut les geeks', ':id0' => 3, ':id1' => 4, ':id2' => 5]
            ]
        );
    }

//TODO : update, delete, add queries
}
