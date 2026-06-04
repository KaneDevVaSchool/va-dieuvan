<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.students_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('p2p_policy_page.students_subtitle') }}</p>
      </div>
      <Button @click="openCreate">
        <PlusIcon class="h-4 w-4" /> {{ t('p2p_policy_page.policy_new_title') }}
      </Button>
    </div>

    <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
      <Select v-model="filters.status" :label="t('p2p_policy_page.filter_status')">
        <option value="">{{ t('p2p_policy_page.status_all') }}</option>
        <option value="active">{{ labelStudentPolicyStatus('active') }}</option>
        <option value="inactive">{{ labelStudentPolicyStatus('inactive') }}</option>
        <option value="suspended">{{ labelStudentPolicyStatus('suspended') }}</option>
      </Select>
      <Select v-model="filters.timeSlot" :label="t('p2p_policy_page.filter_slot')">
        <option value="">{{ t('p2p_policy_page.slot_all') }}</option>
        <option value="morning">{{ t('p2p_policy_page.slot_morning') }}</option>
        <option value="afternoon">{{ t('p2p_policy_page.slot_afternoon') }}</option>
      </Select>
      <Select v-model="filters.semester" :label="t('p2p_policy_page.field_semester')">
        <option value="">{{ t('p2p_policy_page.semester_all') }}</option>
        <option value="1">{{ t('p2p_policy_page.semester_1') }}</option>
        <option value="2">{{ t('p2p_policy_page.semester_2') }}</option>
      </Select>
      <Select v-model="filters.routeId" :label="t('p2p_policy_page.filter_route')">
        <option value="">{{ t('p2p_policy_page.route_all') }}</option>
        <option v-for="r in routes" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
      </Select>
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>
    <div v-else-if="!items.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
      <UserGroupIcon class="mb-3 h-12 w-12 text-slate-300" />
      <p class="text-sm font-medium text-slate-600">{{ t('p2p_policy_page.empty') }}</p>
    </div>

    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[56rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_student') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_route') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.filter_slot') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.field_semester') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_effective') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_status') }}</th>
            <th class="px-3 py-2.5 text-right font-medium">{{ t('p2p_policy_page.col_actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="p in items" :key="p.id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2.5">
              <div class="font-medium text-slate-800">{{ p.student_name }}</div>
              <div class="text-xs text-slate-400">{{ p.class_name || p.student_code }}</div>
            </td>
            <td class="px-3 py-2.5 text-slate-700">{{ p.route_name }}</td>
            <td class="px-3 py-2.5">{{ labelTimeSlot(p.time_slot) }}</td>
            <td class="px-3 py-2.5">{{ t('p2p_policy_page.semester_n', { n: p.semester }) }} · {{ p.school_year }}</td>
            <td class="px-3 py-2.5 text-xs text-slate-500">{{ p.effective_from }} → {{ p.effective_to }}</td>
            <td class="px-3 py-2.5">
              <PolicyPill :text="labelStudentPolicyStatus(p.status)" :pill-class="studentPolicyStatusPillClass(p.status)" />
            </td>
            <td class="px-3 py-2.5 text-right">
              <AppRowActionsMenu :aria-label="t('p2p_policy_page.col_actions')">
                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-slate-50" @click="openEdit(p)">
                  {{ t('common.edit') }}
                </button>
                <button type="button" class="block w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50" @click="remove(p)">
                  {{ t('common.delete') }}
                </button>
              </AppRowActionsMenu>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <StudentPolicyFormModal :open="showForm" :policy="editing" :routes="routes" @close="showForm = false" @saved="onSaved" />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Select from '../../components/ui/Select.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import PolicyPill from '../../components/p2p/PolicyPill.vue'
import StudentPolicyFormModal from '../../components/p2p/StudentPolicyFormModal.vue'
import { listStudentPolicies, listPolicyRoutes, deleteStudentPolicy } from '../../api/p2p'
import { confirmAction } from '../../composables/useConfirm'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import {
  labelStudentPolicyStatus,
  studentPolicyStatusPillClass,
  labelTimeSlot,
} from '../../constants/policyTripStatus'

const { t } = useI18n()
const loading = ref(false)
const items = ref([])
const routes = ref([])
const showForm = ref(false)
const editing = ref(null)

const filters = reactive({ status: '', timeSlot: '', semester: '', routeId: '' })

async function load() {
  loading.value = true
  try {
    const res = await listStudentPolicies({
      status: filters.status || undefined,
      time_slot: filters.timeSlot || undefined,
      semester: filters.semester || undefined,
      route_id: filters.routeId || undefined,
    })
    items.value = res?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function loadRoutes() {
  try {
    routes.value = (await listPolicyRoutes())?.items ?? []
  } catch {
    routes.value = []
  }
}

watch(() => [filters.status, filters.timeSlot, filters.semester, filters.routeId], load)

function openCreate() {
  editing.value = null
  showForm.value = true
}
function openEdit(p) {
  editing.value = { ...p }
  showForm.value = true
}
function onSaved() {
  showForm.value = false
  load()
}

async function remove(p) {
  const ok = await confirmAction({
    title: t('p2p_policy_page.policy_delete_title'),
    message: t('p2p_policy_page.policy_delete_warning', { name: p.student_name }),
    danger: true,
    confirmLabel: t('common.delete'),
  })
  if (!ok) return
  try {
    const res = await deleteStudentPolicy(p.id)
    showAppSuccess(t('p2p_policy_page.policy_deleted_ok', { count: res?.affected_trips ?? 0 }))
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

onMounted(() => {
  loadRoutes()
  load()
})
</script>
