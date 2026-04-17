<template>
  <div
    v-if="banners.length"
    class="mb-4 shrink-0 space-y-2"
    role="region"
    :aria-label="t('operational_banner.region_label')"
  >
    <div
      v-for="(b, i) in banners"
      :key="i"
      class="flex gap-3 rounded-lg border px-3 py-2.5 text-sm leading-snug shadow-sm sm:px-4"
      :class="b.boxClass"
    >
      <component :is="b.icon" class="h-5 w-5 shrink-0" :class="b.iconClass" aria-hidden="true" />
      <div class="min-w-0 flex-1">
        <p class="font-semibold" :class="b.titleClass">{{ b.title }}</p>
        <p class="mt-0.5 text-xs sm:text-sm" :class="b.bodyClass">{{ b.body }}</p>
        <p v-if="b.moduleName" class="mt-1 text-[11px] opacity-90">{{ b.moduleName }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ExclamationTriangleIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/solid'
import { useAuthStore } from '../../store'

const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()

const banners = computed(() => {
  const key = route.meta?.featureKey
  if (!key || typeof key !== 'string') return []

  const st = auth.featureToggleRuntimeState(key)
  if (!st) return []

  const out = []
  const moduleName = st.name ? t('operational_banner.module_label', { name: st.name }) : ''

  if (st.maintenance_mode) {
    out.push({
      icon: WrenchScrewdriverIcon,
      title: t('operational_banner.maintenance_title'),
      body: t('operational_banner.maintenance_body'),
      moduleName,
      boxClass:
        'border-amber-200/90 bg-amber-50 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-50',
      iconClass: 'text-amber-600 dark:text-amber-400',
      titleClass: 'text-amber-900 dark:text-amber-100',
      bodyClass: 'text-amber-900/90 dark:text-amber-100/90',
    })
  }

  if (st.upgrade_notice) {
    out.push({
      icon: ExclamationTriangleIcon,
      title: t('operational_banner.upgrade_title'),
      body: t('operational_banner.upgrade_body'),
      moduleName,
      boxClass:
        'border-violet-200/90 bg-violet-50 text-violet-950 dark:border-violet-900/60 dark:bg-violet-950/45 dark:text-violet-50',
      iconClass: 'text-violet-600 dark:text-violet-400',
      titleClass: 'text-violet-900 dark:text-violet-100',
      bodyClass: 'text-violet-900/90 dark:text-violet-100/90',
    })
  }

  return out
})
</script>
