<template>
  <div class="min-h-full w-full bg-driver-bg pb-[calc(7rem+env(safe-area-inset-bottom))] text-driver-ink">
    <header
      class="sticky top-0 z-40 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md"
      :style="{ paddingTop: 'max(0.5rem, env(safe-area-inset-top))' }"
    >
      <div class="mx-auto flex max-w-lg items-center gap-2 px-3 pt-2 sm:max-w-2xl">
        <button
          type="button"
          class="flex min-h-[48px] min-w-[48px] shrink-0 items-center justify-center rounded-2xl bg-driver-card ring-1 ring-white/[0.08] active:scale-[0.97]"
          :aria-label="t('checklist.back')"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-6 w-6 text-driver-accent" />
        </button>
        <div class="min-w-0 flex-1 text-center">
          <h1 class="truncate text-lg font-bold">{{ sub ? t(`checklist.freq_${sub.period_type}`) : t('checklist.page_title') }}</h1>
          <p v-if="sub" class="truncate text-xs text-driver-muted">{{ sub.code }} · {{ sub.vehicle?.license_plate }}</p>
        </div>
        <span class="w-[48px] shrink-0 text-right text-[11px] text-driver-muted" aria-live="polite">{{ saveStateLabel }}</span>
      </div>
      <div v-if="sub && editable" class="mx-auto max-w-lg px-4 pb-2 pt-2 sm:max-w-2xl">
        <div class="h-2 overflow-hidden rounded-full bg-white/10" role="progressbar" :aria-valuenow="doneCount" :aria-valuemax="items.length">
          <div class="h-full rounded-full bg-driver-accent transition-all" :style="{ width: `${progressPct}%` }" />
        </div>
        <p class="mt-1 text-right text-xs tabular-nums text-driver-muted">{{ t('checklist.progress', { done: doneCount, total: items.length }) }}</p>
      </div>
    </header>

    <div class="mx-auto max-w-lg space-y-4 px-4 pt-4 sm:max-w-2xl">
      <p v-if="loadError" class="rounded-xl bg-amber-500/15 px-3 py-2 text-sm text-amber-100">{{ loadError }}</p>
      <p v-if="loading && !sub" class="text-sm text-driver-muted">{{ t('checklist.loading') }}</p>

      <template v-if="sub">
        <!-- Kết luận (đã nộp) -->
        <section
          v-if="!editable"
          class="rounded-[1.25rem] p-4 ring-1"
          :class="sub.result === 'fail' ? 'bg-rose-500/15 ring-rose-400/30' : 'bg-emerald-500/15 ring-emerald-400/30'"
          data-testid="checklist-result-card"
        >
          <p class="text-lg font-bold">{{ sub.result === 'fail' ? t('checklist.result_fail_title') : t('checklist.result_pass_title') }}</p>
          <p class="mt-1 text-sm">{{ sub.result === 'fail' ? t('checklist.result_fail_desc', { count: sub.critical_ng_count }) : t('checklist.result_pass_desc') }}</p>
          <p class="mt-2 text-xs text-driver-muted">{{ t('checklist.submitted_at', { time: formatDateTime(sub.submitted_at) }) }}</p>
          <p v-if="sub.review_note" class="mt-2 text-sm">{{ t('checklist.review_note') }}: {{ sub.review_note }}</p>
          <button
            type="button"
            class="mt-3 flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-driver-card text-sm font-bold text-driver-accent ring-1 ring-white/10"
            :disabled="downloading"
            @click="downloadPdf"
          >
            <ArrowDownTrayIcon class="h-5 w-5" /> {{ t('checklist.download_pdf') }}
          </button>
        </section>

        <!-- Thông tin chuyến -->
        <section class="rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
          <h2 class="text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('checklist.trip_info') }}</h2>
          <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
            <div><dt class="text-xs text-driver-muted">{{ t('checklist.info_date') }}</dt><dd class="font-semibold">{{ sub.trip_info?.date }}</dd></div>
            <div><dt class="text-xs text-driver-muted">{{ t('checklist.info_plate') }}</dt><dd class="font-semibold">{{ sub.vehicle?.license_plate }}</dd></div>
            <div class="col-span-2"><dt class="text-xs text-driver-muted">{{ t('checklist.info_driver') }}</dt><dd class="font-semibold">{{ sub.trip_info?.driver_name }} · {{ sub.trip_info?.driver_phone }}</dd></div>
          </dl>
          <div class="mt-3 grid gap-2">
            <label class="text-xs text-driver-muted">
              {{ t('checklist.info_route') }}
              <input v-model="tripInfo.campus_route" :readonly="!editable" type="text" maxlength="255" class="mt-1 w-full rounded-lg border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink" @input="markHeaderDirty" />
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label class="text-xs text-driver-muted">
                {{ t('checklist.info_departure') }}
                <input v-model="tripInfo.departure_time" :readonly="!editable" type="text" maxlength="20" placeholder="06:30" class="mt-1 w-full rounded-lg border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink" @input="markHeaderDirty" />
              </label>
              <label class="text-xs text-driver-muted">
                {{ t('checklist.info_attendant') }}
                <input v-model="tripInfo.attendant" :readonly="!editable" type="text" maxlength="255" class="mt-1 w-full rounded-lg border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink" @input="markHeaderDirty" />
              </label>
            </div>
          </div>
        </section>

        <!-- Mục kiểm tra theo nhóm -->
        <section v-for="g in groups" :key="g.name" class="rounded-[1.25rem] bg-driver-card p-3 ring-1 ring-white/[0.06]">
          <h2 class="flex items-center justify-between px-1 pb-2 text-sm font-bold uppercase tracking-wide text-driver-muted">
            <span>{{ g.name }}</span>
            <span class="tabular-nums">{{ g.done }}/{{ g.items.length }}</span>
          </h2>
          <ul class="space-y-2">
            <ChecklistItemRow
              v-for="item in g.items"
              :key="item.id"
              :item="item"
              :editable="editable"
              :error="itemErrors[item.id] || ''"
              :uploading="uploadingItemId === item.id"
              @update:result="(v) => setResult(item, v)"
              @update:note="(v) => setNote(item, v)"
              @upload-photo="(f) => uploadPhoto(item, f)"
              @remove-photo="(a) => removePhoto(item, a)"
            />
          </ul>
        </section>

        <!-- Ghi chú + cam kết + nộp -->
        <section v-if="editable" class="space-y-3 rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
          <label class="block text-sm font-semibold text-driver-muted">
            {{ t('checklist.general_note') }}
            <textarea v-model="headerNote" rows="2" maxlength="2000" class="mt-1.5 w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink" @input="markHeaderDirty" />
          </label>
          <label class="block text-sm font-semibold text-driver-muted">
            {{ t('checklist.inspector') }}
            <input v-model="inspectorName" type="text" maxlength="255" class="mt-1.5 w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink" @input="markHeaderDirty" />
          </label>

          <div v-if="criticalNgCount > 0" class="rounded-xl bg-rose-500/15 px-3 py-2.5 text-sm text-rose-100 ring-1 ring-rose-400/30" role="alert">
            {{ t('checklist.warn_critical', { count: criticalNgCount }) }}
          </div>

          <label class="flex items-start gap-3 rounded-xl bg-driver-surface p-3 ring-1 ring-white/10">
            <input v-model="confirmed" type="checkbox" class="mt-1 h-5 w-5 shrink-0 accent-[#7fdcc8]" data-testid="checklist-confirm" />
            <span class="text-sm leading-snug">{{ statement }}</span>
          </label>

          <p v-if="submitError" class="text-sm text-rose-300">{{ submitError }}</p>

          <button
            type="button"
            class="flex min-h-[52px] w-full items-center justify-center rounded-xl bg-driver-accent py-3 text-base font-bold text-driver-bg transition hover:brightness-110 disabled:opacity-45 active:scale-[0.99]"
            :disabled="submitting || !confirmed || doneCount < items.length"
            data-testid="checklist-submit"
            @click="submit"
          >
            {{ submitting ? t('checklist.submitting') : doneCount < items.length ? t('checklist.submit_remaining', { n: items.length - doneCount }) : t('checklist.submit') }}
          </button>
          <button type="button" class="min-h-[44px] w-full text-sm font-semibold text-driver-muted" :disabled="submitting" @click="discard">
            {{ t('checklist.discard') }}
          </button>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import {
  deleteChecklistEvidence,
  discardChecklist,
  downloadChecklistPdf,
  getMyChecklist,
  saveChecklistDraft,
  submitChecklist,
  uploadChecklistEvidence,
} from '../../api/checklists'
import { confirmAction } from '../../composables/useConfirm'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import ChecklistItemRow from '../../components/driver/checklist/ChecklistItemRow.vue'

