<?php

namespace App\Models;

use App\Abstracts\BaseModel;

/** Модель пермишена.
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 */
class Permission extends BaseModel
{
    protected static string $table = 'permissions';

    protected static array $fillable = ['name', 'description'];

    /** Найти пермишен по имени.
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        return static::findByField('name', $name);
    }
}
