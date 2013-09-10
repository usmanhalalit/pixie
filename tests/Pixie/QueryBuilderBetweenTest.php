<?php namespace Pixie;

use Mockery as m;

class QueryBuilderBetweenTest extends TestCase
{
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->builder = new QueryBuilder\QueryBuilderHandler($this->mockConnection);
    }

    public function testSimpleBetween()
    {
        $query = $this->builder->table('my_table')->whereBetween('my_table.id', array(1, 2))->getQuery()->getRawSql();

        $this->assertEquals('SELECT * FROM `cb_my_table` WHERE `cb_my_table`.`id` BETWEEN (1, 2)', $query);
    }

    public function testNotBetween()
    {
        $query = $this->builder->table('my_table')->whereNotBetween('my_table.id', array(1, 2))->getQuery()->getRawSql();

        $this->assertEquals('SELECT * FROM `cb_my_table` WHERE `cb_my_table`.`id` NOT BETWEEN (1, 2)', $query);
    }

    public function testNormalOr()
    {
        $query = $this->builder->table('test');

        $query->where(function($qb)
        {
           return $qb->where('something', '=', 'somethingElse')->orWhere('something', '=', 'anotherThing');
        });

        $sql = $query->getQuery()->getSql();

        $this->assertEquals('SELECT * FROM `cb_test` WHERE (`something` = ? OR `something` = ?)', $sql);
    }

    public function testBetweenOrNotBetween() // get it?
    {
        $query = $this->builder->table('my_table')->where(function ($qb)
        {
            return $qb->whereBetween('my_table.id', array(1, 2))->orWhereNotBetween('id', array(2, 3, 4));
        })->getQuery()->getRawSql();

        $this->assertEquals('SELECT * FROM `cb_my_table` WHERE (`cb_my_table`.`id` BETWEEN (1, 2) OR `id` NOT BETWEEN (2, 3, 4))', $query);
    }
}