const AUTOSAVE_MS = 1200

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const sub = ref(null)
const items = ref([])
const loading = ref(false)
const loadError = ref('')
const tripInfo = reactive({ campus_route: '', departure_time: '', attendant: '' })
const headerNote = ref('')
const inspectorName = ref('')
const confirmed = ref(false)
const submitting = ref(false)
const submitError = ref('')
const itemErrors = ref({})
const uploadingItemId = ref(null)
const downloading = ref(false)

const dirtyItems = new Set()
let headerDirty = false
let saveTimer = null
const saveState = ref('idle') // idle | pending | saving | saved | error
let savePromise = null

const editable = computed(() => sub.value?.status === 'draft')
const doneCount = computed(() => items.value.filter((i) => i.result).length)
const progressPct = computed(() => (items.value.length ? Math.round((doneCount.value / items.value.length) * 100) : 0))
const criticalNgCount = computed(() => items.value.filter((i) => i.result === 'ng' && i.severity === 'critical').length)
const statement = computed(() =>
  String(sub.value?.form_meta?.confirm_statement || t('checklist.default_statement')).replace(':plate', sub.value?.vehicle?.license_plate || '…'),
)
const saveStateLabel = computed(() => ({ pending: '…', saving: t('checklist.saving'), saved: t('checklist.saved'), error: t('checklist.save_error') }[saveState.value] || ''))

