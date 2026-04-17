<template>
  <div class="space-y-4">
    <Card title="Vai trò người dùng (Role)">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        Tạo và đặt tên các vai trò (ví dụ điều vận, kế toán), rồi gán các quyền thao tác bên dưới cho từng vai.
        Tài khoản siêu quản trị luôn có đủ quyền. Chọn «mẫu có sẵn» để tránh gõ nhầm mã.
      </p>
      <form class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 md:grid-cols-2 lg:grid-cols-4" @submit.prevent="create">
        <Select
          v-model="rolePreset"
          label="Mẫu có sẵn"
          hint="Điền sẵn mã trong hệ thống; có thể chỉnh trước khi thêm."
          placeholder="— Không dùng mẫu —"
        >
          <option value="">— Không dùng mẫu —</option>
          <option v-for="r in seedRoles" :key="r.name" :value="r.name">{{ r.name }} — {{ r.display_name }}</option>
        </Select>
        <Input
          v-model="form.name"
          label="Mã vai trò trong hệ thống"
          placeholder="vd. dispatcher"
          hint="Chữ thường, số hoặc gạch dưới; không dùng dấu cách."
          required
        />
        <Input
          v-model="form.display_name"
          label="Tên hiển thị"
          placeholder="vd. Điều vận"
          hint="Tên đọc được trên giao diện (có dấu, có khoảng trắng)."
        />
        <div class="flex items-end">
          <Button type="submit" :loading="saving">Thêm vai trò</Button>
        </div>
      </form>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải…</div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[32rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-2">Mã</th>
              <th class="py-2 pr-2">Tên hiển thị</th>
              <th class="py-2 pr-2">Số quyền</th>
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
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto" :title="`Sửa vai trò: ${editing.name}`">
        <div class="space-y-3">
          <Input
            v-model="editForm.name"
            label="Mã vai trò"
            hint="Đổi mã có thể ảnh hưởng báo cáo hoặc tích hợp cũ — chỉ đổi khi thật sự cần."
          />
          <Input v-model="editForm.display_name" label="Tên hiển thị" hint="Tên đọc được khi chọn vai trò trên giao diện." />
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Quyền được phép thao tác</label>
            <p class="mb-2 text-xs text-slate-500">Chọn các quyền áp dụng cho vai này (danh sách do quản trị định nghĩa).</p>
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
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'

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
    showAppSuccess('Đã thêm vai trò mới.', 'Thành công')
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
    showAppSuccess('Đã lưu thay đổi vai trò.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function remove(r) {
  const ok = await confirmAction({
    title: 'Xóa vai trò?',
    message: `Xóa vai trò «${r.display_name || r.name}» (mã ${r.name})? Người đang dùng vai này có thể bị ảnh hưởng.`,
    confirmLabel: 'Xóa',
    danger: true,
  })
  if (!ok) return
  try {
    await admin.deleteRole(r.id)
    await load()
    showAppSuccess('Đã xóa vai trò.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

onMounted(load)
</script>
