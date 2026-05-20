# AmoPoint — тестовое задание

Монорепозиторий с тремя независимыми артефактами:

| Спринт | Каталог       | Что внутри                                                                 |
|--------|---------------|----------------------------------------------------------------------------|
| **1**  | `backend/`    | Laravel 11: команда `jokes:fetch` каждые 5 минут + `GET /api/jokes` (JSON:API через `jsonPaginate`) |
| **2**  | `snippet/`    | Vanilla‑JS фильтр полей формы по выбранному типу + локальная демка        |
| **3**  | `tracker/` + `backend/` | Клиентский трекер посещений + Laravel‑бэк (`/api/track`, `/dashboard` с авторизацией и графиками) |

---

## 0. Зависимости

* Docker 24+ и Docker Compose v2 (рекомендуется).
* Либо локально: PHP 8.3 с расширениями `pdo_sqlite`, `mbstring`, `intl`, `gd`, и Composer 2.x.
* Браузер для проверки JS‑сниппета (Sprint 2) и дашборда (Sprint 3).

---

## 1. Запуск Laravel (Sprints 1 + 3)

### 1.1. Через Docker (рекомендуется)

```bash
# Из корня репозитория
docker compose up --build
```

Контейнер `app` поднимет HTTP‑сервер на <http://localhost:8000>,
выполнит `composer install`, создаст `.env`, сгенерирует ключ, накатит миграции
и засидит админа. Контейнер `scheduler` будет запускать `php artisan schedule:work`.

Создать админа (если ещё не создан):

```bash
docker compose exec app php artisan db:seed --force
```

### 1.2. Локально (если есть PHP 8.3 + pdo_sqlite)

```bash
cd backend
cp .env.example .env
composer install
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve
# в другом терминале:
php artisan schedule:work
```

### 1.3. Доступ

* Дашборд: <http://localhost:8000/dashboard>
* Логин:   `admin@amopoint.local` / `secret123`
* Демка трекера: <http://localhost:8000/tracker-demo>

---

## 2. Sprint 1 — Jokes API

### Что сделано

* `App\Console\Commands\FetchJokeCommand` (сигнатура **`jokes:fetch`**) —
  тонкий адаптер CLI → сервис.
* `App\Services\Jokes\JokeFetcher` — оборачивает HTTP‑клиент и `updateOrCreate`,
  идемпотентен (повторный фетч одного и того же external_id не плодит дубли).
* `App\Services\Jokes\JokeApiClient` — изолирует обращение к
  <https://official-joke-api.appspot.com/random_joke>, легко мокается через
  `Http::fake()`.
* `App\Http\Controllers\JokeController` — одна строчка
  `Joke::query()->orderByDesc('id')->jsonPaginate()`, отдаётся через
  `JokeResource::collection(...)`.
* Расписание зарегистрировано в `routes/console.php`:
  ```php
  Schedule::command(FetchJokeCommand::class)->everyFiveMinutes()->withoutOverlapping()->runInBackground();
  ```

### Как проверить

```bash
# Ручной запуск команды
docker compose exec app php artisan jokes:fetch
# Ожидается: "Stored joke #N (external_id=...)."

# Проверка расписания
docker compose exec app php artisan schedule:list
# Ожидается строка: "*/5 * * * *  php artisan jokes:fetch ..."

# Получить список анекдотов (формат JSON:API + jsonPaginate)
curl 'http://localhost:8000/api/jokes?page[size]=5&page[number]=1' | jq
```

Ответ имеет вид:

```json
{
  "data":  [{ "id":1, "external_id":204, "type":"general", "setup":"...", "punchline":"...", ... }],
  "links": { "first":"...", "last":"...", "prev":null, "next":null },
  "meta":  { "current_page":1, "per_page":5, "total":N, ... }
}
```

---

## 3. Sprint 2 — JS‑фильтр testlist.html

Каталог `snippet/`. Подробное описание алгоритма и альтернатив — в
[`snippet/README.md`](snippet/README.md).

### Файлы

* `snippet/testlist-filter.js` — самодостаточный IIFE, подключается одним
  тегом `<script src="testlist-filter.js"></script>` в конец страницы.
* `snippet/testlist-filter.snippet.js` — тот же код одной строкой,
  готов к копированию в DevTools → Console.
* `snippet/testlist.html` — локальная демка, повторяющая структуру
  `http://test.amopoint-dev.ru/testzz/testlist.html`.

### Алгоритм (кратко)

1. Найти первый `<select>`, у которого `name` соответствует `/тип|type/i`.
2. На `change`: пройти все элементы с атрибутом `name` в той же форме
   и показать только те, где `name.includes(value)`. Сложность O(N).
3. `MutationObserver` пересчитывает фильтр при динамическом добавлении полей.
4. Идемпотентность: повторный запуск не плодит подписки (`window.__amopointTestlistFilter`).

### Как проверить

**Локально:**

```bash
# Любым статическим сервером
python3 -m http.server -d snippet 5500
# затем http://localhost:5500/testlist.html — меняйте «Тип» и смотрите, как фильтруются поля
```

**На живой странице:**

