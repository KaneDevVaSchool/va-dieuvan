<template>
  <div class="mx-auto max-w-[1920px] space-y-4 pb-6 text-slate-900">
    <div class="w-full min-w-0 space-y-4">
      <!-- Header -->
      <header class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-lg font-semibold tracking-tight md:text-xl">
            {{ t('dispatcher_board.title') }}
          </h1>
          <p class="mt-0.5 text-sm text-slate-600">
            {{ t('dispatcher_board.subtitle') }}
          </p>
        </div>
        <nav
          class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-slate-500"
          aria-label="Process"
        >
          <span
            v-for="(step, i) in processSteps"
            :key="step"
            class="inline-flex items-center gap-1.5"
          >
            <span
              :class="
                step === 'Assign'
                  ? 'rounded border border-teal-300 bg-teal-50 px-2 py-0.5 font-medium text-teal-800'
                  : ''
              "
            >{{ step }}</span>
            <span v-if="i < processSteps.length - 1" class="text-slate-400">›</span>
          </span>
        </nav>
      </header>

      <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        {{ loadError }}
      </div>

      <div
        class="min-w-0 overflow-x-auto overscroll-x-contain pb-1 [-webkit-overflow-scrolling:touch] [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
      >
        <div class="flex w-max min-w-full flex-col gap-4 xl:flex-row xl:items-stretch xl:gap-6">
        <!-- Trip queue -->
        <aside
          id="dispatcher-trip-queue"
          class="flex w-full shrink-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40 xl:w-[340px] xl:max-w-[340px]"
        >
          <div class="border-b border-slate-200 p-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h2 class="text-sm font-semibold text-slate-900">
                  {{ t('dispatcher_board.queue_title') }}
                  <span class="font-normal text-slate-500">({{ queueTrips.length }})</span>
                </h2>
                <p v-if="pendingRequestsCount" class="mt-0.5 text-xs text-slate-500">
                  {{ t('dispatcher_board.pending_requests_hint', { n: pendingRequestsCount }) }}
                </p>
              </div>
              <RouterLink
                class="shrink-0 text-xs font-medium text-teal-700 hover:text-teal-800"
                to="/requests?status=pending"
              >
                {{ t('dispatcher_board.open_requests') }}
              </RouterLink>
            </div>
            <div class="mt-3 flex items-center gap-1">
              <button
                type="button"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                :aria-label="t('dispatcher_board.filter_scroll_prev')"
                @click="scrollQueueFilters(-1)"
              >
                <ChevronLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              </button>
              <div
                ref="queueFilterScrollRef"
                role="tablist"
                :aria-label="t('dispatcher_board.queue_filters_aria')"
                class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto scroll-smooth py-0.5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
              >
                <button
                  v-for="tab in queueTabs"
                  :key="tab.id"
                  type="button"
                  role="tab"
                  :aria-selected="queueFilter === tab.id"
                  class="inline-flex shrink-0 items-center gap-1 rounded-lg border px-1.5 py-1 text-left text-[10px] font-medium leading-tight shadow-sm transition sm:gap-1.5 sm:px-2 sm:text-[11px]"
                  :class="
                    queueFilter === tab.id
                      ? 'border-teal-500 bg-teal-600 text-white ring-1 ring-teal-500/30 dark:bg-teal-600'
                      : 'border-slate-200/90 bg-white text-slate-700 hover:border-teal-200/80 hover:bg-teal-50/50 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:border-teal-800/50 dark:hover:bg-slate-800'
                  "
                  @click="queueFilter = tab.id"
                >
                  <component
                    :is="queueTabIcon(tab.id)"
                    class="h-3.5 w-3.5 shrink-0 opacity-90 sm:h-4 sm:w-4"
                    aria-hidden="true"
                  />
                  <span class="max-w-[5.5rem] truncate sm:max-w-[6.5rem]">{{ tab.label }}</span>
                  <span
                    class="tabular-nums text-[9px] font-semibold opacity-90"
                    :class="queueFilter === tab.id ? 'text-teal-100' : 'text-slate-500 dark:text-slate-400'"
                  >({{ tab.count }})</span>
                </button>
              </div>
              <button
                type="button"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                :aria-label="t('dispatcher_board.filter_scroll_next')"
                @click="scrollQueueFilters(1)"
              >
                <ChevronRightIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              </button>
            </div>
            <label class="mt-3 block">
              <span class="sr-only">{{ t('dispatcher_board.search') }}</span>
              <input
                v-model="queueSearch"
                type="search"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                :placeholder="t('dispatcher_board.search_placeholder')"
              />
            </label>
          </div>
          <div
            class="min-h-[360px] flex-1 space-y-2.5 overflow-y-auto p-2.5 sm:p-3 xl:max-h-[calc(100dvh-10.5rem)]"
          >
            <div v-if="loading && !trips.length" class="py-8 text-center text-sm text-slate-500">
              {{ t('dispatcher_board.loading') }}
            </div>
            <template v-else>
              <RouterLink
                v-for="trip in filteredQueueTrips"
                :key="trip.id"
                :to="`/trips/${trip.id}`"
                class="group block overflow-hidden rounded-xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/40 to-slate-100/25 p-3 shadow-sm ring-1 ring-slate-900/[0.03] transition-all duration-150 hover:border-teal-400/35 hover:shadow-md hover:ring-teal-500/15 dark:border-slate-600/90 dark:from-slate-900/70 dark:via-slate-900/45 dark:to-slate-950/70 dark:ring-white/[0.04] dark:hover:border-teal-700/50"
                :class="tripTypeBorderClass(trip)"
              >
                <div class="relative">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                      <span
                        class="font-mono text-[11px] font-bold tabular-nums tracking-tight text-teal-700 dark:text-teal-400"
                        >#{{ trip.id }}</span>
                      <span
                        class="inline-flex max-w-full items-center truncate rounded-full border border-slate-200/80 bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm dark:border-slate-600 dark:bg-slate-800/90 dark:text-slate-200"
                      >
                        {{ labelTripType(tripType(trip)) }}
                      </span>
                      <span
                        v-if="
                          dr(trip)?.is_urgent &&
                          (!queueBadge(trip) ||
                            queueBadge(trip).text !== t('dispatcher_board.badge_urgent'))
                        "
                        class="shrink-0 rounded-full bg-rose-500/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-500/25 dark:bg-rose-950/50 dark:text-rose-200"
                      >
                        {{ t('dispatcher_board.badge_urgent') }}
                      </span>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                      <span
                        v-if="queueBadge(trip)"
                        class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                        :class="queueBadge(trip).class"
                      >
                        {{ queueBadge(trip).text }}
                      </span>
                      <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400">{{
                        labelTripStatus(trip.status)
                      }}</span>
                    </div>
                  </div>

                  <p
                    class="mt-2 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100"
                  >
                    {{ tripTitle(trip) }}
                  </p>

                  <div
                    class="mt-2.5 grid grid-cols-2 gap-2 rounded-lg border border-slate-200/70 bg-slate-50/80 px-2 py-1.5 dark:border-slate-600/60 dark:bg-slate-950/40"
                  >
                    <div class="min-w-0 border-r border-slate-200/70 pr-2 dark:border-slate-600/70">
                      <div
                        class="text-[9px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                      >
                        {{ t('dispatcher_board.queue_label_pickup') }}
                      </div>
                      <div class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ fmtTime(trip.depart_at) }}
                      </div>
                      <div class="truncate text-[11px] leading-snug text-slate-600 dark:text-slate-300">
                        {{ origin(trip) || '—' }}
                      </div>
                    </div>
                    <div class="min-w-0 pl-0.5">
                      <div
                        class="text-[9px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                      >
                        {{ t('dispatcher_board.queue_label_arrive') }}
                      </div>
                      <div class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ fmtArrive(trip) }}
                      </div>
                      <div class="truncate text-[11px] leading-snug text-slate-600 dark:text-slate-300">
                        {{ dest(trip) || '—' }}
                      </div>
                    </div>
                  </div>

                  <div
                    class="mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-slate-200/70 pt-2 text-[10px] leading-snug text-slate-600 dark:border-slate-600/70 dark:text-slate-400"
                  >
                    <span v-if="queuePassengerCount(trip) != null" class="tabular-nums">
                      {{ t('dispatcher_board.queue_passengers', { n: queuePassengerCount(trip) }) }}
                    </span>
                    <span
                      v-if="queueRequestId(trip)"
                      class="rounded bg-slate-200/80 px-1.5 py-px font-mono font-medium text-slate-800 dark:bg-slate-700/80 dark:text-slate-200"
                    >
                      {{ t('dispatcher_board.queue_request_ref', { id: queueRequestId(trip) }) }}
                    </span>
                    <span
                      v-if="queueDriverLine(trip)"
                      class="min-w-0 truncate font-medium text-slate-700 dark:text-slate-300"
                      :title="queueDriverLine(trip)"
                    >{{ queueDriverLine(trip) }}</span>
                    <span
                      v-else
                      class="font-medium text-amber-800 dark:text-amber-300/90"
                    >{{ t('dispatcher_board.row_unassigned') }}</span>
                  </div>
                </div>
              </RouterLink>
              <p v-if="!filteredQueueTrips.length" class="py-6 text-center text-sm text-slate-500">
                {{ t('dispatcher_board.queue_empty') }}
              </p>
            </template>
          </div>
        </aside>

        <section
          :class="[
            'w-full shrink-0 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-md shadow-slate-500/[0.07] ring-1 ring-slate-100/90 min-w-[min(100%,520px)] dark:border-slate-700/80 dark:bg-slate-900/40 dark:shadow-none dark:ring-slate-800/80',
            sidebarCollapsed
              ? 'xl:flex-1 xl:min-w-[990px] xl:max-w-none'
              : 'xl:min-w-[990px] xl:w-[990px] xl:max-w-[990px]',
          ]"
        >
          <div class="space-y-3 border-b border-slate-100/90 bg-gradient-to-r from-teal-50/85 via-white to-sky-50/55 px-4 py-3 dark:from-teal-950/35 dark:via-slate-900 dark:to-sky-950/25 dark:border-slate-700/80">
            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="shiftDay(-1)"
              >
                ‹
              </button>
              <span class="min-w-[10rem] text-center text-sm font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ dayTitle }}
              </span>
              <button
                type="button"
                class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="shiftDay(1)"
              >
                ›
              </button>
              <button
                type="button"
                class="rounded-lg border border-teal-200/90 bg-teal-50 px-2.5 py-1.5 text-xs font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/60 dark:text-teal-100 dark:hover:bg-teal-900/50"
                @click="goToday"
              >
                {{ t('dispatcher_board.today') }}
              </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div
                class="flex flex-wrap items-center gap-2"
                role="list"
                :aria-label="t('dispatcher_board.legend_aria')"
              >
                <span role="listitem" class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50/90 px-2.5 py-1 text-[11px] font-medium text-emerald-900 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100">
                  <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500 shadow-sm shadow-emerald-600/40" />
                  {{ t('dispatcher_board.legend_assigned') }}
                </span>
                <span role="listitem" class="inline-flex items-center gap-1.5 rounded-full border border-sky-200/80 bg-sky-50/90 px-2.5 py-1 text-[11px] font-medium text-sky-900 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/40 dark:text-sky-100">
                  <span class="h-2 w-2 shrink-0 rounded-full bg-sky-500 shadow-sm shadow-sky-600/40" />
                  {{ t('dispatcher_board.legend_progress') }}
                </span>
                <span role="listitem" class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/80 bg-rose-50/90 px-2.5 py-1 text-[11px] font-medium text-rose-900 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100">
                  <span class="h-2 w-2 shrink-0 rounded-full bg-rose-500 shadow-sm shadow-rose-600/40" />
                  {{ t('dispatcher_board.legend_conflict') }}
                </span>
              </div>
              <button
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-teal-200 hover:bg-teal-50/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                :title="t('dispatcher_board.bulk_assign_hint')"
                @click="goBulkAssign"
              >
                <Square2StackIcon class="h-4 w-4 text-slate-600" aria-hidden="true" />
                {{ t('dispatcher_board.bulk_assign') }}
              </button>
            </div>
            <div
              class="flex flex-col gap-2 border-t border-slate-200/80 pt-3 dark:border-slate-700/70"
              role="group"
              :aria-label="t('dispatcher_board.service_types')"
            >
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('dispatcher_board.service_types') }}
                </span>
                <span
                  v-for="item in tripTypeLegend"
                  :key="item.type"
                  class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/90 bg-white/90 px-2 py-0.5 text-[10px] font-medium text-slate-800 shadow-sm dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-100"
                >
                  <span class="h-2 w-2 shrink-0 rounded-full shadow-sm" :class="item.dot" />
                  {{ item.label }}
                </span>
              </div>
              <p class="text-[10px] leading-relaxed text-slate-500 dark:text-slate-400">
                {{ t('dispatcher_board.stripe_hint') }}
              </p>
              <p class="text-[10px] leading-relaxed text-slate-500 dark:text-slate-400">
                {{ t('dispatcher_board.ui_upgrade_hint') }}
              </p>
            </div>
          </div>

          <div
            class="overflow-x-auto overscroll-x-contain [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
          >
            <div
              class="w-full min-w-[990px] bg-gradient-to-b from-slate-50/40 to-white p-3 dark:from-slate-950/50 dark:to-slate-900/30"
            >
              <!-- Hour labels -->
              <div class="mb-1.5 flex text-[10px] font-medium text-slate-500 dark:text-slate-400">
                <div class="w-[160px] shrink-0" />
                <div class="grid min-w-0 flex-1" :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }">
                  <div
                    v-for="h in hourSlots"
                    :key="h"
                    class="border-l border-slate-200/90 pl-1.5 text-left tabular-nums first:border-l-0 dark:border-slate-600/80"
                  >
                    {{ String(h).padStart(2, '0') }}:00
                  </div>
                </div>
              </div>

              <div
                v-for="(row, rowIdx) in timelineRows"
                :key="row.key"
                class="flex border-b border-slate-100/90 last:border-b-0 dark:border-slate-700/60"
              >
                <div
                  class="flex w-[160px] shrink-0 flex-col justify-center border-r border-slate-200/90 bg-white/60 py-2.5 pr-2.5 text-xs dark:border-slate-700/80 dark:bg-slate-900/30"
                >
                  <span class="truncate font-medium text-slate-800 dark:text-slate-100">{{ row.label }}</span>
                  <span v-if="row.sub" class="truncate text-[10px] text-rose-600">{{ row.sub }}</span>
                  <span v-else-if="row.meta" class="truncate text-[10px] text-slate-500 dark:text-slate-400">{{ row.meta }}</span>
                </div>
                <div class="relative min-h-[64px] min-w-0 flex-1 bg-slate-50/60 dark:bg-slate-950/20">
                  <div
                    class="pointer-events-none absolute inset-0 grid"
                    :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }"
                  >
                    <div
                      v-for="h in hourSlots"
                      :key="`g-${row.key}-${h}`"
                      class="border-l border-slate-200/70 first:border-l-0 dark:border-slate-700/50"
                    />
                  </div>
                  <!-- now line (once per scroll area — duplicate on each row for alignment) -->
                  <div
                    v-if="nowLinePct !== null"
                    class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-teal-500"
                    :style="{ left: `${nowLinePct}%` }"
                  >
                    <span
                      v-if="rowIdx === 0"
                      class="absolute -top-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md border border-slate-200/90 bg-white px-1.5 py-0.5 text-[9px] font-semibold text-slate-700 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                    >{{ nowLabel }}</span>
                  </div>
                  <RouterLink
                    v-for="bar in row.bars"
                    :key="bar.trip.id"
                    :to="`/trips/${bar.trip.id}`"
                    class="group absolute top-2 z-[5] flex min-h-[2.75rem] items-start gap-1 overflow-hidden rounded-lg border px-2 py-1.5 pl-2 text-left shadow-md transition-all duration-150 hover:z-[6] hover:-translate-y-px hover:shadow-lg hover:brightness-[0.98] focus-visible:z-[6] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/45 dark:hover:brightness-110"
                    :class="bar.toneClass"
                    :style="{ left: `${bar.left}%`, width: `max(${bar.width}%, ${MIN_TIMELINE_BAR_PCT}%)` }"
                    :title="`${labelTripType(tripType(bar.trip))} · ${tripTitle(bar.trip)} · ${fmtTime(bar.trip.depart_at)} · ${labelTripStatus(bar.trip.status)}`"
                  >
                    <span
                      class="pointer-events-none absolute bottom-0 left-0 top-0 w-1 rounded-l-lg shadow-[2px_0_8px_-2px_rgba(0,0,0,0.12)] dark:shadow-[2px_0_8px_-2px_rgba(0,0,0,0.4)]"
                      :class="tripTypeStripeClass(bar.trip)"
                      aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1 pl-1">
                      <span class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
                        <span class="text-[11px] font-bold tabular-nums tracking-tight text-slate-900 dark:text-white">#{{ bar.trip.id }}</span>
                        <span
                          class="max-w-[9rem] truncate rounded-full border border-black/[0.04] bg-white/75 px-1.5 py-0.5 text-[9px] font-semibold leading-none text-slate-700 backdrop-blur-[2px] dark:border-white/10 dark:bg-slate-950/40 dark:text-slate-100"
                        >{{ labelTripType(tripType(bar.trip)) }}</span>
                        <span
                          class="text-[9px] font-medium tabular-nums text-slate-500 dark:text-slate-400"
                        >{{ fmtTime(bar.trip.depart_at) }}</span>
                      </span>
                      <span
                        class="mt-1 block line-clamp-2 text-[10px] font-medium leading-snug text-slate-600 dark:text-slate-300"
                      >{{ tripTitle(bar.trip) }}</span>
                    </span>
                    <span
                      v-if="bar.conflict"
                      class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[9px] font-bold text-white shadow-sm"
                    >!</span>
                  </RouterLink>
                </div>
              </div>

              <p v-if="!timelineRows.length && !loading" class="py-8 text-center text-sm text-slate-500">
                {{ t('dispatcher_board.timeline_empty') }}
              </p>
            </div>
          </div>
        </section>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowsRightLeftIcon,
  BoltIcon,
  BriefcaseIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  CubeIcon,
  QueueListIcon,
  Square2StackIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { listTrips } from '../../api/trips'
