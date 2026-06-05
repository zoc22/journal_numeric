<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection de ressources Maison pour la pagination.
 */
class MaisonCollection extends ResourceCollection
{
    /**
     * Transforme la collection de ressources en tableau.
     *
     * @param Request $request
     * @return array<int, mixed>
     */
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
