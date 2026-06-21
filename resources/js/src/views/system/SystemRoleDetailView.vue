<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  PencilSquareIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  DocumentDuplicateIcon,
  CheckCircleIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { BUSINESS_CAPABILITY_GROUPS, getColorClasses, computeGroupStats } from '../../config/businessCapabilities.js'
import { JOB_ROLE_TEMPLATES } from '../../config/jobRoleTemplates.js'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError } from '../../composables/appMessage'

const route  = useRoute()
const router = useRouter()

const roleId = computed(() => Number(route.params.id))

// ─── State ────────────────────────────────────────────────────────────────────

const loading    = ref(true)
const role       = ref(null)
const members    = ref([])
const activeTab  = ref('capabilities') // 'capabilities' | 'members'

// ─── Derived ─────────────────────────────────────────────────────────────────

const selectedPermNames = computed(() => {
  if (!role.value) return new Set()
  return new Set((role.value.permissions ?? []).map((p) => p.name))
})

const groupStats = computed(() => computeGroupStats(selectedPermNames.value))

const matchedTemplate = computed(() => {
  if (!role.value) return null
  return JOB_ROLE_TEMPLATES.find((t) => t.id === role.value.name || role.value.name?.includes(t.id)) ?? null
})

const roleIcon   = computed(() => matchedTemplate.value?.icon ?? '🎭')
const colorKey   = computed(() => matchedTemplate.value?.colorKey ?? 'slate')

const activeGroups   = computed(() => BUSINESS_CAPABILITY_GROUPS.filter((g) => groupStats.value.get(g.id)?.selected > 0))
const inactiveGroups = computed(() => BUSINESS_CAPABILITY_GROUPS.filter((g) => !groupStats.value.get(g.id)?.selected))

