// Built SPA and mocked APIs; no operational database or notifications.
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
  } else { res.setHeader('Content-Type', 'text/html'); res.end(html) }
})
await new Promise(r => server.listen(0, '127.0.0.1', r))
let browser
try {
  browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'vi-VN', serviceWorkers: 'block' })
  await context.addInitScript(() => {
    localStorage.setItem('sanctum_token', 'portal-test-token')
    localStorage.setItem('vas_onboarded', '1')
  })
  const detail = {
    id: 41, requester_id: 7, requester: { id: 7, name: 'Người đề xuất thử' },
    status: 'approved', trip_type: 'cargo', origin: 'Cơ sở A', destination: 'Cơ sở B',
    depart_at: '2026-10-21T15:00:00+07:00', created_at: '2026-10-20T08:00:00+07:00',
    passenger_count: 0, attachments: [], wizard_snapshot: {},
    trip: { id: 42, status: 'assigned', driver: { id: 52, full_name: 'Tài xế kiểm thử', phone: '0900000052' }, vehicle: { id: 5, type: 'Minibus', license_plate: 'TEST-52' } },
  }
  await context.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname
    let data = { data: { items: [], meta: { total: 0 }, unread_count: 0 } }
    if (path === '/api/user') data = { id: 7, name: 'Người đề xuất thử', roles: [], permissions: [], is_superadmin: false }
    if (path === '/api/portal/dispatch-requests/41') data = { data: detail }
    await route.fulfill({ contentType: 'application/json', body: JSON.stringify(data) })
  })
  const page = await context.newPage()
  const errors = []
  page.on('pageerror', e => errors.push(e.message))
  await page.goto(`http://127.0.0.1:${server.address().port}/portal/requests/41`)
  const strip = page.getByTestId('portal-action-dispatch-strip')
  await expect(strip).toContainText('Tài xế kiểm thử', { timeout: 5000 })
  await expect(strip).toContainText('0900000052')
  await expect(strip).toContainText('Minibus')
  await expect(strip).toContainText('TEST-52')
  await expect(page.locator('.splash-screen')).toHaveCount(0)
  await strip.scrollIntoViewIfNeeded()
  await page.screenshot({ path: 'storage/app/portal-dispatch-info-preview.png', fullPage: true })

  detail.trip = { id: 42, status: 'assigned', driver: null, vehicle: null, external_driver_ref: 'Tài xế nhà cung cấp', external_vehicle_ref: 'Xe thuê ngoài TEST' }
  await page.reload()
  await expect(strip).toContainText('Tài xế nhà cung cấp')
  await expect(strip).toContainText('Xe thuê ngoài TEST')
  detail.status = 'pending'
  detail.trip = null
  await page.reload()
  await expect(page.getByTestId('portal-action-next')).toBeVisible()
  await expect(strip).toHaveCount(0)
  assert.deepEqual(errors, [])
  console.log('PASS: requester sees assigned driver, phone, vehicle, plate, external resources; pending request has no dispatch strip.')
} finally {
  if (browser) await browser.close()
  server.close()
}
