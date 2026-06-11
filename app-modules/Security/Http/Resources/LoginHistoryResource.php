<?php

declare(strict_types=1);

namespace Modules\Security\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'succes' => $this->succes,
            'message_erreur' => $this->message_erreur,
            'ip_address' => $this->ip_address,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'os' => $this->os,
            'country_code' => $this->country_code,
            'continent' => $this->continent,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'city' => $this->city,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
