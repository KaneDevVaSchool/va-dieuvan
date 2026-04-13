<template>
  <div class="mx-auto max-w-6xl px-4 py-6 sm:px-5">
    <header class="mb-6">
      <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        Hồ sơ tài khoản
      </h1>
      <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
        Dữ liệu <span class="font-mono">user_info</span> trên
        <code class="rounded bg-slate-100 px-1 text-xs dark:bg-slate-800">cms_db_staging</code>
        (theo email đăng nhập), khớp cấu trúc
        <span class="font-mono text-xs">scripts/user_info.sql</span>.
      </p>
    </header>

    <div v-if="!auth.user" class="rounded-xl border border-slate-200/90 bg-white p-8 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
      Chưa có dữ liệu người dùng.
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-12 lg:items-start">
      <aside class="lg:col-span-4">
        <div
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80"
        >
          <div
            class="h-24 bg-gradient-to-br from-va-800 via-va-700 to-va-900 dark:from-va-950 dark:via-va-900 dark:to-slate-950"
            aria-hidden="true"
          />
          <div class="relative -mt-12 flex flex-col items-center px-4 pb-5 pt-0">
            <UserAvatar
              class="!h-24 !w-24 !text-2xl ring-4 ring-white dark:ring-slate-900"
              :name="auth.user.name"
              :email="auth.user.email"
              :avatar-url="auth.user.avatar_url"
              :title="auth.user.name ?? ''"
              size="lg"
              ring-prominent
            />
            <p class="mt-3 text-center text-base font-semibold text-slate-900 dark:text-slate-100">
              {{ auth.user.name }}
            </p>
            <p class="mt-0.5 max-w-full truncate text-center text-xs text-slate-500 dark:text-slate-400">
              {{ auth.user.email }}
            </p>
            <div class="mt-3 flex max-w-full flex-wrap justify-center gap-1.5">
              <span
                v-for="role in auth.user.roles ?? []"
                :key="role.id"
                class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                {{ role.display_name ?? role.name }}
              </span>
              <span
                v-if="!(auth.user.roles ?? []).length"
                class="text-[11px] text-slate-400"
              >
                —
              </span>
            </div>
            <div class="mt-5 flex w-full flex-col gap-2 sm:flex-row sm:justify-center">
              <Button :loading="refreshing" type="button" class="w-full sm:w-auto" @click="refresh">
                Làm mới từ máy chủ
              </Button>
              <RouterLink
                class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto"
                to="/hub"
              >
                Trung tâm vận hành
              </RouterLink>
            </div>
          </div>
        </div>
      </aside>

      <div class="space-y-6 lg:col-span-8">
        <section
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80"
        >
          <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700/80">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Thông tin tài khoản
            </h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[280px] text-sm">
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <tr>
                  <th
                    scope="row"
                    class="w-[38%] whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Họ tên
                  </th>
                  <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                    {{ auth.user.name }}
                  </td>
                </tr>
                <tr>
                  <th
                    scope="row"
                    class="whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Email
                  </th>
                  <td class="break-all px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                    {{ auth.user.email }}
                  </td>
                </tr>
                <tr>
                  <th
                    scope="row"
                    class="align-top whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Vai trò
                  </th>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="role in auth.user.roles ?? []"
                        :key="role.id"
                        class="rounded-full bg-va-50 px-2.5 py-0.5 text-xs font-medium text-va-900 dark:bg-va-950/50 dark:text-va-100"
                      >
                        {{ role.display_name ?? role.name }}
                      </span>
                      <span v-if="!(auth.user.roles ?? []).length" class="text-slate-400">—</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80"
        >
          <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700/80">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Bảng <span class="font-mono text-xs">user_info</span> (CMS) — chỉ đọc
            </h2>
            <p v-if="!auth.user.cms_user_info" class="mt-1 text-xs text-amber-700 dark:text-amber-400">
              Chưa có dòng trên CMS (hoặc chưa kết nối CMS_DB_*). Form bên dưới sẽ tạo bản ghi khi lưu.
            </p>
          </div>
          <div v-if="auth.user.cms_user_info" class="overflow-x-auto">
            <table class="w-full min-w-[280px] text-sm">
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <tr v-for="key in cmsReadonlyKeys" :key="key">
                  <th
                    scope="row"
                    class="w-[38%] whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    {{ key }}
                  </th>
                  <td class="px-4 py-3 text-slate-900 dark:text-slate-100">
                    {{ formatCmsCell(auth.user.cms_user_info[key]) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
            Chưa có snapshot — sau khi lưu thành công, bảng này sẽ hiển thị dữ liệu từ CMS.
          </p>

          <div class="border-t border-slate-100 px-4 py-4 dark:border-slate-700/80">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              Chỉnh sửa (ghi vào CMS)
            </h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <template v-for="field in cmsFields" :key="field.key">
                <div v-if="field.type === 'textarea'" class="md:col-span-2">
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ field.label }}</label>
                  <textarea
                    v-model="form[field.key]"
                    rows="3"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-va-600 focus:outline-none focus:ring-2 focus:ring-va-600/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  />
                </div>
                <div v-else-if="field.type === 'gender'">
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ field.label }}</label>
                  <select
                    v-model="form[field.key]"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-va-600 focus:outline-none focus:ring-2 focus:ring-va-600/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  >
                    <option :value="null">—</option>
                    <option :value="1">Nam</option>
                    <option :value="0">Nữ</option>
                  </select>
                </div>
                <div v-else-if="field.type === 'number'">
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ field.label }}</label>
                  <input
                    v-model="form[field.key]"
                    type="number"
                    min="0"
                    step="1"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-va-600 focus:outline-none focus:ring-2 focus:ring-va-600/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  />
                </div>
                <div v-else-if="field.type === 'date'">
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ field.label }}</label>
                  <input
                    v-model="form[field.key]"
                    type="date"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-va-600 focus:outline-none focus:ring-2 focus:ring-va-600/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  />
                </div>
                <div v-else>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ field.label }}</label>
                  <input
                    v-model="form[field.key]"
                    type="text"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-va-600 focus:outline-none focus:ring-2 focus:ring-va-600/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  />
                </div>
              </template>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-700">
            <Button :loading="saving" type="button" @click="save">
              Lưu thay đổi
            </Button>
            <p v-if="msg" class="text-sm text-emerald-700 dark:text-emerald-400">
              {{ msg }}
            </p>
            <p v-if="err" class="text-sm text-rose-600 dark:text-rose-400">
              {{ err }}
            </p>
          </div>
          <p v-if="auth.user.cms_sync_warning" class="border-t border-amber-100 bg-amber-50/80 px-4 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
            {{ auth.user.cms_sync_warning }}
          </p>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Button from '../../components/ui/Button.vue'
