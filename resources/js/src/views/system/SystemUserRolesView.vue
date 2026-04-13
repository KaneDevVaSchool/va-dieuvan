<template>
  <div class="space-y-4">
    <Card title="Gán quyền người dùng">
      <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
        Tìm user theo tên / email, sau đó gán hoặc gỡ role (áp dụng ngay).
      </p>
      <div class="relative max-w-xl">
        <Input
          v-model="query"
          label="Tìm user"
          placeholder="Gõ ít nhất 2 ký tự…"
          autocomplete="off"
          @input="onQueryInput"
        />
        <div
          v-if="suggestions.length"
          class="absolute z-10 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-900"
        >
          <button
            v-for="u in suggestions"
            :key="u.id"
            type="button"
            class="flex w-full flex-col items-start gap-0.5 px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800"
            @click="selectUser(u)"
          >
            <span class="font-medium">{{ u.name }}</span>
            <span class="text-xs text-slate-500">{{ u.email }}</span>
          </button>
        </div>
      </div>

      <div v-if="selected" class="mt-6 space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700">
        <div class="text-sm">
          Đang chọn: <b>{{ selected.name }}</b> — {{ selected.email }}
        </div>
        <div v-if="loadingDetail" class="text-sm text-slate-500">Đang tải role…</div>
        <div v-else class="space-y-2">
          <label v-for="r in roles" :key="r.id" class="flex cursor-pointer items-center gap-2 text-sm">
            <input v-model="selectedRoleIds" type="checkbox" :value="r.id" class="rounded border-slate-300" />
            <span class="font-mono text-xs">{{ r.name }}</span>
            <span v-if="r.display_name" class="text-slate-500">({{ r.display_name }})</span>
          </label>
          <Button :loading="saving" @click="save">Lưu vai trò</Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'

const query = ref('')
const suggestions = ref([])
const selected = ref(null)
const roles = ref([])
const selectedRoleIds = ref([])
const loadingDetail = ref(false)
const saving = ref(false)
let searchTimer = null

async function onQueryInput() {
  clearTimeout(searchTimer)
  const q = query.value.trim()
  if (q.length < 2) {
    suggestions.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      suggestions.value = (await admin.searchUsers(q)) ?? []
    } catch {
      suggestions.value = []
    }
  }, 280)
}

async function selectUser(u) {
  selected.value = u
  suggestions.value = []
  query.value = `${u.name} (${u.email})`
  loadingDetail.value = true
  try {
    const [allRoles, detail] = await Promise.all([admin.listRoles(), admin.getUserRoles(u.id)])
    roles.value = allRoles ?? []
    selectedRoleIds.value = (detail.roles ?? []).map((r) => r.id)
  } catch (e) {
    alert(formatApiError(e))
  } finally {
    loadingDetail.value = false
  }
}

async function save() {
  if (!selected.value) return
  saving.value = true
  try {
    await admin.syncUserRoles(selected.value.id, selectedRoleIds.value)
    alert('Đã cập nhật vai trò.')
  } catch (e) {
    alert(formatApiError(e))
  } finally {
    saving.value = false
  }
}

</script>
