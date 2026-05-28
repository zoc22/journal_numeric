<?php
// app-modules/Core/Contracts/RepositoryInterface.php

namespace Modules\Core\Contracts;

/**
 * Interface de base pour tous les repositories.
 * Un repository est une couche qui sépare la logique métier de l'accès aux données.
 */
interface RepositoryInterface
{
    /**
     * Récupère tous les enregistrements.
     *
     * @param array $columns Les colonnes à sélectionner.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*']);

    /**
     * Trouve un enregistrement par son identifiant.
     *
     * @param int|string $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find($id);

    /**
     * Crée un nouvel enregistrement.
     *
     * @param array $data Les données à insérer.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);

    /**
     * Met à jour un enregistrement.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data);

    /**
     * Supprime un enregistrement.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id);
}
