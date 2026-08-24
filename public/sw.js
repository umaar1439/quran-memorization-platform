/**
 * Service Worker — تحفيظ (Phase 12: PWA والمزامنة Offline).
 *
 * استراتيجية Offline-first:
 *   - Cache First للـ static assets (JS/CSS/الصور/الخطوط/manifest/icons):
 *     يُقرأ من الكاش أولاً، وفي الخلفية يُحدَّث الكاش من الشبكة (stale-while-revalidate خفيف).
 *   - Network First لطلبات API (أي مسار يبدأ بأنماط API معروفة أو Accept: application/json):
 *     يُحاول الشبكة أولاً؛ عند الفشل (offline) يرجع لنسخة مخزَّنة إن وُجدت، وإلا استجابة offline موحّدة.
 *
 * Cache Versioning: تغيير CACHE_VERSION يُبطل كل الكاشات القديمة تلقائياً
 * عبر activate (§Phase 12 نقطة 2).
 */

const CACHE_VERSION = 'tahfeez-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const API_CACHE = `${CACHE_VERSION}-api`;

const STATIC_ASSET_EXTENSIONS = [
    '.js', '.css', '.woff', '.woff2', '.ttf', '.eot',
    '.png', '.jpg', '.jpeg', '.svg', '.webp', '.ico',
];

const PRECACHE_URLS = [
    '/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => key.startsWith('tahfeez-') && key !== STATIC_CACHE && key !== API_CACHE)
                .map((key) => caches.delete(key)),
        )),
    );
    self.clients.claim();
});

function isStaticAsset(url) {
    return STATIC_ASSET_EXTENSIONS.some((ext) => url.pathname.endsWith(ext))
        || url.pathname.startsWith('/build/');
}

function isApiRequest(request, url) {
    return url.pathname.startsWith('/api/')
        || request.headers.get('accept')?.includes('application/json');
}

/**
 * Cache First: يرجع من الكاش فوراً إن وُجد، ويُحدِّث الكاش في الخلفية من الشبكة.
 */
async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    const networkFetch = fetch(request)
        .then((response) => {
            if (response && response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    return cached || networkFetch || Response.error();
}

/**
 * Network First: يحاول الشبكة أولاً (بيانات API طازجة)، وعند الفشل
 * (Offline) يرجع لآخر نسخة مخزَّنة، وإلا استجابة JSON موحّدة تدل على offline.
 */
async function networkFirst(request) {
    const cache = await caches.open(API_CACHE);

    try {
        const response = await fetch(request);

        if (response && response.ok && request.method === 'GET') {
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        if (request.method === 'GET') {
            const cached = await cache.match(request);

            if (cached) {
                return cached;
            }
        }

        return new Response(
            JSON.stringify({ error: 'offline', message: 'لا يوجد اتصال بالإنترنت حالياً.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } },
        );
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (isApiRequest(request, url)) {
        event.respondWith(networkFirst(request));
    }
});
