<?php
// app-modules/Core/Contracts/ServiceInterface.php

namespace Modules\Core\Contracts;

/**
 * Interface de base pour tous les services métier.
 * Un service contient la logique métier et peut utiliser plusieurs repositories.
 */
interface ServiceInterface
{
    /**
     * Récupère toutes les entités.
     *
     * @return mixed
     */
    public function getAll();

    /**
     * Récupère une entité par son ID.
     *
     * @param int|string $id
     * @return mixed
     */
    public function getById($id);

    /**
     * Crée une nouvelle entité.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Met à jour une entité.
     *
     * @param int|string $id
     * @param array $data
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Supprime une entité.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id);
}