// ─── Load ─────────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true
  try {
    const detail = await admin.getRole(roleId.value)
    role.value = detail

    const roleName = role.value?.name
    const userList =
      roleName != null && roleName !== ''
        ? await admin
            .listUsers({ roles: [roleName], per_page: '100' })
            .catch(() => ({ items: [] }))
        : { items: [] }
    members.value = Array.isArray(userList) ? userList : (userList?.items ?? [])
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-5 pb-12">

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-4">
      <div class="h-32 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      <div class="h-96 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
    </div>

    <template v-else-if="role">

      <!-- ── Role hero card ────────────────────────────────────────────────── -->
      <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <!-- Color accent bar -->
        <div
          class="h-1.5 w-full rounded-t-2xl"
          :class="{
            'bg-blue-400':   colorKey === 'blue',
            'bg-teal-400':   colorKey === 'teal',
            'bg-amber-400':  colorKey === 'amber',
            'bg-green-400':  colorKey === 'green',
            'bg-indigo-400': colorKey === 'indigo',
            'bg-purple-400': colorKey === 'purple',
            'bg-violet-400': colorKey === 'violet',
            'bg-rose-400':   colorKey === 'rose',
            'bg-slate-300':  colorKey === 'slate',
          }"
        />
        <div class="p-6">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <!-- Back + icon -->
            <div class="flex items-start gap-3">
              <button
                type="button"
                class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                @click="router.push({ name: 'systemRoles' })"
              >
                <ArrowLeftIcon class="h-4 w-4" />
              </button>
              <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-3xl dark:bg-slate-800">
                {{ roleIcon }}
              </span>
            </div>

            <!-- Identity -->
            <div class="flex-1 min-w-0">
              <h1 class="text-xl font-bold text-slate-900 dark:text-slate-50">
                {{ role.display_name || role.name }}
              </h1>
              <p v-if="role.description" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ role.description }}</p>
              <p v-else class="mt-1 text-xs italic text-slate-400 dark:text-slate-600">Chưa có mô tả</p>

              <!-- Stats pills -->
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  <UserGroupIcon class="h-3.5 w-3.5 text-slate-500" />
                  {{ role.users_count ?? members.length }} nhân viên đang dùng
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 dark:bg-teal-950/40 dark:text-teal-300">
                  <ShieldCheckIcon class="h-3.5 w-3.5" />
                  {{ selectedPermNames.size }} quyền được cấp
                </span>
                <span
                  v-if="matchedTemplate"
                  class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950/40 dark:text-violet-300"
                >
                  {{ matchedTemplate.icon }} Mẫu: {{ matchedTemplate.title }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex shrink-0 gap-2 sm:ml-auto">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="router.push({ name: 'systemRoleNew', query: { cloneFrom: role.id } })"
              >
                <DocumentDuplicateIcon class="h-4 w-4" />
                Nhân bản
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-teal-700"
                @click="router.push({ name: 'systemRoleEdit', params: { id: role.id } })"
              >
                <PencilSquareIcon class="h-4 w-4" />
                Chỉnh sửa
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tabs ────────────────────────────────────────────────────────── -->
      <div class="flex gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-800/50">
        <button
          v-for="tab in [{ id: 'capabilities', label: 'Nhóm quyền' }, { id: 'members', label: `Nhân viên (${members.length})` }]"
          :key="tab.id"
          type="button"
          class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
          :class="activeTab === tab.id
            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-slate-50'
            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- ── Tab: Capabilities ──────────────────────────────────────────── -->
      <div v-if="activeTab === 'capabilities'" class="space-y-3">

        <!-- Active groups -->
        <template v-if="activeGroups.length">
          <h3 class="px-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Nhóm quyền đang bật
          </h3>
          <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
          <div
            v-for="group in activeGroups"
            :key="group.id"
            class="rounded-2xl border p-5"
            :class="`${getColorClasses(group.colorKey).bg} ${getColorClasses(group.colorKey).border}`"
          >
            <!-- Group header -->
            <div class="mb-3 flex items-center gap-2.5">
              <span class="text-xl leading-none">{{ group.icon }}</span>
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-50">{{ group.title }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ group.description }}</p>
              </div>
              <span
                class="ml-auto rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="getColorClasses(group.colorKey).badge"
              >
                {{ groupStats.get(group.id)?.selected }}/{{ group.capabilities.length }}
              </span>
            </div>

            <!-- Capability rows -->
            <div class="grid gap-1.5 sm:grid-cols-2">
              <div
                v-for="cap in group.capabilities"
                :key="cap.perm"
                class="flex items-center gap-2 rounded-lg px-3 py-1.5"
                :class="selectedPermNames.has(cap.perm)
                  ? 'bg-white/70 dark:bg-slate-900/30'
                  : 'opacity-40'"
              >
                <CheckCircleIcon
                  v-if="selectedPermNames.has(cap.perm)"
                  class="h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400"
                />
                <XCircleIcon
                  v-else
                  class="h-4 w-4 shrink-0 text-slate-400"
                />
                <span class="text-sm text-slate-800 dark:text-slate-200">{{ cap.label }}</span>
              </div>
            </div>
          </div>
          </div>
        </template>

        <!-- Inactive groups (collapsed summary) -->
        <template v-if="inactiveGroups.length">
          <h3 class="mt-2 px-1 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
            Nhóm quyền chưa bật
          </h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="group in inactiveGroups"
              :key="group.id"
              class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500"
            >
              <span>{{ group.icon }}</span>
              {{ group.title }}
            </span>
          </div>
        </template>
      </div>

      <!-- ── Tab: Members ──────────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'members'">
        <div
          v-if="!members.length"
          class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
        >
          Chưa có nhân viên nào được gán vai trò này.
        </div>
        <div v-else class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nhân viên</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="member in members"
                :key="member.id"
                class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 last:border-0 dark:border-slate-800 dark:hover:bg-slate-800/40"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-blue-500 text-xs font-bold text-white">
                      {{ (member.name ?? member.email ?? '?').slice(0, 1).toUpperCase() }}
                    </span>
                    <span class="font-medium text-slate-900 dark:text-slate-50">{{ member.name ?? '—' }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ member.email ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>
  </div>
</template>
