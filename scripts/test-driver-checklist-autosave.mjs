// Run after npm run build. Uses the real SPA and mocked APIs; no database or notifications.
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
let browser
try {
  browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'vi-VN', serviceWorkers: 'block' })
  await context.addInitScript(() => {
    localStorage.setItem('sanctum_token', 'audit-mock')
    localStorage.setItem('vas_onboarded', '1')
  })
  let doc = { id: 1, code: 'CL-TEST-1', status: 'draft', period_type: 'daily', lock_version: 0,
    started_at: '2026-10-05T08:00:00+07:00', vehicle: { id: 1, license_plate: 'TEST-A' },
    trip_info: { date: '05/10/2026', driver_name: 'Test Driver' }, note: null,
    items: [{ id: 1, group_name: 'Test', title: 'Check', result: 'ok', severity: 'critical', attachments: [] }] }
  let failures = 2
  const requests = []
  await context.route('**/api/**', async route => {
    const req = route.request(), path = new URL(req.url()).pathname, method = req.method()
    let data = { data: { items: [], meta: { total: 0 }, unread_count: 0 } }, status = 200
    if (path === '/api/user') data = { id: 1, name: 'Test Driver', roles: [{ name: 'driver' }], permissions: ['checklist.submit'] }
    if (path === '/api/driver/checklists/1') {
      if (method === 'PATCH') {
        requests.push({ method, path, payload: req.postDataJSON() })
        if (failures-- > 0) { status = 500; data = { message: 'Simulated autosave failure' } }
        else { doc = { ...doc, ...req.postDataJSON(), lock_version: doc.lock_version + 1 }; data = { data: doc } }
      } else data = { data: doc }
    }
    if (path === '/api/driver/checklists/1/submit') {
      requests.push({ method, path, payload: req.postDataJSON() })
      doc = { ...doc, ...req.postDataJSON(), status: 'submitted', result: 'pass', submitted_at: new Date().toISOString() }
      data = { data: doc }
    }
    await route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(data) })
  })
  const page = await context.newPage()
  const errors = []
  page.on('pageerror', e => errors.push(e.message))
  await page.goto(`http://127.0.0.1:${server.address().port}/driver/checklists/1`)
  const confirm = page.getByTestId('checklist-confirm'), submit = page.getByTestId('checklist-submit')
  await confirm.waitFor()
  const firstFailure = page.waitForResponse(r => r.request().method() === 'PATCH' && r.status() === 500)
  await page.locator('textarea').last().fill('Keep this note')
  await firstFailure
  await confirm.check()
  const submitFailure = page.waitForResponse(r => r.request().method() === 'PATCH' && r.status() === 500)
  await submit.click()
  await submitFailure
  await expect(submit).toBeEnabled()
  assert.equal(requests.filter(r => r.path.endsWith('/submit')).length, 0, 'Must stop submission when autosave fails')
  await expect(page.locator('textarea').last()).toHaveValue('Keep this note')
  await page.locator('textarea').last().fill('Latest note after retry')
  await submit.click()
  await page.getByTestId('checklist-result-card').waitFor()
  assert.equal(doc.note, 'Latest note after retry', 'Latest header changes must survive retries')
  assert.equal(requests.filter(r => r.path.endsWith('/submit')).length, 1)
  assert.equal(requests.filter(r => r.method === 'PATCH').length, 3)
  assert.deepEqual(errors, [], 'No unhandled browser exceptions')
  console.log('PASS: failed autosave blocks submit, preserves header, retries latest data, and submits once.')
  await context.close()

  const accounting = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'vi-VN', serviceWorkers: 'block' })
  await accounting.addInitScript(() => {
    localStorage.setItem('sanctum_token', 'accountant-mock')
    localStorage.setItem('vas_onboarded', '1')
  })
  await accounting.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname
    const data = path === '/api/user'
      ? { id: 2, name: 'Test Accountant', roles: [{ name: 'accountant' }], permissions: ['expense.view', 'expense.manage'] }
      : { data: { items: [], meta: { current_page: 1, last_page: 1, total: 0 }, unread_count: 0 } }
    await route.fulfill({ contentType: 'application/json', body: JSON.stringify(data) })
  })
  const accountingPage = await accounting.newPage()
  const accountingErrors = []
  accountingPage.on('pageerror', e => accountingErrors.push(e.message))
  await accountingPage.goto(`http://127.0.0.1:${server.address().port}/login`)
  await expect(accountingPage).toHaveURL(/\/mng\/expenses$/)
  await expect(accountingPage.locator('input[type="search"]').first()).toBeVisible()
  assert.deepEqual(accountingErrors, [])
  console.log('PASS: accountant-only login opens the financial document list.')
} finally {
  await browser?.close()
  await new Promise(r => server.close(r))
}
