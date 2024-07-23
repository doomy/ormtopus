# Ormtopus
simple ORM library working with Nette framework with entity caching for better performance.

## Prerequisities
In order for orm to work, working dibi connection needs to be established first. In your local configuration file (`local.neon`), you should have your connection set up in similar fashion as below:

```neon
parameters:
    dibi:
        host: localhost
        username: db_username
        password: db_password
        database: db_schema
```

Then, in your global config file (`common.neon`/ `config.neon`) set up following services:

```neon
services:
	connection: Dibi\Connection(%dibi%)
	entityFactory: Doomy\Repository\EntityFactory(@connection)
	repoFactory: Doomy\Repository\RepoFactory(@connection, @entityFactory)
	entityCache: Doomy\EntityCache\EntityCache
	data: Doomy\Ormtopus\DataEntityManager(@repoFactory, @entityCache)
```

### Entity models

for each entity you plan to be working with equivalent php entity model class has to be created manually. See the example below:
```php
<?php

namespace App\Client\Model;

use Doomy\Repository\Model\Entity;
use Doomy\Repository\TableDefinition\Attribute\Column\Identity;
use Doomy\Repository\TableDefinition\Attribute\Column\PrimaryKey;
use Doomy\Repository\TableDefinition\Attribute\Table;


#[Table('test_table')]
class Client extends Entity
{
    #[PrimaryKey]
    #[Identity]
    private ?int $id;
    
    public function __construct(?int $id = null) {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

starting from version 3.0.0 there's no need to specify the columns or column properties in uppercase.

## Usage
The orm is used by injecting `DataEntityManager` service into your presenters or wherever needed, see the example:

```php
final class DashboardPresenter extends Nette\Application\UI\Presenter
{
    private $data;

    public function __construct(DataEntityManager $data)
    {
        $this->data = $data;
        parent::__construct();
    }

    public function renderIndex()
    {
        $this->template->clients = $this->data->findAll(Client::class);
    }
}
```

### Data retrieval methods

#### findById(string $entityClass, int $entityId)
finds a single entity by its id.

#### findOne(string $entityClass, array|string $where = [])
finds and returns **first** entity meeting the criteria in `$where`. Notes on how to compose `$where ` array below.

#### findAll(string $entityClass, array|string $where = [])
finds and returns array of all entities (if where is specified then all entities that meet the criteria) of given class. Notes on how to compose `$where ` array below.

### Data manipulation methods


[DEPRECATED]
#### ~~create(string $entityClass, array $values)~~
~~creates an instance of given entity class from associative array of values in method argument.~~

~~example:  `$clientEntity = $this->data->create(Client::class, ['name' => 'Microsoft', 'ADDRESS' => 'Tasmania']);`~~

NOTE: the preferred way to create a new entity (since version 3.0.0) is now using it's  constructor:
```php
    $clientEntity = new Client(id: 123);
```
**This does not save the entity to the database**

#### deleteById(string $entityClass, int $id)
deletes entity in database by id provided

#### delete(string $entityClass, array|string $where)
deletes all entities meeting provided criteria in database. Notes on how to compose `$where ` array below.

### Composing the where criteria

There are basically two criteria assembly styles.

#### $where as a simple string value
You can either use the whole condition, you would otherwise use in SQL statement as a single string:

`$client = $this->data->findOne(Client::class, 'id > 15 AND address = 'New York')`

#### $where as an associative array
The criteria can also be specified as an associative array. See example:

```php
$client = $this->data->findOne(
	Client::class, 
	['id' => 15, 'address' == 'New York']
)`
```
in general usage, multiple criteria will be considered to be joined by AND clause. The associative example is therefore equivalent to the simple string criteria in the former example.

##### LIKE expressition in assocative $where
`$client = $this->data->findOne(Client::class, ['name' => '~Micros']);`

will have the same result as:

`$client = $this->data->findOne(Client::class, "name LIKE '%Mircros%'");`

More complex LIKE criteria can be achieved using the single string $where criteria.

##### set criteria in assocative $where
`$clients = $this->data->findAll(Client::class, ['id' => [1, 2, 3]]);`

equals to:

`$clients = $this->data->findAll(Client::class, "WHERE id IN(1, 2, 3)");`



