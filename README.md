# README #

AQL Generator For ArangoDb-PHP   [Beta]

*NOTE: AQLGen is currently in a beta state, please report any issues in the issue tracker.*

This is a experimental Aql Query Builder to generate AQL strings.

* Version v1.1-beta

### Setup and basic 

 To run the queries in this examples was used the Statement Class of Arangodb Driver available on [Github ArangoDB-PHP](https://github.com/triAGENS/ArangoDB-PHP)

```php
//configure statement
$connection = new Connection($connectionOptions);
$statement = new Statement($connection, array(
                          "query"     => '',
                          "count"     => true,
                          "sanitize"  => true,
                      ));
                      
use tarsys\AqlGen\AqlGen;

  //mount the query
  $query1 = AqlGen::query('u', 'users'); 
    
//execute 
$statement->setQuery($mainQuery->get());
//$statement->bind($mainQuery->getParams()); //if some params has passed
```


### Examples ###
* Simple query
```php
   //SIMPLE QUERIES

   $query1 = AqlGen::query('u', 'users'); 

     echo $query1->get();
  // Generate:  FOR u IN users RETURN u

  //WITH filter
   $query1 = AqlGen::query('u', 'users')->filter('u.yearsOld == 20');
  
    echo $query1->get();
/* Generate: 
    FOR u IN users 
    FILTER u.yearsOld == 20
    RETURN u
*/

```

* Sub Queries

```php
//Example 1: subquery

  $mainQuery = AqlGen::query('u', 'users'); 

  $locations = AqlGen::query('l', 'locations')->filter('u.id == l.id');

  $mainQuery->subquery($locations)
              ->serReturn('{"user": u, "location": l}');

  echo $mainQuery->get();
 /* Generate this string: 
    FOR u IN users 
       FOR l IN locations 
          FILTER u.id == l.id
    RETURN {`user`:u, `location`:l}
  */
  
```

* Filter with bind params

```php

$mainQuery = AqlGen::query('u', 'users')->filter('u.id == @id', ['id'=> 19]); 

$mainQuery->filter('u.name == @name && u.age == @age')->bindParams(['name'=> 'jhon', 'age' => 20]);
$mainQuery->orFilter('u.group == @group')->bindParam('group', 11);
  
echo $mainQuery->get();
/* Generate: 
    FOR u IN users 
       FILTER u.id == @id  && u.name == @name && u.age == @age ||  u.group == @group
    RETURN u
*/

// USE $mainQuery->getParams(); to retrieve bind params

```

* Variable assignment

```php

$mainQuery = AqlGen::query('u', 'users')
            ->let('myvar', 'hello')
            ->let('myfriends', AqlGen::query('f','friends') );
 
 echo $mainQuery->get();
 
 /* Generate this string: 
    FOR u IN users 
       LET  myvar = `hello`
       LET  myfriends = ( 
          FOR f IN friends 
          RETURN f
        )
    RETURN u
  */ 
  
```

* Result grouping

```php

$mainQuery = AqlGen::query('u', 'users')
            ->collect('myvar', 'u.city', 'g');

echo $mainQuery->get();
 
 /* Generate this string: 
    FOR u IN users 
       COLLECT `myvar` = u.city INTO g
    RETURN u
  */

```

* Result sorting

```php

$mainQuery = AqlGen::query('u', 'users')
            ->sort('u.activity', AqlGen::SORT_DESC)
            ->sort(array('u.name','u.created_date')); // asc by default

echo $mainQuery->get();
 
 /* Generate this string: 
    FOR u IN users 
       SORT u.activity DESC, u.name, u.created_date ASC
    RETURN u
  */
```

* Result sorting

```php

$mainQuery = AqlGen::query('u', 'users')
            ->sort('u.activity', AqlGen::SORT_DESC)
            ->sort(array('u.name','u.created_date')); // asc by default

echo $mainQuery->get();
 
 /* Generate this string: 
    FOR u IN users 
       SORT u.activity DESC, u.name, u.created_date ASC
    RETURN u
  */
```

### Data Modification ###

* Insert 

```php

$data = array(
    "name" => "Paul",
    "age" => 21
)

$query = AqlInsert::query('u', 'users', $data); 

echo $query->get();

 /* Generate this string: 
    INSERT {"name": "Paul", "age": 21} IN users    
  */

//between collections
$query = AqlGen::query('u', 'users')
            ->insert('u', 'backup');

echo $query->get();
 
 /* Generate this string: 
    FOR u IN users 
       INSERT u IN backup
  */
```

* Update 

```php

$data = array(
    "name" => "Paul",
    "age" => 21
)

$query = AqlUpdate::query('u', $data, 'users'); 

echo $query->get();

 /* Generate this string: 
    UPDATE {"name": "Paul", "age": 21} IN users    
  */

//with filters 
$data = array(
            'status' => "inactive"
        );

        $query = AqlGen::query('u', 'users')
            ->filter('u.status == 0')
            ->update($data); 

echo $query->get();
 
 /* Generate this string: 
    FOR u IN users 
       FILTER u.status == 0
       UPDATE u IN users
  */
```

### Contribution guidelines ###

* Give me a feedback/sugestions about this implementation !!Very important!!
* Writing tests
* Code review
* Other guidelines
