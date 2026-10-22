// Real SPA with mocked APIs; no operational database or notifications.
import { chromium, expect } from '@playwright/test'
import assert from 'node:assert/strict'
import { createServer } from 'node:http'
import { readFileSync, existsSync, statSync } from 'node:fs'
import { resolve, extname, sep } from 'node:path'

const root = resolve('public')
const manifest = JSON.parse(readFileSync(resolve(root, 'build/manifest.json')))
const entry = manifest['resources/js/app.js']
const css = [...(entry.css || []), manifest['resources/css/app.css']?.file].filter(Boolean)
// --dev checks the existing Vite process without starting a second optimizer or
// modifying public/hot. This catches stale optimizer URLs hidden by build tests.
const devOrigin = process.argv.includes('--dev') ? readFileSync(resolve(root, 'hot'), 'utf8').trim() : null
const assets = devOrigin
  ? `<script type="module" src="${devOrigin}/@vite/client"></script><script type="module" src="${devOrigin}/resources/js/app.js"></script>`
  : `<script type="module" src="/build/${entry.file}"></script>`
const styles = devOrigin ? '' : css.map(c => `<link rel="stylesheet" href="/build/${c}">`).join('')
const html = `<html><head><meta name="viewport" content="width=device-width, initial-scale=1">${styles}</head><body class="h-dvh overflow-hidden"><div id="app" class="h-full"></div>${assets}</body></html>`
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
let browser
try {
  browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 }, locale: 'vi-VN', serviceWorkers: 'block' })
  await context.addInitScript(() => {
    localStorage.setItem('sanctum_token', 'report-test-token')
    localStorage.setItem('vas_onboarded', '1')
  })
  const report = {
    kpi: { totalTrips: 12, activeDrivers: 1, avgTripsPerDriver: 12, driversAboveThreshold: 0, activeVehicles: 1, avgTripsPerVehicle: 12, vehiclesBelowThreshold: 1, totalHours: 24, avgHoursPerDriver: 24, overallOnTime: 100 },
    drivers: [{ id: 52, name: 'Report Test Driver', code: 'TX52', trips: 12, hours: 24, onTime: 100, types: [12, 0, 0, 0], typesExport: [0, 0, 12, 0] }],
    vehicles: [{ id: 5, plate: 'TEST-52', trips: 12 }],
    quarterly: [3, 3, 3, 3], monthly: Array(12).fill(1),
    filter_options: { drivers: [{ id: 52, name: 'Report Test Driver' }], vehicles: [{ id: 5, plate: 'TEST-52' }] },
    year_comparison: {},
  }
  let reportRequests = 0
  const routeApi = async route => {
    const path = new URL(route.request().url()).pathname
    if (!path.startsWith('/api/')) return route.continue()
    let data = { data: { items: [], meta: { total: 0, last_page: 1 }, unread_count: 0 } }
    if (path === '/api/user') data = { id: 1, name: 'Report Tester', is_active: true, is_superadmin: true, roles: [{ name: 'superadmin' }], permissions: [] }
    if (path === '/api/reports/driver-frequency') {
      reportRequests++
      await new Promise(r => setTimeout(r, 150))
      data = { data: report }
    }
    await route.fulfill({ contentType: 'application/json', body: JSON.stringify(data) })
  }
  await context.route('**/api/**', routeApi)
  const page = await context.newPage()
  const errors = []
  const failedDependencies = []
  page.on('pageerror', e => { errors.push(e.message); console.error('PAGE ERROR:', e.message) })
  if (devOrigin) {
    page.on('requestfailed', request => console.error('REQUEST FAILED:', request.url(), request.failure()?.errorText))
    page.on('console', message => { if (message.type() === 'error') console.error('CONSOLE:', message.text()) })
  }
  page.on('response', response => {
    if (response.url().includes('/node_modules/.vite/') && !response.ok()) {
      failedDependencies.push(`${response.status()} ${response.url()}`)
    }
  })
  await page.goto(`http://127.0.0.1:${server.address().port}/mng/reports/driver-frequency`)
  await expect(page.getByRole('heading', { name: 'Tần suất tài xế & xe', exact: true })).toBeVisible({ timeout: 20000 })
  await expect(page.getByRole('cell', { name: 'Report Test Driver TX52' })).toBeVisible()
  await expect(page.locator('.freq-dash canvas')).toHaveCount(5)
  await page.waitForTimeout(1000)
  assert.equal(reportRequests, 1, 'Initial render must fetch the report once')
  assert.deepEqual(errors, [], 'Report must render without runtime errors')
  const year = page.getByTestId('driver-freq-filter-year')
  await year.selectOption(String(new Date().getFullYear() - 1))
  await expect.poll(() => reportRequests).toBe(2)
  await page.waitForTimeout(700)
  assert.equal(reportRequests, 2, 'Changing a filter must not cause repeated requests')
  await page.getByTestId('datagrid-toolbar-search').fill('no match')
  await expect(page.locator('.freq-empty')).toBeVisible()
  await page.getByTestId('datagrid-toolbar-search').fill('Report Test Driver')
  await expect(page.getByRole('cell', { name: 'Report Test Driver TX52' })).toBeVisible()
  assert.equal(reportRequests, 2, 'Local search must not reload the report')
  await page.setViewportSize({ width: 390, height: 844 })
  await page.waitForTimeout(300)
  assert.deepEqual(errors, [])
  assert.deepEqual(failedDependencies, [], 'Optimized dependencies must load without 504 errors')
  console.log('PASS: report opens, renders five charts, filters once, and searches without reloading')

  if (!devOrigin) {
  const staleContext = await browser.newContext({ serviceWorkers: 'block' })
  await staleContext.addInitScript(() => {
    localStorage.setItem('sanctum_token', 'report-test-token')
    localStorage.setItem('vas_onboarded', '1')
  })
  await staleContext.route('**/api/**', routeApi)
  let failedChunkRequests = 0
  await staleContext.route('**/build/assets/DriverFrequencyReportView-*.js', async route => {
    failedChunkRequests++
    await route.fulfill({ status: 404, contentType: 'text/javascript', body: '' })
  })
  const stalePage = await staleContext.newPage()
  await stalePage.goto(`http://127.0.0.1:${server.address().port}/mng/reports/driver-frequency`)
  await stalePage.waitForTimeout(2500)
  console.log('Missing report chunk requests:', failedChunkRequests)
  assert(failedChunkRequests <= 2, 'Missing report chunks must not cause an endless reload loop')
  await expect(stalePage.getByRole('alertdialog')).toContainText('Không thể mở trang')
  assert.equal(await stalePage.evaluate(() => sessionStorage.getItem('va-dieuvan:chunk-reload-once')), '1')
  await staleContext.close()
  console.log('PASS: a missing report chunk only triggers one recovery reload and shows an error')

  const recoveryContext = await browser.newContext({ serviceWorkers: 'block' })
  await recoveryContext.addInitScript(() => {
    localStorage.setItem('sanctum_token', 'report-test-token')
    localStorage.setItem('vas_onboarded', '1')
  })
  await recoveryContext.route('**/api/**', routeApi)
  let recoveryChunkRequests = 0
  await recoveryContext.route('**/build/assets/DriverFrequencyReportView-*.js', async route => {
    recoveryChunkRequests++
    if (recoveryChunkRequests === 1) {
      await route.fulfill({ status: 404, contentType: 'text/javascript', body: '' })
    } else {
      await route.continue()
    }
  })
  const recoveryPage = await recoveryContext.newPage()
  await recoveryPage.goto(`http://127.0.0.1:${server.address().port}/mng/reports/driver-frequency`)
  await expect(recoveryPage.getByRole('cell', { name: 'Report Test Driver TX52' })).toBeVisible()
  assert.equal(recoveryChunkRequests, 2, 'A transient missing chunk must recover on the next load')
  assert.equal(await recoveryPage.evaluate(() => sessionStorage.getItem('va-dieuvan:chunk-reload-once')), null)
  await recoveryContext.close()
  console.log('PASS: report opens after a transient chunk failure and resets recovery after navigation')
  } else {
    console.log('PASS: live Vite report loads without outdated optimized dependencies')
  }
} finally {
  await browser?.close()
  await new Promise(r => server.close(r))
}
