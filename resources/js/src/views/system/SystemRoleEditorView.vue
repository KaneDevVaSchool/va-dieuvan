<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  CheckIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { JOB_ROLE_TEMPLATES, TEMPLATE_COLOR_CLASSES } from '../../config/jobRoleTemplates.js'
import { BUSINESS_CAPABILITY_GROUPS, getColorClasses } from '../../config/businessCapabilities.js'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'

const route  = useRoute()
const router = useRouter()

// ─── Props derived from route ─────────────────────────────────────────────────

const roleId    = computed(() => route.params.id ? Number(route.params.id) : null)
const cloneFrom = computed(() => route.query.cloneFrom ? Number(route.query.cloneFrom) : null)
const isCreate  = computed(() => !roleId.value)

// ─── State ────────────────────────────────────────────────────────────────────

const loading        = ref(true)
const saving         = ref(false)
const allPerms       = ref([])     // { id, name, display_name, plain_description }[]
const selectedTemplate = ref(null) // template id string or null

/** Internal: set of selected permission *names* (human-readable layer) */
const selectedPermNames = ref(new Set())

/** form fields */
const form = reactive({ name: '', display_name: '', description: '' })

/** Accordion open state per capability group */
const openGroups = ref(new Set())

/** step: 'template' | 'permissions' — only shown in create mode */
const step = ref('template')

// ─── Derived ─────────────────────────────────────────────────────────────────

/** name → id lookup for the full permissions list */
const permNameToId = computed(() => {
  const m = new Map()
  for (const p of allPerms.value) m.set(p.name, p.id)
  return m
})

/** Selected permission IDs for API payload */
const selectedPermIds = computed(() => {
  const ids = []
  for (const name of selectedPermNames.value) {
    const id = permNameToId.value.get(name)
    if (id !== undefined) ids.push(id)
  }
  return ids
})

/** Number of selected capabilities per group */
function groupSelectedCount(group) {
  return group.capabilities.filter((c) => selectedPermNames.value.has(c.perm)).length
}

const totalSelected = computed(() => selectedPermNames.value.size)

/** name validation — only in create mode */
const nameError = computed(() => {
  if (!isCreate.value) return ''
  const v = form.name.trim()
  if (!v) return 'Mã vai trò là bắt buộc.'
  if (!/^[a-z][a-z0-9_]*$/.test(v)) return 'Chỉ dùng chữ thường, số và dấu gạch dưới, bắt đầu bằng chữ cái.'
  return ''
})

const canSubmit = computed(() => !nameError.value && !saving.value && !loading.value)

const pageTitle = computed(() => isCreate.value ? 'Tạo vai trò mới' : `Chỉnh sửa: ${form.display_name || form.name}`)

// ─── Load ─────────────────────────────────────────────────────────────────────

