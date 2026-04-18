<template>
  <div class="space-y-4 sm:space-y-6">
    <Card :title="t('feature_toggles.page_title')">
      <form
        class="grid gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 sm:grid-cols-2 lg:grid-cols-6"
        @submit.prevent="create"
      >
        <Select
          v-model="togglePresetIdx"
          :label="t('feature_toggles.preset_label')"
          :placeholder="t('feature_toggles.preset_ph')"
          class="sm:col-span-2"
        >
          <option value="">{{ t('feature_toggles.preset_ph') }}</option>
          <option v-for="(row, i) in seedToggles" :key="row.key" :value="String(i)">
            {{ row.name }} ({{ row.key }})
          </option>
        </Select>
        <Input
          v-model="form.key"
          :label="t('feature_toggles.key_label')"
          :placeholder="t('feature_toggles.key_ph')"
          required
        />
        <Input
          v-model="form.name"
          :label="t('feature_toggles.name_label')"
          :placeholder="t('feature_toggles.name_ph')"
          required
        />
        <Input v-model="form.module" :label="t('feature_toggles.module_label')" :placeholder="t('feature_toggles.module_ph')" />
        <div class="flex flex-col justify-end gap-2 sm:flex-row sm:items-end">
          <label class="flex min-h-[2.5rem] cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-600" />
            {{ t('feature_toggles.default_on') }}
          </label>
          <Button type="submit" class="w-full sm:w-auto" :loading="saving">{{ t('feature_toggles.add_btn') }}</Button>
        </div>
      </form>

      <div v-if="!loading && items.length" class="mt-4">
        <Input
          v-model="filterQ"
          :label="t('feature_toggles.filter_label')"
          :placeholder="t('feature_toggles.filter_ph')"
          class="max-w-md"
        />
      </div>

      <div v-if="loading" class="mt-4 text-sm text-slate-500">{{ t('feature_toggles.loading') }}</div>

      <template v-else>
        <h3 class="mt-6 text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ t('feature_toggles.table_title') }}
        </h3>

        <div v-if="filteredItems.length === 0" class="mt-3 rounded-lg border border-dashed border-slate-200 py-8 text-center text-sm text-slate-500 dark:border-slate-700">
          {{ t('feature_toggles.empty_filtered') }}
        </div>

        <!-- Desktop -->
        <div v-else class="mt-3 hidden md:block overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
          <table class="w-full min-w-[52rem] text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/90 text-slate-600 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400">
                <th class="py-2.5 pl-3 pr-2 font-medium">{{ t('feature_toggles.col_name') }}</th>
                <th class="py-2.5 pr-2 font-medium">{{ t('feature_toggles.col_key') }}</th>
                <th class="py-2.5 pr-2 font-medium">{{ t('feature_toggles.col_group') }}</th>
                <th class="py-2.5 pr-2 text-center font-medium">{{ t('feature_toggles.col_show') }}</th>
                <th class="py-2.5 pr-2 text-center font-medium">{{ t('feature_toggles.col_maint') }}</th>
                <th class="py-2.5 pr-2 text-center font-medium">{{ t('feature_toggles.col_upgrade') }}</th>
                <th class="py-2.5 pr-3 text-right font-medium">{{ t('feature_toggles.col_actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in filteredItems"
                :key="row.id"
                class="border-b border-slate-100 dark:border-slate-800"
              >
                <td class="py-2.5 pl-3 pr-2 font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</td>
                <td class="py-2.5 pr-2 font-mono text-xs text-slate-600 dark:text-slate-400">{{ row.key }}</td>
                <td class="py-2.5 pr-2 text-slate-600 dark:text-slate-400">{{ row.module ?? '—' }}</td>
                <td class="py-2.5 pr-2 text-center">
                  <input
                    type="checkbox"
                    :checked="row.is_enabled"
                    class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                    :aria-label="t('feature_toggles.col_show')"
                    @change="patchRow(row, { is_enabled: $event.target.checked })"
                  />
                </td>
                <td class="py-2.5 pr-2 text-center">
                  <input
                    type="checkbox"
                    :checked="row.maintenance_mode"
                    class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                    :aria-label="t('feature_toggles.col_maint')"
                    @change="patchRow(row, { maintenance_mode: $event.target.checked })"
                  />
                </td>
                <td class="py-2.5 pr-2 text-center">
                  <input
                    type="checkbox"
                    :checked="row.upgrade_notice"
                    class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                    :aria-label="t('feature_toggles.col_upgrade')"
                    @change="patchRow(row, { upgrade_notice: $event.target.checked })"
                  />
                </td>
                <td class="py-2.5 pr-3 text-right">
                  <Button
                    variant="secondary"
                    class="text-red-600 dark:text-red-400"
                    @click="confirmRemove(row)"
                  >
                    {{ t('feature_toggles.delete') }}
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile / tablet cards -->
        <div class="mt-3 space-y-3 md:hidden">
          <div
            v-for="row in filteredItems"
            :key="'m' + row.id"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"
          >
            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.name }}</div>
            <div class="mt-1 font-mono text-xs text-slate-500">{{ row.key }}</div>
            <div class="mt-3 grid gap-3 border-t border-slate-100 pt-3 dark:border-slate-700">
              <label class="flex items-center justify-between gap-2 text-sm">
                <span>{{ t('feature_toggles.col_show') }}</span>
                <input
                  type="checkbox"
                  :checked="row.is_enabled"
                  class="h-4 w-4 rounded border-slate-300 text-teal-700"
                  @change="patchRow(row, { is_enabled: $event.target.checked })"
                />
              </label>
              <label class="flex items-center justify-between gap-2 text-sm">
                <span>{{ t('feature_toggles.col_maint') }}</span>
                <input
                  type="checkbox"
                  :checked="row.maintenance_mode"
                  class="h-4 w-4 rounded border-slate-300 text-amber-600"
                  @change="patchRow(row, { maintenance_mode: $event.target.checked })"
                />
              </label>
              <label class="flex items-center justify-between gap-2 text-sm">
                <span>{{ t('feature_toggles.col_upgrade') }}</span>
                <input
                  type="checkbox"
                  :checked="row.upgrade_notice"
                  class="h-4 w-4 rounded border-slate-300 text-violet-600"
                  @change="patchRow(row, { upgrade_notice: $event.target.checked })"
                />
              </label>
              <Button variant="secondary" class="w-full text-red-600" @click="confirmRemove(row)">
                {{ t('feature_toggles.delete') }}
              </Button>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Card :title="t('feature_toggles.cluster_title')">
      <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        {{ t('feature_toggles.cluster_intro') }}
      </p>
      <div class="space-y-4">
        <section
          v-for="(cl, ci) in navClusters"
          :key="'cl' + ci"
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-slate-50/50 dark:border-slate-700 dark:bg-slate-900/30"
        >
          <h3 class="border-b border-slate-200/80 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-900 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100">
            {{ t(cl.headingKey) }}
          </h3>
          <div class="divide-y divide-slate-200/80 dark:divide-slate-700">
            <div
              v-for="feat in cl.features"
              :key="feat.featureKey + cl.headingKey"
              class="px-3 py-2.5 sm:px-4"
            >
              <div class="font-mono text-[11px] font-medium text-teal-800 dark:text-teal-200/95">
                {{ feat.featureKey }}
              </div>
              <ul class="mt-1.5 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li
                  v-for="(ln, li) in feat.links"
                  :key="li + ln.to"
                  class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
                >
                  <span>{{ t(ln.labelKey) }}</span>
                  <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                  <code class="rounded bg-slate-200/60 px-1 py-px text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ ln.to }}</code>
                </li>
              </ul>
            </div>
          </div>
        </section>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { getFeatureToggleNavClusters } from '../../config/nav'
import { SEED_FEATURE_TOGGLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const auth = useAuthStore()

const navClusters = getFeatureToggleNavClusters()

async function syncSessionFromServer() {
  if (!auth.isLoggedIn) return
  try {
    await auth.fetchMe()
  } catch {
    /* ignore */
  }
}

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const filterQ = ref('')
const form = reactive({ key: '', name: '', module: '', is_enabled: true })
const seedToggles = SEED_FEATURE_TOGGLE_PRESETS
const togglePresetIdx = ref('')

const filteredItems = computed(() => {
  const q = filterQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((r) => {
    const name = String(r.name ?? '').toLowerCase()
    const key = String(r.key ?? '').toLowerCase()
    const mod = String(r.module ?? '').toLowerCase()
    return name.includes(q) || key.includes(q) || mod.includes(q)
  })
})

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
    const list = (await admin.listFeatureToggles()) ?? []
    items.value = list.map((r) => ({
      ...r,
      maintenance_mode: !!r.maintenance_mode,
      upgrade_notice: !!r.upgrade_notice,
    }))
  } catch (e) {
    showAppError(formatApiError(e))
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
      maintenance_mode: false,
      upgrade_notice: false,
    })
    form.key = ''
    form.name = ''
    form.module = ''
    form.is_enabled = true
    togglePresetIdx.value = ''
    await load()
    await syncSessionFromServer()
    showAppSuccess(t('feature_toggles.success_create'), t('feature_toggles.success_title'))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function patchRow(row, partial) {
  try {
    await admin.updateFeatureToggle(row.id, partial)
    Object.assign(row, partial)
    await syncSessionFromServer()
  } catch (e) {
    showAppError(formatApiError(e))
    await load()
  }
}

async function confirmRemove(row) {
  const ok = await confirmAction({
    title: t('feature_toggles.confirm_delete_title'),
    message: t('feature_toggles.confirm_delete_body', { name: row.name, key: row.key }),
    confirmLabel: t('feature_toggles.confirm_delete_ok'),
    danger: true,
  })
  if (!ok) return
  try {
    await admin.deleteFeatureToggle(row.id)
    await load()
    await syncSessionFromServer()
    showAppSuccess(t('feature_toggles.success_delete'), t('feature_toggles.success_title'))
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

onMounted(load)
</script>
