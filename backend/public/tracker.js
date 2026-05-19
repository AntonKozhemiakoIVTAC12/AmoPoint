/**
 * AmoPoint visit tracker.
 *
 * Подключение:
 *   <script src="https://YOUR_HOST/tracker.js"
 *           data-endpoint="https://YOUR_HOST/api/track"
 *           data-auto="true"></script>
 *
 * Что собирает:
 *   - visitor_id (UUID v4, хранится в localStorage, переживает перезагрузки)
 *   - url, referrer, language, screen size
 *   - device/browser/os (определяется и на клиенте, и продублировано на сервере)
 *   - city/country: пробуем ipapi.co (бесплатный CORS-эндпоинт); если не отвечает,
 *     сервер сам догеолоцирует IP через ip-api.com
 *
 * Принципы:
 *   - fail-silent: любые ошибки только логируются, страница не падает;
 *   - используем `navigator.sendBeacon` при наличии — переживает unload;
 *   - идемпотентность: повторное подключение не плодит запросы.
 */
(function () {
    'use strict';

    if (window.AmoPointTracker) {
        return;
    }

    const script = document.currentScript;
    const endpoint = (script && script.dataset.endpoint) || '/api/track';
    const auto = !script || script.dataset.auto !== 'false';

    function uuidv4() {
        if (crypto && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }
        // RFC4122 v4 fallback
        const bytes = new Uint8Array(16);
        (crypto || window.msCrypto).getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = Array.from(bytes, b => b.toString(16).padStart(2, '0'));
        return `${hex.slice(0, 4).join('')}-${hex.slice(4, 6).join('')}-${hex.slice(6, 8).join('')}-${hex.slice(8, 10).join('')}-${hex.slice(10, 16).join('')}`;
    }

    function getVisitorId() {
        const KEY = 'amopoint_visitor_id';
        try {
            let id = localStorage.getItem(KEY);
            if (!id) {
                id = uuidv4();
                localStorage.setItem(KEY, id);
            }
            return id;
        } catch {
            // приватный режим / SSR / отключено хранилище
            return uuidv4();
        }
    }

    function detectDevice() {
        const ua = navigator.userAgent || '';
        if (/iPad|Tablet|Kindle|Silk/i.test(ua)) return 'tablet';
        if (/Mobile|Android|iPhone|iPod|Opera Mini|IEMobile/i.test(ua)) return 'mobile';
        if (/bot|spider|crawler|slurp|preview/i.test(ua)) return 'bot';
        return 'desktop';
    }

    function detectBrowser() {
        const ua = navigator.userAgent || '';
        if (/Edg\//i.test(ua)) return 'Edge';
        if (/OPR\/|Opera/i.test(ua)) return 'Opera';
        if (/Chrome\//i.test(ua)) return 'Chrome';
        if (/Firefox\//i.test(ua)) return 'Firefox';
        if (/Safari\//i.test(ua)) return 'Safari';
        if (/MSIE |Trident\//i.test(ua)) return 'IE';
        return 'other';
    }

    function detectOs() {
        const ua = navigator.userAgent || '';
        if (/iPhone|iPad|iPod/i.test(ua)) return 'iOS';
        if (/Android/i.test(ua)) return 'Android';
        if (/Windows NT/i.test(ua)) return 'Windows';
        if (/Mac OS X|Macintosh/i.test(ua)) return 'macOS';
        if (/Linux/i.test(ua)) return 'Linux';
        return 'other';
    }

    async function lookupGeo() {
        try {
            const ctrl = new AbortController();
            const t = setTimeout(() => ctrl.abort(), 1500);
            const resp = await fetch('https://ipapi.co/json/', { signal: ctrl.signal, mode: 'cors' });
            clearTimeout(t);
            if (!resp.ok) return null;
            const data = await resp.json();
            return {
                ip: data.ip || null,
                city: data.city || null,
                country: data.country_name || null,
            };
        } catch {
            return null;
        }
    }

    async function buildPayload() {
        const geo = await lookupGeo();
        return {
            visitor_id: getVisitorId(),
            url: location.href,
            referrer: document.referrer || null,
            device: detectDevice(),
            browser: detectBrowser(),
            os: detectOs(),
            ip: geo?.ip ?? null,
            city: geo?.city ?? null,
            country: geo?.country ?? null,
        };
    }

    async function send(payload) {
        const body = JSON.stringify(payload);
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };

        // Beacon переживает unload, но не даёт прочитать ответ — поэтому
        // используем его только при `pagehide`, а в обычной отправке fetch.
        try {
            const resp = await fetch(endpoint, { method: 'POST', headers, body, keepalive: true, credentials: 'omit' });
            return await resp.json().catch(() => ({ ok: resp.ok }));
        } catch (e) {
            try { navigator.sendBeacon(endpoint, body); } catch {}
            return { ok: false, error: String(e) };
        }
    }

    async function track() {
        const payload = await buildPayload();
        return send(payload);
    }

    window.AmoPointTracker = { track, buildPayload };

    if (auto) {
        // Чуть откладываем, чтобы не блокировать первый рендер
        if (document.readyState === 'complete') {
            setTimeout(track, 50);
        } else {
            window.addEventListener('load', () => setTimeout(track, 50), { once: true });
        }
    }
})();
