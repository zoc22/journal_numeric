<?php
// app-modules/Core/Services/BaseService.php

namespace Modules\Core\Services;

use Modules\Core\Contracts\ServiceInterface;
use Modules\Core\Contracts\RepositoryInterface;

/**
 * Classe abstraite que tous les services métier pourront étendre.
 * Elle contient la logique générique CRUD.
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * Le repository associé à ce service.
     *
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * Constructeur : injection du repository.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Récupère toutes les entités.
     *
     * @return mixed
     */
    public function getAll(): mixed
    {
        return $this->repository->all();
    }

    /**
     * Récupère une entité par son ID.
     *
     * @param int|string $id
     * @return mixed
     */
    public function getById($id): mixed
    {
        return $this->repository->find($id);
    }

    /**
     * Crée une nouvelle entité.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data): mixed
    {
        return $this->repository->create($data);
    }

    /**
     * Met à jour une entité.
     *
     * @param int|string $id
     * @param array $data
     * @return mixed
     */
    public function update($id, array $data): mixed
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Supprime une entité.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool
    {
        return $this->repository->delete($id);
    }
}
