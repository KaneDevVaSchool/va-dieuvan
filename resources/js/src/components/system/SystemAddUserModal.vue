<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
      role="dialog"
      aria-modal="true"
      aria-labelledby="add-user-title"
      @click.self="$emit('close')"
    >
      <div
        class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-md flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900"
        @click.stop
      >
        <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
          <h2 id="add-user-title" class="text-base font-semibold text-slate-900 dark:text-white">Thêm người dùng</h2>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Dành cho người không có trong CMS. Người dùng đăng nhập bằng
            <strong class="font-semibold text-slate-700 dark:text-slate-300">tài khoản Google</strong> với đúng email bên dưới.
          </p>
        </div>

        <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
          <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              Họ tên *
              <input
                v-model="form.name"
                type="text"
                required
                maxlength="255"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                data-testid="add-user-name"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              Email (Google) *
              <input
                v-model="form.email"
                type="email"
                required
                maxlength="255"
                placeholder="vd. ten@vaschools.edu.vn hoặc ten@gmail.com"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                data-testid="add-user-email"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                Mã nhân viên
                <input
                  v-model="form.employee_code"
                  type="text"
                  maxlength="50"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                Số điện thoại
                <input
                  v-model="form.phone"
                  type="tel"
                  maxlength="32"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              Vai trò
              <select
                v-model="form.role_id"
                class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                data-testid="add-user-role"
              >
                <option value="">— Chưa gán (gán sau) —</option>
                <option v-for="r in roles" :key="r.id" :value="String(r.id)">{{ r.display_name || r.name }}</option>
              </select>
            </label>
            <p v-if="error" class="text-xs text-rose-600" role="alert">{{ error }}</p>
          </div>
          <div class="flex shrink-0 gap-2 border-t border-slate-200 p-4 dark:border-slate-700">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="$emit('close')"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
              :disabled="saving"
              data-testid="add-user-submit"
            >
              {{ saving ? 'Đang lưu…' : 'Thêm người dùng' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { createUser } from '../../api/admin'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const props = defineProps({
  open: { type: Boolean, default: false },
  /** Danh sách vai trò (đã có sẵn trên trang Phân vai trò). */
  roles: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'created'])

const saving = ref(false)
const error = ref('')
const form = ref(emptyForm())

function emptyForm() {
  return { name: '', email: '', employee_code: '', phone: '', role_id: '' }
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    form.value = emptyForm()
    error.value = ''
  },
)

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const f = form.value
    const created = await createUser({
      name: f.name,
      email: f.email,
      employee_code: f.employee_code || null,
      phone: f.phone || null,
      role_id: f.role_id ? Number(f.role_id) : null,
    })
    showAppSuccess(`Đã thêm ${created.user.name} (${created.user.email}).`)
    emit('created', created.user)
  } catch (e) {
    const st = e?.response?.status
    if (st === 422) {
      const errs = e.response.data?.errors
      error.value =
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        e.response.data?.message ||
        'Dữ liệu không hợp lệ.'
    } else if (st === 403) {
      error.value = e.response.data?.message || 'Bạn không có quyền thực hiện thao tác này.'
    } else {
      showAppErrorFromApi(e, 'Không thêm được người dùng.')
    }
  } finally {
    saving.value = false
  }
}
</script>
