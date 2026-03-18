<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use TinyRouter\Http\Request;
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
     * @param Request $request
     * @return Response
     * @throws \App\Exceptions\AuthenticationException
     * @throws \App\Exceptions\AuthorizationException
     */
    public function index(Request $request): Response
    {
        authorize('viewAny', User::class);

        $result = User::paginate($request);

        return success(UserResource::collection($result['data']), $result['meta']);
    }

    /** Получить пользователя по ID.
     * @param User $user
     * @return Response
     * @throws \App\Exceptions\AuthenticationException
     * @throws \App\Exceptions\AuthorizationException
     */
    public function show(User $user): Response
    {
        authorize('view', $user);

        return success(UserResource::make($user));
    }

    /** Создать пользователя.
     * @param CreateUserRequest $req
     * @param CreateUserAction  $action
     * @return Response
     * @throws ValidationException
     * @throws QueryException
     * @throws \App\Exceptions\AuthenticationException
     * @throws \App\Exceptions\AuthorizationException
     */
    public function store(CreateUserRequest $req, CreateUserAction $action): Response
    {
        authorize('create', User::class);

        return created(UserResource::make($action->run($req->toDto())));
    }

    /** Обновить пользователя.
     * @param UpdateUserRequest $req
     * @param User              $user
     * @param UpdateUserAction  $action
     * @return Response
     * @throws ValidationException
     * @throws QueryException
     * @throws \App\Exceptions\AuthenticationException
     * @throws \App\Exceptions\AuthorizationException
     */
    public function update(UpdateUserRequest $req, User $user, UpdateUserAction $action): Response
    {
        authorize('update', $user);

        return success(UserResource::make($action->run($user, $req->toDto())));
    }

    /** Удалить пользователя.
     * @param User             $user
     * @param DeleteUserAction $action
     * @return Response
     * @throws \App\Exceptions\AuthenticationException
     * @throws \App\Exceptions\AuthorizationException
     */
    public function destroy(User $user, DeleteUserAction $action): Response
    {
        authorize('delete', $user);

        $action->run($user);

        return success(['message' => 'User deleted successfully.']);
    }
}
