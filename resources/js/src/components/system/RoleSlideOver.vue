<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import Input from '../ui/Input.vue'
import Button from '../ui/Button.vue'
import PermissionMatrix from './PermissionMatrix.vue'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'

/**
 * RoleSlideOver — right-aligned slide-over panel for creating or editing a role.
 *
 * Props:
 *   role       — existing role object (null → create mode)
 *   allPerms   — full list of permission objects from the parent
 *   saving     — external loading flag (optional)
 *
 * Emits:
 *   close      — panel should be dismissed
 *   saved      — role was created or updated; parent should reload list
 */
const props = defineProps({
  role:     { type: Object, default: null },
  allPerms: { type: Array,  required: true },
  saving:   { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'saved'])

// ─── State ──────────────────────────────────────────────────────────────────

const isCreate = computed(() => !props.role)
const title    = computed(() => isCreate.value ? 'Thêm vai trò mới' : `Sửa vai trò: ${props.role?.display_name || props.role?.name}`)

const form = reactive({
  name:         '',
  display_name: '',
  description:  '',
})

const selectedPermIds = ref([])
const loadingPerms    = ref(false)
const internalSaving  = ref(false)
const busy = computed(() => props.saving || internalSaving.value)

// ─── Lifecycle ───────────────────────────────────────────────────────────────

onMounted(() => {
  if (props.role) {
    form.name         = props.role.name ?? ''
    form.display_name = props.role.display_name ?? ''
    form.description  = props.role.description ?? ''
    fetchRolePerms(props.role.id)
  }
  document.addEventListener('keydown', handleEsc)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc)
})

watch(() => props.role, (r) => {
  if (r) {
    form.name         = r.name ?? ''
    form.display_name = r.display_name ?? ''
    form.description  = r.description ?? ''
    fetchRolePerms(r.id)
  } else {
    resetForm()
  }
})

// ─── Helpers ─────────────────────────────────────────────────────────────────

function resetForm() {
  form.name         = ''
  form.display_name = ''
  form.description  = ''
  selectedPermIds.value = []
}

function handleEsc(e) {
  if (e.key === 'Escape' && !busy.value) emit('close')
}

async function fetchRolePerms(roleId) {
  loadingPerms.value = true
  try {
    const detail = await admin.getRole(roleId)
    selectedPermIds.value = (detail.permissions ?? []).map((p) => p.id)
  } catch {
    selectedPermIds.value = []
  } finally {
    loadingPerms.value = false
  }
}

// ─── Validation ──────────────────────────────────────────────────────────────

const nameError = computed(() => {
  const v = form.name.trim()
  if (!v) return 'Mã vai trò là bắt buộc.'
  if (!/^[a-z][a-z0-9_]*$/.test(v)) return 'Chỉ dùng chữ thường, số và dấu gạch dưới, bắt đầu bằng chữ cái.'
  return ''
})

// ─── Submit ──────────────────────────────────────────────────────────────────

async function submit() {
  if (nameError.value || busy.value) return

  internalSaving.value = true
  try {
    const payload = {
      name:           form.name.trim(),
      display_name:   form.display_name.trim() || null,
      description:    form.description.trim()  || null,
      permission_ids: selectedPermIds.value,
    }

    if (isCreate.value) {
      await admin.createRole(payload)
      showAppSuccess('Đã thêm vai trò mới.', 'Thành công')
    } else {
      await admin.updateRole(props.role.id, payload)
      showAppSuccess('Đã lưu thay đổi vai trò.', 'Thành công')
    }

    emit('saved')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    internalSaving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
  <!-- Backdrop -->
  <div
    class="fixed inset-0 z-50 flex justify-end"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="isCreate ? 'slideover-create-title' : 'slideover-edit-title'"
  >
    <!-- Dim overlay -->
    <div
      class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity"
      aria-hidden="true"
      @click="!busy && emit('close')"
    />

    <!-- Panel -->
    <div class="relative z-10 flex h-full w-full max-w-2xl flex-col bg-white shadow-2xl dark:bg-slate-900">
      <!-- ── Sticky header ──────────────────────────────────────────────── -->
      <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-900">
        <h2
          :id="isCreate ? 'slideover-create-title' : 'slideover-edit-title'"
          class="text-base font-semibold text-slate-900 dark:text-slate-100"
        >
          {{ title }}
        </h2>
        <button
          type="button"
          class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
          aria-label="Đóng"
          :disabled="busy"
          @click="emit('close')"
        >
          <XMarkIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <!-- ── Scrollable body ────────────────────────────────────────────── -->
      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-6 py-5">

        <!-- Section 1: Role info -->
        <section class="mb-6">
          <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Thông tin vai trò
          </h3>
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-1">
              <Input
                v-model="form.name"
                label="Mã vai trò *"
                placeholder="vd. dispatcher"
                :disabled="busy || (!isCreate)"
                :error="form.name && nameError ? nameError : ''"
              />
              <p v-if="!isCreate" class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                Mã vai trò không thể đổi sau khi tạo.
              </p>
            </div>
            <Input
              v-model="form.display_name"
              label="Tên hiển thị"
              placeholder="vd. Điều vận"
              :disabled="busy"
            />
            <div class="sm:col-span-2">
              <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-800 dark:text-slate-200">Mô tả</span>
                <textarea
                  v-model="form.description"
                  rows="2"
                  placeholder="Mô tả ngắn về vai trò này…"
                  :disabled="busy"
                  class="w-full resize-none rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-500/25 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500"
                />
              </label>
            </div>
          </div>
        </section>

        <!-- Divider -->
        <div class="mb-5 border-t border-slate-100 dark:border-slate-800" />

        <!-- Section 2: Permission matrix -->
        <section>
          <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Phân quyền theo module
          </h3>

          <!-- Loading perms shimmer -->
          <div v-if="loadingPerms" class="space-y-2 py-4">
            <div
              v-for="i in 4"
              :key="i"
              class="h-10 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"
            />
          </div>

          <PermissionMatrix
            v-else
            v-model="selectedPermIds"
            :all-perms="allPerms"
            :readonly="busy"
          />
        </section>
      </div>

      <!-- ── Sticky footer ──────────────────────────────────────────────── -->
      <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-900">
        <Button variant="secondary" type="button" :disabled="busy" @click="emit('close')">
          Huỷ
        </Button>
        <Button
          type="button"
          :loading="busy"
          :disabled="busy || !!nameError"
          @click="submit"
        >
          {{ isCreate ? 'Thêm vai trò' : 'Lưu thay đổi' }}
        </Button>
      </div>
    </div>
  </div>
  </Teleport>
</template>
