<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use TinyRouter\Http\Response;
use App\Exceptions\QueryException;
use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\UpdateUserAction;
use App\Exceptions\ValidationException;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Requests\Api\V1\CreateUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;

/** Контроллер управления пользователями. */
class UserController
{
    /** Список пользователей с пагинацией.
     * @return Response
     */
    public function index(): Response
    {
        $page    = (int) ($_GET['page']     ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 15);
        $result  = User::paginate($page, $perPage);

        return success(UserResource::collection($result['data']), $result['meta']);
    }

    /** Получить пользователя по ID.
     * @param User $user
     * @return Response
     */
    public function show(User $user): Response
    {
        return success(UserResource::make($user));
    }

    /** Создать пользователя.
     * @param CreateUserRequest $req
     * @param CreateUserAction  $action
     * @return Response
     * @throws ValidationException
     * @throws QueryException
     */
    public function store(CreateUserRequest $req, CreateUserAction $action): Response
    {
        return created(UserResource::make($action->run($req->toDto())));
    }

    /** Обновить пользователя.
     * @param UpdateUserRequest $req
     * @param User              $user
     * @param UpdateUserAction  $action
     * @return Response
     * @throws ValidationException
     * @throws QueryException
     */
    public function update(UpdateUserRequest $req, User $user, UpdateUserAction $action): Response
    {
        return success(UserResource::make($action->run($user, $req->toDto())));
    }

    /** Удалить пользователя.
     * @param User             $user
     * @param DeleteUserAction $action
     * @return Response
     */
    public function destroy(User $user, DeleteUserAction $action): Response
    {
        $action->run($user);

        return success(['message' => 'User deleted successfully.']);
    }
}
