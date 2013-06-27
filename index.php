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

$subQuery = DB::table('person_details')->select('details')->where('person_id', '=', 3);

$query = DB::table('my_table')
    ->select('my_table.*')
    ->select(array(DB::raw('count(cb_my_table.id) as tot'), DB::subQuery($subQuery, 'pop')))
    ->where('value', '=', 'Ifrah')
    ->orWhereIn('my_table.id', [1, 2])
    ->groupBy(['value', 'my_table.id', 'person_details.id'])
    ->orderBy('my_table.id', 'DESC')
    ->orderBy('value')
    ->having('tot', '<', 2)
    ->limit(1)
    ->offset(0)
    ->join('person_details', 'person_details.person_id', '=', 'my_table.id')
    //->join('cb_person_details', 'cb_person_details.person.id', '=', 'cb_my_table.id')
;

$nestedQuery = DB::table(DB::subQuery($query, 'bb'))->select('*');
//$query = DB::select('*')->from('cb_my_table')->whereNotIn('id', array(1))->get();
var_dump($nestedQuery->get());