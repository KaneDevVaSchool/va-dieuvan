import { test, expect } from '@playwright/test'
import { loginWithToken } from '../../helpers/auth'

/**
 * Hồ sơ chi phí: tài xế lập Phiếu đề xuất → nộp → thấy nút tải PDF / Excel và upload scan.
 */
test.describe('Tài xế — hồ sơ đề xuất / thanh toán', () => {
  test.beforeEach(async ({ page }) => {
    await loginWithToken(page, 'driver')
  })

  test('trang chi phí có lối vào hồ sơ', async ({ page }) => {
    await page.goto('/driver/costs')
    await page.click('[data-testid="driver-costs-expense-link"]')
    await expect(page).toHaveURL(/\/driver\/expenses/)
  })

  test('lập và nộp phiếu đề xuất', async ({ page }) => {
    await page.goto('/driver/expenses')
    await page.click('[data-testid="expense-create-proposal"]')
    await page.waitForURL(/\/driver\/expenses\/new/)

    await page.getByLabel('Mục đích sử dụng').fill('Thay lốp trước xe')
    const line = page.locator('li').filter({ hasText: 'Dòng 1' })
    await line.getByLabel('Danh mục').fill('Lốp xe 215/70R15')
    await line.getByLabel('Đơn giá').fill('2500000')

    await page.click('[data-testid="expense-save"]')
    await page.waitForURL(/\/driver\/expenses\/\d+$/)
    await expect(page.getByText(/DX-\d{6}-\d+/).first()).toBeVisible()
    await page.getByRole('button', { name: 'Đóng', exact: true }).last().click()

    await page.click('[data-testid="expense-submit"]')
    await page.getByRole('button', { name: 'Đồng ý' }).click()
    await page.getByRole('button', { name: 'Đóng', exact: true }).last().click()
    await expect(page.getByText('Đã nộp').first()).toBeVisible()
    await expect(page.getByText('Upload bản scan có chữ ký').first()).toBeVisible()
  })
})
