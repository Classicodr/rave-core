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

namespace rave\tests\database\orm;

use PHPUnit_Framework_TestCase;
use rave\core\database\orm\Query;
use rave\core\exception\IncorrectQueryException;
use rave\tests\app\entity\ArticlesEntity;
use rave\tests\app\model\ArticlesModel;
use rave\tests\app\model\UsersModel;

/**
 * Class QueryTest
 * Unit test of Query class
 *
 * @since 0.4.0-alpha
 * @package rave\tests\database\orm
 */
class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot concat inexisting statement');
        $query = Query::newQuery();
        $query->getParams();
    }

    /**
     * Test setQuery
     */
    public function testSetQuery()
    {
        /*
         * Normal assignement
         */
        $query = Query::newQuery();
        $query->setQuery('SELECT * FROM articles WHERE id = :id', [':id' => 'id']);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles WHERE id = :id', 'values' => [':id' => 'id']],
            $query->getParams()
        );

        /*
         * Empty value
         */
        $query = Query::newQuery();
        $query->setQuery('SELECT * FROM articles');

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles'],
            $query->getParams()
        );
    }

    /**
     * Test Insert
     *
     * @throws IncorrectQueryException
     */
    public function testInsert()
    {
        /*
         * String
         */
        $query_string = Query::newQuery();
        $query_string->insertInto('articles')
                     ->values(['title' => 'title', 'name' => 'My name']);

        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, name) VALUES (:title, :name)',
                'values' => [':title' => 'title', ':name' => 'My name']
            ],
            $query_string->getParams());
        /*
         * Model class
         */
        $query_model = Query::newQuery();
        $article_model = new ArticlesModel();
        $query_model->insertInto($article_model)
                    ->values(['title' => 'title', 'name' => 'My name']);
        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, name) VALUES (:title, :name)',
                'values' => [':title' => 'title', ':name' => 'My name']
            ],
            $query_model->getParams());

        $this->assertEquals($query_model, $query_string);
    }

    public function testInsertDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add INSERT INTO statement');
        $query = Query::newQuery();
        $query->insertInto('articles')->insertInto('articles');
    }

    public function testInsertIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete INSERT statement');
        $query = Query::newQuery();
        $query->insertInto('articles');
        $query->getParams();
    }

    public function testInsertIncorrect()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incorrect class in INSERT');
        $query = Query::newQuery();
        $query->insertInto($query);
    }

    /**
     * Test Values
     *
     * @throws IncorrectQueryException
     */
    public function testValues()
    {
        /*
         * Array test
         */
        $query = Query::newQuery();
        $query->insertInto('articles')
              ->values(['title' => 'Hello World', 'groupe' => 'Jackson Five']);

        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, groupe) VALUES (:title, :groupe)',
                'values' => [':title' => 'Hello World', ':groupe' => 'Jackson Five']
            ],
            $query->getParams()
        );

        /*
         * Entity test
         */
        $query = Query::newQuery();
        $articles_entity = new ArticlesEntity();
        $articles_entity->set(['title' => 'Hello world', 'content' => 'really long']);

        $query->insertInto('articles')
              ->values($articles_entity);
        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, content) VALUES (:title, :content)',
                'values' => [':title' => 'Hello world', ':content' => 'really long']
            ],
            $query->getParams());
    }

    public function testValuesDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add (...)VALUES(...) statement');

        $query = Query::newQuery()
                      ->insertInto('articles')
                      ->values(['dummy' => 'data'])
                      ->values(['dummy2' => 'data2']);
    }

    public function testValuesIncorrect()
    {
        $this->setExpectedException(IncorrectQueryException::class,
            'Not an array, nor an Entity in INSERT declaration');
        $query = Query::newQuery();
        $query->insertInto('articles')
              ->values('hello world');

    }

    public function testValuesMissingInsertInto()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add (...)VALUES(...) statement');

        $query = Query::newQuery();
        $query->values(['dummy' => 'data']);
    }

    /**
     * Test Delete
     *
     * @throws IncorrectQueryException
     */
    public function testDelete()
    {
        $query = Query::newQuery();

        $query->delete()
              ->from('articles')
              ->where(['id', '=', 1]);

        $this->assertEquals([
            'statement' => 'DELETE FROM articles WHERE id = :id ',
            'values' => [':id' => 1]
        ],
            $query->getParams());
    }

    public function testDeleteDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add DELETE statement');
        $query = Query::newQuery();
        $query->delete()->delete();
    }

    public function testDeleteIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete DELETE statement');
        $query = Query::newQuery();
        $query->delete();
        $query->getParams();
    }

    public function testDeleteMissingFrom()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete DELETE statement');
        $query = Query::newQuery();
        $query->delete()->where(['articles', '=', 2]);
        $query->getParams();
    }

    public function testDeleteMissingWhere()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete DELETE statement');
        $query = Query::newQuery();
        $query->delete()->from('articles');
        $query->getParams();
    }

    /**
     * Test Select
     *
     * @throws IncorrectQueryException
     */
    public function testSelect()
    {
        /*
         * test default
         */
        $query = Query::newQuery();
        $query->select()
              ->from('articles');

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles '],
            $query->getParams());

        /*
         * Test array
         */
        $query = Query::newQuery();
        $query->select(['id', 'title'])
              ->from('articles');

        $this->assertEquals(
            $query->getParams(),
            ['statement' => 'SELECT id, title FROM articles ']
        );

        /*
         * Test string
         */
        $query = Query::newQuery();
        $query->select('id, title')
              ->from('articles');

        $this->assertEquals(
            $query->getParams(),
            ['statement' => 'SELECT id, title FROM articles ']
        );
    }

    public function testSelectDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add a select statement');
        $query = Query::newQuery();
        $query->select()->select();
        $query->getParams();
    }

    public function testSelectIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete SELECT statement');
        $query = Query::newQuery();
        $query->select();
        $query->getParams();
    }

    public function testSelectIncorrect()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incorrect SELECT');
        $query = Query::newQuery();
        $query->select($query);
        $query->getParams();
    }

    /**
     * Test From
     *
     * @throws IncorrectQueryException
     */
    public function testFrom()
    {
        /*
         * Test string
         */
        $query = Query::newQuery();
        $query->select()
              ->from('articles');

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles '],
            $query->getParams());

        /*
         * Test Model class
         */
        $query = Query::newQuery();
        $articles_model = new ArticlesModel();
        $query->select()
              ->from($articles_model);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles '],
            $query->getParams()
        );

        /*
         * Test array full string
         */
        $query = Query::newQuery();
        $query->select()
              ->from(['articles', 'blog']);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles, blog '],
            $query->getParams()
        );

        /*
         * Test array full ClassModel
         */
        $query = Query::newQuery();
        $articles_model = new ArticlesModel();
        $users_model = new UsersModel();
        $query->select()
              ->from([$articles_model, $users_model]);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles, users '],
            $query->getParams()
        );

        /*
         * Test array string and Model classes
         */
        $query_model = Query::newQuery();
        $articles_model = new ArticlesModel();
        $query_model->select()
                    ->from([$articles_model, 'users']);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles, users '],
            $query_model->getParams()
        );

        $this->assertEquals($query, $query_model);
    }

    public function testFromDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add FROM statement');
        $query = Query::newQuery();
        $query->select()
              ->from('articles')
              ->from('users')
              ->getParams();
    }

    public function testFromIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add FROM statement');
        $query = Query::newQuery();
        $query->from('articles')
              ->getParams();
    }

    public function testFromIncorrectArrayAttribute()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Unsupported Model value in FROM : 5');
        $query = Query::newQuery();
        $query->select()
              ->from([5]);
    }

    public function testFromIncorrectAttribute()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Unsupported Model value in FROM : 5');
        $query = Query::newQuery();
        $query->select()
              ->from(5);
    }

    /**
     * Test Update
     *
     * @throws IncorrectQueryException
     */
    public function testUpdate()
    {
        /*
         * string
         */
        $query = Query::newQuery();
        $query->update('articles')
              ->set(['title' => 'Hello world'])
              ->where(['id', '=', 2]);

        $this->assertEquals([
            'statement' => 'UPDATE articles SET title = :title WHERE id = :id ',
            'values' => [':title' => 'Hello world', ':id' => 2]
        ], $query->getParams());

        /*
         * Model
         */
        $query_model = Query::newQuery();
        $articles_model = new ArticlesModel();
        $query_model->update($articles_model)
                    ->set(['title' => 'Hello world'])
                    ->where(['id', '=', 2]);

        $this->assertEquals([
            'statement' => 'UPDATE articles SET title = :title WHERE id = :id ',
            'values' => [':title' => 'Hello world', ':id' => 2]
        ], $query_model->getParams());
        $this->assertEquals($query, $query_model);
    }

    public function testUpdateDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add UPDATE statement');
        Query::newQuery()
             ->update('articles')
             ->update('users')
             ->getParams();
    }

    public function testUpdateIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete UPDATE statement');
        Query::newQuery()
             ->update('articles')
             ->getParams();
    }

    public function testUpdateIncorrect()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incorrect parameter on UPDATE');
        Query::newQuery()
             ->update(2);
    }

    public function testUpdateMissingSet()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete UPDATE statement');
        Query::newQuery()
             ->update('articles')
             ->where(['id', '=', 2])
             ->getParams();
    }

    public function testUpdateMissingWhere()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete UPDATE statement');
        Query::newQuery()
             ->update('articles')
             ->set(['title' => 'Hello world'])
             ->getParams();
    }

    /**
     * Test Set
     *
     * @throws IncorrectQueryException
     */
    public function testSet()
    {
        $default_query = Query::newQuery()
                              ->setQuery('UPDATE articles SET title = :title, content = :content WHERE title = :title0 ',
                                  [
                                      ':title' => 'hello world',
                                      ':content' => 'really long',
                                      ':title0' => 'Hello Jackson'
                                  ]);

        /*
         * Test Set array
         */
        $query_array = Query::newQuery();
        $query_array->update('articles')
                    ->set(['title' => 'hello world', 'content' => 'really long'])
                    ->where(['title', '=', 'Hello Jackson']);

        $this->assertEquals($default_query->getParams(), $query_array->getParams());

        /*
         * Test set Entity
         */
        $articles_entity = new ArticlesEntity();
        $articles_entity->title = 'hello world';
        $articles_entity->content = 'really long';
        $query_entity = Query::newQuery()
                             ->update('articles')
                             ->set($articles_entity)
                             ->where(['title', '=', 'Hello Jackson']);

        $this->assertEquals($default_query->getParams(), $query_entity->getParams());

        $this->assertEquals($query_array, $query_entity);

    }

    public function testSetDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add SET statement');
        Query::newQuery()->update('articles')
             ->set(['title' => 'test'])
             ->set(['content' => 'testcontent']);
    }

    public function testSetIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add SET statement');
        Query::newQuery()->set(['title' => 'hello world'])->getParams();
    }

    public function testSetIncorrect()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Not an array nor an Entity during statement SET');
        Query::newQuery()->update('articles')
             ->set('test');
    }

    public function testSetMissingUpdate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add SET statement');
        Query::newQuery()->set(['title' => 'hello world'])
             ->where(['id', '=', 2])->getParams();
    }

    public function testSetMissingWhere()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Incomplete UPDATE statement');
        Query::newQuery()->update('articles')->set(['title' => 'hello world'])->getParams();
    }

    /**
     * Test where
     *
     * @throws IncorrectQueryException
     */
    public function testWhere()
    {

        $query_default = Query::newQuery('SELECT * FROM articles WHERE id = :id ', [':id' => 2]);
        /*
         * Test array conditions,values
         */
        $query_old = Query::newQuery()
                          ->select()
                          ->from('articles')
                          ->where(['conditions' => 'id = :id', 'values' => [':id' => 2]]);

        $this->assertEquals($query_default->getParams(), $query_old->getParams()
        );

        /*
         * Test full array,
         */
        $articles_model = new ArticlesModel();

        $query_better = Query::newQuery()
                             ->select()
                             ->from($articles_model)
                             ->where(['id', '=', 2]);
        $this->assertEquals($query_default->getParams(), $query_better->getParams());

        $this->assertEquals($query_old, $query_better);

        /*
         * test Select
         */
        $query_select = Query::newQuery()
                             ->select()
                             ->from('articles')
                             ->where([
                                 'AND' => [
                                     ['id', '=', 2],
                                     ['id', '=', 4]
                                 ]
                             ]);

        $this->assertEquals([
            'statement' => 'SELECT * FROM articles WHERE (id = :id AND id = :id0) ',
            'values' => [':id' => 2, ':id0' => 4]
        ], $query_select->getParams());

        /*
         * test Delete
         */
        $query = Query::newQuery();
        $query->delete()->from('articles')->where([
            'AND' => [
                ['id', '=', 2],
                ['title', '=', 'salut les geeks'],
                'OR' => [
                    ['id', '=', 3],
                    ['id', '=', 4],
                    ['id', '=', 5],
                ]
            ]
        ]);
        $this->assertEquals(
            [
                'statement' => 'DELETE FROM articles WHERE (id = :id AND title = :title AND (id = :id0 OR id = :id1 OR id = :id2)) ',
                'values' => [
                    ':id' => 2,
                    ':title' => 'salut les geeks',
                    ':id0' => 3,
                    ':id1' => 4,
                    ':id2' => 5
                ]
            ],
            $query->getParams());

        /*
         * test Update
         */
        $query = Query::newQuery();
        $query->update('articles')
              ->set(['title' => 'Harry Potter', 'author' => 'J.K Rowling'])
              ->where(
                  [
                      'conditions' => '(id = :id AND title = :title0 AND (id = :id1 OR id = :id2 
OR id = :id3)) ',
                      'values' => [
                          ':id' => 2,
                          ':title0' => 'salut les geeks',
                          ':id1' => 3,
                          ':id2' => 4,
                          ':id3' => 5
                      ]
                  ]);

        $this->assertEquals(
            [
                'statement' =>
                    'UPDATE articles SET title = :title, author = :author WHERE (id = :id AND title = :title0 AND (id = :id1 OR id = :id2 
OR id = :id3))  ',
                'values' =>
                    [
                        ':title' => 'Harry Potter',
                        ':author' => 'J.K Rowling',
                        ':id' => 2,
                        ':title0' => 'salut les geeks',
                        ':id1' => 3,
                        ':id2' => 4,
                        ':id3' => 5
                    ]
            ],
            $query->getParams()
        );
    }

    public function testWhereDuplicate()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add a WHERE statement');
        $query = Query::newQuery()->select()->from('articles')->where(['id', '=', 2])->where(['id', '=', 3]);
    }

    public function testWhereIncomplete()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Cannot add a WHERE statement');
        $query = Query::newQuery()->where(['id', '=', 2]);
    }

    public function testWhereIncorrectArray()
    {
        $this->setExpectedException(IncorrectQueryException::class, 'Bad where construction');
        Query::newQuery()
             ->select()
             ->from('articles')
             ->where(['test', '=', 2, 4]);
    }

//TODO : update, delete, add queries
}
