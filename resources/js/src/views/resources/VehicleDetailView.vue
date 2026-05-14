<template>
  <div
    class="mx-auto max-w-5xl space-y-6 rounded-xl border border-slate-200 bg-white p-4 text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:p-6"
  >
    <div class="flex flex-wrap items-center gap-3">
      <RouterLink
        to="/resources/list?tab=vehicles"
        class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400"
      >
        ← {{ t('vehicle_detail.back') }}
      </RouterLink>
    </div>

    <div v-if="loading" class="py-12 text-center text-sm text-slate-500">{{ t('resources.loading') }}</div>
    <div v-else-if="loadError" class="py-12 text-center text-sm text-rose-600">{{ loadError }}</div>

    <template v-else>
      <!-- Hồ sơ xe -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('vehicle_detail.profile') }}</h2>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('vehicle_detail.profile_hint') }}</p>

        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.license_plate') }}</div>
            <div class="mt-0.5 font-medium">{{ vehicle.license_plate || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.type') }}</div>
            <div class="mt-0.5">{{ vehicle.type || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.seat_count') }}</div>
            <div class="mt-0.5">{{ vehicle.seat_count ?? '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_status') }}</div>
            <div class="mt-0.5">{{ labelVehicleStatus(vehicle.status) }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.owner_name') }}</div>
            <div class="mt-0.5">{{ vehicle.owner_name || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.default_driver') }}</div>
            <div class="mt-0.5">{{ vehicle.default_driver?.full_name || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.inspection_expires') }}</div>
            <div class="mt-0.5">{{ vehicle.inspection_expires_at || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('vehicle_detail.insurance_expires') }}</div>
            <div class="mt-0.5">{{ vehicle.insurance_expires_at || '—' }}</div>
          </div>
          <div class="sm:col-span-2">
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_notes') }}</div>
            <div class="mt-0.5 whitespace-pre-wrap">{{ vehicle.notes || '—' }}</div>
          </div>
        </div>
      </section>

      <!-- Giấy tờ -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('vehicle_detail.compliance_title') }}</h2>
            <p class="mt-1 max-w-3xl text-xs text-slate-600 dark:text-slate-400">{{ t('vehicle_detail.compliance_intro') }}</p>
          </div>
          <button
            v-if="canManage"
            type="button"
            class="rounded-lg bg-teal-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-teal-500"
            @click="openDocModal(null)"
          >
            {{ t('vehicle_detail.compliance_quick_add') }}
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
              <tr
                v-for="doc in documents"
                :key="doc.id"
                class="cursor-pointer transition hover:bg-slate-50 dark:hover:bg-slate-800/40"
                @click="openDocModal(doc)"
              >
                <td class="px-3 py-2 align-top">{{ docTypeLabel(doc.doc_type) }}</td>
                <td class="max-w-[200px] px-3 py-2 align-top text-xs text-slate-600 dark:text-slate-400">
                  <span class="line-clamp-2">{{ doc.title || '—' }}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-2 align-top text-xs">{{ doc.expires_at || '—' }}</td>
                <td class="px-3 py-2 align-top">
                  <span :class="expiryPillClass(doc.expiry)">{{ expiryLabel(doc.expiry) }}</span>
                </td>
                <td class="max-w-[min(100%,280px)] px-3 py-2 align-top text-xs" @click.stop>
                  <span v-if="!doc.attachments?.length" class="text-slate-400">—</span>
                  <div v-else class="flex flex-col gap-2">
                    <a
                      v-for="(a, aIdx) in doc.attachments"
                      :key="a.id ?? `att-${aIdx}`"
                      :href="resolveAttachmentAbsoluteUrl(a)"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="truncate rounded-lg border border-slate-200/90 bg-slate-50/80 px-2 py-1.5 text-teal-700 underline dark:border-slate-600/80 dark:bg-slate-800/40 dark:text-teal-400"
                    >
                      {{ a.original_name || a.id }}
                    </a>
                  </div>
                </td>
                <td v-if="canManage" class="whitespace-nowrap px-3 py-2 align-top text-right text-xs" @click.stop>
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
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('vehicle_detail.audit_title') }}</h2>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('vehicle_detail.audit_hint') }}</p>
        <ul class="mt-3 space-y-2 text-xs">
          <li
            v-for="log in auditLogs"
            :key="log.id"
            class="rounded-lg border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40"
          >
            <div class="flex flex-wrap justify-between gap-2">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ log.event }}</span>
              <span class="text-slate-500">{{ formatDt(log.created_at) }}</span>
            </div>
            <div class="mt-1 text-slate-600 dark:text-slate-400">{{ log.actor?.name || log.actor_id || '—' }}</div>
          </li>
        </ul>
        <p v-if="!auditLogs.length" class="mt-2 text-sm text-slate-500">{{ t('resources.empty') }}</p>
      </section>
    </template>

    <Teleport to="body">
      <div
        v-if="docModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="docModalOpen = false"
      >
        <div
          class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900"
          @click.stop
        >
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">
              {{
                !canManage
                  ? t('driver_detail.doc_modal_view')
                  : editingDocId
                    ? t('driver_detail.doc_modal_edit')
                    : t('driver_detail.doc_modal_add')
              }}
            </h2>
          </div>
          <form class="max-h-[75vh] space-y-3 overflow-y-auto p-4" @submit.prevent="submitDocForm">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_doc_type') }}
              <select
                v-model="docForm.doc_type"
                required
                :disabled="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm disabled:cursor-not-allowed disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              >
                <option v-for="opt in docTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_title') }}
              <input
                v-model="docForm.title"
                type="text"
                :readonly="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                :placeholder="t('driver_detail.ph_doc_title')"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.notes') }}
              <textarea
                v-model="docForm.notes"
                rows="3"
                :readonly="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                :placeholder="t('driver_detail.ph_doc_notes')"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.issued_at') }}
                <input
                  v-model="docForm.issued_at"
                  type="date"
                  :readonly="!canManage"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.expires_at') }}
                <input
                  v-model="docForm.expires_at"
                  type="date"
                  :readonly="!canManage"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                />
              </label>
            </div>
            <label v-if="canManage" class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.upload_file') }}
              <input
                type="file"
                class="mt-1 w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-800 dark:file:bg-teal-950 dark:file:text-teal-300"
                @change="onDocFile"
              />
            </label>
            <label v-if="canManage && editingDocId" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
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
                {{ canManage ? t('app.cancel') : t('resources.close_panel') }}
              </button>
              <button
                v-if="canManage"
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
  createVehicleComplianceDocument,
  deleteVehicleComplianceDocument,
  getVehicle,
  getVehicleComplianceAudit,
  listVehicleComplianceDocuments,
  updateVehicleComplianceDocument,
} from '../../api/operational'
import { formatApiError } from '../../api/http'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'