import UserAvatar from '../../components/branding/UserAvatar.vue'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'

/** Khớp scripts/user_info.sql — employee_code → cột code trên API */
const cmsFields = [
  { key: 'employee_code', label: 'Mã nhân viên (code)', type: 'text' },
  { key: 'phone', label: 'Số điện thoại', type: 'text' },
  { key: 'gender', label: 'Giới tính (1 Nam, 0 Nữ)', type: 'gender' },
  { key: 'birthdate', label: 'Ngày sinh', type: 'date' },
  { key: 'birth_place', label: 'Nơi sinh', type: 'text' },
  { key: 'national', label: 'Quốc tịch / dân tộc', type: 'text' },
  { key: 'religion', label: 'Tôn giáo', type: 'text' },
  { key: 'hometown', label: 'Quê quán', type: 'text' },
  { key: 'identity', label: 'CMND/CCCD', type: 'text' },
  { key: 'identity_date', label: 'Ngày cấp CMND/CCCD', type: 'date' },
  { key: 'identity_place', label: 'Nơi cấp CMND/CCCD', type: 'text' },
  { key: 'tax_code', label: 'Mã số thuế', type: 'text' },
  { key: 'social_insurance_number', label: 'Số BHXH', type: 'text' },
  { key: 'health_insurance_code', label: 'Mã BHYT', type: 'text' },
  { key: 'unemployment_insurance_number', label: 'Số BHTN', type: 'text' },
  { key: 'address', label: 'Địa chỉ', type: 'text' },
  { key: 'household', label: 'Hộ khẩu', type: 'text' },
  { key: 'bank_account', label: 'Số tài khoản', type: 'text' },
  { key: 'bank', label: 'Ngân hàng', type: 'text' },
  { key: 'start_working_date', label: 'Ngày bắt đầu làm việc', type: 'date' },
  { key: 'working_place', label: 'Nơi làm việc', type: 'text' },
  { key: 'company_name', label: 'Tên công ty', type: 'text' },
  { key: 'department_name', label: 'Phòng ban', type: 'text' },
  { key: 'unit_name', label: 'Đơn vị', type: 'text' },
  { key: 'headquarter_name', label: 'Trụ sở', type: 'text' },
  { key: 'position_name', label: 'Chức vụ', type: 'text' },
  { key: 'concurrent_position_name', label: 'Chức vụ kiêm nhiệm', type: 'text' },
  { key: 'department_id', label: 'department_id', type: 'number' },
  { key: 'company_id', label: 'company_id', type: 'number' },
  { key: 'note', label: 'Ghi chú', type: 'textarea' },
]

