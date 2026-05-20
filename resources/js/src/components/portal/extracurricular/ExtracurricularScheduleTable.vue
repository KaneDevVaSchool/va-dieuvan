<template>
  <div class="space-y-3">
    <details
      v-for="group in grouped"
      :key="group.key"
      class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      :open="group.defaultOpen"
    >
      <summary
        class="flex cursor-pointer list-none items-center justify-between gap-3 bg-indigo-50/70 px-4 py-3 text-sm font-semibold text-indigo-950 transition hover:bg-indigo-50 [&::-webkit-details-marker]:hidden"
      >
        <span class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
          <ChevronRightIcon
            class="h-4 w-4 shrink-0 text-indigo-600 transition group-open:rotate-90"
            aria-hidden="true"
          />
          <template v-if="props.groupBy === 'plan' && editingTemplateId === templateIdForGroup(group)">
            <span class="flex min-w-0 flex-1 flex-wrap items-center gap-2" @click.stop @mousedown.stop>
            <input
              ref="planLabelInputRef"
              v-model="planLabelDraft"
              type="text"
              maxlength="255"
              class="min-w-0 flex-1 rounded-lg border border-indigo-300 bg-white px-2 py-1 text-sm font-semibold text-indigo-950 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
              :aria-label="t('portal.extracurricular_list.plan_name_edit_label')"
              @keydown.enter.prevent="savePlanLabel(group)"
              @keydown.escape.prevent="cancelPlanLabelEdit"
              @click.stop
            />
            <button
              type="button"
              class="shrink-0 rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
              :disabled="planLabelSaving"
              @click.stop.prevent="savePlanLabel(group)"
            >
              {{ planLabelSaving ? t('portal.extracurricular_list.plan_name_save_busy') : t('portal.extracurricular_list.plan_name_save') }}
            </button>
            <button
              type="button"
              class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-indigo-800 hover:bg-indigo-100/80"
              :disabled="planLabelSaving"
              @click.stop.prevent="cancelPlanLabelEdit"
            >
              {{ t('portal.extracurricular_list.plan_name_cancel') }}
            </button>
            </span>
          </template>
          <template v-else>
            <span class="truncate">{{ group.label }}</span>
            <button
              v-if="props.groupBy === 'plan' && templateIdForGroup(group)"
              type="button"
              class="shrink-0 rounded-md p-1 text-indigo-700 hover:bg-indigo-100/80"
              :title="t('portal.extracurricular_list.plan_name_edit')"
              :aria-label="t('portal.extracurricular_list.plan_name_edit')"
              @click.stop.prevent="startPlanLabelEdit(group)"
            >
              <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </template>
          <p
            v-if="planLabelError && editingTemplateId === templateIdForGroup(group)"
            class="w-full basis-full text-xs font-normal text-red-700"
          >
            {{ planLabelError }}
          </p>
        </span>
        <span class="shrink-0 text-xs font-medium text-indigo-800/90">
          {{ t('portal.extracurricular_list.group_summary', { count: group.items.length }) }}
          <span v-if="group.pendingHs > 0" class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-amber-900">
            {{ group.pendingHs }} {{ t('portal.extracurricular_list.pending_hs_short') }}
          </span>
        </span>
      </summary>

      <div class="hidden border-t border-slate-100 md:block">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
            <tr>
              <th class="px-4 py-2">{{ t('portal.extracurricular_table.col_request') }}</th>
              <th class="px-4 py-2">{{ t('portal.extracurricular_table.col_depart') }}</th>
              <th class="px-4 py-2">{{ t('portal.extracurricular_table.col_route') }}</th>
              <th class="px-4 py-2">{{ t('portal.extracurricular_table.col_status') }}</th>
              <th class="whitespace-nowrap px-4 py-2 text-center">{{ t('portal.extracurricular_table.col_plan') }}</th>
              <th class="min-w-[9rem] px-4 py-2">{{ t('portal.extracurricular_table.col_actual') }}</th>
              <th class="px-4 py-2 text-right">{{ t('portal.extracurricular_table.col_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="req in group.items"
              :key="req.id"
              class="transition hover:bg-slate-50/80"
            >
              <td class="whitespace-nowrap px-4 py-3">
                <span class="font-mono font-semibold text-slate-900">#{{ req.id }}</span>
                <StudentCountTrackingBadge
                  class="mt-1"
                  :tracking-key="row.studentCountTrackingKey(req)"
                  i18n-prefix="portal.extracurricular_table"
                />
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ departFmt(req) }}</td>
              <td class="px-4 py-3 text-slate-800">{{ routeLine(req) }}</td>
              <td class="px-4 py-3">
                <StatusBadge :status="req.status" size="sm" />
              </td>
              <td class="px-4 py-3 text-center tabular-nums text-slate-700">
                {{ row.planStudentCount(req) ?? '—' }}
              </td>
              <td class="px-4 py-3">
                <StudentCountCell
                  :req="req"
                  :draft="draftFor(req.id)"
                  :saving="savingId === req.id"
                  :error="errors[req.id]"
                  :can-edit="row.canEditStudentCount(req)"
                  :lock-hint="lockHintFor(req)"
                  :show-submit="false"
                  :save-label-key="'portal.extracurricular_table.update_count'"
                  :save-busy-label-key="'portal.extracurricular_table.update_count_busy'"
                  @update:draft="setDraft(req.id, $event)"
                  @save="saveCount(req)"
                />
              </td>
              <td class="px-4 py-3 text-right">
                <ExtracurricularRowActions
                  :req="req"
                  variant="portal"
                  :detail-route-name="detailRouteName"
                  :show-complete-bm03="row.needsPortalBm03Completion(req)"
                  :can-complete-bm03="row.canOpenPortalBm03Completion(req)"
                  :complete-disabled-hint="completeBm03HintFor(req)"
                  :show-clone="false"
                  @open-detail="openDetail(req)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="space-y-3 border-t border-slate-100 p-3 md:hidden">
        <article
          v-for="req in group.items"
          :key="'m-' + req.id"
          class="rounded-xl border border-slate-200 bg-slate-50/50 p-3"
        >
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0">
              <span class="font-mono text-sm font-bold text-slate-900">#{{ req.id }}</span>
              <StatusBadge class="mt-1" :status="req.status" size="sm" />
            </div>
            <ExtracurricularRowActions
              :req="req"
              variant="portal"
              :detail-route-name="detailRouteName"
              :show-complete-bm03="row.needsPortalBm03Completion(req)"
              :can-complete-bm03="row.canOpenPortalBm03Completion(req)"
              :complete-disabled-hint="completeBm03HintFor(req)"
              :show-clone="false"
              @open-detail="openDetail(req)"
            />
          </div>
          <p class="mt-1 text-sm text-slate-800">{{ routeLine(req) }}</p>
          <p class="text-xs text-slate-500">{{ departFmt(req) }}</p>
          <div class="mt-2 flex items-center justify-between text-xs">
            <span class="text-slate-500">{{ t('portal.extracurricular_table.col_plan') }}</span>
            <span class="font-semibold tabular-nums">{{ row.planStudentCount(req) ?? '—' }}</span>
          </div>
          <div class="mt-2">
            <StudentCountCell
              :req="req"
              :draft="draftFor(req.id)"
              :saving="savingId === req.id"
              :error="errors[req.id]"
              :can-edit="row.canEditStudentCount(req)"
              :lock-hint="lockHintFor(req)"
              :show-submit="false"
              mobile
              :save-label-key="'portal.extracurricular_table.update_count'"
              :save-busy-label-key="'portal.extracurricular_table.update_count_busy'"
              @update:draft="setDraft(req.id, $event)"
              @save="saveCount(req)"
            />
          </div>
        </article>
      </div>
    </details>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronRightIcon, PencilSquareIcon } from '@heroicons/vue/24/outline'
