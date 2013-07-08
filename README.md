# Pixie Query Builder
An expressive, framework agnostic query builder for PHP, it supports MySQL, SQLite and PostgreSQL. It takes care of query sanitization, table prefixing and many other things with a unified API. PHP 5.3 is required.

It has some advanced features like:

 - Nested Criteria
 - Sub Queries
 - Nested Queries
 - Multiple Database Connections.

The syntax is quite similar to Laravel's query builder.

### Table of Context

 - [Example](#example)
 - [Installation](#installation)
 - [Connection](#connection)
    - [Alias](#alias)
    - [Multiple Connection](#alias)
    - [SQLite and PostgreSQL Config Sample](sqlite-and-postgresql-config-sample)
 - [Query](#query)
 - [**Select**](#select)
    - [Get Easily](#get-easily)
    - [Multiple Selects](#multiple-selects)
    - [Get All](#get-all)
    - [Get First Row](#get-first-row)
    - [Get Rows Count](#get-rows-count)
 - [**Where**](#where)
    - [Where In](#where-in)
    - [Grouped Where](#grouped-where)
 - [Group By and Order By](#group-by-and-order-by)
 - [Having](#having)
 - [Limit and Offset](#limit-and-offset)
 - [Join](#join)
    - [Multiple Join Criteria](#multiple-join-criteria)
 - [Raw Query](#raw-query)
    - [Raw Expressions](#raw-expressions)
 - [**Insert**](#insert)
    - [Batch Insert](#batch-insert)
 - [**Update**](#update)
 - [**Delete**](#delete)
 - [Get Built Query](#get-built-query)
 - [Sub Queries and Nested Queries](#sub-queries-and-nested-queries)
 - [Get PDO Instance](#get-pdo-instance)


## Example
```PHP
// Create a connection, once only.
new \Pixie\Connection('mysql', array(
                    'driver'    => 'mysql',
                    'host'      => 'localhost',
                    'database'  => 'your-database',
                    'username'  => 'root',
                    'password'  => 'your-password',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => 'cb_',
            ), 'QB');
```

The query below returns the row where id = 3, null if no rows.
```PHP
$row = QB::table('my_table')->find(3);
```

Full queries:

```PHP
$query = QB::table('my_table')->where('name', '=', 'Sana');

// Get result
var_dump($query->get());
```

There are many advanced options which are documented below. Sold? Lets install.

## Installation

Pixie uses [Composer](http://getcomposer.org/doc/00-intro.md#installation-nix) to make things easy.

Learn to use composer and add this to require section (in your composer.json):

    "usmanhalalit/pixie": "dev-master"

And run:

    composer update

Library on [Packagist](https://packagist.org/packages/usmanhalalit/pixie).

## Full Usage API
___

## Connection
Pixie supports three database drivers, MySQL, SQLite and PostgreSQL. You can specify the driver during connection and the associated configuration when creating a new connection. You can also create multiple connections, but use different alias for each (not `QB` for all);
```PHP
$config = array(
            'driver'    => 'mysql', // Db driver
            'host'      => 'localhost',
            'database'  => 'your-database',
            'username'  => 'root',
            'password'  => 'your-password',
            'charset'   => 'utf8', // Optional
            'collation' => 'utf8_unicode_ci', // Optional
            'prefix'    => 'cb_', // Table prefix, optional
        )

new \Pixie\Connection('mysql', $config), 'QB');

$query = QB::table('person_details')->where('person_id', '=', 3);
```

### Alias
**(Optional topic, you may skip)**

When you create a connection:
```PHP
new \Pixie\Connection('mysql', $config), 'MyAlias');
```
MyAlias is the name for the class alias you want to use (like `MyAlias::table(...)`), you can use whatever name (with Namespace also, `MyNamespace\\MyClass`) you like or you may skip it if you don't need an alias. Alias gives you the ability to easily access the QueryBuilder class across your application.

When not using an alias you can instanciate the QueryBuilder handler separately, helpful for Dependency Injection and Testing.

```PHP
$connection = new \Pixie\Connection('mysql', $config));
$qb = new \Pixie\QueryBuilder\QueryBuilderHandler($connection);

$query = $qb->table('person_details')->where('person_id', '=', 3);

var_dump($query->get());
```

`$connection` here is optional, if not given it will always associate itself to the first connection, but it can be useful when you have multiple database connections.

### SQLite and PostgreSQL Config Sample
```PHP
new \Pixie\Connection('sqlite', array(
        	    'driver'   => 'sqlite',
			    'database' => 'your-file.sqlite',
			    'prefix'   => 'cb_',
		    ), 'QB');
```

```PHP
new \Pixie\Connection('pgsql', array(
                    'driver'   => 'pgsql',
                    'host'     => 'localhost',
                    'database' => 'your-database',
                    'username' => 'postgres',
                    'password' => 'your-password',
                    'charset'  => 'utf8',
                    'prefix'   => 'cb_',
                    'schema'   => 'public',
                ), 'QB');
```

## Query
You **must** use `table()` method before every query.
To select from multiple tables just pass an array.
```PHP
QB::table(array('mytable1', 'mytable2'));
```


### Get Easily
The query below returns the (first) row where id = 3, null if no rows.
```PHP
$row = QB::table('my_table')->find(3);
```
Access you row like, `echo $row->name`. If your field name is not `id` then pass the field name as second parameter `QB::table('my_table')->find(3, 'person_id');`.

The query below returns the all rows where name = 'Sana', null if no rows.
```PHP
$result = QB::table('my_table')->findAll('name', 'Sana');
```


### Select
```PHP
$query = QB::table('my_table')->select('*');
```

#### Multiple Selects
```PHP
->select(array('mytable.myfield1', 'mytable.myfield2', 'another_table.myfield3'));
```

Using select method multiple times `select('a')->select('b')` will also select `a` and `b`. Can be useful if you want to do conditional selects (within a PHP `if`).


#### Get All
Return an array.
```PHP
$query = QB::table('my_table')->where('name', '=', 'Sana');
$result = $query->get();
```
You can loop through it like:
```PHP
foreach ($result as $row) {
    echo $row->name;
}
```

#### Get First Row
```PHP
$query = QB::table('my_table')->where('name', '=', 'Sana');
$row = $query->first();
```
Returns the first row, or null if there is no record. Using this method you can also make sure if a record exists. Access these like `echo $row->name`.


#### Get Rows Count
```PHP
$query = QB::table('my_table')->where('name', '=', 'Sana');
$query->count();
```

### Where
Basic syntax is `(fieldname, operator, value)`, if you give two parameters then `=` operator is assumed. So `where('name', 'usman')` and `where('name', '=', 'usman')` is the same.

```PHP
QB::table('my_table')
    ->where('name', '=', 'usman')
    ->where('age', '>', 25)
    ->orWhere('type', '=', 'admin')
    ->orWhere('description', 'LIKE', '%query%')
    ;
```


#### Where In
```PHP
QB::table('my_table')
    ->whereIn('name', array('usman', 'sana'))
    ->orWhereIn('name', array('heera', 'dalim'))
    ;

QB::table('my_table')
    ->whereNotIn('name', array('heera', 'dalim'))
    ->orWhereNotIn('name', array('usman', 'sana'))
    ;
```

#### Grouped Where
Sometimes queries get complex, where you need grouped criteria, for example `WHERE age = 10 and (name like '%usman%' or description LIKE '%usman%')`.

Pixie allows you to do so, you can nest as many closures as you need, like below.
```PHP
QB::table('my_table')
            ->where('my_table.age', 10)
            ->where(function($q)
                {
                    $q->where('name', 'LIKE', '%usman%');
                    // You can provide a closure on these wheres too, to nest further.
                    $q->orWhere('description', 'LIKE', '%usman%');
                });
```

### Group By and Order By
```PHP
$query = QB::table('my_table')->groupBy('age')->orderBy('created_at');
```

#### Multiple Group By
```PHP
->groupBy(array('mytable.myfield1', 'mytable.myfield2', 'another_table.myfield3'));

->orderBy(array('mytable.myfield1', 'mytable.myfield2', 'another_table.myfield3'));
```

Using `groupBy()` or `orderBy()` methods multiple times `groupBy('a')->groupBy('b')` will also group by first `a` and than `b`. Can be useful if you want to do conditional grouping (within a PHP `if`). Same applies to `orderBy()`.

### Having
```PHP
    ->having('total_count', '>', 2)
    ->orHaving('type', '=', 'admin');
```

### Limit and Offset
```PHP
->limit(30);

->offset(10);
```

### Join
```PHP
QB::table('my_table')
    ->join('another_table', 'another_table.person_id', '=', 'my_table.id)
    
```

Available methods,

 - join() or innerJoin
 - leftJoin()
 - rightJoin()

If you need `FULL OUTER` join or any other join, just pass it as 5th parameter of `join` method.
```PHP
->join('another_table', 'another_table.person_id', '=', 'my_table.id, 'FULL OUTER')
```

#### Multiple Join Criteria
If you need more than one criterion to join a table then pass a closure as second parameter.

```PHP
->join('another_table'), function($table)
    {
        $table->on(''another_table.person_id', '=', 'my_table.id');
        $table->on(''another_table.person_id2', '=', 'my_table.id2');
        $table->orOn(''another_table.age', '>', QB::raw(1));
    })
```

### Raw Query
You can always use raw queries if you need,
```PHP
$query = QB::query('select * from cb_my_table where age = 12');

var_dump($query->get());
```

You can also pass your bindings
```PHP
QB::query('select * from cb_my_table where age = ? and name = ?', array(10, 'usman'));
```

#### Raw Expressions

When you wrap an expression with `raw()` method, Pixie doesn't try to sanitize these.
```PHP
QB::table('my_table')
            ->select(QB::raw('count(cb_my_table.id) as tot'))
            ->where('value', '=', 'Ifrah')
```


___
**NOTE:** Queries that run through `query()` method are not sanitized until you pass all values through bindings. Queries that run through `raw()` method are not sanitized either, you have to do it yourself. And of course these don't add table prefix too, but you can use the `addTablePrefix()` method.

### Insert
```PHP
$data = array(
    'name' = 'Sana',
    'description' = 'Blah'
);
$insertId = QB::table('my_table')->insert($data);
```

`insert()` method returns the insert id.

#### Batch Insert
```PHP
$data = array(
    array(
        'name' = 'Sana',
        'description' = 'Blah'
    ),
    array(
        'name' = 'Usman',
        'description' = 'Blah'
    ),
);
$insertIds = QB::table('my_table')->insert($data);
```

In case of batch insert, it will return an array of insert ids.

### Update
```PHP
$data = array(
    'name' = 'Sana',
    'description' = 'Blah'
);

QB::table('my_table')->where('id', 5)->update($data);
```

Will update the name field to Sana and description field to Blah where id = 5.

### Delete
```PHP
QB::table('my_table')->where('id', '>', 5)->delete();
```
Will delete all the rows where id is greater than 5.

### Get Built Query
Sometimes you may need to get the query string, its possible.
```PHP
$query = QB::table('my_table')->where('id', '=', 3);
$queryObj = $query->getQuery();
```
`getQuery()` will return a query object, from this you can get sql, bindings or raw sql.


```PHP
$queryObj->getSql();
// Returns: SELECT * FROM my_table where `id` = ?
```
```PHP
$queryObj->getBindings();
// Returns: array(3)
```

```PHP
$queryObj->getRawSql();
// Returns: SELECT * FROM my_table where `id` = 3
```

### Sub Queries and Nested Queries
Rarely but you may need to do sub queries or nested queries. Pixie is powerful enough to do this for you. You can create different query objects and use the `QB::subQuery()` method.

```PHP
$subQuery = QB::table('person_details')->select('details')->where('person_id', '=', 3);


$query = QB::table('my_table')
            ->select('my_table.*')
            ->select(QB::subQuery($subQuery, 'pop'));

$nestedQuery = QB::table(QB::subQuery($query, 'bb'))->select('*');
```

This will produce a query like this:

    SELECT * FROM (SELECT `cb_my_table`.*, (SELECT `details` FROM `cb_person_details` WHERE `person_id` = 3) as pop FROM `cb_my_table`) as bb
    
**NOTE:** Pixie doesn't use bindings for sub queries and nested queries. It quotes values with PDO's `quote()` method.

### Get PDO Instance
```PHP
QB::pdo();
```
    
