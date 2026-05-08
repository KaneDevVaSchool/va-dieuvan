<template>
  <div class="min-h-full w-full overflow-x-hidden bg-driver-bg pb-[calc(12rem+env(safe-area-inset-bottom))] text-driver-ink">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="sticky top-0 z-20 flex items-center gap-3 bg-driver-bg/95 px-4 py-3 pt-[calc(0.75rem+env(safe-area-inset-top))] shadow-[0_1px_0_rgba(127,220,200,0.08)] backdrop-blur-sm">
      <RouterLink
        :to="{ name: 'driverMaintenance' }"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/8 text-white ring-1 ring-white/10 active:scale-95"
        :aria-label="t('driver_maintenance.back')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L4.862 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </RouterLink>

      <h1 class="flex-1 truncate text-center text-[18px] font-bold text-driver-ink">
        {{ currentItem?.name ?? t('driver_maintenance.detail_title') }}
      </h1>

      <button
        v-if="currentItem"
        type="button"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/8 text-white ring-1 ring-white/10 active:scale-95"
        :aria-label="t('driver_maintenance.edit')"
        @click="editMode = !editMode"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
          <path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z"/>
        </svg>
      </button>
    </div>

    <!-- ── Skeleton ──────────────────────────────────────────────── -->
    <template v-if="detailLoading">
      <div class="h-44 animate-pulse bg-driver-card" />
      <div class="mx-auto max-w-lg space-y-3 px-4 pt-5">
        <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded-2xl bg-driver-card" />
      </div>
    </template>

    <!-- ── Error ─────────────────────────────────────────────────── -->
    <div
      v-else-if="detailError"
      class="mx-auto mt-6 max-w-lg px-4"
    >
      <div class="rounded-2xl border border-[#ff6b6b]/30 bg-[#2a1010] px-4 py-4">
        <p class="text-sm font-semibold text-[#ff9999]">{{ detailError }}</p>
        <button
          type="button"
          class="mt-3 rounded-full bg-[#ff6b6b]/20 px-4 py-1.5 text-sm font-semibold text-[#ff9999] ring-1 ring-[#ff6b6b]/40"
          @click="fetchDetail(itemId)"
        >Thử lại</button>
      </div>
    </div>

    <template v-else-if="currentItem">
      <!-- ── Hero Status Card ────────────────────────────────────── -->
      <div :class="['flex flex-col items-center gap-1 px-4 py-8', heroBgClass]">
        <!-- Icon -->
        <div :class="['flex h-16 w-16 items-center justify-center rounded-full', heroIconBg]">
          <component :is="heroIconComponent" class="h-9 w-9" :class="heroIconColor" />
        </div>

        <!-- Days remaining -->
        <p :class="['mt-3 text-[48px] font-bold tabular-nums leading-none', heroTextColor]">
          {{ currentItem.days_remaining ?? '—' }}
        </p>
        <p class="text-sm text-white/50">{{ t('driver_maintenance.days_remaining') }}</p>

        <!-- Expiry or next date -->
        <p class="mt-1 text-base font-semibold text-white/70">
          <span v-if="currentItem.expiry_date">
            {{ t('driver_maintenance.deadline') }}: {{ formatDate(currentItem.expiry_date) }}
          </span>
          <span v-else-if="currentItem.next_service_date">
            {{ t('driver_maintenance.next_service_date') }}: {{ formatDate(currentItem.next_service_date) }}
          </span>
          <span v-else-if="currentItem.next_service_km">
            {{ t('driver_maintenance.next_service_km') }} {{ currentItem.next_service_km?.toLocaleString('vi-VN') }} km
          </span>
        </p>

        <MaintenanceStatusBadge
          :status="currentItem.status"
          :days-remaining="currentItem.days_remaining"
          class="mt-2"
        />
      </div>

      <div class="mx-auto max-w-lg space-y-4 px-4 pt-5">

        <!-- ── Detail Info Rows ─────────────────────────────────── -->
        <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/8">
          <template v-if="!editMode">
            <DetailRow :label="t('driver_maintenance.detail_issued_by')" :value="currentItem.issued_by" />
            <DetailRow :label="t('driver_maintenance.detail_expires_at')" :value="formatDate(currentItem.expiry_date)" />
            <DetailRow :label="t('driver_maintenance.detail_next_service')" :value="formatDate(currentItem.next_service_date)" />
            <DetailRow :label="t('driver_maintenance.detail_last_service')" :value="formatDate(currentItem.last_service_date)" />
            <DetailRow :label="t('driver_maintenance.detail_last_km')" :value="currentItem.last_service_km != null ? `${currentItem.last_service_km?.toLocaleString('vi-VN')} km` : null" />
            <DetailRow :label="t('driver_maintenance.detail_next_km')" :value="currentItem.next_service_km != null ? `${currentItem.next_service_km?.toLocaleString('vi-VN')} km` : null" />
            <DetailRow :label="t('driver_maintenance.detail_cost')" :value="formatMoney(currentItem.estimated_renewal_cost)" />
            <DetailRow :label="t('driver_maintenance.detail_notes')" :value="currentItem.notes" />
          </template>

          <!-- Edit form -->
          <template v-else>
            <div class="space-y-4 px-4 py-4">
              <EditField
                v-if="currentItem.expiry_date !== undefined"
                :label="t('driver_maintenance.detail_expires_at')"
                type="date"
                v-model="editForm.expiry_date"
              />
              <EditField
                v-if="currentItem.next_service_date !== undefined"
                :label="t('driver_maintenance.detail_next_service')"
                type="date"
                v-model="editForm.next_service_date"
              />
              <EditField
                v-if="currentItem.next_service_km !== undefined"
                :label="t('driver_maintenance.detail_next_km')"
                type="number"
                v-model="editForm.next_service_km"
              />
              <EditField
                :label="t('driver_maintenance.detail_issued_by')"
                type="text"
                v-model="editForm.issued_by"
              />
              <EditField
                :label="t('driver_maintenance.detail_cost')"
                type="number"
                v-model="editForm.estimated_renewal_cost"
              />
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-white/60">{{ t('driver_maintenance.detail_notes') }}</label>
                <textarea
                  v-model="editForm.notes"
                  rows="3"
                  autocomplete="off"
                  class="w-full resize-y rounded-xl border border-white/10 bg-driver-surface px-4 py-3 text-base text-white placeholder:text-white/25 [-webkit-appearance:none] [appearance:none] focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50 [color-scheme:dark]"
                />
              </div>
            </div>
          </template>
        </div>

        <!-- Renewal cost input (visible in edit mode only, separate for clarity) -->
        <div v-if="editMode" class="overflow-hidden rounded-2xl bg-driver-card px-4 py-4 ring-1 ring-white/8">
          <EditField
            :label="t('driver_maintenance.history_amount') + ' (lần gia hạn này)'"
            type="number"
            v-model="editForm.amount_paid"
          />
        </div>

        <!-- Save / error row in edit mode -->
        <div v-if="editMode" class="space-y-2">
          <p v-if="saveError" class="rounded-xl bg-[#2a1010] px-3 py-2 text-sm text-[#ff9999]">{{ saveError }}</p>
          <button
            type="button"
            :disabled="saving"
            class="flex min-h-[52px] w-full items-center justify-center rounded-[14px] bg-[#7fdcc8] text-[16px] font-bold text-[#0a0f0d] shadow transition disabled:opacity-60"
            @click="onSave"
          >
            {{ saving ? t('driver_maintenance.action_saving') : t('driver_maintenance.action_save') }}
          </button>
          <button
            type="button"
            class="flex min-h-[44px] w-full items-center justify-center rounded-[14px] text-sm text-white/50"
            @click="editMode = false"
          >
            {{ t('driver_maintenance.reminder_cancel') }}
          </button>
        </div>

        <!-- ── Reminder Toggle ──────────────────────────────────── -->
        <MaintenanceReminderToggle
          v-model:model-enabled="reminderEnabled"
          v-model:model-days="reminderDays"
        />

        <!-- ── Image Uploader ───────────────────────────────────── -->
        <MaintenanceImageUploader
          :images="currentItem.images ?? []"
          :uploading="imageUploading"
          :upload-error="imageError"
          @upload="onUploadImage"
        />

        <!-- ── History Timeline ─────────────────────────────────── -->
        <MaintenanceHistoryTimeline :history="currentItem.history ?? []" />

        <!-- Spacer -->
        <div class="h-4" />
      </div>
    </template>

    <!-- ── Success Toast ──────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="toast">
        <div
          v-if="showSuccess"
          class="fixed left-1/2 top-[calc(1rem+env(safe-area-inset-top))] z-50 -translate-x-1/2"
        >
          <div class="flex items-center gap-2 rounded-full bg-[#1dbc5e] px-4 py-2.5 shadow-lg shadow-[#1dbc5e]/30">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-white" aria-hidden="true">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-bold text-white">{{ t('driver_maintenance.save_success') }}</span>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Bottom Action Buttons ──────────────────────────────────── -->
    <div
      v-if="currentItem && !editMode"
      class="fixed bottom-[calc(var(--driver-bottom-nav-height,3.5rem)+env(safe-area-inset-bottom))] left-0 right-0 z-30 space-y-2.5 px-4 pb-3 pt-4"
      style="background: linear-gradient(to top, #0a0f0d 70%, transparent);"
    >
      <button
        type="button"
        class="flex min-h-[52px] w-full items-center justify-center rounded-[14px] bg-driver-accent text-[18px] font-bold text-driver-bg shadow-lg shadow-[#7fdcc8]/25 transition active:scale-[0.98]"
        @click="editMode = true"
      >
        {{ t('driver_maintenance.action_renew') }}
      </button>
      <a
        :href="`tel:${t('driver_maintenance.support_phone')}`"
        class="flex min-h-[48px] w-full items-center justify-center rounded-[14px] text-[16px] font-semibold text-[#7fdcc8] ring-1 ring-[#7fdcc8]/30 transition active:scale-[0.98]"
      >
        {{ t('driver_maintenance.action_contact') }}
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import MaintenanceHistoryTimeline from '../../components/maintenance/MaintenanceHistoryTimeline.vue'
import MaintenanceImageUploader from '../../components/maintenance/MaintenanceImageUploader.vue'
import MaintenanceReminderToggle from '../../components/maintenance/MaintenanceReminderToggle.vue'
import MaintenanceStatusBadge from '../../components/maintenance/MaintenanceStatusBadge.vue'
import { useMaintenance } from '../../composables/useMaintenance'

