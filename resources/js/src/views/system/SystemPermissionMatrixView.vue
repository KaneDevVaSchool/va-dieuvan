<script setup>
import { computed, onMounted, ref } from 'vue'
import { BUSINESS_CAPABILITY_GROUPS } from '../../config/businessCapabilities.js'
import { JOB_ROLE_TEMPLATES } from '../../config/jobRoleTemplates.js'
import { getPermissionMatrix, syncPermissionMatrix } from '../../api/system'
import { showAppError, showAppSuccess } from '../../composables/appMessage'

const loading = ref(true)
const saving  = ref(false)
const dirty   = ref(false)

const roles    = ref([])   // [{ id, name, display_name }]
const permMap  = ref({})   // permName → permId (from API)
const matrix   = ref({})   // roleId → Set<permId>
const original = ref({})   // deep-copy of matrix at load time

// ─── Build a permName→id lookup from the API modules data ────────────────────

function buildPermMap(apiModules) {
  const map = {}
  for (const mod of apiModules) {
    for (const p of mod.permissions ?? []) {
      map[p.name] = p.id
    }
  }
  return map
}

// ─── Derived: role icon helper ────────────────────────────────────────────────

function getRoleIcon(role) {
  const tpl = JOB_ROLE_TEMPLATES.find((t) => t.id === role.name || role.name?.includes(t.id))
  return tpl?.icon ?? '🎭'
}

// ─── Matrix helpers ───────────────────────────────────────────────────────────

function hasPerm(roleId, permName) {
  const permId = permMap.value[permName]
  if (permId === undefined) return false
  return (matrix.value[String(roleId)] ?? new Set()).has(permId)
}

function toggle(roleId, permName) {
  const permId = permMap.value[permName]
  if (permId === undefined) return
  const key = String(roleId)
  const s = new Set(matrix.value[key] ?? [])
  if (s.has(permId)) s.delete(permId)
  else s.add(permId)
  matrix.value[key] = s
  dirty.value = true
}

/** Count how many capabilities in a group a role has enabled */
function groupCount(roleId, group) {
  return group.capabilities.filter((c) => hasPerm(roleId, c.perm)).length
}

/** Toggle an entire capability group for a role */
function toggleGroup(roleId, group) {
  const allOn = group.capabilities.every((c) => hasPerm(roleId, c.perm))
  for (const c of group.capabilities) {
    const permId = permMap.value[c.perm]
    if (permId === undefined) continue
    const key = String(roleId)
    const s = new Set(matrix.value[key] ?? [])
    if (allOn) s.delete(permId)
    else s.add(permId)
    matrix.value[key] = s
  }
  dirty.value = true
}

/** Build delta changes for API */
function buildChanges() {
  const changes = []
  for (const role of roles.value) {
    const rid  = String(role.id)
    const before = original.value[rid] ?? new Set()
    const after  = matrix.value[rid]  ?? new Set()
    const grant  = [...after].filter((id) => !before.has(id))
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
    showAppSuccess('Đã lưu ma trận phân quyền.')
    // Deep-copy current state as new baseline
    original.value = Object.fromEntries(
      Object.entries(matrix.value).map(([k, v]) => [k, new Set(v)]),
    )
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
    roles.value = data.roles ?? []
    permMap.value = buildPermMap(data.modules ?? [])
    // Convert array-based matrix → Set-based
    matrix.value = Object.fromEntries(
      Object.entries(data.matrix ?? {}).map(([k, v]) => [k, new Set(v)]),
    )
    original.value = Object.fromEntries(
      Object.entries(matrix.value).map(([k, v]) => [k, new Set(v)]),
    )
  } catch (e) {
    showAppError(e)
  } finally {
    loading.value = false
  }
})

/** Total permissions granted across all roles (for the summary strip) */
const totalGranted = computed(() => {
  let n = 0
  for (const s of Object.values(matrix.value)) n += s.size
  return n
})
</script>

