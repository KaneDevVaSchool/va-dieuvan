<template>
  <div v-if="auth.user?.impersonation?.active" role="status" data-testid="impersonation-banner" class="sticky top-0 z-40 flex shrink-0 items-center justify-between gap-3 bg-amber-600 px-3.5 py-2 text-white shadow-sm print:hidden sm:px-5">
    <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-xs sm:text-sm">
      <span class="font-medium text-amber-100">Đang trải nghiệm với tư cách:</span>
      <span class="truncate font-bold">{{ auth.user.name }}</span>
      <span class="rounded-md bg-white/20 px-2 py-0.5 text-[11px] font-semibold">{{ auth.user.impersonation.role?.label }}</span>
    </div>
    <button type="button" :disabled="leaving" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 shadow-sm hover:bg-amber-50 disabled:opacity-60" @click="leave">{{ leaving ? 'Đang thoát…' : 'Thoát giả lập' }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../store'
import { confirmAction } from '../../composables/useConfirm'
import { showAppErrorFromApi } from '../../composables/appMessage'

const auth = useAuthStore()
const leaving = ref(false)
async function leave() {
  if (leaving.value) return
  const confirmed = await confirmAction({
    title: 'Thoát phiên giả lập',
    message: `Kết thúc phiên giả lập "${auth.user.name}" và quay lại tài khoản Super Admin?`,
    confirmLabel: 'Thoát giả lập',
  })
  if (!confirmed) return
  leaving.value = true
  try {
    const home = await auth.leaveImpersonation()
    window.location.replace(home)
  } catch (error) {
    showAppErrorFromApi(error, 'Không khôi phục được phiên đăng nhập.')
    window.location.replace('/login')
  }
}
</script>
