<template>
  <div class="space-y-4 md:space-y-5">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
      {{ loadError }}
    </div>

    <div class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
            {{ t('dashboard_analytics.title') }}
          </h1>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            {{ t('dashboard_analytics.subtitle') }}
          </p>
        </div>
        <RouterLink
          class="shrink-0 text-sm font-medium text-teal-700 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-300"
          :to="staffPath('/reports')"
        >
          {{ t('dashboard_analytics.reports_link') }} →
        </RouterLink>
      </div>

      <!-- Truy cập nhanh: mũi tên hai bên, ẩn thanh cuộn -->
      <div>
        <h2
          class="inline-flex items-center gap-1.5 px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
          :title="t('dashboard_analytics.quick_section_tooltip')"
        >
          {{ t('dashboard_analytics.quick_title') }}
          <InformationCircleIcon class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
        </h2>
        <div class="relative -mx-0.5 mt-2 flex items-stretch gap-1 sm:-mx-1 sm:gap-2">
          <button
            type="button"
            class="flex h-auto min-h-[5.5rem] w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-teal-200/70 hover:bg-white hover:text-teal-700 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-teal-800 dark:hover:text-teal-400 sm:w-9"
            :disabled="!quickCanScrollLeft"
            :aria-label="t('dashboard_analytics.quick_scroll_prev')"
            @click="scrollQuickLinks(-1)"
          >
            <ChevronLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </button>
          <div
            ref="quickScrollRef"
            class="dash-quick-scroll min-h-[5.5rem] min-w-0 flex-1 overflow-x-auto overflow-y-hidden scroll-smooth"
            @scroll.passive="updateQuickScrollState"
          >
            <div class="flex h-full flex-nowrap items-stretch gap-2 px-0.5 py-0.5 sm:gap-3">
              <RouterLink
                v-for="item in quickLinks"
                :key="item.to"
                :to="item.to"
                :title="item.hint"
                :class="[
                  'flex w-[6.5rem] shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border bg-gradient-to-b px-2.5 py-3.5 text-center shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md sm:w-32 md:w-36',
                  item.cardClass,
                ]"
              >
                <component :is="item.icon" :class="['h-7 w-7 shrink-0 sm:h-8 sm:w-8', item.iconClass]" aria-hidden="true" />
                <span class="w-full text-center line-clamp-2 text-[11px] font-medium leading-tight sm:text-xs" :class="item.labelClass">
                  {{ item.title }}
                </span>
              </RouterLink>
            </div>
          </div>
          <button
            type="button"
            class="flex h-auto min-h-[5.5rem] w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-teal-200/70 hover:bg-white hover:text-teal-700 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-teal-800 dark:hover:text-teal-400 sm:w-9"
            :disabled="!quickCanScrollRight"
            :aria-label="t('dashboard_analytics.quick_scroll_next')"
            @click="scrollQuickLinks(1)"
          >
            <ChevronRightIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </button>
        </div>
      </div>

      <TransportReportFilters />
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300 dark:bg-slate-600" />
      {{ t('dashboard_analytics.loading') }}
    </div>

    <section class="space-y-3" aria-labelledby="dash-section-kpis">
      <h2
        id="dash-section-kpis"
        class="inline-flex items-center gap-1.5 px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
        :title="t('dashboard_analytics.section_kpis_tooltip')"
      >
        {{ t('dashboard_analytics.section_kpis') }}
        <InformationCircleIcon class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
      </h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card
        :title="t('dashboard_analytics.kpi_trips_title')"
        :hint="t('dashboard_analytics.kpi_trips_tooltip')"
        class="border-teal-200/90 bg-gradient-to-br from-teal-50/90 via-white to-white shadow-md shadow-teal-900/[0.04] ring-1 ring-teal-900/[0.04] dark:border-teal-900/35 dark:from-teal-950/35 dark:via-slate-900/90 dark:to-slate-900/80 dark:shadow-none dark:ring-teal-900/20"
      >
        <div class="text-2xl font-bold tabular-nums text-teal-800 dark:text-teal-200 md:text-3xl">{{ totalTrips }}</div>
        <p class="mt-0.5 text-[11px] text-teal-900/70 dark:text-teal-300/80">{{ t('dashboard_analytics.kpi_trips_sub') }}</p>
        <div class="mt-2 max-h-24 space-y-1 overflow-y-auto border-t border-teal-100/90 pt-2 text-[11px] text-slate-600 dark:border-teal-900/40 dark:text-slate-300 md:max-h-28">
          <div v-for="row in tripStatusRows" :key="row.key" class="flex justify-between gap-2 tabular-nums">
            <span class="truncate text-slate-500 dark:text-slate-400">{{ row.label }}</span>
            <span class="shrink-0 font-medium text-slate-900 dark:text-slate-100">{{ row.n }}</span>
          </div>
          <div v-if="!tripStatusRows.length" class="text-slate-400 dark:text-slate-500">—</div>
        </div>
      </Card>

      <Card
        :title="t('dashboard_analytics.kpi_cost_title')"
        :hint="t('dashboard_analytics.kpi_cost_tooltip')"
        class="border-amber-200/90 bg-gradient-to-br from-amber-50/90 via-white to-white shadow-md shadow-amber-900/[0.04] ring-1 ring-amber-900/[0.04] dark:border-amber-900/35 dark:from-amber-950/30 dark:via-slate-900/90 dark:to-slate-900/80 dark:shadow-none dark:ring-amber-900/20"
      >
        <div class="text-xl font-bold tabular-nums text-amber-800 dark:text-amber-200 md:text-2xl">{{ formatMoney(totalConfirmedCost) }}</div>
        <p class="mt-0.5 text-[11px] text-amber-900/70 dark:text-amber-300/80">{{ t('dashboard_analytics.kpi_cost_sub') }}</p>
      </Card>

      <Card
        :title="t('dashboard_analytics.kpi_sla_title')"
        :hint="t('dashboard_analytics.kpi_sla_tooltip')"
        class="border-rose-200/90 bg-gradient-to-br from-rose-50/90 via-white to-white shadow-md shadow-rose-900/[0.04] ring-1 ring-rose-900/[0.04] dark:border-rose-900/35 dark:from-rose-950/30 dark:via-slate-900/90 dark:to-slate-900/80 dark:shadow-none dark:ring-rose-900/20"
      >
        <div class="text-xl font-bold tabular-nums text-rose-600 dark:text-rose-400 md:text-2xl">{{ summary?.cargo_sla_breaches ?? 0 }}</div>
        <p class="mt-0.5 text-[11px] text-rose-900/70 dark:text-rose-300/80">{{ t('dashboard_analytics.kpi_sla_sub') }}</p>
      </Card>

      <Card
        :title="t('dashboard_analytics.kpi_providers_title')"
        :hint="t('dashboard_analytics.kpi_providers_tooltip')"
        class="border-violet-200/90 bg-gradient-to-br from-violet-50/90 via-white to-white shadow-md shadow-violet-900/[0.04] ring-1 ring-violet-900/[0.04] dark:border-violet-900/35 dark:from-violet-950/30 dark:via-slate-900/90 dark:to-slate-900/80 dark:shadow-none dark:ring-violet-900/20"
      >
        <div class="truncate text-base font-semibold text-violet-900 dark:text-violet-200 md:text-lg">{{ topProviderName }}</div>
        <p class="mt-0.5 text-[11px] text-violet-800/80 dark:text-violet-300/80">{{ t('dashboard_analytics.kpi_providers_sub') }}</p>
        <p v-if="topProviderAmount" class="mt-1.5 text-sm font-medium tabular-nums text-violet-800 dark:text-violet-300">
          {{ formatMoney(topProviderAmount) }}
        </p>
      </Card>
    </div>

    <!-- KPI hàng 2 -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <Card
        :title="t('dashboard_analytics.kpi_completion_title')"
        :hint="t('dashboard_analytics.kpi_completion_tooltip')"
        class="border-emerald-200/90 bg-gradient-to-br from-emerald-50/90 via-white to-white shadow-md shadow-emerald-900/[0.04] ring-1 ring-emerald-900/[0.04] dark:border-emerald-900/35 dark:from-emerald-950/30 dark:via-slate-900/90 dark:to-slate-900/80 dark:shadow-none dark:ring-emerald-900/20"
      >
        <div class="text-xl font-bold tabular-nums text-emerald-800 dark:text-emerald-200 md:text-2xl">
          <template v-if="completionRate != null">{{ completionRate }}%</template>
          <template v-else>—</template>
        </div>
        <p class="mt-0.5 text-[11px] text-emerald-900/70 dark:text-emerald-300/80">
          {{ completedTrips }} / {{ totalTripsInRange }} · {{ t('dashboard_analytics.kpi_completion_sub') }}
        </p>
      </Card>
      <Card
        :title="t('dashboard_analytics.kpi_distance_title')"
        :hint="t('dashboard_analytics.kpi_distance_tooltip')"
        class="border-sky-200/90 bg-gradient-to-br from-sky-50/90 via-white to-white shadow-md shadow-sky-900/[0.04] ring-1 ring-sky-900/[0.04] dark:border-sky-900/35 dark:from-sky-950/30 dark:via-slate-900/90 dark:to-slate-900/80 dark:shadow-none dark:ring-sky-900/20"
      >
        <div class="text-xl font-bold tabular-nums text-sky-800 dark:text-sky-200 md:text-2xl">
          {{ formatDistanceKm(summary?.trip_records_distance_km) }}
        </div>
        <p class="mt-0.5 text-[11px] text-sky-900/70 dark:text-sky-300/80">{{ t('dashboard_analytics.kpi_distance_sub') }}</p>
      </Card>
    </div>
    </section>

    <section class="border-t border-slate-200/80 pt-6 md:pt-7 dark:border-slate-800" aria-labelledby="dash-section-compliance">
    <!-- Tuân thủ xe -->
    <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50/40 to-teal-50/25 p-3 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-teal-950/20 dark:ring-white/[0.04] md:p-4">
      <h2
        id="dash-section-compliance"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-white"
        :title="t('dashboard_analytics.section_compliance_tooltip')"
      >
        {{ t('dashboard_analytics.section_compliance') }}
        <InformationCircleIcon class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
      </h2>
      <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="box in complianceBoxes"
          :key="box.key"
          :title="box.tooltip"
          class="rounded-lg border border-slate-100/90 bg-white/90 px-3 py-2.5 shadow-sm ring-1 ring-slate-900/[0.02] dark:border-slate-700 dark:bg-slate-950/50 dark:ring-white/[0.04]"
          :class="box.cardTone"
        >
          <div class="text-xs font-semibold" :class="box.titleClass">{{ box.title }}</div>
          <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] tabular-nums">
            <span class="text-rose-600 dark:text-rose-400">{{ t('dashboard_analytics.compliance_overdue') }}: {{ box.overdue }}</span>
            <span v-if="box.soon != null" class="text-amber-700 dark:text-amber-400">{{ t('dashboard_analytics.compliance_due_30d') }}: {{ box.soon }}</span>
            <span v-if="box.stale != null" class="text-slate-600 dark:text-slate-400">{{ box.staleLabel }}: {{ box.stale }}</span>
          </div>
        </div>
      </div>
    </div>
    </section>

    <section class="border-t border-slate-200/80 pt-6 md:pt-7 dark:border-slate-800" aria-labelledby="dash-section-recent">
      <h2 id="dash-section-recent" class="mb-3 px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.section_recent_activity') }}
      </h2>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ t('dashboard_analytics.section_recent_trips') }}
          </h3>
          <RouterLink
            class="text-xs font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400"
            :to="staffPath('/trips')"
          >
            {{ t('dashboard_analytics.recent_see_all') }} →
          </RouterLink>
        </div>
        <div v-if="recentTripsBusy && !recentTrips.length" class="space-y-2" aria-busy="true">
          <div
            v-for="s in 5"
            :key="'tskel-' + s"
            class="h-[4.25rem] animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/80"
          />
        </div>
        <div v-else-if="recentTrips.length" class="space-y-2">
          <RouterLink
            v-for="tr in recentTrips"
            :key="tr.id"
            :to="staffPath(`/trips/${tr.id}`)"
            :class="[
              'group flex gap-3 rounded-xl border p-3 transition',
              'border-slate-100 bg-gradient-to-br from-white to-slate-50/90 shadow-sm ring-1 ring-slate-900/[0.03]',
              'hover:border-teal-200/90 hover:shadow-md hover:ring-teal-500/10',
              'dark:border-slate-700/90 dark:from-slate-900 dark:to-slate-900/80 dark:ring-white/[0.04]',
              'dark:hover:border-teal-800/60',
              recentTripsBusy ? 'pointer-events-none opacity-55' : '',
            ]"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-100/95 text-teal-700 shadow-inner dark:bg-teal-950/55 dark:text-teal-300"
              aria-hidden="true"
            >
              <TruckIcon class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="font-mono text-[11px] font-semibold tabular-nums text-slate-400 dark:text-slate-500">
                      TRP-{{ String(tr.id).padStart(4, '0') }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                      <ArrowsRightLeftIcon class="h-3.5 w-3.5 opacity-70" aria-hidden="true" />
                      {{ t('dashboard_analytics.recent_col_route') }}
                    </span>
                  </div>
                  <p
                    class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-900 group-hover:text-teal-800 dark:text-slate-50 dark:group-hover:text-teal-300"
                  >
                    <span class="text-slate-800 dark:text-slate-100">{{ tr.dispatch_request?.origin ?? '—' }}</span>
                    <span class="mx-1 text-teal-500 dark:text-teal-500/90">→</span>
                    <span class="text-slate-800 dark:text-slate-100">{{ tr.dispatch_request?.destination ?? '—' }}</span>
                  </p>
                  <p class="mt-1 flex items-center gap-1.5 text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
                    <ClockIcon class="h-3.5 w-3.5 shrink-0 opacity-80" aria-hidden="true" />
                    <span>{{ t('dashboard_analytics.recent_col_when') }}: {{ formatDepartShort(tr.depart_at) }}</span>
                  </p>
                </div>
                <span
                  :class="[
                    'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold leading-none',
                    tripStatusPillClass(tr.status),
                  ]"
                >
                  {{ labelTripStatus(tr.status) }}
                </span>
              </div>
            </div>
          </RouterLink>
          <div
            v-if="(recentTripsMeta.last_page ?? 1) > 1"
            class="flex flex-col gap-2 border-t border-slate-100 pt-3 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
              {{
                t('dashboard_analytics.recent_page_range', {
                  from: recentTripsPageFrom,
                  to: recentTripsPageTo,
                  total: recentTripsMeta.total ?? 0,
                })
              }}
            </p>
            <div class="flex flex-wrap items-center justify-end gap-1">
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-35 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
                :disabled="recentTripsBusy || (recentTripsMeta.current_page ?? 1) <= 1"
                :aria-label="t('trips_page.prev')"
                @click="goRecentTripsPage((recentTripsMeta.current_page ?? 1) - 1)"
              >
                <ChevronLeftIcon class="h-4 w-4" />
              </button>
              <button
                v-for="n in recentTripsPageNumbers"
                :key="'rtp-' + n"
                type="button"
                :class="[
                  'h-8 min-w-[2rem] rounded-lg px-2 text-xs font-semibold tabular-nums transition',
                  n === (recentTripsMeta.current_page ?? 1)
                    ? 'bg-teal-600 text-white shadow-sm dark:bg-teal-600'
                    : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
                ]"
                :disabled="recentTripsBusy"
                @click="goRecentTripsPage(n)"
              >
                {{ n }}
              </button>
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-35 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
                :disabled="recentTripsBusy || (recentTripsMeta.current_page ?? 1) >= (recentTripsMeta.last_page ?? 1)"
                :aria-label="t('trips_page.next')"
                @click="goRecentTripsPage((recentTripsMeta.current_page ?? 1) + 1)"
              >
                <ChevronRightIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.recent_empty') }}</p>
      </div>
      <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ t('dashboard_analytics.section_recent_requests') }}
          </h3>
          <RouterLink
            class="text-xs font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400"
            :to="staffPath('/requests')"
          >
            {{ t('dashboard_analytics.recent_see_all') }} →
          </RouterLink>
        </div>
        <div v-if="recentRequestsBusy && !recentRequests.length" class="space-y-2" aria-busy="true">
          <div
            v-for="s in 5"
            :key="'rskel-' + s"
            class="h-[4.25rem] animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/80"
          />
        </div>
        <div v-else-if="recentRequests.length" class="space-y-2">
          <RouterLink
            v-for="rq in recentRequests"
            :key="rq.id"
            :to="staffPath(`/requests/${rq.id}`)"
            :class="[
              'group flex gap-3 rounded-xl border p-3 transition',
              'border-slate-100 bg-gradient-to-br from-white to-violet-50/40 shadow-sm ring-1 ring-slate-900/[0.03]',
              'hover:border-violet-200/90 hover:shadow-md hover:ring-violet-500/10',
              'dark:border-slate-700/90 dark:from-slate-900 dark:to-violet-950/25 dark:ring-white/[0.04]',
              'dark:hover:border-violet-800/50',
              recentRequestsBusy ? 'pointer-events-none opacity-55' : '',
            ]"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100/95 text-violet-700 shadow-inner dark:bg-violet-950/50 dark:text-violet-300"
              aria-hidden="true"
            >
              <ClipboardDocumentListIcon class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="font-mono text-[11px] font-semibold tabular-nums text-slate-400 dark:text-slate-500">
                      #{{ rq.id }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                      <ArrowsRightLeftIcon class="h-3.5 w-3.5 opacity-70" aria-hidden="true" />
                      {{ t('dashboard_analytics.recent_col_route') }}
                    </span>
                  </div>
                  <p
                    class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-900 group-hover:text-violet-800 dark:text-slate-50 dark:group-hover:text-violet-300"
                  >
                    <span class="text-slate-800 dark:text-slate-100">{{ rq.origin ?? '—' }}</span>
                    <span class="mx-1 text-violet-500 dark:text-violet-400">→</span>
                    <span class="text-slate-800 dark:text-slate-100">{{ rq.destination ?? '—' }}</span>
                  </p>
                  <p class="mt-1 flex items-center gap-1.5 text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
                    <ClockIcon class="h-3.5 w-3.5 shrink-0 opacity-80" aria-hidden="true" />
                    <span>{{ t('dashboard_analytics.recent_col_when') }}: {{ formatDepartShort(rq.depart_at) }}</span>
                  </p>
                </div>
                <span
                  :class="[
                    'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold leading-none',
                    requestStatusPillClass(rq.status),
                  ]"
                >
                  {{ labelRequestStatus(rq.status) }}
                </span>
              </div>
            </div>
          </RouterLink>
          <div
            v-if="(recentRequestsMeta.last_page ?? 1) > 1"
            class="flex flex-col gap-2 border-t border-slate-100 pt-3 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
              {{
                t('dashboard_analytics.recent_page_range', {
                  from: recentRequestsPageFrom,
                  to: recentRequestsPageTo,
                  total: recentRequestsMeta.total ?? 0,
                })
              }}
            </p>
            <div class="flex flex-wrap items-center justify-end gap-1">
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-35 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
                :disabled="recentRequestsBusy || (recentRequestsMeta.current_page ?? 1) <= 1"
                :aria-label="t('trips_page.prev')"
                @click="goRecentRequestsPage((recentRequestsMeta.current_page ?? 1) - 1)"
              >
                <ChevronLeftIcon class="h-4 w-4" />
              </button>
              <button
                v-for="n in recentRequestsPageNumbers"
                :key="'rrp-' + n"
                type="button"
                :class="[
                  'h-8 min-w-[2rem] rounded-lg px-2 text-xs font-semibold tabular-nums transition',
                  n === (recentRequestsMeta.current_page ?? 1)
                    ? 'bg-violet-600 text-white shadow-sm dark:bg-violet-600'
                    : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
                ]"
                :disabled="recentRequestsBusy"
                @click="goRecentRequestsPage(n)"
              >
                {{ n }}
              </button>
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-35 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
                :disabled="recentRequestsBusy || (recentRequestsMeta.current_page ?? 1) >= (recentRequestsMeta.last_page ?? 1)"
                :aria-label="t('trips_page.next')"
                @click="goRecentRequestsPage((recentRequestsMeta.current_page ?? 1) + 1)"
              >
                <ChevronRightIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.recent_empty') }}</p>
      </div>
    </div>
    </section>
  </div>
