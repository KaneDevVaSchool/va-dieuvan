<template>
  <Modal
    :open="true"
    wide
    :title="student ? 'Sửa thông tin học sinh' : 'Thêm học sinh mới'"
    :description="student ? 'Cập nhật hồ sơ học sinh trong hệ thống đưa đón.' : 'Điền hồ sơ học sinh. Các trường có dấu * là bắt buộc.'"
    @close="$emit('close')"
  >
    <form class="space-y-6" @submit.prevent="submit">
      <!-- ── Thông tin học sinh ─────────────────────────────── -->
      <section class="space-y-3">
        <div class="flex items-center gap-2">
          <AcademicCapIcon class="h-4 w-4 text-va-700" />
          <h3 class="text-sm font-semibold text-slate-800">Thông tin học sinh</h3>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Input
            v-model="form.code"
            label="Mã học sinh"
            required
            :disabled="!!student"
            placeholder="VD: HS2026001"
            tooltip="Mã định danh duy nhất của học sinh. Không thể thay đổi sau khi tạo."
          />
          <Input
            v-model="form.full_name"
            label="Họ và tên"
            required
            placeholder="VD: Nguyễn Văn An"
            tooltip="Họ tên đầy đủ của học sinh theo giấy khai sinh."
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Select
            v-model="form.metadata.gender"
            label="Giới tính"
            placeholder="— Chọn giới tính —"
            tooltip="Giới tính của học sinh."
          >
            <option value="male">Nam</option>
            <option value="female">Nữ</option>
            <option value="other">Khác</option>
          </Select>
          <Input
            v-model="form.metadata.date_of_birth"
            type="date"
            label="Ngày sinh"
            tooltip="Ngày sinh dùng để tính tuổi học sinh."
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Input
            v-model="form.grade"
            label="Khối"
            placeholder="VD: 1, 2, 6, 10..."
            tooltip="Khối lớp của học sinh."
          />
          <Input
            v-model="form.class_name"
            label="Lớp"
            placeholder="VD: 1A, 6A2..."
            tooltip="Tên lớp học sinh đang theo học."
          />
        </div>
      </section>

      <!-- ── Gia đình & liên hệ ─────────────────────────────── -->
      <section class="space-y-3 border-t border-slate-100 pt-5">
        <div class="flex items-center gap-2">
          <UsersIcon class="h-4 w-4 text-va-700" />
          <h3 class="text-sm font-semibold text-slate-800">Gia đình &amp; liên hệ</h3>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Input
            v-model="form.metadata.father_name"
            label="Họ tên cha"
            placeholder="VD: Nguyễn Văn Bình"
            tooltip="Họ tên đầy đủ của cha học sinh."
          />
          <Input
            v-model="form.metadata.father_phone"
            type="tel"
            label="SĐT cha"
            placeholder="VD: 0901 234 567"
            tooltip="Số điện thoại liên hệ của cha."
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Input
            v-model="form.metadata.mother_name"
            label="Họ tên mẹ"
            placeholder="VD: Trần Thị Hoa"
            tooltip="Họ tên đầy đủ của mẹ học sinh."
          />
          <Input
            v-model="form.metadata.mother_phone"
            type="tel"
            label="SĐT mẹ"
            placeholder="VD: 0907 654 321"
            tooltip="Số điện thoại liên hệ của mẹ."
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Input
            v-model="form.parent_name"
            label="Người liên hệ chính"
            placeholder="VD: Nguyễn Văn Bình (Cha)"
            tooltip="Người được liên hệ đầu tiên khi cần (cha, mẹ hoặc người giám hộ)."
          />
          <Input
            v-model="form.parent_phone"
            type="tel"
            label="SĐT liên hệ chính"
            placeholder="VD: 0901 234 567"
            tooltip="Số điện thoại ưu tiên gọi khi đưa đón hoặc khẩn cấp."
          />
        </div>
      </section>

      <!-- ── Địa chỉ & đưa đón ──────────────────────────────── -->
      <section class="space-y-3 border-t border-slate-100 pt-5">
        <div class="flex items-center gap-2">
          <MapPinIcon class="h-4 w-4 text-va-700" />
          <h3 class="text-sm font-semibold text-slate-800">Địa chỉ &amp; đưa đón</h3>
        </div>

        <Input
          v-model="form.address"
          label="Địa chỉ nhà"
          placeholder="VD: 123 Lê Lợi, P. Bến Nghé, Q.1, TP.HCM"
          tooltip="Địa chỉ thường trú của học sinh."
        />
        <Input
          v-model="form.metadata.pickup_point"
          label="Điểm đón"
          placeholder="VD: Cổng chung cư Sunrise, ngã tư Hàng Xanh..."
          tooltip="Vị trí cụ thể tài xế đón / trả học sinh. Có thể khác địa chỉ nhà."
        />

        <label class="block">
          <div class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600">
            <span>Ghi chú</span>
            <span
              title="Lưu ý đặc biệt: dị ứng, sức khỏe, người được phép đón thay..."
              class="inline-flex cursor-help text-slate-400 transition hover:text-slate-600"
            >
              <InformationCircleIcon class="h-3.5 w-3.5" />
            </span>
          </div>
          <textarea
            v-model="form.metadata.note"
            rows="2"
            placeholder="VD: Học sinh bị dị ứng đậu phộng, ông nội có thể đón thay..."
            class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring placeholder:text-slate-400"
          ></textarea>
        </label>

        <Select v-if="student" v-model="form.status" label="Trạng thái" tooltip="Trạng thái học vụ của học sinh.">
          <option value="active">Đang học</option>
          <option value="inactive">Ngừng</option>
          <option value="transferred">Chuyển trường</option>
          <option value="graduated">Tốt nghiệp</option>
        </Select>
      </section>

      <!-- ── Actions ────────────────────────────────────────── -->
      <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
        <Button type="button" variant="secondary" @click="$emit('close')">Hủy</Button>
        <Button type="submit" :loading="saving">{{ student ? 'Lưu thay đổi' : 'Thêm học sinh' }}</Button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { reactive, ref } from 'vue'
import {
  AcademicCapIcon, UsersIcon, MapPinIcon, InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import Modal from '../ui/Modal.vue'
import Input from '../ui/Input.vue'
import Select from '../ui/Select.vue'
import Button from '../ui/Button.vue'
import { createStudent, updateStudent } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const props = defineProps({ student: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])
const saving = ref(false)

function buildForm(student) {
  return {
    code: student?.code ?? '',
    full_name: student?.full_name ?? '',
    grade: student?.grade ?? '',
    class_name: student?.class_name ?? '',
    parent_name: student?.parent_name ?? '',
    parent_phone: student?.parent_phone ?? '',
    address: student?.address ?? '',
    status: student?.status ?? 'active',
    metadata: {
      gender: student?.gender ?? student?.metadata?.gender ?? '',
      date_of_birth: student?.date_of_birth ?? student?.metadata?.date_of_birth ?? '',
      father_name: student?.father_name ?? student?.metadata?.father_name ?? '',
      father_phone: student?.father_phone ?? student?.metadata?.father_phone ?? '',
      mother_name: student?.mother_name ?? student?.metadata?.mother_name ?? '',
      mother_phone: student?.mother_phone ?? student?.metadata?.mother_phone ?? '',
      pickup_point: student?.pickup_point ?? student?.metadata?.pickup_point ?? '',
      note: student?.note ?? student?.metadata?.note ?? '',
    },
  }
}

const form = reactive(buildForm(props.student))

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
