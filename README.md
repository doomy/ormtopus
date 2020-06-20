# Ormtopus
simple ORM library working with Nette framework with entity caching for better performance.

## Prerequisities
In order for orm to work, working dibi connection needs to be established first. In your local configuration file (`local.neon`), you should have your connection set up in similar fashion as below:

```
parameters:
    dibi:
        host: localhost
        username: db_username
        password: db_password
        database: db_schema
```

Then, in your global config file (`common.neon`/ `config.neon`) set up following services:

```
services:
	...
	connection: Doomy\CustomDibi\Connection(%dibi%)
	entityFactory: Doomy\Repository\EntityFactory(@connection)
	repoFactory: Doomy\Repository\RepoFactory(@connection, @entityFactory)
	entityCache: Doomy\EntityCache\EntityCache
	data: Doomy\Ormtopus\DataEntityManager(@repoFactory, @entityCache)
```

### Entity models

for each entity you plan to be working with equivalent php entity model class has to be created manually. See the example below:
```
<?php

namespace App\Client\Model;

use Doomy\Repository\Model\Entity;

class Client extends Entity
{
    const TABLE = 't_client';

    public $NAME;
	public $ADDRESS;
}
```

all the accessible database fields need to be specified as public property with identifier in UPPERCASE (regardless of the case in the db schema).

## Usage
The orm is used by injecting `DataEntityManager` service into your presenters or wherever needed, see the example:

```
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

#### create(string $entityClass, array $values)
creates an instance of given entity class from associative array of values in method argument. **This does not save the entity to the database**

example:  `$clientEntity = $this->data->create(Client::class, ['NAME' => 'Microsoft', 'ADDRESS' => 'Tasmania']);`

#### deleteById(string $entityClass, int $id)
deletes entity in database by id provided

#### delete(string $entityClass, array|string $where)
deletes all entities meeting provided criteria in database. Notes on how to compose `$where ` array below.

### Composing the where criteria

There are basically two criteria assembly styles.

#### $where as a simple string value
You can either use the whole condition, you would otherwise use in SQL statement as a single string:

`$client = $this->data->findOne(Client::class, 'ID > 15 AND ADDRESS = 'New York')`

#### $where as an associative array
The criteria can also be specified as an associative array. See example:

```
$client = $this->data->findOne(
	Client::class, 
	['ID' => 15, 'ADDRESS' == 'New York']
)`
```
in general usage, multiple criteria will be considered to be joined by AND clause. The associative example is therefore equivalent to the simple string criteria in the former example.

##### LIKE expressition in assocative $where
`$client = $this->data->findOne(Client::class, ['NAME' => '~Micros']);`

will have the same result as:

`$client = $this->data->findOne(Client::class, "NAME LIKE '%Mircros%'");`

More complex LIKE criteria can be achieved using the single string $where criteria.

##### set criteria in assocative $where
`$clients = $this->data->findAll(Client::class, ['ID' => [1, 2, 3]]);`

equals to:

`$clients = $this->data->findAll(Client::class, "WHERE id IN(1, 2, 3)");`