import { listRequests } from '../../api/requests'
import { labelTripStatus, labelTripType } from '../../util/labels'
import { shortViDayLabel, toLocalDateKey } from '../../util/dates'
import { useUiStore } from '../../store/ui'

const { t } = useI18n()
const router = useRouter()

const { sidebarCollapsed } = storeToRefs(useUiStore())

const GRID_START = 6
const GRID_END = 22
const PROCESS = ['Request', 'Review', 'Approve', 'Assign', 'Execute', 'Complete']

const processSteps = PROCESS
const hourSlots = []
for (let h = GRID_START; h < GRID_END; h++) hourSlots.push(h)

const loading = ref(false)
const loadError = ref('')
const trips = ref([])
const pendingRequestsCount = ref(0)
const selectedDate = ref(new Date())
const queueFilter = ref('all')
const queueSearch = ref('')
const queueFilterScrollRef = ref(null)

function scrollQueueFilters(direction) {
  const el = queueFilterScrollRef.value
  if (!el) return
  const step = Math.min(Math.max(el.clientWidth * 0.72, 200), 360)
  el.scrollBy({ left: direction * step, behavior: 'smooth' })
}

const QUEUE_TAB_ICONS = {
  all: QueueListIcon,
  urgent: BoltIcon,
  door_to_door: UserGroupIcon,
  point_to_point: ArrowsRightLeftIcon,
  business: BriefcaseIcon,
  cargo: CubeIcon,
}

