import { Page } from '@playwright/test'

export type UserRole = 'dispatcher' | 'driver' | 'admin' | 'department_head' | 'internal_user'

const TEST_USERS: Record<UserRole, { email: string; password: string }> = {
  dispatcher: { email: 'test-dispatcher@va.test', password: 'password' },
  driver: { email: 'test-driver@va.test', password: 'password' },
  admin: { email: 'test-admin@va.test', password: 'password' },
  department_head: { email: 'test-dept-head@va.test', password: 'password' },
  internal_user: { email: 'test-internal@va.test', password: 'password' },
}

export async function loginAs(page: Page, role: UserRole): Promise<void> {
  const user = TEST_USERS[role]

  await page.goto('/login')
  await page.fill('[data-testid="email-input"]', user.email)
  await page.fill('[data-testid="password-input"]', user.password)
  await page.click('[data-testid="login-btn"]')
  await page.waitForURL(/\/(dashboard|driver|portal)/)
}

export async function logout(page: Page): Promise<void> {
  await page.click('[data-testid="user-menu"]')
  await page.click('[data-testid="logout-btn"]')
  await page.waitForURL('/login')
}

export async function apiLogin(page: Page, role: UserRole): Promise<string> {
  const user = TEST_USERS[role]

  const response = await page.request.post('/api/login', {
    data: { email: user.email, password: user.password },
  })

  const body = await response.json()
  return body.token
}
