# Sprint 3 — клиентский трекер посещений

## Что это

`tracker.js` — самодостаточный vanilla‑JS скрипт, который встраивается на любую
страницу одним тегом `<script>` и отправляет на сервер информацию о визите:

* `visitor_id` — UUID v4, хранится в `localStorage`, позволяет считать **уникальные** визиты;
* `url`, `referrer`;
* `device`, `browser`, `os` — определяются на клиенте по `navigator.userAgent`;
* `ip`, `city`, `country` — пробуем получить через бесплатный CORS‑эндпоинт `ipapi.co`,
  если не отвечает или заблокирован браузером — сервер сам догеолоцирует IP через `ip-api.com`.

## Подключение

```html
<script src="https://YOUR_HOST/tracker.js"
        data-endpoint="https://YOUR_HOST/api/track"
        data-auto="true"></script>
```

После подключения скрипт:

1. После события `load` через 50 мс собирает данные и шлёт POST на `data-endpoint`;
2. Использует `fetch(..., { keepalive: true })`, при ошибке падает в `navigator.sendBeacon`;
3. Подвешивает на `window.AmoPointTracker` объект `{ track, buildPayload }` для отладки.

## Почему vanilla, а не библиотека

Альтернативы:

* **Google Analytics / Plausible / Umami** — это полноценные сервисы. Тестовое задание
  специально просит написать клиент-серверную часть самостоятельно.
* **`bowser`/`ua-parser-js`** — отлично определяют User‑Agent, но добавляют 10‑40 КБ к
  размеру скрипта; для нужд этого ТЗ (4 категории устройства + 6 браузеров) хватает
  20 строк регулярок.
* **Тяжёлая телеметрия (Sentry/PostHog)** — нужна авторизация, ключи проекта, разбухает.

Поэтому скрипт сделан без зависимостей и весит несколько килобайт.

## Локальная проверка

```bash
# Откройте demo.html в браузере (или раздайте через любой http‑сервер) ‑‑
# tracker сам отправит POST на http://localhost:8000/api/track.
python3 -m http.server -d tracker 5500
# затем http://localhost:5500/demo.html
```