</template>

<script setup>
import { computed, markRaw, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowsRightLeftIcon,
  BanknotesIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeIcon,
  DocumentMagnifyingGlassIcon,
  InformationCircleIcon,
  PlusCircleIcon,
  Square2StackIcon,
  TableCellsIcon,
  TruckIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import Card from '../components/ui/Card.vue'
import TransportReportFilters from '../components/reports/TransportReportFilters.vue'
import { useTransportReportSummary } from '../composables/useTransportReportSummary'
import { listTrips } from '../api/trips'
import { listRequests, normalizeRequestListParams } from '../api/requests'
import { labelTripStatus, labelRequestStatus } from '../util/labels'
import { buildStaffPrefixedPath as staffPath } from '../config/dispatchWebBase'

const { t } = useI18n()

const {
  loading,
  loadError,
  summary,
  summaryFilters,
  formatMoney,
  totalTrips,
  totalConfirmedCost,
  topProviderName,
  topProviderAmount,
  completionRate,
  completedTrips,
  totalTripsInRange,
  rangeValid,
  reloadSummary,
} = useTransportReportSummary()

const quickScrollRef = ref(null)
const quickCanScrollLeft = ref(false)
const quickCanScrollRight = ref(false)

const RECENT_PAGE_SIZE = 5

const recentTrips = ref([])
const recentRequests = ref([])
const recentTripsPage = ref(1)
const recentRequestsPage = ref(1)
const recentTripsMeta = ref({})
const recentRequestsMeta = ref({})
const recentTripsBusy = ref(false)
const recentRequestsBusy = ref(false)

function updateQuickScrollState() {
  const el = quickScrollRef.value
  if (!el) {
    quickCanScrollLeft.value = false
    quickCanScrollRight.value = false
    return
  }
  const { scrollLeft, scrollWidth, clientWidth } = el
  quickCanScrollLeft.value = scrollLeft > 2
  quickCanScrollRight.value = scrollLeft + clientWidth < scrollWidth - 2
}

function scrollQuickLinks(direction) {
  const el = quickScrollRef.value
  if (!el) return
  const step = Math.max(160, Math.floor(el.clientWidth * 0.82))
  el.scrollBy({ left: direction * step, behavior: 'smooth' })
}

const quickLinks = computed(() => [
  {
    to: staffPath('/dispatcher'),
    title: t('dashboard_analytics.quick_dispatcher'),
    hint: t('dashboard_analytics.quick_dispatcher_tooltip'),
    icon: markRaw(Square2StackIcon),
    cardClass:
      'border-teal-200/90 from-teal-50/95 to-white ring-teal-900/[0.06] hover:border-teal-300 dark:border-teal-800/60 dark:from-teal-950/40 dark:to-slate-900/85 dark:ring-teal-900/25 dark:hover:border-teal-700',
    iconClass: 'text-teal-600 dark:text-teal-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/trips'),
    hint: t('dashboard_analytics.quick_trips_tooltip'),
    icon: markRaw(TruckIcon),
    cardClass:
      'border-sky-200/90 from-sky-50/95 to-white ring-sky-900/[0.06] hover:border-sky-300 dark:border-sky-800/55 dark:from-sky-950/35 dark:to-slate-900/85 dark:ring-sky-900/25 dark:hover:border-sky-700',
    iconClass: 'text-sky-600 dark:text-sky-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/dispatch-requests/new'),
    title: t('dashboard_analytics.quick_new_request'),
    hint: t('dashboard_analytics.quick_new_request_tooltip'),
    icon: markRaw(PlusCircleIcon),
    cardClass:
      'border-emerald-200/90 from-emerald-50/95 to-white ring-emerald-900/[0.06] hover:border-emerald-300 dark:border-emerald-800/55 dark:from-emerald-950/35 dark:to-slate-900/85 dark:ring-emerald-900/25 dark:hover:border-emerald-700',
    iconClass: 'text-emerald-600 dark:text-emerald-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/requests'),
    hint: t('dashboard_analytics.quick_requests_tooltip'),
    icon: markRaw(ClipboardDocumentListIcon),
    cardClass:
      'border-violet-200/90 from-violet-50/95 to-white ring-violet-900/[0.06] hover:border-violet-300 dark:border-violet-800/55 dark:from-violet-950/35 dark:to-slate-900/85 dark:ring-violet-900/25 dark:hover:border-violet-700',
    iconClass: 'text-violet-600 dark:text-violet-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/resources/list'),
    title: t('dashboard_analytics.quick_resources'),
    hint: t('dashboard_analytics.quick_resources_tooltip'),
    icon: markRaw(UserGroupIcon),
    cardClass:
      'border-indigo-200/90 from-indigo-50/95 to-white ring-indigo-900/[0.06] hover:border-indigo-300 dark:border-indigo-800/55 dark:from-indigo-950/35 dark:to-slate-900/85 dark:ring-indigo-900/25 dark:hover:border-indigo-700',
    iconClass: 'text-indigo-600 dark:text-indigo-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/cargo'),
    title: t('dashboard_analytics.quick_cargo'),
    hint: t('dashboard_analytics.quick_cargo_tooltip'),
    icon: markRaw(CubeIcon),
    cardClass:
      'border-amber-200/90 from-amber-50/95 to-white ring-amber-900/[0.06] hover:border-amber-300 dark:border-amber-800/55 dark:from-amber-950/35 dark:to-slate-900/85 dark:ring-amber-900/25 dark:hover:border-amber-700',
    iconClass: 'text-amber-600 dark:text-amber-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/costs'),
    hint: t('dashboard_analytics.quick_costs_tooltip'),
    icon: markRaw(BanknotesIcon),
    cardClass:
      'border-rose-200/90 from-rose-50/95 to-white ring-rose-900/[0.06] hover:border-rose-300 dark:border-rose-800/55 dark:from-rose-950/35 dark:to-slate-900/85 dark:ring-rose-900/25 dark:hover:border-rose-700',
    iconClass: 'text-rose-600 dark:text-rose-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/pricing'),
    title: t('dashboard_analytics.quick_pricing'),
    hint: t('dashboard_analytics.quick_pricing_tooltip'),
    icon: markRaw(TableCellsIcon),
    cardClass:
      'border-cyan-200/90 from-cyan-50/95 to-white ring-cyan-900/[0.06] hover:border-cyan-300 dark:border-cyan-800/55 dark:from-cyan-950/35 dark:to-slate-900/85 dark:ring-cyan-900/25 dark:hover:border-cyan-700',
    iconClass: 'text-cyan-600 dark:text-cyan-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/audit-logs'),
    title: t('dashboard_analytics.quick_audit'),
    hint: t('dashboard_analytics.quick_audit_tooltip'),
    icon: markRaw(DocumentMagnifyingGlassIcon),
    cardClass:
      'border-slate-300/90 from-slate-100/90 to-white ring-slate-900/[0.05] hover:border-slate-400 dark:border-slate-600 dark:from-slate-800/50 dark:to-slate-900/85 dark:ring-slate-900/30 dark:hover:border-slate-500',
    iconClass: 'text-slate-600 dark:text-slate-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
])

