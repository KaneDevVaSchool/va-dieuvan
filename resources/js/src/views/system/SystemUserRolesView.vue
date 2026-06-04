<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import {
  MagnifyingGlassIcon,
  UserGroupIcon,
  CheckIcon,
  ArrowPathIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useUserRoleManager } from '../../composables/useUserRoleManager'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'

const {
  loading, savingIds, savedIds, bulkApplying,
  users, roles, meta, rowRoleId,
  selectedIds, bulkRoleId, bulkAction,
  filters, isLocked,
  allSelected, someSelected, displayFrom, displayTo,
  bootstrap, applyFilters, goPage, onRoleChange,
  toggleSelect, toggleSelectAll, clearSelection, applyBulk,
  bumpSearch,
} = useUserRoleManager()

// Tìm kiếm debounce — khi nhập tự áp filter
watch(() => filters.q, () => bumpSearch())

const ASSIGNMENT_OPTS = [
  { value: 'all',        label: 'Tất cả' },
  { value: 'assigned',   label: 'Đã gán vai trò' },
  { value: 'unassigned', label: 'Chưa gán vai trò' },
]

const PER_PAGE_OPTS = ['10', '25', '50', '100', 'all']

const activeFilters = computed(() => {
  let n = 0
  if (filters.q.trim()) n++
  if (filters.assignment !== 'all') n++
  if (filters.per_page !== '25') n++
  return n
})

function resetFilters() {
  filters.q          = ''
  filters.assignment = 'all'
  filters.per_page   = '25'
  filters.roles      = []
  applyFilters()
}

function userCurrentRoleName(user) {
  const rid = rowRoleId[user.id]
  if (!rid) return null
  return roles.value.find((r) => r.id === rid)?.display_name
    || roles.value.find((r) => r.id === rid)?.name
    || null
}