const groups = computed(() => {
  const map = new Map()
  for (const item of items.value) {
    if (!map.has(item.group_name)) map.set(item.group_name, [])
    map.get(item.group_name).push(item)
  }
  return [...map.entries()].map(([name, list]) => ({ name, items: list, done: list.filter((i) => i.result).length }))
})

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverChecklists' })
}

function formatDateTime(iso) {
  return iso ? new Date(iso).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''
}

function applyServer(data) {
  sub.value = data
  items.value = (data.items ?? []).map((i) => ({ ...i, attachments: i.attachments ?? [] }))
  tripInfo.campus_route = data.trip_info?.campus_route ?? ''
  tripInfo.departure_time = data.trip_info?.departure_time ?? ''
  tripInfo.attendant = data.trip_info?.attendant ?? ''
  headerNote.value = data.note ?? ''
  inspectorName.value = data.inspector_name ?? ''
}

/** Gộp thay đổi từ server nhưng giữ giá trị người dùng đang sửa (chưa lưu). */
function mergeServer(data) {
  const local = new Map(items.value.map((i) => [i.id, i]))
  sub.value = data
  items.value = (data.items ?? []).map((i) => {
    const mine = local.get(i.id)
    if (mine && dirtyItems.has(i.id)) return { ...i, result: mine.result, note: mine.note, attachments: i.attachments ?? [] }
    return { ...i, attachments: i.attachments ?? [] }
  })
}

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    applyServer(await getMyChecklist(route.params.id))
  } catch (err) {
    loadError.value = err?.response?.data?.message || t('checklist.load_error')
  } finally {
    loading.value = false
  }
}

function buildPayload() {
  const payload = { lock_version: sub.value.lock_version }
  const changed = items.value.filter((i) => dirtyItems.has(i.id)).map((i) => ({ id: i.id, result: i.result ?? null, note: i.note ?? null }))
  if (changed.length) payload.items = changed
  if (headerDirty) {
    payload.trip_info = { ...tripInfo }
    payload.note = headerNote.value || null
    payload.inspector_name = inspectorName.value || null
  }
  return payload
}

function scheduleSave() {
  if (!editable.value) return
  saveState.value = 'pending'
  clearTimeout(saveTimer)
  saveTimer = setTimeout(() => void flushSave(), AUTOSAVE_MS)
}

