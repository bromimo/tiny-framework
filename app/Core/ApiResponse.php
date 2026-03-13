<?php

namespace App\Core;

use TinyRouter\Http\Response;
use App\Abstracts\BaseResource;

/** Формирование JSON-ответов API. */
class ApiResponse
{
    /** Успешный ответ 200.
     * @param BaseResource|array<mixed> $data Данные ответа.
     * @param array<string, mixed>      $meta Метаданные пагинации (необязательно).
     * @return Response
     */
    public static function ok(BaseResource|array $data, array $meta = []): Response
    {
        $payload = ['data' => self::resolve($data)];
        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }
        return self::json($payload, 200);
    }

    /** Ответ 201 Created.
     * @param BaseResource|array<mixed> $data
     * @return Response
     */
    public static function created(BaseResource|array $data): Response
    {
        return self::json(['data' => self::resolve($data)], 201);
    }

    /** Ответ с ошибкой.
     * @param mixed $message
     * @param int   $status
     * @return Response
     */
    public static function error(mixed $message, int $status = 400): Response
    {
        return self::json(['error' => $message], $status);
    }

    /** Ответ 404 Not Found.
     * @param string $message
     * @return Response
     */
    public static function notFound(string $message = 'Not found.'): Response
    {
        return self::error($message, 404);
    }

    /** Ответ 401 Unauthorized.
     * @param string $message
     * @return Response
     */
    public static function unauthorized(string $message = 'Unauthorized.'): Response
    {
        return self::error($message, 401);
    }

    /** Сериализовать payload в JSON-ответ.
     * @param array<mixed> $payload
     * @param int          $status
     * @return Response
     */
    private static function json(array $payload, int $status): Response
    {
        return (new Response(json_encode($payload), $status))
            ->withHeader('Content-Type', 'application/json');
    }

    /** Привести данные к массиву если передан BaseResource.
     * @param BaseResource|array<mixed> $data
     * @return array<mixed>
     */
    private static function resolve(BaseResource|array $data): array
    {
        return $data instanceof BaseResource ? $data->toArray() : $data;
    }
}
