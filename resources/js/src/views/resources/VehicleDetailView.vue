<template>
  <div class="w-full max-w-none space-y-6 pb-10 text-slate-900 dark:text-slate-100">
    <div v-if="loading" class="py-12 text-center text-sm text-slate-500">{{ t('resources.loading') }}</div>
    <div v-else-if="loadError" class="py-12 text-center text-sm text-rose-600">{{ loadError }}</div>

    <template v-else>
      <!-- Content header -->
      <div class="border-b border-slate-200/80 pb-6 dark:border-slate-700/80">
        <RouterLink
          to="/resources/list?tab=vehicles"
          class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400"
          data-testid="vehicle-detail-back"
        >
          ← {{ t('vehicle_detail.back') }}
        </RouterLink>
        <div class="flex flex-wrap items-start gap-3 sm:gap-4">
          <div
            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 ring-1 ring-teal-200/60 dark:bg-teal-950/40 dark:text-teal-400 dark:ring-teal-800/60"
            aria-hidden="true"
          >
            <component :is="vehicleIconComponent" class="h-7 w-7" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
              {{ t('vehicle_detail.page_heading_hint') }}
            </p>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-2">
              <h1 class="font-mono text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                <EmptyValue :value="vehicle.license_plate" empty-key="vehicle_detail.empty_license_plate" />
              </h1>
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="vehicleStatusBadgeClass(vehicle.status)"
              >
                {{ labelVehicleStatus(vehicle.status) }}
              </span>
            </div>
            <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600 dark:text-slate-400">
              <EmptyValue :value="vehicle.type" empty-key="vehicle_detail.empty_type" />
              <template v-if="vehicle.seat_count != null">
                <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                {{ t('vehicle_detail.seat_suffix', { n: vehicle.seat_count }) }}
              </template>
              <template v-if="vehicle.payload_kg">
                <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                {{ t('vehicle_detail.payload_suffix', { n: vehicle.payload_kg }) }}
              </template>
              <template v-if="vehicle.owner_name">
                <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                <span class="min-w-0 truncate">{{ vehicle.owner_name }}</span>
              </template>
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">{{ t('vehicle_detail.page_subtitle') }}</p>
          </div>
        </div>
      </div>

      <!-- Hồ sơ xe -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('vehicle_detail.profile') }}</h2>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('vehicle_detail.profile_hint') }}</p>
          </div>
          <button
            v-if="canManage"
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
            data-testid="vehicle-detail-toggle-profile-edit"
            @click="toggleProfileEdit"
          >
            {{ profileEdit ? t('vehicle_detail.cancel_edit') : t('vehicle_detail.edit') }}
          </button>
        </div>

        <div v-if="!profileEdit" class="mt-4 space-y-4">
          <div
            class="flex flex-col gap-2 rounded-xl border border-slate-100 bg-slate-50/90 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-slate-800 dark:bg-slate-800/50"
            data-testid="vehicle-detail-compliance-pills"
          >
            <p class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('vehicle_detail.compliance_pills_heading') }}
            </p>
            <dl class="flex min-w-0 flex-1 flex-wrap items-center gap-2 sm:justify-end">
              <div
                v-for="(pill, idx) in vehicleCompliancePills"
                :key="idx"
                class="inline-flex min-w-0 items-center gap-2 rounded-lg border border-slate-200/80 bg-white px-2.5 py-1.5 dark:border-slate-700 dark:bg-slate-900/60"
              >
                <dt class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">{{ pill.label }}</dt>
                <dd class="m-0">
                  <span :class="pill.class">{{ pill.value }}</span>
                </dd>
              </div>
            </dl>
          </div>

          <dl
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
            data-testid="vehicle-detail-profile-fields"
          >
            <div
              v-for="field in profileReadFields"
              :key="field.key"
              class="min-w-0 rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-800/40"
              :class="field.spanWide ? 'sm:col-span-2 lg:col-span-3' : ''"
            >
              <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ field.label }}
              </dt>
              <dd
                class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-200"
                :class="[
                  field.mono ? 'font-mono' : '',
                  field.tabular ? 'tabular-nums' : '',
                  field.preWrap ? 'whitespace-pre-wrap' : '',
                ]"
              >
                <template v-if="field.kind === 'status'">{{ labelVehicleStatus(vehicle.status) }}</template>
                <EmptyValue
                  v-else
                  :value="field.value"
                  :empty-key="field.emptyKey"
                />
              </dd>
            </div>
          </dl>
        </div>

        <form v-else class="mt-4 space-y-4" @submit.prevent="saveVehicleProfile">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('vehicle_detail.edit_form_hint') }}</p>
          <div class="grid gap-3 sm:grid-cols-2">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.col_status') }}
              <span class="text-rose-500" aria-hidden="true">*</span>
              <select
                v-model="profileForm.status"
                required
                class="mt-1 h-10 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                data-testid="vehicle-detail-profile-status"
              >
                <option value="ready">{{ t('resources.vehicle_status_ready') }}</option>
                <option value="in_use">{{ t('resources.vehicle_status_in_use') }}</option>
                <option value="maintenance">{{ t('resources.vehicle_status_maintenance') }}</option>
                <option value="broken">{{ t('resources.vehicle_status_broken') }}</option>
              </select>
            </label>
            <div class="hidden sm:block" aria-hidden="true" />
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('vehicle_detail.caretaker_name') }}
              <input
                v-model="profileForm.caretaker_name"
                type="text"
                class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.vehicle_ph_caretaker')"
                data-testid="vehicle-detail-profile-caretaker-name"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('vehicle_detail.caretaker_phone') }}
              <input
                v-model="profileForm.caretaker_phone"
                type="tel"
                autocomplete="tel"
                class="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.vehicle_ph_caretaker_phone')"
                data-testid="vehicle-detail-profile-caretaker-phone"
              />
            </label>
            <label class="sm:col-span-2 block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('vehicle_detail.notes') }}
              <textarea
                v-model="profileForm.notes"
                rows="4"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.vehicle_ph_notes')"
                data-testid="vehicle-detail-profile-notes"
              />
            </label>
          </div>
          <div class="flex flex-wrap gap-2 pt-1">
            <button
              type="submit"
              class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
              :disabled="profileSaving"
              data-testid="vehicle-detail-save-profile"
            >
              {{ profileSaving ? t('resources.loading') : t('vehicle_detail.save_profile') }}
            </button>
            <p v-if="profileError" class="text-xs text-rose-600">{{ profileError }}</p>
          </div>
        </form>
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
            data-testid="vehicle-detail-compliance-add"
            @click="openDocModal(null)"
          >
            {{ t('vehicle_detail.compliance_quick_add') }}
          </button>
        </div>

        <div
          v-if="complianceExpirySummary.total > 0"
          class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/35 dark:text-amber-100"
          role="status"
        >
          {{
            t('vehicle_detail.compliance_expiry_alert', {
              n: complianceExpirySummary.total,
              soon: complianceExpirySummary.soon,
              exp: complianceExpirySummary.exp,
            })
          }}
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
                  <span class="line-clamp-2">
                    <EmptyValue :value="doc.title" empty-key="driver_detail.empty_doc_title" />
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-2 align-top text-xs">
                  <EmptyValue :value="doc.expires_at" empty-key="driver_detail.empty_doc_expires" />
                </td>
                <td class="px-3 py-2 align-top">
                  <span :class="expiryPillClass(doc.expiry)">{{ expiryLabel(doc.expiry) }}</span>
                </td>
                <td class="max-w-[min(100%,280px)] px-3 py-2 align-top text-xs" @click.stop>
                  <span v-if="!doc.attachments?.length">
                    <EmptyValue value="" empty-key="vehicle_detail.empty_attachment" />
                  </span>
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
                  <button
                    type="button"
                    class="text-teal-700 hover:underline dark:text-teal-400"
                    data-testid="vehicle-detail-doc-edit"
                    @click="openDocModal(doc)"
                  >
                    {{ t('resources.action_edit') }}
                  </button>
                  <button
                    type="button"
                    class="ml-2 text-rose-600 hover:underline"
                    data-testid="vehicle-detail-doc-delete"
                    @click="confirmDelete(doc)"
                  >
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
      <section
        v-if="canManage"
        class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5"
      >
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('vehicle_detail.audit_title') }}</h2>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('vehicle_detail.audit_hint') }}</p>
        <ul class="mt-3 space-y-2 text-xs">
          <li
            v-for="log in auditLogs"
            :key="log.id"
            class="rounded-lg border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40"
          >
            <div class="flex flex-wrap justify-between gap-2">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ formatAuditEvent(log.event) }}</span>
              <span class="text-slate-500">{{ formatDt(log.created_at) }}</span>
            </div>
            <div class="mt-1 text-slate-600 dark:text-slate-400">
              <EmptyValue :value="log.actor?.name || log.actor_id" empty-key="driver_detail.empty_actor" />
            </div>
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
          class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900"
          @click.stop
        >
          <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
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
          <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitDocForm">
            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-y-contain p-4">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_doc_type') }}
              <select
                v-model="docForm.doc_type"
                required
                :disabled="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm disabled:cursor-not-allowed disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                data-testid="vehicle-detail-doc-type"
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
                data-testid="vehicle-detail-doc-title"
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
                data-testid="vehicle-detail-doc-notes"
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
                data-testid="vehicle-detail-doc-file"
                @change="onDocFile"
              />
            </label>
            <label v-if="canManage && editingDocId" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
              <input v-model="docForm.replace_file" type="checkbox" class="rounded border-slate-300 text-teal-600" />
              {{ t('driver_detail.replace_file') }}
            </label>
            <p v-if="docFormError" class="text-xs text-rose-600">{{ docFormError }}</p>
            </div>
            <div class="flex shrink-0 gap-2 border-t border-slate-200 p-4 dark:border-slate-700">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                data-testid="vehicle-detail-doc-cancel"
                @click="docModalOpen = false"
              >
                {{ canManage ? t('app.cancel') : t('resources.close_panel') }}
              </button>
              <button
                v-if="canManage"
                type="submit"
                class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                :disabled="docSaving"
                data-testid="vehicle-detail-doc-save"
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
  updateVehicle,
  updateVehicleComplianceDocument,
} from '../../api/operational'
import { formatApiError } from '../../api/http'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import EmptyValue from '../../components/ui/EmptyValue.vue'
import { vehicleIconKind, VEHICLE_ICON_COMPONENTS } from '../../util/vehicleIcon'

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