const { t } = useI18n()
const route = useRoute()

const {
  currentItem, detailLoading, detailError,
  fetchDetail, updateItem, uploadImage, formatDate, formatMoney,
} = useMaintenance()

const itemId = computed(() => Number(route.params.id))

// Edit state
const editMode = ref(false)
const saving = ref(false)
const saveError = ref(null)
const showSuccess = ref(false)
const editForm = reactive({
  expiry_date: null,
  next_service_date: null,
  next_service_km: null,
  issued_by: null,
  estimated_renewal_cost: null,
  notes: null,
  amount_paid: null,
  reminder_enabled: false,
  reminder_days_before: 14,
})

// Image upload state
const imageUploading = ref(false)
const imageError = ref(null)

// Reminder sync
const reminderEnabled = ref(false)
const reminderDays = ref(14)

watch(currentItem, (item) => {
  if (!item) return
  editForm.expiry_date = item.expiry_date ?? null
  editForm.next_service_date = item.next_service_date ?? null
  editForm.next_service_km = item.next_service_km ?? null
  editForm.issued_by = item.issued_by ?? null
  editForm.estimated_renewal_cost = item.estimated_renewal_cost ?? null
  editForm.notes = item.notes ?? null
  editForm.amount_paid = null
  reminderEnabled.value = item.reminder_enabled ?? false
  reminderDays.value = item.reminder_days_before ?? 14
}, { immediate: true })

