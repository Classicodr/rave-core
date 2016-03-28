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
use rave\tests\app\entity\ArticlesEntity;
use rave\tests\app\model\ArticlesModel;

/**
 * Class QueryTest
 * Unit test of Query class
 *
 * @since 0.4.0-alpha
 * @package rave\tests\database\orm
 */
class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testNewQuery()
    {
        $query_new = Query::create();
        $query_classic = new Query();

        $this->assertEquals($query_classic, $query_new);

        $query_classic = new Query();
        $query_classic->setQuery("SELECT * FROM articles WHERE id = :id", [':id' => 2]);
        $query_new = Query::create("SELECT * FROM articles WHERE id = :id", [':id' => 2]);

        $this->assertEquals($query_classic, $query_new);

    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot concat inexisting statement
     */
    public function testConstructInvalid()
    {
        Query::create()->getParams();
    }

    /**
     * Test setQuery
     */
    public function testSetQuery()
    {
        /*
         * Normal assignement
         */
        $query = Query::create();
        $query->setQuery('SELECT * FROM articles WHERE id = :id', [':id' => 'id']);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles WHERE id = :id', 'values' => [':id' => 'id']],
            $query->getParams()
        );

        /*
         * Empty value
         */
        $query = Query::create();
        $query->setQuery('SELECT * FROM articles');

        $this->assertEquals(
            ['statement' => 'SELECT * FROM articles'],
            $query->getParams()
        );
    }

    /**
     * COMPLETE Test Insert
     *
     * @throws \rave\core\exception\IncorrectQueryException
     */
    public function testInsert()
    {
        /*
         * insertInto(string)->values(array)
         */
        $query_string_array = Query::create();
        $query_string_array->insertInto('articles')
            ->values(['title' => 'Hello world', 'content' => 'really long']);

        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, content) VALUES (:title, :content)',
                'values' => [':title' => 'Hello world', ':content' => 'really long']
            ],
            $query_string_array->getParams());

        /*
         * insertInto(string)->values(Entity)
         */
        $query_string_entity = Query::create();
        $articles_entity = new ArticlesEntity();
        $articles_entity->set(['title' => 'Hello world', 'content' => 'really long']);

        $query_string_entity->insertInto('articles')
            ->values($articles_entity)
            ->getParams();

        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, content) VALUES (:title, :content)',
                'values' => [':title' => 'Hello world', ':content' => 'really long']
            ],
            $query_string_entity->getParams());

        /*
         * insertInto(Model)->values(array)
         */
        $query_model_array = Query::create();
        $article_model = new ArticlesModel();
        $query_model_array->insertInto($article_model)
            ->values(['title' => 'Hello World', 'content' => 'really long']);

        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, content) VALUES (:title, :content)',
                'values' => [':title' => 'Hello World', ':content' => 'really long']
            ],
            $query_model_array->getParams());

        /*
         * insertInto(Model)->values(Entity)
         */
        $query_model_entity = Query::create();
        $article_model = new ArticlesModel();
        $articles_entity = new ArticlesEntity();
        $articles_entity->set(['title' => 'Hello World', 'content' => 'really long']);
        $query_model_entity->insertInto($article_model)
            ->values($articles_entity);

        $this->assertEquals(
            [
                'statement' => 'INSERT INTO articles (title, content) VALUES (:title, :content)',
                'values' => [':title' => 'Hello World', ':content' => 'really long']
            ],
            $query_model_entity->getParams());
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add INSERT INTO statement
     */
    public function testInsertDuplicate()
    {
        Query::create()
            ->insertInto('articles')
            ->insertInto('articles');
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete INSERT statement
     */
    public function testInsertIncompleteMissingValues()
    {
        Query::create()
            ->insertInto('articles')
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incorrect class in INSERT
     */
    public function testInsertIncorrect()
    {
        Query::create()
            ->insertInto(Query::create());
    }

    /*
     * Test Values
     */
    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add (...)VALUES(...) statement
     */
    public function testValuesDuplicate()
    {
        Query::create()
            ->insertInto('articles')
            ->values(['dummy' => 'data'])
            ->values(['dummy2' => 'data2']);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add (...)VALUES(...) statement
     */
    public function testValuesIncompleteMissingInsertInto()
    {
        Query::create()
            ->values(['dummy' => 'data']);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Not an array, nor an Entity in INSERT declaration
     */
    public function testValuesIncorrect()
    {
        Query::create()->insertInto('articles')
            ->values('hello world');

    }

    /**
     * COMPLETE Test Delete
     * from() and where() complete test is in select and UPDATE
     *
     * can't be incorrect ^^
     *
     * @throws \rave\core\exception\IncorrectQueryException
     */
    public function testDelete()
    {
        /*
         * delete() from() and where() are independent
         *
         * from() and where() are tested in select() and update()
        */
        $query = Query::create()
            ->delete()
            ->from('articles')
            ->where(['id', '=', 1]);

        $this->assertEquals([
            'statement' => 'DELETE FROM articles WHERE id = :id ',
            'values' => [':id' => 1]
        ],
            $query->getParams());
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add DELETE statement
     */
    public function testDeleteDuplicate()
    {
        $query = Query::create();
        $query->delete()->delete();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete DELETE statement
     */
    public function testDeleteIncomplete()
    {
        Query::create()
            ->delete()
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete DELETE statement
     */
    public function testDeleteMissingFrom()
    {
        Query::create()
            ->delete()
            ->where(['articles', '=', 2])
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete DELETE statement
     */
    public function testDeleteMissingWhere()
    {
        Query::create()
            ->delete()
            ->from('articles')
            ->getParams();
    }

    /**
     * COMPLETE Test Select
     *
     * from() and where() are tested here
     *
     * @throws \rave\core\exception\IncorrectQueryException
     */
    public function testSelect()
    {
        $articles_model = new ArticlesModel();

        /* select(), from() and where() are independant */
        /*
         * select()->from(string)
         */
        $query = Query::create();
        $query->select()
            ->from(['users', $articles_model]);

        $this->assertEquals(
            ['statement' => 'SELECT * FROM users, articles '],
            $query->getParams());

        /*
         * select(array)->from(Model)->where(condition,values)
         */
        $query_array_string = Query::create()
            ->select(['id', 'title'])
            ->from($articles_model)
            ->where([
                'conditions' => '(id = :id AND (title = :title OR title = :title0))',
                'values' => [':id' => 2, ':title' => 'helloworld', ':title0' => 'hello world']
            ]);

        $this->assertEquals(
            [
                'statement' => 'SELECT id, title FROM articles WHERE (id = :id AND (title = :title OR title = :title0)) ',
                'values' => [':id' => 2, ':title' => 'helloworld', ':title0' => 'hello world']
            ],
            $query_array_string->getParams()
        );

        /*
         * select(string)->from(string)->where(newWhere)
         */
        $query_string_string = Query::create()
            ->select('id, title')
            ->from('articles')
            ->where(['AND' => [['id', '=', 2], 'OR' => [['title', '=', 'helloworld'], ['title', '=', 'hello world']]]]);
        $this->assertEquals(
            [
                'statement' => 'SELECT id, title FROM articles WHERE (id = :id AND (title = :title OR title = :title0)) ',
                'values' => [':id' => 2, ':title' => 'helloworld', ':title0' => 'hello world']
            ],
            $query_string_string->getParams()
        );

    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add a select statement
     */
    public function testSelectDuplicate()
    {
        Query::create()
            ->select()
            ->select()
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete SELECT statement
     */
    public function testSelectIncompleteMissingFrom()
    {
        Query::create()
            ->select()
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incorrect SELECT
     */
    public function testSelectIncorrect()
    {
        Query::create()
            ->select(Query::create())
            ->getParams();
    }

    /*
     * Test From
     */
    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add FROM statement
     */
    public function testFromDuplicate()
    {
        Query::create()
            ->select()
            ->from('articles')
            ->from('users')
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add FROM statement
     */
    public function testFromIncomplete()
    {
        Query::create()
            ->from('articles')
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Unsupported Model value in FROM : 5
     */
    public function testFromIncorrectArrayAttribute()
    {
        Query::create()
            ->select()
            ->from([5]);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Unsupported Model value in FROM : 5
     */
    public function testFromIncorrectAttribute()
    {
        Query::create()
            ->select()
            ->from(5);
    }

    /**
     * Complete Test Update
     *
     * @throws \rave\core\exception\IncorrectQueryException
     */
    public function testUpdate()
    {
        /*
         * update(string)->set(array)->where(new array)
         */
        $query = Query::create();
        $query->update('articles')
            ->set(['title' => 'Hello world', 'content' => 'thisisacontent'])
            ->where(['title', '=', 'helloworld']);

        $this->assertEquals([
            'statement' => 'UPDATE articles SET title = :title, content = :content WHERE title = :title0 ',
            'values' => [':title' => 'Hello world', ':content' => 'thisisacontent', ':title0' => 'helloworld']
        ], $query->getParams());

        /*
         * update(Model)->set(entity)->where(old array)
         */
        $query_model = Query::create();
        $articles_model = new ArticlesModel();
        $articles_entity = new ArticlesEntity();
        $articles_entity->set(['title' => 'Hello world', 'content' => 'thisisacontent']);

        $query_model->update($articles_model)
            ->set($articles_entity)
            ->where([
                'conditions' => 'title = :title0',
                'values' => [':title0' => 'helloworld']
            ]);
        $this->assertEquals([
            'statement' => 'UPDATE articles SET title = :title, content = :content WHERE title = :title0 ',
            'values' => [':title' => 'Hello world', ':content' => 'thisisacontent', ':title0' => 'helloworld']
        ], $query_model->getParams());
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add UPDATE statement
     */

    public function testUpdateDuplicate()
    {
        Query::create()
            ->update('articles')
            ->update('users')
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete UPDATE statement
     */
    public function testUpdateIncomplete()
    {
        Query::create()
            ->update('articles')
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incorrect parameter on UPDATE
     */
    public function testUpdateIncorrect()
    {
        Query::create()
            ->update(2);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete UPDATE statement
     */

    public function testUpdateMissingSet()
    {
        Query::create()
            ->update('articles')
            ->where(['id', '=', 2])
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete UPDATE statement
     */

    public function testUpdateMissingWhere()
    {
        Query::create()
            ->update('articles')
            ->set(['title' => 'Hello world'])
            ->getParams();
    }

    /*
     * Test Set
     */

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add SET statement
     */

    public function testSetDuplicate()
    {
        Query::create()->update('articles')
            ->set(['title' => 'test'])
            ->set(['content' => 'testcontent']);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add SET statement
     */
    public function testSetIncompleteMissingUpdate()
    {
        Query::create()
            ->set(['title' => 'hello world'])
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Not an array nor an Entity during statement SET
     */
    public function testSetIncorrect()
    {
        Query::create()
            ->update('articles')
            ->set('test');
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add SET statement
     */
    public function testSetMissingUpdate()
    {
        Query::create()
            ->set(['title' => 'hello world'])
            ->where(['id', '=', 2])
            ->getParams();
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Incomplete UPDATE statement
     */
    public function testSetMissingWhere()
    {
        Query::create()
            ->update('articles')
            ->set(['title' => 'hello world'])
            ->getParams();
    }

    /*
     * Test where
     */

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add a WHERE statement
     */
    public function testWhereDuplicate()
    {
        Query::create()
            ->select()
            ->from('articles')
            ->where(['id', '=', 2])
            ->where(['id', '=', 3]);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Cannot add a WHERE statement
     */
    public function testWhereIncomplete()
    {
        Query::create()
            ->where(['id', '=', 2]);
    }

    /**
     * @throws \rave\core\exception\IncorrectQueryException
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage Bad where construction
     */
    public function testWhereIncorrectArray()
    {
        Query::create()
            ->select()
            ->from('articles')
            ->where(['test', '=', 2, 4]);
    }

    public function testAppendSQL()
    {
        $query = Query::create()
            ->select()
            ->from('articles')
            ->appendSQL('GROUP BY id');
        $this->assertEquals(['statement' => 'SELECT * FROM articles GROUP BY id'], $query->getParams());
    }

    public function testGetters()
    {
        $query = Query::create()->select()->from('articles')->where(['id', '=', 2]);
        $this->assertEquals(['statement' => $query->getStatement(), 'values' => $query->getValues()],
            $query->getParams());
    }

    /**
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage The query is incorrect
     */
    public function testFindInvalid()
    {
        Query::create()->find();
    }

    /**
     * @expectedException \rave\core\exception\IncorrectQueryException
     * @expectedExceptionMessage The query is incorrect
     * @throws \rave\core\exception\IncorrectQueryException
     */
    public function testFirstInvalid()
    {
        Query::create()->first();
    }

    /*
     * Testing queries and fetching Entities from database
     */

    /**
     * Creates the articles Database
     */
    public function testInitDB()
    {
        $query = new Query();
        $query->setQuery('CREATE TABLE IF NOT EXISTS test.articles(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT UNIQUE ,
    title VARCHAR(300) NOT NULL,
    content TEXT NOT NULL
);');
        $query->execute();
    }

    /**
     * @depends testInitDB
     */
    public function testInsertDB()
    {
        $query = new Query();

        $model = new ArticlesModel();
        $entity = new ArticlesEntity();
        $entity->set(['title' => 'Hello World', 'content' => 'thisisacontent']);
        $query->insertInto($model)->values($entity)->execute();
    }

    /**
     * @depends testInsertDB
     */
    public function testSelectDB()
    {
        $model = new ArticlesModel();
        $query = new Query();
        $entity_manual = new ArticlesEntity();
        $entity_manual->set(['title' => 'Hello World', 'content' => 'thisisacontent']);

        $query->select()->from($model);
        $entities = $query->find();

        $query = Query::create()->select()->from($model);
        $entity = $query->find('first');

        $entity_manual->id = $entity->id; // YEAH !! Databases

        $this->assertEquals($entity_manual, $entity);
        $this->assertEquals($entities[0], $entity);

    }

    /**
     * @depends testSelectDB
     */
    public function testUpdateDB()
    {
        $model = new ArticlesModel();

        $query = Query::create()->select()->from($model);
        $entity = $query->find('first');

        $entity->title = 'Hell o World';
        $entity->content = 'This is a content';

        Query::create()
            ->update($model)
            ->set($entity)
            ->where(['id', '=', $entity->id])
            ->execute();

        $query = Query::create()
            ->select()
            ->from($model);

        $entity_after_update = $query->first();

        $this->assertEquals($entity, $entity_after_update);
    }

    /**
     * @depends testUpdateDB
     */
    public function testDeleteDB()
    {
        $model = new ArticlesModel();
        $entity = Query::create()->select()->from($model)->find('first');

        Query::create()
            ->delete()
            ->from($model)
            ->where(['id', '=', $entity->id])
            ->execute();

        $this->assertEquals([], Query::create()->select()->from($model)->find());
    }

}
