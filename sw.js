// sw.js - نسخه کامل و تست شده
const CACHE_NAME = 'golestan-v2';
const ASSETS = [
    '/',
    '/offline.php',
    '/assets/css/style.css',
    '/assets/js/theme.js',
    '/manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap',
];

// نصب - کش کردن فایل‌های استاتیک
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(ASSETS))
            .then(() => self.skipWaiting())
    );
});

// فعال‌سازی - پاک کردن کش قدیمی
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys => 
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// استراتژی: Network First, Cache Fallback
self.addEventListener('fetch', (event) => {
    // فقط GET requests
    if (event.request.method !== 'GET') return;
    
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // کش کردن فقط فایل‌های استاتیک
                if (response.status === 200 && (
                    event.request.url.includes('/assets/') ||
                    event.request.url.includes('fonts.googleapis.com') ||
                    event.request.url.includes('cdnjs.cloudflare.com')
                )) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request)
                    .then(cached => cached || caches.match('/offline.php'));
            })
    );
});