1. Откройте <http://test.amopoint-dev.ru/testzz/testlist.html>.
2. DevTools → Console.
3. Скопируйте содержимое `snippet/testlist-filter.snippet.js`, вставьте, Enter.

---

## 4. Sprint 3 — счётчик посещений + дашборд

### Backend

Реализация поделена на сервисы (контроллеры тонкие):

* `App\Services\Visits\VisitData` — иммутабельный DTO.
* `App\Services\Visits\VisitRecorder` — пишет визит, дозаполняет
  city/country через `App\Support\GeoIp`, парсит UA в `device/browser/os`.
* `App\Services\Visits\VisitStatsBuilder` — собирает агрегаты:
  почасовые уникальные за 24 ч, топ городов, распределение по устройствам,
  сводные KPI.
* `App\Http\Requests\StoreVisitRequest` — валидация + конвертация в DTO.
* `App\Http\Controllers\VisitController` — три метода:
  `store` (POST `/api/track`), `index` (GET `/api/visits` с
  `jsonPaginate()`), `stats` (GET `/dashboard/stats.json`).
* `App\Http\Controllers\DashboardController` — отдаёт Blade с готовыми
  агрегатами; Chart.js рисует bar + pie + horizontal bar.

Авторизация — ручная (без Breeze), через стандартный Laravel Auth:
`LoginController::show/store/destroy`, middleware `auth`, единственный
сидер `AdminUserSeeder` (`admin@amopoint.local` / `secret123`).

### Frontend — `tracker/tracker.js`

Vanilla‑скрипт без зависимостей (5 КБ). Что делает:

1. Берёт/создаёт `visitor_id` (UUID v4) в `localStorage`.
2. Определяет `device/browser/os` по `navigator.userAgent`.
3. Пробует получить `ip/city/country` через `https://ipapi.co/json/`.
   Если CORS‑запрос не прошёл — не страшно: сервер сам догеолоцирует IP
   через `ip-api.com`.
4. Шлёт POST `/api/track` с `fetch({ keepalive: true })`,
   фоллбэк на `navigator.sendBeacon`.
5. `fail-silent`: любые ошибки только в `console.warn`.

### Графики

* **`#chart-hours`** (bar): ось X = час суток (24 столбца), ось Y = число
  уникальных посетителей в этот час.
* **`#chart-cities`** (pie): доли городов.
* **`#chart-devices`** (horizontal bar): распределение по устройствам.

### Как проверить

```bash
# 1. Запустить стек (если ещё не запущен)
docker compose up -d

# 2. Симулировать визиты
for i in 1 2 3 4 5; do
  curl -sS -X POST http://localhost:8000/api/track \
    -H "Content-Type: application/json" \
    -H "User-Agent: Mozilla/5.0 (Macintosh) Chrome/120" \
    -d "{\"visitor_id\":\"u-$i\",\"url\":\"http://demo/page$i\",\"city\":\"Saint Petersburg\",\"country\":\"Russia\",\"device\":\"desktop\"}"; echo
done

# 3. Открыть http://localhost:8000/login,
#    войти как admin@amopoint.local / secret123,
#    оказаться на /dashboard и увидеть графики.

# 4. JSON-сырьё для дебага (нужна сессия — открыть в браузере после логина):
#    http://localhost:8000/dashboard/stats.json
```

Подключение трекера на сторонний сайт:

```html
<script src="https://YOUR_HOST/tracker.js"
        data-endpoint="https://YOUR_HOST/api/track"
        data-auto="true"></script>
```

---

## 5. Структура каталогов

```
.
├── README.md                       # этот файл
├── docker-compose.yml
├── docker/Dockerfile               # PHP 8.3 + pdo_sqlite + composer
├── backend/                        # Laravel 11 (Sprints 1 + 3)
│   ├── app/
│   │   ├── Console/Commands/FetchJokeCommand.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Auth/LoginController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── JokeController.php
│   │   │   │   └── VisitController.php
│   │   │   ├── Requests/StoreVisitRequest.php
│   │   │   └── Resources/{JokeResource,VisitResource}.php
│   │   ├── Models/{Joke,Visit,User}.php
│   │   ├── Providers/AppServiceProvider.php
│   │   ├── Services/
│   │   │   ├── Jokes/{JokeApiClient,JokeFetcher}.php
│   │   │   └── Visits/{VisitData,VisitRecorder,VisitStatsBuilder}.php
│   │   └── Support/{GeoIp,UserAgentParser}.php
│   ├── config/{json-api-paginate.php, services.php (блок jokes)}
│   ├── database/{migrations, seeders/AdminUserSeeder.php}
│   ├── public/tracker.js           # копия tracker/tracker.js для встраивания
│   ├── resources/views/{layouts/app.blade.php, dashboard.blade.php,
│   │                    auth/login.blade.php, tracker-demo.blade.php}
│   └── routes/{api.php, web.php, console.php}
├── snippet/                        # Sprint 2
│   ├── testlist.html
│   ├── testlist-filter.js
│   ├── testlist-filter.snippet.js
│   └── README.md
└── tracker/                        # Sprint 3 client
    ├── tracker.js
    ├── demo.html
    └── README.md
```