onMounted(() => bootstrap())
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-8">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-50">Phân vai trò nhân viên</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          Mỗi nhân viên được gán <strong class="font-semibold text-slate-700 dark:text-slate-300">1 vai trò chính</strong>.
          Thay đổi lưu tự động sau 0,5 giây.
        </p>
      </div>
      <div class="shrink-0 text-xs text-slate-400 dark:text-slate-500">
        Tổng: {{ meta.total }} nhân viên
      </div>
    </div>

    <!-- ── Filter bar ─────────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Search -->
      <div class="relative">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input
          v-model="filters.q"
          type="search"
          placeholder="Tìm nhân viên…"
          aria-label="Tìm nhân viên"
          class="h-9 w-44 rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 sm:w-56"
          :disabled="isLocked"
        />
      </div>

      <!-- Tình trạng gán -->
      <select
        v-model="filters.assignment"
        aria-label="Lọc tình trạng gán"
        class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
        :disabled="isLocked"
        @change="applyFilters"
      >
        <option v-for="opt in ASSIGNMENT_OPTS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <!-- Per page -->
      <select
        v-model="filters.per_page"
        aria-label="Số dòng mỗi trang"
        class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
        :disabled="isLocked"
        @change="applyFilters"
      >
        <option value="10">10 / trang</option>
        <option value="25">25 / trang</option>
        <option value="50">50 / trang</option>
        <option value="100">100 / trang</option>
        <option value="all">Tất cả</option>
      </select>

      <!-- Apply button -->
      <Button
        variant="secondary"
        class="h-9 px-4"
        :loading="loading"
        :disabled="isLocked"
        @click="applyFilters"
      >
        Tìm
      </Button>

      <!-- Clear filters -->
      <button
        v-if="activeFilters > 0"
        type="button"
        class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-400"
        @click="resetFilters"
      >
        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        Xóa bộ lọc
      </button>
    </div>

    <!-- ── Bulk action bar ────────────────────────────────────────────────── -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-2 opacity-0"
    >
      <div
        v-if="selectedIds.length > 0"
        class="flex flex-wrap items-center gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 dark:border-teal-800/50 dark:bg-teal-950/30"
      >
        <span class="text-sm font-semibold text-teal-800 dark:text-teal-300">
          Đã chọn {{ selectedIds.length }} nhân viên
        </span>

        <div class="flex flex-wrap items-center gap-2">
          <select
            v-model="bulkAction"
            aria-label="Thao tác bulk"
            class="h-8 rounded-lg border border-teal-200 bg-white px-2.5 text-sm text-slate-700 focus:outline-none dark:border-teal-800/50 dark:bg-slate-900 dark:text-slate-200"
          >
            <option value="assign">Đặt vai trò</option>
            <option value="remove">Gỡ vai trò</option>
          </select>

          <select
            v-model="bulkRoleId"
            aria-label="Vai trò bulk"
            class="h-8 rounded-lg border border-teal-200 bg-white px-2.5 text-sm text-slate-700 focus:outline-none dark:border-teal-800/50 dark:bg-slate-900 dark:text-slate-200"
          >
            <option value="">— Chọn vai trò —</option>
            <option v-for="r in roles" :key="r.id" :value="String(r.id)">
              {{ r.display_name || r.name }}
            </option>
          </select>

          <Button
            class="h-8 px-4 text-sm"
            :loading="bulkApplying"
            :disabled="bulkApplying || (bulkAction === 'assign' && !bulkRoleId)"
            @click="applyBulk"
          >
            Áp dụng
          </Button>
        </div>

        <button
          type="button"
          class="ml-auto rounded-lg p-1 text-teal-500 hover:bg-teal-100 dark:text-teal-400 dark:hover:bg-teal-900/40"
          @click="clearSelection"
        >
          <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </Transition>

    <!-- ── Truncated warning ──────────────────────────────────────────────── -->
    <div
      v-if="meta.truncated"
      class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
    >
      Kết quả bị giới hạn ở {{ meta.cap }} nhân viên. Dùng bộ lọc để thu hẹp tìm kiếm.
    </div>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 8" :key="i" class="h-14 animate-pulse rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800" />
    </div>

    <!-- ── Empty ─────────────────────────────────────────────────────────── -->
    <template v-else-if="!users.length">
      <Card>
        <div class="py-12 text-center">
          <UserGroupIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-400">Không có nhân viên nào khớp.</p>
          <button
            v-if="activeFilters > 0"
            type="button"
            class="mt-2 text-sm text-teal-600 underline hover:text-teal-800 dark:text-teal-400"
            @click="resetFilters"
          >
            Xóa bộ lọc
          </button>
        </div>
      </Card>
    </template>

    <!-- ── Table ─────────────────────────────────────────────────────────── -->
    <template v-else>
      <div class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">

        <!-- Desktop -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full border-collapse text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-500 dark:border-slate-700 dark:bg-slate-800/80">
                <!-- Select all -->
                <th class="w-10 py-2.5 pl-4 pr-2">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    :indeterminate="someSelected"
                    aria-label="Chọn tất cả"
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
                    @change="toggleSelectAll"
                  />
                </th>
                <th class="py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">Nhân viên</th>
                <th class="py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">Email</th>
                <th class="py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">Vai trò chính</th>
                <th class="w-20 py-2.5 pl-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide">Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in users"
                :key="user.id"
                class="border-b border-slate-100 transition-colors hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30"
                :class="{ 'bg-teal-50/20 dark:bg-teal-950/10': selectedIds.includes(user.id) }"
              >
                <!-- Checkbox -->
                <td class="py-3 pl-4 pr-2 align-middle">
                  <input
                    type="checkbox"
                    :checked="selectedIds.includes(user.id)"
                    :aria-label="`Chọn ${user.name}`"
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
                    @change="toggleSelect(user.id)"
                  />
                </td>

                <!-- Name -->
                <td class="py-3 pr-3 align-middle">
                  <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                      {{ (user.name ?? '?')[0].toUpperCase() }}
                    </span>
                    <div class="min-w-0">
                      <p class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</p>
                      <p v-if="user.employee_code" class="text-[11px] text-slate-400">{{ user.employee_code }}</p>
                    </div>
                  </div>
                </td>

                <!-- Email -->
                <td class="py-3 pr-3 align-middle text-sm text-slate-500 dark:text-slate-400">{{ user.email }}</td>

                <!-- Role dropdown -->
                <td class="py-3 pr-3 align-middle">
                  <select
                    v-model="rowRoleId[user.id]"
                    :aria-label="`Vai trò của ${user.name}`"
                    :disabled="isLocked || savingIds.has(user.id)"
                    class="w-full max-w-[14rem] rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-900 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    @change="onRoleChange(user)"
                  >
                    <option :value="null">— Chưa gán —</option>
                    <option v-for="r in roles" :key="r.id" :value="r.id">
                      {{ r.display_name || r.name }}
                    </option>
                  </select>
                </td>

                <!-- Status -->
                <td class="py-3 pl-2 pr-4 text-right align-middle">
                  <span
                    v-if="savingIds.has(user.id)"
                    class="inline-flex items-center gap-1 text-xs text-slate-400"
                  >
                    <ArrowPathIcon class="h-3.5 w-3.5 animate-spin" aria-hidden="true" />
                    Đang lưu
                  </span>
                  <span
                    v-else-if="savedIds.has(user.id)"
                    class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 dark:text-teal-400"
                  >
                    <CheckIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    Đã lưu
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
          <div
            v-for="user in users"
            :key="'m' + user.id"
            class="flex items-start gap-3 bg-white p-4 dark:bg-slate-900/60"
            :class="{ 'bg-teal-50/20 dark:bg-teal-950/10': selectedIds.includes(user.id) }"
          >
            <input
              type="checkbox"
              :checked="selectedIds.includes(user.id)"
              :aria-label="`Chọn ${user.name}`"
              class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600"
              @change="toggleSelect(user.id)"
            />
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ user.email }}</p>
              <div class="mt-3 flex items-center gap-2">
                <select
                  v-model="rowRoleId[user.id]"
                  :aria-label="`Vai trò của ${user.name}`"
                  :disabled="isLocked || savingIds.has(user.id)"
                  class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-900 focus:border-teal-400 focus:outline-none disabled:opacity-60 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  @change="onRoleChange(user)"
                >
                  <option :value="null">— Chưa gán —</option>
                  <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name || r.name }}</option>
                </select>
                <span v-if="savingIds.has(user.id)" class="text-xs text-slate-400">
                  <ArrowPathIcon class="h-4 w-4 animate-spin" aria-hidden="true" />
                </span>
                <span v-else-if="savedIds.has(user.id)" class="text-xs font-medium text-teal-600 dark:text-teal-400">
                  <CheckIcon class="h-4 w-4" aria-hidden="true" />
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Footer + Pagination ────────────────────────────────────────── -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500 dark:text-slate-400">
          <template v-if="meta.per_page_mode === 'paged'">
            Hiển thị {{ displayFrom }}–{{ displayTo }} trong {{ meta.total }} nhân viên
          </template>
          <template v-else>
            Đang hiển thị {{ users.length }} / {{ meta.total }} nhân viên
          </template>
        </p>

        <div v-if="meta.per_page_mode === 'paged' && meta.last_page > 1" class="flex items-center gap-2">
          <Button
            variant="secondary"
            class="h-8 px-3 text-sm"
            :disabled="meta.current_page <= 1 || isLocked"
            @click="goPage(meta.current_page - 1)"
          >
            ← Trước
          </Button>
          <span class="text-sm text-slate-600 dark:text-slate-400">
            {{ meta.current_page }} / {{ meta.last_page }}
          </span>
          <Button
            variant="secondary"
            class="h-8 px-3 text-sm"
            :disabled="meta.current_page >= meta.last_page || isLocked"
            @click="goPage(meta.current_page + 1)"
          >
            Sau →
          </Button>
        </div>
      </div>
    </template>

  </div>
</template>
