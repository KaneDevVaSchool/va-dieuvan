<template>
  <div
    class="driver-cost-detail -mx-3 min-h-[calc(100dvh-6rem)] w-[calc(100%+1.5rem)] bg-[#ECEEF1] pb-32 sm:mx-auto sm:min-h-0 sm:w-full sm:max-w-lg sm:pb-8"
  >
    <div class="bg-[#001a2e] px-3 py-3 text-white sm:rounded-b-2xl sm:px-4">
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10"
          :aria-label="t('driver_cost_req.back')"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-5 w-5" />
        </button>
        <h1 class="min-w-0 flex-1 text-center text-base font-bold">
          {{ t('driver_cost_req.title') }}
        </h1>
        <div class="w-10" />
      </div>
    </div>

    <div class="space-y-3 px-3 pt-3 sm:px-0">
      <p
        v-if="loadError"
        class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
      >
        {{ loadError }}
      </p>
      <div v-if="loading" class="space-y-2">
        <div class="h-32 animate-pulse rounded-2xl bg-slate-200" />
        <div class="h-40 animate-pulse rounded-2xl bg-slate-200" />
      </div>
      <template v-else-if="cost">
        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm">
          <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-sm font-bold text-slate-900">{{ t('driver_cost_req.approval_title') }}</h2>
            <span
              class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wide"
              :class="statusBadgeClass"
            >
              {{ statusBadgeText }}
            </span>
          </div>
          <div class="relative flex justify-between gap-0 px-0.5">
            <div
              class="absolute left-[16%] right-[16%] top-[0.6rem] h-0.5 rounded bg-slate-200"
              aria-hidden="true"
            />
            <div
              class="absolute left-[16%] top-[0.6rem] h-0.5 rounded bg-amber-500 transition-all"
              :style="{ width: stepperLineWidth }"
            />
            <div
              v-for="(s, i) in stepperDisplay"
              :key="i"
              class="relative z-10 flex w-[32%] flex-col items-center text-center"
            >
              <div
                class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-xs"
                :class="s.iconClass"
              >
                <CheckIcon v-if="s.state === 'done'" class="h-3.5 w-3.5" />
                <ClockIcon
                  v-else-if="s.state === 'current'"
                  class="h-3.5 w-3.5"
                />
                <XMarkIcon v-else-if="s.state === 'bad'" class="h-3.5 w-3.5" />
                <span v-else class="h-1.5 w-1.5 rounded-full bg-slate-300" />
              </div>
              <p class="mt-1.5 text-[10px] font-medium leading-tight text-slate-800">{{ s.label }}</p>
              <p v-if="s.caption" class="mt-0.5 text-[9px] text-slate-500">{{ s.caption }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
            {{ t('driver_cost_req.cost_type_label') }}
          </p>
          <div class="mt-2 flex items-start gap-2.5">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-700"
            >
              <MapIcon v-if="cost.type === 'toll' || cost.type === 'parking'" class="h-5 w-5" />
              <FireIcon v-else-if="cost.type === 'fuel'" class="h-5 w-5" />
              <BanknotesIcon v-else class="h-5 w-5" />
            </div>
            <p class="text-lg font-bold text-[#001a2e]">{{ typeLabelUi(cost.type) }}</p>
          </div>
          <p class="mt-1 text-[10px] text-slate-500">{{ t('driver_cost_req.amount_label') }}</p>
          <p class="text-2xl font-bold tabular-nums text-slate-900">{{ formatVnd(cost.amount) }}</p>
          <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-sm">
            <div>
              <p class="text-[10px] text-slate-500">{{ t('driver_cost_req.date_label') }}</p>
              <p class="font-semibold text-slate-900">{{ dateLabel }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">{{ t('driver_cost_req.time_label') }}</p>
              <p class="font-semibold text-slate-900">{{ timeLabel }}</p>
            </div>
          </div>
        </div>

        <RouterLink
          v-if="cost.trip_id"
          :to="`/driver/trips/${cost.trip_id}`"
          class="flex items-center gap-2.5 rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm"
        >
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100">
            <MapIcon class="h-4 w-4 text-slate-700" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] text-slate-500">{{ t('driver_cost_req.trip_label') }}</p>
            <p class="text-sm font-bold text-[#001a2e]">#TR-{{ cost.trip_id }}</p>
            <p class="truncate text-xs text-slate-600">{{ tripRouteLine }}</p>
          </div>
          <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-300" />
        </RouterLink>

        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm">
          <h2 class="text-sm font-bold text-slate-900">{{ t('driver_cost_req.images_title') }}</h2>
          <div
            v-if="!imageSources.length"
            class="mt-2 rounded-lg border border-dashed border-slate-200 py-6 text-center text-xs text-slate-500"
          >
            {{ t('driver_cost_req.no_images') }}
          </div>
          <div v-else class="mt-2 grid grid-cols-2 gap-2">
            <a
              v-for="(src, i) in imageSources"
              :key="i"
              :href="src"
              target="_blank"
              rel="noopener"
              class="block overflow-hidden rounded-xl bg-slate-100"
            >
              <img
                :src="src"
                alt=""
                class="aspect-square w-full object-cover"
                @error="onImgErr"
              />
            </a>
          </div>
        </div>

        <div
          v-if="cost.description"
          class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm"
        >
          <h2 class="text-sm font-bold text-slate-900">{{ t('driver_cost_req.notes_title') }}</h2>
          <p class="mt-2 rounded-xl bg-slate-50/95 px-3 py-2.5 text-sm text-slate-700">
            {{ cost.description }}
          </p>
        </div>
      </template>
    </div>

    <div
      v-if="cost && canAct"
      class="safe-pb fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200/80 bg-white/95 px-3 py-3 sm:static sm:mt-4 sm:border-0"
    >
      <div class="mx-auto flex max-w-lg gap-2 sm:px-0">
        <button
          type="button"
          class="flex flex-1 items-center justify-center gap-1.5 rounded-2xl border-2 border-rose-500 py-2.5 text-sm font-bold text-rose-600"
          :disabled="actionBusy"
          @click="onCancel"
        >
          <TrashIcon class="h-4 w-4" />
          {{ t('driver_cost_req.btn_cancel') }}
        </button>
        <button
          type="button"
          class="flex flex-1 items-center justify-center gap-1.5 rounded-2xl py-2.5 text-sm font-bold text-white"
          style="background: #001a2e"
          :disabled="actionBusy"
          @click="openEdit"
        >
          <PencilSquareIcon class="h-4 w-4" />
          {{ t('driver_cost_req.btn_edit') }}
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="editOpen"
        class="fixed inset-0 z-40 flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
        @click.self="editOpen = false"
      >
        <div
          class="w-full max-w-md rounded-t-2xl bg-white p-4 shadow-2xl sm:rounded-2xl"
          @click.stop
        >
          <h3 class="text-base font-bold text-[#001a2e]">{{ t('driver_cost_req.edit_title') }}</h3>
          <label class="mt-3 block text-[11px] font-medium text-slate-500">
            {{ t('driver_trip_detail.cost_type') }}
          </label>
          <select
            v-model="editForm.type"
            class="mt-0.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
          >
            <option value="fuel">{{ typeLabelUi('fuel') }}</option>
            <option value="toll">{{ typeLabelUi('toll') }}</option>
            <option value="parking">{{ typeLabelUi('parking') }}</option>
            <option value="other">{{ typeLabelUi('other') }}</option>
          </select>
          <label class="mt-2 block text-[11px] text-slate-500">{{ t('driver_trip_detail.cost_amount') }}</label>
          <input
            v-model="editForm.amount"
            type="text"
            class="mt-0.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            inputmode="numeric"
          />
          <label class="mt-2 block text-[11px] text-slate-500">{{ t('driver_trip_detail.cost_desc') }}</label>
          <input
            v-model="editForm.description"
            type="text"
            class="mt-0.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
          />
          <p v-if="editError" class="mt-2 text-xs text-rose-600">{{ editError }}</p>
          <div class="mt-4 flex gap-2">
            <button
              type="button"
              class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold"
              @click="editOpen = false"
            >
              {{ t('driver_trip_detail.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-xl py-2.5 text-sm font-bold text-white"
              style="background: #001a2e"
              :disabled="editBusy"
              @click="saveEdit"
            >
              {{ t('driver_trip_detail.confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  BanknotesIcon,
  CheckIcon,
  ChevronRightIcon,
  ClockIcon,
  FireIcon,
  MapIcon,
  PencilSquareIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { deleteTripCost, getTripCost, updateTripCost } from '../../api/costs'
import { formatVnd } from '../../util/labels'

const { t, te } = useI18n()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const loadError = ref('')
const cost = ref(null)
const actionBusy = ref(false)
const editOpen = ref(false)
const editBusy = ref(false)
const editError = ref('')
const editForm = ref({ type: 'toll', amount: '', description: '' })

const id = computed(() => {
  const n = Number(route.params.id)
  return Number.isFinite(n) && n > 0 ? n : null
})

const canAct = computed(
  () => cost.value && ['draft', 'submitted'].includes(cost.value.status),
)

const dr = computed(() => cost.value?.trip?.dispatch_request)
const statusBadgeClass = computed(() => {
  const s = cost.value?.status
  if (s === 'confirmed') return 'bg-emerald-100 text-emerald-800'
  if (s === 'rejected') return 'bg-rose-100 text-rose-800'
  if (s === 'submitted') return 'bg-amber-100 text-amber-800'
  return 'bg-slate-100 text-slate-700'
})

const statusBadgeText = computed(() => {
  const s = cost.value?.status
  if (s === 'confirmed') return t('driver_cost_req.badge_done')
  if (s === 'rejected') return t('driver_cost_req.badge_rejected')
  if (s === 'submitted') return t('driver_cost_req.badge_pending')
  return t('driver_cost_req.badge_draft')
})

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverCosts' })
}

function typeLabelUi(type) {
  const k = `driver_trip_detail.cost_type_${String(type || 'other')}`
  if (te(k)) return t(k)
  return type
}

const dateLabel = computed(() => {
  if (!cost.value?.created_at) return '—'
  const d = new Date(cost.value.created_at)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
})

const timeLabel = computed(() => {
  if (!cost.value?.created_at) return '—'
  const d = new Date(cost.value.created_at)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
})

const tripRouteLine = computed(() => {
  const o = (dr.value?.origin || '').trim()
  const dest = (dr.value?.destination || '').trim()
  if (o && dest) return `${o} → ${dest}`
  return o || dest || '—'
})

const imageSources = computed(() => {
  const c = cost.value
  if (!c) return []
  const at = c.attachments
  if (Array.isArray(at) && at.length) {
    return at.map((a) => a.url).filter(Boolean)
  }
  if (c.receipt_url && /^https?:/i.test(c.receipt_url)) return [c.receipt_url]
  return []
})

function onImgErr(e) {
  e?.target && (e.target.style.display = 'none')
}

const stepperLineWidth = computed(() => {
  const s = cost.value?.status
  if (s === 'confirmed') return '68%'
  if (s === 'submitted') return '34%'
  if (s === 'rejected') return '34%'
  if (s === 'draft') return '0%'
  return '0%'
})

const stepperDisplay = computed(() => {
  const c = cost.value
  if (!c) return []
  const s = c.status
  const created = c.created_at
  const sentD = shortDate(created)
  if (s === 'draft') {
    return [
      { state: 'current', label: t('driver_cost_req.st_draft'), caption: sentD, iconClass: 'border-amber-500 bg-amber-50 text-amber-700' },
      { state: 'up', label: t('driver_cost_req.st_review'), caption: '—', iconClass: 'border-slate-200 bg-white' },
      { state: 'up', label: t('driver_cost_req.st_done'), caption: '—', iconClass: 'border-slate-200 bg-white' },
    ]
  }
  if (s === 'submitted') {
    return [
      { state: 'done', label: t('driver_cost_req.st_sent'), caption: sentD, iconClass: 'border-emerald-500 bg-emerald-500 text-white' },
      {
        state: 'current',
        label: t('driver_cost_req.st_review'),
        caption: t('driver_cost_req.caption_today'),
        iconClass: 'border-amber-500 bg-amber-50 text-amber-600',
      },
      { state: 'up', label: t('driver_cost_req.st_done'), caption: '—', iconClass: 'border-slate-200 bg-white' },
    ]
  }
  if (s === 'confirmed') {
    const fin = shortDate(c.confirmed_at || c.updated_at)
    return [
      { state: 'done', label: t('driver_cost_req.st_sent'), caption: sentD, iconClass: 'border-emerald-500 bg-emerald-500 text-white' },
      {
        state: 'done',
        label: t('driver_cost_req.st_review'),
        caption: t('driver_cost_req.caption_approved'),
        iconClass: 'border-emerald-500 bg-emerald-500 text-white',
      },
      { state: 'done', label: t('driver_cost_req.st_done'), caption: fin, iconClass: 'border-emerald-500 bg-emerald-500 text-white' },
    ]
  }
  if (s === 'rejected') {
    return [
      { state: 'done', label: t('driver_cost_req.st_sent'), caption: sentD, iconClass: 'border-emerald-500 bg-emerald-500 text-white' },
      {
        state: 'bad',
        label: t('driver_cost_req.st_reject_short'),
        caption: '',
        iconClass: 'border-rose-500 bg-rose-50 text-rose-600',
      },
      { state: 'up', label: t('driver_cost_req.st_done'), caption: '—', iconClass: 'border-slate-200 bg-white' },
    ]
  }
  return [
    { state: 'done', label: t('driver_cost_req.st_sent'), caption: sentD, iconClass: 'border-emerald-500 bg-emerald-500 text-white' },
    { state: 'done', label: t('driver_cost_req.st_review'), caption: '—', iconClass: 'border-slate-200 bg-white' },
    { state: 'done', label: t('driver_cost_req.st_done'), caption: '—', iconClass: 'border-slate-200 bg-white' },
  ]
})

function shortDate(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}

async function load() {
  if (id.value == null) {
    loadError.value = t('driver_trip_detail.err_bad_id')
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    cost.value = await getTripCost(id.value)
  } catch {
    loadError.value = t('driver_home.load_error')
    cost.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})

watch(
  () => route.params.id,
  () => {
    void load()
  },
)

function openEdit() {
  const c = cost.value
  if (!c) return
  editError.value = ''
  editForm.value = {
    type: c.type || 'other',
    amount: String(Math.round(Number(c.amount) || 0)),
    description: c.description || '',
  }
  editOpen.value = true
}

async function saveEdit() {
  if (id.value == null || editBusy.value) return
  const a = String(editForm.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num < 0) {
    editError.value = t('driver_trip_detail.cost_err_amount')
    return
  }
  editBusy.value = true
  editError.value = ''
  try {
    await updateTripCost(id.value, {
      type: editForm.value.type,
      amount: num,
      description: editForm.value.description?.trim() || null,
      currency: 'VND',
    })
    editOpen.value = false
    await load()
  } catch {
    editError.value = t('driver_trip_detail.cost_err_submit')
  } finally {
    editBusy.value = false
  }
}

async function onCancel() {
  if (id.value == null || !canAct.value) return
  if (!window.confirm(t('driver_cost_req.cancel_confirm'))) return
  actionBusy.value = true
  try {
    await deleteTripCost(id.value)
    goBack()
  } catch {
    loadError.value = t('driver_cost_req.cancel_err')
  } finally {
    actionBusy.value = false
  }
}
</script>

<style scoped>
.safe-pb {
  padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0px));
}
</style>
