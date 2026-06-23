<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowRightIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
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
  if (route.value.hasRoute && (body.includes('→') || body.includes('->'))) return ''
  return body
})

const routeOriginLabel = computed(() =>
  route.value.origin ? route.value.origin : t('portal.notifications_route_pending'),
)

const routeDestinationLabel = computed(() =>
  route.value.destination ? route.value.destination : t('portal.notifications_route_pending'),
)

const showRouteRow = computed(() => route.value.hasRoute)

const linkAriaLabel = computed(() => {
  if (!requestId.value) return undefined
  const ref = requestRef.value || requestId.value
  return t('portal.notifications_open_request', { id: ref })
})

function onClick() {
  emit('click', n.value)
}
</script>

<template>
  <li
    class="overflow-hidden rounded-xl border bg-white shadow-sm transition hover:border-slate-200 hover:shadow-md"
    :class="
      n.read
        ? 'border-slate-200/90'
        : 'border-va-200/80 ring-1 ring-va-100/60'
    "
    data-testid="portal-notification-card"
  >
    <component
      :is="linkComponent"
      v-bind="linkBind"
      class="group/card flex min-w-0 items-stretch gap-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
      :aria-label="linkAriaLabel"
      @click="onClick"
    >
      <span
        class="w-1 shrink-0"
        :class="n.read ? 'bg-slate-100' : 'bg-va-700'"
        aria-hidden="true"
      />

      <div class="flex min-w-0 flex-1 items-start gap-3 px-3 py-3.5 sm:px-4 sm:py-4">
        <span
          class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1"
          :class="visual.iconBg"
          aria-hidden="true"
        >
          <component :is="visual.icon" class="h-5 w-5" :class="visual.iconColor" />
          <span
            v-if="!n.read"
            class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-violet-600"
            aria-hidden="true"
          />
        </span>

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-2">
            <p class="min-w-0 flex-1 text-sm font-semibold leading-snug text-slate-900 sm:text-[15px]">
              {{ n.data?.title ?? n.type }}
            </p>
            <time
              class="shrink-0 text-[11px] font-medium tabular-nums text-slate-400"
              :datetime="n.created_at"
            >
              {{ relativeTime }}
            </time>
          </div>

          <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
            <span
              v-if="requestRef"
              class="font-mono text-xs font-bold tracking-tight text-va-900/90"
            >
              {{ requestRef }}
            </span>
            <span
              v-else-if="requestId"
              class="font-mono text-xs font-semibold text-slate-600"
            >
              #{{ requestId }}
            </span>
            <span
              v-if="visual.badgeLabel"
              class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold leading-none"
              :class="visual.badgeClass"
            >
              <component
                :is="visual.badgeIcon"
                v-if="visual.badgeIcon"
                class="h-3 w-3 shrink-0"
                aria-hidden="true"
              />
              {{ visual.badgeLabel }}
            </span>
            <span
              v-if="!n.read"
              class="inline-flex rounded-md bg-violet-50 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-violet-800 ring-1 ring-violet-200/60"
            >
              {{ t('portal.notifications_badge_new') }}
            </span>
          </div>

          <div
            v-if="showRouteRow"
            class="mt-2 flex min-w-0 items-start gap-1.5 rounded-lg border border-slate-100 bg-slate-50/90 px-2.5 py-2 text-xs leading-snug text-slate-800"
          >
            <span class="min-w-0 flex-1 font-medium line-clamp-2">
              <span class="text-emerald-800/90">{{ routeOriginLabel }}</span>
            </span>
            <ArrowRightIcon
              class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400"
              aria-hidden="true"
            />
            <span class="min-w-0 flex-1 text-right font-medium line-clamp-2">
              <span class="text-rose-800/90">{{ routeDestinationLabel }}</span>
            </span>
          </div>

          <p
            v-if="bodyText"
            class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-600"
          >
            {{ bodyText }}
          </p>
        </div>

        <ChevronRightIcon
          v-if="requestId"
          class="mt-1 hidden h-5 w-5 shrink-0 text-slate-300 transition group-hover/card:text-va-700 sm:block"
          aria-hidden="true"
        />
      </div>
    </component>
  </li>
</template>
