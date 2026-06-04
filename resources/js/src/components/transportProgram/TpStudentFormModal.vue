<template>
  <Modal :open="true" :title="student ? 'Sửa học sinh' : 'Thêm học sinh'" @close="$emit('close')">
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <Input v-model="form.code" label="Mã học sinh" required :disabled="!!student" />
        <Input v-model="form.full_name" label="Họ tên" required />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <Input v-model="form.grade" label="Khối" />
        <Input v-model="form.class_name" label="Lớp" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <Input v-model="form.parent_name" label="Phụ huynh" />
        <Input v-model="form.parent_phone" label="SĐT phụ huynh" />
      </div>
      <Input v-model="form.address" label="Địa chỉ" />
      <Select v-if="student" v-model="form.status" label="Trạng thái">
        <option value="active">Đang học</option>
        <option value="inactive">Ngừng</option>
        <option value="transferred">Chuyển trường</option>
        <option value="graduated">Tốt nghiệp</option>
      </Select>
      <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
        <Button variant="secondary" @click="$emit('close')">Hủy</Button>
        <Button :loading="saving" @click="submit">Lưu</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { reactive, ref } from 'vue'
import Modal from '../ui/Modal.vue'
import Input from '../ui/Input.vue'
import Select from '../ui/Select.vue'
import Button from '../ui/Button.vue'
import { createStudent, updateStudent } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const props = defineProps({ student: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])
const saving = ref(false)

const form = reactive({
  code: props.student?.code ?? '',
  full_name: props.student?.full_name ?? '',
  grade: props.student?.grade ?? '',
  class_name: props.student?.class_name ?? '',
  parent_name: props.student?.parent_name ?? '',
  parent_phone: props.student?.parent_phone ?? '',
  address: props.student?.address ?? '',
  status: props.student?.status ?? 'active',
})

async function submit() {
  saving.value = true
  try {
    if (props.student) {
      await updateStudent(props.student.id, form)
    } else {
      const { status, ...payload } = form
      await createStudent(payload)
    }
    showAppSuccess('Đã lưu.')
    emit('saved')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}
</script>
