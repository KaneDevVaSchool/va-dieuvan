import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../store'
import { showAppSuccess } from './appMessage'

/**
 * Đăng xuất API + xóa phiên, về trang gốc `/` (đăng nhập) và báo thành công.
 */
export function useAuthLogout() {
  const auth = useAuthStore()
  const router = useRouter()
  const { t } = useI18n()

  async function performLogout() {
    await auth.logout()
    await router.replace({ path: '/' })
    showAppSuccess(t('app.logout_success'), t('app.logout_success_title'))
  }

  return { performLogout }
}
