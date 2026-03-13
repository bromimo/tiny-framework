# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run a single test file
./vendor/bin/phpunit tests/Unit/Core/ValidatorTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testRequiredRule tests/Unit/Core/ValidatorTest.php
```

Tests require `.env.testing` with `DB_DATABASE=proj1_test`. Run migrations against that DB before feature tests.

## Architecture

Кастомный PHP-фреймворк без Laravel/Symfony. Единственная внешняя зависимость для роутинга — `bromimo/tiny-router`.

### Request lifecycle

`public/index.php` → `bootstrap/app.php` (loads `.env`, includes routes) → `TinyRouter` dispatch → `AuthMiddleware` (if protected route) → Controller → `ApiResponse`

### Слои

| Слой | Расположение | Назначение |
|------|-------------|------------|
| Core | `app/Core/` | Database (PDO singleton), SQL (query wrapper), Logger, Validator, Env, Config, Cache drivers |
| Facades | `app/Facades/` | Статические фасады: `DB`, `Cache`, `Config`, `Env` |
| Abstracts | `app/Abstracts/` | `BaseModel` (CRUD через `DB`), `BaseDto` (reflection-based from/toArray), `BaseRequest` (JSON body + validation) |
| Models | `app/Models/` | `User`, `Token` — наследуют `BaseModel`, добавляют доменную логику |
| DTOs | `app/DTOs/` | `readonly` классы `UserDto`, `LoginDto` — наследуют `BaseDto` |
| Requests | `app/Http/Requests/` | Валидация + маппинг в DTO через `toDto()` |
| Helpers | `app/Helpers/helpers.php` | Глобальные функции: `q()`, `q1()`, `qi()` (SQL shortcuts), `config()`, `env()`, `generateUuid()`, `getBearerToken()` |

### Database access pattern

Никогда не используй `new SQL()` или `new Database()` напрямую в коде приложения. Доступ только через:
- Хелперы: `q($sql, $params)`, `q1($sql, $params)`, `qi($sql, $params)`
- Фасад: `DB::query()`, `DB::query_once()`, `DB::query_insert()`
- Методы `BaseModel`: `findById()`, `findAll()`, `deleteById()`

### Auth flow

Bearer token (UUID v4) в таблице `tokens` с `expires_at`. `AuthMiddleware` проверяет `Token::findValid($token)`. Токен создаётся при логине, удаляется при логауте. `config('auth.token.lifetime')` по умолчанию `+28 days`.

### Validation

`Validator::validate($data, $rules)` возвращает массив ошибок. Правила: `required`, `email`, `min:N`, `max:N`. Первая ошибка на поле останавливает проверку этого поля. Контроллеры вызывают `(new SomeRequest($request))->validate()` — при ошибках возвращает `ApiResponse::error($errors, 422)`.

### Config & Cache

`Config::get('auth.token.lifetime')` читает конфиг с dot-нотацией из `config/*.php`. Config кешируется через `Cache`. Дефолтный драйвер кеша — memcached (см. `config/cache.php`), можно переключить на `file` или `array`.

## Code Style

- PHPDocs на русском: описание на первой строке сразу после `/**` без пустой строки. Все теги: `@param`, `@return`, `@throws`.
- `use`-импорты выстраивать по возрастанию длины строки.
- `BaseDto` — `abstract readonly class` (чтобы readonly свойства наследовались).
- Ответы API: только через статические методы `ApiResponse::ok()`, `::created()`, `::error()`, `::notFound()`, `::unauthorized()`.