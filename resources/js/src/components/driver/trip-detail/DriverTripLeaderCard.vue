<template>
  <div class="flex items-center gap-3 rounded-2xl bg-driver-card px-4 py-3.5 ring-1 ring-white/[0.06]">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#7fdcc8]/15 text-[#7fdcc8] ring-1 ring-[#7fdcc8]/25">
      <UserCircleIcon class="h-6 w-6" aria-hidden="true" />
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-muted/70">
        {{ t('driver_trip_detail.leader_title') }}
      </p>
      <p class="mt-0.5 break-words text-base font-semibold leading-snug text-driver-ink sm:text-lg">
        {{ leader.name }}
      </p>
    </div>
    <a
      v-if="leader.phone"
      :href="`tel:${leader.phone}`"
      class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-full border border-white/10 bg-driver-surface text-driver-muted active:bg-driver-elevated"
      :aria-label="t('driver_trip_detail.call')"
    >
      <PhoneIcon class="h-5 w-5" aria-hidden="true" />
    </a>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { PhoneIcon, UserCircleIcon } from '@heroicons/vue/24/outline'

defineProps({
  leader: { type: Object, required: true },
})

const { t } = useI18n()
</script>
