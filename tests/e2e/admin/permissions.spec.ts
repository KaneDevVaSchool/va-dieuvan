import { test, expect } from '@playwright/test'
import { loginAs } from '../../helpers/auth'

test.describe('Admin — Phân quyền', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin')
    await page.goto('/system/roles')
  })

  test('hiển thị danh sách roles', async ({ page }) => {
    await expect(page.locator('[data-testid="roles-list"]')).toBeVisible()
  })

  test('có thể xem chi tiết permission của role', async ({ page }) => {
    const firstRole = page.locator('[data-testid="role-item"]').first()
    const count = await firstRole.count()

    if (count > 0) {
      await firstRole.click()
      await expect(page.locator('[data-testid="role-detail"]')).toBeVisible()
    }
  })

  test('non-admin không truy cập được trang roles', async ({ page }) => {
    await loginAs(page, 'dispatcher')
    await page.goto('/system/roles')
    await expect(page).not.toHaveURL('/system/roles')
  })
})

test.describe('Admin — Feature Toggles', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'admin')
    await page.goto('/system/feature-toggles')
  })

  test('hiển thị danh sách feature flags', async ({ page }) => {
    await expect(page.locator('[data-testid="feature-list"]')).toBeVisible()
  })

  test('có thể bật/tắt feature flag', async ({ page }) => {
    const firstToggle = page.locator('[data-testid="feature-toggle"]').first()
    const count = await firstToggle.count()

    if (count > 0) {
      const initialState = await firstToggle.getAttribute('aria-checked')
      await firstToggle.click()

      await page.waitForResponse(/\/api\/admin\/feature-toggles/)

      const newState = await firstToggle.getAttribute('aria-checked')
      expect(newState).not.toBe(initialState)
    }
  })
})
