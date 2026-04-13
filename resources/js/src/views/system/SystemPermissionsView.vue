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
          hint="Chọn để điền đúng chuỗi tên đã dùng trong code/điều kiện."
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
          label="Mô tả hiển thị"
          placeholder="Giống tên quyền hoặc mô tả ngắn"
          hint="Hiển thị tại màn gán quyền; seed mặc định trùng tên kỹ thuật."
        />
        <div class="flex items-end">
          <Button type="submit" :loading="saving">Thêm</Button>
        </div>
      </form>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải…</div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[32rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-2">Permission</th>
              <th class="py-2 pr-2">Hiển thị</th>
              <th class="py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id" class="border-b border-slate-100 dark:border-slate-800">
              <td class="py-2 pr-2 font-mono text-xs">{{ p.name }}</td>
              <td class="py-2 pr-2">{{ p.display_name ?? '—' }}</td>
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
      <Card class="w-full max-w-md" title="Sửa permission">
        <div class="space-y-3">
          <Input
            v-model="editForm.name"
            label="Tên quyền"
            hint="Đổi tên sẽ cần cập nhật mọi nơi đang kiểm tra permission bằng chuỗi cũ."
          />
          <Input v-model="editForm.display_name" label="Mô tả hiển thị" hint="Nhãn thân thiện cho admin khi xem danh sách." />
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
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const editing = ref(null)
const form = reactive({ name: '', display_name: '' })
const editForm = reactive({ name: '', display_name: '' })
const seedPermissions = SEED_PERMISSION_PRESETS
const permissionPreset = ref('')

watch(permissionPreset, (v) => {
  if (!v) return
  const p = seedPermissions.find((x) => x.name === v)
  if (p) {
    form.name = p.name
    form.display_name = p.display_name
  }
})

async function load() {
  loading.value = true
  try {
    items.value = (await admin.listPermissions()) ?? []
  } catch (e) {
    alert(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function create() {
  if (!form.name.trim()) return
  saving.value = true
  try {
    await admin.createPermission({ name: form.name.trim(), display_name: form.display_name || null })
    form.name = ''
    form.display_name = ''
    permissionPreset.value = ''
    await load()
  } catch (e) {
    alert(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function openEdit(p) {
  editing.value = p
  editForm.name = p.name
  editForm.display_name = p.display_name ?? ''
}

async function saveEdit() {
  if (!editing.value) return
  saving.value = true
  try {
    await admin.updatePermission(editing.value.id, {
      name: editForm.name.trim(),
      display_name: editForm.display_name || null,
    })
    editing.value = null
    await load()
  } catch (e) {
    alert(formatApiError(e))
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
    alert(formatApiError(e))
  }
}

onMounted(load)
</script>