import { updatePortalDispatchPlanLabel } from '../../../api/requests'
import { formatApiError } from '../../../api/http'
import StatusBadge from '../../ui/StatusBadge.vue'
import StudentCountCell from '../../requests/extracurricular/StudentCountCell.vue'
import StudentCountTrackingBadge from '../../requests/extracurricular/StudentCountTrackingBadge.vue'
import { useAuthStore } from '../../../store'
import { useExtracurricularRequestRow } from '../../../composables/useExtracurricularRequestRow'
import { useExtracurricularInlineStudentCount } from '../../../composables/useExtracurricularInlineStudentCount'
import ExtracurricularRowActions from '../../requests/extracurricular/ExtracurricularRowActions.vue'

const props = defineProps({
  requests: { type: Array, default: () => [] },
  groupBy: { type: String, default: 'day' },
  detailRouteName: { type: String, required: true },
})

const editingTemplateId = ref(null)
const planLabelDraft = ref('')
const planLabelSaving = ref(false)
const planLabelError = ref('')
const planLabelInputRef = ref(null)

const emit = defineEmits(['refresh'])

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const i18nPrefix = 'portal.extracurricular_table'
const requestsRef = computed(() => props.requests)
const { errors, savingId, draftFor, setDraft, saveCount } = useExtracurricularInlineStudentCount(
  requestsRef,
  row,
  {
    variant: 'portal',
    i18nPrefix,
    onSaved: () => emit('refresh'),
  },
)

const todayKey = computed(() => {
  const d = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
})

const grouped = computed(() => {
  const map = new Map()
  for (const req of props.requests) {
    const key = groupKey(req)
    if (!map.has(key)) {
      map.set(key, {
        key,
        label: groupLabel(req, key),
        templateId: templateIdFromReq(req),
        items: [],
      })
    }
    map.get(key).items.push(req)
  }
  const arr = [...map.values()]
  arr.sort((a, b) => String(a.key).localeCompare(String(b.key)))
  for (const g of arr) {
    g.items.sort((a, b) => String(a.depart_at || '').localeCompare(String(b.depart_at || '')))
    g.pendingHs = g.items.filter((r) => row.studentCountTrackingKey(r) === 'not_updated').length
    g.defaultOpen = props.groupBy === 'day' && g.key === todayKey.value
  }
  if (arr.length && !arr.some((g) => g.defaultOpen)) {
    arr[0].defaultOpen = true
  }
  return arr
})

