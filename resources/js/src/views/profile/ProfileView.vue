<template>
  <div class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6">
    <header class="mb-6 border-b border-slate-200 pb-4 dark:border-slate-700">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Hồ sơ tài khoản</h1>
      <p class="mt-1 text-sm text-slate-500">Thông tin tài khoản và hồ sơ nhân sự HCNS</p>
    </header>
    <p v-if="!auth.user" class="text-sm text-slate-500">Chưa có dữ liệu người dùng.</p>
    <div v-else class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
      <aside class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 text-center dark:border-slate-700 dark:bg-slate-900">
          <UserAvatar class="mx-auto" :name="auth.user.name" :email="auth.user.email" :avatar-url="auth.user.avatar_url" size="lg" />
          <h2 class="mt-3 font-semibold text-slate-900 dark:text-white">{{ auth.user.name }}</h2>
          <p class="mt-1 break-all text-sm text-slate-500">{{ auth.user.email }}</p>
          <p v-if="auth.user.work_info?.code" class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ auth.user.work_info.code }}</p>
          <div class="mt-3 flex flex-wrap justify-center gap-2">
            <span v-for="role in auth.user.roles ?? []" :key="role.id" class="rounded-full bg-sky-50 px-2 py-1 text-xs text-sky-800 dark:bg-sky-950 dark:text-sky-200">
              {{ role.display_name || role.name }}
            </span>
          </div>
          <Button class="mt-4 w-full" type="button" :loading="refreshing" @click="refresh">Làm mới từ máy chủ</Button>
        </section>
      </aside>
      <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
          <h2 class="font-semibold text-slate-900 dark:text-white">Thông tin công việc</h2>
          <p class="mt-1 text-xs text-slate-500">Thông tin được quản lý qua hồ sơ HCNS. Liên hệ nhân sự khi cần điều chỉnh.</p>
          <p v-if="!auth.user.work_info?.code" class="mt-3 text-sm text-amber-700 dark:text-amber-400">Chưa có hồ sơ HCNS được liên kết với tài khoản.</p>
          <dl class="mt-4 grid gap-x-6 sm:grid-cols-2">
            <div v-for="field in workFields" :key="field.key" class="border-b border-slate-100 py-3 dark:border-slate-800">
              <dt class="text-xs text-slate-500">{{ field.label }}</dt>
              <dd class="mt-1 break-words text-sm text-slate-900 dark:text-slate-100">{{ auth.user.work_info?.[field.key] || '—' }}</dd>
            </div>
          </dl>
        </section>
        <form class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900" @submit.prevent="save">
          <h2 class="font-semibold text-slate-900 dark:text-white">Thông tin liên hệ</h2>
          <label for="account-phone" class="mt-4 block text-sm text-slate-600 dark:text-slate-300">Số điện thoại liên hệ</label>
          <input id="account-phone" v-model="phone" type="tel" maxlength="255" class="mt-2 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 text-slate-900 dark:border-slate-600 dark:text-white" />
          <Button class="mt-4" type="submit" :loading="saving">Lưu thay đổi</Button>
        </form>
        <p v-if="message" role="status" class="text-sm text-emerald-700 dark:text-emerald-400">{{ message }}</p>
        <p v-if="error" role="alert" class="text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Button from '../../components/ui/Button.vue'
import UserAvatar from '../../components/branding/UserAvatar.vue'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'

const auth = useAuthStore()
const phone = ref('')
const saving = ref(false)
const refreshing = ref(false)
const message = ref('')
const error = ref('')
const workFields = [
  { key: 'code', label: 'Mã nhân viên' },
  { key: 'company_name', label: 'Pháp nhân' },
  { key: 'department_name', label: 'Phòng ban' },
  { key: 'unit_name', label: 'Bộ phận' },
  { key: 'headquarter_name', label: 'Trụ sở' },
  { key: 'working_place', label: 'Nơi làm việc' },
  { key: 'position_name', label: 'Chức danh' },
  { key: 'concurrent_position_name', label: 'Chức danh kiêm nhiệm' },
]

watch(() => auth.user, (user) => { phone.value = user?.phone ?? '' }, { immediate: true })

async function refresh() {
  refreshing.value = true
  error.value = ''
  message.value = ''
  try {
    await auth.fetchMe()
    message.value = 'Đã cập nhật.'
  } catch (e) {
    error.value = formatApiError(e, 'Không tải được hồ sơ.')
  } finally {
    refreshing.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  message.value = ''
  try {
    await auth.patchProfile({ phone: phone.value.trim() || null })
    message.value = 'Đã lưu thông tin liên hệ.'
  } catch (e) {
    error.value = formatApiError(e, 'Không lưu được hồ sơ.')
  } finally {
    saving.value = false
  }
}
</script>
