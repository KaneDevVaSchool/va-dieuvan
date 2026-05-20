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

    <div class="xc-portal-group-card hidden md:block">
      <div class="xc-portal-table-scroll">
        <table class="xc-portal-table">
          <thead>
            <tr>
              <th>{{ t(`${i18nPrefix}.col_request`) }}</th>
              <th class="min-w-[12rem]">{{ t(`${i18nPrefix}.col_route`) }}</th>
              <th>{{ t(`${i18nPrefix}.col_depart`) }}</th>
              <th>{{ t(`${i18nPrefix}.col_status`) }}</th>
              <th>{{ t(`${i18nPrefix}.col_tracking`) }}</th>
              <th class="text-center">{{ t(`${i18nPrefix}.col_plan`) }}</th>
              <th class="min-w-[10rem]">{{ t(`${i18nPrefix}.col_actual`) }}</th>
              <th class="w-40 text-right">{{ t(`${i18nPrefix}.col_actions`) }}</th>
            </tr>
          </thead>
          <tbody>
          <tr v-for="req in requests" :key="req.id">
            <td class="whitespace-nowrap">
              <RequestIdCell :req="req" />
            </td>
            <td>
              <p class="font-medium leading-snug text-slate-800">{{ routeLine(req) }}</p>
            </td>
            <td class="whitespace-nowrap text-xs text-slate-600">
              {{ departFmt(req) }}
            </td>
            <td>
              <StatusBadge :status="req.status" size="sm" />
            </td>
            <td>
              <StudentCountTrackingBadge
                :tracking-key="row.studentCountTrackingKey(req)"
                :i18n-prefix="i18nPrefix"
              />
            </td>
            <td class="text-center tabular-nums font-medium text-slate-700">
              {{ row.planStudentCount(req) ?? '—' }}
            </td>
            <td>
              <StudentCountCell
                :req="req"
                :draft="draftFor(req.id)"
                :saving="savingId === req.id"
                :submitting="submittingId === req.id"
                :error="errors[req.id]"
                :can-edit="row.canEditStudentCount(req)"
                :lock-hint="lockHintFor(req)"
                :show-submit="variant !== 'portal'"
                :can-submit="row.canSubmitStudentCount(req)"
                :submit-disabled-hint="submitHintFor(req)"
                :save-label-key="`${i18nPrefix}.update_count`"
                :save-busy-label-key="`${i18nPrefix}.update_count_busy`"
                :submit-label-key="`${i18nPrefix}.submit_dispatch`"
                :submit-busy-label-key="`${i18nPrefix}.submit_dispatch_busy`"
                @update:draft="setDraft(req.id, $event)"
                @save="onSaveRow(req)"
                @submit="submitRow(req)"
              />
            </td>
            <td class="text-right">
              <ExtracurricularRowActions
                :req="req"
                :variant="variant"
                :detail-route-name="detailRouteName"
                :show-complete-bm03="variant === 'portal' && row.needsPortalBm03Completion(req)"
                :can-complete-bm03="row.canOpenPortalBm03Completion(req)"
                :complete-disabled-hint="completeBm03HintFor(req)"
                :show-clone="row.canShowCloneSimilar(req)"
                :can-clone="row.canCloneReset(req)"
                :clone-busy="cloneBusyId === req.id"
                :clone-label="t(`${i18nPrefix}.clone_similar`)"
                :clone-busy-label="t(`${i18nPrefix}.clone_similar_busy`)"
                :clone-disabled-hint="t(`${i18nPrefix}.clone_similar_disabled_hint`)"
                @clone="$emit('clone', req)"
                @open-detail="openDetail(req)"
              />
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="space-y-3 md:hidden">
      <article
        v-for="req in requests"
        :key="'m-' + req.id"
        class="xc-portal-mobile-row overflow-hidden"
      >
        <div class="border-b border-violet-100/90 bg-gradient-to-r from-violet-50/80 to-white px-4 py-3">
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
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-slate-500">{{ t(`${i18nPrefix}.col_tracking`) }}</span>
            <StudentCountTrackingBadge
              :tracking-key="row.studentCountTrackingKey(req)"
              :i18n-prefix="i18nPrefix"
            />
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
            :submitting="submittingId === req.id"
            :error="errors[req.id]"
            :can-edit="row.canEditStudentCount(req)"
            :lock-hint="lockHintFor(req)"
            :show-submit="variant !== 'portal'"
            :can-submit="row.canSubmitStudentCount(req)"
            :submit-disabled-hint="submitHintFor(req)"
            :save-label-key="`${i18nPrefix}.update_count`"
            :save-busy-label-key="`${i18nPrefix}.update_count_busy`"
            :submit-label-key="`${i18nPrefix}.submit_dispatch`"
            :submit-busy-label-key="`${i18nPrefix}.submit_dispatch_busy`"
            mobile
            @update:draft="setDraft(req.id, $event)"
            @save="onSaveRow(req)"
            @submit="submitRow(req)"
          />
          <div class="flex justify-end border-t border-slate-100 pt-3">
            <ExtracurricularRowActions
              :req="req"
              :variant="variant"
              :detail-route-name="detailRouteName"
              :show-complete-bm03="variant === 'portal' && row.needsPortalBm03Completion(req)"
              :can-complete-bm03="row.canOpenPortalBm03Completion(req)"
              :complete-disabled-hint="completeBm03HintFor(req)"
              :show-clone="row.canShowCloneSimilar(req)"
              :can-clone="row.canCloneReset(req)"
              :clone-busy="cloneBusyId === req.id"
              :clone-label="t(`${i18nPrefix}.clone_similar`)"
              :clone-busy-label="t(`${i18nPrefix}.clone_similar_busy`)"
              :clone-disabled-hint="t(`${i18nPrefix}.clone_similar_disabled_hint`)"
              @clone="$emit('clone', req)"
              @open-detail="openDetail(req)"
            />
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { AcademicCapIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'
import StudentCountCell from './extracurricular/StudentCountCell.vue'
import StudentCountTrackingBadge from './extracurricular/StudentCountTrackingBadge.vue'
import ExtracurricularRowActions from './extracurricular/ExtracurricularRowActions.vue'
import { useExtracurricularRequestRow } from '../../composables/useExtracurricularRequestRow'
import { useExtracurricularInlineStudentCount } from '../../composables/useExtracurricularInlineStudentCount'
import { useAuthStore } from '../../store'
import { submitPortalRecurringInstance, submitStudentCount } from '../../api/requests'
import { formatApiError } from '../../api/http'
import { confirmAction } from '../../composables/useConfirm'

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
  detailRouteName: { type: String, default: 'portalRequestDetail' },
})

