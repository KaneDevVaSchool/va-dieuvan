<template>
  <div
    class="driver-trip-detail mx-auto flex min-h-full w-full max-w-lg flex-col bg-driver-bg text-driver-ink sm:pb-0"
  >
    <!-- ─── Dark header ────────────────────────────────────── -->
    <div class="relative isolate shrink-0 bg-gradient-to-b from-[#020B0B] to-[#031818] px-4 pb-5 pt-3 text-driver-ink shadow-[0_10px_40px_-8px_rgba(34,211,238,0.12)] ring-1 ring-[#7fdcc8]/10 sm:rounded-b-3xl">
      <!-- Row 1: back / title / menu -->
      <div class="flex items-center justify-between gap-2">
        <RouterLink
          :to="{ name: 'driverSchedule' }"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-driver-ink ring-1 ring-white/20 active:bg-white/20"
          :aria-label="t('driver_trip_detail.back')"
        >
          <ArrowLeftIcon class="h-5 w-5" />
        </RouterLink>
        <h1 class="flex-1 text-center text-base font-bold tracking-tight">
          {{ trip ? t('driver_trip_detail.header_title', { id: trip.id }) : t('driver_trip_detail.title') }}
        </h1>
        <div class="relative" ref="moreRoot">
          <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 active:bg-white/20"
            :aria-label="t('driver_trip_detail.more')"
            @click="moreOpen = !moreOpen"
          >
            <EllipsisVerticalIcon class="h-5 w-5" />
          </button>
          <div
            v-show="moreOpen"
            class="absolute right-0 top-11 z-20 min-w-[10rem] overflow-hidden rounded-xl border border-white/15 bg-driver-card py-1 text-left text-sm shadow-lg ring-1 ring-white/10"
          >
            <button
              type="button"
              class="w-full px-3 py-2.5 text-left text-driver-ink/90 hover:bg-white/10"
              @click="refresh(); moreOpen = false"
            >
              {{ t('driver_trip_detail.action_refresh') }}
            </button>
            <a
              v-if="dispatcherPhone"
              :href="`tel:${dispatcherPhone}`"
              class="block px-3 py-2.5 text-driver-ink/90 hover:bg-white/10"
              @click="moreOpen = false"
            >
              {{ t('driver_trip_detail.call_dispatcher') }}
            </a>
          </div>
        </div>
      </div>

      <template v-if="trip">
        <!-- Row 2: status badge -->
        <div class="mt-3 flex justify-center">
          <span
            class="rounded-full px-4 py-1 text-sm font-semibold"
            :class="statusBadgeClass"
          >{{ headerStatusText }}</span>
        </div>

        <!-- Row 3: date-time -->
        <p class="mt-2 text-center text-sm text-driver-muted/80">{{ formattedDateLine }}</p>

        <!-- Row 4: stats strip -->
        <div class="mt-3 grid grid-cols-3 divide-x divide-white/15 rounded-2xl bg-white/10 py-3">
          <div class="flex flex-col items-center gap-0.5 px-2">
            <UserGroupIcon class="h-5 w-5 text-[#7fdcc8]" />
            <span class="text-[10px] text-driver-muted/80">{{ t('driver_trip_detail.stats_students') }}</span>
            <span class="text-base font-bold tabular-nums text-driver-ink">{{ statsStudentCount }}</span>
          </div>
          <div class="flex flex-col items-center gap-0.5 px-2">
            <MapPinIcon class="h-5 w-5 text-[#7fdcc8]" />
            <span class="text-[10px] text-driver-muted/80">{{ t('driver_trip_detail.stats_distance') }}</span>
            <span class="text-base font-bold text-driver-ink">{{ statsDistance }}</span>
          </div>
          <div class="flex flex-col items-center gap-0.5 px-2">
            <ClockIcon class="h-5 w-5 text-[#7fdcc8]" />
            <span class="text-[10px] text-driver-muted/80">{{ t('driver_trip_detail.stats_duration') }}</span>
            <span class="text-base font-bold text-driver-ink">{{ statsDuration }}</span>
          </div>
        </div>
      </template>

      <!-- Warning banner -->
      <div
        v-if="warningBanner"
        class="mt-3 flex gap-3 rounded-2xl border border-amber-400/50 bg-gradient-to-r from-amber-500/95 to-amber-600/90 px-3 py-3 text-amber-950 shadow-md"
      >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/30">
          <ClockIcon class="h-5 w-5 text-amber-950" />
        </div>
        <div class="min-w-0">
          <p class="text-sm font-bold">{{ t('driver_trip_detail.warn_title', { n: warningBanner.minutes }) }}</p>
          <p class="mt-0.5 text-xs leading-snug text-amber-950/90">{{ warningBanner.body }}</p>
        </div>
      </div>
    </div>

    <!-- ─── Scrollable content ─────────────────────────────── -->
    <div class="relative z-10 -mt-3 flex-1 space-y-3 px-3 pb-44 sm:px-0 sm:pb-12">
      <p
        v-if="loadError"
        class="mt-3 rounded-xl border border-amber-700/50 bg-amber-950/40 px-3 py-2 text-xs text-amber-100 ring-1 ring-amber-600/30"
      >{{ loadError }}</p>

      <div v-if="loading && !trip" class="mt-3 space-y-3">
        <div class="h-40 animate-pulse rounded-2xl bg-driver-surface" />
        <div class="h-48 animate-pulse rounded-2xl bg-driver-surface" />
        <div class="h-32 animate-pulse rounded-2xl bg-driver-surface" />
      </div>

      <template v-else-if="trip">

        <!-- 1. Route accordion -->
        <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
          <button
            type="button"
            class="flex w-full items-center gap-2.5 px-4 py-3.5 text-left"
            @click="routeExpanded = !routeExpanded"
          >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#7fdcc8]/20">
              <MapIcon class="h-4 w-4 text-driver-accent" />
            </div>
            <span class="flex-1 text-sm font-bold text-driver-ink">{{ t('driver_trip_detail.route_section') }}</span>
            <ChevronDownIcon
              class="h-5 w-5 text-driver-muted/80 transition-transform"
              :class="routeExpanded ? 'rotate-180' : ''"
            />
          </button>

          <div v-show="routeExpanded" class="px-4 pb-4">
            <!-- Start point -->
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
                <MapPinIcon class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1 pt-0.5">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-driver-muted/80">{{ t('driver_trip_detail.point_start') }}</p>
                <p class="text-sm font-bold text-driver-ink">{{ originMain }}</p>
                <p v-if="originSub" class="text-xs text-driver-muted">{{ originSub }}</p>
                <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-[#7fdcc8]/20 px-2.5 py-0.5 text-[11px] font-semibold text-driver-accent">
                  <CheckCircleIcon class="h-3.5 w-3.5" />
                  {{ t('driver_trip_detail.route_start_ready') }}
                </span>
              </div>
            </div>

            <!-- Connector: stops count -->
            <div class="my-1 ml-5 flex items-center gap-2">
              <div class="flex h-full w-0 flex-col items-center">
                <div class="h-10 w-px border-l-2 border-dashed border-white/10" />
              </div>
              <span
                v-if="paxKind === 'student' && paxList.length > 0"
                class="ml-1.5 rounded-full border border-white/10 bg-driver-surface px-3 py-0.5 text-[11px] font-semibold text-driver-muted"
              >
                <UserGroupIcon class="mr-1 inline h-3.5 w-3.5 text-driver-muted/80" />
                {{ t('driver_trip_detail.route_stops', { n: paxList.length }) }}
              </span>
            </div>

            <!-- End point -->
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm">
                <FlagIcon class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1 pt-0.5">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-driver-muted/80">{{ t('driver_trip_detail.point_end') }}</p>
                <p class="text-sm font-bold text-driver-ink">{{ destMain }}</p>
                <p v-if="destSub" class="text-xs text-driver-muted">{{ destSub }}</p>
                <p class="mt-1 flex items-center gap-1 text-xs text-driver-muted">
                  <ClockIcon class="h-3.5 w-3.5" />
                  {{ t('driver_trip_detail.planned', { t: timeHm(trip.arrive_by || dr?.arrive_by) }) }}
                </p>
              </div>
            </div>

            <!-- Map button -->
            <a
              v-if="mapUrl"
              :href="mapUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-sky-600 bg-sky-600 py-2.5 text-sm font-bold text-white transition active:opacity-80"
            >
              <MapIcon class="h-4 w-4" />
              {{ t('driver_trip_detail.route_map_btn') }}
            </a>
          </div>
        </div>

        <!-- 2. Student list (D2D / P2P) -->
        <template v-if="paxKind === 'student'">
          <div v-if="paxList.length" class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
            <!-- Header -->
            <div class="flex items-center justify-between gap-2 px-4 py-3.5">
              <h2 class="flex items-center gap-2 text-sm font-bold text-driver-ink">
                <UserGroupIcon class="h-4 w-4 text-driver-muted/80" />
                {{ t('driver_trip_detail.students_title', { n: displayedPaxList.length }) }}
              </h2>
              <div class="flex gap-1.5">
                <button
                  type="button"
                  class="flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold transition"
                  :class="studentFilterStatus !== 'all' ? 'border-[#7fdcc8] bg-[#7fdcc8]/10 text-driver-accent' : 'border-white/10 bg-driver-surface text-driver-muted'"
                  @click="cycleFilter"
                >
                  <FunnelIcon class="h-3.5 w-3.5" />
                  {{ t('driver_trip_detail.filter_btn') }}
                  <span v-if="studentFilterStatus !== 'all'" class="ml-0.5 font-bold text-driver-accent">·</span>
                </button>
                <button
                  type="button"
                  class="flex items-center gap-1 rounded-full border border-white/10 bg-driver-surface px-3 py-1 text-xs font-semibold text-driver-muted transition"
                  @click="cycleSort"
                >
                  <ArrowsUpDownIcon class="h-3.5 w-3.5" />
                  {{ t('driver_trip_detail.sort_btn') }}
                </button>
              </div>
            </div>

            <!-- Student cards -->
            <ul class="divide-y divide-white/10">
              <li
                v-for="p in displayedPaxList"
                :key="p._origIndex"
                class="transition"
                :class="isPaused ? 'opacity-50' : ''"
              >
                <!-- Main row -->
                <div
                  class="flex cursor-pointer items-start gap-3 px-4 py-3"
                  @click="toggleStudent(p._origIndex)"
                >
                  <!-- Avatar with number badge -->
                  <div class="relative shrink-0">
                    <div
                      class="flex h-12 w-12 items-center justify-center rounded-full bg-driver-elevated text-sm font-bold text-driver-muted"
                    >
                      {{ studentInitials(p.name) }}
                    </div>
                    <!-- Number badge -->
                    <div
                      class="absolute -left-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white text-[10px] font-bold"
                      :class="rowState(p._origIndex) === 'picked_up' ? 'bg-emerald-500 text-white' : rowState(p._origIndex) === 'absent' ? 'bg-driver-muted/80 text-driver-ink' : 'bg-driver-accent/20 text-driver-ink'"
                    >
                      {{ p._origIndex + 1 }}
                    </div>
                    <!-- Picked check -->
                    <div
                      v-if="rowState(p._origIndex) === 'picked_up'"
                      class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white"
                    >
                      <CheckIcon class="h-3.5 w-3.5" />
                    </div>
                  </div>

                  <!-- Info -->
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <span class="font-semibold text-driver-ink">{{ p.name }}</span>
                      <span
                        v-if="isNextIndex(p._origIndex) && canMarkPickup"
                        class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700"
                      >{{ t('driver_trip_detail.tag_next') }}</span>
                    </div>
                    <p class="mt-0.5 text-xs text-driver-muted">{{ p.subtitle }}</p>
                    <p v-if="p.address" class="mt-0.5 flex items-center gap-1 text-xs text-driver-muted/80">
                      <MapPinIcon class="h-3 w-3 shrink-0" />
                      {{ p.address }}
                    </p>
                  </div>

                  <!-- Right: status + time + chevron -->
                  <div class="flex shrink-0 flex-col items-end gap-1">
                    <span
                      class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                      :class="rowState(p._origIndex) === 'picked_up' ? 'bg-emerald-500/20 text-emerald-300' : rowState(p._origIndex) === 'absent' ? 'bg-driver-elevated text-driver-muted' : 'bg-amber-500/20 text-amber-200'"
                    >
                      {{ rowState(p._origIndex) === 'picked_up' ? t('driver_trip_detail.state_picked') : rowState(p._origIndex) === 'absent' ? t('driver_trip_detail.btn_absent') : t('driver_trip_detail.status_waiting') }}
                    </span>
                    <span v-if="p.time" class="text-[11px] font-semibold tabular-nums text-driver-muted">{{ p.time }}</span>
                    <ChevronDownIcon
                      class="h-4 w-4 text-driver-muted/60 transition-transform"
                      :class="expandedStudentIdx === p._origIndex ? 'rotate-180' : ''"
                    />
                  </div>
                </div>

                <!-- Expanded: action buttons -->
                <div
                  v-if="expandedStudentIdx === p._origIndex"
                  class="flex gap-2 border-t border-white/[0.06] bg-driver-surface/50 px-4 py-3"
                >
                  <a
                    v-if="p.phone"
                    :href="`tel:${p.phone}`"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-500 py-2.5 text-sm font-bold text-white active:opacity-80"
                  >
                    <PhoneIcon class="h-4 w-4" />
                    {{ t('driver_trip_detail.call') }}
                  </a>
                  <button
                    v-if="canMarkPickup && rowState(p._origIndex) !== 'picked_up'"
                    type="button"
                    :disabled="eventPosting"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-[#7fdcc8] py-2.5 text-sm font-bold text-driver-bg disabled:opacity-50 active:opacity-80"
                    @click.stop="setRowState(p._origIndex, 'picked_up')"
                  >
                    <CheckIcon class="h-4 w-4" />
                    {{ t('driver_trip_detail.btn_picked') }}
                  </button>
                  <button
                    v-if="canMarkPickup && rowState(p._origIndex) !== 'absent'"
                    type="button"
                    :disabled="eventPosting"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-driver-surface py-2.5 text-sm font-semibold text-driver-muted disabled:opacity-50 active:opacity-80"
                    @click.stop="setRowState(p._origIndex, 'absent')"
                  >
                    {{ t('driver_trip_detail.btn_absent') }}
                  </button>
                  <button
                    type="button"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/10 bg-driver-surface py-2.5 text-sm font-semibold text-driver-muted active:bg-driver-elevated"
                  >
                    <InformationCircleIcon class="h-4 w-4" />
                    {{ t('driver_trip_detail.btn_detail') }}
                  </button>
                </div>
              </li>
            </ul>
          </div>

          <p
            v-else
            class="rounded-2xl border border-dashed border-white/10 bg-driver-surface/70 px-3 py-5 text-center text-sm text-driver-muted"
          >{{ t('driver_trip_detail.no_pax') }}</p>
        </template>

        <!-- Other pax types (cargo / business / unlisted) -->
        <div v-else-if="paxList.length" class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
          <div class="mb-2 flex items-center justify-between px-4 pt-3.5">
            <h2 class="text-sm font-bold text-driver-ink">
              {{ t('driver_trip_detail.students_title', { n: paxList.length }) }}
            </h2>
          </div>
          <ul class="divide-y divide-white/10 px-4 pb-3">
            <li
              v-for="(p, i) in paxList"
              :key="i"
              class="flex items-start gap-3 py-3"
            >
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-xs font-bold text-driver-muted">
                {{ studentInitials(p.name) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-driver-ink">{{ p.name }}</p>
                <p class="text-xs text-driver-muted">{{ p.subtitle }}</p>
              </div>
              <a
                v-if="p.phone"
                :href="`tel:${p.phone}`"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/10 bg-driver-surface text-driver-muted"
              >
                <PhoneIcon class="h-4 w-4" />
              </a>
            </li>
          </ul>
        </div>

        <!-- 3. KM row (ẩn trên PWA tài xế theo yêu cầu — hoàn tất chuyến không bắt nhập km) -->
        <button
          v-if="driverKmSectionEnabled"
          type="button"
          class="flex w-full items-center justify-between gap-2 rounded-2xl bg-driver-card px-4 py-3.5 text-left ring-1 ring-white/[0.06]"
          @click="openKmModal()"
        >
          <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-driver-muted">
              <ChartBarIcon class="h-4 w-4" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-driver-ink">{{ t('driver_trip_detail.km_row_title') }}</p>
              <p v-if="!hasEndOdometer" class="text-xs text-amber-600">{{ t('driver_trip_detail.km_end_missing') }}</p>
              <p v-else class="text-xs text-driver-muted">{{ t('driver_trip_detail.km_end_value', { km: formatKm(trip.record.end_odometer_km) }) }}</p>
            </div>
          </div>
          <ChevronRightIcon class="h-5 w-5 shrink-0 text-driver-muted/60" />
        </button>

        <!-- 4. Costs accordion -->
        <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
          <button
            type="button"
            class="flex w-full items-center gap-2.5 px-4 py-3.5 text-left"
            @click="costsExpanded = !costsExpanded"
          >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-50">
              <BanknotesIcon class="h-4 w-4 text-orange-500" />
            </div>
            <span class="flex-1 text-sm font-bold text-driver-ink">{{ t('driver_trip_detail.costs_section') }}</span>
            <span v-if="tripCosts.length" class="mr-1 text-xs font-semibold text-driver-muted">{{ formatVnd(costsTotalAmount) }}</span>
            <ChevronDownIcon
              class="h-5 w-5 text-driver-muted/80 transition-transform"
              :class="costsExpanded ? 'rotate-180' : ''"
            />
          </button>

          <div v-show="costsExpanded" class="px-4 pb-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs text-driver-muted">
                {{ tripCosts.length ? t('driver_trip_detail.costs_total', { amount: formatVnd(costsTotalAmount) }) : t('driver_trip_detail.costs_empty') }}
              </span>
              <button
                v-if="canAddCost"
                type="button"
                class="flex items-center gap-1 rounded-full bg-driver-bg px-3 py-1.5 text-xs font-bold text-white active:opacity-80"
                @click="openCostModal"
              >
                <PlusIcon class="h-3.5 w-3.5" />
                {{ t('driver_trip_detail.costs_add') }}
              </button>
            </div>
            <ul v-if="tripCosts.length" class="space-y-2">
              <li v-for="c in tripCosts" :key="c.id">
                <RouterLink
                  :to="`/driver/costs/${c.id}`"
                  class="flex items-center gap-2.5 rounded-xl border border-white/[0.06] bg-driver-surface/60 px-3 py-2.5 transition active:bg-driver-elevated"
                >
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-driver-muted ring-1 ring-white/10">
                    <FireIcon v-if="c.type === 'fuel'" class="h-4 w-4 text-orange-500" />
                    <WrenchScrewdriverIcon v-else-if="c.type === 'repair'" class="h-4 w-4 text-blue-500" />
                    <SparklesIcon v-else-if="c.type === 'wash'" class="h-4 w-4 text-sky-500" />
                    <BanknotesIcon v-else class="h-4 w-4 text-driver-muted" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-driver-ink">
                      {{ costTypeLabel(c.type) }}
                      <span v-if="c.description" class="font-normal text-driver-muted"> · {{ c.description }}</span>
                    </p>
                    <div class="mt-0.5 flex items-center gap-2">
                      <p class="text-[11px] text-driver-muted/80">{{ formatCostTime(c.created_at) }}</p>
                      <span
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                        :class="c.status === 'confirmed' ? 'bg-emerald-500/20 text-emerald-300' : c.status === 'rejected' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-200'"
                      >
                        {{ costStatusLabel(c.status) }}
                      </span>
                    </div>
                  </div>
                  <div class="flex shrink-0 items-center gap-0.5 text-sm font-bold tabular-nums text-driver-ink">
                    {{ formatVnd(c.amount) }}
                    <ChevronRightIcon class="h-4 w-4 text-driver-muted/60" />
                  </div>
                </RouterLink>
              </li>
            </ul>
            <p v-else class="py-2 text-center text-xs text-driver-muted/80">{{ t('driver_trip_detail.costs_empty') }}</p>
          </div>
        </div>

        <!-- 5. Trip notes accordion -->
        <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
          <button
            type="button"
            class="flex w-full items-center gap-2.5 px-4 py-3.5 text-left"
            @click="notesExpanded = !notesExpanded"
          >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-50">
              <ChatBubbleLeftEllipsisIcon class="h-4 w-4 text-orange-500" />
            </div>
            <span class="flex-1 text-sm font-bold text-driver-ink">{{ t('driver_trip_detail.notes_section') }}</span>
            <ChevronDownIcon
              class="h-5 w-5 text-driver-muted/80 transition-transform"
              :class="notesExpanded ? 'rotate-180' : ''"
            />
          </button>

          <div v-show="notesExpanded" class="px-4 pb-4">
            <!-- Dispatch notes -->
            <div
              v-if="dispatchNotes"
              class="mb-3 rounded-xl border border-blue-100 bg-blue-50/80 px-3 py-2.5"
            >
              <p class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-blue-800">
                <InformationCircleIcon class="h-3.5 w-3.5" />
                {{ t('driver_trip_detail.notes_important') }}
              </p>
              <ul class="list-disc pl-4 text-xs leading-relaxed text-blue-700">
                <li
                  v-for="(line, li) in dispatchNoteLines"
                  :key="li"
                >{{ line }}</li>
              </ul>
            </div>

            <!-- Driver note input -->
            <textarea
              v-model="notesDraft"
              rows="3"
              class="w-full resize-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink placeholder:text-driver-muted/80 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50"
              :placeholder="t('driver_trip_detail.notes_driver_ph')"
            />
            <div class="mt-2 flex justify-end">
              <button
                type="button"
                :disabled="notesSaving || !notesDraft.trim()"
                class="flex items-center gap-1.5 rounded-xl bg-driver-bg px-4 py-2 text-xs font-bold text-white disabled:opacity-40 active:opacity-80"
                @click="saveDriverNote"
              >
                <CheckIcon v-if="notesSaved" class="h-3.5 w-3.5 text-[#7fdcc8]" />
                {{ notesSaved ? t('driver_trip_detail.notes_saved') : t('driver_trip_detail.notes_save') }}
              </button>
            </div>
          </div>
        </div>

        <!-- 6. Emergency contact accordion -->
        <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
          <button
            type="button"
            class="flex w-full items-center gap-2.5 px-4 py-3.5 text-left"
            @click="contactExpanded = !contactExpanded"
          >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-50">
              <PhoneArrowUpRightIcon class="h-4 w-4 text-rose-500" />
            </div>
            <span class="flex-1 text-sm font-bold text-driver-ink">{{ t('driver_trip_detail.contact_section') }}</span>
            <ChevronDownIcon
              class="h-5 w-5 text-driver-muted/80 transition-transform"
              :class="contactExpanded ? 'rotate-180' : ''"
            />
          </button>

          <div v-show="contactExpanded" class="px-4 pb-4">
            <div class="flex items-center gap-3 rounded-xl bg-rose-50/60 px-3 py-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-500/90 text-white shadow-sm">
                <UserIcon class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-driver-muted">{{ t('driver_trip_detail.contact_dispatcher_role') }}</p>
                <p class="text-sm font-bold text-driver-ink">{{ requesterLine }}</p>
              </div>
              <a
                v-if="dispatcherPhone"
                :href="`tel:${dispatcherPhone}`"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm"
              >
                <PhoneIcon class="h-4 w-4" />
              </a>
            </div>
          </div>
        </div>

      </template>
    </div>

    <!-- ─── Sticky bottom bar ──────────────────────────────── -->
    <div
      class="safe-pb fixed bottom-[calc(var(--driver-bottom-nav-height,3.5rem)+env(safe-area-inset-bottom))] left-0 right-0 z-[35] border-t border-[#7fdcc8]/15 bg-driver-card/95 px-3 pt-2 backdrop-blur-md sm:static sm:bottom-auto sm:z-auto sm:mt-4 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:backdrop-blur-0"
    >
      <!-- Progress row (only while in-progress + student trips) -->
      <div
        v-if="showPickupBar && trip?.status === 'in_progress'"
        class="mb-2 flex items-center gap-2 rounded-2xl bg-driver-surface px-3 py-2"
      >
        <span class="flex-1 text-sm font-semibold text-driver-muted">
          {{ t('driver_trip_detail.bottom_picked', { n: pickedCount, total: paxList.length }) }}
        </span>
        <span class="text-sm font-bold text-orange-500">
          {{ t('driver_trip_detail.bottom_remaining', { n: paxList.length - pickedCount }) }}
        </span>
        <button
          type="button"
          class="ml-1 flex items-center gap-1 rounded-full border border-white/10 px-3 py-1.5 text-xs font-bold text-driver-muted transition active:bg-driver-elevated"
          :class="isPaused ? 'border-[#7fdcc8] bg-[#7fdcc8]/10 text-driver-accent' : ''"
          @click="isPaused = !isPaused"
        >
          <PauseIcon v-if="!isPaused" class="h-3.5 w-3.5" />
          <PlayIcon v-else class="h-3.5 w-3.5" />
          {{ isPaused ? t('driver_trip_detail.btn_resume_trip') : t('driver_trip_detail.btn_pause') }}
        </button>
      </div>

      <!-- Main action -->
      <button
        v-if="canStart"
        type="button"
        :disabled="actionBusy"
        class="mb-2 flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-500 py-3.5 text-sm font-bold text-white shadow-md disabled:opacity-50 active:opacity-80"
        @click="startTrip"
      >
        <PlayIcon class="h-5 w-5" />
        {{ t('driver_trip_detail.btn_start_trip') }}
      </button>
      <button
        v-else-if="canEndTrip"
        type="button"
        :disabled="actionBusy"
        class="mb-2 flex w-full items-center justify-center gap-2 rounded-2xl bg-driver-bg py-3.5 text-sm font-bold text-white shadow-md disabled:opacity-50 active:opacity-80"
        @click="onEndTrip"
      >
        <FlagIcon class="h-5 w-5" />
        {{ t('driver_trip_detail.btn_end_trip') }}
      </button>
      <p
        v-else-if="trip?.status === 'completed'"
        class="mb-2 rounded-2xl border border-emerald-500/35 bg-emerald-950/35 py-3 text-center text-sm font-medium text-emerald-100 ring-1 ring-emerald-500/25"
      >
        {{ t('driver_trip_detail.done') }}
      </p>
    </div>

    <!-- ─── Modals (Teleport) ──────────────────────────────── -->
    <Teleport to="body">
      <!-- KM modal -->
      <div
        v-if="kmModalOpen"
        class="fixed inset-0 z-[45] flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
        @click.self="kmModalOpen = false"
      >
        <div class="w-full max-w-md rounded-t-2xl bg-driver-card p-4 shadow-2xl ring-1 ring-white/10 sm:rounded-2xl" @click.stop>
          <div class="mb-3 flex items-start justify-between gap-2">
            <h3 class="pr-6 text-base font-bold text-driver-ink">{{ t('driver_trip_detail.km_modal_title') }}</h3>
            <button
              type="button"
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-driver-muted"
              @click="kmModalOpen = false"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[11px] font-medium text-driver-muted">{{ t('driver_trip_detail.km_start') }}</label>
              <div class="mt-0.5 flex items-center rounded-xl border border-white/10 bg-driver-surface px-2 py-2 text-sm text-driver-ink">
                <input class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm" :value="formatKmInput(startKmModel)" readonly />
                <span class="ml-1 text-xs text-driver-muted/80">km</span>
              </div>
            </div>
            <div>
              <label class="text-[11px] font-medium text-driver-muted">{{ t('driver_trip_detail.km_end') }}</label>
              <div class="mt-0.5 flex items-center rounded-xl border border-white/10 bg-driver-surface px-2 py-2 focus-within:ring-2 focus-within:ring-[#7fdcc8]/50">
                <input
                  v-model="endKmModel"
                  type="text"
                  inputmode="numeric"
                  class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-driver-ink placeholder:text-driver-muted/50"
                  :placeholder="t('driver_trip_detail.km_placeholder')"
                  @input="onEndKmInput"
                />
                <span class="ml-1 text-xs text-driver-muted/80">km</span>
              </div>
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2 rounded-xl bg-driver-elevated px-3 py-2.5 text-sm text-driver-muted">
            <MapIcon class="h-4 w-4 text-driver-muted/80" />
            <span class="text-driver-muted">{{ t('driver_trip_detail.km_distance') }}</span>
            <span class="ml-auto font-bold tabular-nums text-driver-ink">{{ distancePreview }}</span>
          </div>
          <div class="mt-3">
            <label class="text-[11px] font-medium text-driver-muted">{{ t('driver_trip_detail.km_note') }}</label>
            <textarea
              v-model="kmNoteModel"
              rows="3"
              class="mt-0.5 w-full resize-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50"
              :placeholder="t('driver_trip_detail.km_note_ph')"
            />
          </div>
          <div class="mt-4 flex gap-2">
            <button
              type="button"
              class="flex-1 rounded-xl border border-white/10 py-2.5 text-sm font-semibold text-driver-muted"
              @click="kmModalOpen = false"
            >{{ t('driver_trip_detail.cancel') }}</button>
            <button
              type="button"
              :disabled="kmSaving || !canSubmitKm"
              class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-driver-accent py-2.5 text-sm font-bold text-driver-bg disabled:opacity-50"
              @click="submitKmModal"
            >
              {{ t('driver_trip_detail.confirm') }}
              <ArrowRightIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Cost modal -->
      <div
        v-if="costModalOpen"
        class="fixed inset-0 z-[45] flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
        @click.self="costModalOpen = false"
      >
        <div class="w-full max-w-md rounded-t-2xl bg-driver-card p-4 shadow-2xl ring-1 ring-white/10 sm:rounded-2xl" @click.stop>
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-driver-ink">{{ t('driver_trip_detail.cost_modal_title') }}</h3>
            <button
              type="button"
              class="flex h-8 w-8 items-center justify-center rounded-full bg-driver-elevated text-driver-muted"
              @click="costModalOpen = false"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <!-- Type selector (pill grid) -->
          <label class="text-[11px] font-medium text-driver-muted">{{ t('driver_trip_detail.cost_type') }}</label>
          <div class="mt-1.5 grid grid-cols-4 gap-1.5">
            <button
              v-for="ct in costTypes"
              :key="ct.value"
              type="button"
              class="flex flex-col items-center gap-1 rounded-xl border py-2 text-[11px] font-semibold transition"
              :class="costForm.type === ct.value ? 'border-[#7fdcc8] bg-[#7fdcc8]/10 text-driver-accent' : 'border-white/10 bg-driver-surface text-driver-muted'"
              @click="costForm.type = ct.value"
            >
              <span class="text-base">{{ ct.icon }}</span>
              <span>{{ ct.label }}</span>
            </button>
          </div>

          <label class="mt-3 block text-[11px] font-medium text-driver-muted">{{ t('driver_trip_detail.cost_amount') }}</label>
          <input
            v-model="costForm.amount"
            type="text"
            inputmode="numeric"
            class="mt-0.5 w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 text-sm text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50"
            :placeholder="t('driver_trip_detail.cost_amount_ph')"
          />

          <label class="mt-2.5 block text-[11px] font-medium text-driver-muted">{{ t('driver_trip_detail.cost_desc') }}</label>
          <input
            v-model="costForm.description"
            type="text"
            class="mt-0.5 w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 text-sm text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/50"
            :placeholder="t('driver_trip_detail.cost_desc_ph')"
          />

          <p v-if="costError" class="mt-2 text-xs text-rose-400">{{ costError }}</p>

          <div class="mt-4 flex gap-2">
            <button
              type="button"
              class="flex-1 rounded-xl border border-white/10 py-2.5 text-sm font-semibold text-driver-muted"
              @click="costModalOpen = false"
            >{{ t('driver_trip_detail.cancel') }}</button>
            <button
              type="button"
              :disabled="costSaving"
              class="flex-1 rounded-xl bg-driver-accent py-2.5 text-sm font-bold text-driver-bg disabled:opacity-50"
              @click="submitCost"
            >{{ t('driver_trip_detail.confirm') }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  ArrowsUpDownIcon,
  BanknotesIcon,
  ChartBarIcon,
  ChatBubbleLeftEllipsisIcon,
  CheckCircleIcon,
  CheckIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  ClockIcon,
  EllipsisVerticalIcon,
  FireIcon,
  FlagIcon,
  FunnelIcon,
  InformationCircleIcon,
  MapIcon,
  MapPinIcon,
  PauseIcon,
  PhoneArrowUpRightIcon,
  PhoneIcon,
  PlayIcon,
  PlusIcon,
  SparklesIcon,
  UserGroupIcon,
  UserIcon,
  WrenchScrewdriverIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { addTripEvent, getTrip, updateTripStatus, upsertTripRecord } from '../../api/trips'
import { submitTripCost } from '../../api/costs'
import { isPassengerRowFilled, isBusinessRowFilled, isCargoRowFilled } from '../../composables/dispatchWizardConstants'
import { formatVnd } from '../../util/labels'

const { t, te } = useI18n()
const route = useRoute()

const PASSENGER_PICKUP_EVENT = 'passenger_pickup'

// ─── Core state ───────────────────────────────────────────
const trip = ref(null)
const loading = ref(true)
const loadError = ref('')
const moreOpen = ref(false)
const moreRoot = ref(null)
const eventPosting = ref(false)
const actionBusy = ref(false)

/** Ẩn nhập KM trên màn tài xế; kết thúc chuyến không mở modal odometer */
const driverKmSectionEnabled = false

// ─── KM modal state ───────────────────────────────────────
const kmModalOpen = ref(false)
const kmAfterSave = ref(null)
const kmSaving = ref(false)
const endKmModel = ref('')
const kmNoteModel = ref('')

// ─── Cost modal state ─────────────────────────────────────
const costModalOpen = ref(false)
const costSaving = ref(false)
const costError = ref('')
const costForm = ref({ type: 'fuel', amount: '', description: '' })

// ─── UI accordion & interaction state ────────────────────
const routeExpanded = ref(true)
const costsExpanded = ref(false)
const notesExpanded = ref(false)
const contactExpanded = ref(false)
const expandedStudentIdx = ref(null)
const isPaused = ref(false)

// ─── Notes draft state ────────────────────────────────────
const notesDraft = ref('')
const notesSaving = ref(false)
const notesSaved = ref(false)

// ─── Student filter / sort ────────────────────────────────
const studentFilterStatus = ref('all')   // 'all' | 'waiting' | 'picked_up' | 'absent'
const studentSortOrder = ref('default')  // 'default' | 'time_asc' | 'time_desc'

// ─── Cost types list (for pill selector) ─────────────────
const costTypes = computed(() => [
  { value: 'fuel',    icon: '⛽', label: costTypeLabel('fuel') },
  { value: 'toll',    icon: '🛣️', label: costTypeLabel('toll') },
  { value: 'parking', icon: '🅿️', label: costTypeLabel('parking') },
  { value: 'meal',    icon: '🍜', label: costTypeLabel('meal') },
  { value: 'wash',    icon: '🚿', label: costTypeLabel('wash') },
  { value: 'fine',    icon: '🚨', label: costTypeLabel('fine') },
  { value: 'repair',  icon: '🔧', label: costTypeLabel('repair') },
  { value: 'other',   icon: '💼', label: costTypeLabel('other') },
])

// ─── Click-outside for overflow menu ─────────────────────
function onDocClick(e) {
  const el = moreRoot.value
  if (el && !el.contains(e.target)) moreOpen.value = false
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  void load()
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
})

// ─── Route param ─────────────────────────────────────────
const tripId = computed(() => {
  const id = route.params.id
  const n = Number(id)
  return Number.isFinite(n) && n > 0 ? n : null
})

watch(
  () => route.params.id,
  () => { void load() },
)

// ─── Trip data shortcuts ──────────────────────────────────
const dr = computed(() => trip.value?.dispatch_request ?? null)
const snap = computed(() => dr.value?.wizard_snapshot ?? null)

// ─── Header computeds ─────────────────────────────────────
const tripHeadlineType = computed(() => {
  const tt = dr.value?.trip_type
  if (tt === 'door_to_door') return 'D2D'
  if (tt === 'point_to_point') return 'P2P'
  if (tt === 'business') return t('driver_trip_detail.type_biz')
  if (tt === 'cargo') return 'Cargo'
  return '—'
})

const requesterLine = computed(() => dr.value?.requester?.name?.trim() || '—')

const tripCodeDisplay = computed(() => {
  const id = trip.value?.id
  if (!id) return '—'
  const tt = dr.value?.trip_type
  const pre = tt === 'business' ? 'CT' : tt === 'cargo' ? 'CG' : 'TR'
  return `#${pre}-${id}`
})

const statusBadgeClass = computed(() => {
  const s = trip.value?.status
  if (s === 'in_progress') return 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30'
  if (s === 'completed') return 'bg-[#7fdcc8]/20 text-[#7fdcc8] ring-1 ring-[#7fdcc8]/30'
  if (s === 'cancelled') return 'bg-rose-500/20 text-rose-300 ring-1 ring-rose-400/30'
  return 'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/30'
})

function statusLabelTr(st) {
  const k = `trips_page.trip_status.${st}`
  const tr = t(k)
  return tr === k ? st : tr
}

const headerStatusText = computed(() => {
  if (!trip.value) return '—'
  if (trip.value.status === 'in_progress') return t('driver_trip_detail.status_running')
  return statusLabelTr(trip.value.status)
})

const formattedDateLine = computed(() => {
  const tr = trip.value
  if (!tr?.depart_at) return '—'
  const d = new Date(tr.depart_at)
  if (Number.isNaN(d.getTime())) return '—'
  const datePart = d.toLocaleDateString('vi-VN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  const start = timeHm(tr.depart_at)
  const end = timeHm(tr.arrive_by || dr.value?.arrive_by)
  return `${datePart} • ${start} – ${end}`
})

// ─── Stats strip ──────────────────────────────────────────
const statsStudentCount = computed(() => paxList.value.length || 0)

const statsDistance = computed(() => {
  const rec = trip.value?.record
  if (rec?.distance_km != null) return `${Number(rec.distance_km).toLocaleString('vi-VN')} km`
  const dr2 = dr.value
  if (dr2?.estimated_distance_km != null) return `${Number(dr2.estimated_distance_km).toLocaleString('vi-VN')} km`
  return '— km'
})

const statsDuration = computed(() => {
  const tr = trip.value
  if (!tr?.depart_at) return `— ${t('driver_trip_detail.stats_duration_unit')}`
  const end = tr.arrive_by || dr.value?.arrive_by
  if (!end) return `— ${t('driver_trip_detail.stats_duration_unit')}`
  const mins = Math.round((new Date(end).getTime() - new Date(tr.depart_at).getTime()) / 60000)
  if (!Number.isFinite(mins) || mins < 0) return `— ${t('driver_trip_detail.stats_duration_unit')}`
  return `${mins} ${t('driver_trip_detail.stats_duration_unit')}`
})

// ─── Address helpers ──────────────────────────────────────
function splitAddress(s) {
  const t0 = (s || '').trim()
  if (!t0) return { main: '—', sub: '' }
  const i = t0.indexOf(',')
  if (i === -1) return { main: t0, sub: '' }
  return { main: t0.slice(0, i).trim(), sub: t0.slice(i + 1).trim() }
}

const originLines = computed(() => splitAddress(dr.value?.origin))
const destLines = computed(() => splitAddress(dr.value?.destination))
const originMain = computed(() => originLines.value.main)
const originSub = computed(() => originLines.value.sub)
const destMain = computed(() => destLines.value.main)
const destSub = computed(() => destLines.value.sub)

const dispatchNotes = computed(() => (dr.value?.notes || '').trim() || null)

const dispatchNoteLines = computed(() => {
  const raw = dispatchNotes.value
  if (!raw) return []
  return raw.split('\n').map(l => l.replace(/^[-•*]\s*/, '').trim()).filter(Boolean)
})

// ─── Map helpers ──────────────────────────────────────────
const embedMapSrc = computed(() => {
  const o = dr.value?.origin?.trim()
  const d = dr.value?.destination?.trim()
  if (o && d) return `https://maps.google.com/maps?q=${encodeURIComponent(`${o} → ${d}`)}&output=embed`
  if (d) return `https://maps.google.com/maps?q=${encodeURIComponent(d)}&output=embed`
  if (o) return `https://maps.google.com/maps?q=${encodeURIComponent(o)}&output=embed`
  return ''
})

const mapUrl = computed(() => {
  const a = (dr.value?.origin || '').trim()
  const b = (dr.value?.destination || '').trim()
  if (a && b) return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(a)}&destination=${encodeURIComponent(b)}&travelmode=driving`
  if (a) return `https://maps.google.com/maps?q=${encodeURIComponent(a)}`
  if (b) return `https://maps.google.com/maps?q=${encodeURIComponent(b)}`
  return ''
})

const routeTitle = computed(() => {
  const tt = dr.value?.trip_type
  const short = (() => {
    if (tt === 'door_to_door') return 'D2D'
    if (tt === 'point_to_point') return 'P2P'
    if (tt === 'business') return t('driver_trip_detail.type_biz')
    if (tt === 'cargo') return 'Cargo'
    return '—'
  })()
  if (!trip.value?.depart_at) return short
  const h = new Date(trip.value.depart_at).getHours()
  const part = h < 12 ? t('driver_trip_detail.period_morning') : t('driver_trip_detail.period_afternoon')
  return `${short} ${part}`
})

// ─── Costs ────────────────────────────────────────────────
const tripCosts = computed(() => {
  const c = trip.value?.costs
  if (!Array.isArray(c)) return []
  return c.slice().sort((a, b) => (b.id || 0) - (a.id || 0))
})

const costsTotalAmount = computed(() => tripCosts.value.reduce((s, c) => s + (Number(c.amount) || 0), 0))

const canAddCost = computed(
  () => trip.value && ['in_progress', 'assigned', 'driver_confirmed', 'pending', 'approved'].includes(trip.value.status),
)

function costTypeLabel(type) {
  const k = `driver_trip_detail.cost_type_${String(type || 'other')}`
  if (te(k)) return t(k)
  return type
}

function costStatusLabel(status) {
  const map = { submitted: 'cost_status_submitted', confirmed: 'cost_status_confirmed', rejected: 'cost_status_rejected' }
  const k = `driver_trip_detail.${map[status] || 'cost_status_submitted'}`
  return te(k) ? t(k) : status
}

// ─── Time helpers ─────────────────────────────────────────
function timeHm(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: true })
}

function formatCostTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  const today = new Date()
  const isToday = d.toDateString() === today.toDateString()
  const time = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: true })
  if (isToday) return t('driver_trip_detail.today_time', { t: time })
  return d.toLocaleString('vi-VN', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const dispatcherPhone = computed(() => null)

// ─── KM modal ─────────────────────────────────────────────
const startKmModel = computed(() => {
  const r = trip.value?.record
  if (r?.start_odometer_km != null) return r.start_odometer_km
  const v = trip.value?.vehicle?.odometer_km
  if (v != null) return v
  const d0 = trip.value?.driver?.odometer_km
  if (d0 != null) return d0
  return null
})

const hasEndOdometer = computed(() => trip.value?.record?.end_odometer_km != null)

function formatKm(n) {
  if (n == null || n === '') return '—'
  return Number(n).toLocaleString('vi-VN')
}

function formatKmInput(n) {
  if (n == null || n === '') return ''
  return Number(n).toLocaleString('vi-VN')
}

const distancePreview = computed(() => {
  const s = startKmModel.value
  const e = parseDigits(endKmModel.value)
  if (s == null || e == null) return t('driver_trip_detail.km_dash')
  const d = e - Number(s)
  if (Number.isNaN(d) || d < 0) return t('driver_trip_detail.km_dash')
  return `${d.toLocaleString('vi-VN')} km`
})

const canSubmitKm = computed(() => {
  const e = parseDigits(endKmModel.value)
  if (e == null) return false
  const s = startKmModel.value
  if (s != null && e < Number(s)) return false
  return true
})

function parseDigits(v) {
  const d = String(v || '').replace(/[^\d]/g, '')
  if (d === '') return null
  return parseInt(d, 10)
}

function onEndKmInput() {
  const p = parseDigits(endKmModel.value)
  if (p != null) endKmModel.value = p.toLocaleString('vi-VN')
}

function openKmModal(completeAfter) {
  kmAfterSave.value = completeAfter === 'complete' ? 'complete' : null
  const e = trip.value?.record?.end_odometer_km
  if (e != null) {
    endKmModel.value = String(e)
    onEndKmInput()
  } else {
    endKmModel.value = ''
  }
  kmNoteModel.value = (trip.value?.record?.driver_notes || '').trim()
  kmModalOpen.value = true
}

async function submitKmModal() {
  const id = tripId.value
  if (id == null || !canSubmitKm.value || kmSaving.value) return
  const e = parseDigits(endKmModel.value)
  if (e == null) return
  const doComplete = kmAfterSave.value === 'complete'
  kmSaving.value = true
  try {
    const s = startKmModel.value
    const payload = { end_odometer_km: e }
    const note = kmNoteModel.value?.trim()
    if (note) payload.driver_notes = note
    if (s != null && trip.value?.record?.start_odometer_km == null) {
      payload.start_odometer_km = Number(s)
    }
    await upsertTripRecord(id, payload)
    await refresh()
    kmModalOpen.value = false
    if (doComplete) await doCompleteTrip()
  } catch {
    loadError.value = t('driver_trip_detail.km_err')
  } finally {
    kmSaving.value = false
    kmAfterSave.value = null
  }
}

// ─── Cost modal ───────────────────────────────────────────
function openCostModal() {
  costError.value = ''
  costForm.value = { type: 'fuel', amount: '', description: '' }
  costModalOpen.value = true
}

async function submitCost() {
  const id = tripId.value
  if (id == null || costSaving.value) return
  const a = String(costForm.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num < 0) {
    costError.value = t('driver_trip_detail.cost_err_amount')
    return
  }
  costSaving.value = true
  costError.value = ''
  try {
    await submitTripCost(
      id,
      {
        type: costForm.value.type,
        amount: num,
        description: costForm.value.description?.trim() || null,
        currency: 'VND',
      },
      { idempotencyKey: `driver-cost-${id}-${Date.now()}` },
    )
    costModalOpen.value = false
    await refresh()
  } catch {
    costError.value = t('driver_trip_detail.cost_err_submit')
  } finally {
    costSaving.value = false
  }
}

// ─── Driver note save ─────────────────────────────────────
async function saveDriverNote() {
  const id = tripId.value
  if (id == null || notesSaving.value || !notesDraft.value.trim()) return
  notesSaving.value = true
  notesSaved.value = false
  try {
    await upsertTripRecord(id, { driver_notes: notesDraft.value.trim() })
    notesSaved.value = true
    await refresh()
    setTimeout(() => { notesSaved.value = false }, 2000)
  } catch {
    loadError.value = t('driver_trip_detail.km_err')
  } finally {
    notesSaving.value = false
  }
}

// ─── Warning banner ───────────────────────────────────────
const warningBanner = computed(() => {
  const tr = trip.value
  if (!tr) return null
  if (!['in_progress', 'assigned', 'driver_confirmed', 'pending', 'approved'].includes(tr.status)) return null
  const endIso = tr.arrive_by || dr.value?.arrive_by
  if (!endIso) return null
  const end = new Date(endIso).getTime()
  if (!Number.isFinite(end)) return null
  const min = Math.round((end - Date.now()) / 60000)
  if (min < 0 || min > 10) return null
  const firstDrop = (dr.value?.destination || '').trim() || '—'
  return { minutes: min, body: t('driver_trip_detail.warn_body', { place: firstDrop }) }
})

// ─── Passenger / student list ─────────────────────────────
const pickupStateByIndex = computed(() => {
  const evs = trip.value?.events
  if (!Array.isArray(evs) || !evs.length) return new Map()
  const sorted = [...evs].sort((a, b) => (b.id || 0) - (a.id || 0))
  const m = new Map()
  for (const e of sorted) {
    if (e.type !== PASSENGER_PICKUP_EVENT) continue
    const d = e.data
    if (!d || d.row_index == null) continue
    const idx = Number(d.row_index)
    if (Number.isNaN(idx) || m.has(idx)) continue
    const st = d.state
    if (st === 'picked_up' || st === 'absent') m.set(idx, st)
  }
  return m
})

function rowState(i) {
  return pickupStateByIndex.value.get(i) ?? null
}

const paxKind = computed(() => {
  const tt = dr.value?.trip_type
  if (tt === 'door_to_door' || tt === 'point_to_point') return 'student'
  return 'other'
})

const paxList = computed(() => {
  const ttr = dr.value
  if (!ttr) return []
  const s = snap.value
  const out = []
  const tt = ttr.trip_type

  if (tt === 'cargo' && s?.cargoRows?.length) {
    let i = 0
    for (const r of s.cargoRows) {
      if (!isCargoRowFilled(r)) continue
      out.push({
        name: r.name?.trim() || t('driver_trip_detail.cargo_item', { n: ++i }),
        subtitle: [r.pickup_at, r.pickup_place].filter(Boolean).join(' · ') || '—',
        phone: (r.pickup_contact || r.delivery_contact || '').replace(/\D/g, '') || null,
        address: null,
        time: null,
      })
    }
    return out
  }

  if (tt === 'business' && s?.businessRows?.length) {
    let i = 0
    for (const r of s.businessRows) {
      if (!isBusinessRowFilled(r)) continue
      out.push({
        name: t('driver_trip_detail.biz_party', { n: ++i }),
        subtitle: r.notes?.trim() || r.pickup || '—',
        phone: null,
        address: null,
        time: null,
      })
    }
    if (out.length) return out
  }

  let idx = 0
  for (const r of s?.passengerRows ?? []) {
    if (!isPassengerRowFilled(r)) continue
    idx += 1
    const name = r.person_in_charge?.trim() || t('driver_trip_detail.guest_n', { n: idx })
    const classGuess = classFromNotes(r.notes)
    const rawTime = r.depart_at || ttr.depart_at
    const timePart = rawTime
      ? t('driver_trip_detail.expected', { t: timeHm(rawTime) })
      : ''
    const sub = classGuess || (r.notes || '').trim() || '—'
    out.push({
      name,
      subtitle: sub,
      phone: (r.phone || '').replace(/\D/g, '') || null,
      address: (r.pickup || '').trim() || null,
      time: rawTime ? timeHm(rawTime) : null,
    })
  }

  for (const r of s?.businessRows ?? []) {
    if (tt === 'business') break
    if (!isBusinessRowFilled(r)) continue
    idx += 1
    out.push({
      name: t('driver_trip_detail.biz_party', { n: idx }),
      subtitle: r.notes?.trim() || '—',
      phone: null,
      address: null,
      time: null,
    })
  }

  if (!out.length && (ttr.passenger_count ?? 0) > 0) {
    out.push({
      name: t('driver_trip_detail.unlisted', { n: ttr.passenger_count }),
      subtitle: '—',
      phone: ttr.requester?.phone || null,
      address: null,
      time: null,
    })
  }
  return out
})

// Indexed paxList for preserving original indices after filter/sort
const indexedPaxList = computed(() =>
  paxList.value.map((p, i) => ({ ...p, _origIndex: i })),
)

const displayedPaxList = computed(() => {
  let list = indexedPaxList.value

  // Filter
  if (studentFilterStatus.value !== 'all') {
    list = list.filter(p => {
      const st = rowState(p._origIndex)
      if (studentFilterStatus.value === 'waiting') return st == null
      return st === studentFilterStatus.value
    })
  }

  // Sort
  if (studentSortOrder.value === 'time_asc') {
    list = [...list].sort((a, b) => (a.time || '').localeCompare(b.time || ''))
  } else if (studentSortOrder.value === 'time_desc') {
    list = [...list].sort((a, b) => (b.time || '').localeCompare(a.time || ''))
  }

  return list
})

function cycleFilter() {
  const cycle = ['all', 'waiting', 'picked_up', 'absent']
  const idx = cycle.indexOf(studentFilterStatus.value)
  studentFilterStatus.value = cycle[(idx + 1) % cycle.length]
}

function cycleSort() {
  const cycle = ['default', 'time_asc', 'time_desc']
  const idx = cycle.indexOf(studentSortOrder.value)
  studentSortOrder.value = cycle[(idx + 1) % cycle.length]
}

function toggleStudent(origIdx) {
  expandedStudentIdx.value = expandedStudentIdx.value === origIdx ? null : origIdx
}

function classFromNotes(notes) {
  const s = (notes || '').trim()
  if (!s) return ''
  const m = s.match(/lớp\s*([0-9A-Za-z]+)/i) || s.match(/Lớp\s*([0-9A-Za-z.]+)/)
  if (m) return `Lớp ${m[1]}`
  if (s.length < 40) return s
  return s.slice(0, 36) + '…'
}

function studentInitials(name) {
  const n = (name || '').trim() || '?'
  const p = n.split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
}

const showPickupBar = computed(() => paxKind.value === 'student' && paxList.value.length > 0)

const pickedCount = computed(() => {
  if (paxKind.value !== 'student') return 0
  let c = 0
  for (let i = 0; i < paxList.value.length; i += 1) {
    if (rowState(i) === 'picked_up') c += 1
  }
  return c
})

function isNextIndex(i) {
  if (paxKind.value !== 'student' || !canMarkPickup.value) return false
  for (let j = 0; j < paxList.value.length; j += 1) {
    const st = rowState(j)
    if (st === 'picked_up' || st === 'absent') continue
    return j === i
  }
  return false
}

const canMarkPickup = computed(
  () => trip.value?.status === 'in_progress' && paxKind.value === 'student' && paxList.value.length > 0,
)

const canStart = computed(() => {
  if (!trip.value) return false
  return ['assigned', 'driver_confirmed', 'pending', 'approved'].includes(trip.value.status)
})

const canEndTrip = computed(() => trip.value?.status === 'in_progress')

async function onEndTrip() {
  if (!canEndTrip.value) return
  if (!hasEndOdometer.value && driverKmSectionEnabled) {
    openKmModal('complete')
    return
  }
  await doCompleteTrip()
}

async function doCompleteTrip() {
  const id = tripId.value
  if (id == null || actionBusy.value) return
  actionBusy.value = true
  try {
    await updateTripStatus(id, { status: 'completed' })
    await refresh()
  } catch {
    loadError.value = t('driver_trip_detail.status_err')
  } finally {
    actionBusy.value = false
  }
}

// ─── Data loading ─────────────────────────────────────────
async function load() {
  const id = tripId.value
  if (id == null) {
    loadError.value = t('driver_trip_detail.err_bad_id')
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    trip.value = await getTrip(id)
  } catch {
    loadError.value = t('driver_home.load_error')
    trip.value = null
  } finally {
    loading.value = false
  }
}

async function refresh() {
  const id = tripId.value
  if (id == null) return
  try {
    trip.value = await getTrip(id)
  } catch {
    /* keep stale data */
  }
}

async function setRowState(i, state) {
  const id = tripId.value
  if (id == null || eventPosting.value) return
  eventPosting.value = true
  try {
    await addTripEvent(id, {
      type: PASSENGER_PICKUP_EVENT,
      data: { row_index: i, state, at: new Date().toISOString() },
    })
    await refresh()
  } catch {
    loadError.value = t('driver_trip_detail.event_err')
  } finally {
    eventPosting.value = false
  }
}

async function startTrip() {
  const id = tripId.value
  if (id == null || actionBusy.value) return
  actionBusy.value = true
  try {
    await updateTripStatus(id, { status: 'in_progress' })
    await refresh()
  } catch {
    loadError.value = t('driver_trip_detail.status_err')
  } finally {
    actionBusy.value = false
  }
}
</script>

<style scoped>
.safe-pb {
  padding-bottom: 0.75rem;
}
</style>