function queueTabIcon(id) {
  return QUEUE_TAB_ICONS[id] ?? QueueListIcon
}

const queueTabs = computed(() => {
  const q = queueTrips.value
  return [
    { id: 'all', label: t('dispatcher_board.tab_all'), count: q.length },
    {
      id: 'urgent',
      label: t('dispatcher_board.tab_urgent'),
      count: q.filter((x) => dr(x)?.is_urgent).length,
    },
    {
      id: 'door_to_door',
      label: t('dispatcher_board.tab_d2d'),
      count: q.filter((x) => tripType(x) === 'door_to_door').length,
    },
    {
      id: 'point_to_point',
      label: t('dispatcher_board.tab_p2p'),
      count: q.filter((x) => tripType(x) === 'point_to_point').length,
    },
    {
      id: 'business',
      label: t('dispatcher_board.tab_business'),
      count: q.filter((x) => tripType(x) === 'business').length,
    },
    {
      id: 'cargo',
      label: t('dispatcher_board.tab_cargo'),
      count: q.filter((x) => tripType(x) === 'cargo').length,
    },
  ]
})

const tripTypeLegend = computed(() => [
  { type: 'door_to_door', label: labelTripType('door_to_door'), dot: 'bg-violet-500 shadow-sm shadow-violet-600/35' },
  { type: 'point_to_point', label: labelTripType('point_to_point'), dot: 'bg-cyan-500 shadow-sm shadow-cyan-600/35' },
  { type: 'business', label: labelTripType('business'), dot: 'bg-amber-500 shadow-sm shadow-amber-600/35' },
  { type: 'cargo', label: labelTripType('cargo'), dot: 'bg-teal-600 shadow-sm shadow-teal-600/35' },
])

