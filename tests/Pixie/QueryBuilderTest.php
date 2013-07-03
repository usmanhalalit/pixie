<?php namespace Pixie;

use Mockery as m;

class QueryBuilderTest extends TestCase
{
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->builder = new QueryBuilder\QueryBuilderHandler($this->mockConnection);
    }
    public function testSelectQuery()
    {
        $subQuery = $this->builder->table('person_details')->select('details')->where('person_id', '=', 3);


        $query = $this->builder->table('my_table')
            ->select('my_table.*')
            ->select(array($this->builder->raw('count(cb_my_table.id) as tot'), $this->builder->subQuery($subQuery, 'pop')))
            ->where('value', '=', 'Ifrah')
            ->orWhereIn('my_table.id', array(1, 2))
            ->groupBy(array('value', 'my_table.id', 'person_details.id'))
            ->orderBy('my_table.id', 'DESC')
            ->orderBy('value')
            ->having('tot', '<', 2)
            ->limit(1)
            ->offset(0)
            ->join(
                'person_details',
                'person_details.person_id',
                '=',
                'my_table.id'
            )//->join('cb_person_details', 'cb_person_details.person.id', '=', 'cb_my_table.id')
        ;

        $nestedQuery = $this->builder->table($this->builder->subQuery($query, 'bb'))->select('*');
        //$query = DB::select('*')->from('cb_my_table')->whereNotIn('id', array(1))->get();
        $this->assertEquals("SELECT * FROM (SELECT `cb_my_table`.*, count(cb_my_table.id) as tot, (SELECT `details` FROM `cb_person_details` WHERE `person_id` = 3) as pop FROM `cb_my_table` INNER JOIN `cb_person_details`  ON `cb_person_details`.`person_id` = `cb_my_table`.`id` WHERE `value` = 'Ifrah' AND `cb_my_table`.`id` IN (1, 2) GROUP BY `value`, `cb_my_table`.`id`, `cb_person_details`.`id` HAVING `tot` < 2 ORDER BY `cb_my_table`.`id` DESC,`value` ASC LIMIT 1 OFFSET 0) as bb"
            , $nestedQuery->getQuery()->getRawSql());
    }

    public function testInsertQuery()
    {
        $builder = $this->builder->from('my_table');
        $data = array('key' => 'Name',
                'value' => 'Sana',);

        $this->assertEquals("INSERT INTO cb_my_table (`key`,`value`) VALUES ('Name','Sana')"
            , $builder->getQuery('insert', $data)->getRawSql());
    }

    public function testUpdateQuery()
    {
        $builder = $this->builder->table('my_table')->where('value', '=', 'Sana');

        $data = array(
            'key' => 'Sana',
            'value' => 'Amrin',
        );

        $this->assertEquals("UPDATE cb_my_table SET `key`='Sana',`value`='Amrin' WHERE `value` = 'Sana'"
            , $builder->getQuery('update', $data)->getRawSql());
    }

    public function testDeleteQuery()
    {
        $this->builder = new QueryBuilder\QueryBuilderHandler($this->mockConnection);

        $builder = $this->builder->table('my_table')->where('value', '=', 'Amrin');

        $this->assertEquals("DELETE from cb_my_table WHERE `value` = 'Amrin'"
            , $builder->getQuery('delete')->getRawSql());
    }

}