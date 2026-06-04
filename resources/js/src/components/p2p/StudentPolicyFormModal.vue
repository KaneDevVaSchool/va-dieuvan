<template>
  <Modal
    :open="open"
    :title="isEdit ? t('p2p_policy_page.policy_edit_title') : t('p2p_policy_page.policy_new_title')"
    @close="emit('close')"
  >
    <div class="space-y-4">
      <!-- Học sinh -->
      <div v-if="isEdit" class="rounded-lg bg-slate-50 px-3 py-2 text-sm">
        <div class="font-medium text-slate-800">{{ form.student_name }}</div>
        <div class="text-xs text-slate-500">{{ form.class_name }}</div>
      </div>
      <div v-else class="relative">
        <label class="block">
          <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('p2p_policy_page.field_student') }}<span class="text-rose-600"> *</span></span>
          <input
            v-model="studentQuery"
            type="text"
            class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring"
            :placeholder="t('p2p_policy_page.search_student_ph')"
            @input="onStudentSearch"
            @focus="onStudentSearch"
          />
        </label>
        <div
          v-if="studentResults.length && !selectedStudent"
          class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md border border-slate-200 bg-white shadow-lg"
        >
          <button
            v-for="s in studentResults"
            :key="s.id"
            type="button"
            class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
            @click="pickStudent(s)"
          >
            <span class="font-medium text-slate-800">{{ s.full_name }}</span>
            <span class="ml-1 text-xs text-slate-500">{{ s.class_name || s.student_code }}</span>
          </button>
        </div>
        <div v-if="selectedStudent" class="mt-1 flex items-center justify-between rounded-md bg-teal-50 px-3 py-1.5 text-sm text-teal-800">
          <span>{{ selectedStudent.full_name }} · {{ selectedStudent.class_name || selectedStudent.student_code }}</span>
          <button type="button" class="text-xs text-teal-700 underline" @click="clearStudent">{{ t('common.change') }}</button>
        </div>
      </div>

      <Select v-model="form.route_id" :label="t('p2p_policy_page.field_route')" :placeholder="t('p2p_policy_page.pick_route')" required>
        <option v-for="r in routes" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
      </Select>

      <div class="grid grid-cols-2 gap-3">
        <Input v-model="form.school_year" :label="t('p2p_policy_page.field_school_year')" placeholder="2025-2026" required />
        <Select v-model="form.semester" :label="t('p2p_policy_page.field_semester')" required>
          <option value="1">{{ t('p2p_policy_page.semester_1') }}</option>
          <option value="2">{{ t('p2p_policy_page.semester_2') }}</option>
        </Select>
      </div>

      <!-- Ca: edit = 1 ca; create = chọn ca hoặc cả 2 (E1) -->
      <div>
        <Select v-if="isEdit" v-model="form.time_slot" :label="t('p2p_policy_page.field_slot')" required>
          <option value="morning">{{ t('p2p_policy_page.slot_morning') }}</option>
          <option value="afternoon">{{ t('p2p_policy_page.slot_afternoon') }}</option>
        </Select>
        <template v-else>
          <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('p2p_policy_page.field_slot') }}<span class="text-rose-600"> *</span></span>
          <div class="flex flex-wrap gap-3 text-sm">
            <label class="inline-flex items-center gap-1.5"><input v-model="createSlots" type="checkbox" value="morning" /> {{ t('p2p_policy_page.slot_morning') }}</label>
            <label class="inline-flex items-center gap-1.5"><input v-model="createSlots" type="checkbox" value="afternoon" /> {{ t('p2p_policy_page.slot_afternoon') }}</label>
          </div>
          <p class="mt-1 text-[11px] text-slate-400">{{ t('p2p_policy_page.slot_both_hint') }}</p>
        </template>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <Input v-model="form.effective_from" type="date" :label="t('p2p_policy_page.field_from')" required />
        <Input v-model="form.effective_to" type="date" :label="t('p2p_policy_page.field_to')" required />
      </div>

      <Select v-if="isEdit" v-model="form.status" :label="t('p2p_policy_page.col_status')">
        <option value="active">{{ labelStudentPolicyStatus('active') }}</option>
        <option value="inactive">{{ labelStudentPolicyStatus('inactive') }}</option>
        <option value="suspended">{{ labelStudentPolicyStatus('suspended') }}</option>
      </Select>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

      <div class="flex justify-end gap-2 pt-1">
        <Button variant="secondary" :disabled="loading" @click="emit('close')">{{ t('common.cancel') }}</Button>
        <Button :loading="loading" :disabled="!canSubmit" @click="submit">{{ t('common.save') }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import Select from '../ui/Select.vue'
import Input from '../ui/Input.vue'
import {
  createStudentPolicy,
  updateStudentPolicy,
  searchPolicyStudents,
  getStudentPolicyImpact,
} from '../../api/p2p'
import { formatApiError } from '../../api/http'
import { confirmAction } from '../../composables/useConfirm'
import { showAppSuccess } from '../../composables/appMessage'
import { labelStudentPolicyStatus } from '../../constants/policyTripStatus'

const props = defineProps({
  open: { type: Boolean, default: false },
  policy: { type: Object, default: null },
  routes: { type: Array, default: () => [] },
})
const emit = defineEmits(['close', 'saved'])
const { t } = useI18n()

const isEdit = computed(() => !!props.policy?.id)

const form = reactive({
  student_id: '',
  student_name: '',
  class_name: '',
  route_id: '',
  school_year: defaultSchoolYear(),
  semester: '1',
  time_slot: 'morning',
  effective_from: '',
  effective_to: '',
  status: 'active',
})
const createSlots = ref(['morning'])
const selectedStudent = ref(null)
const studentQuery = ref('')
const studentResults = ref([])
const loading = ref(false)
const error = ref('')
let searchTimer = null

const canSubmit = computed(() => {
  if (!form.route_id || !form.school_year || !form.effective_from || !form.effective_to) return false
  if (isEdit.value) return true
  return !!selectedStudent.value && createSlots.value.length > 0
})

watch(
  () => props.open,
  (v) => {
    if (!v) return
    error.value = ''
    studentQuery.value = ''
    studentResults.value = []
    if (isEdit.value) {
      Object.assign(form, {
        student_id: props.policy.student_id,
        student_name: props.policy.student_name,
        class_name: props.policy.class_name,
        route_id: String(props.policy.route_id ?? ''),
        school_year: props.policy.school_year ?? defaultSchoolYear(),
        semester: String(props.policy.semester ?? '1'),
        time_slot: props.policy.time_slot ?? 'morning',
        effective_from: props.policy.effective_from ?? '',
        effective_to: props.policy.effective_to ?? '',
        status: props.policy.status ?? 'active',
      })
    } else {
      Object.assign(form, {
        student_id: '', student_name: '', class_name: '',
        route_id: '', school_year: defaultSchoolYear(), semester: '1',
        time_slot: 'morning', effective_from: '', effective_to: '', status: 'active',
      })
      createSlots.value = ['morning']
      selectedStudent.value = null
    }
  },
)

function onStudentSearch() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    try {
      const res = await searchPolicyStudents(studentQuery.value)
      studentResults.value = res?.items ?? []
    } catch {
      studentResults.value = []
    }
  }, 250)
}

