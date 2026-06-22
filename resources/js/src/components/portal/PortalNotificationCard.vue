<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  ChevronRightIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'
import { refCodeFromPortalNotification, routeFromPortalNotification } from '../../util/portalNotificationRoute'

const props = defineProps({
  notification: { type: Object, required: true },
  visual: { type: Object, required: true },
  relativeTime: { type: String, default: '' },
  linkComponent: { type: [Object, String], default: 'div' },
  linkBind: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['click', 'mark-read'])

const { t } = useI18n()

const n = computed(() => props.notification)

const route = computed(() => routeFromPortalNotification(n.value))

const requestId = computed(() => {
  const id = n.value.data?.dispatch_request_id
  return id != null && id !== '' ? String(id) : ''
})

const requestRef = computed(() => refCodeFromPortalNotification(n.value))

const bodyText = computed(() => {
  const body = n.value.data?.body != null ? String(n.value.data.body).trim() : ''
  if (!body) return ''
  if (route.value.hasRoute && body.includes('→')) return ''
  return body
})

const showRouteBlock = computed(() => route.value.hasRoute || requestId.value !== '')

function onClick() {
  emit('click', n.value)
}
</script>

<template>
  <li
    class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-md"
    :class="
      n.read
        ? 'border-slate-200/90'
        : 'border-va-200/90 bg-gradient-to-br from-va-50/50 via-white to-white ring-1 ring-va-100/80'
    "
    data-testid="portal-notification-card"
  >
    <component
      :is="linkComponent"
      v-bind="linkBind"
      class="block min-h-[11rem] px-4 py-5 sm:min-h-[12rem] sm:px-6 sm:py-6"
      @click="onClick"
    >
      <div class="flex gap-4">
        <span
          class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-white"
          :class="n.read ? 'bg-slate-200' : 'bg-va-700 shadow-sm shadow-va-700/30'"
          :aria-hidden="true"
        />

        <span
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1"
          :class="visual.iconBg"
          aria-hidden="true"
        >
          <component :is="visual.icon" class="h-6 w-6" :class="visual.iconColor" />
        </span>

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="text-base font-bold leading-snug text-slate-900 sm:text-lg">
                {{ n.data?.title ?? n.type }}
              </p>
              <p v-if="requestRef" class="mt-1 font-mono text-xs font-semibold tracking-tight text-va-800/90">
                {{ requestRef }}
              </p>
            </div>
            <time
              class="shrink-0 text-xs font-medium tabular-nums text-slate-400"
              :datetime="n.created_at"
            >
              {{ relativeTime }}
            </time>
          </div>

          <p v-if="bodyText" class="mt-2 text-sm leading-relaxed text-slate-600">
            {{ bodyText }}
          </p>

          <div
            v-if="showRouteBlock"
            class="mt-4 grid gap-2 rounded-xl border border-slate-100 bg-slate-50/80 p-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center sm:gap-3 sm:p-4"
          >
            <div class="min-w-0 rounded-lg border border-emerald-100/80 bg-emerald-50/70 px-3 py-2.5">
              <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700/90">
                {{ t('portal.origin') }}
              </p>
              <p class="mt-1 flex items-start gap-1.5 text-sm font-semibold leading-snug text-slate-900">
                <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" aria-hidden="true" />
                <span class="min-w-0">{{
                  route.origin || t('portal.notifications_route_pending')
                }}</span>
              </p>
            </div>

            <div class="flex justify-center py-0.5 sm:py-0" aria-hidden="true">
              <ArrowRightIcon class="hidden h-5 w-5 text-slate-300 sm:block" />
              <ArrowRightIcon class="h-4 w-4 rotate-90 text-slate-300 sm:hidden" />
            </div>

            <div class="min-w-0 rounded-lg border border-rose-100/80 bg-rose-50/70 px-3 py-2.5">
              <p class="text-[10px] font-bold uppercase tracking-wider text-rose-700/90">
                {{ t('portal.destination') }}
              </p>
              <p class="mt-1 flex items-start gap-1.5 text-sm font-semibold leading-snug text-slate-900">
                <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" aria-hidden="true" />
                <span class="min-w-0">{{
                  route.destination || t('portal.notifications_route_pending')
                }}</span>
              </p>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <span
              v-if="visual.badgeLabel"
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold"
              :class="visual.badgeClass"
            >
              <component
                :is="visual.badgeIcon"
                v-if="visual.badgeIcon"
                class="h-3 w-3"
                aria-hidden="true"
              />
              {{ visual.badgeLabel }}
            </span>
            <span
              v-if="!n.read"
              class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-800 ring-1 ring-violet-200/60"
            >
              {{ t('portal.notifications_badge_new') }}
            </span>
          </div>
        </div>

        <ChevronRightIcon
          v-if="requestId"
          class="mt-2 hidden h-5 w-5 shrink-0 text-slate-300 sm:block"
          aria-hidden="true"
        />
      </div>
    </component>
  </li>
</template>
