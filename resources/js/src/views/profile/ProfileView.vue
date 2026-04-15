<template>
  <div class="mx-auto max-w-[1100px] px-4 py-6 sm:px-5">
    <header class="mb-6">
      <h1 class="text-xl font-medium tracking-tight text-slate-900 dark:text-slate-100">
        Hồ sơ tài khoản
      </h1>
      <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
        Quản lý thông tin cá nhân và tài khoản của bạn
      </p>
    </header>

    <div v-if="!auth.user" class="rounded-xl border border-slate-200/90 bg-white p-8 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
      Chưa có dữ liệu người dùng.
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start">
      <!-- Sidebar -->
      <aside class="flex flex-col gap-5">
        <div
          class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/70 dark:shadow-none"
        >
          <div class="px-5 pb-5 pt-6 text-center">
            <UserAvatar
              class="!h-[72px] !w-[72px] !text-[22px] !font-medium !ring-4 !ring-white !bg-va-700 !text-white dark:!ring-slate-900 dark:!bg-va-800"
              :name="auth.user.name"
              :email="auth.user.email"
              :avatar-url="auth.user.avatar_url"
              :title="auth.user.name ?? ''"
              size="lg"
              ring-prominent
            />
            <p class="mt-3 text-[15px] font-medium text-slate-900 dark:text-slate-100">
              {{ auth.user.name }}
            </p>
            <p class="mt-0.5 max-w-full truncate text-center text-xs text-slate-500 dark:text-slate-400">
              {{ auth.user.email }}
            </p>
            <div class="mt-2 flex max-w-full flex-wrap justify-center gap-1.5">
              <span
                v-for="(text, idx) in sidebarProfileBadges"
                :key="`${text}-${idx}`"
                class="rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-medium text-sky-900 dark:bg-sky-950/50 dark:text-sky-100"
              >
                {{ text }}
              </span>
              <span v-if="!sidebarProfileBadges.length" class="text-[11px] text-slate-400">
                —
              </span>
            </div>
            <p
              v-if="employeeCodeDisplay"
              class="mt-2 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
            >
              {{ employeeCodeDisplay }}
            </p>
            <div class="mt-4 flex w-full flex-col gap-2">
              <Button :loading="refreshing" type="button" class="w-full" @click="refresh">
                Làm mới từ máy chủ
              </Button>
              <RouterLink
                class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                to="/dispatcher"
              >
                Bảng điều vận
              </RouterLink>
            </div>
          </div>
        </div>

        <div
          class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/70 dark:shadow-none"
        >
          <h2 class="mb-3 text-[13px] font-medium text-slate-600 dark:text-slate-300">
            Thông tin nhanh
          </h2>
          <div v-if="quickInfoRows.length" class="flex flex-col">
            <div
              v-for="row in quickInfoRows"
              :key="row.label"
              class="flex items-center justify-between gap-3 border-b border-slate-100/90 py-2.5 text-sm last:border-b-0 dark:border-slate-700/60"
            >
              <span class="shrink-0 text-slate-500 dark:text-slate-400">{{ row.label }}</span>
              <span class="min-w-0 text-right font-medium text-slate-800 dark:text-slate-100">{{ row.value }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500 dark:text-slate-400">
            Chưa có dữ liệu CMS — lưu hồ sơ để đồng bộ.
          </p>
        </div>
      </aside>

      <!-- Main -->
      <div class="flex min-w-0 flex-col gap-5">
        <div
          class="flex flex-wrap gap-1 rounded-xl bg-slate-100/90 p-1 dark:bg-slate-800/60"
          role="tablist"
          aria-label="Nhóm thông tin hồ sơ"
        >
          <button
            v-for="tab in profileTabs"
            :key="tab.id"
            type="button"
            role="tab"
            :aria-selected="activeTab === tab.id"
            class="min-w-0 flex-1 rounded-lg px-3 py-2 text-center text-[13px] transition-colors duration-150 sm:flex-none sm:px-4"
            :class="
              activeTab === tab.id
                ? 'border border-slate-200/60 bg-white font-medium text-slate-900 shadow-sm dark:border-slate-600/80 dark:bg-slate-900 dark:text-slate-100'
                : 'border border-transparent text-slate-500 hover:bg-white/50 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-slate-200'
            "
            @click="activeTab = tab.id"
          >
            {{ tab.label }}
          </button>
        </div>

        <section
          class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/70 dark:shadow-none"
        >
          <div class="border-b border-slate-100/90 px-5 py-4 dark:border-slate-700/60">
            <h2 class="text-[15px] font-medium text-slate-900 dark:text-slate-100">
              Chỉnh sửa hồ sơ
            </h2>
            <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
              <span class="font-medium text-rose-600 dark:text-rose-400" aria-hidden="true">*</span>
              Trường được đánh dấu nên điền đầy đủ để đồng bộ nhân sự, lương và bảo hiểm.
            </p>
            <p v-if="!auth.user.cms_user_info" class="mt-2 text-xs text-amber-800/90 dark:text-amber-400/95">
              Chưa có dòng trên CMS — lưu lần đầu sẽ tạo bản ghi.
            </p>
          </div>

          <div class="px-5 py-5">
            <template v-for="(block, bi) in activeTabSections" :key="block.section">
              <hr v-if="bi > 0" class="my-6 border-0 border-t border-slate-100 dark:border-slate-700/60" />
              <h3 class="mb-4 text-[13px] font-medium text-slate-600 dark:text-slate-300">
                {{ block.section }}
              </h3>
              <div class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2">
                <template v-for="field in block.fields" :key="field.key">
                  <div v-if="field.type === 'textarea'" class="md:col-span-2">
                    <ProfileFieldLabel :label="field.label" :important="field.important" />
                    <textarea
                      v-model="form[field.key]"
                      rows="3"
                      :placeholder="field.placeholder ?? ''"
                      class="field-control mt-1.5 min-h-[88px]"
                    />
                  </div>
                  <div v-else-if="field.type === 'gender'">
                    <ProfileFieldLabel :label="field.label" :important="field.important" />
                    <select v-model="form[field.key]" class="field-control mt-1.5">
                      <option :value="null">Chọn giới tính</option>
                      <option :value="1">Nam</option>
                      <option :value="0">Nữ</option>
                    </select>
                  </div>
                  <div v-else-if="field.type === 'number'">
                    <ProfileFieldLabel :label="field.label" :important="field.important" />
                    <input
                      v-model="form[field.key]"
                      type="number"
                      min="0"
                      step="1"
                      :placeholder="field.placeholder ?? ''"
                      class="field-control mt-1.5"
                    />
                  </div>
                  <div v-else-if="field.type === 'date'">
                    <ProfileFieldLabel :label="field.label" :important="field.important" />
                    <input
                      v-model="form[field.key]"
                      type="date"
                      :title="field.placeholder ?? ''"
                      class="field-control mt-1.5"
                    />
                    <p v-if="field.placeholder" class="mt-1 text-[11px] leading-snug text-slate-400 dark:text-slate-500">
                      {{ field.placeholder }}
                    </p>
                  </div>
                  <div v-else :class="field.fullWidth ? 'md:col-span-2' : ''">
                    <ProfileFieldLabel :label="field.label" :important="field.important" />
                    <input
                      v-model="form[field.key]"
                      type="text"
                      :placeholder="field.placeholder ?? ''"
                      class="field-control mt-1.5"
                    />
                  </div>
                </template>
              </div>
            </template>
          </div>

          <div class="flex flex-wrap items-center gap-3 border-t border-slate-100/90 px-5 py-4 dark:border-slate-700/60">
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
import ProfileFieldLabel from './ProfileFieldLabel.vue'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'

const profileTabs = [
  { id: 'personal', label: 'Thông tin cá nhân' },
  { id: 'documents', label: 'Giấy tờ & Bảo hiểm' },
  { id: 'finance', label: 'Tài chính' },
  { id: 'work', label: 'Công việc' },
]

/** Khớp scripts/user_info.sql — employee_code → cột code trên API; tab + section + placeholder + important (*) */
const cmsFields = [
  {
    key: 'gender',
    label: 'Giới tính',
    type: 'gender',
    tab: 'personal',
    section: 'Thông tin cơ bản',
    important: true,
  },
  {
    key: 'birthdate',
    label: 'Ngày sinh',
    type: 'date',
    tab: 'personal',
    section: 'Thông tin cơ bản',
    important: true,
    placeholder: 'Chọn ngày theo giấy khai sinh hoặc CCCD',
  },
  {
    key: 'birth_place',
    label: 'Nơi sinh',
    type: 'text',
    tab: 'personal',
    section: 'Thông tin cơ bản',
    placeholder: 'Ví dụ: TP. Hồ Chí Minh',
  },
  {
    key: 'national',
    label: 'Quốc tịch / Dân tộc',
    type: 'text',
    tab: 'personal',
    section: 'Thông tin cơ bản',
    placeholder: 'Ví dụ: Việt Nam / Kinh',
  },
  {
    key: 'religion',
    label: 'Tôn giáo',
    type: 'text',
    tab: 'personal',
    section: 'Thông tin cơ bản',
    placeholder: 'Nếu không có, có thể để trống',
  },
  {
    key: 'hometown',
    label: 'Quê quán',
    type: 'text',
    tab: 'personal',
    section: 'Thông tin cơ bản',
    placeholder: 'Theo hộ khẩu thường trú hoặc CCCD',
  },
  {
    key: 'address',
    label: 'Địa chỉ',
    type: 'text',
    tab: 'personal',
    section: 'Địa chỉ & liên hệ',
    fullWidth: true,
    important: true,
    placeholder: 'Địa chỉ liên hệ / nhận thư hiện tại',
  },
  {
    key: 'household',
    label: 'Hộ khẩu',
    type: 'text',
    tab: 'personal',
    section: 'Địa chỉ & liên hệ',
    fullWidth: true,
    placeholder: 'Địa chỉ trên sổ hộ khẩu hoặc ghi trong CCCD',
  },
  {
    key: 'phone',
    label: 'Số điện thoại',
    type: 'text',
    tab: 'personal',
    section: 'Địa chỉ & liên hệ',
    important: true,
    placeholder: 'Ví dụ: 0901 234 567',
  },

  {
    key: 'identity',
    label: 'Số CMND/CCCD',
    type: 'text',
    tab: 'documents',
    section: 'Giấy tờ tùy thân',
    important: true,
    placeholder: '12 số (CCCD mới) hoặc số CMND cũ',
  },
  {
    key: 'identity_date',
    label: 'Ngày cấp',
    type: 'date',
    tab: 'documents',
    section: 'Giấy tờ tùy thân',
    placeholder: 'Ngày cấp ghi trên mặt sau CCCD',
  },
  {
    key: 'identity_place',
    label: 'Nơi cấp',
    type: 'text',
    tab: 'documents',
    section: 'Giấy tờ tùy thân',
    placeholder: 'Ví dụ: Cục Cảnh sát ĐKQL cư trú và DLQG về dân cư',
  },
  {
    key: 'tax_code',
    label: 'Mã số thuế',
    type: 'text',
    tab: 'documents',
    section: 'Giấy tờ tùy thân',
    important: true,
    placeholder: '10 hoặc 13 số (nếu có)',
  },
  {
    key: 'social_insurance_number',
    label: 'Số BHXH',
    type: 'text',
    tab: 'documents',
    section: 'Bảo hiểm',
    important: true,
    placeholder: 'Số trên thẻ BHXH',
  },
  {
    key: 'health_insurance_code',
    label: 'Mã BHYT',
    type: 'text',
    tab: 'documents',
    section: 'Bảo hiểm',
    important: true,
    placeholder: 'Mã trên thẻ BHYT',
  },
  {
    key: 'unemployment_insurance_number',
    label: 'Số BHTN',
    type: 'text',
    tab: 'documents',
    section: 'Bảo hiểm',
    placeholder: 'Nếu đã tham gia BHTN',
  },

  {
    key: 'bank_account',
    label: 'Số tài khoản',
    type: 'text',
    tab: 'finance',
    section: 'Tài khoản ngân hàng',
    important: true,
    placeholder: 'Chỉ số tài khoản, không ghi chữ',
  },
  {
    key: 'bank',
    label: 'Ngân hàng',
    type: 'text',
    tab: 'finance',
    section: 'Tài khoản ngân hàng',
    important: true,
    placeholder: 'Ví dụ: Vietcombank — Chi nhánh…',
  },

  {
    key: 'employee_code',
    label: 'Mã nhân viên',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    important: true,
    placeholder: 'Mã trên hợp đồng / hệ thống nhân sự',
  },
  {
    key: 'start_working_date',
    label: 'Ngày bắt đầu làm việc',
    type: 'date',
    tab: 'work',
    section: 'Thông tin công tác',
    placeholder: 'Theo quyết định tuyển dụng hoặc hợp đồng',
  },
  {
    key: 'working_place',
    label: 'Nơi làm việc',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    important: true,
    placeholder: 'Ví dụ: HCM, Hà Nội…',
  },
  {
    key: 'company_name',
    label: 'Tên công ty',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    placeholder: 'Tên pháp lý đơn vị công tác',
  },
  {
    key: 'department_name',
    label: 'Phòng ban',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    important: true,
    placeholder: 'Ví dụ: Phòng Công nghệ thông tin',
  },
  {
    key: 'unit_name',
    label: 'Đơn vị',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    placeholder: 'Khối / khối học / bộ phận (nếu có)',
  },
  {
    key: 'headquarter_name',
    label: 'Trụ sở',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    placeholder: 'Ví dụ: Tân Bình, Cầu Giấy…',
  },
  {
    key: 'position_name',
    label: 'Chức vụ',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    important: true,
    placeholder: 'Chức danh chính thức',
  },
  {
    key: 'concurrent_position_name',
    label: 'Chức vụ kiêm nhiệm',
    type: 'text',
    tab: 'work',
    section: 'Thông tin công tác',
    placeholder: 'Nếu có',
  },
]

const auth = useAuthStore()
const activeTab = ref('personal')
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

const activeTabSections = computed(() => {
  const tab = activeTab.value
  const map = new Map()
  for (const f of cmsFields) {
    if (f.tab !== tab) continue
    if (!map.has(f.section)) map.set(f.section, [])
    map.get(f.section).push(f)
  }
  return [...map.entries()].map(([section, fields]) => ({ section, fields }))
})

function isNonEmpty(val) {
  if (val === null || val === undefined) return false
  if (typeof val === 'object') return Object.keys(val).length > 0
  return String(val).trim() !== ''
}

const employeeCodeDisplay = computed(() => {
  const ci = auth.user?.cms_user_info
  const code = ci?.code != null ? String(ci.code) : auth.user?.employee_code
  return code && String(code).trim() !== '' ? String(code).trim() : ''
})

/** Vai trò app + chức vụ CMS (position_name) — nhiều user không có role Spatie nhưng có chức vụ trên user_info */
const sidebarProfileBadges = computed(() => {
  const u = auth.user
  if (!u) return []
  const ci = u.cms_user_info || {}
  const seen = new Set()
  const out = []
  const push = (s) => {
    const t = String(s ?? '').trim()
    if (!t) return
    const key = t.toLowerCase()
    if (seen.has(key)) return
    seen.add(key)
    out.push(t)
  }
  for (const r of u.roles ?? []) {
    push(r.display_name ?? r.name)
  }
  if (isNonEmpty(ci.position_name)) {
    push(ci.position_name)
  }
  if (isNonEmpty(ci.concurrent_position_name)) {
    push(ci.concurrent_position_name)
  }
  return out
})

const quickInfoRows = computed(() => {
  const u = auth.user
  const ci = u?.cms_user_info
  const rows = []

  if (ci && isNonEmpty(ci.department_name)) {
    rows.push({ label: 'Phòng ban', value: String(ci.department_name) })
  }
  if (ci && isNonEmpty(ci.headquarter_name)) {
    rows.push({ label: 'Trụ sở', value: String(ci.headquarter_name) })
  }
  if (ci && isNonEmpty(ci.working_place)) {
    rows.push({ label: 'Nơi làm việc', value: String(ci.working_place) })
  }
  const code = ci?.code != null ? String(ci.code) : u?.employee_code
  if (code != null && String(code).trim() !== '') {
    rows.push({ label: 'Mã nhân viên', value: String(code).trim() })
  }
  if (ci && ci.user_id != null && String(ci.user_id).trim() !== '') {
    rows.push({ label: 'User ID', value: String(ci.user_id) })
  } else if (u?.id != null) {
    rows.push({ label: 'ID tài khoản', value: String(u.id) })
  }
  if (ci && ci.created_at) {
    rows.push({ label: 'Tạo lúc', value: formatDisplayDate(ci.created_at) })
  }

  return rows
})

function formatDisplayDate(val) {
  const s = String(val)
  const d = s.length >= 10 ? s.slice(0, 10) : s
  const parts = d.split('-')
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`
  }
  return s
}

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

<style scoped>
.field-control {
  @apply w-full rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600/70 dark:bg-slate-950/30 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-va-700;
}
</style>