const tripListQuery = computed(() => {
  const o = { ...summaryFilters.value }
  if (o.trip_status) {
    o.status = o.trip_status
    delete o.trip_status
  }
  return o
})

const requestListQuery = computed(() => {
  const o = { ...summaryFilters.value }
  delete o.fleet_mode
  return o
})

const tripStatusRows = computed(() => {
  const raw = summary.value?.trips_by_status ?? {}
  return Object.entries(raw)
    .map(([key, v]) => ({
      key,
      label: labelTripStatus(key),
      n: Number(v ?? 0),
    }))
    .filter((r) => r.n > 0)
    .sort((a, b) => b.n - a.n)
})

const complianceBoxes = computed(() => {
  const vc = summary.value?.vehicle_compliance ?? {}
  const ins = vc.inspection ?? {}
  const insu = vc.insurance ?? {}
  const road = vc.road_fee ?? {}
  const maint = vc.maintenance ?? {}
  return [
    {
      key: 'insp',
      title: t('dashboard_analytics.compliance_inspection'),
      tooltip: t('dashboard_analytics.compliance_inspection_tooltip'),
      cardTone: 'border-l-4 border-l-teal-500 bg-gradient-to-r from-teal-50/80 to-white dark:from-teal-950/35 dark:to-slate-950/50',
      titleClass: 'text-teal-900 dark:text-teal-200',
      overdue: ins.overdue ?? 0,
      soon: ins.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'insu',
      title: t('dashboard_analytics.compliance_insurance'),
      tooltip: t('dashboard_analytics.compliance_insurance_tooltip'),
      cardTone: 'border-l-4 border-l-sky-500 bg-gradient-to-r from-sky-50/80 to-white dark:from-sky-950/35 dark:to-slate-950/50',
      titleClass: 'text-sky-900 dark:text-sky-200',
      overdue: insu.overdue ?? 0,
      soon: insu.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'road',
      title: t('dashboard_analytics.compliance_road_fee'),
      tooltip: t('dashboard_analytics.compliance_road_fee_tooltip'),
      cardTone: 'border-l-4 border-l-amber-500 bg-gradient-to-r from-amber-50/80 to-white dark:from-amber-950/30 dark:to-slate-950/50',
      titleClass: 'text-amber-900 dark:text-amber-200',
      overdue: road.overdue ?? 0,
      soon: road.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'maint',
      title: t('dashboard_analytics.compliance_maintenance'),
      tooltip: t('dashboard_analytics.compliance_maintenance_tooltip'),
      cardTone: 'border-l-4 border-l-violet-500 bg-gradient-to-r from-violet-50/80 to-white dark:from-violet-950/30 dark:to-slate-950/50',
      titleClass: 'text-violet-900 dark:text-violet-200',
      overdue: 0,
      soon: null,
      stale: maint.no_recent_service_180d ?? 0,
      staleLabel: t('dashboard_analytics.compliance_maint_stale'),
    },
  ]
})