function pickStudent(s) {
  selectedStudent.value = s
  form.student_id = s.id
  studentResults.value = []
}

function clearStudent() {
  selectedStudent.value = null
  form.student_id = ''
  studentQuery.value = ''
}

function basePayload() {
  return {
    route_id: Number(form.route_id),
    school_year: form.school_year,
    semester: Number(form.semester),
    effective_from: form.effective_from,
    effective_to: form.effective_to,
  }
}

async function submit() {
  loading.value = true
  error.value = ''
  try {
    if (isEdit.value) {
      await submitEdit()
    } else {
      await submitCreate()
    }
    emit('saved')
    emit('close')
  } catch (err) {
    if (err?.name === 'AbortError') return // người dùng hủy xác nhận
    error.value = formatApiError(err)
  } finally {
    loading.value = false
  }
}

async function submitCreate() {
  const slots = createSlots.value
  for (const slot of slots) {
    await createStudentPolicy({ ...basePayload(), student_id: Number(form.student_id), time_slot: slot })
  }
}

async function submitEdit() {
  const stopping = ['inactive', 'suspended'].includes(form.status) && props.policy.status === 'active'
  if (stopping) {
    let affected = 0
    try {
      affected = (await getStudentPolicyImpact(props.policy.id))?.affected_trips ?? 0
    } catch {
      /* ignore impact lookup error */
    }
    const ok = await confirmAction({
      title: t('p2p_policy_page.policy_stop_title'),
      message: t('p2p_policy_page.policy_stop_warning', { count: affected }),
      danger: true,
    })
    if (!ok) {
      throw new DOMException('cancelled', 'AbortError')
    }
  }
  const res = await updateStudentPolicy(props.policy.id, {
    ...basePayload(),
    time_slot: form.time_slot,
    status: form.status,
  })
  if (res?.affected_trips) {
    showAppSuccess(t('p2p_policy_page.policy_stopped_ok', { count: res.affected_trips }))
  }
}

function defaultSchoolYear() {
  const now = new Date()
  const y = now.getFullYear()
  return now.getMonth() + 1 >= 8 ? `${y}-${y + 1}` : `${y - 1}-${y}`
}
</script>