const DOC_TYPES = [
  'registration',
  'insurance_certificate',
  'inspection_certificate',
  'transport_permit',
  'ownership_proof',
  'lease_contract',
  'maintenance_record',
  'other',
]

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const canManage = computed(() => auth.hasPermission('resource.vehicle.manage'))

const loading = ref(true)
const loadError = ref('')
const vehicle = ref({})
const documents = ref([])
const auditLogs = ref([])

const docModalOpen = ref(false)
const docSaving = ref(false)
const docFormError = ref('')
const editingDocId = ref(null)
const docFile = ref(null)
const docForm = ref({
  doc_type: 'registration',
  title: '',
  notes: '',
  issued_at: '',
  expires_at: '',
  replace_file: false,
})

const docTypeOptions = computed(() =>
  DOC_TYPES.map((value) => ({
    value,
    label: t(`vehicle_compliance_doc_type.${value}`),
  })),
)

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
    const [veh, docs] = await Promise.all([getVehicle(id), listVehicleComplianceDocuments(id)])
    vehicle.value = veh
    documents.value = docs.items || []
    try {
      const audit = await getVehicleComplianceAudit(id)
      auditLogs.value = audit.items || []
    } catch {
      auditLogs.value = []
    }
  } catch (e) {
    loadError.value = formatApiError(e, t('resources.load_error'))
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(
  () => route.params.id,
  () => load(),
)

function docTypeLabel(type) {
  const k = `vehicle_compliance_doc_type.${type}`
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

function labelVehicleStatus(s) {
  const map = {
    ready: t('resources.vehicle_status_ready'),
    in_use: t('resources.vehicle_status_in_use'),
    maintenance: t('resources.vehicle_status_maintenance'),
    broken: t('resources.vehicle_status_broken'),
  }
  return map[s] ?? s ?? '—'
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
    doc_type: 'registration',
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
  if (!canManage.value) return
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
      await updateVehicleComplianceDocument(id, editingDocId.value, fd)
    } else {
      await createVehicleComplianceDocument(id, fd)
    }
    docModalOpen.value = false
    await load()
  } catch (e) {
    const st = e?.response?.status
    if (st === 422) {
      const msg = e?.response?.data?.message
      const errs = e?.response?.data?.errors
      docFormError.value =
        (typeof msg === 'string' && msg) ||
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        t('resources.load_error')
    } else {
      showAppErrorFromApi(e, t('resources.load_error'))
    }
  } finally {
    docSaving.value = false
  }
}

async function confirmDelete(doc) {
  if (!canManage.value) return
  if (!window.confirm(t('vehicle_detail.confirm_delete_doc'))) return
  const id = Number(route.params.id)
  try {
    await deleteVehicleComplianceDocument(id, doc.id)
    await load()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  }
}

function resolveAttachmentAbsoluteUrl(a) {
  const u = a?.url
  if (!u) return ''
  if (typeof window === 'undefined') return u
  const viteBackend =
    import.meta.env.DEV && import.meta.env.VITE_APP_URL ? String(import.meta.env.VITE_APP_URL).trim().replace(/\/$/, '') : ''
  try {
    const parsed = new URL(u, window.location.origin)
    if (parsed.pathname.startsWith('/storage')) {
      const base = viteBackend || window.location.origin
      return `${base}${parsed.pathname}${parsed.search}`
    }
    return parsed.href
  } catch {
    const path = u.startsWith('/') ? u : `/${u}`
    if (path.startsWith('/storage')) {
      const base = viteBackend || window.location.origin
      return `${base}${path}`
    }
    return `${window.location.origin}${path}`
  }
}
</script>