const { t, te } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const canManage = computed(() => auth.hasPermission('resource.vehicle.manage'))

const loading = ref(true)
const loadError = ref('')
const vehicle = ref({})
const documents = ref([])
const auditLogs = ref([])

const profileEdit = ref(false)
const profileSaving = ref(false)
const profileError = ref('')
const profileForm = ref({
  status: 'ready',
  caretaker_name: '',
  caretaker_phone: '',
  notes: '',
})

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

const vehicleIconComponent = computed(() => {
  const kind = vehicleIconKind(vehicle.value)
  return VEHICLE_ICON_COMPONENTS[kind] || VEHICLE_ICON_COMPONENTS.van
})

const profileReadFields = computed(() => {
  const v = vehicle.value || {}
  return [
    {
      key: 'license_plate',
      label: t('vehicle_detail.license_plate'),
      value: v.license_plate,
      emptyKey: 'vehicle_detail.empty_license_plate',
      mono: true,
    },
    {
      key: 'type',
      label: t('vehicle_detail.type'),
      value: v.type,
      emptyKey: 'vehicle_detail.empty_type',
    },
    {
      key: 'seat_count',
      label: t('vehicle_detail.seat_count'),
      value: v.seat_count != null ? String(v.seat_count) : '',
      emptyKey: 'vehicle_detail.empty_seat_count',
      tabular: true,
    },
    {
      key: 'status',
      label: t('resources.col_status'),
      kind: 'status',
    },
    {
      key: 'owner_name',
      label: t('vehicle_detail.owner_name'),
      value: v.owner_name,
      emptyKey: 'resources.empty_owner',
    },
    {
      key: 'caretaker_name',
      label: t('vehicle_detail.caretaker_name'),
      value: v.caretaker_name,
      emptyKey: 'vehicle_detail.empty_caretaker',
    },
    {
      key: 'caretaker_phone',
      label: t('vehicle_detail.caretaker_phone'),
      value: v.caretaker_phone,
      emptyKey: 'vehicle_detail.empty_caretaker_phone',
    },
    {
      key: 'default_driver',
      label: t('vehicle_detail.default_driver'),
      value: v.default_driver?.full_name,
      emptyKey: 'resources.unassigned',
    },
    {
      key: 'inspection_expires',
      label: t('vehicle_detail.inspection_expires'),
      value: v.inspection_expires_at,
      emptyKey: 'resources.empty_date',
    },
    {
      key: 'insurance_expires',
      label: t('vehicle_detail.insurance_expires'),
      value: v.insurance_expires_at,
      emptyKey: 'resources.empty_date',
    },
    {
      key: 'notes',
      label: t('vehicle_detail.notes'),
      value: v.notes,
      emptyKey: 'vehicle_detail.empty_notes',
      spanWide: true,
      preWrap: true,
    },
  ]
})

