<script setup>
import {
  ArrowPathIcon,
  CheckIcon,
  EnvelopeIcon,
  IdentificationIcon,
  BuildingOffice2Icon,
  BriefcaseIcon,
} from '@heroicons/vue/24/outline'
import UserAvatar from '../branding/UserAvatar.vue'

const props = defineProps({
  user: { type: Object, required: true },
  roles: { type: Array, default: () => [] },
  roleId: { type: [Number, null], default: null },
  selected: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  saved: { type: Boolean, default: false },
  locked: { type: Boolean, default: false },
  colVisible: { type: Object, required: true },
  displayOrSupplement: { type: Function, required: true },
  isSupplementValue: { type: Function, required: true },
  currentRoleName: { type: String, default: null },
})

const emit = defineEmits(['toggle-select', 'role-change'])

const NEEDS = 'Cần bổ sung'
</script>

<template>
  <article
    class="rounded-2xl bg-slate-50/90 px-3 py-3.5 transition dark:bg-slate-800/35 sm:px-4"
    :class="selected ? 'ring-2 ring-teal-500/30 ring-offset-2 ring-offset-white dark:ring-offset-slate-950' : ''"
    :data-testid="`user-role-card-${user.id}`"
  >
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4">
      <div class="flex min-w-0 items-start gap-3 lg:min-w-[14rem] lg:max-w-[18rem] lg:shrink-0">
        <input
          type="checkbox"
          :checked="selected"
          :aria-label="`Chọn ${user.name}`"
          class="mt-2.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
          data-testid="user-role-card-select"
          @change="emit('toggle-select')"
        />
        <UserAvatar
          :name="user.name"
          :email="user.email"
          :avatar-url="user.avatar_url"
          size="md"
          class="shrink-0"
        />
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</p>
          <p v-if="colVisible.email" class="mt-0.5 flex items-center gap-1 truncate text-xs text-slate-500 dark:text-slate-400">
            <EnvelopeIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ user.email }}
          </p>
          <p
            v-if="currentRoleName"
            class="mt-1 inline-flex max-w-full truncate rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-950/50 dark:text-violet-200"
          >
            {{ currentRoleName }}
          </p>
        </div>
      </div>

      <div class="grid min-w-0 flex-1 grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3 lg:grid-cols-3">
        <div v-if="colVisible.employee_code" class="min-w-0">
          <p class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
            <IdentificationIcon class="h-3 w-3" aria-hidden="true" />
            Mã NV
          </p>
          <p
            class="mt-0.5 truncate text-sm"
            :class="isSupplementValue(user.employee_code) ? 'italic text-slate-400' : 'font-mono text-slate-700 dark:text-slate-300'"
          >
            {{ displayOrSupplement(user.employee_code) }}
          </p>
        </div>
        <div v-if="colVisible.department" class="min-w-0">
          <p class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
            <BuildingOffice2Icon class="h-3 w-3" aria-hidden="true" />
            Phòng ban
          </p>
          <p
            class="mt-0.5 line-clamp-2 text-sm"
            :class="isSupplementValue(user.department_name) ? 'italic text-slate-400' : 'text-slate-700 dark:text-slate-300'"
          >
            {{ displayOrSupplement(user.department_name) }}
          </p>
        </div>
        <div v-if="colVisible.position" class="min-w-0">
          <p class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
            <BriefcaseIcon class="h-3 w-3" aria-hidden="true" />
            Chức vụ
          </p>
          <p
            class="mt-0.5 line-clamp-2 text-sm"
            :class="isSupplementValue(user.position_name) ? 'italic text-slate-400' : 'text-slate-700 dark:text-slate-300'"
          >
            {{ displayOrSupplement(user.position_name) }}
          </p>
        </div>
      </div>

      <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center lg:min-w-[13rem] lg:flex-col lg:items-stretch">
        <div v-if="colVisible.role" class="min-w-0">
          <label class="sr-only" :for="`ur-role-${user.id}`">Vai trò của {{ user.name }}</label>
          <select
            :id="`ur-role-${user.id}`"
            :value="roleId ?? ''"
            :disabled="locked || saving"
            class="input h-10 w-full min-w-[10rem] rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
            data-testid="user-role-card-role-select"
            @change="emit('role-change', $event.target.value === '' ? null : Number($event.target.value))"
          >
            <option value="">— Chưa gán —</option>
            <option v-for="r in roles" :key="r.id" :value="r.id">
              {{ r.display_name || r.name }}
            </option>
          </select>
        </div>
        <div v-if="colVisible.status" class="flex items-center justify-end gap-1 text-xs sm:justify-start">
          <span v-if="saving" class="inline-flex items-center gap-1 text-slate-400">
            <ArrowPathIcon class="h-4 w-4 animate-spin" aria-hidden="true" />
            Đang lưu
          </span>
          <span v-else-if="saved" class="inline-flex items-center gap-1 font-medium text-teal-600 dark:text-teal-400">
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            Đã lưu
          </span>
          <span v-else-if="!currentRoleName" class="text-slate-400">{{ NEEDS }}</span>
        </div>
      </div>
    </div>
  </article>
</template>