function formatDistanceKm(v) {
  const n = Number(v ?? 0)
  if (!Number.isFinite(n) || n <= 0) return '—'
  return `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Math.round(n))} km`
}

function formatDepartShort(s) {
  if (s == null || s === '') return '—'
  return String(s).replace('T', ' ').slice(0, 16)
}

function tripStatusPillClass(s) {
  const map = {
    pending: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
    approved: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
    assigned: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200',
    driver_confirmed: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    in_progress: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
    completed: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    incident: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}

function requestStatusPillClass(s) {
  const map = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    pending: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    approved: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    cancelled: 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}

const recentTripsPageFrom = computed(() => {
  const m = recentTripsMeta.value
  const total = m.total ?? 0
  if (total <= 0) return 0
  const cur = m.current_page ?? 1
  const pp = m.per_page ?? RECENT_PAGE_SIZE
  return (cur - 1) * pp + 1
})

const recentTripsPageTo = computed(() => {
  const m = recentTripsMeta.value
  const total = m.total ?? 0
  if (total <= 0) return 0
  const cur = m.current_page ?? 1
  const pp = m.per_page ?? RECENT_PAGE_SIZE
  return Math.min(cur * pp, total)
})

const recentTripsPageNumbers = computed(() => {
  const m = recentTripsMeta.value
  const last = m.last_page ?? 1
  const cur = m.current_page ?? 1
  const delta = 1
  const start = Math.max(1, cur - delta)
  const end = Math.min(last, cur + delta)
  const pages = []
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

const recentRequestsPageFrom = computed(() => {
  const m = recentRequestsMeta.value
  const total = m.total ?? 0
  if (total <= 0) return 0
  const cur = m.current_page ?? 1
  const pp = m.per_page ?? RECENT_PAGE_SIZE
  return (cur - 1) * pp + 1
})

const recentRequestsPageTo = computed(() => {
  const m = recentRequestsMeta.value
  const total = m.total ?? 0
  if (total <= 0) return 0
  const cur = m.current_page ?? 1
  const pp = m.per_page ?? RECENT_PAGE_SIZE
  return Math.min(cur * pp, total)
})

const recentRequestsPageNumbers = computed(() => {
  const m = recentRequestsMeta.value
  const last = m.last_page ?? 1
  const cur = m.current_page ?? 1
  const delta = 1
  const start = Math.max(1, cur - delta)
  const end = Math.min(last, cur + delta)
  const pages = []
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function fetchRecentTrips() {
  if (!rangeValid.value) return
  recentTripsBusy.value = true
  try {
    const res = await listTrips({
      ...tripListQuery.value,
      per_page: RECENT_PAGE_SIZE,
      page: recentTripsPage.value,
    })
    recentTrips.value = res.items ?? []
    recentTripsMeta.value = res.meta ?? {}
  } catch {
    recentTrips.value = []
    recentTripsMeta.value = {}
  } finally {
    recentTripsBusy.value = false
  }
}

async function fetchRecentRequests() {
  if (!rangeValid.value) return
  recentRequestsBusy.value = true
  try {
    const res = await listRequests(
      normalizeRequestListParams({
        ...requestListQuery.value,
        per_page: RECENT_PAGE_SIZE,
        page: recentRequestsPage.value,
      }),
    )
    recentRequests.value = res.items ?? []
    recentRequestsMeta.value = res.meta ?? {}
  } catch {
    recentRequests.value = []
    recentRequestsMeta.value = {}
  } finally {
    recentRequestsBusy.value = false
  }
}

async function goRecentTripsPage(n) {
  const last = recentTripsMeta.value.last_page ?? 1
  if (n < 1 || n > last) return
  recentTripsPage.value = n
  await fetchRecentTrips()
}

async function goRecentRequestsPage(n) {
  const last = recentRequestsMeta.value.last_page ?? 1
  if (n < 1 || n > last) return
  recentRequestsPage.value = n
  await fetchRecentRequests()
}

async function loadRecentLists() {
  if (!rangeValid.value) return
  recentTripsPage.value = 1
  recentRequestsPage.value = 1
  await Promise.all([fetchRecentTrips(), fetchRecentRequests()])
}

watch(
  summary,
  () => {
    loadRecentLists()
  },
  { deep: true },
)

function onDashboardResize() {
  updateQuickScrollState()
}

onMounted(() => {
  reloadSummary()
  window.addEventListener('resize', onDashboardResize)
  nextTick(() => updateQuickScrollState())
})

onUnmounted(() => {
  window.removeEventListener('resize', onDashboardResize)
})
</script>

<style scoped>
.dash-quick-scroll {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.dash-quick-scroll::-webkit-scrollbar {
  display: none;
}
</style>
