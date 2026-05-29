<template>
  <div class="p2p-hub-page w-full space-y-8 pb-16 text-slate-900 dark:text-slate-100">
    <header
      class="relative overflow-hidden rounded-2xl border border-teal-200/70 bg-gradient-to-br from-teal-50 via-white to-indigo-50/40 p-6 shadow-sm ring-1 ring-teal-900/[0.04] dark:border-teal-900/50 dark:from-teal-950/35 dark:via-slate-900/90 dark:to-indigo-950/25 dark:ring-teal-900/20 md:p-8"
    >
      <div class="relative z-[1] flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 space-y-2">
          <div class="inline-flex items-center gap-2 rounded-full bg-teal-100/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-teal-900 dark:bg-teal-900/50 dark:text-teal-100">
            <SparklesIcon class="h-3.5 w-3.5 shrink-0 text-teal-600 dark:text-teal-300" aria-hidden="true" />
            {{ t('p2p_policy_page.hub_badge') }}
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
            {{ t('p2p_policy_page.hub_title') }}
          </h1>
        </div>
        <RouterLink
          :to="p2pStepTo('p2pPolicyTerm', workflowTermId)"
          class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 dark:bg-teal-700 dark:hover:bg-teal-600"
        >
          <PlusCircleIcon class="h-5 w-5" aria-hidden="true" />
          {{ t('p2p_policy_page.create_term') }}
        </RouterLink>
      </div>
      <div
        class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-teal-400/10 blur-2xl dark:bg-teal-500/10"
        aria-hidden="true"
      />
    </header>

    <section aria-labelledby="p2p-workflow-heading">
      <h2
        id="p2p-workflow-heading"
        class="mb-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
      >
        <ClipboardDocumentCheckIcon class="h-4 w-4 text-violet-500 dark:text-violet-400" aria-hidden="true" />
        {{ t('p2p_policy_page.hub_workflow_title') }}
      </h2>
      <ol class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <li v-for="(step, idx) in workflowSteps" :key="step.key">
          <RouterLink
            :to="step.to"
            class="flex h-full gap-3 rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-teal-300 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-900/60 dark:hover:border-teal-800"
          >
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold text-white"
              :class="step.stepClass"
            >
              {{ idx + 1 }}
            </span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ step.title }}</p>
              <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ step.desc }}</p>
            </div>
          </RouterLink>
        </li>
      </ol>
    </section>

    <section aria-labelledby="p2p-nav-heading">
      <h2
        id="p2p-nav-heading"
        class="mb-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
      >
        <Squares2X2Icon class="h-4 w-4 text-teal-600 dark:text-teal-400" aria-hidden="true" />
        {{ t('p2p_policy_page.hub_nav_section') }}
      </h2>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          v-for="card in cards"
          :key="card.to.name"
          :to="card.to"
          :class="[
            'group flex flex-col rounded-2xl border bg-gradient-to-b p-5 shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md',
            card.cardClass,
          ]"
        >
          <div class="flex items-start justify-between gap-3">
            <span
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/80 shadow-sm ring-1 ring-black/[0.04] dark:bg-slate-900/70 dark:ring-white/10"
            >
              <component :is="card.icon" :class="['h-7 w-7', card.iconClass]" aria-hidden="true" />
            </span>
            <ChevronRightIcon
              class="h-5 w-5 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-teal-600 dark:text-slate-600 dark:group-hover:text-teal-400"
              aria-hidden="true"
            />
          </div>
          <p class="mt-4 text-base font-semibold text-slate-900 dark:text-white">{{ card.label }}</p>
        </RouterLink>
      </div>
    </section>

    <div v-if="!selectedTerm && !loadingTerms" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-8 text-center dark:border-slate-600 dark:bg-slate-900/40">
      <CalendarDaysIcon class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" aria-hidden="true" />
      <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">{{ t('p2p_policy_page.hub_no_term_title') }}</h2>
      <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.hub_no_term_desc') }}</p>
      <RouterLink
        :to="{ name: 'p2pPolicyTerm' }"
        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-800"
      >
        <PlusCircleIcon class="h-5 w-5" aria-hidden="true" />
        {{ t('p2p_policy_page.hub_go_term_config') }}
      </RouterLink>
    </div>

    <div v-else-if="selectedTerm" class="grid gap-6 xl:grid-cols-12">
      <Card class="xl:col-span-5 !p-5" :title="t('p2p_policy_page.hub_term_panel_title')" :hint="t('p2p_policy_page.hub_term_panel_hint')">
        <div v-if="terms.length > 1" class="mb-4">
          <label class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('p2p_policy_page.hub_term_select') }}
          </label>
          <select
            v-model="selectedTermId"
            class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-600 dark:bg-slate-800"
            @change="onTermChange"
          >
            <option v-for="term in terms" :key="term.id" :value="term.id">
              {{ termOptionLabel(term) }}
            </option>
          </select>
        </div>

        <div class="space-y-4">
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
              :class="statusBadgeClass(selectedTerm.status)"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80" aria-hidden="true" />
              {{ termStatusLabel(selectedTerm.status) }}
            </span>
            <span v-if="selectedTerm.academic_term" class="text-sm font-medium text-slate-800 dark:text-slate-200">
              {{ selectedTerm.academic_term.academic_year }} · {{ selectedTerm.academic_term.name }}
            </span>
          </div>

          <dl class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                <CalendarIcon class="h-3.5 w-3.5 text-sky-500" aria-hidden="true" />
                {{ t('p2p_policy_page.field_operating_from') }}
              </dt>
              <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ formatOperatingDate(selectedTerm.operating_from) }}</dd>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                <CalendarIcon class="h-3.5 w-3.5 text-indigo-500" aria-hidden="true" />
                {{ t('p2p_policy_page.field_operating_to') }}
              </dt>
              <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ formatOperatingDate(selectedTerm.operating_to) }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50/80 p-3 dark:bg-amber-950/20 sm:col-span-2">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-amber-800/80 dark:text-amber-200/80">
                <SunIcon class="h-3.5 w-3.5 text-amber-500" aria-hidden="true" />
                {{ t('p2p_policy_page.field_morning') }}
              </dt>
              <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">
                {{ formatTimeRange(selectedTerm.default_morning_start, selectedTerm.default_morning_end) }}
              </dd>
            </div>
            <div class="rounded-lg bg-violet-50/80 p-3 dark:bg-violet-950/20 sm:col-span-2">
              <dt class="flex items-center gap-1.5 text-xs font-medium text-violet-800/80 dark:text-violet-200/80">
                <MoonIcon class="h-3.5 w-3.5 text-violet-500" aria-hidden="true" />
                {{ t('p2p_policy_page.field_afternoon') }}
              </dt>
              <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">
                {{ formatTimeRange(selectedTerm.default_afternoon_start, selectedTerm.default_afternoon_end) }}
              </dd>
            </div>
          </dl>
        </div>
      </Card>

      <section
        class="xl:col-span-7 rounded-2xl border p-5 shadow-sm transition-colors"
        :class="readinessPanelClass"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex items-start gap-3">
            <span
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
              :class="readinessIconWrapClass"
            >
              <component :is="readinessIcon" class="h-6 w-6" aria-hidden="true" />
            </span>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.readiness_title') }}</h2>
            </div>
          </div>
          <span
            v-if="readiness"
            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="readiness?.ready ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100'"
          >
            {{ readiness?.ready ? t('p2p_policy_page.readiness_badge_ok') : t('p2p_policy_page.readiness_badge_pending') }}
          </span>
        </div>

        <ul v-if="readiness?.issues?.length" class="mt-5 space-y-2">
          <li
            v-for="(issue, i) in readiness.issues"
            :key="i"
            class="flex gap-3 rounded-lg border border-amber-200/80 bg-white/70 px-3 py-2.5 text-sm text-amber-950 dark:border-amber-800/50 dark:bg-slate-900/50 dark:text-amber-100"
          >
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" aria-hidden="true" />
            <span>{{ readinessIssueText(issue) }}</span>
          </li>
        </ul>

        <div
          v-else-if="readiness?.ready"
          class="mt-5 flex gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 dark:border-emerald-800/50 dark:bg-emerald-950/30"
        >
          <CheckCircleIcon class="h-6 w-6 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
          <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100">{{ t('p2p_policy_page.readiness_ok') }}</p>
        </div>

        <div v-if="generationRun && generationRun.status === 'running'" class="mt-5 rounded-xl border border-sky-200/80 bg-sky-50/80 p-4 dark:border-sky-800/50 dark:bg-sky-950/25">
          <div class="flex items-center gap-2 text-sm font-medium text-sky-900 dark:text-sky-100">
            <ArrowPathIcon class="h-5 w-5 animate-spin text-sky-600 dark:text-sky-400" aria-hidden="true" />
            {{ t('p2p_policy_page.generating_progress', { created: generationRun.created_slots, total: generationRun.total_slots }) }}
          </div>
          <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-sky-200/80 dark:bg-sky-900/50">
            <div
              class="h-full rounded-full bg-gradient-to-r from-sky-500 to-teal-500 transition-all duration-500"
              :style="{ width: progressPct + '%' }"
            />
          </div>
          <p class="mt-2 text-xs text-sky-800/80 dark:text-sky-200/70">{{ progressPct }}%</p>
        </div>

        <button
          v-if="readiness?.ready && selectedTerm.status === 'draft'"
          type="button"
          class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-700 to-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-teal-800 hover:to-emerald-800 disabled:opacity-50 sm:w-auto"
          :disabled="activating"
          @click="openActivate"
        >
          <RocketLaunchIcon class="h-5 w-5" aria-hidden="true" />
          {{ t('p2p_policy_page.activate_cta') }}
        </button>
      </section>
    </div>

    <dialog
      ref="activateDialog"
      class="w-[min(100%,28rem)] rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 dark:border-slate-700 dark:bg-slate-900"
    >
      <form class="p-6" @submit.prevent="confirmActivate">
        <div class="flex items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 dark:bg-teal-900/50">
            <RocketLaunchIcon class="h-5 w-5 text-teal-700 dark:text-teal-300" aria-hidden="true" />
          </span>
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('p2p_policy_page.activate_modal_title') }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ activateSummary }}</p>
          </div>
        </div>
        <label class="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-sm dark:border-slate-600 dark:bg-slate-800/50">
          <input v-model="activateChecked" type="checkbox" class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
          <span class="text-slate-700 dark:text-slate-200">{{ t('p2p_policy_page.activate_confirm_checkbox') }}</span>
        </label>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
            @click="closeActivate"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            type="submit"
            class="rounded-xl bg-va-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-va-900 disabled:opacity-50"
            :disabled="!activateChecked || activating"
          >
            {{ t('p2p_policy_page.activate_confirm') }}
          </button>
        </div>
      </form>
    </dialog>
  </div>
