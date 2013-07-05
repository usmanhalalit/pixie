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
    /*->where('my_table.id', '>', 1)
    //->orWhere('id', '=', 1)
    ->where(function($q)
        {
            $q->where('value', 'LIKE', '%sana%');
            $q->orWhere(function($q2)
                {
                    $q2->where('key', 'LIKE', '%sana%');
                });
        })*/
    ->join(DB::raw('cb_person_details as a'), DB::raw('a.person_id'), '=', 'my_table.id')

    ->join(DB::raw('cb_person_details as b'), function($table)
        {
            $table->on(DB::raw('b.person_id'), '=', 'my_table.id');
            $table->orOn(DB::raw('b.age'), '>', DB::raw(1));
        }, null, null, 'left')

;

var_dump($query->get());