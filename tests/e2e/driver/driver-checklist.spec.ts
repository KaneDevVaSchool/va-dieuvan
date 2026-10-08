import { test, expect } from '@playwright/test'
import { loginWithToken } from '../../helpers/auth'

/**
 * Checklist Daily: tài xế tạo phiếu, tick toàn bộ, 1 mục NG (ghi chú + ảnh), nộp → kết luận KHÔNG ĐẠT.
 * Yêu cầu: tài khoản test-driver@va.test gắn hồ sơ tài xế, có ít nhất 1 xe.
 */
test.describe('Tài xế — checklist hằng ngày', () => {
  test.beforeEach(async ({ page }) => {
    await loginWithToken(page, 'driver')
  })

  test('dashboard hiển thị thẻ checklist hôm nay', async ({ page }) => {
    await page.goto('/driver')
    await expect(page.locator('[data-testid="driver-checklist-today-card"]')).toBeVisible()
  })

  test('làm và nộp checklist daily có mục không đạt', async ({ page }) => {
    await page.goto('/driver/checklists')
    await page.click('[data-testid="checklist-template-daily_safety"]')

    const vehicle = page.locator('[data-testid="checklist-start-vehicle"]')
    const firstVehicle = await vehicle.locator('option:not([disabled])').first().getAttribute('value')
    await vehicle.selectOption(firstVehicle ?? '')
    await page.click('[data-testid="checklist-start-submit"]')
    await page.waitForURL(/\/driver\/checklists\/\d+/)

    const items = page.locator('[data-testid^="checklist-item-"][data-testid$="-ok"]')
    await expect(items.first()).toBeVisible()
    const count = await items.count()
    expect(count).toBeGreaterThan(20)
    for (let i = 0; i < count; i++) {
      await items.nth(i).click()
    }

    // Mục đầu tiên (Phanh — Critical) chuyển sang Không đạt.
    const firstNg = page.locator('[data-testid^="checklist-item-"][data-testid$="-ng"]').first()
    await firstNg.click()
    const firstRow = page.locator('li[data-testid^="checklist-item-"]').first()
    await firstRow.locator('textarea').fill('Phanh tay yếu, đã báo điều phối đổi xe')
    await firstRow.locator('input[type="file"]').setInputFiles({
      name: 'phanh.png',
      mimeType: 'image/png',
      buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64'),
    })
    await expect(firstRow.locator('button[title="phanh.png"]')).toBeVisible()

    await page.check('[data-testid="checklist-confirm"]')
    await page.click('[data-testid="checklist-submit"]')

    await expect(page.locator('[data-testid="checklist-result-card"]')).toContainText('KHÔNG ĐỦ ĐIỀU KIỆN', { timeout: 20_000 })
  })
})
