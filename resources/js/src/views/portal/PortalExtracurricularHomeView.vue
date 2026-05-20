<template>
  <div>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
          <RouterLink
            :to="{ name: 'portalHome' }"
            class="text-xs font-semibold text-indigo-600 underline-offset-2 hover:underline"
          >
            ← {{ t('portal.back_dashboard') }}
          </RouterLink>
          <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
            {{ t('portal.extracurricular_module.heading') }}
          </h1>
          <p class="mt-2 max-w-2xl text-sm text-slate-600">
            {{ t('portal.extracurricular_module.lead') }}
          </p>
        </div>
        <RouterLink
          :to="{ name: 'portalExtracurricularCreate' }"
          class="inline-flex min-h-[48px] shrink-0 items-center justify-center gap-2 rounded-2xl bg-violet-600 px-6 text-sm font-bold text-white shadow-md transition hover:bg-violet-700"
        >
          <PlusCircleIcon class="h-5 w-5" aria-hidden="true" />
          {{ t('portal.extracurricular_module.cta_create') }}
        </RouterLink>
      </div>

      <div class="mt-10 grid gap-4 sm:grid-cols-2">
        <RouterLink
          :to="{ name: 'portalExtracurricularList' }"
          class="group flex min-h-[120px] flex-col justify-between rounded-3xl border border-violet-200/80 bg-gradient-to-br from-violet-50/90 via-white to-indigo-50/40 p-6 shadow-sm transition hover:border-violet-300 hover:shadow-md"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-md shadow-violet-500/25">
            <ListBulletIcon class="h-6 w-6" aria-hidden="true" />
          </span>
          <span>
            <span class="mt-4 block text-lg font-bold text-slate-900">
              {{ t('portal.extracurricular_module.card_list_title') }}
            </span>
            <span class="mt-1 block text-sm text-slate-600">
              {{ t('portal.extracurricular_module.card_list_hint') }}
            </span>
          </span>
        </RouterLink>
        <RouterLink
          :to="{ name: 'portalExtracurricularCreate' }"
          class="group flex min-h-[120px] flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md">
            <PlusCircleIcon class="h-6 w-6" aria-hidden="true" />
          </span>
          <span>
            <span class="mt-4 block text-lg font-bold text-slate-900">
              {{ t('portal.extracurricular_module.card_create_title') }}
            </span>
            <span class="mt-1 block text-sm text-slate-600">
              {{ t('portal.extracurricular_module.card_create_hint') }}
            </span>
          </span>
        </RouterLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ListBulletIcon, PlusCircleIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()
</script>