// Sync reminder toggle back to editForm
watch([reminderEnabled, reminderDays], ([en, days]) => {
  editForm.reminder_enabled = en
  editForm.reminder_days_before = days
})

async function onSave() {
  saving.value = true
  saveError.value = null
  try {
    await updateItem(itemId.value, {
      ...editForm,
      reminder_enabled: reminderEnabled.value,
      reminder_days_before: reminderDays.value,
    })
    editMode.value = false
    showSuccess.value = true
    setTimeout(() => { showSuccess.value = false }, 2500)
    // Refresh detail for history
    await fetchDetail(itemId.value)
  } catch (err) {
    saveError.value = err?.response?.data?.message ?? t('driver_maintenance.action_error')
  } finally {
    saving.value = false
  }
}

async function onUploadImage(file) {
  imageUploading.value = true
  imageError.value = null
  try {
    await uploadImage(itemId.value, file)
  } catch {
    imageError.value = t('driver_maintenance.image_upload_error')
  } finally {
    imageUploading.value = false
  }
}

// ── Hero style helpers ──────────────────────────────────────────
const heroBgClass = computed(() => {
  const s = currentItem.value?.status
  if (s === 'urgent') return 'bg-gradient-to-b from-[#2a1010] to-[#0a0f0d]'
  if (s === 'warning') return 'bg-gradient-to-b from-[#1a1400] to-[#0a0f0d]'
  return 'bg-gradient-to-b from-[#0d1f17] to-[#0a0f0d]'
})

