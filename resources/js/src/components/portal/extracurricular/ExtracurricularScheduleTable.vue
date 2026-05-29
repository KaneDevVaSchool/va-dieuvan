<template>
  <div class="space-y-4">
    <p class="text-right text-xs text-slate-500">
      {{
        t('portal.extracurricular_list.schedule_summary', {
          groups: grouped.length,
          total: requests.length,
        })
      }}
    </p>
    <section
      v-for="group in grouped"
      :key="group.key"
      class="xc-portal-group-card"
    >
      <div class="xc-portal-group-header flex flex-col gap-0">
      <div
        class="flex items-center justify-between gap-3 px-4 py-3.5 text-sm font-semibold text-violet-950"
      >
        <button
          type="button"
          class="flex min-w-0 flex-1 items-center gap-2 rounded-lg text-left transition hover:bg-violet-50/60 focus:outline-none focus:ring-2 focus:ring-violet-500/30"
          :aria-expanded="isGroupOpen(group.key)"
          :aria-label="t('portal.extracurricular_list.collapse_hint')"
          @click="toggleGroup(group.key)"
        >
          <ChevronRightIcon
            class="h-4 w-4 shrink-0 text-violet-600 transition"
            :class="{ 'rotate-90': isGroupOpen(group.key) }"
            aria-hidden="true"
          />
          <span class="flex min-w-0 flex-1 flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-2">
            <span class="truncate">{{ group.label }}</span>
          </span>
        </button>

        <div
          v-if="props.groupBy === 'plan'"
          class="flex min-w-0 max-w-[min(100%,28rem)] flex-1 flex-wrap items-center justify-end gap-2"
          @click.stop
        >
          <template v-if="editingTemplateId === templateIdForGroup(group)">
            <input
              :ref="setPlanLabelInputRef"
              v-model="planLabelDraft"
              type="text"
              maxlength="255"
              class="min-w-[8rem] flex-1 rounded-lg border border-indigo-300 bg-white px-2 py-1 text-sm font-semibold text-indigo-950 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
              :aria-label="t('portal.extracurricular_list.plan_name_edit_label')"
              @keydown.enter.prevent="savePlanLabel(group)"
              @keydown.escape.prevent="cancelPlanLabelEdit"
            />
            <button
              type="button"
              class="shrink-0 rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
              :disabled="planLabelSaving"
              @click="savePlanLabel(group)"
            >
              {{ planLabelSaving ? t('portal.extracurricular_list.plan_name_save_busy') : t('portal.extracurricular_list.plan_name_save') }}
            </button>
            <button
              type="button"
              class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-indigo-800 hover:bg-indigo-100/80"
              :disabled="planLabelSaving"
              @click="cancelPlanLabelEdit"
            >
              {{ t('portal.extracurricular_list.plan_name_cancel') }}
            </button>
          </template>
          <button
            v-else-if="templateIdForGroup(group)"
            type="button"
            class="shrink-0 rounded-md p-1.5 text-indigo-700 hover:bg-indigo-100/80"
            :title="t('portal.extracurricular_list.plan_name_edit')"
            :aria-label="t('portal.extracurricular_list.plan_name_edit')"
            @click="startPlanLabelEdit(group)"
          >
            <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
          </button>
          <p
            v-if="planLabelError && editingTemplateId === templateIdForGroup(group)"
            class="w-full text-xs font-normal text-red-700"
          >
            {{ planLabelError }}
          </p>
        </div>

        <button
          type="button"
          class="shrink-0 text-xs font-medium text-indigo-800/90 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-500/30 rounded"
          @click="toggleGroup(group.key)"
        >
          {{ t('portal.extracurricular_list.group_summary', { count: group.items.length }) }}
          <span v-if="group.pendingHs > 0" class="ml-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900">
            {{ group.pendingHs }} {{ t('portal.extracurricular_list.pending_hs_short') }}
          </span>
        </button>
      </div>

      <div
        v-if="packageBudgetUsageForGroup(group)"
        class="flex flex-wrap items-center gap-2 border-t border-violet-100/70 px-4 py-2 text-xs font-medium"
        :class="packageBudgetBannerClass(packageBudgetUsageForGroup(group))"
      >
        <span class="tabular-nums text-slate-800">
          {{
            t('portal.extracurricular_list.package_budget_line', {
              used: formatVnd(packageBudgetUsageForGroup(group).used),
              budget: formatVnd(packageBudgetUsageForGroup(group).budget),
              remaining: formatVnd(packageBudgetUsageForGroup(group).remaining),
            })
          }}
        </span>
        <span
          v-if="packageBudgetUsageForGroup(group).severity === 'warning'"
          class="rounded-full bg-amber-200/90 px-2 py-0.5 font-semibold text-amber-950"
        >
          {{ t('portal.extracurricular_list.package_budget_warning') }}
        </span>
        <span
          v-else-if="packageBudgetUsageForGroup(group).severity === 'exceeded'"
          class="rounded-full bg-rose-200/90 px-2 py-0.5 font-semibold text-rose-950"
        >
          {{ t('portal.extracurricular_list.package_budget_exceeded') }}
        </span>
      </div>
      </div>

      <div v-show="isGroupOpen(group.key)" class="hidden border-t border-violet-100/80 md:block">
        <div class="xc-portal-table-scroll">
          <table class="xc-portal-table">
            <thead>
              <tr>
                <th>{{ t('portal.extracurricular_table.col_request') }}</th>
                <th>{{ t('portal.extracurricular_table.col_depart') }}</th>
                <th class="min-w-[10rem]">{{ t('portal.extracurricular_table.col_route') }}</th>
                <th>{{ t('portal.extracurricular_table.col_status') }}</th>
                <th class="xc-portal-tabular text-center">{{ t('portal.extracurricular_table.col_plan') }}</th>
                <th
                  v-if="groupHasFilledTripCost(group)"
                  class="whitespace-nowrap text-right"
                >
                  {{ t('portal.extracurricular_table.col_trip_cost') }}
                </th>
                <th class="min-w-[11rem]">{{ t('portal.extracurricular_table.col_actual') }}</th>
                <th class="xc-portal-col-actions text-right">{{ t('portal.extracurricular_table.col_actions') }}</th>
              </tr>
            </thead>
            <tbody>
            <tr
              v-for="req in group.items"
              :key="req.id"
            >
              <td class="whitespace-nowrap">
                <span class="font-mono text-sm font-bold text-slate-900">#{{ req.id }}</span>
                <StudentCountTrackingBadge
                  class="mt-1"
                  :tracking-key="row.studentCountTrackingKey(req)"
                  i18n-prefix="portal.extracurricular_table"
                />
              </td>
              <td class="xc-portal-tabular whitespace-nowrap text-slate-600">{{ departFmt(req) }}</td>
              <td class="font-medium text-slate-800">{{ routeLine(req) }}</td>
              <td>
                <StatusBadge :status="req.status" size="sm" />
              </td>
              <td class="xc-portal-tabular text-center font-medium text-slate-700">
                {{ row.planStudentCount(req) ?? '—' }}
              </td>
              <td
                v-if="groupHasFilledTripCost(group)"
                class="whitespace-nowrap text-right text-sm tabular-nums text-slate-700"
              >
                <template v-if="reqHasFilledTripCost(req)">
                  <span class="font-semibold text-slate-900">{{ tripCostDisplayForReq(req) }}</span>
                  <span class="mt-0.5 block text-[10px] font-medium uppercase tracking-wide text-teal-700">
                    {{ t('portal.extracurricular_table.cost_filled_dispatch') }}
                  </span>
                </template>
              </td>
              <td>
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
              <td class="xc-portal-col-actions text-right">
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
      </div>

      <div v-show="isGroupOpen(group.key)" class="space-y-3 border-t border-violet-100/80 bg-slate-50/30 p-3 md:hidden">
        <article
          v-for="req in group.items"
          :key="'m-' + req.id"
          class="xc-portal-mobile-row p-3.5"
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
          <div
            v-if="reqHasFilledTripCost(req)"
            class="mt-1 flex items-center justify-between text-xs"
          >
            <span class="text-slate-500">{{ t('portal.extracurricular_table.col_trip_cost_filled') }}</span>
            <span class="font-semibold tabular-nums text-slate-800">{{ tripCostDisplayForReq(req) }}</span>
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
    </section>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronRightIcon, PencilSquareIcon } from '@heroicons/vue/24/outline'