</template>

<script setup>
import { computed, markRaw, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowPathIcon,
  CalendarDaysIcon,
  CalendarIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  ClipboardDocumentCheckIcon,
  ExclamationTriangleIcon,
  MapIcon,
  MoonIcon,
  PlusCircleIcon,
  RocketLaunchIcon,
  SparklesIcon,
  Squares2X2Icon,
  SunIcon,
  TruckIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import {
  activateP2pPolicyTerm,
  getP2pPolicyTermReadiness,
  getPolicyGenerationRun,
  listP2pPolicyTerms,
} from '../../api/p2pPolicy'
import { p2pStepTo } from '../../composables/useP2pPolicyWorkflow'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { formatIsoDate } from '../../util/datetime'
import { p2pTermActivateIdempotencyKey } from '../../util/idempotency'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const terms = ref([])
const selectedTerm = ref(null)
const selectedTermId = ref(null)
const loadingTerms = ref(true)
const readiness = ref(null)
const activating = ref(false)
const generationRun = ref(null)
const activateDialog = ref(null)
const activateChecked = ref(false)
const activateIdempotencyKey = ref('')
let pollTimer = null

const workflowTermId = computed(() => selectedTermId.value ?? null)

const cards = computed(() => [
  {
    to: p2pStepTo('p2pPolicyTerm', workflowTermId.value),
    label: t('p2p_policy_page.nav_term'),
    hint: t('p2p_policy_page.nav_term_hint'),
    icon: markRaw(CalendarDaysIcon),
    iconClass: 'text-violet-600 dark:text-violet-400',
    cardClass:
      'border-violet-200/90 from-violet-50/95 to-white ring-violet-900/[0.06] hover:border-violet-300 dark:border-violet-800/55 dark:from-violet-950/35 dark:to-slate-900/85 dark:ring-violet-900/25 dark:hover:border-violet-700',
  },
  {
    to: p2pStepTo('p2pPolicyRoutes', workflowTermId.value),
    label: t('p2p_policy_page.nav_routes'),
    hint: t('p2p_policy_page.nav_routes_hint'),
    icon: markRaw(MapIcon),
    iconClass: 'text-sky-600 dark:text-sky-400',
    cardClass:
      'border-sky-200/90 from-sky-50/95 to-white ring-sky-900/[0.06] hover:border-sky-300 dark:border-sky-800/55 dark:from-sky-950/35 dark:to-slate-900/85 dark:ring-sky-900/25 dark:hover:border-sky-700',
  },
  {
    to: p2pStepTo('p2pPolicyStudents', workflowTermId.value),
    label: t('p2p_policy_page.nav_students'),
    hint: t('p2p_policy_page.nav_students_hint'),
    icon: markRaw(UsersIcon),
    iconClass: 'text-emerald-600 dark:text-emerald-400',
    cardClass:
      'border-emerald-200/90 from-emerald-50/95 to-white ring-emerald-900/[0.06] hover:border-emerald-300 dark:border-emerald-800/55 dark:from-emerald-950/35 dark:to-slate-900/85 dark:ring-emerald-900/25 dark:hover:border-emerald-700',
  },
  {
    to: p2pStepTo('p2pPolicyTrips', workflowTermId.value),
    label: t('p2p_policy_page.nav_trips'),
    hint: t('p2p_policy_page.nav_trips_hint'),
    icon: markRaw(TruckIcon),
    iconClass: 'text-amber-600 dark:text-amber-400',
    cardClass:
      'border-amber-200/90 from-amber-50/95 to-white ring-amber-900/[0.06] hover:border-amber-300 dark:border-amber-800/55 dark:from-amber-950/35 dark:to-slate-900/85 dark:ring-amber-900/25 dark:hover:border-amber-700',
  },
])

const workflowSteps = computed(() => {
  const tid = workflowTermId.value
  return [
    {
      key: 'term',
      to: p2pStepTo('p2pPolicyTerm', tid),
      title: t('p2p_policy_page.hub_workflow_step1_title'),
      desc: t('p2p_policy_page.hub_workflow_step1_desc'),
      stepClass: 'bg-violet-600 shadow-violet-900/20 shadow-sm',
    },
    {
      key: 'routes',
      to: p2pStepTo('p2pPolicyRoutes', tid),
      title: t('p2p_policy_page.hub_workflow_step2_title'),
      desc: t('p2p_policy_page.hub_workflow_step2_desc'),
      stepClass: 'bg-sky-600 shadow-sky-900/20 shadow-sm',
    },
    {
      key: 'students',
      to: p2pStepTo('p2pPolicyStudents', tid),
      title: t('p2p_policy_page.hub_workflow_step3_title'),
      desc: t('p2p_policy_page.hub_workflow_step3_desc'),
      stepClass: 'bg-emerald-600 shadow-emerald-900/20 shadow-sm',
    },
    {
      key: 'activate',
      to: p2pStepTo('p2pPolicyHub', tid),
      title: t('p2p_policy_page.hub_workflow_step4_title'),
      desc: t('p2p_policy_page.hub_workflow_step4_desc'),
      stepClass: 'bg-teal-600 shadow-teal-900/20 shadow-sm',
    },
  ]
})

const progressPct = computed(() => {
  const run = generationRun.value
  if (!run?.total_slots) return 0
  return Math.min(100, Math.round((run.created_slots / run.total_slots) * 100))
})

const activateSummary = computed(() => {
  const term = selectedTerm.value
  if (!term) return ''
  const ay = term.academic_term?.academic_year ?? ''
  return t('p2p_policy_page.activate_modal_body', {
    year: ay,
    from: formatOperatingDate(term.operating_from),
    to: formatOperatingDate(term.operating_to),
  })
})

function formatOperatingDate(val) {
  const loc = locale.value === 'en' ? 'en' : 'vi'
  return formatIsoDate(val, loc)
}

function readinessIssueText(issue) {
  if (typeof issue === 'string') return issue
  const code = issue?.code
  if (!code) return ''
  const key = `p2p_policy_page.readiness_issue_${code}`
  const translated = t(key, { route: issue.route ?? '' })
  return translated === key ? code : translated
}

const readinessPanelClass = computed(() => {
  if (readiness.value?.ready) {
    return 'border-emerald-200/70 bg-gradient-to-br from-emerald-50/50 via-white to-teal-50/30 dark:border-emerald-900/40 dark:from-emerald-950/20 dark:via-slate-900/80 dark:to-teal-950/15'
  }
  return 'border-violet-200/70 bg-gradient-to-br from-amber-50/40 via-white to-violet-50/30 dark:border-violet-900/40 dark:from-amber-950/15 dark:via-slate-900/80 dark:to-violet-950/20'
})

const readinessIconWrapClass = computed(() =>
  readiness.value?.ready
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
)

const readinessIcon = computed(() =>
  readiness.value?.ready ? markRaw(CheckCircleIcon) : markRaw(ExclamationTriangleIcon),
)

function termStatusLabel(status) {
  const key = `p2p_policy_page.status_${status}`
  const translated = t(key)
  return translated === key ? status : translated
}

function statusBadgeClass(status) {
  const map = {
    draft: 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100',
    generating: 'bg-sky-100 text-sky-900 dark:bg-sky-900/40 dark:text-sky-100',
    active: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100',
    closed: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
  }
  return map[status] ?? map.draft
}

function formatTimeRange(start, end) {
  if (!start && !end) return '—'
  const s = (start ?? '').slice(0, 5)
  const e = (end ?? '').slice(0, 5)
  if (s && e) return `${s} — ${e}`
  return s || e || '—'
}

function termOptionLabel(term) {
  const ay = term.academic_term?.academic_year ?? `#${term.id}`
  const name = term.academic_term?.name ?? ''
  return `${ay}${name ? ` · ${name}` : ''} (${termStatusLabel(term.status)})`
}

async function loadReadinessForTerm(term) {
  if (!term) {
    readiness.value = null
    generationRun.value = null
    return
  }
  readiness.value = await getP2pPolicyTermReadiness(term.id)
  if (term.generation_run_id) {
    generationRun.value = await getPolicyGenerationRun(term.generation_run_id)
  } else {
    generationRun.value = null
  }
}

async function load() {
  loadingTerms.value = true
  try {
    const res = await listP2pPolicyTerms({ per_page: 20 })
    terms.value = res.items ?? []
    const qId = route.query.term_id ? Number(route.query.term_id) : null
    selectedTerm.value =
      terms.value.find((x) => x.id === qId) ??
      terms.value.find((x) => x.status === 'draft') ??
      terms.value[0] ??
      null
    selectedTermId.value = selectedTerm.value?.id ?? null
    if (selectedTerm.value) {
      await loadReadinessForTerm(selectedTerm.value)
    }
  } finally {
    loadingTerms.value = false
  }
}

function onTermChange() {
  const term = terms.value.find((x) => x.id === selectedTermId.value)
  selectedTerm.value = term ?? null
  router.replace({ query: { ...route.query, term_id: term?.id ?? undefined } })
  loadReadinessForTerm(term)
}

function openActivate() {
  if (!selectedTerm.value?.id) return
  activateChecked.value = false
  activateIdempotencyKey.value = p2pTermActivateIdempotencyKey(selectedTerm.value.id)
  activateDialog.value?.showModal()
}

function closeActivate() {
  activateDialog.value?.close()
}

async function confirmActivate() {
  if (!selectedTerm.value || activating.value) return
  activating.value = true
  try {
    const key =
      activateIdempotencyKey.value || p2pTermActivateIdempotencyKey(selectedTerm.value.id)
    const res = await activateP2pPolicyTerm(selectedTerm.value.id, key)
    generationRun.value = res.generation_run
    selectedTerm.value = res.term
    selectedTermId.value = res.term?.id ?? null
    closeActivate()
    startPoll()
  } catch (e) {
    showAppErrorFromApi(e, t('p2p_policy_page.activate_error_fallback'))
  } finally {
    activating.value = false
  }
}

function startPoll() {
  stopPoll()
  pollTimer = setInterval(async () => {
    if (!generationRun.value?.id) return
    generationRun.value = await getPolicyGenerationRun(generationRun.value.id)
    if (generationRun.value.status !== 'running') {
      stopPoll()
      await load()
    }
  }, 2000)
}

function stopPoll() {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = null
}

onMounted(load)
onUnmounted(stopPoll)

watch(
  () => route.query.term_id,
  () => load(),
)
</script>
