<?php


namespace Doomy\Ormtopus;

use Doomy\Repository\RepoFactory;
use Doomy\EntityCache\EntityCache;

class DataEntityManager
{
    private $repoFactory;
    private $entityCache;

    public function __construct(RepoFactory $repoFactory, EntityCache $entityCache) {
        $this->repoFactory = $repoFactory;
        $this->entityCache = $entityCache;
    }

    public function findOne($entityClass, $where, $orderBy = null) {
        $repository = $this->repoFactory->getRepository($entityClass);
        return $repository->findOne($where, $orderBy);
    }

    public function findAll($entityClass, $where = null, $orderBy = null, $limit = null) {
        if (!$where && !$orderBy) {
            $cached = $this->entityCache->getAll($entityClass);
            if ($cached) return $cached;
        }

        $repository = $this->repoFactory->getRepository($entityClass);
        $entities = $repository->findAll($where, $orderBy, $limit);
        $this->entityCache->cacheAll($entityClass, $entities);
        return $entities;
    }

    public function save($entityClass, $values) {
        $repository = $this->repoFactory->getRepository($entityClass);
        $this->entityCache->flush($entityClass);
        return $repository->save($values);
    }

    public function findById($entityClass, $id) {
        $cached = $this->entityCache->getById($entityClass, $id);
        if ($cached) return $cached;

        $repository = $this->repoFactory->getRepository($entityClass);
        $entity = $repository->findById($id);
        $this->entityCache->cacheById($entityClass, $id, $entity);
        return $entity;
    }

    public function deleteById($entityClass, $id) {
        $repository = $this->repoFactory->getRepository($entityClass);
        $this->entityCache->flushById($entityClass, $id);
        return $repository->deleteById($id);
    }

    public function delete($entityClass, $where) {
        $repository = $this->repoFactory->getRepository($entityClass);
        $this->entityCache->flush($entityClass);
        $repository->delete($where);
    }

    public function create($entityClass, $values) {
        return new $entityClass($values, $this->repoFactory);
    }
}