const auth = useAuthStore()
const refreshing = ref(false)
const saving = ref(false)
const msg = ref('')
const err = ref('')

function emptyForm() {
  const o = {}
  for (const f of cmsFields) {
    if (f.type === 'gender') o[f.key] = null
    else if (f.type === 'number') o[f.key] = ''
    else o[f.key] = ''
  }
  return o
}

const form = reactive(emptyForm())

const cmsReadonlyKeys = computed(() => {
  const ci = auth.user?.cms_user_info
  if (!ci || typeof ci !== 'object') return []
  return Object.keys(ci).sort((a, b) => a.localeCompare(b))
})

function toInputDate(val) {
  if (val == null || val === '') return ''
  const s = String(val)
  return s.length >= 10 ? s.slice(0, 10) : ''
}

function syncFormFromUser() {
  const u = auth.user
  if (!u) return
  const ci = u.cms_user_info || {}
  for (const f of cmsFields) {
    if (f.key === 'employee_code') {
      form[f.key] = ci.code != null ? String(ci.code) : (u.employee_code ?? '')
      continue
    }
    if (f.type === 'gender') {
      const g = ci.gender
      if (g === null || g === undefined || g === '') form[f.key] = null
      else form[f.key] = Number(g) === 1 ? 1 : 0
      continue
    }
    if (f.type === 'date') {
      form[f.key] = toInputDate(ci[f.key])
      continue
    }
    if (f.type === 'number') {
      const n = ci[f.key]
      form[f.key] = n != null && n !== '' ? String(n) : ''
      continue
    }
    if (f.type === 'textarea') {
      form[f.key] = ci[f.key] != null ? String(ci[f.key]) : ''
      continue
    }
    if (f.key === 'phone') {
      form[f.key] = ci.phone != null ? String(ci.phone) : (u.phone ?? '')
      continue
    }
    const v = ci[f.key]
    form[f.key] = v != null ? String(v) : ''
  }
}

watch(() => auth.user, syncFormFromUser, { immediate: true, deep: true })

function formatCmsCell(val) {
  if (val === null || val === undefined) return '—'
  if (typeof val === 'object') return JSON.stringify(val)
  return String(val)
}

function buildPayload() {
  const payload = {}
  for (const f of cmsFields) {
    const raw = form[f.key]
    if (f.type === 'gender') {
      payload[f.key] = raw === null || raw === '' ? null : raw
      continue
    }
    if (f.type === 'number') {
      const s = String(raw).trim()
      payload[f.key] = s === '' ? null : Number(s)
      continue
    }
    if (f.type === 'date') {
      const s = String(raw).trim()
      payload[f.key] = s === '' ? null : s
      continue
    }
    const s = String(raw ?? '').trim()
    payload[f.key] = s === '' ? null : s
  }
  return payload
}

async function refresh() {
  msg.value = ''
  err.value = ''
  refreshing.value = true
  try {
    await auth.fetchMe()
    syncFormFromUser()
    msg.value = 'Đã cập nhật.'
  } catch (e) {
    err.value = formatApiError(e, 'Không tải được hồ sơ.')
  } finally {
    refreshing.value = false
  }
}

async function save() {
  msg.value = ''
  err.value = ''
  saving.value = true
  try {
    await auth.patchProfile(buildPayload())
    msg.value = 'Đã lưu.'
  } catch (e) {
    err.value = formatApiError(e, 'Không lưu được.')
  } finally {
    saving.value = false
  }
}
</script>
