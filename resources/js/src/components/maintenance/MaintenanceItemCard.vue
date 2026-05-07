<template>
  <RouterLink
    :to="{ name: 'driverMaintenanceDetail', params: { id: item.id } }"
    class="flex min-h-[64px] items-center gap-3 rounded-2xl bg-[#111d16] px-4 py-3.5 ring-1 ring-white/8 active:scale-[0.98] transition-transform"
  >
    <!-- Icon circle -->
    <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-full', iconBgClass]">
      <component :is="iconComponent" class="h-6 w-6" :class="iconColorClass" />
    </div>

    <!-- Text -->
    <div class="min-w-0 flex-1">
      <p class="text-[17px] font-bold leading-snug text-white">{{ item.name }}</p>
      <p class="mt-0.5 text-sm text-white/50">
        <span v-if="item.expiry_date">{{ t('driver_maintenance.deadline') }}: {{ formatDate(item.expiry_date) }}</span>
        <span v-else-if="item.next_service_km">{{ t('driver_maintenance.next_service_km') }} {{ item.next_service_km.toLocaleString('vi-VN') }} {{ t('driver_maintenance.km_unit') }}</span>
        <span v-else-if="item.next_service_date">{{ t('driver_maintenance.next_service_date') }}: {{ formatDate(item.next_service_date) }}</span>
        <span v-else>—</span>
      </p>
    </div>

    <!-- Badge -->
    <div class="flex shrink-0 items-center gap-2">
      <MaintenanceStatusBadge :status="item.status" :days-remaining="item.days_remaining" />
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
        class="h-4 w-4 text-white/30" aria-hidden="true">
        <path fill-rule="evenodd"
          d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
          clip-rule="evenodd" />
      </svg>
    </div>
  </RouterLink>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import MaintenanceStatusBadge from './MaintenanceStatusBadge.vue'

const { t } = useI18n()

const props = defineProps({
  item: { type: Object, required: true },
})

function formatDate(ymd) {
  if (!ymd) return '—'
  const [y, m, d] = String(ymd).split('-')
  if (!y || !m || !d) return ymd
  return `${d}/${m}/${y}`
}

const iconBgClass = computed(() => {
  if (props.item.status === 'urgent') return 'bg-[#ff6b6b]/15'
  if (props.item.status === 'warning') return 'bg-amber-400/15'
  return 'bg-[#7fdcc8]/12'
})

const iconColorClass = computed(() => {
  if (props.item.status === 'urgent') return 'text-[#ff6b6b]'
  if (props.item.status === 'warning') return 'text-amber-300'
  return 'text-[#7fdcc8]'
})

// SVG icon as inline component per type
const iconComponent = computed(() => {
  const icon = props.item.icon ?? 'document'
  const icons = {
    registration: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.644 1.59a.75.75 0 0 1 .712 0l9.75 5.25a.75.75 0 0 1 0 1.32l-9.75 5.25a.75.75 0 0 1-.712 0l-9.75-5.25a.75.75 0 0 1 0-1.32l9.75-5.25Z"/><path d="m3.265 10.602 7.668 4.129a2.25 2.25 0 0 0 2.134 0l7.668-4.13 1.37.739a.75.75 0 0 1 0 1.32l-9.75 5.25a.75.75 0 0 1-.71 0l-9.75-5.25a.75.75 0 0 1 0-1.32l1.37-.738Z"/><path d="m10.933 19.231-7.668-4.13-1.37.739a.75.75 0 0 0 0 1.32l9.75 5.25c.221.12.489.12.71 0l9.75-5.25a.75.75 0 0 0 0-1.32l-1.37-.738-7.668 4.13a2.25 2.25 0 0 1-2.134-.001Z"/></svg>`,
    },
    insurance: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/></svg>`,
    },
    oil: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.47 1.72a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 0 1-1.06 1.06l-2.47-2.47V21a.75.75 0 0 1-1.5 0V4.06L8.78 6.53a.75.75 0 0 1-1.06-1.06l3.75-3.75Z"/></svg>`,
    },
    tire: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM6.262 6.072a8.25 8.25 0 1 0 10.562-.766 4.5 4.5 0 0 1-1.318 1.357L14.25 7.5l.165.33a.809.809 0 0 1-1.086 1.085l-.604-.302a1.125 1.125 0 0 0-1.298.21l-.132.131c-.439.44-.439 1.152 0 1.591l.296.296c.256.257.622.374.98.314l1.17-.195c.323-.054.654.036.905.245l1.33 1.108c.32.267.46.694.358 1.1a8.7 8.7 0 0 1-2.288 4.04l-.723.724a1.125 1.125 0 0 1-1.298.21l-.153-.076a1.125 1.125 0 0 1-.622-1.006v-1.089c0-.298-.119-.585-.33-.796l-1.347-1.347a1.125 1.125 0 0 1-.21-1.298L9.75 12l-1.64-1.073a1.125 1.125 0 0 1 0-1.908l1.16-.551a1.125 1.125 0 0 0 .12-1.97l-.065-.038c-.144-.087-.37-.1-.531-.02l-1.793.896a1.125 1.125 0 0 1-.924.015l-.88-.44a1.125 1.125 0 0 0-1.386.206 8.25 8.25 0 0 0 1.81 7.878Z" clip-rule="evenodd"/></svg>`,
    },
    filter: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3.792 2.938A49.069 49.069 0 0 1 12 2.25c2.797 0 5.54.236 8.209.688a1.857 1.857 0 0 1 1.541 1.836v1.044a3 3 0 0 1-.879 2.121l-6.182 6.182a1.5 1.5 0 0 0-.439 1.061v2.927a3 3 0 0 1-1.658 2.684l-1.5.75a3 3 0 0 1-4.342-2.684V15.11a1.5 1.5 0 0 0-.44-1.06L3.41 7.866A3 3 0 0 1 2.53 5.74V4.696a1.857 1.857 0 0 1 1.261-1.758Z" clip-rule="evenodd"/></svg>`,
    },
    brake: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm0 8.625a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25ZM15.375 12a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" clip-rule="evenodd"/></svg>`,
    },
    document: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75-6.75a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z" clip-rule="evenodd"/><path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z"/></svg>`,
    },
  }
  return icons[icon] ?? icons.document
})
</script>
