# Authorization (RBAC) — Design Spec

**Дата:** 2026-03-18
**Статус:** Draft
**Пункт роадмапа:** Priority 1 — Authorization (RBAC / ownership checks)

---

## Проблема

Любой аутентифицированный пользователь может CRUD любого другого пользователя. Нет ролей, нет ownership-проверок, нет понятия «текущий пользователь» в пайплайне запроса.

## Решения

| Аспект | Решение |
|--------|---------|
| Уровень контроля | Полная RBAC: роли + пермишены |
| Авторизация действий | Policy-классы |
| Текущий пользователь | Фасад `Auth::user()` (через `setInstance()` паттерн, как все фасады проекта) |
| Ownership | Встроен в Policy-логику |
| Именование пермишенов | Dot-нотация `resource.action` |
| Guard-система | Абстракция для token (API) и session (web в будущем) |
| Дефолтные роли/пермишены | В `config/auth.php`, синк через сидер |

---

## 1. Схема БД

### Таблица `roles`

| Колонка | Тип | Описание |
|---------|-----|----------|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| name | VARCHAR(50) UNIQUE NOT NULL | Машинное имя: `admin`, `user` |
| description | VARCHAR(255) NULL | Человекочитаемое описание |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### Таблица `permissions`

| Колонка | Тип | Описание |
|---------|-----|----------|
| id | INT UNSIGNED PK AUTO_INCREMENT | |
| name | VARCHAR(100) UNIQUE NOT NULL | Dot-нотация: `users.view`, `users.update` |
| description | VARCHAR(255) NULL | |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### Таблица `role_permissions`

| Колонка | Тип | Описание |
|---------|-----|----------|
| role_id | INT UNSIGNED FK → roles.id ON DELETE CASCADE | |
| permission_id | INT UNSIGNED FK → permissions.id ON DELETE CASCADE | |
| PRIMARY KEY (role_id, permission_id) | | |

### Изменение таблицы `users`

- Добавить колонку `role_id INT UNSIGNED NOT NULL DEFAULT 1` с FK → `roles.id ON DELETE RESTRICT`
- Индекс на `role_id`
- DEFAULT 1 указывает на роль `user`
- Один пользователь = одна роль (не many-to-many)
- `ON DELETE RESTRICT` — нельзя удалить роль, пока есть пользователи с этой ролью

**Зависимость порядка миграций:** миграция `add_role_id_to_users` зависит от `create_roles_table`. Сидер `RolesAndPermissionsSeeder` должен запускаться **между** созданием таблицы `roles` и добавлением `role_id` в `users`, чтобы гарантировать `id = 1` для роли `user`. Альтернатива: сидер вставляет роль `user` с явным `id = 1`.

**Миграция к many-to-many (если понадобится в будущем):** создать `user_roles` pivot-таблицу, мигрировать данные из `users.role_id`, удалить колонку `role_id`. Трейт `HasRole` заменяется на `HasRoles` с поддержкой множественных ролей.

---

## 2. Guard-система (аутентификация)

### GuardInterface (`app/Core/Auth/GuardInterface.php`)

```
user(): ?User        — текущий аутентифицированный пользователь
check(): bool        — аутентифицирован ли
id(): ?int           — ID текущего пользователя
validate(Request): ?User  — провалидировать запрос и вернуть пользователя
```

### TokenGuard (`app/Core/Auth/TokenGuard.php`)

- Реализует `GuardInterface`
- `validate(Request $request)` извлекает токен из `$request->headers['authorization']` (не из `$_SERVER` напрямую — для тестируемости и корректности)
- Далее: парсит Bearer prefix → `Token::findValid()` → загружает `User` по `user_id` из токена
- Кеширует результат в свойстве `$user` — повторные вызовы `user()` не ходят в БД
- Текущая логика из `AuthMiddleware`, вынесенная в отдельный класс

### SessionGuard (будущее, сейчас не реализуем)

- Та же логика через `$_SESSION` / cookie
- Добавится когда понадобятся web-маршруты

### AuthManager (`app/Core/Auth/AuthManager.php`)

- Хранит guard-ы по имени: `'api' => TokenGuard`, `'web' => SessionGuard`
- Дефолтный guard из `config/auth.php` (`'default' => 'api'`)
- Методы: `guard(?string $name = null)`, `user()`, `check()`, `id()`
- `user()` / `check()` / `id()` делегируют дефолтному guard-у

### Фасад Auth (`app/Facades/Auth.php`)

