import { test, expect } from '@playwright/test'

test.describe('Xác thực người dùng', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
  })

  test('hiển thị trang login', async ({ page }) => {
    await expect(page).toHaveTitle(/VA Điều Vận|Đăng nhập/)
    await expect(page.locator('[data-testid="email-input"]')).toBeVisible()
    await expect(page.locator('[data-testid="password-input"]')).toBeVisible()
    await expect(page.locator('[data-testid="login-btn"]')).toBeVisible()
  })

  test('đăng nhập thành công với email/password hợp lệ', async ({ page }) => {
    await page.fill('[data-testid="email-input"]', 'test-dispatcher@va.test')
    await page.fill('[data-testid="password-input"]', 'password')
    await page.click('[data-testid="login-btn"]')

    await page.waitForURL(/\/(dashboard|trips|requests)/)
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
  })

  test('hiển thị lỗi khi đăng nhập sai mật khẩu', async ({ page }) => {
    await page.fill('[data-testid="email-input"]', 'test-dispatcher@va.test')
    await page.fill('[data-testid="password-input"]', 'wrong-password')
    await page.click('[data-testid="login-btn"]')

    await expect(page.locator('[data-testid="login-error"]')).toBeVisible()
    await expect(page).toHaveURL('/login')
  })

  test('redirect về /login khi chưa đăng nhập truy cập route protected', async ({ page }) => {
    await page.goto('/trips')
    await expect(page).toHaveURL('/login')
  })

  test('hiển thị nút đăng nhập Google', async ({ page }) => {
    await expect(page.locator('[data-testid="google-login-btn"]')).toBeVisible()
  })
})