const heroTextColor = computed(() => {
  const s = currentItem.value?.status
  if (s === 'urgent') return 'text-[#ff6b6b]'
  if (s === 'warning') return 'text-amber-300'
  return 'text-[#7fdcc8]'
})

const heroIconBg = computed(() => {
  const s = currentItem.value?.status
  if (s === 'urgent') return 'bg-[#ff6b6b]/15'
  if (s === 'warning') return 'bg-amber-400/15'
  return 'bg-[#7fdcc8]/12'
})

const heroIconColor = computed(() => {
  const s = currentItem.value?.status
  if (s === 'urgent') return 'text-[#ff6b6b]'
  if (s === 'warning') return 'text-amber-300'
  return 'text-[#7fdcc8]'
})

const ICONS = {
  registration: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.644 1.59a.75.75 0 0 1 .712 0l9.75 5.25a.75.75 0 0 1 0 1.32l-9.75 5.25a.75.75 0 0 1-.712 0l-9.75-5.25a.75.75 0 0 1 0-1.32l9.75-5.25Z"/><path d="m3.265 10.602 7.668 4.129a2.25 2.25 0 0 0 2.134 0l7.668-4.13 1.37.739a.75.75 0 0 1 0 1.32l-9.75 5.25a.75.75 0 0 1-.71 0l-9.75-5.25a.75.75 0 0 1 0-1.32l1.37-.738Z"/><path d="m10.933 19.231-7.668-4.13-1.37.739a.75.75 0 0 0 0 1.32l9.75 5.25c.221.12.489.12.71 0l9.75-5.25a.75.75 0 0 0 0-1.32l-1.37-.738-7.668 4.13a2.25 2.25 0 0 1-2.134-.001Z"/></svg>` },
  insurance: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/></svg>` },
  oil: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.47 1.72a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 0 1-1.06 1.06l-2.47-2.47V21a.75.75 0 0 1-1.5 0V4.06L8.78 6.53a.75.75 0 0 1-1.06-1.06l3.75-3.75Z"/></svg>` },
  tire: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1-.21-1.298L9.75 12l-1.64-1.073a1.125 1.125 0 0 1 0-1.908l1.16-.551a1.125 1.125 0 0 0 .12-1.97l-.065-.038c-.144-.087-.37-.1-.531-.02l-1.793.896a1.125 1.125 0 0 1-.924.015l-.88-.44a1.125 1.125 0 0 0-1.386.206 8.25 8.25 0 0 0 1.81 7.878Z" clip-rule="evenodd"/></svg>` },
  filter: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.792 2.938A49.069 49.069 0 0 1 12 2.25c2.797 0 5.54.236 8.209.688a1.857 1.857 0 0 1 1.541 1.836v1.044a3 3 0 0 1-.879 2.121l-6.182 6.182a1.5 1.5 0 0 0-.439 1.061v2.927a3 3 0 0 1-1.658 2.684l-1.5.75a3 3 0 0 1-4.342-2.684V15.11a1.5 1.5 0 0 0-.44-1.06L3.41 7.866A3 3 0 0 1 2.53 5.74V4.696a1.857 1.857 0 0 1 1.261-1.758Z" clip-rule="evenodd"/></svg>` },
  brake: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm0 8.625a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25ZM15.375 12a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" clip-rule="evenodd"/></svg>` },
  document: { template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75-6.75a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z" clip-rule="evenodd"/></svg>` },
}

const heroIconComponent = computed(() => {
  const icon = currentItem.value?.icon ?? 'document'
  return ICONS[icon] ?? ICONS.document
})

// ── Inlined helper sub-components ─────────────────────────────
const DetailRow = {
  props: { label: String, value: [String, Number] },
  template: `
    <div class="flex items-start justify-between gap-3 border-b border-white/6 px-4 py-3 last:border-0">
      <span class="text-sm text-white/45">{{ label }}</span>
      <span class="text-right text-sm font-semibold text-white/85">{{ value ?? '—' }}</span>
    </div>
  `,
}

const EditField = {
  props: { label: String, type: { default: 'text' }, modelValue: [String, Number], placeholder: String },
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="mb-1.5 block text-sm font-semibold text-white/60">{{ label }}</label>
      <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder ?? label"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full rounded-xl bg-white/8 px-4 py-3 text-base text-white placeholder:text-white/25 ring-1 ring-white/12 focus:outline-none focus:ring-[#7fdcc8]/50 [color-scheme:dark]"
      />
    </div>
  `,
}

onMounted(() => fetchDetail(itemId.value))
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px);
}
.toast-enter-to,
.toast-leave-from {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
</style>