const dayKey = computed(() => toLocalDateKey(selectedDate.value))

const dayTitle = computed(() => {
  const k = dayKey.value
  const today = toLocalDateKey(new Date())
  if (k === today) return `${t('dispatcher_board.today')}, ${shortViDayLabel(k)}`
  return shortViDayLabel(k)
})

function dr(trip) {
  return trip.dispatch_request ?? trip.dispatchRequest
}

function tripType(trip) {
  return dr(trip)?.trip_type ?? ''
}

function tripTypeStripeClass(trip) {
  const m = {
    door_to_door: 'bg-violet-500',
    point_to_point: 'bg-cyan-500',
    business: 'bg-amber-500',
    cargo: 'bg-teal-600',
  }
  return m[tripType(trip)] ?? 'bg-slate-400'
}

function tripTypeBorderClass(trip) {
  const m = {
    door_to_door: 'border-l-4 border-l-violet-500',
    point_to_point: 'border-l-4 border-l-cyan-500',
    business: 'border-l-4 border-l-amber-500',
    cargo: 'border-l-4 border-l-teal-600',
  }
  return m[tripType(trip)] ?? 'border-l-4 border-l-slate-400'
}

function origin(trip) {
  return dr(trip)?.origin ?? ''
}

function dest(trip) {
  return dr(trip)?.destination ?? ''
}

