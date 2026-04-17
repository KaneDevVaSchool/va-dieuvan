<template>
  <div class="space-y-4">
    <Card title="Quyền thao tác (Permission)">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        Mỗi quyền mô tả một hành động cụ thể (ví dụ tạo yêu cầu, duyệt chuyến). Mã quyền dùng chữ thường và dấu chấm,
        ví dụ <span class="font-mono text-xs">request.create</span>. Chọn mẫu có sẵn để điền nhanh và giảm sai sót.
      </p>
      <form class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 md:grid-cols-2 lg:grid-cols-4" @submit.prevent="create">
        <Select
          v-model="permissionPreset"
          label="Mẫu có sẵn"
          hint="Điền sẵn mã và mô tả; có thể chỉnh trước khi thêm."
          placeholder="— Không dùng mẫu —"
        >
          <option value="">— Không dùng mẫu —</option>
          <option v-for="p in seedPermissions" :key="p.name" :value="p.name">{{ p.name }}</option>
        </Select>
        <Input
          v-model="form.name"
          label="Mã quyền trong hệ thống"
          placeholder="vd. request.create"
          hint="Không dấu cách; các phần cách nhau bằng dấu chấm."
          required
        />
        <Input
          v-model="form.display_name"
          label="Nhãn ngắn (tùy chọn)"
          placeholder="vd. request.create"
          hint="Hiển thị cạnh mã trong danh sách quản trị."
        />
        <div class="flex items-end">
          <Button type="submit" :loading="saving">Thêm quyền</Button>
        </div>
        <div class="md:col-span-2 lg:col-span-4">
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Giải thích cho người vận hành</span>
            <textarea
              v-model="form.plain_description"
              rows="2"
              class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900"
              placeholder="Ví dụ: Được phép tạo yêu cầu điều xe mới trên hệ thống."
            />
            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
              Nên điền để bộ phận nhân sự / quản trị hiểu rõ quyền này dùng để làm gì. Có thể để trống nếu đã có mô tả mẫu.
            </span>
          </label>
        </div>
      </form>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải…</div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-2">Mã quyền</th>
              <th class="py-2 pr-2">Nhãn ngắn</th>
              <th class="py-2 pr-2">Giải thích</th>
              <th class="py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id" class="border-b border-slate-100 dark:border-slate-800">
              <td class="py-2 pr-2 font-mono text-xs">{{ p.name }}</td>
              <td class="max-w-[12rem] py-2 pr-2">{{ p.display_name ?? '—' }}</td>
              <td class="max-w-xl py-2 pr-2 text-slate-700 dark:text-slate-300">{{ p.plain_summary ?? '—' }}</td>
              <td class="py-2 text-right">
                <Button variant="secondary" class="mr-1" @click="openEdit(p)">Sửa</Button>
                <Button variant="secondary" class="text-red-600" @click="remove(p)">Xóa</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>

    <div
      v-if="editing"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="editing = null"
    >
      <Card class="w-full max-w-lg" title="Sửa quyền">
        <div class="space-y-3">
          <Input
            v-model="editForm.name"
            label="Mã quyền"
            hint="Đổi mã cần rà soát toàn bộ chỗ đang dùng mã cũ — chỉ thực hiện khi có kế hoạch."
          />
          <Input
            v-model="editForm.display_name"
            label="Nhãn ngắn"
            hint="Hiển thị trong danh sách quản trị."
          />
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Giải thích cho người vận hành</span>
            <textarea
              v-model="editForm.plain_description"
              rows="3"
              class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900"
            />
            <span class="mt-1 block text-xs text-slate-500">Để trống: dùng mô tả mẫu theo mã (nếu có trong hệ thống).</span>
          </label>
          <div class="flex justify-end gap-2">
            <Button variant="secondary" type="button" @click="editing = null">Hủy</Button>
            <Button :loading="saving" @click="saveEdit">Lưu</Button>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { SEED_PERMISSION_PRESETS } from '../../config/systemSeedOptions'
import permissionPlainVi from '../../data/permission_plain_vi.json'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const editing = ref(null)
const form = reactive({ name: '', display_name: '', plain_description: '' })
const editForm = reactive({ name: '', display_name: '', plain_description: '' })
const seedPermissions = SEED_PERMISSION_PRESETS
const permissionPreset = ref('')

watch(permissionPreset, (v) => {
  if (!v) return
  const p = seedPermissions.find((x) => x.name === v)
  if (p) {
    form.name = p.name
    form.display_name = p.display_name
    form.plain_description = permissionPlainVi[v] ?? ''
  }
})

async function load() {
  loading.value = true
  try {
    items.value = (await admin.listPermissions()) ?? []
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function create() {
  if (!form.name.trim()) return
  saving.value = true
  try {
    await admin.createPermission({
      name: form.name.trim(),
      display_name: form.display_name?.trim() || null,
      plain_description: form.plain_description?.trim() || null,
    })
    form.name = ''
    form.display_name = ''
    form.plain_description = ''
    permissionPreset.value = ''
    await load()
    showAppSuccess('Đã thêm quyền mới.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function openEdit(p) {
  editing.value = p
  editForm.name = p.name
  editForm.display_name = p.display_name ?? ''
  editForm.plain_description = p.plain_description ?? ''
}

async function saveEdit() {
  if (!editing.value) return
  saving.value = true
  try {
    await admin.updatePermission(editing.value.id, {
      name: editForm.name.trim(),
      display_name: editForm.display_name?.trim() || null,
      plain_description: editForm.plain_description?.trim() || null,
    })
    editing.value = null
    await load()
    showAppSuccess('Đã lưu thay đổi quyền.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  const ok = await confirmAction({
    title: 'Xóa quyền?',
    message: `Xóa quyền «${p.name}»? Các vai trò đang dùng quyền này có thể cần chỉnh lại.`,
    confirmLabel: 'Xóa',
    danger: true,
  })
  if (!ok) return
  try {
    await admin.deletePermission(p.id)
    await load()
    showAppSuccess('Đã xóa quyền.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

onMounted(load)
</script>
