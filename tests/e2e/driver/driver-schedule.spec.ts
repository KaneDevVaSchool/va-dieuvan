import { test, expect } from '@playwright/test'
import { loginAs } from '../../helpers/auth'

test.describe('Giao diện tài xế — lịch chuyến', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'driver')
    await page.goto('/driver')
  })

  test('tài xế thấy dashboard sau khi đăng nhập', async ({ page }) => {
    await expect(page).toHaveURL(/\/driver/)
    await expect(page.locator('[data-testid="driver-header"]')).toBeVisible()
  })

  test('hiển thị danh sách chuyến sắp tới', async ({ page }) => {
    await expect(page.locator('[data-testid="driver-schedule"]')).toBeVisible()
  })

  test('không truy cập được trang dispatch (403)', async ({ page }) => {
    await page.goto('/trips')
    await expect(page).not.toHaveURL('/trips')
  })
})

test.describe('Giao diện tài xế — TP attendance', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, 'driver')
  })

  test('tài xế xem danh sách ngày TP', async ({ page }) => {
    await page.goto('/driver/tp')
    await expect(page.locator('[data-testid="tp-days-list"]')).toBeVisible()
  })
})
