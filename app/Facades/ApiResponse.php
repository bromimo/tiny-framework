<?php

namespace App\Facades;

use TinyRouter\Http\Response;
use App\Abstracts\BaseResource;
use App\Core\ApiResponse as CoreApiResponse;

/** Статический фасад над App\Core\ApiResponse. */
class ApiResponse
{
    /** Ответ 200 OK.
     * @param BaseResource|array<mixed> $data Данные ответа.
     * @param array<string, mixed>      $meta Метаданные пагинации (необязательно).
     * @return Response
     */
    public static function ok(BaseResource|array $data, array $meta = []): Response
    {
        return CoreApiResponse::ok($data, $meta);
    }

    /** Ответ 201 Created.
     * @param BaseResource|array<mixed> $data
     * @return Response
     */
    public static function created(BaseResource|array $data): Response
    {
        return CoreApiResponse::created($data);
    }

    /** Ответ с ошибкой.
     * @param string $message
     * @param int    $status
     * @return Response
     */
    public static function error(string $message, int $status = 400): Response
    {
        return CoreApiResponse::error($message, $status);
    }

    /** Ответ с ошибкой валидации (422).
     * @param array<string, string> $fields
     * @param string                $message
     * @return Response
     */
    public static function validationError(array $fields, string $message = 'Validation failed.'): Response
    {
        return CoreApiResponse::validationError($fields, $message);
    }

    /** Ответ 404 Not Found.
     * @param string $message
     * @return Response
     */
    public static function notFound(string $message = 'Not found.'): Response
    {
        return CoreApiResponse::notFound($message);
    }

    /** Ответ 401 Unauthorized.
     * @param string $message
     * @return Response
     */
    public static function unauthorized(string $message = 'Unauthorized.'): Response
    {
        return CoreApiResponse::unauthorized($message);
    }
}
