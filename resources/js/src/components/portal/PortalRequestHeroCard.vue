<template>
  <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <RouterLink
          :to="{ name: backRoute }"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
          :aria-label="t('portal.back_list')"
        >
          <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
        </RouterLink>
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <h1 class="font-mono text-2xl font-semibold tracking-tight text-slate-900">
              #{{ req.id }}
            </h1>
            <StatusBadge :status="req.status" />
            <span
              v-if="priorityTone === 'urgent'"
              class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-800 ring-1 ring-rose-200/80"
            >
              <BoltIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ priorityLabel }}
            </span>
            <span
              v-else
              class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
            >
              {{ priorityLabel }}
            </span>
          </div>
          <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600">
            <div v-if="createdFmt" class="flex gap-1.5">
              <dt class="text-slate-500">{{ t('portal.detail_hero.created') }}</dt>
              <dd class="font-medium text-slate-800">{{ createdFmt }}</dd>
            </div>
            <div v-if="requesterName" class="flex gap-1.5">
              <dt class="text-slate-500">{{ t('portal.requester') }}</dt>
              <dd class="font-medium text-slate-800">{{ requesterName }}</dd>
            </div>
            <div v-if="pollingRefreshing" class="inline-flex items-center gap-1.5 text-teal-700">
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-teal-600" aria-hidden="true" />
              {{ t('portal.auto_refresh_indicator') }}
            </div>
          </dl>
        </div>
      </div>

      <div class="flex flex-wrap gap-2 sm:justify-end">
        <button
          type="button"
          class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="$emit('copy-id')"
        >
          <ClipboardDocumentIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
          {{ copyIdFeedback ? t('portal.copied') : t('portal.copy_id') }}
        </button>
        <button
          type="button"
          class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="!canPrint"
          @click="$emit('print')"
        >
          <PrinterIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
          {{ t('portal.detail_hero.print') }}
        </button>
        <button
          type="button"
          class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="$emit('follow')"
        >
          <BellIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
          {{ t('portal.detail_hero.follow') }}
        </button>
        <a
          :href="contactHref"
          class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-va-200 bg-va-50 px-3 text-sm font-semibold text-va-900 transition hover:bg-va-100"
        >
          <PhoneIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('portal.detail_hero.contact_dispatch') }}
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  BellIcon,
  BoltIcon,
  ClipboardDocumentIcon,
  PhoneIcon,
  PrinterIcon,
} from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'

const props = defineProps({
  req: { type: Object, required: true },
  backRoute: { type: String, required: true },
  priorityLabel: { type: String, default: '' },
  priorityTone: { type: String, default: 'normal' },
  copyIdFeedback: { type: Boolean, default: false },
  pollingRefreshing: { type: Boolean, default: false },
  canPrint: { type: Boolean, default: false },
  contactHref: { type: String, default: 'mailto:dieuhanh@vaschools.edu.vn' },
})

defineEmits(['copy-id', 'print', 'follow'])

const { t } = useI18n()

const requesterName = computed(() => {
  const r = props.req
  return (r.requester_name || r.requester?.name || r.user?.name || '').trim()
})

const createdFmt = computed(() => {
  const iso = props.req?.created_at
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
})
</script>
