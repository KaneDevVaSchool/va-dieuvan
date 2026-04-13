<template>
  <div class="space-y-4">
    <Card title="Feature toggle">
      <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
        Bật/tắt module hoặc màn hình. User thường không thấy menu khi tắt; Super Admin luôn truy cập được. Chọn mẫu để khớp
        <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">FeatureToggleSeeder</code> (key cố định trong code Vue router).
      </p>
      <form class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 md:grid-cols-2 lg:grid-cols-5" @submit.prevent="create">
        <Select
          v-model="togglePresetIdx"
          label="Mẫu toggle (seed)"
          hint="Điền sẵn key / tên / module chuẩn. Thêm trùng key sẽ báo lỗi từ API."
          placeholder="— Không dùng mẫu —"
        >
          <option v-for="(row, i) in seedToggles" :key="row.key" :value="String(i)">
            {{ row.key }}
          </option>
        </Select>
        <Input
          v-model="form.key"
          label="Key (slug)"
          placeholder="module.operations"
          hint="Dạng module...., khớp meta.featureKey trên route (vd. module.system.roles)."
          required
        />
        <Input
          v-model="form.name"
          label="Tên hiển thị"
          placeholder="Điều vận..."
          hint="Nhãn trong cài đặt / tài liệu nội bộ."
          required
        />
        <Input
          v-model="form.module"
          label="Module"
          placeholder="operations"
          hint="Nhóm logic (overview, operations, system…), có thể để trống."
        />
        <div class="flex items-end gap-2">
          <label class="flex items-center gap-2 text-sm">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300" />
            Bật
          </label>
          <Button type="submit" :loading="saving">Thêm</Button>
        </div>
      </form>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải…</div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-2">Key</th>
              <th class="py-2 pr-2">Tên</th>
              <th class="py-2 pr-2">Module</th>
              <th class="py-2 pr-2">Bật</th>
              <th class="py-2 text-right">Xóa</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in items" :key="row.id" class="border-b border-slate-100 dark:border-slate-800">
              <td class="py-2 pr-2 font-mono text-xs">{{ row.key }}</td>
              <td class="py-2 pr-2">{{ row.name }}</td>
              <td class="py-2 pr-2">{{ row.module ?? '—' }}</td>
              <td class="py-2 pr-2">
                <input
                  type="checkbox"
                  :checked="row.is_enabled"
                  class="rounded border-slate-300"
                  @change="toggle(row, $event.target.checked)"
                />
              </td>
              <td class="py-2 text-right">
                <Button variant="secondary" class="text-red-600" @click="remove(row)">Xóa</Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { SEED_FEATURE_TOGGLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const form = reactive({ key: '', name: '', module: '', is_enabled: true })
const seedToggles = SEED_FEATURE_TOGGLE_PRESETS
const togglePresetIdx = ref('')

watch(togglePresetIdx, (v) => {
  if (v === '' || v == null) return
  const row = seedToggles[Number(v)]
  if (row) {
    form.key = row.key
    form.name = row.name
    form.module = row.module
  }
})

async function load() {
  loading.value = true
  try {
    items.value = (await admin.listFeatureToggles()) ?? []
  } catch (e) {
    alert(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function create() {
  if (!form.key.trim() || !form.name.trim()) return
  saving.value = true
  try {
    await admin.createFeatureToggle({
      key: form.key.trim(),
      name: form.name.trim(),
      module: form.module.trim() || null,
      is_enabled: !!form.is_enabled,
    })
    form.key = ''
    form.name = ''
    form.module = ''
    form.is_enabled = true
    togglePresetIdx.value = ''
    await load()
  } catch (e) {
    alert(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function toggle(row, enabled) {
  try {
    await admin.updateFeatureToggle(row.id, { is_enabled: enabled })
    row.is_enabled = enabled
  } catch (e) {
    alert(formatApiError(e))
    await load()
  }
}

async function remove(row) {
  if (!confirm(`Xóa toggle ${row.key}?`)) return
  try {
    await admin.deleteFeatureToggle(row.id)
    await load()
  } catch (e) {
    alert(formatApiError(e))
  }
}

onMounted(load)
</script>
