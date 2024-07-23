<?php

namespace Doomy\Ormtopus\Tests;
use Dibi\Exception;
use Doomy\CustomDibi\Connection;
use Doomy\EntityCache\EntityCache;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\EntityFactory;
use Doomy\Repository\Helper\DbHelper;
use Doomy\Repository\RepoFactory;
use Doomy\Repository\TableDefinition\ColumnTypeMapper;
use Doomy\Repository\TableDefinition\TableDefinitionFactory;
use Doomy\Repository\Tests\Support\TestEntity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class DataEntityManagerTest extends TestCase
{
    private Connection $connection;

    public function __construct(string $name)
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../vendor/doomy/testing/testingDbCredentials.json'), true);

        $this->connection = new Connection($config);

        parent::__construct($name);
    }

    public function testDataEntityManager(): void
    {
        $tableDefinitionFactory = new TableDefinitionFactory(new ColumnTypeMapper());
        $dbHelper = new DbHelper(new ColumnTypeMapper());
        $entityFactory = new EntityFactory();
        $repoFactory = new RepoFactory($this->connection, $entityFactory, $dbHelper, $tableDefinitionFactory);
        $entityCache = new EntityCache();
        $dataEntityManager = new DataEntityManager($repoFactory, $entityCache);

        $tableDefinition = $tableDefinitionFactory->createTableDefinition(TestEntity::class);
        $createCode = $dbHelper->getCreateTable($tableDefinition);

        $this->connection->query($createCode);

        $entity1 = new TestEntity(intColumn: 1, varcharColumn: 'test1');
        $entity2 = new TestEntity(intColumn: 2, varcharColumn: 'test2');
        $entity3 = new TestEntity(varcharColumn: 'test3');

        $dataEntityManager->save(TestEntity::class, $entity1);
        $dataEntityManager->save(TestEntity::class, $entity2);
        $dataEntityManager->save(TestEntity::class, $entity3);

        $foundAll = $dataEntityManager->findAll(TestEntity::class);
        Assert::assertCount(3, $foundAll);

        $foundEntity1 = array_shift($foundAll);
        Assert::assertEquals(1, $foundEntity1->getIntColumn());
        Assert::assertEquals('test1', $foundEntity1->getVarcharColumn());

        $foundEntity2 = array_shift($foundAll);
        Assert::assertEquals(2, $foundEntity2->getIntColumn());
        Assert::assertEquals('test2', $foundEntity2->getVarcharColumn());

        $foundEntity3 = array_shift($foundAll);
        Assert::assertEquals(3, $foundEntity3->getIntColumn()); // testing auto_increment
        Assert::assertEquals('test3', $foundEntity3->getVarcharColumn());


        $entitiesFiltered = $dataEntityManager->findAll(TestEntity::class, ['intColumn' => 1]);
        Assert::assertCount(1, $entitiesFiltered);

        $entitiesFilteredByLike = $dataEntityManager->findAll(TestEntity::class, ['varcharColumn' => "~test"]);
        Assert::assertCount(3, $entitiesFilteredByLike);

        $entityFoundByVarcharColumn = $dataEntityManager->findOne(TestEntity::class, ['varcharColumn' => 'test2']);
        Assert::assertEquals(2, $entityFoundByVarcharColumn->getIntColumn());

        $entityFoundById = $dataEntityManager->findById(TestEntity::class, 3);
        Assert::assertEquals('test3', $entityFoundById->getVarcharColumn());

        $entityFoundById->setVarcharColumn('test 3 updated');
        $dataEntityManager->save(TestEntity::class, $entityFoundById);
        $entityUpdated = $dataEntityManager->findById(TestEntity::class,3);
        Assert::assertEquals('test 3 updated', $entityUpdated->getVarcharColumn());

        $dataEntityManager->deleteById(TestEntity::class,3);
        Assert::assertCount(2, $dataEntityManager->findAll(TestEntity::class));

        $dataEntityManager->delete(TestEntity::class, 'intColumn = 2');
        Assert::assertCount(1, $dataEntityManager->findAll(TestEntity::class));
    }

    public function tearDown(): void
    {
        $this->connection->query('DROP TABLE test_table');
    }

}