<template>
  <div class="space-y-6 pb-12">
    <div>
      <h1 class="text-xl font-bold text-slate-900 dark:text-white md:text-2xl">
        {{ t('p2p_policy_page.hub_title') }}
      </h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        {{ t('p2p_policy_page.hub_subtitle') }}
      </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <RouterLink
        v-for="card in cards"
        :key="card.to"
        :to="card.to"
        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-900/5 transition hover:border-teal-200 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-teal-800"
      >
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ card.label }}</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ card.hint }}</p>
      </RouterLink>
    </div>

    <section v-if="selectedTerm" class="rounded-2xl border border-violet-200/70 bg-gradient-to-r from-slate-50 via-violet-50/50 to-indigo-50/30 p-4 dark:border-violet-800/50 dark:from-slate-950 dark:via-violet-950/30">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('p2p_policy_page.readiness_title') }}</h2>
      <ul v-if="readiness?.issues?.length" class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-200">
        <li v-for="(issue, i) in readiness.issues" :key="i">{{ issue }}</li>
      </ul>
      <p v-else-if="readiness?.ready" class="mt-2 text-sm text-emerald-700 dark:text-emerald-300">
        {{ t('p2p_policy_page.readiness_ok') }}
      </p>
      <button
        v-if="readiness?.ready && selectedTerm.status === 'draft'"
        type="button"
        class="mt-4 rounded-lg bg-va-800 px-4 py-2 text-sm font-medium text-white hover:bg-va-900 disabled:opacity-50"
        :disabled="activating"
        @click="openActivate"
      >
        {{ t('p2p_policy_page.activate_cta') }}
      </button>
      <div v-if="generationRun && generationRun.status === 'running'" class="mt-4">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          {{ t('p2p_policy_page.generating_progress', { created: generationRun.created_slots, total: generationRun.total_slots }) }}
        </p>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
          <div
            class="h-full bg-teal-500 transition-all"
            :style="{ width: progressPct + '%' }"
          />
        </div>
      </div>
    </section>

    <dialog ref="activateDialog" class="rounded-xl border border-slate-200 p-0 shadow-xl dark:border-slate-700 dark:bg-slate-900">
      <form class="max-w-md p-6" @submit.prevent="confirmActivate">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('p2p_policy_page.activate_modal_title') }}</h3>
        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ activateSummary }}</p>
        <label class="mt-4 flex items-start gap-2 text-sm">
          <input v-model="activateChecked" type="checkbox" class="mt-1 rounded border-slate-300" />
          <span>{{ t('p2p_policy_page.activate_confirm_checkbox') }}</span>
        </label>
        <div class="mt-6 flex justify-end gap-2">
          <button type="button" class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300" @click="closeActivate">
            {{ t('common.cancel') }}
          </button>
          <button
            type="submit"
            class="rounded-lg bg-va-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
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
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import {
  activateP2pPolicyTerm,
  getP2pPolicyTermReadiness,
  getPolicyGenerationRun,
  listP2pPolicyTerms,
} from '../../api/p2pPolicy'

const { t } = useI18n()
const route = useRoute()

const terms = ref([])
const selectedTerm = ref(null)
const readiness = ref(null)
const activating = ref(false)
const generationRun = ref(null)
const activateDialog = ref(null)
const activateChecked = ref(false)
let pollTimer = null

const cards = computed(() => [
  { to: { name: 'p2pPolicyTerm' }, label: t('p2p_policy_page.nav_term'), hint: t('p2p_policy_page.nav_term_hint') },
  { to: { name: 'p2pPolicyRoutes' }, label: t('p2p_policy_page.nav_routes'), hint: t('p2p_policy_page.nav_routes_hint') },
  { to: { name: 'p2pPolicyStudents' }, label: t('p2p_policy_page.nav_students'), hint: t('p2p_policy_page.nav_students_hint') },
])

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
    from: term.operating_from,
    to: term.operating_to,
  })
})

async function load() {
  const res = await listP2pPolicyTerms({ per_page: 20 })
  terms.value = res.items ?? []
  const qId = route.query.term_id ? Number(route.query.term_id) : null
  selectedTerm.value =
    terms.value.find((x) => x.id === qId) ??
    terms.value.find((x) => x.status === 'draft') ??
    terms.value[0] ??
    null
  if (selectedTerm.value) {
    readiness.value = await getP2pPolicyTermReadiness(selectedTerm.value.id)
    if (selectedTerm.value.generation_run_id) {
      generationRun.value = await getPolicyGenerationRun(selectedTerm.value.generation_run_id)
    }
  }
}

function openActivate() {
  activateChecked.value = false
  activateDialog.value?.showModal()
}

function closeActivate() {
  activateDialog.value?.close()
}

async function confirmActivate() {
  if (!selectedTerm.value) return
  activating.value = true
  try {
    const res = await activateP2pPolicyTerm(
      selectedTerm.value.id,
      `p2p-policy-activate-${selectedTerm.value.id}-${Date.now()}`,
    )
    generationRun.value = res.generation_run
    selectedTerm.value = res.term
    closeActivate()
    startPoll()
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
