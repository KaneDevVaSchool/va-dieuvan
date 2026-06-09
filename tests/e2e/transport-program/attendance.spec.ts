import { test, expect } from '@playwright/test'
import { loginAs } from '../../helpers/auth'

test.describe('Điểm danh Transport Program', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'dispatcher')
  })

  test('hiển thị danh sách chương trình TP', async ({ page }) => {
    await page.goto('/transport-programs')
    await expect(page.locator('[data-testid="tp-list"]')).toBeVisible()
  })

  test('xem chi tiết ngày trong chương trình', async ({ page }) => {
    await page.goto('/transport-programs')

    const firstProgram = page.locator('[data-testid="tp-program-item"]').first()
    if (await firstProgram.count() > 0) {
      await firstProgram.click()
      await expect(page).toHaveURL(/\/transport-programs\/\d+/)
      await expect(page.locator('[data-testid="tp-days-calendar"]')).toBeVisible()
    }
  })

  test('điểm danh view hiển thị danh sách học sinh', async ({ page }) => {
    // Navigate to a specific program day attendance
    await page.goto('/transport-programs')

    // Find a program and navigate to attendance
    const attendanceLink = page.locator('[data-testid="view-attendance-link"]').first()
    if (await attendanceLink.count() > 0) {
      await attendanceLink.click()
      await expect(page.locator('[data-testid="attendance-list"]')).toBeVisible()
      await expect(page.locator('[data-testid="attendance-summary"]')).toBeVisible()
    }
  })
})
