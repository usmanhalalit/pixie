<?php
require 'vendor/autoload.php';

new Pixie\Connection('mysql', array(
                    'driver'    => 'mysql',
                    'host'      => 'localhost',
                    'database'  => 'caliber',
                    'username'  => 'root',
                    'password'  => 'bossboss',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => 'cb_',
            ), 'DB');

$query = DB::table('my_table')
    ->where('my_table.id', '>', 1)
    ->orWhere('my_table.id', 1)
    ->where(function($q)
        {
            $q->where('value', 'LIKE', '%sana%');
            $q->orWhere(function($q2)
                {
                    $q2->where('key', 'LIKE', '%sana%');
                    $q2->orWhere('value', 'LIKE', '%sana%');
                });
        })
    ->join(DB::raw('cb_person_details as a'), DB::raw('a.person_id'), '=', 'my_table.id')

    ->leftJoin(DB::raw('cb_person_details as b'), function($table)
        {
            $table->on(DB::raw('b.person_id'), '=', 'my_table.id');
            $table->orOn(DB::raw('b.age'), '>', DB::raw(1));
        })
;

//var_dump($query->getQuery()->getRawSql());
//var_dump($query->get());

DB::getConnection()->createAlias('QB');

$subQuery = QB::table('person_details')->select('details')->where('person_id', '=', 3);


$query = QB::table('my_table')
            ->select('my_table.*')
            ->select(QB::subQuery($subQuery, 'pop'));

$nestedQuery = QB::table(QB::subQuery($query, 'bb'))->select('*');

//var_dump($nestedQuery->getQuery()->getRawSql());

var_dump(DB::query('select * from cb_my_table where id = ?', array(2))->get());
