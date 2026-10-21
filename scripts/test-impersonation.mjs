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
let browser
try {
  browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'vi-VN', serviceWorkers: 'block' })
  await context.addInitScript(() => {
    if (!localStorage.getItem('sanctum_token')) localStorage.setItem('sanctum_token', 'original-test-token')
    localStorage.setItem('vas_onboarded', '1')
  })
  const roles = [{ id: 1, name: 'superadmin', display_name: 'Super Admin' }, { id: 2, name: 'driver', display_name: 'Tài xế' }]
  const original = { id: 1, name: 'Test Super Admin', email: 'phongcongnghe@vaschools.edu.vn', is_active: true, is_superadmin: true, can_impersonate: true, roles: [roles[0]], permissions: [], impersonation: null }
  const target = { id: 2, name: 'Test Driver', email: 'driver@example.test', is_active: true, is_superadmin: false, roles: [roles[1]], permissions: ['trip.view_own', 'trip.update_status'], impersonation: { active: true, impersonator: { id: 1, name: original.name }, role: { name: 'driver', label: 'Tài xế' } } }
  let expired = false
  const portalTarget = { id: 3, name: 'No role', email: 'none@example.test', is_active: true, is_superadmin: false, roles: [], permissions: [], impersonation: { active: true, portal: true, impersonator: { id: 1, name: original.name }, role: { name: null, label: 'Portal người yêu cầu' } } }
  let starts = 0
  let leaves = 0
  await context.route('**/api/**', async route => {
    const request = route.request()
    const path = new URL(request.url()).pathname
    const impersonating = request.headers().authorization === 'Bearer impersonated-test-token'
    const portalImpersonating = request.headers().authorization === 'Bearer impersonated-portal-token'
    let status = 200
    let data = { data: { items: [], meta: { total: 0, last_page: 1 }, unread_count: 0 } }
    if (path === '/api/user') {
      if (impersonating && expired) { status = 401; data = { message: 'Expired' } }
      else data = impersonating ? target : portalImpersonating ? portalTarget : original
    }
    if (path === '/api/impersonate/3') {
      assert.deepEqual(request.postDataJSON(), { portal: true })
      data = { data: { token: 'impersonated-portal-token', impersonation: portalTarget.impersonation } }
    }
    if (path === '/api/admin/roles') data = { data: roles }
    if (path === '/api/admin/roles/2') data = { data: { ...roles[1], permissions: [], users_count: 1 } }
    if (path === '/api/admin/users') data = { data: { items: [original, { ...target, impersonation: null }, { id: 3, name: 'No role', email: 'none@example.test', roles: [] }], meta: { total: 3, current_page: 1, last_page: 1, per_page: 25, per_page_mode: 'paged' } } }
    if (path === '/api/impersonate/2') {
      assert.equal(request.postDataJSON().role, 'driver')
      starts++
      data = { data: { token: 'impersonated-test-token', impersonation: target.impersonation } }
    }
    if (path === '/api/impersonate/leave') { leaves++; data = { data: null } }
    if (path === '/api/driver/summary') data = { data: { driver: { id: 52, full_name: target.name }, stats: { trips_today: 0 }, vehicle: null } }
    if (path === '/api/driver/checklists/overview') data = { data: {} }
    await route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(data) })
  })
  const page = await context.newPage()
  const errors = []
  page.on('pageerror', error => errors.push(error.message))
  const origin = `http://127.0.0.1:${server.address().port}`
  const roleReturnPath = '/mng/system/roles/2?tab=members#members'
  await page.goto(`${origin}${roleReturnPath}`)
  await page.getByRole('button', { name: /^Nhân viên \(/ }).click()
  await expect(page.getByTestId('role-member-1').getByTestId('role-member-impersonate')).toHaveCount(0)
  await page.getByTestId('role-member-2').getByTestId('role-member-impersonate').click()
  await expect(page.getByRole('dialog').getByRole('combobox')).toHaveCount(0)
  await expect(page.getByRole('dialog')).toContainText('Vai trò trải nghiệm: Tài xế')
  await page.getByRole('dialog').getByRole('button', { name: 'Giả lập', exact: true }).click()
  await expect(page).toHaveURL(/\/driver$/)
  await expect(page.getByTestId('impersonation-banner')).toContainText('Test Driver')
  await page.reload()
  await expect(page.getByTestId('impersonation-banner')).toBeVisible()
  await page.getByTestId('impersonation-banner').getByRole('button', { name: 'Thoát giả lập' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Thoát giả lập' }).click()
  await expect(page).toHaveURL(`${origin}${roleReturnPath}`)
  await expect(page.getByTestId('role-member-2')).toBeVisible()
  assert.equal(await page.evaluate(() => localStorage.getItem('va_impersonator_return_path')), null)
  starts = 0
  leaves = 0
  await page.goto(`${origin}/mng/system/user-roles`)
  await expect(page.getByTestId('user-role-card-2').getByTestId('user-role-impersonate')).toBeVisible()
  await expect(page.getByTestId('user-role-card-1').getByTestId('user-role-impersonate')).toHaveCount(0)
  await expect(page.getByTestId('user-role-card-3').getByTestId('user-role-impersonate')).toBeVisible()

  // User chưa có vai trò → giả lập Portal người yêu cầu.
  await page.getByTestId('user-role-card-3').getByTestId('user-role-impersonate').click()
  await expect(page.getByRole('dialog').getByRole('combobox')).toHaveCount(0)
  await expect(page.getByRole('dialog')).toContainText('Vai trò trải nghiệm: Portal người yêu cầu')
  await page.getByRole('dialog').getByRole('button', { name: 'Giả lập', exact: true }).click()
  await expect(page).toHaveURL(/\/portal$/)
  await expect(page.getByTestId('impersonation-banner')).toContainText('No role')
  await expect(page.getByTestId('impersonation-banner')).toContainText('Portal người yêu cầu')
  await expect(page.locator('.splash-screen')).toHaveCount(0)
  await expect(page.getByTestId('impersonation-banner')).toBeInViewport()
  await page.locator('.portal-app-main').evaluate(el => el.scrollTo(0, 400))
  await page.screenshot({ path: 'storage/app/impersonation-portal-preview.png' })
  await page.getByTestId('impersonation-banner').getByRole('button', { name: 'Thoát giả lập' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Thoát giả lập' }).click()
  await expect(page).toHaveURL(/\/mng\/system\/user-roles$/)
  assert.equal(leaves, 1)
  leaves = 0

  async function startDriver() {
    await page.getByTestId('user-role-card-2').getByTestId('user-role-impersonate').click()
    await expect(page.getByRole('dialog')).toBeVisible()
    await page.getByRole('dialog').getByRole('button', { name: 'Giả lập', exact: true }).click()
    await expect(page).toHaveURL(/\/driver$/)
    await expect(page.getByTestId('impersonation-banner')).toContainText('Test Driver')
    await expect(page.getByTestId('impersonation-banner')).toContainText('Tài xế')
  }
  const userReturnPath = '/mng/system/user-roles?roles=driver&per_page=25#employees'
  await page.goto(`${origin}${userReturnPath}`)
  await startDriver()
  await page.reload()
  await expect(page.getByTestId('impersonation-banner')).toBeVisible()
  await expect(page.locator('.splash-screen')).toHaveCount(0)
  await page.screenshot({ path: 'storage/app/impersonation-driver-preview.png', fullPage: true })
  await page.getByTestId('impersonation-banner').getByRole('button', { name: 'Thoát giả lập' }).click()
  await expect(page.getByRole('dialog')).toContainText('Super Admin')
  await page.getByRole('dialog').getByRole('button', { name: 'Thoát giả lập' }).click()
  await expect(page).toHaveURL(`${origin}${userReturnPath}`)
  await expect(page.getByTestId('user-role-card-2')).toBeVisible()
  assert.equal(leaves, 1)
  assert.equal(await page.evaluate(() => localStorage.getItem('va_impersonator_token')), null)
  assert.equal(await page.evaluate(() => localStorage.getItem('sanctum_token')), 'original-test-token')

  await startDriver()
  expired = true
  await page.reload()
  await expect(page).toHaveURL(`${origin}${userReturnPath}`)
  await expect(page.getByTestId('user-role-card-2')).toBeVisible()
  assert.equal(starts, 2)
  assert.equal(await page.evaluate(() => localStorage.getItem('sanctum_token')), 'original-test-token')
  original.email = 'other-superadmin@example.test'
  original.can_impersonate = false
  await page.reload()
  await expect(page.getByTestId('user-role-card-2')).toBeVisible()
  await expect(page.getByTestId('user-role-impersonate')).toHaveCount(0)
  await page.goto(`${origin}/mng/system/roles/2`)
  await page.getByRole('button', { name: /^Nhân viên \(/ }).click()
  await expect(page.getByTestId('role-member-2')).toBeVisible()
  await expect(page.getByTestId('role-member-impersonate')).toHaveCount(0)
  assert.deepEqual(errors, [])
  console.log('PASS: SIS user/role selection, portal requester, driver identity, reload, confirmed exit, expired-session restoration, and no browser exceptions.')
} finally {
  if (browser) await browser.close()
  server.close()
}
