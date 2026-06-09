import { test, expect } from '@playwright/test'
import { loginAs } from '../../helpers/auth'

test.describe('Danh sách chuyến đi', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'dispatcher')
    await page.goto('/trips')
  })

  test('hiển thị danh sách chuyến', async ({ page }) => {
    await expect(page.locator('[data-testid="trips-list"]')).toBeVisible()
  })

  test('có thể filter theo trạng thái', async ({ page }) => {
    await page.click('[data-testid="filter-status-pending"]')
    await page.waitForResponse(/\/api\/trips/)

    const items = page.locator('[data-testid="trip-card"]')
    const count = await items.count()

    if (count > 0) {
      const statusBadge = items.first().locator('[data-testid="status-badge"]')
      await expect(statusBadge).toContainText('Chờ')
    }
  })

  test('có thể tìm kiếm theo ID chuyến', async ({ page }) => {
    await page.fill('[data-testid="search-input"]', '1')
    await page.waitForResponse(/\/api\/trips/)

    await expect(page.locator('[data-testid="trips-list"]')).toBeVisible()
  })

  test('có thể lọc theo ngày', async ({ page }) => {
    await page.fill('[data-testid="filter-from"]', '2026-01-01')
    await page.fill('[data-testid="filter-to"]', '2026-12-31')
    await page.waitForResponse(/\/api\/trips/)

    await expect(page.locator('[data-testid="trips-list"]')).toBeVisible()
  })

  test('hiển thị KPI stats', async ({ page }) => {
    await expect(page.locator('[data-testid="stats-total"]')).toBeVisible()
    await expect(page.locator('[data-testid="stats-in-progress"]')).toBeVisible()
    await expect(page.locator('[data-testid="stats-completed"]')).toBeVisible()
  })

  test('click vào chuyến để xem chi tiết', async ({ page }) => {
    const firstTrip = page.locator('[data-testid="trip-card"]').first()
    const count = await firstTrip.count()

    if (count > 0) {
      await firstTrip.click()
      await expect(page).toHaveURL(/\/trips\/\d+/)
    }
  })
})
