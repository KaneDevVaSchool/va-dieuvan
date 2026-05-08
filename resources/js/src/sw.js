/// <reference lib="webworker" />

import { clientsClaim } from 'workbox-core'
import {
  precacheAndRoute,
  cleanupOutdatedCaches,
  createHandlerBoundToURL,
} from 'workbox-precaching'
import { NavigationRoute, registerRoute } from 'workbox-routing'
import { CacheFirst, StaleWhileRevalidate } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'

clientsClaim()

// Injected by vite-plugin-pwa at build time
precacheAndRoute(self.__WB_MANIFEST)
cleanupOutdatedCaches()

const navigationHandler = createHandlerBoundToURL('/')
registerRoute(
  new NavigationRoute(navigationHandler, {
    denylist: [/^\/api\//],
  }),
)

// Google Fonts: cache-first, 1 year
registerRoute(
  ({ url }) => /^https:\/\/fonts\.(googleapis|gstatic)\.com/.test(url.href),
  new CacheFirst({
    cacheName: 'google-fonts',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 20,
        maxAgeSeconds: 60 * 60 * 24 * 365,
      }),
    ],
  }),
)

// Static images (local + any same-origin /images/ path): cache-first, 30 days
registerRoute(
  ({ request, url }) =>
    request.destination === 'image' &&
    url.origin === self.location.origin,
  new CacheFirst({
    cacheName: 'local-images',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 60,
        maxAgeSeconds: 60 * 60 * 24 * 30,
      }),
    ],
  }),
)

// Safe read-only API endpoints: stale-while-revalidate, 24 h
registerRoute(
  /\/api\/(targets|config|lookup|reference-pricing)\b/,
  new StaleWhileRevalidate({
    cacheName: 'api-lookup',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 50,
        maxAgeSeconds: 60 * 60 * 24,
      }),
    ],
  }),
)

self.addEventListener('message', (event) => {
  if (event?.data?.type === 'SKIP_WAITING') {
    void self.skipWaiting()
  }
})

self.addEventListener('push', (event) => {
  /** @type {{ title?: string; body?: string; url?: string; tag?: string; is_urgent?: boolean }} */
  let payload = { title: 'VAS Dispatch', body: '', url: '/driver' }
  try {
    if (event.data != null) {
      const j = event.data.json()
      payload = { ...payload, ...j }
    }
  } catch {
    try {
      const t = event.data?.text?.() ?? ''
      if (t) payload = { ...payload, body: t }
    } catch {
      /* ignore */
    }
  }

  const title = payload.title && String(payload.title).trim() !== '' ? payload.title : 'VAS Dispatch'
  const body = payload.body != null ? String(payload.body) : ''
  const url = payload.url != null && String(payload.url).trim() !== '' ? String(payload.url) : '/driver'
  const tag = payload.tag != null && String(payload.tag).trim() !== '' ? String(payload.tag) : 'va-trip'
  const requireInteraction = Boolean(payload.is_urgent)

  event.waitUntil(
    self.registration.showNotification(title, {
      body,
      icon: '/icons/pwa-192.png',
      badge: '/icons/pwa-192.png',
      tag,
      data: { url },
      requireInteraction,
    }),
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const raw = event.notification.data?.url || '/driver'
  const targetUrl = raw.startsWith('http')
    ? raw
    : new URL(raw, self.location.origin).href

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.startsWith(self.location.origin) && 'focus' in client) {
          // Navigate the existing window to the right page, then bring it to front.
          if ('navigate' in client) {
            return client.navigate(targetUrl).then((c) => c?.focus?.())
          }
          return client.focus()
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl)
      }
      return undefined
    }),
  )
})
