<template>
  <div class="space-y-4">
    <Card title="Quản lý Role">
      <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
        Tạo / sửa vai trò và gán quyền (permission). Super Admin luôn có toàn quyền. Chọn mẫu bên dưới để khớp
        <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">RbacSeeder</code> — tránh gõ sai tên.
      </p>
      <form class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 md:grid-cols-2 lg:grid-cols-4" @submit.prevent="create">
        <Select
          v-model="rolePreset"
          label="Mẫu role (seed)"
          hint="Chọn để điền sẵn slug và tên hiển thị. Vẫn có thể sửa trước khi lưu."
          placeholder="— Không dùng mẫu —"
        >
          <option v-for="r in seedRoles" :key="r.name" :value="r.name">{{ r.name }} — {{ r.display_name }}</option>
        </Select>
        <Input
          v-model="form.name"
          label="Tên (slug)"
          placeholder="dispatcher"
          hint="Chữ thường, số, gạch dưới; ví dụ dispatcher, internal_user."
          required
        />
        <Input
          v-model="form.display_name"
          label="Tên hiển thị"
          placeholder="Dispatcher"
          hint="Nhãn cho người dùng cuối (có dấu, có thể có khoảng trắng)."
        />
        <div class="flex items-end">
          <Button type="submit" :loading="saving">Thêm role</Button>
        </div>
      </form>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải…</div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[32rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-2">Role</th>
              <th class="py-2 pr-2">Hiển thị</th>
              <th class="py-2 pr-2"># Quyền</th>
              <th class="py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in items" :key="r.id" class="border-b border-slate-100 dark:border-slate-800">
              <td class="py-2 pr-2 font-mono text-xs">{{ r.name }}</td>
              <td class="py-2 pr-2">{{ r.display_name ?? '—' }}</td>
              <td class="py-2 pr-2">{{ r.permissions_count ?? 0 }}</td>
              <td class="py-2 text-right">
                <Button variant="secondary" class="mr-1" @click="openEdit(r)">Sửa</Button>
                <Button variant="secondary" class="text-red-600" :disabled="r.name === 'superadmin'" @click="remove(r)">
                  Xóa
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>

    <div
      v-if="editing"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      @click.self="editing = null"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto" :title="`Sửa role: ${editing.name}`">
        <div class="space-y-3">
          <Input
            v-model="editForm.name"
            label="Tên (slug)"
            hint="Đổi slug có thể ảnh hưởng code/policy tra cứu theo tên role."
          />
          <Input v-model="editForm.display_name" label="Tên hiển thị" hint="Hiển thị trên giao diện / báo cáo." />
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Quyền</label>
            <p class="mb-2 text-xs text-slate-500">Tick các permission áp cho role này (danh sách lấy từ bảng permissions).</p>
            <div class="max-h-48 overflow-y-auto rounded border border-slate-200 p-2 dark:border-slate-600">
              <label v-for="p in allPerms" :key="p.id" class="flex cursor-pointer items-center gap-2 py-1 text-sm">
                <input v-model="editForm.permission_ids" type="checkbox" :value="p.id" class="rounded border-slate-300" />
                <span class="font-mono text-xs">{{ p.name }}</span>
              </label>
            </div>
          </div>
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
import { SEED_ROLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError } from '../../composables/appMessage'

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const allPerms = ref([])
const editing = ref(null)
const form = reactive({ name: '', display_name: '' })
const editForm = reactive({ name: '', display_name: '', permission_ids: [] })
const seedRoles = SEED_ROLE_PRESETS
const rolePreset = ref('')

watch(rolePreset, (v) => {
  if (!v) return
  const r = seedRoles.find((x) => x.name === v)
  if (r) {
    form.name = r.name
    form.display_name = r.display_name
  }
})

async function load() {
  loading.value = true
  try {
    const [roles, perms] = await Promise.all([admin.listRoles(), admin.listPermissions()])
    items.value = roles ?? []
    allPerms.value = perms ?? []
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
    await admin.createRole({ name: form.name.trim(), display_name: form.display_name || null, permission_ids: [] })
    form.name = ''
    form.display_name = ''
    rolePreset.value = ''
    await load()
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function openEdit(r) {
  editing.value = r
  editForm.name = r.name
  editForm.display_name = r.display_name ?? ''
  editForm.permission_ids = []
  admin
    .getRole(r.id)
    .then((detail) => {
      editForm.permission_ids = (detail.permissions ?? []).map((p) => p.id)
    })
    .catch(() => {})
}

async function saveEdit() {
  if (!editing.value) return
  saving.value = true
  try {
    await admin.updateRole(editing.value.id, {
      name: editForm.name.trim(),
      display_name: editForm.display_name || null,
      permission_ids: editForm.permission_ids,
    })
    editing.value = null
    await load()
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function remove(r) {
  if (!confirm(`Xóa role ${r.name}?`)) return
  try {
    await admin.deleteRole(r.id)
    await load()
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

onMounted(load)
</script>