- Следует паттерну `setInstance()`, как все фасады проекта (DB, Cache, Event, Queue, App)
- Приватное статическое свойство `$instance` типа `?AuthManager`
- Публичный `setInstance(AuthManager $manager)` — вызывается из `bootstrap/app.php`
- Публичный `reset()` — для тестов
- Методы: `Auth::user()`, `Auth::check()`, `Auth::id()`, `Auth::guard('api')`

### Изменения в AuthMiddleware

- Вместо прямой работы с токеном — вызывает `Auth::guard('api')->validate($request)`
- При успехе: пользователь доступен через `Auth::user()`
- При неудаче — бросает `AuthenticationException`
- **Примечание:** Request в TinyRouter иммутабельный (readonly), поэтому пользователь **не** кладётся в request. Единственный способ получить текущего пользователя — `Auth::user()`

### Конфиг `config/auth.php` (расширение)

```php
'defaults' => [
    'guard' => 'api',
],
'guards' => [
    'api' => [
        'driver' => 'token',
    ],
    // 'web' => ['driver' => 'session'],  // будущее
],
```

---

## 3. RBAC — модели и проверка прав

### Модель Role (`app/Models/Role.php`)

- Наследует `BaseModel`, таблица `roles`
- `$fillable`: `['name', 'description']`
- Методы:
  - `permissions(): array` — загружает пермишены роли через JOIN `role_permissions` + `permissions`
  - `hasPermission(string $permission): bool` — проверяет наличие пермишена у роли
  - `static findByName(string $name): ?static`
- Кеширование пермишенов в свойстве `$cachedPermissions` в рамках одного HTTP-запроса (без персистентного кеша между запросами — добавить при необходимости через `Cache::remember()`)
- Wildcard `*`: если в `role_permissions` есть пермишен `*`, `hasPermission()` возвращает `true` для любого запроса

### Модель Permission (`app/Models/Permission.php`)

- Наследует `BaseModel`, таблица `permissions`
- `$fillable`: `['name', 'description']`
- `static findByName(string $name): ?static`

### Трейт HasRole (`app/Traits/HasRole.php`)

- Подключается к модели `User`
- Методы:
  - `role(): ?Role` — загружает роль по `role_id` (с кешированием в свойстве)
  - `hasRole(string $roleName): bool`
  - `hasPermission(string $permission): bool` — делегирует `$this->role()->hasPermission()`
  - `isAdmin(): bool` — шорткат для `hasRole('admin')`

### Изменения в User

- `use HasRole`
- `$fillable` добавляется `'role_id'`

### Дефолтные роли и пермишены (`config/auth.php`)

```php
'roles' => [
    'admin' => [
        'description' => 'Администратор',
        'permissions' => ['*'],
    ],
    'user' => [
        'description' => 'Пользователь',
        'permissions' => ['users.view'],
    ],
],
'permissions' => [
    'users.view',
    'users.create',
    'users.update',
    'users.delete',
],
```

### Сидер (`database/seeders/RolesAndPermissionsSeeder.php`)

- Читает конфиг, создаёт/обновляет роли и пермишены в БД
- Роль `user` вставляется с явным `id = 1` (гарантирует соответствие `DEFAULT 1` в `users.role_id`)
- Вызывается через CLI-команду `db:seed`
- Идемпотентный — безопасно запускать повторно

---

## 4. Policy-система

### PolicyInterface (`app/Core/Auth/PolicyInterface.php`)

Маркерный интерфейс — все Policy его реализуют.

### Gate (`app/Core/Auth/Gate.php`)

- Инициализируется в `bootstrap/app.php`, доступен через `App::make(Gate::class)` (собственный фасад не нужен — Gate используется только внутри хелпера `authorize()`)
- Реестр Policy: маппинг модель → Policy-класс
- Методы:
  - `register(string $modelClass, string $policyClass): void` — регистрирует Policy для модели
  - `policy(string $modelClass): PolicyInterface` — резолвит Policy через контейнер
  - `authorize(string $ability, mixed $model): bool` — находит Policy по классу модели, вызывает метод `$ability`
  - `denies(string $ability, mixed $model): bool` — инверсия `authorize`

**Сигнатура `authorize()`:** параметр `$model` принимает:
- **Объект модели** — для действий над конкретным ресурсом (`update`, `delete`, `view`). Policy-метод получает `($authUser, $targetModel)`.
- **Строку (class-string)** — для действий без конкретного ресурса (`create`, `viewAny`). Policy-метод получает только `($authUser)`.

Gate определяет тип по `is_string($model)` vs `is_object($model)`, а класс модели для поиска Policy — по `$model::class` для объектов или по значению строки для class-string.

