<template>
  <div
    class="mx-auto max-w-5xl space-y-6 rounded-xl border border-slate-200 bg-white p-4 text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:p-6"
  >
    <div class="flex flex-wrap items-center gap-3">
      <RouterLink
        to="/resources"
        class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400"
      >
        ← {{ t('driver_detail.back') }}
      </RouterLink>
    </div>

    <div v-if="loading" class="py-12 text-center text-sm text-slate-500">{{ t('resources.loading') }}</div>
    <div v-else-if="loadError" class="py-12 text-center text-sm text-rose-600">{{ loadError }}</div>

    <template v-else>
      <!-- Hồ sơ -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('driver_detail.profile') }}</h2>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('driver_detail.profile_hint') }}</p>
          </div>
          <button
            v-if="canManage"
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
            @click="toggleEdit"
          >
            {{ profileEdit ? t('driver_detail.cancel_edit') : t('driver_detail.edit') }}
          </button>
        </div>

        <div v-if="!profileEdit" class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_driver_name') }}</div>
            <div class="mt-0.5 font-medium">{{ driver.full_name }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_phone') }}</div>
            <div class="mt-0.5">{{ driver.phone || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_user_email') }}</div>
            <div class="mt-0.5 break-all">{{ driver.user?.email || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_employee_code') }}</div>
            <div class="mt-0.5 font-mono text-xs">{{ driver.user?.employee_code || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('driver_detail.national_id') }}</div>
            <div class="mt-0.5">{{ driver.national_id || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_license') }}</div>
            <div class="mt-0.5">{{ licenseLine }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('driver_detail.employment') }}</div>
            <div class="mt-0.5">{{ labelEmployment(driver.employment_status) }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('driver_detail.availability') }}</div>
            <div class="mt-0.5">{{ labelAvailability(driver.availability_status) }}</div>
          </div>
        </div>

        <form v-else class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="saveProfile">
          <label class="sm:col-span-2 block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('resources.col_driver_name') }}
            <input
              v-model="profileForm.full_name"
              type="text"
              required
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_full_name')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('resources.col_phone') }}
            <input
              v-model="profileForm.phone"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_phone')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.national_id') }}
            <input
              v-model="profileForm.national_id"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_national_id')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.license_class') }}
            <input
              v-model="profileForm.license_class"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_license_class')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.license_expires') }}
            <input
              v-model="profileForm.license_expires_at"
              type="date"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.employment') }}
            <select
              v-model="profileForm.employment_status"
              class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            >
              <option value="active">{{ t('driver_detail.emp_active') }}</option>
              <option value="on_leave">{{ t('driver_detail.emp_on_leave') }}</option>
              <option value="terminated">{{ t('driver_detail.emp_terminated') }}</option>
            </select>
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.availability') }}
            <select
              v-model="profileForm.availability_status"
              class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            >
              <option value="available">{{ t('driver_detail.avail_available') }}</option>
              <option value="busy">{{ t('driver_detail.avail_busy') }}</option>
              <option value="offline">{{ t('driver_detail.avail_offline') }}</option>
            </select>
          </label>
          <div class="sm:col-span-2 flex flex-wrap gap-2 pt-2">
            <button
              type="submit"
              class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
              :disabled="savingProfile"
            >
              {{ savingProfile ? t('resources.loading') : t('driver_detail.save_profile') }}
            </button>
            <p v-if="profileError" class="text-xs text-rose-600">{{ profileError }}</p>
          </div>
        </form>
      </section>

      <!-- Giấy tờ & tuân thủ -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('driver_detail.compliance_title') }}</h2>
            <p class="mt-1 max-w-3xl text-xs text-slate-600 dark:text-slate-400">{{ t('driver_detail.compliance_intro') }}</p>
          </div>
          <button
            v-if="canManage"
            type="button"
            class="rounded-lg bg-teal-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-teal-500"
            @click="openDocModal(null)"
          >
            {{ t('driver_detail.compliance_quick_add') }}
          </button>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full min-w-[720px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_doc_type') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_title') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_expires') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('resources.col_status') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_file') }}</th>
                <th v-if="canManage" class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('resources.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="doc in documents" :key="doc.id">
                <td class="px-3 py-2 align-top">{{ docTypeLabel(doc.doc_type) }}</td>
                <td class="max-w-[200px] px-3 py-2 align-top text-xs text-slate-600 dark:text-slate-400">
                  <span class="line-clamp-2">{{ doc.title || '—' }}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-2 align-top text-xs">{{ doc.expires_at || '—' }}</td>
                <td class="px-3 py-2 align-top">
                  <span :class="expiryPillClass(doc.expiry)">{{ expiryLabel(doc.expiry) }}</span>
                </td>
                <td class="px-3 py-2 align-top text-xs">
                  <a
                    v-for="a in doc.attachments"
                    :key="a.id"
                    :href="a.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mr-2 text-teal-700 dark:text-teal-400"
                  >
                    {{ a.original_name || a.id }}
                  </a>
                  <span v-if="!doc.attachments?.length" class="text-slate-400">—</span>
                </td>
                <td v-if="canManage" class="whitespace-nowrap px-3 py-2 align-top text-right text-xs">
                  <button type="button" class="text-teal-700 hover:underline dark:text-teal-400" @click="openDocModal(doc)">
                    {{ t('resources.action_edit') }}
                  </button>
                  <button type="button" class="ml-2 text-rose-600 hover:underline" @click="confirmDelete(doc)">
                    {{ t('driver_detail.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!documents.length" class="mt-3 text-sm text-slate-500">{{ t('resources.empty') }}</p>
      </section>

      <!-- Audit -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('driver_detail.audit_title') }}</h2>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('driver_detail.audit_hint') }}</p>
        <ul class="mt-3 space-y-2 text-xs">
          <li v-for="log in auditLogs" :key="log.id" class="rounded-lg border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
            <div class="flex flex-wrap justify-between gap-2">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ log.event }}</span>
              <span class="text-slate-500">{{ formatDt(log.created_at) }}</span>
            </div>
            <div class="mt-1 text-slate-600 dark:text-slate-400">{{ log.actor?.name || log.actor_id || '—' }}</div>
          </li>
        </ul>
        <p v-if="!auditLogs.length" class="mt-2 text-sm text-slate-500">{{ t('resources.empty') }}</p>
        <RouterLink
          v-if="canViewAudit"
          to="/audit-logs"
          class="mt-3 inline-block text-sm font-medium text-teal-700 hover:underline dark:text-teal-400"
        >
          {{ t('driver_detail.audit_full') }}
        </RouterLink>
      </section>
    </template>

    <!-- Modal giấy tờ -->
    <Teleport to="body">
      <div
        v-if="docModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="docModalOpen = false"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900" @click.stop>
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">
              {{ editingDocId ? t('driver_detail.doc_modal_edit') : t('driver_detail.doc_modal_add') }}
            </h2>
          </div>
          <form class="max-h-[75vh] space-y-3 overflow-y-auto p-4" @submit.prevent="submitDocForm">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_doc_type') }}
              <select
                v-model="docForm.doc_type"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              >
                <option v-for="opt in docTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_title') }}
              <input
                v-model="docForm.title"
                type="text"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('driver_detail.ph_doc_title')"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.notes') }}
              <textarea
                v-model="docForm.notes"
                rows="3"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('driver_detail.ph_doc_notes')"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.issued_at') }}
                <input v-model="docForm.issued_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.expires_at') }}
                <input v-model="docForm.expires_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.upload_file') }}
              <input type="file" class="mt-1 w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-800 dark:file:bg-teal-950 dark:file:text-teal-300" @change="onDocFile" />
            </label>
            <label v-if="editingDocId" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
              <input v-model="docForm.replace_file" type="checkbox" class="rounded border-slate-300 text-teal-600" />
              {{ t('driver_detail.replace_file') }}
            </label>
            <p v-if="docFormError" class="text-xs text-rose-600">{{ docFormError }}</p>
            <div class="flex gap-2 pt-2">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="docModalOpen = false"
              >
                {{ t('app.cancel') }}
              </button>
              <button
                type="submit"
                class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                :disabled="docSaving"
              >
                {{ docSaving ? t('resources.loading') : t('driver_detail.save_doc') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  createDriverComplianceDocument,
  deleteDriverComplianceDocument,
  getDriver,
  getDriverComplianceAudit,
  listDriverComplianceDocuments,
  updateDriver,
  updateDriverComplianceDocument,
} from '../../api/operational'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const canManage = computed(() => auth.hasPermission('resource.driver.manage'))
const canViewAudit = computed(() => auth.hasPermission('audit_log.view'))

const loading = ref(true)
const loadError = ref('')
const driver = ref({})
const documents = ref([])
const auditLogs = ref([])

const profileEdit = ref(false)
const savingProfile = ref(false)
const profileError = ref('')
const profileForm = ref({
  full_name: '',
  phone: '',
  national_id: '',
  license_class: '',
  license_expires_at: '',
  employment_status: 'active',
  availability_status: 'available',
})

const docModalOpen = ref(false)
const editingDocId = ref(null)
const docSaving = ref(false)
const docFormError = ref('')
const docFile = ref(null)
const docForm = ref({
  doc_type: 'license',
  title: '',
  notes: '',
  issued_at: '',
  expires_at: '',
  replace_file: false,
})

const docTypeOptions = computed(() =>
  DOC_TYPES.map((value) => ({
    value,
    label: t(`driver_compliance_doc_type.${value}`),
  })),
)

const DOC_TYPES = [
  'id_card',
  'license',
  'medical_certificate',
  'criminal_record',
  'training_certificate',
  'labor_contract',
  'social_insurance',
  'other',
]

const licenseLine = computed(() => {
  const d = driver.value
  if (!d) return '—'
  const parts = [d.license_class, d.license_expires_at].filter(Boolean)
  return parts.length ? parts.join(' — ') : '—'
})

function syncProfileForm() {
  const d = driver.value
  profileForm.value = {
    full_name: d.full_name || '',
    phone: d.phone || '',
    national_id: d.national_id || '',
    license_class: d.license_class || '',
    license_expires_at: d.license_expires_at || '',
    employment_status: d.employment_status || 'active',
    availability_status: d.availability_status || 'available',
  }
}

function toggleEdit() {
  if (!profileEdit.value) {
    syncProfileForm()
    profileEdit.value = true
  } else {
    profileEdit.value = false
    profileError.value = ''
  }
}

async function load() {
  loading.value = true
  loadError.value = ''
  const id = Number(route.params.id)
  if (!id) {
    loadError.value = t('resources.load_error')
    loading.value = false
    return
  }
  try {
    const [d, docs, audit] = await Promise.all([
      getDriver(id),
      listDriverComplianceDocuments(id),
      getDriverComplianceAudit(id),
    ])
    driver.value = d
    documents.value = docs.items || []
    auditLogs.value = audit.items || []
  } catch {
    loadError.value = t('resources.load_error')
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(
  () => route.params.id,
  () => load(),
)

async function saveProfile() {
  savingProfile.value = true
  profileError.value = ''
  try {
    const id = Number(route.params.id)
    const f = profileForm.value
    driver.value = await updateDriver(id, {
      full_name: f.full_name.trim(),
      phone: f.phone?.trim() || null,
      national_id: f.national_id?.trim() || null,
      license_class: f.license_class?.trim() || null,
      license_expires_at: f.license_expires_at || null,
      employment_status: f.employment_status,
      availability_status: f.availability_status,
    })
    profileEdit.value = false
  } catch (e) {
    profileError.value = e?.response?.data?.message || t('resources.load_error')
  } finally {
    savingProfile.value = false
  }
}

function docTypeLabel(type) {
  const k = `driver_compliance_doc_type.${type}`
  return t(k) !== k ? t(k) : type
}

function expiryPillClass(exp) {
  if (!exp || exp.state === 'none') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  if (exp.state === 'ok') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  }
  if (exp.state === 'soon') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  }
  return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
}

function expiryLabel(exp) {
  if (!exp || exp.state === 'none') return t('driver_detail.expiry_none')
  if (exp.state === 'ok') return t('resources.compliance_ok')
  if (exp.state === 'soon') return t('resources.exp_in_days', { n: exp.days })
  return t('resources.compliance_exp')
}

function labelEmployment(s) {
  const map = {
    active: t('driver_detail.emp_active'),
    on_leave: t('driver_detail.emp_on_leave'),
    terminated: t('driver_detail.emp_terminated'),
  }
  return map[s] ?? s
}

function labelAvailability(s) {
  const map = {
    available: t('driver_detail.avail_available'),
    busy: t('driver_detail.avail_busy'),
    offline: t('driver_detail.avail_offline'),
  }
  return map[s] ?? s
}

function formatDt(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString()
  } catch {
    return iso
  }
}

