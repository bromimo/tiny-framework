# Event System — Design Spec

## Цель

Добавить систему событий (event dispatching) для аудит-лога и расширяемости. Слушатели выполняются синхронно; контракт `ShouldQueue` подготовлен для будущей async-очереди.

## Архитектура

Минимальный EventDispatcher: один класс-реестр, POPO-события, callable/class-string слушатели, статический фасад `Event`. Регистрация в `EventServiceProvider`. Dispatch из Actions.

## Компоненты

### 1. EventDispatcher (`app/Core/EventDispatcher.php`)

Реестр слушателей + dispatch. Хранит массив `[eventClass => [callable|string, ...]]`.

**API:**

```php
class EventDispatcher
{
    /** Зарегистрировать слушателя на тип события.
     * @param string          $eventClass FQCN события.
     * @param callable|string $listener   Callable или class-string с __invoke.
     */
    public function listen(string $eventClass, callable|string $listener): void;

    /** Отправить событие всем зарегистрированным слушателям.
     * @param object $event Объект события.
     * @return object Тот же объект (позволяет listeners модифицировать).
     */
    public function dispatch(object $event): object;

    /** Проверить наличие слушателей для события.
     * @param string $eventClass FQCN события.
     */
    public function hasListeners(string $eventClass): bool;

    /** Очистить слушателей. Без аргумента — все, с аргументом — для конкретного события.
     * @param string|null $eventClass
     */
    public function flush(?string $eventClass = null): void;
}
```

**Правила dispatch:**
- Слушатели вызываются в порядке регистрации (FIFO).
- Если `$listener` — строка (class-string), создаётся `new $listener` и вызывается `->__invoke($event)`.
- Если `$listener` — callable, вызывается напрямую с `$event`.
- Если listener реализует `ShouldQueue`, пока выполняется синхронно (fallback). Логирует `[queued:sync]`.
- `dispatch()` возвращает объект события.
- Если слушателей нет — ничего не происходит, ошибок нет.

### 2. ShouldQueue (`app/Contracts/ShouldQueue.php`)

Маркер-интерфейс. Пустой. Listener, реализующий его, помечается как «должен выполняться в очереди». До реализации Queue — синхронный fallback.

```php
interface ShouldQueue {}
```

### 3. Event Facade (`app/Facades/Event.php`)

Статический фасад по паттерну проекта. Хранит `EventDispatcher` в static property. Метод `swap()` для подмены в bootstrap/тестах.

```php
class Event
{
    private static ?EventDispatcher $instance = null;

    public static function swap(EventDispatcher $dispatcher): void;
    public static function listen(string $eventClass, callable|string $listener): void;
    public static function dispatch(object $event): object;
    public static function hasListeners(string $eventClass): bool;
    public static function flush(?string $eventClass = null): void;
}
```

Если `$instance === null` — создаёт новый `EventDispatcher` (как Cache fallback на ArrayDriver).

### 4. События (`app/Events/`)

Каждое событие — `readonly class` с публичными свойствами. Без базового класса, без интерфейса.

| Класс | Свойства | Когда диспатчится |
|-------|----------|-------------------|
| `UserCreated` | `int $userId, string $email` | `CreateUserAction::run()` после `User::create()` |
| `UserUpdated` | `int $userId, array $changedFields` | `UpdateUserAction::run()` после `User::update()` |
| `UserDeleted` | `int $userId` | `DeleteUserAction::run()` после `User::deleteById()` |
| `LoginSucceeded` | `int $userId, string $ip` | `LoginAction::run()` при успешном логине |
| `LoginFailed` | `string $email, string $ip` | `LoginAction::run()` при неудачном логине |

**Принципы:**
- Минимум данных — ID и поля, не модель целиком.
- `readonly` — immutable после создания.
- Без timestamp — слушатель использует `time()` при обработке.

### 5. Слушатели (`app/Listeners/`)

Класс с `__invoke(EventClass $event): void`. Один listener — одно действие.

**LogAuthEvent** — логирует auth-события через `Logger::info()`:
- `LoginSucceeded` → `"Login succeeded for user {userId} from {ip}"`
- `LoginFailed` → `"Login failed for email {email} from {ip}"`

Принимает union type `LoginSucceeded|LoginFailed`.

**LogUserChange** — логирует CRUD-события через `Logger::info()`:
- `UserCreated` → `"User created: {userId} ({email})"`
- `UserUpdated` → `"User updated: {userId}, fields: {changedFields}"`
- `UserDeleted` → `"User deleted: {userId}"`

Принимает union type `UserCreated|UserUpdated|UserDeleted`.

### 6. EventServiceProvider (`app/Providers/EventServiceProvider.php`)