<template>
  <div class="mx-auto max-w-[1400px] space-y-5 pb-10">

    <!-- ── Page header ──────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-50">Ma trận phân quyền</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Xem và điều chỉnh nhanh quyền của nhiều vai trò cùng lúc.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
        :disabled="saving || !dirty"
        @click="save"
      >
        <span
          v-if="saving"
          class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
        />
        Lưu thay đổi
        <span v-if="dirty" class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">Chưa lưu</span>
      </button>
    </div>

    <!-- ── Loading ──────────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-3">
      <div class="h-16 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      <div class="h-96 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
    </div>

    <template v-else-if="roles.length">

      <!-- ── Stats strip ──────────────────────────────────────────────── -->
      <div class="flex flex-wrap gap-3">
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Vai trò</p>
          <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-slate-50">{{ roles.length }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Nhóm quyền</p>
          <p class="text-lg font-bold text-slate-900 tabular-nums dark:text-slate-50">{{ BUSINESS_CAPABILITY_GROUPS.length }}</p>
        </div>
        <div class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 dark:border-teal-800/50 dark:bg-teal-950/30">
          <p class="text-[11px] font-medium text-teal-600 dark:text-teal-400">Tổng quyền đã bật</p>
          <p class="text-lg font-bold text-teal-800 tabular-nums dark:text-teal-200">{{ totalGranted }}</p>
        </div>
        <div
          v-if="dirty"
          class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 dark:border-amber-800/50 dark:bg-amber-950/30"
        >
          <p class="text-[11px] font-medium text-amber-600 dark:text-amber-400">Có thay đổi chưa lưu</p>
          <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Nhấn "Lưu thay đổi" để áp dụng</p>
        </div>
      </div>

      <!-- ── Matrix table ─────────────────────────────────────────────── -->
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
          <table class="w-full min-w-max border-collapse text-sm">

            <!-- Thead: role columns -->
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/90 dark:border-slate-700 dark:bg-slate-800/90">
                <!-- Sticky row-header column -->
                <th class="sticky left-0 z-20 min-w-[220px] bg-slate-50/95 px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/95 dark:text-slate-400">
                  Nhóm chức năng
                </th>

                <!-- Role columns -->
                <th
                  v-for="role in roles"
                  :key="role.id"
                  class="min-w-[130px] px-3 py-4 text-center"
                >
                  <div class="flex flex-col items-center gap-1">
                    <span class="text-xl leading-none">{{ getRoleIcon(role) }}</span>
                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                      {{ role.display_name || role.name }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>

            <!-- Tbody: one group of rows per business capability group -->
            <tbody>
              <template v-for="group in BUSINESS_CAPABILITY_GROUPS" :key="group.id">

                <!-- Group header row -->
                <tr class="border-b border-slate-100 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-800/30">
                  <td class="sticky left-0 z-10 bg-slate-50/95 px-5 py-2.5 dark:bg-slate-800/90">
                    <div class="flex items-center gap-2">
                      <span class="text-base leading-none">{{ group.icon }}</span>
                      <span class="font-semibold text-slate-800 dark:text-slate-200">{{ group.title }}</span>
                      <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ group.capabilities.length }} quyền</span>
                    </div>
                  </td>

                  <!-- Group-level toggle per role (all-or-nothing) -->
                  <td
                    v-for="role in roles"
                    :key="`grp-${group.id}-${role.id}`"
                    class="px-3 py-2.5 text-center"
                  >
                    <div class="flex flex-col items-center gap-1">
                      <button
                        type="button"
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border-2 transition"
                        :class="groupCount(role.id, group) === group.capabilities.length
                          ? 'border-teal-500 bg-teal-500'
                          : groupCount(role.id, group) > 0
                            ? 'border-teal-300 bg-teal-100 dark:border-teal-700 dark:bg-teal-900/50'
                            : 'border-slate-300 bg-slate-200 dark:border-slate-600 dark:bg-slate-700'"
                        :disabled="saving"
                        @click="toggleGroup(role.id, group)"
                      >
                        <span
                          class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition"
                          :class="groupCount(role.id, group) === group.capabilities.length ? 'translate-x-4' : 'translate-x-0.5'"
                        />
                      </button>
                      <span class="text-[10px] tabular-nums text-slate-400 dark:text-slate-500">
                        {{ groupCount(role.id, group) }}/{{ group.capabilities.length }}
                      </span>
                    </div>
                  </td>
                </tr>

                <!-- Individual capability rows -->
                <tr
                  v-for="cap in group.capabilities"
                  :key="`${group.id}-${cap.perm}`"
                  class="border-b border-slate-100/80 transition-colors hover:bg-slate-50/60 last:border-0 dark:border-slate-800/60 dark:hover:bg-slate-800/20"
                >
                  <!-- Row label -->
                  <td class="sticky left-0 z-10 bg-white px-5 py-2.5 pl-10 dark:bg-slate-900">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ cap.label }}</span>
                  </td>

                  <!-- Checkbox per role -->
                  <td
                    v-for="role in roles"
                    :key="`${role.id}-${cap.perm}`"
                    class="px-3 py-2.5 text-center"
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-600 transition focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-800"
                      :checked="hasPerm(role.id, cap.perm)"
                      :disabled="saving"
                      @change="toggle(role.id, cap.perm)"
                    />
                  </td>
                </tr>

              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Save sticky footer (visible when dirty) -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition ease-in duration-150"
        leave-to-class="translate-y-4 opacity-0"
      >
        <div
          v-if="dirty"
          class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-2xl border border-amber-300 bg-amber-50 px-6 py-3 shadow-xl shadow-amber-500/10 dark:border-amber-800/60 dark:bg-amber-950/90"
        >
          <div class="flex items-center gap-4">
            <p class="text-sm font-medium text-amber-900 dark:text-amber-200">Có thay đổi chưa được lưu</p>
            <button
              type="button"
              class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50"
              :disabled="saving"
              @click="save"
            >
              Lưu ngay
            </button>
          </div>
        </div>
      </Transition>

    </template>

    <!-- Empty state -->
    <div
      v-else-if="!loading"
      class="rounded-2xl border border-dashed border-slate-200 py-16 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
    >
      Không có dữ liệu ma trận.
    </div>

  </div>
</template>