**Хук `before()`:** Gate **не** имеет автоматического admin-bypass. Каждый Policy-метод явно проверяет пермишены через `$authUser->hasPermission()`. Wildcard `*` обрабатывается на уровне `Role::hasPermission()`. Это осознанное решение: Policy-метод — единственный источник правды для авторизации, никаких скрытых обходов.

### UserPolicy (`app/Policies/UserPolicy.php`)

```
viewAny(User $authUser): bool
  → $authUser->hasPermission('users.view')

view(User $authUser, User $targetUser): bool
  → $authUser->hasPermission('users.view')

create(User $authUser): bool
  → $authUser->hasPermission('users.create')

update(User $authUser, User $targetUser): bool
  → если $authUser->hasPermission('users.update'):
      → admin — может любого
      → остальные — только $authUser->id === $targetUser->id
  → иначе false

delete(User $authUser, User $targetUser): bool
  → если $authUser->hasPermission('users.delete'):
      → admin — может любого, кроме себя ($authUser->id !== $targetUser->id)
      → остальные — false
  → иначе false
```

### Хелпер authorize() (`app/Helpers/helpers.php`)

```
authorize(string $ability, mixed $model): void
  → $user = Auth::user() ?? throw AuthenticationException
  → App::make(Gate::class)->authorize($ability, $model)
  → при отказе бросает AuthorizationException
```

Хелпер проверяет наличие аутентифицированного пользователя перед вызовом Gate. Если `Auth::user()` возвращает `null` (вызов вне auth-protected роута) — бросается `AuthenticationException`.

### Использование в контроллере

```php
// UserController::index()
authorize('viewAny', User::class);

// UserController::update()
authorize('update', $user);  // бросит 403 если нельзя
```

---

## 5. Интеграция с middleware и роутами

### Два уровня авторизации

**Уровень 1 — Middleware `can:` (грубая проверка пермишена):**
- Проверяет только наличие пермишена у пользователя: `Auth::user()->hasPermission($permission)`
- Не резолвит модель, не вызывает Policy
- Параметр — строка пермишена: `can:users.update`
- Быстрый fail на уровне роутинга для пользователей без базового пермишена

**Уровень 2 — `authorize()` в контроллере (тонкая проверка через Policy):**
- Вызывает Policy с учётом ownership, ролей, бизнес-правил
- Работает с конкретным экземпляром модели или class-string
- Обязателен для действий с ownership-логикой (update, delete)

Middleware `can:` — опциональный coarse-grained фильтр. `authorize()` в контроллере — основной механизм авторизации.

### AuthorizationMiddleware (`app/Http/Middleware/AuthorizationMiddleware.php`)

- Работает **после** `AuthMiddleware`
- Принимает строковый параметр пермишена: `can:users.update`
- Проверяет `Auth::user()->hasPermission($permission)`
- При отсутствии пермишена — бросает `AuthorizationException` (403)
- **Не** резолвит модель, **не** вызывает Policy

### Регистрация в `bootstrap/app.php`

```php
$router->addMiddlewareFactory('can', function (string $params): AuthorizationMiddleware {
    return new AuthorizationMiddleware($params);
});
```

Параметр `$params` — строка (как в `rate_limit`), парсится фабрикой.

### Роуты после интеграции

```
GET    /api/v1/users           middleware: auth:api, can:users.view, rate_limit:60,60
GET    /api/v1/users/{id}      middleware: auth:api, can:users.view, rate_limit:60,60
POST   /api/v1/users           middleware: auth:api, can:users.create, rate_limit:60,60
PUT    /api/v1/users/{id}      middleware: auth:api, can:users.update, rate_limit:60,60
DELETE /api/v1/users/{id}      middleware: auth:api, can:users.delete, rate_limit:60,60
```

Контроллеры дополнительно вызывают `authorize()` для ownership-проверок:
- `index()` → `authorize('viewAny', User::class)`
- `show()` → `authorize('view', $user)`
- `store()` → (middleware достаточно, Policy::create проверяет тот же пермишен)
- `update()` → `authorize('update', $user)` (ownership)
- `destroy()` → `authorize('delete', $user)` (admin + not self)

### Порядок middleware в пайплайне

`RateLimit → CORS → Auth (аутентификация) → Can (пермишен) → Controller (Policy)`

---

## 6. Error handling и API-ответы

### Новые исключения

