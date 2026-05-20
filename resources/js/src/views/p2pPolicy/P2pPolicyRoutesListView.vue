<template>
  <div class="p2p-routes-page w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="{ name: 'p2pPolicyHub' }"
          class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-base font-medium text-teal-800 transition hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
        >
          <ArrowLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('p2p_policy_page.back_to_hub') }}
        </RouterLink>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
          {{ t('p2p_policy_page.routes_title') }}
        </h1>
        <p class="max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.routes_subtitle') }}
        </p>
      </div>
    </header>

    <form @submit.prevent="create">
      <Card :hint="t('p2p_policy_page.tip_section_routes_create')">
        <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_routes_create') }}</h2>
        <div class="grid gap-5 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_p2p_term')" :hint="t('p2p_policy_page.tip_p2p_term')" required />
            <select v-model="createForm.p2p_policy_term_id" required class="p2p-term-input mt-2 w-full">
              <option disabled value="">{{ t('p2p_policy_page.placeholder_campus') }}</option>
              <option v-for="term in terms" :key="term.id" :value="term.id">{{ p2pTermLabel(term) }}</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_route_name')" :hint="t('p2p_policy_page.tip_route_name')" required />
            <input
              v-model="createForm.name"
              required
              class="p2p-term-input mt-2 w-full"
              :placeholder="t('p2p_policy_page.placeholder_route_name')"
            />
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_origin_campus')" :hint="t('p2p_policy_page.tip_campus')" required />
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
              <select v-model="createForm.origin_campus_id" required class="p2p-term-input min-w-0 flex-1">
                <option disabled value="">{{ t('p2p_policy_page.placeholder_campus') }}</option>
                <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <button
                type="button"
                class="shrink-0 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-base font-medium text-teal-900 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
                @click="openCampusModal"
              >
                {{ t('p2p_policy_page.add_campus_btn') }}
              </button>
            </div>
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_dest_campus')" :hint="t('p2p_policy_page.tip_campus')" required />
            <select v-model="createForm.dest_campus_id" required class="p2p-term-input mt-2 w-full">
              <option disabled value="">{{ t('p2p_policy_page.placeholder_campus') }}</option>
              <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
        </div>
        <button type="submit" class="mt-5 rounded-xl bg-va-800 px-6 py-3 text-base font-semibold text-white hover:bg-va-900">
          {{ t('p2p_policy_page.add_route') }}
        </button>
      </Card>
    </form>

    <Card :hint="t('p2p_policy_page.tip_section_routes_list')">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_routes_list') }}</h2>
        <div class="min-w-[14rem]">
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.filter_routes_by_term')" />
          <select v-model="listTermId" class="p2p-term-input mt-2 w-full" @change="loadRoutes">
            <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
            <option v-for="term in terms" :key="term.id" :value="term.id">{{ p2pTermLabel(term) }}</option>
          </select>
        </div>
      </div>
      <ul class="divide-y rounded-xl border border-slate-200 dark:border-slate-700">
        <li v-for="r in routes" :key="r.id" class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 text-sm">
          <div>
            <p class="text-base font-medium text-slate-900 dark:text-white">{{ r.name }}</p>
            <p class="text-xs text-slate-500">{{ r.origin_campus?.name }} → {{ r.dest_campus?.name }} · HS: {{ r.policy_students_count ?? 0 }}</p>
          </div>
          <div class="flex flex-wrap items-end gap-2">
            <div>
              <span class="text-xs font-semibold text-slate-600">{{ t('p2p_policy_page.pick_vehicle') }}</span>
              <select v-model="assignDraft[r.id].vehicle_id" class="mt-1 rounded-md border px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" :title="t('p2p_policy_page.tip_assign_vehicle')">
                <option value="">{{ t('p2p_policy_page.pick_vehicle') }}</option>
                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }}</option>
              </select>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-600">{{ t('p2p_policy_page.pick_driver') }}</span>
              <select v-model="assignDraft[r.id].driver_id" class="mt-1 rounded-md border px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" :title="t('p2p_policy_page.tip_assign_driver')">
                <option value="">{{ t('p2p_policy_page.pick_driver') }}</option>
                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.full_name }}</option>
              </select>
            </div>
            <button type="button" class="rounded-lg bg-slate-800 px-3 py-2 text-xs text-white" @click="saveAssign(r.id)">
              {{ t('p2p_policy_page.save') }}
            </button>
          </div>
        </li>
        <li v-if="!routes.length" class="px-4 py-8 text-center text-slate-500">{{ t('p2p_policy_page.empty') }}</li>
      </ul>
    </Card>

    <dialog ref="campusDialog" class="w-[min(100%,28rem)] rounded-2xl border p-0 shadow-xl dark:border-slate-700 dark:bg-slate-900">
      <form class="p-5 space-y-4" @submit.prevent="submitCampus">
        <h3 class="text-lg font-bold">{{ t('p2p_policy_page.add_campus_modal_title') }}</h3>
        <p class="text-sm text-slate-500">{{ t('p2p_policy_page.add_campus_modal_hint') }}</p>
        <div>
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.campus_field_code')" required />
          <input v-model="campusForm.code" required class="p2p-term-input mt-2 w-full" :placeholder="t('p2p_policy_page.campus_placeholder_code')" />
        </div>
        <div>
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.campus_field_name')" required />
          <input v-model="campusForm.name" required class="p2p-term-input mt-2 w-full" :placeholder="t('p2p_policy_page.campus_placeholder_name')" />
        </div>
        <p v-if="campusError" class="text-sm text-rose-600">{{ campusError }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="closeCampusModal">{{ t('p2p_policy_page.cancel') }}</button>
          <button type="submit" class="rounded-lg bg-va-800 px-4 py-2 text-sm text-white" :disabled="campusSaving">
            {{ t('p2p_policy_page.add_campus_save') }}
          </button>
        </div>
      </form>
    </dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { http } from '../../api/http'
import P2pPolicyFieldLabel from '../../components/p2pPolicy/P2pPolicyFieldLabel.vue'
import Card from '../../components/ui/Card.vue'
import {
  assignPolicyRoute,
  createCampus,
  createPolicyRoute,
  listCampuses,
  listP2pPolicyTerms,
  listPolicyRoutes,
} from '../../api/p2pPolicy'

const { t } = useI18n()
const routes = ref([])
const terms = ref([])
const campuses = ref([])
const vehicles = ref([])
const drivers = ref([])
const assignDraft = reactive({})
const listTermId = ref('')

const createForm = reactive({
  p2p_policy_term_id: '',
  name: '',
  origin_campus_id: '',
  dest_campus_id: '',
})

const campusDialog = ref(null)
const campusSaving = ref(false)
const campusError = ref('')
const campusForm = reactive({ code: '', name: '' })

function p2pTermLabel(term) {
  const year = term.academic_term?.academic_year ?? ''
  return year ? `${year} — ${term.status}` : `#${term.id} — ${term.status}`
}

async function loadCampuses() {
  const c = await listCampuses({ per_page: 100 })
  campuses.value = c.items ?? []
}

async function loadRoutes() {
  const params = { per_page: 100 }
  if (listTermId.value) params.p2p_policy_term_id = listTermId.value
  const rt = await listPolicyRoutes(params)
  routes.value = rt.items ?? []
  for (const r of routes.value) {
    if (!assignDraft[r.id]) {
      assignDraft[r.id] = { vehicle_id: r.vehicle_id ?? '', driver_id: r.driver_id ?? '' }
    }
  }
}

async function load() {
  const [tr] = await Promise.all([listP2pPolicyTerms({ per_page: 30 }), loadCampuses()])
  terms.value = tr.items ?? []
  if (terms.value[0] && !createForm.p2p_policy_term_id) {
    createForm.p2p_policy_term_id = terms.value[0].id
    listTermId.value = terms.value[0].id
  }
  if (campuses.value[0] && !createForm.origin_campus_id) {
    createForm.origin_campus_id = campuses.value[0].id
    createForm.dest_campus_id = campuses.value[1]?.id ?? campuses.value[0].id
  }
  const [{ data: v }, { data: d }] = await Promise.all([
    http.get('/vehicles', { params: { per_page: 100 } }),
    http.get('/drivers', { params: { per_page: 100 } }),
  ])
  vehicles.value = v.data?.items ?? v.data ?? []
  drivers.value = d.data?.items ?? d.data ?? []
  await loadRoutes()
}

async function create() {
  await createPolicyRoute({ ...createForm })
  createForm.name = ''
  await loadRoutes()
}

async function saveAssign(routeId) {
  const p = assignDraft[routeId]
  await assignPolicyRoute(routeId, {
    vehicle_id: Number(p.vehicle_id),
    driver_id: Number(p.driver_id),
  })
  await loadRoutes()
}

function openCampusModal() {
  campusError.value = ''
  campusForm.code = ''
  campusForm.name = ''
  campusDialog.value?.showModal()
}

function closeCampusModal() {
  campusDialog.value?.close()
}

async function submitCampus() {
  campusSaving.value = true
  campusError.value = ''
  try {
    const created = await createCampus({ ...campusForm, is_active: true })
    await loadCampuses()
    if (!createForm.origin_campus_id) createForm.origin_campus_id = created.id
    closeCampusModal()
  } catch (e) {
    campusError.value = e?.response?.data?.message ?? t('p2p_policy_page.add_campus_error')
  } finally {
    campusSaving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.p2p-term-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base text-slate-900 shadow-sm ring-1 ring-slate-900/5 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100;
}
</style>
