<?php

declare(strict_types=1);

namespace Doomy\Ormtopus;

use Doomy\EntityCache\EntityCache;
use Doomy\Repository\Model\Entity;
use Doomy\Repository\RepoFactory;

final readonly class DataEntityManager
{
    public function __construct(
        private RepoFactory $repoFactory,
        private EntityCache $entityCache
    ) {
    }

    /**
     * @template T of Entity
     * @param class-string<T> $entityClass
     * @param mixed[]|string|null $where
     * @return T|null
     */
    public function findOne(string $entityClass, array|string|null $where, ?string $orderBy = null): ?Entity
    {
        $repository = $this->repoFactory->getRepository($entityClass);
        return $repository->findOne($where, $orderBy);
    }

    /**
     * @template T of Entity
     * @param class-string<T> $entityClass
     * @param mixed[]|string|null $where
     * @return T[]
     */
    public function findAll(
        string $entityClass,
        array|string|null $where = null,
        ?string $orderBy = null,
        ?int $limit = null
    ): array {
        if (! $where && ! $orderBy) {
            $cached = $this->entityCache->getAll($entityClass);
            if ($cached) {
                return $cached;
            }
        }

        $repository = $this->repoFactory->getRepository($entityClass);
        $entities = $repository->findAll($where, $orderBy, $limit);
        $this->entityCache->cacheAll($entityClass, $entities);
        return $entities;
    }

    /**
     * @template T of Entity
     * @param class-string<T> $entityClass
     * @param mixed[] $values
     * @return T
     */
    public function save(string $entityClass, array $values): Entity
    {
        $repository = $this->repoFactory->getRepository($entityClass);
        $this->entityCache->flush($entityClass);
        return $repository->save($values);
    }

    /**
     * @template T of Entity
     * @param class-string<T> $entityClass
     * @return T|null
     */
    public function findById(string $entityClass, int|string $id): ?Entity
    {
        $cached = $this->entityCache->getById($entityClass, $id);
        if ($cached) {
            return $cached;
        }

        $repository = $this->repoFactory->getRepository($entityClass);
        $entity = $repository->findById($id);
        $this->entityCache->cacheById($entityClass, $id, $entity);
        return $entity;
    }

    /**
     * @template T of Entity
     * @param class-string<T> $entityClass
     */
    public function deleteById(string $entityClass, string|int $id): void
    {
        $repository = $this->repoFactory->getRepository($entityClass);
        $this->entityCache->flushById($entityClass, $id);
        $repository->deleteById($id);
    }

    /**
     * @template T of Entity
     * @param mixed[]|string $where
     * @param class-string<T> $entityClass
     */
    public function delete(string $entityClass, array|string $where): void
    {
        $repository = $this->repoFactory->getRepository($entityClass);
        $this->entityCache->flush($entityClass);
        $repository->delete($where);
    }

    /**
     * @template T of Entity
     * @param class-string<T> $entityClass
     * @param mixed[] $values
     * @return T
     */
    public function create(string $entityClass, array $values)
    {
        return new $entityClass($values, $this->repoFactory);
    }
}
