// Real SPA with mocked APIs. No operational database or notifications.
import { chromium, expect } from '@playwright/test'
import assert from 'node:assert/strict'
import { createServer } from 'node:http'
import { readFileSync, existsSync, statSync } from 'node:fs'
import { resolve, extname, sep } from 'node:path'

const root = resolve('public')
const manifest = JSON.parse(readFileSync(resolve(root, 'build/manifest.json')))
const entry = manifest['resources/js/app.js']
const css = [...(entry.css || []), manifest['resources/css/app.css']?.file].filter(Boolean)
const html = `<html><head><meta name="viewport" content="width=device-width, initial-scale=1">${css.map(c => `<link rel="stylesheet" href="/build/${c}">`).join('')}</head><body class="h-dvh overflow-hidden"><div id="app" class="h-full"></div><script type="module" src="/build/${entry.file}"></script></body></html>`
const server = createServer((req, res) => {
  const path = resolve(root, '.' + new URL(req.url, 'http://localhost').pathname)
  if (path.startsWith(root + sep) && existsSync(path) && statSync(path).isFile()) {
    res.setHeader('Content-Type', ({ '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml', '.png': 'image/png', '.json': 'application/json' })[extname(path)] || 'application/octet-stream')
    res.end(readFileSync(path))
  } else {
    res.setHeader('Content-Type', 'text/html')
    res.end(html)
  }
})
await new Promise(r => server.listen(0, '127.0.0.1', r))
// Verify actual scroll boundaries against the visible mobile navigation.
let browser
try {
  browser = await chromium.launch({ headless: true })
  const origin = 'http://127.0.0.1:' + server.address().port
  const requests = Array.from({ length: 5 }, (_, i) => ({
    id: 100 + i, status: 'approved', code: 'REQ-TEST-' + i, request_type: 'p2p',
    origin: 'Test origin ' + i, destination: 'Test destination ' + i,
    depart_at: '2026-10-22T08:00:00+07:00', passenger_count: 4,
  }))
  for (const mode of ['superadmin', 'dispatcher', 'portal']) {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'vi-VN', serviceWorkers: 'block' })
    await context.addInitScript(() => {
      localStorage.setItem('sanctum_token', 'bottom-clearance-test')
      localStorage.setItem('vas_onboarded', '1')
    })
    const user = { id: 1, name: 'Layout Test', email: 'layout@example.test', is_active: true,
      is_superadmin: mode === 'superadmin', roles: mode === 'portal' ? [] : [{ name: mode }], permissions: [] }
    await context.route('**/api/**', async route => {
      const path = new URL(route.request().url()).pathname
      let data = { data: { items: [], meta: { total: 0, last_page: 1 }, unread_count: 0 } }
      if (path === '/api/user') data = user
      if (['/api/requests', '/api/portal/dispatch-requests'].includes(path)) data = { data: { items: requests, meta: { total: 5, current_page: 1, last_page: 1 } } }
      if (path === '/api/trips') data = { data: { items: requests.map(r => ({ ...r, trip_number: 'TRP-' + r.id })), meta: { total: 5, last_page: 1 } } }
      if (path === '/api/reports/summary') data = { data: { trips_by_status: { approved: 5 }, dispatch_requests_by_status: { approved: 5 } } }
      if (path === '/api/portal/dispatch-requests/summary') data = { data: { total: 5, pending: 0 } }
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(data) })
    })
    const page = await context.newPage()
    const errors = []
    page.on('pageerror', e => errors.push(e.message))
    await page.goto(origin + (mode === 'portal' ? '/portal' : '/mng'))
    await expect(page.locator('.splash-screen')).toHaveCount(0)
    const main = page.locator(mode === 'portal' ? '.portal-app-main' : '#app-main-scroll')
    const nav = page.locator(mode === 'portal' ? '[data-testid="portal-bottom-nav"]' : '#app-main-scroll + div nav')
    const lastCard = page.getByTestId(mode === 'portal' ? 'portal-home-request-card-104' : 'dash-recent-request-104')
    await expect(lastCard).toBeAttached()
    for (const width of [320, 390, 767]) {
      await page.setViewportSize({ width, height: 844 })
      await expect(nav).toBeVisible()
      await main.evaluate(el => el.scrollTop = el.scrollHeight)
      await page.evaluate(() => new Promise(requestAnimationFrame))
      const navRect = await nav.boundingBox(), mainRect = await main.boundingBox()
      assert.ok(mainRect.y + mainRect.height <= navRect.y + 1, mode + ': main ends above nav')
      if (mode !== 'portal') {
        await expect(lastCard).toBeInViewport({ ratio: 0.99 })
        const cardRect = await lastCard.boundingBox()
        const clearance = navRect.y - cardRect.y - cardRect.height
        assert.ok(clearance >= 12 && clearance < 80, mode + ': useful bottom gap without excessive space: ' + clearance)
      } else {
        const contentBottom = await main.locator(':scope > div:last-child').evaluate(el => el.getBoundingClientRect().bottom)
        const clearance = mainRect.y + mainRect.height - contentBottom
        assert.ok(clearance >= 20 && clearance <= 30, 'Portal final content has the shared bottom gap (pb-6): ' + clearance)
        await lastCard.scrollIntoViewIfNeeded()
        await expect(lastCard).toBeInViewport({ ratio: 0.99 })
      }
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth))
    }
    await page.setViewportSize({ width: 390, height: 844 })
    await nav.evaluate(el => el.style.paddingBottom = '24px')
    const grownMain = await main.boundingBox(), grownNav = await nav.boundingBox()
    assert.ok(grownMain.y + grownMain.height <= grownNav.y + 1, 'Actual nav height is reserved')
    await nav.evaluate(el => el.style.paddingBottom = '')
    await main.evaluate(el => el.scrollTop = el.scrollHeight)
    await page.screenshot({ path: 'storage/app/mobile-bottom-' + mode + '.png' })
    await page.setViewportSize({ width: 1024, height: 900 })
    await expect(nav).not.toBeVisible()
    assert.deepEqual(errors, [], mode + ': no browser errors')
    await context.close()
  }
  console.log('PASS: superadmin /mng, dispatcher /mng and /portal; final content visible, bottom gap, 320/390/767px, changing nav height and desktop.')
} finally {
  await browser?.close()
  await new Promise(r => server.close(r))
}
