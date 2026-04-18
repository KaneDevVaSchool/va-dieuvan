<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-6 sm:space-y-6 sm:pb-8">
    <header class="border-l-4 border-[color:var(--va-brand)] pl-4">
      <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-xl">
        {{ t('feature_toggles.page_title') }}
      </h1>
      <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('feature_toggles.page_subtitle') }}
      </p>
    </header>

    <!-- Thêm công tắc -->
    <Card :title="t('feature_toggles.section_add')">
      <p class="mb-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('feature_toggles.section_add_hint') }}
      </p>
      <form
        class="grid gap-4 border-b border-slate-200/90 pb-5 dark:border-slate-700 sm:grid-cols-2 lg:grid-cols-6"
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
        <div class="flex flex-col justify-end gap-3 sm:flex-row sm:items-end">
          <label
            class="flex min-h-[2.5rem] cursor-pointer items-center gap-2 rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-300"
          >
            <input
              v-model="form.is_enabled"
              type="checkbox"
              class="rounded border-slate-300 text-teal-700 focus:ring-teal-600 dark:border-slate-500"
            />
            {{ t('feature_toggles.default_on') }}
          </label>
          <Button type="submit" class="w-full shrink-0 sm:w-auto" :loading="saving">
            {{ t('feature_toggles.add_btn') }}
          </Button>
        </div>
      </form>
    </Card>

    <!-- Danh sách + lọc -->
    <Card :title="t('feature_toggles.section_list')">
      <AppFilterBar class="mb-5">
        <div
          class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
        >
          {{ t('feature_toggles.filter_bar_title') }}
        </div>
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
          <div class="min-w-0 flex-1">
            <Input
              v-model="filterQ"
              :label="t('feature_toggles.filter_label')"
              :placeholder="t('feature_toggles.filter_ph')"
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
            {{ t('feature_toggles.clear_filter') }}
          </Button>
        </div>
        <p v-if="!loading" class="mt-3 text-xs text-slate-500 dark:text-slate-400">
          {{ t('feature_toggles.count_hint', { n: filteredItems.length, total: items.length }) }}
        </p>
      </AppFilterBar>

      <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-500 dark:text-slate-400">
        <span
          class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600 dark:border-slate-600 dark:border-t-teal-400"
          aria-hidden="true"
        />
        {{ t('feature_toggles.loading') }}
      </div>

      <template v-else-if="!items.length">
        <div
          class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 py-12 text-center dark:border-slate-700 dark:bg-slate-900/30"
        >
          <MagnifyingGlassIcon class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true" />
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('feature_toggles.empty_list') }}</p>
        </div>
      </template>

      <template v-else>
        <div
          v-if="filteredItems.length === 0"
          class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200/90"
        >
          {{ t('feature_toggles.empty_filtered') }}
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <!-- Desktop -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[52rem] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                  <th class="whitespace-nowrap py-3 pl-4 pr-3 font-semibold">{{ t('feature_toggles.col_name') }}</th>
                  <th class="whitespace-nowrap py-3 pr-3 font-semibold">{{ t('feature_toggles.col_key') }}</th>
                  <th class="whitespace-nowrap py-3 pr-3 font-semibold">{{ t('feature_toggles.col_group') }}</th>
                  <th class="w-24 whitespace-nowrap py-3 px-2 text-center font-semibold">{{ t('feature_toggles.col_show') }}</th>
                  <th class="w-28 whitespace-nowrap py-3 px-2 text-center font-semibold">{{ t('feature_toggles.col_maint_short') }}</th>
                  <th class="w-28 whitespace-nowrap py-3 px-2 text-center font-semibold">{{ t('feature_toggles.col_upgrade_short') }}</th>
                  <th class="whitespace-nowrap py-3 pl-2 pr-4 text-right font-semibold">{{ t('feature_toggles.col_actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, ri) in filteredItems"
                  :key="row.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="ri % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <td class="py-3 pl-4 pr-3 align-middle font-medium text-slate-900 dark:text-slate-100">
                    {{ row.name }}
                  </td>
                  <td class="max-w-[14rem] py-3 pr-3 align-middle font-mono text-xs text-slate-600 dark:text-slate-400">
                    <span class="break-all">{{ row.key }}</span>
                  </td>
                  <td class="py-3 pr-3 align-middle text-slate-600 dark:text-slate-400">{{ row.module ?? '—' }}</td>
                  <td class="py-3 px-2 text-center align-middle">
                    <input
                      type="checkbox"
                      :checked="row.is_enabled"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                      :aria-label="t('feature_toggles.col_show')"
                      @change="patchRow(row, { is_enabled: $event.target.checked })"
                    />
                  </td>
                  <td class="py-3 px-2 text-center align-middle">
                    <input
                      type="checkbox"
                      :checked="row.maintenance_mode"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                      :aria-label="t('feature_toggles.col_maint')"
                      @change="patchRow(row, { maintenance_mode: $event.target.checked })"
                    />
                  </td>
                  <td class="py-3 px-2 text-center align-middle">
                    <input
                      type="checkbox"
                      :checked="row.upgrade_notice"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                      :aria-label="t('feature_toggles.col_upgrade')"
                      @change="patchRow(row, { upgrade_notice: $event.target.checked })"
                    />
                  </td>
                  <td class="py-3 pl-2 pr-4 text-right align-middle">
                    <Button
                      variant="secondary"
                      class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                      @click="confirmRemove(row)"
                    >
                      {{ t('feature_toggles.delete') }}
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile -->
          <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            <div
              v-for="row in filteredItems"
              :key="'m' + row.id"
              class="bg-white p-4 dark:bg-slate-900/60"
            >
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.name }}</div>
              <div class="mt-1 break-all font-mono text-xs text-slate-500">{{ row.key }}</div>
              <div v-if="row.module" class="mt-1 text-xs text-slate-500">{{ row.module }}</div>
              <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <label class="flex items-center justify-between gap-2 text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ t('feature_toggles.col_show') }}</span>
                  <input
                    type="checkbox"
                    :checked="row.is_enabled"
                    class="h-4 w-4 rounded border-slate-300 text-teal-700"
                    @change="patchRow(row, { is_enabled: $event.target.checked })"
                  />
                </label>
                <label class="flex items-center justify-between gap-2 text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ t('feature_toggles.col_maint') }}</span>
                  <input
                    type="checkbox"
                    :checked="row.maintenance_mode"
                    class="h-4 w-4 rounded border-slate-300 text-amber-600"
                    @change="patchRow(row, { maintenance_mode: $event.target.checked })"
                  />
                </label>
                <label class="flex items-center justify-between gap-2 text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ t('feature_toggles.col_upgrade') }}</span>
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
        </div>
      </template>
    </Card>

    <!-- Tham chiếu menu -->
    <Card :title="t('feature_toggles.cluster_title')">
      <p class="mb-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('feature_toggles.cluster_intro') }}
      </p>
      <div class="grid gap-3 sm:gap-4 md:grid-cols-2">
        <details
          v-for="(cl, ci) in navClusters"
          :key="'cl' + ci"
          class="group overflow-hidden rounded-xl border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-900/50"
        >
          <summary
            class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-900 outline-none ring-inset ring-teal-600/0 transition hover:bg-slate-50 focus-visible:ring-2 dark:text-slate-100 dark:hover:bg-slate-800/80 [&::-webkit-details-marker]:hidden"
          >
            <span class="flex items-center justify-between gap-2">
              {{ t(cl.headingKey) }}
              <ChevronDownIcon
                class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                aria-hidden="true"
              />
            </span>
          </summary>
          <div class="border-t border-slate-100 dark:border-slate-800">
            <div
              v-for="feat in cl.features"
              :key="feat.featureKey + cl.headingKey"
              class="border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-slate-800"
            >
              <div class="font-mono text-xs font-semibold text-teal-800 dark:text-teal-300">{{ feat.featureKey }}</div>
              <ul class="mt-2 space-y-1.5 text-sm text-slate-600 dark:text-slate-400">
                <li
                  v-for="(ln, li) in feat.links"
                  :key="li + ln.to"
                  class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
                >
                  <span>{{ t(ln.labelKey) }}</span>
                  <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                  <code
                    class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                    >{{ ln.to }}</code
                  >
                </li>
              </ul>
            </div>
          </div>
        </details>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
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
    showAppSuccess(t('feature_toggles.success_create'), t('feature_toggles.toast_success_title'))
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
    showAppSuccess(t('feature_toggles.success_delete'), t('feature_toggles.toast_success_title'))
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

onMounted(load)
</script>
