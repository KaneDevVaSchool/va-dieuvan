/**
 * Service Worker — PWA: tĩnh cache-first, API GET network-first + cache phụ, điều hướng offline.
 * Bump CACHE_* khi đổi chiến lược hoặc cần xóa cache cũ.
 */
const CACHE_STATIC = 'va-dispatch-static-v7'
const CACHE_API = 'va-dispatch-api-v1'

const IDB_NAME = 'va-dispatch'
const IDB_VER = 1
const OUTBOX_STORE = 'outbox'

const PRECACHE_URLS = [
  '/',
  '/offline.html',
  '/manifest.webmanifest',
  '/login',
  '/images/logo/logo_pwa_v1.png',
  '/images/logo/logo.png',
  '/images/logo/google.png',
]

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then(async (cache) => {
      await Promise.all(PRECACHE_URLS.map((url) => cache.add(url).catch(() => {})))
      await self.skipWaiting()
    }),
  )
})

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((k) => k !== CACHE_STATIC && k !== CACHE_API)
            .map((k) => caches.delete(k)),
        ),
      )
      .then(() => self.clients.claim()),
  )
})

self.addEventListener('push', (event) => {
  let title = 'Thông báo'
  let body = ''
  let targetUrl = '/'
  let tag = 'va-push'
  if (event.data) {
    try {
      const j = event.data.json()
      if (j && typeof j === 'object') {
        if (j.title) title = String(j.title)
        if (j.body) body = String(j.body)
        if (j.url) targetUrl = String(j.url)
        if (j.tag) tag = String(j.tag)
      }
    } catch {
      const t = event.data.text()
      if (t) body = t
    }
  }
  const options = {
    body,
    icon: '/images/logo/logo_pwa_v1.png',
    badge: '/images/logo/logo_pwa_v1.png',
    tag,
    data: { url: targetUrl },
    vibrate: [120, 80, 120],
  }
  event.waitUntil(self.registration.showNotification(title, options))
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const d = event.notification.data || {}
  const u = typeof d === 'string' ? d : d?.url
  const path = u && String(u).trim() ? String(u) : '/'
  const target = new URL(path, self.location.origin).href
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const c of clientList) {
        if (!c.url) continue
        if (new URL(c.url).origin !== self.location.origin) continue
        if (typeof c.navigate === 'function') {
          return c.navigate(target).then(() => c.focus())
        }
        if ('focus' in c) {
          return c.focus()
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(target)
      }
    }),
  )
})

self.addEventListener('sync', (event) => {
  if (event.tag === 'va-outbox-sync') {
    event.waitUntil(flushOutboxFromSw())
  }
})

self.addEventListener('fetch', (event) => {
  const req = event.request
  if (req.method !== 'GET') return

  const url = new URL(req.url)
  if (url.origin !== self.location.origin) return

  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstApi(CACHE_API, req))
    return
  }

  if (req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(networkFirstNavigation(req))
    return
  }

  if (url.pathname.startsWith('/build/assets/')) {
    event.respondWith(cacheFirst(CACHE_STATIC, req))
    return
  }

  if (
    url.pathname.startsWith('/images/') ||
    PRECACHE_URLS.some((p) => url.pathname === p || url.pathname.startsWith(`${p}?`))
  ) {
    event.respondWith(cacheFirst(CACHE_STATIC, req))
  }
})

async function networkFirstNavigation(req) {
  const cache = await caches.open(CACHE_STATIC)
  try {
    const res = await fetch(req)
    if (res.ok) {
      cache.put(req, res.clone()).catch(() => {})
    }
    return res
  } catch {
    const offline = await caches.match('/offline.html')
    if (offline) return offline
    const cached = await cache.match(req)
    if (cached) return cached
    return new Response('Offline', { status: 503, statusText: 'Offline' })
  }
}

async function networkFirstApi(cacheName, req) {
  const cache = await caches.open(cacheName)
  try {
    const res = await fetch(req)
    if (res.ok) {
      cache.put(req, res.clone()).catch(() => {})
    }
    return res
  } catch {
    const hit = await cache.match(req)
    if (hit) return hit
    return new Response(
      JSON.stringify({
        message: 'Không có mạng. Thử lại sau hoặc kiểm tra kết nối.',
        offline: true,
      }),
      { status: 503, headers: { 'Content-Type': 'application/json' } },
    )
  }
}

async function cacheFirst(cacheName, req) {
  const cache = await caches.open(cacheName)
  const hit = await cache.match(req)
  if (hit) return hit
  try {
    const res = await fetch(req)
    if (res.ok) await cache.put(req, res.clone())
    return res
  } catch {
    return hit || new Response('Offline', { status: 503, statusText: 'Offline' })
  }
}

function openIdb() {
  return new Promise((resolve, reject) => {
    const r = indexedDB.open(IDB_NAME, IDB_VER)
    r.onerror = () => reject(r.error)
    r.onsuccess = () => resolve(r.result)
    r.onupgradeneeded = () => {
      /* store tạo từ app chính */
    }
  })
}

function idbGetAll(db) {
  return new Promise((resolve, reject) => {
    const tx = db.transaction(OUTBOX_STORE, 'readonly')
    const q = tx.objectStore(OUTBOX_STORE).getAll()
    q.onsuccess = () => resolve(q.result || [])
    q.onerror = () => reject(q.error)
  })
}

function idbDelete(db, id) {
  return new Promise((resolve, reject) => {
    const tx = db.transaction(OUTBOX_STORE, 'readwrite')
    const d = tx.objectStore(OUTBOX_STORE).delete(id)
    d.onsuccess = () => resolve()
    d.onerror = () => reject(d.error)
  })
}

async function flushOutboxFromSw() {
  let db
  try {
    db = await openIdb()
  } catch {
    return
  }
  let items
  try {
    items = await idbGetAll(db)
  } catch {
    return
  }

  for (const row of items) {
    const headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(row.headers && typeof row.headers === 'object' ? row.headers : {}),
    }
    if (row.tokenSnapshot) {
      headers.Authorization = `Bearer ${row.tokenSnapshot}`
    }
    try {
      const res = await fetch(row.url, {
        method: row.method,
        headers,
        body: row.method === 'GET' || row.method === 'HEAD' ? undefined : row.body,
      })
      if (res.ok) {
        await idbDelete(db, row.id)
      }
    } catch {
      /* giữ lại để flush sau */
    }
  }
}