async function loadAll() {
  loading.value = true
  try {
    const [perms, ...rest] = await Promise.all([
      admin.listPermissions(),
      // edit mode: fetch role detail; clone mode: fetch source role
      (roleId.value || cloneFrom.value)
        ? admin.getRole(roleId.value ?? cloneFrom.value)
        : Promise.resolve(null),
    ])
    allPerms.value = perms ?? []
    const roleData = rest[0]

    if (roleData) {
      if (isCreate.value) {
        // clone mode — copy permissions only
        const names = new Set((roleData.permissions ?? []).map((p) => p.name))
        selectedPermNames.value = names
        step.value = 'permissions'
      } else {
        // edit mode
        form.name         = roleData.name         ?? ''
        form.display_name = roleData.display_name ?? ''
        form.description  = roleData.description  ?? ''
        const names = new Set((roleData.permissions ?? []).map((p) => p.name))
        selectedPermNames.value = names
        // auto-open groups that have at least one selection
        for (const g of BUSINESS_CAPABILITY_GROUPS) {
          if (g.capabilities.some((c) => names.has(c.perm))) openGroups.value.add(g.id)
        }
      }
    }
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

// ─── Template selection ───────────────────────────────────────────────────────

function applyTemplate(tpl) {
  selectedTemplate.value = tpl.id
  selectedPermNames.value = new Set(tpl.permissions)
  // Pre-populate display_name from template title if empty
  if (!form.display_name) form.display_name = tpl.id !== 'custom' ? tpl.title : ''
  if (!form.name && tpl.id !== 'custom') form.name = tpl.id
}

function advanceToPermissions() {
  step.value = 'permissions'
  // Open all groups that have at least one selected perm
  for (const g of BUSINESS_CAPABILITY_GROUPS) {
    if (g.capabilities.some((c) => selectedPermNames.value.has(c.perm))) {
      openGroups.value.add(g.id)
    }
  }
}

// ─── Permission toggles ───────────────────────────────────────────────────────

function togglePerm(permName) {
  const s = new Set(selectedPermNames.value)
  if (s.has(permName)) s.delete(permName)
  else s.add(permName)
  selectedPermNames.value = s
}

function toggleGroup(group) {
  const allSelected = group.capabilities.every((c) => selectedPermNames.value.has(c.perm))
  const s = new Set(selectedPermNames.value)
  if (allSelected) {
    group.capabilities.forEach((c) => s.delete(c.perm))
  } else {
    group.capabilities.forEach((c) => s.add(c.perm))
  }
  selectedPermNames.value = s
}

function toggleGroupOpen(groupId) {
  const s = new Set(openGroups.value)
  if (s.has(groupId)) s.delete(groupId)
  else s.add(groupId)
  openGroups.value = s
}

function expandAll() {
  openGroups.value = new Set(BUSINESS_CAPABILITY_GROUPS.map((g) => g.id))
}

function collapseAll() {
  openGroups.value = new Set()
}

// ─── Submit ───────────────────────────────────────────────────────────────────

async function submit() {
  if (!canSubmit.value) return
  saving.value = true
  try {
    const payload = {
      display_name:   form.display_name.trim() || null,
      description:    form.description.trim()  || null,
      permission_ids: selectedPermIds.value,
    }
    if (isCreate.value) {
      payload.name = form.name.trim()
      await admin.createRole(payload)
      showAppSuccess('Đã tạo vai trò mới.', 'Thành công')
    } else {
      await admin.updateRole(roleId.value, payload)
      showAppSuccess('Đã lưu thay đổi.', 'Thành công')
    }
    router.push({ name: 'systemRoles' })
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-0 pb-12">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="mb-6 flex items-center gap-3">
      <button
        type="button"
        class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        @click="router.push({ name: 'systemRoles' })"
      >
        <ArrowLeftIcon class="h-5 w-5" />
      </button>
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-50">{{ pageTitle }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
          {{ isCreate ? 'Chọn mẫu công việc, sau đó tinh chỉnh quyền theo nhu cầu.' : 'Điều chỉnh thông tin và nhóm quyền cho vai trò này.' }}
        </p>
      </div>
    </div>

    <!-- ── Loading shimmer ────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-4">
      <div class="h-48 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      <div class="h-64 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
    </div>

    <template v-else>

      <!-- ════════════════════════════════════════════════════════════════════
           STEP 1: TEMPLATE PICKER (create mode only)
           ════════════════════════════════════════════════════════════════════ -->
      <template v-if="isCreate && step === 'template'">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-slate-50">Chọn mẫu công việc</h2>
          <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
            Mỗi mẫu đã cài sẵn các quyền phù hợp — bạn có thể tinh chỉnh ở bước tiếp theo.
          </p>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button
              v-for="tpl in JOB_ROLE_TEMPLATES"
              :key="tpl.id"
              type="button"
              class="relative flex flex-col gap-2 rounded-xl border-2 p-4 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
              :class="selectedTemplate === tpl.id
                ? TEMPLATE_COLOR_CLASSES[tpl.colorKey]?.selected ?? TEMPLATE_COLOR_CLASSES.slate.selected
                : TEMPLATE_COLOR_CLASSES[tpl.colorKey]?.idle ?? TEMPLATE_COLOR_CLASSES.slate.idle"
              @click="applyTemplate(tpl)"
            >
              <!-- Check mark when selected -->
              <span
                v-if="selectedTemplate === tpl.id"
                class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-teal-500 text-white shadow"
              >
                <CheckIcon class="h-3 w-3" />
              </span>

              <!-- Icon bubble -->
              <span
                class="flex h-10 w-10 items-center justify-center rounded-xl text-2xl"
                :class="TEMPLATE_COLOR_CLASSES[tpl.colorKey]?.icon ?? TEMPLATE_COLOR_CLASSES.slate.icon"
              >{{ tpl.icon }}</span>

              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-50">{{ tpl.title }}</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ tpl.description }}</p>
              </div>

              <p
                v-if="tpl.permissions.length"
                class="text-[11px] font-medium"
                :class="TEMPLATE_COLOR_CLASSES[tpl.colorKey]?.text ?? TEMPLATE_COLOR_CLASSES.slate.text"
              >
                {{ tpl.permissions.length }} quyền đặt sẵn
              </p>
              <p v-else class="text-[11px] text-slate-400 dark:text-slate-500">Bắt đầu trống</p>
            </button>
          </div>
        </div>

        <!-- Next button -->
        <div class="mt-4 flex justify-end">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
            :disabled="!selectedTemplate"
            @click="advanceToPermissions"
          >
            Tiếp theo: Cấu hình quyền
            <ChevronDownIcon class="h-4 w-4 rotate-[-90deg]" />
          </button>
        </div>
      </template>

      <!-- ════════════════════════════════════════════════════════════════════
           STEP 2 (create) / MAIN EDITOR (edit): Role info + Permission cards
           ════════════════════════════════════════════════════════════════════ -->
      <template v-if="!isCreate || step === 'permissions'">

        <!-- Template badge (create mode) -->
        <div v-if="isCreate && selectedTemplate" class="mb-4 flex items-center gap-2">
          <button
            type="button"
            class="flex items-center gap-1.5 text-xs text-slate-500 transition hover:text-teal-600 dark:text-slate-400 dark:hover:text-teal-400"
            @click="step = 'template'"
          >
            <ArrowLeftIcon class="h-3.5 w-3.5" />
            Đổi mẫu
          </button>
          <span class="text-slate-300 dark:text-slate-700">|</span>
          <span class="text-xs text-slate-500 dark:text-slate-400">
            Đang dùng mẫu:
            <strong class="text-slate-800 dark:text-slate-200">
              {{ JOB_ROLE_TEMPLATES.find(t => t.id === selectedTemplate)?.title }}
            </strong>
          </span>
        </div>

        <!-- ── Section A: Role identity ──────────────────────────────────── -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Thông tin vai trò
          </h2>

          <div class="grid gap-4 sm:grid-cols-2">
            <!-- Display name -->
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-800 dark:text-slate-200">
                Tên hiển thị <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.display_name"
                type="text"
                placeholder="vd. Điều phối vận hành"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-500/25 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                :disabled="saving"
              />
            </div>

            <!-- Slug / code — only in create mode -->
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-800 dark:text-slate-200">
                Mã vai trò <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="vd. dispatcher"
                class="w-full rounded-xl border bg-white px-3.5 py-2.5 font-mono text-sm text-slate-900 placeholder:text-slate-400 outline-none focus:ring-2 focus:ring-teal-500/25 disabled:opacity-60 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                :class="nameError
                  ? 'border-red-400 focus:border-red-400 dark:border-red-600'
                  : 'border-slate-200 focus:border-teal-400 dark:border-slate-600'"
                :disabled="saving || !isCreate"
              />
              <p v-if="!isCreate" class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                Mã không thể thay đổi sau khi tạo.
              </p>
              <p v-else-if="nameError" class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ nameError }}</p>
              <p v-else class="mt-1 text-[11px] text-slate-400">Chỉ chữ thường, số và dấu _ (gạch dưới).</p>
            </div>

            <!-- Description -->
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-800 dark:text-slate-200">Mô tả</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Mô tả ngắn về vai trò và phạm vi công việc…"
                class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-500/25 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                :disabled="saving"
              />
            </div>
          </div>
        </div>

        <!-- ── Section B: Permission cards ──────────────────────────────── -->
        <div class="mt-5 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <!-- Card header -->
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
            <div>
              <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Nhóm quyền
              </h2>
              <p class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">
                Đã bật
                <span class="font-bold text-teal-700 dark:text-teal-400">{{ totalSelected }}</span>
                quyền
              </p>
            </div>
            <div class="flex gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="expandAll"
              >Mở tất cả</button>
              <button
                type="button"
                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="collapseAll"
              >Thu gọn</button>
            </div>
          </div>

          <!-- Capability cards list -->
          <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="group in BUSINESS_CAPABILITY_GROUPS"
              :key="group.id"
              class="px-6 py-4"
            >
              <!-- Group header row -->
              <div class="flex items-center gap-3">
                <!-- Group toggle (enable/disable entire group) -->
                <button
                  type="button"
                  class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-500"
                  :class="groupSelectedCount(group) === group.capabilities.length
                    ? 'border-teal-500 bg-teal-500'
                    : groupSelectedCount(group) > 0
                      ? 'border-teal-300 bg-teal-200 dark:border-teal-700 dark:bg-teal-900/50'
                      : 'border-slate-300 bg-slate-200 dark:border-slate-600 dark:bg-slate-700'"
                  :disabled="saving"
                  :aria-label="`Bật / tắt nhóm ${group.title}`"
                  @click="toggleGroup(group)"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition"
                    :class="groupSelectedCount(group) === group.capabilities.length ? 'translate-x-5' : 'translate-x-0.5'"
                  />
                </button>

                <!-- Icon + title -->
                <span class="text-xl leading-none">{{ group.icon }}</span>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-slate-900 dark:text-slate-50">{{ group.title }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ group.description }}</p>
                </div>

                <!-- Count badge -->
                <span
                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums"
                  :class="groupSelectedCount(group) > 0
                    ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-200'
                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                >
                  {{ groupSelectedCount(group) }}/{{ group.capabilities.length }}
                </span>

                <!-- Expand/collapse chevron -->
                <button
                  type="button"
                  class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                  :aria-label="openGroups.has(group.id) ? 'Thu gọn' : 'Mở rộng'"
                  @click="toggleGroupOpen(group.id)"
                >
                  <ChevronUpIcon v-if="openGroups.has(group.id)" class="h-4 w-4" />
                  <ChevronDownIcon v-else class="h-4 w-4" />
                </button>
              </div>

              <!-- Capability list (accordion) -->
              <div
                v-if="openGroups.has(group.id)"
                class="mt-3 ml-14 grid gap-2 sm:grid-cols-2"
              >
                <label
                  v-for="cap in group.capabilities"
                  :key="cap.perm"
                  class="flex cursor-pointer items-center gap-3 rounded-xl px-3.5 py-2.5 transition"
                  :class="selectedPermNames.has(cap.perm)
                    ? `${getColorClasses(group.colorKey).bg} ${getColorClasses(group.colorKey).border} border`
                    : 'border border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
                    :checked="selectedPermNames.has(cap.perm)"
                    :disabled="saving"
                    @change="togglePerm(cap.perm)"
                  />
                  <span class="text-sm text-slate-800 dark:text-slate-200">{{ cap.label }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Warning if zero permissions ──────────────────────────────── -->
        <div
          v-if="totalSelected === 0"
          class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200"
        >
          <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" />
          <p>Vai trò này chưa có quyền nào. Nhân viên được gán sẽ không thực hiện được bất kỳ thao tác nào.</p>
        </div>

        <!-- ── Footer actions ─────────────────────────────────────────────── -->
        <div class="mt-6 flex items-center justify-between gap-3">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
            :disabled="saving"
            @click="router.push({ name: 'systemRoles' })"
          >
            Hủy
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
            :disabled="!canSubmit"
            @click="submit"
          >
            <span
              v-if="saving"
              class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
            />
            <CheckIcon v-else class="h-4 w-4" />
            {{ isCreate ? 'Tạo vai trò' : 'Lưu thay đổi' }}
          </button>
        </div>

      </template>
    </template>

  </div>
</template>
