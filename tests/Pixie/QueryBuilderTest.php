<?php namespace Pixie;

use Mockery as m;
use Pixie\QueryBuilder\QueryBuilderHandler;

class QueryBuilder extends TestCase
{
    /**
     * @var QueryBuilderHandler
     */
    protected $builder;

    public function setUp()
    {
        parent::setUp();
        $this->builder = new QueryBuilderHandler($this->mockConnection);
    }

    public function testRawQuery()
    {
        $query = 'select * from cb_my_table where id = ? and name = ?';
        $bindings = array(5, 'usman');
        $queryArr = $this->builder->query($query, $bindings)->get();
        $this->assertEquals($queryArr, array($query, $bindings));
    }

    /**
     * @expectedException \Pixie\Exception
     * @expectedExceptionCode 3
     */
    public function testTableNotSpecifiedException(){
        $this->builder->where('a', 'b')->get();
    }
}