function tripTitle(trip) {
  const a = origin(trip)
  const b = dest(trip)
  if (a && b) return `${a} → ${b}`
  return a || b || t('dispatcher_board.untitled_trip')
}

function queueRequestId(trip) {
  const id = dr(trip)?.id
  return id != null ? id : null
}

function queuePassengerCount(trip) {
  const raw = dr(trip)?.passenger_count
  if (raw == null || raw === '') return null
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : null
}

function queueDriverLine(trip) {
  const name = trip.driver?.full_name?.trim()
  const plate = trip.vehicle?.license_plate?.trim()
  if (name && plate) return `${name} · ${plate}`
  if (name) return name
  if (plate) return plate
  return ''
}

function fmtTime(v) {
  if (!v) return '—'
  return new Date(v).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function fmtArrive(trip) {
  const r = dr(trip)
  const ab = r?.arrive_by ?? trip.arrive_by
  return ab ? fmtTime(ab) : '—'
}

/** Chuyến cần phân công / xử lý tiếp */
const queueTrips = computed(() => {
  return trips.value.filter((x) => ['pending', 'approved'].includes(x.status))
})

const filteredQueueTrips = computed(() => {
  let list = queueTrips.value
  const f = queueFilter.value
  if (f === 'urgent') list = list.filter((x) => dr(x)?.is_urgent)
  else if (f === 'cargo') list = list.filter((x) => tripType(x) === 'cargo')
  else if (f === 'door_to_door') list = list.filter((x) => tripType(x) === 'door_to_door')
  else if (f === 'point_to_point') list = list.filter((x) => tripType(x) === 'point_to_point')
  else if (f === 'business') list = list.filter((x) => tripType(x) === 'business')

  const q = queueSearch.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((x) => {
    const idStr = String(x.id)
    const hay = [idStr, origin(x), dest(x), tripTitle(x)].join(' ').toLowerCase()
    return hay.includes(q)
  })
})

function queueBadge(trip) {
  const r = dr(trip)
  if (r?.status === 'approved') {
    return { text: t('dispatcher_board.badge_approved'), class: 'bg-sky-100 text-sky-800' }
  }
  if (r?.is_urgent) {
    return { text: t('dispatcher_board.badge_urgent'), class: 'bg-rose-100 text-rose-800' }
  }
  return null
}

function hourValue(d) {
  const x = new Date(d)
  return x.getHours() + x.getMinutes() / 60 + x.getSeconds() / 3600
}

function tripEnd(trip) {
  const r = dr(trip)
  const end = r?.arrive_by ?? trip.arrive_by
  if (end) return new Date(end)
  const s = new Date(trip.depart_at)
  return new Date(s.getTime() + 60 * 60 * 1000)
}

/** Đủ rộng để hiển thị mã chuyến, giờ và tuyến */
const MIN_TIMELINE_BAR_PCT = 15

function pctRange(trip) {
  const start = hourValue(trip.depart_at)
  const end = hourValue(tripEnd(trip))
  const span = GRID_END - GRID_START
  const left = ((Math.max(GRID_START, start) - GRID_START) / span) * 100
  const right = ((Math.min(GRID_END, end) - GRID_START) / span) * 100
  const width = Math.max(right - left, MIN_TIMELINE_BAR_PCT)
  return { left, width: Math.min(width, 100 - left) }
}

function barTone(trip, conflict) {
  if (conflict) {
    return [
      'border-rose-200/95',
      'bg-gradient-to-br from-rose-50 via-white to-rose-100/70',
      'text-rose-950 dark:border-rose-800/70 dark:from-rose-950/45 dark:via-rose-950/25 dark:to-rose-900/50 dark:text-rose-50',
    ].join(' ')
  }
  if (trip.status === 'in_progress') {
    return [
      'border-sky-200/95',
      'bg-gradient-to-br from-sky-50 via-white to-sky-100/60',
      'text-sky-950 dark:border-sky-800/65 dark:from-sky-950/40 dark:via-slate-900/35 dark:to-sky-900/45 dark:text-sky-100',
    ].join(' ')
  }
  if (['assigned', 'driver_confirmed'].includes(trip.status)) {
    return [
      'border-emerald-200/95',
      'bg-gradient-to-br from-emerald-50 via-white to-emerald-100/60',
      'text-emerald-950 dark:border-emerald-800/65 dark:from-emerald-950/38 dark:via-slate-900/30 dark:to-emerald-900/42 dark:text-emerald-50',
    ].join(' ')
  }
  return [
    'border-slate-200/90',
    'bg-gradient-to-br from-white via-slate-50/90 to-slate-100/80',
    'text-slate-800 dark:border-slate-600/75 dark:from-slate-800/80 dark:via-slate-900/55 dark:to-slate-900/85 dark:text-slate-100',
  ].join(' ')
}

/** Cặp chuyến trùng giờ trên cùng tài xế */
function computeConflicts(tripList) {
  const byDriver = new Map()
  for (const trip of tripList) {
    const id = trip.driver_id ?? 0
    if (!byDriver.has(id)) byDriver.set(id, [])
    byDriver.get(id).push(trip)
  }
  const conflictIds = new Set()
  for (const group of byDriver.values()) {
    const sorted = [...group].sort((a, b) => new Date(a.depart_at) - new Date(b.depart_at))
    for (let i = 0; i < sorted.length; i++) {
      for (let j = i + 1; j < sorted.length; j++) {
        const a = sorted[i]
        const b = sorted[j]
        const aEnd = tripEnd(a).getTime()
        const bStart = new Date(b.depart_at).getTime()
        if (bStart < aEnd) {
          conflictIds.add(a.id)
          conflictIds.add(b.id)
        } else {
          break
        }
      }
    }
  }
  return conflictIds
}

/** Trên lịch: chỉ chuyến còn vận hành (ẩn đã xong / huỷ / hàng đợi phân công). */
const timelineTrips = computed(() =>
  trips.value.filter((x) => !['pending', 'approved', 'cancelled', 'completed'].includes(x.status)),
)

const conflictIds = computed(() => computeConflicts(timelineTrips.value))

const timelineRows = computed(() => {
  const list = timelineTrips.value
  const byDriver = new Map()
  const unassigned = []

  for (const trip of list) {
    if (!trip.driver_id) {
      unassigned.push(trip)
      continue
    }
    const name = trip.driver?.full_name ?? `#${trip.driver_id}`
    const plate = trip.vehicle?.license_plate
    const key = `d-${trip.driver_id}`
    if (!byDriver.has(key)) {
      byDriver.set(key, {
        key,
        label: name,
        meta: plate ?? '',
        trips: [],
      })
    }
    byDriver.get(key).trips.push(trip)
  }

  const rows = []
  if (unassigned.length) {
    rows.push({
      key: 'unassigned',
      label: t('dispatcher_board.row_unassigned'),
      sub: '',
      meta: '',
      trips: unassigned,
    })
  }
  for (const r of byDriver.values()) {
    rows.push({
      key: r.key,
      label: r.label,
      sub: '',
      meta: r.meta,
      trips: r.trips,
    })
  }

  for (const row of rows) {
    row.bars = row.trips.map((trip) => {
      const { left, width } = pctRange(trip)
      const conflict = conflictIds.value.has(trip.id)
      return {
        trip,
        left,
        width,
        conflict,
        toneClass: barTone(trip, conflict),
      }
    })
  }

  return rows
})

const nowLinePct = computed(() => {
  const today = toLocalDateKey(new Date())
  if (dayKey.value !== today) return null
  const now = hourValue(new Date())
  if (now < GRID_START || now > GRID_END) return null
  const span = GRID_END - GRID_START
  return ((now - GRID_START) / span) * 100
})

const nowLabel = computed(() =>
  new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
)

function shiftDay(delta) {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() + delta)
  selectedDate.value = d
}

function goToday() {
  selectedDate.value = new Date()
}

/** Mở danh sách chuyến (lọc chờ xử lý) để gán xe / tài xế từng chuyến — API bulk chưa có. */
function goBulkAssign() {
  router.push({ path: '/trips', query: { status: 'pending' } })
}

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const k = dayKey.value
    const [tr, req] = await Promise.all([
      listTrips({ from: k, to: k, per_page: 100, page: 1 }),
      listRequests({ status: 'pending', per_page: 1, page: 1 }),
    ])
    trips.value = tr.items ?? []
    pendingRequestsCount.value = req.meta?.total ?? 0
  } catch (e) {
    loadError.value = e?.response?.data?.message ?? t('dispatcher_board.load_error')
    trips.value = []
  } finally {
    loading.value = false
  }
}

watch(dayKey, () => {
  load()
})

onMounted(load)
</script>
