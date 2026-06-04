<template>
  <div class="mx-auto max-w-[1400px] space-y-4 pb-8">
    <Card title="Ma trận quyền — Role × Permission">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-600 dark:text-slate-400">
          Chọn ô để bật/tắt quyền; lưu gửi delta lên server.
        </p>
        <Button :disabled="saving || !dirty" @click="save">Lưu thay đổi</Button>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">Đang tải ma trận…</p>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700">
              <th class="sticky left-0 z-10 bg-white px-3 py-2 text-left font-semibold dark:bg-slate-900">
                Permission
              </th>
              <th
                v-for="role in roles"
                :key="role.id"
                class="px-2 py-2 text-center font-medium text-slate-700 dark:text-slate-300"
              >
                {{ role.display_name || role.name }}
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="mod in modules" :key="mod.module">
              <tr class="bg-slate-50 dark:bg-slate-800/50">
                <td :colspan="roles.length + 1" class="px-3 py-1.5 text-xs font-bold uppercase text-slate-500">
                  {{ mod.module }}
                </td>
              </tr>
              <tr
                v-for="perm in mod.permissions"
                :key="perm.id"
                class="border-b border-slate-100 dark:border-slate-800"
              >
                <td class="sticky left-0 z-10 bg-white px-3 py-1.5 dark:bg-slate-900">
                  <span class="font-medium">{{ perm.label }}</span>
                  <span class="block text-[11px] text-slate-500">{{ perm.name }}</span>
                </td>
                <td v-for="role in roles" :key="`${role.id}-${perm.id}`" class="px-2 py-1.5 text-center">
                  <input
                    type="checkbox"
                    :checked="hasPerm(role.id, perm.id)"
                    class="h-4 w-4 rounded border-slate-300 text-teal-600"
                    @change="toggle(role.id, perm.id, $event.target.checked)"
                  />
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { getPermissionMatrix, syncPermissionMatrix } from '../../api/system'
import { showAppError, showAppSuccess } from '../../composables/appMessage'

const loading = ref(true)
const saving = ref(false)
const dirty = ref(false)
const roles = ref([])
const modules = ref([])
const matrix = ref({})
const original = ref({})

function hasPerm(roleId, permId) {
  const list = matrix.value[String(roleId)] || []
  return list.includes(permId)
}

function toggle(roleId, permId, checked) {
  const key = String(roleId)
  const set = new Set(matrix.value[key] || [])
  if (checked) {
    set.add(permId)
  } else {
    set.delete(permId)
  }
  matrix.value[key] = [...set]
  dirty.value = true
}

function buildChanges() {
  const changes = []
  for (const role of roles.value) {
    const rid = String(role.id)
    const before = new Set(original.value[rid] || [])
    const after = new Set(matrix.value[rid] || [])
    const grant = [...after].filter((id) => !before.has(id))
    const revoke = [...before].filter((id) => !after.has(id))
    if (grant.length || revoke.length) {
      changes.push({ role_id: role.id, grant, revoke })
    }
  }
  return changes
}

async function save() {
  const changes = buildChanges()
  if (!changes.length) return
  saving.value = true
  try {
    await syncPermissionMatrix(changes)
    showAppSuccess('Đã lưu ma trận quyền.')
    original.value = JSON.parse(JSON.stringify(matrix.value))
    dirty.value = false
  } catch (e) {
    showAppError(e)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const data = await getPermissionMatrix()
    roles.value = data.roles || []
    modules.value = data.modules || []
    matrix.value = data.matrix || {}
    original.value = JSON.parse(JSON.stringify(matrix.value))
  } catch (e) {
    showAppError(e)
  } finally {
    loading.value = false
  }
})
</script>
