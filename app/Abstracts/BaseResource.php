<?php

namespace App\Abstracts;

/** Базовый класс для ресурсов API. Определяет поля, возвращаемые в ответе. */
abstract class BaseResource
{
    /** @param BaseModel $resource Экземпляр модели. */
    public function __construct(protected readonly BaseModel $resource) {}

    /** Преобразовать ресурс в массив для API-ответа.
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /** Создать ресурс и вернуть его массив.
     * @param BaseModel $resource
     * @return array<string, mixed>
     */
    public static function make(BaseModel $resource): array
    {
        return (new static($resource))->toArray();
    }

    /** Преобразовать коллекцию моделей в массив ресурсов.
     * @param array<int, BaseModel> $resources
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $resources): array
    {
        return array_map(fn(BaseModel $resource) => (new static($resource))->toArray(), $resources);
    }
}