function docStateFromDate(iso) {
  if (!iso) return { state: 'none', days: null, until: null }
  const d = new Date(`${iso}T12:00:00`)
  const ms = d.getTime() - Date.now()
  const days = Math.ceil(ms / 86400000)
  const until = iso
  if (days < 0) return { state: 'exp', days, until }
  if (days <= 30) return { state: 'soon', days, until }
  return { state: 'ok', days: null, until }
}

function complianceStatusLabel(doc) {
  if (doc.state === 'none') return t('resources.empty_date')
  if (doc.state === 'ok') return t('resources.compliance_ok')
  if (doc.state === 'soon') return t('resources.exp_in_days', { n: doc.days })
  return t('resources.compliance_exp')
}

function compliancePillClass(doc) {
  if (doc.state === 'none') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  if (doc.state === 'ok') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  }
  if (doc.state === 'soon') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  }
  return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
}

function compliancePill(label, doc) {
  return { label, value: complianceStatusLabel(doc), class: compliancePillClass(doc) }
}

const vehicleCompliancePills = computed(() => {
  const v = vehicle.value || {}
  return [
    compliancePill(t('resources.tag_ins'), docStateFromDate(v.insurance_expires_at)),
    compliancePill(t('resources.tag_reg'), docStateFromDate(v.inspection_expires_at)),
    compliancePill(t('resources.tag_road_fee'), docStateFromDate(v.road_fee_expires_at)),
  ]
})

