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
  const user = { id: 1, name: 'Menu Test', email: 'menu@example.test', is_active: true, is_superadmin: true, roles: [{ id: 1, name: 'superadmin' }], permissions: [] }
  await context.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname
    let data = { data: { items: [], meta: { total: 0, last_page: 1 }, unread_count: 0 } }
    if (path === '/api/user') data = user
    if (path === '/api/admin/roles') data = { data: [] }
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(data) })
  })
  const page = await context.newPage()
  const errors = []
  page.on('pageerror', error => errors.push(error.message))
  const origin = `http://127.0.0.1:${server.address().port}`

  await page.goto(origin + '/mng/system/user-roles')
  const menu = page.getByRole('button', { name: 'Menu', exact: true })
  const drawer = page.getByRole('dialog', { name: 'Menu', exact: true })
  await expect(menu).toBeVisible()
  await expect(page.locator('.splash-screen')).toHaveCount(0)
  await page.screenshot({ path: 'storage/app/mobile-menu-header.png' })
  await menu.click()
  await page.screenshot({ path: 'storage/app/mobile-menu-drawer.png' })
  await expect(drawer).toBeVisible()
  await expect(drawer.locator('a[href="/mng/system/user-roles"]')).toBeVisible()
  assert.ok(await drawer.locator('nav a').count() > 10)
  await page.keyboard.press('Escape')
  await expect(drawer).not.toBeVisible()
  await expect(menu).toBeFocused()
  await menu.click()
  await page.mouse.click(380, 400)
  await expect(drawer).not.toBeVisible()
  await menu.click()
  await drawer.locator('a[href="/mng/system/user-roles"]').click()
  await expect(drawer).not.toBeVisible()
  for (const width of [320, 390, 767]) {
    await page.setViewportSize({ width, height: 844 })
    await expect(menu).toBeVisible()
    await menu.click()
    await expect(drawer).toBeVisible()
    await expect(drawer.locator('nav')).toBeVisible()
    assert.ok(await drawer.evaluate(el => el.scrollWidth <= el.clientWidth))
    await page.keyboard.press('Escape')
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth))
  }
  await page.evaluate(() => {
    localStorage.setItem('va-sidebar-axis', 'vertical')
    localStorage.setItem('va-sidebar-rail', '1')
  })
  await page.reload()
  await menu.click()
  await expect(drawer.locator('a[href="/mng/system/user-roles"]')).toBeVisible()
  await page.keyboard.press('Escape')
  await page.setViewportSize({ width: 1440, height: 900 })
  await expect(menu).not.toBeVisible()
  await expect(page.locator('aside')).toHaveCSS('width', '68px')
  assert.equal(await page.evaluate(() => localStorage.getItem('va-sidebar-axis')), 'vertical')
  assert.equal(await page.evaluate(() => localStorage.getItem('va-sidebar-rail')), '1')
  await page.setViewportSize({ width: 390, height: 844 })
  await menu.click()
  await expect(drawer).toBeVisible()
  await page.setViewportSize({ width: 1024, height: 844 })
  await expect(drawer).not.toBeVisible()
  await page.setViewportSize({ width: 390, height: 844 })
  await menu.click()
  await drawer.locator('a[href="/mng/system/roles"]').click()
  await expect(page).toHaveURL(/\/mng\/system\/roles$/)
  await expect(drawer).not.toBeVisible()
  assert.deepEqual(errors, [])
  console.log('Mobile sidebar: full navigation, narrow widths, dismissal, focus, resize and desktop preferences passed.')
} finally {
  await browser?.close()
  await new Promise(r => server.close(r))
}
