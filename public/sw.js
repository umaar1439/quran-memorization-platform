/**
 * Service Worker — تحفيظ (PWA / offline).
 *
 * قرار التخزين (privacy-first): لا نخزّن أي HTML أو JSON مصادَق عليه.
 * استجابات Laravel تحمل بيانات المستخدم داخل data-page، وكاشٌ دائم على جهاز
 * مشترك = تسريب بيانات. لذلك:
 *   - navigation  → الشبكة أولاً، وعند الفشل صفحة offline ثابتة مُخزَّنة مسبقاً.
 *   - static      → Cache First + إعادة تحقق في الخلفية (أصول hashed آمنة).
 *   - api/inertia → الشبكة فقط، مع إشارة offline موحّدة لطلبات JSON.
 * الكتابة بلا اتصال تبقى مسؤولية OfflineQueue/SyncService (IndexedDB).
 */

const CACHE_VERSION = 'tahfeez-v2';
const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const STATIC_CACHE = `${CACHE_VERSION}-static`;

const OFFLINE_URL = '/offline.html';
const SHELL_URLS = [OFFLINE_URL, '/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png'];

const STATIC_EXTENSIONS = [
    '.js', '.css', '.woff', '.woff2', '.ttf', '.eot',
    '.png', '.jpg', '.jpeg', '.svg', '.webp', '.ico', '.json',
];

/** سقف عدد المدخلات: يمنع تضخّم التخزين على الأجهزة الضعيفة. */
const STATIC_CACHE_LIMIT = 80;

/** بديل مضمّن عند غياب /offline.html من الكاش. */
const OFFLINE_FALLBACK_HTML = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8">'
    + '<meta name="viewport" content="width=device-width,initial-scale=1"><title>لا يوجد اتصال</title></head>'
    + '<body style="font-family:system-ui,Tahoma,sans-serif;padding:24px;text-align:center;line-height:1.75">'
    + '<h1 style="font-size:1.125rem">لا يوجد اتصال بالإنترنت</h1>'
    + '<p>تحقّق من الشبكة ثم أعد المحاولة.</p></body></html>';

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);

        // addAll يرفض الدفعة كلها إذا فشل ملف واحد، لذلك نضيف كل ملف على حدة.
        await Promise.all(SHELL_URLS.map(async (url) => {
            try {
                await cache.add(new Request(url, { cache: 'reload' }));
            } catch (error) {
                // ملف واحد مفقود لا يجب أن يُفشل تثبيت الـ SW بالكامل.
                console.warn('[sw] تعذّر تخزين', url, error);
            }
        }));
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        const stale = keys.filter((key) => key.startsWith('tahfeez-') && !key.startsWith(CACHE_VERSION));

        await Promise.all(stale.map((key) => caches.delete(key)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    // التحديث لا يُطبَّق إلا بطلب صريح من الواجهة (PwaUpdateBanner).
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

function isStaticAsset(url) {
    return STATIC_EXTENSIONS.some((ext) => url.pathname.endsWith(ext))
        || url.pathname.startsWith('/build/');
}

function isApiRequest(request, url) {
    return url.pathname.startsWith('/api/')
        || request.headers.get('accept')?.includes('application/json')
        || request.headers.get('X-Inertia') === 'true';
}

async function trimCache(cache, maxEntries) {
    const keys = await cache.keys();

    if (keys.length <= maxEntries) {
        return;
    }

    // Cache API يحفظ ترتيب الإضافة: نحذف الأقدم أولاً.
    await Promise.all(keys.slice(0, keys.length - maxEntries).map((key) => cache.delete(key)));
}

/** إعادة التحقق في الخلفية: تحديث الكاش دون تأخير الاستجابة (أفضل جهد). */
async function revalidate(request, cache) {
    try {
        const response = await fetch(request);

        if (response.ok && response.type === 'basic') {
            await cache.put(request, response.clone());
        }
    } catch {
        // الشبكة غير متاحة — النسخة المخزَّنة هي المطلوب.
    }
}

async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);

    try {
        const cached = await cache.match(request);

        if (cached) {
            // لا نستخدم event.waitUntil هنا: إعادة التحقق "أفضل جهد" ولا يجوز
            // تعليق عمر الحدث بعد أن تُسلَّم الاستجابة.
            revalidate(request, cache);

            return cached;
        }

        const response = await fetch(request);

        if (response.ok && response.type === 'basic') {
            await cache.put(request, response.clone());
            await trimCache(cache, STATIC_CACHE_LIMIT);
        }

        return response;
    } catch {
        // أول زيارة + بلا اتصال: لا توجد نسخة مخزَّنة بديلة.
        return new Response('', { status: 504, statusText: 'Offline' });
    }
}

async function handleNavigation(request) {
    try {
        return await fetch(request);
    } catch {
        const cache = await caches.open(SHELL_CACHE);
        const offlinePage = await cache.match(OFFLINE_URL);

        if (offlinePage) {
            return offlinePage;
        }

        // الصفحة المخزَّنة مسبقاً غير متاحة (تثبيت أول فشل جزئياً):
        // نرجع صفحة مصغّرة مضمّنة بدل شاشة خطأ فارغة من المتصفح.
        return new Response(OFFLINE_FALLBACK_HTML, {
            status: 200,
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        });
    }
}

async function networkOnly(request) {
    try {
        return await fetch(request);
    } catch {
        return new Response(
            JSON.stringify({ error: 'offline', message: 'لا يوجد اتصال بالإنترنت حالياً.' }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' },
            },
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

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(request));

        return;
    }

    // طلبات API/Inertia أولاً: لا تُخزَّن أبداً (بيانات مستخدم مصادَق عليه).
    if (isApiRequest(request, url)) {
        event.respondWith(networkOnly(request));

        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
    }
});