function openDetail(req) {
  router.push({ name: props.detailRouteName, params: { id: String(req.id) } })
}

function completeBm03HintFor(req) {
  if (row.canOpenPortalBm03Completion(req)) return ''
  const k = row.lockHintKey(req)
  if (k) return t(`${i18nPrefix}.${k}`)
  if (req?.student_count_submitted_at) {
    return t('portal.extracurricular_list.action_complete_bm03_done')
  }
  return t('portal.extracurricular_list.action_complete_bm03_unavailable')
}

function lockHintFor(req) {
  const k = row.lockHintKey(req)
  return k ? t(`${i18nPrefix}.${k}`) : ''
}

function templateIdFromReq(req) {
  const tid = req?.dispatch_request_template_id ?? req?.dispatch_request_template?.id
  if (tid == null || tid === '') return null
  const n = Number(tid)
  return Number.isFinite(n) && n > 0 ? n : null
}

function templateIdForGroup(group) {
  if (group?.templateId) return group.templateId
  const req = group?.items?.[0]
  return req ? templateIdFromReq(req) : null
}

function rawPlanLabelFromReq(req) {
  const pkgLabel = req?.dispatch_request_template?.dispatch_package?.label
  return String(pkgLabel || '').trim()
}

function startPlanLabelEdit(group) {
  const tid = templateIdForGroup(group)
  if (!tid) return
  planLabelError.value = ''
  editingTemplateId.value = tid
  const raw =
    group?.items?.map((r) => rawPlanLabelFromReq(r)).find((s) => s) ||
    (group.label === t('portal.recurring_plan.plan_name_unnamed') ? '' : String(group.label || '').trim())
  planLabelDraft.value = raw
  nextTick(() => planLabelInputRef.value?.focus())
}

function cancelPlanLabelEdit() {
  editingTemplateId.value = null
  planLabelDraft.value = ''
  planLabelError.value = ''
}

async function savePlanLabel(group) {
  const tid = templateIdForGroup(group)
  const trimmed = planLabelDraft.value.trim()
  if (!tid) {
    planLabelError.value = t('portal.extracurricular_list.plan_name_save_fail')
    return
  }
  if (!trimmed) {
    planLabelError.value = t('portal.extracurricular_create.blocker_plan_name')
    return
  }
  planLabelSaving.value = true
  planLabelError.value = ''
  try {
    await updatePortalDispatchPlanLabel(tid, trimmed)
    patchLocalPlanLabel(group, trimmed)
    cancelPlanLabelEdit()
    emit('refresh')
  } catch (e) {
    planLabelError.value = formatApiError(e, t('portal.extracurricular_list.plan_name_save_fail'))
  } finally {
    planLabelSaving.value = false
  }
}

function patchLocalPlanLabel(group, label) {
  for (const req of group?.items || []) {
    const pkg = req?.dispatch_request_template?.dispatch_package
    if (pkg && typeof pkg === 'object') {
      pkg.label = label
    }
  }
}

function planNameFor(req) {
  const pkgLabel = req?.dispatch_request_template?.dispatch_package?.label
  const snapName = req?.wizard_snapshot?.form?.plan_name
  const s = String(pkgLabel || snapName || '').trim()
  return s || t('portal.recurring_plan.plan_name_unnamed')
}

function groupKey(req) {
  if (props.groupBy === 'plan') {
    const tid = req?.dispatch_request_template_id
    return `${tid ?? 'single'}::${planNameFor(req)}`
  }
  if (props.groupBy === 'route') {
    return `${req.origin || ''}→${req.destination || ''}`
  }
  if (props.groupBy === 'status') {
    return String(req.status || 'pending')
  }
  return String(req.depart_at || '').slice(0, 10)
}

function groupLabel(req, key) {
  if (props.groupBy === 'plan') {
    return planNameFor(req)
  }
  if (props.groupBy === 'route') {
    return routeLine(req) || key
  }
  if (props.groupBy === 'status') {
    return key
  }
  try {
    const [y, m, d] = key.split('-').map(Number)
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Intl.DateTimeFormat(loc, { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' }).format(
      new Date(y, m - 1, d),
    )
  } catch {
    return key
  }
}

function routeLine(req) {
  const o = req?.origin?.trim()
  const d = req?.destination?.trim()
  if (o && d) return `${o} → ${d}`
  return o || d || '—'
}

function departFmt(req) {
  if (!req?.depart_at) return '—'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Intl.DateTimeFormat(loc, {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(req.depart_at))
  } catch {
    return req.depart_at
  }
}
</script>
