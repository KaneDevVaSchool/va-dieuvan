<template>
  <div class="space-y-4">
    <Card title="Quản lý Role">
      <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
        Tạo / sửa vai trò và gán quyền (permission). Super Admin luôn có toàn quyền.
      </p>
      <form class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 md:grid-cols-3" @submit.prevent="create">
        <Input v-model="form.name" label="Tên (slug)" placeholder="dispatcher" required />
        <Input v-model="form.display_name" label="Tên hiển thị" placeholder="Dispatcher" />
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
          <Input v-model="editForm.name" label="Tên (slug)" />
          <Input v-model="editForm.display_name" label="Tên hiển thị" />
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Quyền</label>
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
import { onMounted, reactive, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const allPerms = ref([])
const editing = ref(null)
const form = reactive({ name: '', display_name: '' })
const editForm = reactive({ name: '', display_name: '', permission_ids: [] })

async function load() {
  loading.value = true
  try {
    const [roles, perms] = await Promise.all([admin.listRoles(), admin.listPermissions()])
    items.value = roles ?? []
    allPerms.value = perms ?? []
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
    await admin.createRole({ name: form.name.trim(), display_name: form.display_name || null, permission_ids: [] })
    form.name = ''
    form.display_name = ''
    await load()
  } catch (e) {
    alert(formatApiError(e))
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
    alert(formatApiError(e))
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
    alert(formatApiError(e))
  }
}

onMounted(load)
</script>