async function flushSave() {
  clearTimeout(saveTimer)
  if (savePromise) await savePromise
  if (!dirtyItems.size && !headerDirty) return
  const payload = buildPayload()
  const sentIds = [...dirtyItems]
  dirtyItems.clear()
  headerDirty = false
  saveState.value = 'saving'
  savePromise = (async () => {
    try {
      mergeServer(await saveChecklistDraft(sub.value.id, payload))
      saveState.value = 'saved'
    } catch (err) {
      sentIds.forEach((id) => dirtyItems.add(id))
      saveState.value = 'error'
      if (err?.response?.status === 409) {
        showAppError(err.response.data?.message || t('checklist.conflict'))
        await load()
      }
    } finally {
      savePromise = null
    }
  })()
  await savePromise
}

function markHeaderDirty() {
  headerDirty = true
  scheduleSave()
}

function setResult(item, value) {
  if (!editable.value) return
  item.result = value
  item.checked_at = value ? new Date().toISOString() : null
  delete itemErrors.value[item.id]
  dirtyItems.add(item.id)
  scheduleSave()
}

function setNote(item, value) {
  item.note = value
  dirtyItems.add(item.id)
  scheduleSave()
}

async function uploadPhoto(item, file) {
  uploadingItemId.value = item.id
  try {
    const att = await uploadChecklistEvidence(sub.value.id, item.id, file)
    item.attachments = [...(item.attachments ?? []), att]
    delete itemErrors.value[item.id]
  } catch (err) {
    showAppError(err?.response?.data?.message || t('checklist.photo_error'))
  } finally {
    uploadingItemId.value = null
  }
}

async function removePhoto(item, attachment) {
  try {
    await deleteChecklistEvidence(sub.value.id, item.id, attachment.id)
    item.attachments = item.attachments.filter((a) => a.id !== attachment.id)
  } catch (err) {
    showAppError(err?.response?.data?.message || t('checklist.photo_error'))
  }
}

function mapErrors(errors) {
  const out = {}
  for (const [key, messages] of Object.entries(errors || {})) {
    const m = key.match(/^items\.(\d+)\./)
    if (m) out[Number(m[1])] = [out[Number(m[1])], ...(Array.isArray(messages) ? messages : [messages])].filter(Boolean).join(' ')
  }
  return out
}

async function submit() {
  if (submitting.value) return
  submitting.value = true
  submitError.value = ''
  try {
    await flushSave()
    const payload = { ...buildPayload(), confirm: true, client_submitted_at: new Date().toISOString() }
    const data = await submitChecklist(sub.value.id, payload)
    dirtyItems.clear()
    headerDirty = false
    applyServer(data)
    showAppSuccess(data.result === 'fail' ? t('checklist.submitted_fail') : t('checklist.submitted_ok'), t('checklist.submitted_title'))
    window.scrollTo?.({ top: 0 })
  } catch (err) {
    const res = err?.response
    if (res?.status === 422 && res.data?.errors) {
      itemErrors.value = mapErrors(res.data.errors)
      submitError.value = t('checklist.submit_invalid')
      const first = Object.keys(itemErrors.value)[0]
      if (first) document.querySelector(`[data-testid="checklist-item-${first}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    } else {
      submitError.value = res?.data?.message || t('checklist.submit_error')
    }
  } finally {
    submitting.value = false
  }
}

async function discard() {
  const ok = await confirmAction({ title: t('checklist.discard'), message: t('checklist.discard_confirm'), danger: true })
  if (!ok) return
  try {
    clearTimeout(saveTimer)
    await discardChecklist(sub.value.id)
    await router.replace({ name: 'driverChecklists' })
  } catch (err) {
    showAppError(err?.response?.data?.message || t('checklist.submit_error'))
  }
}

async function downloadPdf() {
  downloading.value = true
  try {
    await downloadChecklistPdf(sub.value, 'driver')
  } catch {
    showAppError(t('checklist.download_error'))
  } finally {
    downloading.value = false
  }
}

onMounted(load)
onBeforeUnmount(() => {
  if (dirtyItems.size || headerDirty) void flushSave()
})
</script>