Централизованная регистрация слушателей. Один static метод `register(EventDispatcher)`.

```php
class EventServiceProvider
{
    public static function register(EventDispatcher $dispatcher): void
    {
        $dispatcher->listen(LoginSucceeded::class, LogAuthEvent::class);
        $dispatcher->listen(LoginFailed::class, LogAuthEvent::class);
        $dispatcher->listen(UserCreated::class, LogUserChange::class);
        $dispatcher->listen(UserUpdated::class, LogUserChange::class);
        $dispatcher->listen(UserDeleted::class, LogUserChange::class);
    }
}
```

### 7. Интеграция в bootstrap (`bootstrap/app.php`)

После `Config::load()`, до загрузки маршрутов:

```php
$dispatcher = new \App\Core\EventDispatcher();
\App\Facades\Event::swap($dispatcher);
\App\Providers\EventServiceProvider::register($dispatcher);
```

### 8. Интеграция в Actions

**LoginAction::run()** — dispatch после успешного/неудачного логина:
```php
if (!$passwordValid) {
    Event::dispatch(new LoginFailed($dto->email, $_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    return ApiResponse::error('Invalid credentials.', 401);
}
$token = Token::create($user->id);
Event::dispatch(new LoginSucceeded($user->id, $_SERVER['REMOTE_ADDR'] ?? 'unknown'));
```

**CreateUserAction::run()** — после создания:
```php
$user = User::create($dto);
Event::dispatch(new UserCreated($user->id, $dto->email));
return $user;
```

**UpdateUserAction::run()** — после обновления:
```php
$updated = User::update($user->id, $dto);
Event::dispatch(new UserUpdated($user->id, array_keys($dto->toArray())));
return $updated;
```

**DeleteUserAction::run()** — после удаления:
```php
User::deleteById($user->id);
Event::dispatch(new UserDeleted($user->id));
```

## Тестирование

### Unit: EventDispatcherTest (`tests/Unit/Core/EventDispatcherTest.php`)

- `test_dispatch_calls_registered_callable_listener` — callable вызывается с объектом события
- `test_dispatch_calls_class_string_listener_via_invoke` — class-string создаётся, вызывается `__invoke`
- `test_dispatch_calls_multiple_listeners_in_registration_order` — порядок FIFO
- `test_dispatch_without_listeners_returns_event` — нет ошибок
- `test_has_listeners_returns_true_when_registered`
- `test_has_listeners_returns_false_when_empty`
- `test_flush_clears_all_listeners`
- `test_flush_clears_specific_event_only`

### Unit: LogAuthEventTest (`tests/Unit/Listeners/LogAuthEventTest.php`)

- `test_handles_login_succeeded` — проверяет что Logger::info() вызван с правильным сообщением
- `test_handles_login_failed` — аналогично

### Unit: LogUserChangeTest (`tests/Unit/Listeners/LogUserChangeTest.php`)

- `test_handles_user_created`
- `test_handles_user_updated`
- `test_handles_user_deleted`

**Примечание по тестам listeners:** Logger — статический класс, прямой мок невозможен. Подход: вызвать listener, проверить что файл лога содержит ожидаемую строку (integration-style). Или использовать output buffer / temp log path через config override в тестах.

### Feature тесты

Существующие feature-тесты Actions/Controllers не меняются — события не влияют на HTTP-ответ. Достаточно добавить `Event::flush()` в `FeatureTestCase::setUp()` если нужно предотвратить побочные эффекты listeners в feature-тестах.

## Файловая структура

```
app/Core/EventDispatcher.php              — ядро
app/Contracts/ShouldQueue.php             — маркер-интерфейс
app/Facades/Event.php                     — фасад
app/Events/UserCreated.php                — событие
app/Events/UserUpdated.php                — событие
app/Events/UserDeleted.php                — событие
app/Events/LoginSucceeded.php             — событие
app/Events/LoginFailed.php                — событие
app/Listeners/LogAuthEvent.php            — аудит auth
app/Listeners/LogUserChange.php           — аудит users
app/Providers/EventServiceProvider.php    — регистрация
bootstrap/app.php                         — инициализация (modify)
app/Actions/Auth/LoginAction.php          — dispatch login events (modify)
app/Actions/User/CreateUserAction.php     — dispatch UserCreated (modify)
app/Actions/User/UpdateUserAction.php     — dispatch UserUpdated (modify)
app/Actions/User/DeleteUserAction.php     — dispatch UserDeleted (modify)
tests/Unit/Core/EventDispatcherTest.php   — тесты dispatcher
tests/Unit/Listeners/LogAuthEventTest.php — тесты auth listener
tests/Unit/Listeners/LogUserChangeTest.php — тесты user listener
```
