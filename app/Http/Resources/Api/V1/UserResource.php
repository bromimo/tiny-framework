<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Abstracts\BaseResource;

/** Ресурс пользователя для API v1. Скрывает поле password. */
class UserResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ];
    }
}