function emptyDocForm() {
  docForm.value = {
    doc_type: 'license',
    title: '',
    notes: '',
    issued_at: '',
    expires_at: '',
    replace_file: false,
  }
  docFile.value = null
}

function openDocModal(doc) {
  docFormError.value = ''
  if (doc) {
    editingDocId.value = doc.id
    docForm.value = {
      doc_type: doc.doc_type,
      title: doc.title || '',
      notes: doc.notes || '',
      issued_at: doc.issued_at || '',
      expires_at: doc.expires_at || '',
      replace_file: false,
    }
    docFile.value = null
  } else {
    editingDocId.value = null
    emptyDocForm()
  }
  docModalOpen.value = true
}

function onDocFile(e) {
  const f = e.target.files?.[0]
  docFile.value = f || null
}

async function submitDocForm() {
  docSaving.value = true
  docFormError.value = ''
  const id = Number(route.params.id)
  try {
    const fd = new FormData()
    fd.append('doc_type', docForm.value.doc_type)
    if (docForm.value.title) fd.append('title', docForm.value.title)
    if (docForm.value.notes) fd.append('notes', docForm.value.notes)
    if (docForm.value.issued_at) fd.append('issued_at', docForm.value.issued_at)
    if (docForm.value.expires_at) fd.append('expires_at', docForm.value.expires_at)
    if (docFile.value) fd.append('file', docFile.value)
    if (editingDocId.value) {
      fd.append('replace_file', docForm.value.replace_file ? '1' : '0')
      await updateDriverComplianceDocument(id, editingDocId.value, fd)
    } else {
      await createDriverComplianceDocument(id, fd)
    }
    docModalOpen.value = false
    await load()
  } catch (e) {
    const msg = e?.response?.data?.message
    const errs = e?.response?.data?.errors
    docFormError.value =
      (typeof msg === 'string' && msg) ||
      (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
      t('resources.load_error')
  } finally {
    docSaving.value = false
  }
}

async function confirmDelete(doc) {
  if (!canManage.value) return
  if (!window.confirm(t('driver_detail.confirm_delete_doc'))) return
  const id = Number(route.params.id)
  try {
    await deleteDriverComplianceDocument(id, doc.id)
    await load()
  } catch {
    alert(t('resources.load_error'))
  }
}
</script>
