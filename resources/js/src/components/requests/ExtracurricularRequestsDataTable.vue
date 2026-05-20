<template>
  <div class="space-y-4">
    <div
      class="rounded-2xl border border-violet-200/80 bg-gradient-to-r from-violet-50/90 via-indigo-50/40 to-white px-4 py-3 shadow-sm ring-1 ring-violet-100/60"
    >
      <div class="flex gap-3">
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow-md shadow-violet-500/25"
          aria-hidden="true"
        >
          <AcademicCapIcon class="h-5 w-5" />
        </div>
        <div class="min-w-0">
          <h2 class="text-sm font-bold text-violet-950 sm:text-base">{{ t(`${i18nPrefix}.banner_title`) }}</h2>
          <p class="mt-0.5 text-xs leading-relaxed text-slate-600 sm:text-sm">{{ t(`${i18nPrefix}.banner_lead`) }}</p>
        </div>
      </div>
    </div>

    <div class="hidden overflow-hidden rounded-2xl border border-violet-200/80 bg-white shadow-md shadow-violet-500/5 md:block">
      <table class="min-w-full text-left text-sm">
        <thead
          class="bg-gradient-to-r from-violet-50/90 via-indigo-50/50 to-white text-[11px] font-semibold uppercase tracking-wide text-violet-800"
        >
          <tr>
            <th class="px-4 py-3">{{ t(`${i18nPrefix}.col_request`) }}</th>
            <th class="min-w-[12rem] px-4 py-3">{{ t(`${i18nPrefix}.col_route`) }}</th>
            <th class="whitespace-nowrap px-4 py-3">{{ t(`${i18nPrefix}.col_depart`) }}</th>
            <th class="px-4 py-3">{{ t(`${i18nPrefix}.col_status`) }}</th>
            <th class="whitespace-nowrap px-4 py-3 text-center">{{ t(`${i18nPrefix}.col_plan`) }}</th>
            <th class="min-w-[10rem] px-4 py-3">{{ t(`${i18nPrefix}.col_actual`) }}</th>
            <th class="w-40 px-4 py-3 text-right">{{ t(`${i18nPrefix}.col_actions`) }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-violet-100/80">
          <tr v-for="req in requests" :key="req.id" class="transition hover:bg-violet-50/40">
            <td class="whitespace-nowrap px-4 py-3 align-top">
              <RequestIdCell :req="req" />
            </td>
            <td class="px-4 py-3 align-top">
              <p class="font-medium text-slate-800">{{ routeLine(req) }}</p>
            </td>
            <td class="whitespace-nowrap px-4 py-3 align-top text-xs text-slate-600">
              {{ departFmt(req) }}
            </td>
            <td class="px-4 py-3 align-top">
              <StatusBadge :status="req.status" size="sm" />
            </td>
            <td class="px-4 py-3 align-top text-center tabular-nums text-slate-700">
              {{ row.planStudentCount(req) ?? '—' }}
            </td>
            <td class="px-4 py-3 align-top">
              <StudentCountCell
                :req="req"
                :draft="draftFor(req.id)"
                :saving="savingId === req.id"
                :error="errors[req.id]"
                :can-edit="row.canEditStudentCount(req)"
                :lock-hint="lockHintFor(req)"
                @update:draft="setDraft(req.id, $event)"
                @save="saveRow(req)"
              />
            </td>
            <td class="px-4 py-3 align-top text-right">
              <ExtracurricularRowActions
                :req="req"
                :variant="variant"
                :can-clone="row.canCloneReset(req)"
                :clone-busy="cloneBusyId === req.id"
                @clone="$emit('clone', req)"
                @open-detail="openDetail(req)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="space-y-3 md:hidden">
      <article
        v-for="req in requests"
        :key="'m-' + req.id"
        class="overflow-hidden rounded-2xl border border-violet-200/80 bg-white shadow-sm"
      >
        <div class="border-b border-violet-100/80 bg-violet-50/50 px-4 py-3">
          <div class="mb-2">
            <RequestIdCell :req="req" />
          </div>
          <p class="text-sm font-medium text-slate-800">{{ routeLine(req) }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ departFmt(req) }}</p>
        </div>
        <div class="space-y-3 px-4 py-3">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-slate-500">{{ t(`${i18nPrefix}.col_status`) }}</span>
            <StatusBadge :status="req.status" size="sm" />
          </div>
          <div class="flex justify-between gap-4 text-sm">
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-wide text-violet-700">
                {{ t(`${i18nPrefix}.col_plan`) }}
              </p>
              <p class="mt-0.5 tabular-nums font-medium text-slate-800">{{ row.planStudentCount(req) ?? '—' }}</p>
            </div>
          </div>
          <StudentCountCell
            :req="req"
            :draft="draftFor(req.id)"
            :saving="savingId === req.id"
            :error="errors[req.id]"
            :can-edit="row.canEditStudentCount(req)"
            :lock-hint="lockHintFor(req)"
            mobile
            @update:draft="setDraft(req.id, $event)"
            @save="saveRow(req)"
          />
          <ExtracurricularRowActions
            :req="req"
            :variant="variant"
            :can-clone="row.canCloneReset(req)"
            :clone-busy="cloneBusyId === req.id"
            block
            @clone="$emit('clone', req)"
            @open-detail="openDetail(req)"
          />
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { AcademicCapIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'
import StudentCountCell from './extracurricular/StudentCountCell.vue'
import ExtracurricularRowActions from './extracurricular/ExtracurricularRowActions.vue'
import { useExtracurricularRequestRow } from '../../composables/useExtracurricularRequestRow'
import { useAuthStore } from '../../store'
import { patchPassengerCount } from '../../api/requests'
import { formatApiError } from '../../api/http'

const RequestIdCell = defineComponent({
  name: 'RequestIdCell',
  props: { req: { type: Object, required: true } },
  setup(props) {
    const { t } = useI18n()
    return () =>
      h('div', { class: 'flex flex-wrap items-center gap-1.5' }, [
        h('span', { class: 'font-mono text-sm font-bold text-slate-900' }, `#${props.req.id}`),
        props.req.dispatch_request_template_id
          ? h(
              'span',
              {
                class:
                  'inline-flex items-center gap-0.5 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase text-sky-900',
              },
              [
                h(ArrowPathIcon, { class: 'h-3 w-3', 'aria-hidden': 'true' }),
                t('request_detail.badge_recurring'),
              ],
            )
          : null,
      ])
  },
})

const props = defineProps({
  requests: { type: Array, required: true },
  variant: { type: String, default: 'portal' },
})

const emit = defineEmits(['refresh', 'clone'])

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const i18nPrefix = computed(() =>
  props.variant === 'portal' ? 'portal.extracurricular_table' : 'requests_page.extracurricular_table',
)

const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const drafts = reactive({})
const errors = reactive({})
const savingId = ref(null)
const cloneBusyId = ref(null)

watch(
  () => props.requests,
  (list) => {
    for (const req of list || []) {
      drafts[req.id] = row.actualStudentCount(req)
    }
  },
  { immediate: true, deep: true },
)

function draftFor(id) {
  return drafts[id] ?? 1
}

function setDraft(id, val) {
  drafts[id] = val
  delete errors[id]
}

const localeTag = computed(() => (locale.value === 'vi' ? 'vi-VN' : 'en-US'))

function lockHintFor(req) {
  const k = row.lockHintKey(req)
  return k ? t(`${i18nPrefix.value}.${k}`) : ''
}

function routeLine(req) {
  const o = (req.origin || '').trim()
  const d = (req.destination || '').trim()
  if (o || d) return `${o || '…'} → ${d || '…'}`.trim()
  return t(`${i18nPrefix.value}.no_route`)
}

function departFmt(req) {
  const raw = req.depart_at
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleString(localeTag.value, {
      weekday: 'short',
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return '—'
  }
}

async function saveRow(req) {
  if (!row.canEditStudentCount(req) || savingId.value) return
  const id = req.id
  const n = Math.round(Number(draftFor(id)))
  if (!Number.isFinite(n) || n < 1 || n > 999) {
    errors[id] = t(`${i18nPrefix.value}.invalid_count`)
    return
  }
  savingId.value = id
  delete errors[id]
  try {
    await patchPassengerCount(id, n)
    emit('refresh')
  } catch (e) {
    errors[id] = formatApiError(e, t(`${i18nPrefix.value}.save_fail`))
  } finally {
    savingId.value = null
  }
}

function openDetail(req) {
  if (props.variant === 'portal') {
    router.push({ name: 'portalRequestDetail', params: { id: String(req.id) } })
  } else {
    router.push(`/requests/${req.id}`)
  }
}

defineExpose({
  setCloneBusy(id, busy) {
    cloneBusyId.value = busy ? id : null
  },
})
</script>
