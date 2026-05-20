<template>
  <div class="space-y-4 pb-12">
    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.routes_title') }}</h1>
    <form class="grid gap-3 rounded-xl border p-4 sm:grid-cols-2 dark:border-slate-700" @submit.prevent="create">
      <select v-model="createForm.p2p_policy_term_id" required class="rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800">
        <option v-for="term in terms" :key="term.id" :value="term.id">{{ term.id }} — {{ term.status }}</option>
      </select>
      <input v-model="createForm.name" required :placeholder="t('p2p_policy_page.route_name')" class="rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800" />
      <select v-model="createForm.origin_campus_id" required class="rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800">
        <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="createForm.dest_campus_id" required class="rounded-md border px-2 py-2 dark:border-slate-600 dark:bg-slate-800">
        <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <button type="submit" class="sm:col-span-2 rounded-lg bg-va-800 px-4 py-2 text-sm text-white">{{ t('p2p_policy_page.add_route') }}</button>
    </form>
    <ul class="divide-y rounded-xl border dark:border-slate-700">
      <li v-for="r in routes" :key="r.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
        <div>
          <p class="font-medium">{{ r.name }}</p>
          <p class="text-xs text-slate-500">{{ r.origin_campus?.name }} → {{ r.dest_campus?.name }} · HS: {{ r.policy_students_count ?? 0 }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <select v-model="assignDraft[r.id].vehicle_id" class="rounded border px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-800">
            <option value="">{{ t('p2p_policy_page.pick_vehicle') }}</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }}</option>
          </select>
          <select v-model="assignDraft[r.id].driver_id" class="rounded border px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-800">
            <option value="">{{ t('p2p_policy_page.pick_driver') }}</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.full_name }}</option>
          </select>
          <button type="button" class="rounded bg-slate-800 px-2 py-1 text-xs text-white" @click="saveAssign(r.id)">
            {{ t('p2p_policy_page.save') }}
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { http } from '../../api/http'
import { assignPolicyRoute, createPolicyRoute, listCampuses, listP2pPolicyTerms, listPolicyRoutes } from '../../api/p2pPolicy'

const { t } = useI18n()
const routes = ref([])
const terms = ref([])
const campuses = ref([])
const vehicles = ref([])
const drivers = ref([])
const assignDraft = reactive({})

const createForm = reactive({
  p2p_policy_term_id: '',
  name: '',
  origin_campus_id: '',
  dest_campus_id: '',
})

async function load() {
  const [tr, c, rt] = await Promise.all([
    listP2pPolicyTerms({ per_page: 30 }),
    listCampuses({ per_page: 50 }),
    listPolicyRoutes({ per_page: 50 }),
  ])
  terms.value = tr.items ?? []
  campuses.value = c.items ?? []
  routes.value = rt.items ?? []
  if (terms.value[0]) createForm.p2p_policy_term_id = terms.value[0].id
  if (campuses.value[0]) {
    createForm.origin_campus_id = campuses.value[0].id
    createForm.dest_campus_id = campuses.value[1]?.id ?? campuses.value[0].id
  }
  const [{ data: v }, { data: d }] = await Promise.all([
    http.get('/vehicles', { params: { per_page: 100 } }),
    http.get('/drivers', { params: { per_page: 100 } }),
  ])
  vehicles.value = v.data?.items ?? v.data ?? []
  drivers.value = d.data?.items ?? d.data ?? []
  for (const r of routes.value) {
    assignDraft[r.id] = {
      vehicle_id: r.vehicle_id ?? '',
      driver_id: r.driver_id ?? '',
    }
  }
}

async function create() {
  await createPolicyRoute({ ...createForm })
  createForm.name = ''
  await load()
}

async function saveAssign(routeId) {
  const p = assignDraft[routeId]
  await assignPolicyRoute(routeId, {
    vehicle_id: Number(p.vehicle_id),
    driver_id: Number(p.driver_id),
  })
  await load()
}

onMounted(load)
</script>