**AuthorizationException** (`app/Exceptions/AuthorizationException.php`):
- Бросается из `authorize()` хелпера и `AuthorizationMiddleware`
- Содержит message (по умолчанию `'Forbidden.'`) и ability name
- Ответ: `ApiResponse::error('Forbidden.', 403)`

**AuthenticationException** (`app/Exceptions/AuthenticationException.php`):
- Заменяет прямой `return ApiResponse::unauthorized()` в `AuthMiddleware`
- Бросается когда guard не может аутентифицировать запрос
- Также бросается из `authorize()` хелпера, если `Auth::user()` возвращает `null`
- Ответ: `ApiResponse::unauthorized('Unauthorized.')` — 401

### Глобальная обработка

Добавляются два catch-блока в `bootstrap/app.php`:

```
AuthenticationException  → 401 {"error": {"message": "Unauthorized."}}
AuthorizationException   → 403 {"error": {"message": "Forbidden."}}
```

### Разделение 401 vs 403

- **401** — не аутентифицирован (нет токена, невалидный/истёкший)
- **403** — аутентифицирован, но нет прав на действие

---

## 7. Тестирование

### Unit-тесты

**`tests/Unit/Core/Auth/TokenGuardTest.php`:**
- Валидный токен в headers → возвращает пользователя
- Невалидный/отсутствующий токен → возвращает null
- Повторный вызов `user()` → возвращает кешированный результат (не повторный SQL)

**`tests/Unit/Core/Auth/AuthManagerTest.php`:**
- Дефолтный guard резолвится из конфига
- `guard('api')` возвращает TokenGuard
- `user()` / `check()` / `id()` делегируют текущему guard-у

**`tests/Unit/Core/Auth/GateTest.php`:**
- `authorize('update', $userInstance)` — вызывает `UserPolicy::update` с двумя аргументами
- `authorize('create', User::class)` — вызывает `UserPolicy::create` с одним аргументом
- Неизвестная модель → исключение
- Неизвестный ability → исключение

**`tests/Unit/Policies/UserPolicyTest.php`:**
- Admin может viewAny/view/create/update/delete любого
- Admin не может delete себя
- User может viewAny/view любого
- User может update только себя
- User не может create/delete

**`tests/Unit/Models/RoleTest.php`:**
- `hasPermission()` — прямое совпадение
- `hasPermission()` — wildcard `*`
- `permissions()` — загрузка через JOIN

### Integration-тесты

**`tests/Feature/AuthorizationTest.php`:**
- Аутентифицированный user → `GET /users` → 200
- Аутентифицированный user → `PUT /users/{own_id}` → 200
- Аутентифицированный user → `PUT /users/{other_id}` → 403
- Аутентифицированный user → `DELETE /users/{any_id}` → 403
- Аутентифицированный admin → `PUT /users/{any_id}` → 200
- Аутентифицированный admin → `DELETE /users/{other_id}` → 200
- Аутентифицированный admin → `DELETE /users/{own_id}` → 403
- Неаутентифицированный → любой защищённый роут → 401
- User без пермишена `users.view` → `GET /users` → 403

---

## 8. Структура файлов

### Новые файлы

```
app/Core/Auth/
├── GuardInterface.php
├── TokenGuard.php
├── AuthManager.php
├── Gate.php
├── PolicyInterface.php

app/Facades/
├── Auth.php

app/Models/
├── Role.php
├── Permission.php

app/Traits/
├── HasRole.php

app/Policies/
├── UserPolicy.php

app/Http/Middleware/
├── AuthorizationMiddleware.php

app/Exceptions/
├── AuthorizationException.php
├── AuthenticationException.php

database/migrations/
├── xxxx_create_roles_table.php
├── xxxx_create_permissions_table.php
├── xxxx_create_role_permissions_table.php
├── xxxx_add_role_id_to_users_table.php

database/seeders/
├── RolesAndPermissionsSeeder.php

tests/Unit/Core/Auth/
├── TokenGuardTest.php
├── AuthManagerTest.php
├── GateTest.php

tests/Unit/Policies/
├── UserPolicyTest.php

tests/Unit/Models/
├── RoleTest.php

tests/Feature/
├── AuthorizationTest.php
```

### Изменяемые файлы

```
app/Models/User.php             — + use HasRole, + role_id в $fillable
app/Http/Middleware/AuthMiddleware.php  — делегирует TokenGuard, бросает AuthenticationException
config/auth.php                 — + defaults, guards, roles, permissions
bootstrap/app.php               — + Auth::setInstance(), Gate регистрация, middleware can:, catch-блоки
app/Helpers/helpers.php         — + authorize()
```
