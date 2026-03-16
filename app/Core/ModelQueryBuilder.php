<?php

namespace App\Core;

use App\Abstracts\BaseModel;

/** Query Builder с привязкой к модели: автоматический soft delete и гидрация в модели. */
class ModelQueryBuilder extends QueryBuilder
{
    /** @var class-string<BaseModel> */
    private string $modelClass;

    /** @param class-string<BaseModel> $modelClass FQCN класса модели. */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        parent::__construct($modelClass::getTable());

        if ($modelClass::hasSoftDelete()) {
            $this->wheres[] = ['type' => 'null', 'column' => 'deleted_at', 'softDelete' => true];
        }
    }

    /** Отключить автоматический soft delete фильтр.
     * @return static
     */
    public function withTrashed(): static
    {
        $this->wheres = array_values(array_filter(
            $this->wheres,
            fn($w) => empty($w['softDelete'])
        ));
        return $this;
    }

    /** Выполнить SELECT и вернуть массив моделей.
     * @return array<int, BaseModel>
     */
    public function get(): mixed
    {
        return array_map(
            fn(array $row) => new ($this->modelClass)($row),
            parent::get()
        );
    }

    /** Выполнить SELECT и вернуть первую модель.
     * @return BaseModel|null
     */
    public function first(): mixed
    {
        $row = parent::first();
        return $row !== null ? new ($this->modelClass)($row) : null;
    }

    /** Постраничный запрос с гидрацией.
     * @param int $page    Номер страницы.
     * @param int $perPage Записей на страницу.
     * @return array{data: array<int, BaseModel>, meta: array{total: int, per_page: int, current_page: int, last_page: int}}
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $result = parent::paginate($page, $perPage);
        $result['data'] = array_map(
            fn(array $row) => new ($this->modelClass)($row),
            $result['data']
        );
        return $result;
    }
}
