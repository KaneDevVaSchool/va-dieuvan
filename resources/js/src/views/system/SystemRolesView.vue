<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-6 sm:space-y-6 sm:pb-8">
    <header class="border-l-4 border-[color:var(--va-brand)] pl-4">
      <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-xl">
        Vai trò người dùng (Role)
      </h1>
      <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        Tạo và đặt tên các vai trò (ví dụ điều vận, kế toán), rồi gán quyền cho từng vai. Siêu quản trị luôn có đủ quyền.
        Chọn «mẫu có sẵn» để tránh gõ nhầm mã.
      </p>
    </header>

    <Card title="Thêm vai trò mới">
      <AppFilterBar class="mb-1">
        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          Biểu mẫu
        </div>
        <form class="grid gap-4 md:grid-cols-2 lg:grid-cols-4" @submit.prevent="create">
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
            <Button type="submit" class="w-full sm:w-auto" :loading="saving">Thêm vai trò</Button>
          </div>
        </form>
      </AppFilterBar>
    </Card>

    <Card title="Danh sách vai trò">
      <AppFilterBar class="mb-5">
        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          Lọc nhanh
        </div>
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
          <div class="min-w-0 flex-1">
            <Input
              v-model="filterQ"
              label="Tìm theo mã hoặc tên hiển thị"
              placeholder="Gõ một phần mã / tên…"
              class="w-full"
            />
          </div>
          <Button
            v-if="filterQ.trim()"
            variant="secondary"
            type="button"
            class="w-full shrink-0 md:w-auto"
            @click="filterQ = ''"
          >
            Xóa lọc
          </Button>
        </div>
        <p v-if="!loading" class="mt-3 text-xs text-slate-500 dark:text-slate-400">
          Hiển thị {{ filteredItems.length }} / {{ items.length }} vai trò.
        </p>
      </AppFilterBar>

      <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-500 dark:text-slate-400">
        <span
          class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600 dark:border-slate-600 dark:border-t-teal-400"
          aria-hidden="true"
        />
        Đang tải…
      </div>

      <template v-else-if="!items.length">
        <div
          class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 py-12 text-center dark:border-slate-700 dark:bg-slate-900/30"
        >
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có vai trò nào.</p>
        </div>
      </template>

      <template v-else>
        <div
          v-if="filteredItems.length === 0"
          class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200/90"
        >
          Không có vai trò khớp bộ lọc.
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[32rem] border-collapse text-left text-sm">
              <thead>
                <tr
                  class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300"
                >
                  <th class="whitespace-nowrap py-3 pl-4 pr-3 font-semibold">Mã</th>
                  <th class="whitespace-nowrap py-3 pr-3 font-semibold">Tên hiển thị</th>
                  <th class="whitespace-nowrap py-3 pr-3 font-semibold">Số quyền</th>
                  <th class="whitespace-nowrap py-3 pl-2 pr-4 text-right font-semibold">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(r, ri) in filteredItems"
                  :key="r.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="ri % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <td class="py-3 pl-4 pr-3 align-middle font-mono text-xs text-slate-800 dark:text-slate-200">
                    {{ r.name }}
                  </td>
                  <td class="py-3 pr-3 align-middle font-medium text-slate-900 dark:text-slate-100">
                    {{ r.display_name ?? '—' }}
                  </td>
                  <td class="py-3 pr-3 align-middle tabular-nums text-slate-600 dark:text-slate-400">
                    {{ r.permissions_count ?? 0 }}
                  </td>
                  <td class="py-3 pl-2 pr-4 text-right align-middle">
                    <Button variant="secondary" class="mr-1" @click="openEdit(r)">Sửa</Button>
                    <Button variant="secondary" class="text-red-600" :disabled="r.name === 'superadmin'" @click="remove(r)">
                      Xóa
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
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
            <div
              class="max-h-48 overflow-y-auto rounded-lg border border-violet-200/70 bg-slate-50/50 p-2 dark:border-violet-900/35 dark:bg-slate-900/40"
            >
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
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
const filterQ = ref('')

const filteredItems = computed(() => {
  const q = filterQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((r) => {
    const name = (r.name ?? '').toLowerCase()
    const dn = (r.display_name ?? '').toLowerCase()
    return name.includes(q) || dn.includes(q)
  })
})

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