const emit = defineEmits(['refresh', 'clone'])

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const i18nPrefix = computed(() =>
  props.variant === 'portal' ? 'portal.extracurricular_table' : 'requests_page.extracurricular_table',
)

const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const requestsRef = computed(() => props.requests)
const {
  errors,
  savingId,
  draftFor,
  setDraft,
  saveCount,
} = useExtracurricularInlineStudentCount(requestsRef, row, {
  variant: computed(() => props.variant),
  i18nPrefix,
  onSaved: () => emit('refresh'),
})

const submittingId = ref(null)
const cloneBusyId = ref(null)

async function onSaveRow(req) {
  await saveCount(req)
}

const localeTag = computed(() => (locale.value === 'vi' ? 'vi-VN' : 'en-US'))

function lockHintFor(req) {
  const k = row.lockHintKey(req)
  return k ? t(`${i18nPrefix.value}.${k}`) : ''
}

function completeBm03HintFor(req) {
  if (row.canOpenPortalBm03Completion(req)) return ''
  const k = row.lockHintKey(req)
  if (k) return t(`${i18nPrefix.value}.${k}`)
  if (req?.student_count_submitted_at) {
    return t('portal.extracurricular_list.action_complete_bm03_done')
  }
  return t('portal.extracurricular_list.action_complete_bm03_unavailable')
}

function submitHintFor(req) {
  if (row.canSubmitStudentCount(req)) return ''
  if (req?.student_count_actual == null || req?.student_count_actual === '') {
    return t(`${i18nPrefix.value}.submit_save_first_hint`)
  }
  return ''
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

async function submitRow(req) {
  if (!row.canSubmitStudentCount(req) || submittingId.value) return
  const n = row.actualStudentCount(req)
  const ok = await confirmAction({
    title: t(`${i18nPrefix.value}.submit_confirm_title`),
    message: t(`${i18nPrefix.value}.submit_confirm_message`, { count: n }),
    confirmLabel: t(`${i18nPrefix.value}.submit_dispatch`),
  })
  if (!ok) return
  submittingId.value = req.id
  delete errors[req.id]
  try {
    if (props.variant === 'portal') {
      await submitPortalRecurringInstance(req.id)
    } else {
      await submitStudentCount(req.id)
    }
    emit('refresh')
  } catch (e) {
    errors[req.id] = formatApiError(e, t(`${i18nPrefix.value}.submit_fail`))
  } finally {
    submittingId.value = null
  }
}

function openDetail(req) {
  if (props.variant === 'portal') {
    router.push({ name: props.detailRouteName, params: { id: String(req.id) } })
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

<style src="../portal/extracurricular/extracurricularPortalTable.css"></style>
