<?php namespace Pixie;

use Mockery as m;

class QueryBuilderFunctionUseTest extends TestCase
{
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->builder = new QueryBuilder\QueryBuilderHandler($this->mockConnection);
    }

    public function testSelectQueryUsingMySQLFunction()
    {
        $query = $this->builder->table('my_table')->select(array('id', 'FROM_UNIXTIMSTAMP(`columnName`)'));

        $this->assertEquals('SELECT `id`, FROM_UNIXTIMSTAMP(`columnName`) FROM `cb_my_table`', $query->getQuery()->getRawSql());
    }

    public function testSelectQueryNestedMySQLFunction()
    {
        $query = $this->builder->table('my_table')->select(array('id', 'FROM_UNIXTIMSTAMP(CONCAT(123456, 789))'));

        $this->assertEquals('SELECT `id`, FROM_UNIXTIMSTAMP(CONCAT(123456, 789)) FROM `cb_my_table`', $query->getQuery()->getRawSql());
    }

    public function testWhereQueryAndSelectFunctionUse()
    {
        $query = $this->builder->table('my_table')->select(array('id', 'FROM_UNIXTIMSTAMP(CONCAT(123456, 789))'))->where('UNIX_TIMESTAMP(occurred)', '=', 'fakeValue');

        $this->assertEquals('SELECT `id`, FROM_UNIXTIMSTAMP(CONCAT(123456, 789)) FROM `cb_my_table` WHERE UNIX_TIMESTAMP(occurred) = \'fakeValue\'', $query->getQuery()->getRawSql());
    }
}