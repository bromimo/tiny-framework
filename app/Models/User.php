<?php

namespace App\Models;

use App\DTOs\UserDto;
use App\Abstracts\BaseModel;
use App\Attributes\ObservedBy;
use App\Observers\UserObserver;
use App\Exceptions\QueryException;

/** Модель таблицы `users`. */
#[ObservedBy(UserObserver::class)]
class User extends BaseModel
{
    protected static string $table    = 'users';
    protected static array  $fillable = ['first_name', 'last_name', 'email', 'password'];
    protected static array  $hidden   = ['password'];

    /** Найти пользователя по email-адресу.
     * @param string $email
     * @return static|null Null если не найден.
     */
    public static function findByEmail(string $email): ?static
    {
        return static::findByField('email', $email);
    }

    /** Создать нового пользователя из DTO.
     * @param UserDto $dto
     * @return static|null Созданная запись, или null если обсервер отменил.
     * @throws QueryException При ошибке запроса (например, дублирование email — SQLSTATE 23000).
     */
    public static function create(UserDto $dto): ?static
    {
        return static::insert([
            'first_name' => $dto->first_name,
            'last_name'  => $dto->last_name,
            'email'      => $dto->email,
            'password'   => password_hash($dto->password, config('auth.password.algo'), config('auth.password.options')),
        ]);
    }

    /** Обновить существующего пользователя непустыми полями из DTO.
     * @param int     $id  ID пользователя.
     * @param UserDto $dto Поля для обновления; пустые и null-значения пропускаются.
     * @return static|null Обновлённая запись, или null если не найдена.
     * @throws QueryException При ошибке запроса (например, дублирование email — SQLSTATE 23000).
     */
    public static function update(int $id, UserDto $dto): ?static
    {
        return static::modify($id, [
            'first_name' => $dto->first_name,
            'last_name'  => $dto->last_name,
            'email'    => $dto->email,
            'password' => $dto->password !== null
                ? password_hash($dto->password, config('auth.password.algo'), config('auth.password.options'))
                : null,
        ]);
    }
}