import { updatePortalDispatchPlanLabel } from '../../../api/requests'
import { formatApiError } from '../../../api/http'
import { formatVnd } from '../../../util/labels'
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

const emit = defineEmits(['refresh', 'plan-label-saved'])

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

const openGroupKeys = ref(new Set())

function isGroupOpen(key) {
  return openGroupKeys.value.has(key)
}

function toggleGroup(key) {
  const next = new Set(openGroupKeys.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  openGroupKeys.value = next
}

watch(
  grouped,
  (arr) => {
    const next = new Set(openGroupKeys.value)
    for (const g of arr) {
      if (g.defaultOpen) next.add(g.key)
    }
    if (!next.size && arr.length) next.add(arr[0].key)
    openGroupKeys.value = next
  },
  { immediate: true },
)

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

function reqHasFilledTripCost(req) {
  const sp = Number(req?.service_price)
  return Number.isFinite(sp) && sp > 0
}

function tripCostDisplayForReq(req) {
  if (!reqHasFilledTripCost(req)) return ''
  return formatVnd(Number(req.service_price))
}

function groupHasFilledTripCost(group) {
  return (group?.items || []).some((req) => reqHasFilledTripCost(req))
}

function packageBudgetUsageForGroup(group) {
  for (const req of group?.items || []) {
    const u = req?.dispatch_package_budget_usage
    if (u && u.budget > 0 && u.used > 0) return u
  }
  return null
}

function packageBudgetBannerClass(usage) {
  if (!usage) return 'bg-slate-50/80'
  if (usage.severity === 'exceeded') return 'bg-rose-50/90'
  if (usage.severity === 'warning') return 'bg-amber-50/90'
  return 'bg-violet-50/50'
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
  const next = new Set(openGroupKeys.value)
  next.add(group.key)
  openGroupKeys.value = next
  planLabelError.value = ''
  editingTemplateId.value = tid
  const raw =
    group?.items?.map((r) => rawPlanLabelFromReq(r)).find((s) => s) ||
    (group.label === t('portal.recurring_plan.plan_name_unnamed') ? '' : String(group.label || '').trim())
  planLabelDraft.value = raw
  focusPlanLabelInput()
}

function setPlanLabelInputRef(el) {
  planLabelInputRef.value = el
}

function focusPlanLabelInput() {
  nextTick(() => {
    const el = planLabelInputRef.value
    if (el && typeof el.focus === 'function') {
      el.focus()
    }
  })
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
    patchLocalPlanLabel(group, trimmed, tid)
    cancelPlanLabelEdit()
    emit('plan-label-saved', { templateId: tid, label: trimmed })
  } catch (e) {
    planLabelError.value = formatApiError(e, t('portal.extracurricular_list.plan_name_save_fail'))
  } finally {
    planLabelSaving.value = false
  }
}

function patchLocalPlanLabel(group, label, templateId) {
  const tid = templateId ?? templateIdForGroup(group)
  for (const req of group?.items || []) {
    req.recurring_plan_label = label
    if (!req.dispatch_request_template) {
      req.dispatch_request_template = { id: tid }
    }
    if (!req.dispatch_request_template.dispatch_package) {
      req.dispatch_request_template.dispatch_package = { label }
    } else {
      req.dispatch_request_template.dispatch_package.label = label
    }
  }
}

function planNameFor(req) {
  const fromApi = req?.recurring_plan_label
  const pkgLabel = req?.dispatch_request_template?.dispatch_package?.label
  const tplSnap = req?.dispatch_request_template?.wizard_snapshot?.form?.plan_name
  const s = String(fromApi || pkgLabel || tplSnap || '').trim()
  return s || t('portal.recurring_plan.plan_name_unnamed')
}

function groupKey(req) {
  if (props.groupBy === 'plan') {
    const tid = templateIdFromReq(req)
    return tid != null ? `tpl:${tid}` : `req:${req?.id ?? 'unknown'}`
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

<style src="./extracurricularPortalTable.css"></style>