const complianceExpirySummary = computed(() => {
  let soon = 0
  let exp = 0
  for (const doc of documents.value) {
    const st = doc.expiry?.state
    if (st === 'soon') soon += 1
    else if (st === 'exp') exp += 1
  }
  return { soon, exp, total: soon + exp }
})

function syncProfileFormFromVehicle() {
  const v = vehicle.value || {}
  profileForm.value = {
    status: v.status || 'ready',
    caretaker_name: v.caretaker_name || '',
    caretaker_phone: v.caretaker_phone || '',
    notes: v.notes || '',
  }
}

function toggleProfileEdit() {
  profileEdit.value = !profileEdit.value
  profileError.value = ''
  if (!profileEdit.value) syncProfileFormFromVehicle()
}

async function saveVehicleProfile() {
  if (!canManage.value) return
  const id = Number(route.params.id)
  if (!id) return
  profileSaving.value = true
  profileError.value = ''
  try {
    await updateVehicle(id, {
      status: profileForm.value.status,
      caretaker_name: profileForm.value.caretaker_name?.trim() || null,
      caretaker_phone: profileForm.value.caretaker_phone?.trim() || null,
      notes: profileForm.value.notes?.trim() || null,
    })
    showAppSuccess(t('dispatch_settings.saved_hint'), t('requests_page.preset_toast_title'))
    profileEdit.value = false
    await load()
  } catch (e) {
    profileError.value = formatApiError(e, t('vehicle_detail.profile_save_error'))
  } finally {
    profileSaving.value = false
  }
}

function vehicleStatusBadgeClass(s) {
  const map = {
    ready: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200',
    in_use: 'bg-sky-100 text-sky-900 dark:bg-sky-950/50 dark:text-sky-200',
    maintenance: 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
    broken: 'bg-rose-100 text-rose-900 dark:bg-rose-950/50 dark:text-rose-200',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'
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
    const [veh, docs] = await Promise.all([getVehicle(id), listVehicleComplianceDocuments(id)])
    vehicle.value = veh
    documents.value = docs.items || []
    syncProfileFormFromVehicle()
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
  return te(k) ? t(k) : type
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
  return map[s] ?? s ?? t('resources.empty_not_available')
}

function formatAuditEvent(event) {
  const key = `vehicle_detail.audit_event_${String(event || '').replace(/\./g, '_')}`
  if (te(key)) return t(key)
  return t('vehicle_detail.audit_event_unknown')
}

function formatDt(iso) {
  if (!iso) return t('vehicle_detail.empty_datetime')
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
