<template>
  <div class="space-y-4">
    <Card title="Quản lý Permission">
      <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
        Đặt tên quyền theo <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">module.hành_động</code> (ví dụ
        <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">request.create</code>). Danh sách dưới khớp
        <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">RbacSeeder</code>.
      </p>
      <form class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 md:grid-cols-2 lg:grid-cols-4" @submit.prevent="create">
        <Select
          v-model="permissionPreset"
          label="Mẫu permission (seed)"
          hint="Chọn để điền tên, nhãn và mô tả dễ hiểu từ mẫu hệ thống."
          placeholder="— Không dùng mẫu —"
        >
          <option v-for="p in seedPermissions" :key="p.name" :value="p.name">{{ p.name }}</option>
        </Select>
        <Input
          v-model="form.name"
          label="Tên quyền"
          placeholder="module.action"
          hint="Không khoảng trắng; dùng dấu chấm phân tách (vd. trip.assign)."
          required
        />
        <Input
          v-model="form.display_name"
          label="Mô tả hiển thị (kỹ thuật)"
          placeholder="Giống tên quyền hoặc nhãn ngắn"
          hint="Nhãn khi admin xem danh sách permission."
        />
        <div class="flex items-end">
          <Button type="submit" :loading="saving">Thêm</Button>
        </div>
        <div class="md:col-span-2 lg:col-span-4">
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Mô tả dễ hiểu (cho người không chuyên IT)</span>
            <textarea
              v-model="form.plain_description"
              rows="2"
              class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900"
              placeholder="Ví dụ: Được tạo yêu cầu điều xe mới — trình bày bằng tiếng đời thường."
            />
            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
              Có thể để trống: hệ thống sẽ dùng mô tả mẫu theo tên quyền (nếu có).
            </span>
          </label>
        </div>
      </form>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải…</div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-2">Permission</th>
              <th class="py-2 pr-2">Nhãn hiển thị</th>
              <th class="py-2 pr-2">Ý nghĩa (dễ hiểu)</th>
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
      <Card class="w-full max-w-lg" title="Sửa permission">
        <div class="space-y-3">
          <Input
            v-model="editForm.name"
            label="Tên quyền"
            hint="Đổi tên sẽ cần cập nhật mọi nơi đang kiểm tra permission bằng chuỗi cũ."
          />
          <Input
            v-model="editForm.display_name"
            label="Nhãn hiển thị (kỹ thuật)"
            hint="Hiển thị bên cạnh tên trong danh sách admin."
          />
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Mô tả dễ hiểu (không chuyên IT)</span>
            <textarea
              v-model="editForm.plain_description"
              rows="3"
              class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900"
            />
            <span class="mt-1 block text-xs text-slate-500">Để trống = dùng mô tả mẫu theo tên (file dữ liệu hệ thống).</span>
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
import { showAppError } from '../../composables/appMessage'

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
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  if (!confirm(`Xóa permission ${p.name}?`)) return
  try {
    await admin.deletePermission(p.id)
    await load()
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

onMounted(load)
</script>
