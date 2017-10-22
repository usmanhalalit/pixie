<?php

namespace Pixie;

use Pixie\QueryBuilder\QueryBuilderHandler;

class QueryObjectTest extends TestCase
{
    /**
     * Tests how a float value is inserted to the raw query
     */
    public function testFloatValuesToRawSql()
    {
        $originalLocale = setlocale(LC_NUMERIC, null);
        $builder = new QueryBuilderHandler($this->mockConnection);

        // A locale with dot decimal separator
        setlocale(LC_NUMERIC, 'en_US');
        $query = $builder->table('table_name')->select('*')->where('field_name', 12.3456789);
        $this->assertEquals(
            'SELECT * FROM `cb_table_name` WHERE `field_name` = 12.3456789',
            $query->getQuery()->getRawSql()
        );

        // A locale with comma decimal separator
        setlocale(LC_NUMERIC, 'ru_RU');
        $query = $builder->table('table_name')->select('*')->where('field_name', 98.7654321);
        $this->assertEquals(
            'SELECT * FROM `cb_table_name` WHERE `field_name` = 98.7654321',
            $query->getQuery()->getRawSql()
        );

        setlocale(LC_NUMERIC, $originalLocale);
    }
}
