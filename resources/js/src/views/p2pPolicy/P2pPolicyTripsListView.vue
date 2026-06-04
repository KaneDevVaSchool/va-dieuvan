<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header + date navigation -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.trips_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.trips_subtitle') }}</p>
      </div>
      <!-- Date navigation -->
      <div class="flex items-center gap-1.5">
        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800" :title="'Hôm qua'" @click="shiftDate(-1)">
          <ChevronLeftIcon class="h-4 w-4" />
        </button>
        <input v-model="filterDate" type="date" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800" :title="'Ngày mai'" @click="shiftDate(1)">
          <ChevronRightIcon class="h-4 w-4" />
        </button>
        <button v-if="filterDate !== todayIso" type="button" class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/30 dark:text-teal-300" @click="filterDate = todayIso">Hôm nay</button>
      </div>
    </div>

    <!-- KPI Strip — click to filter by status -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
      <button
        v-for="box in kpiBoxes"
        :key="box.key"
        type="button"
        :class="[
          'rounded-2xl border p-3 shadow-sm transition text-left sm:p-4',
          filterStatus === box.key
            ? 'border-teal-400 bg-teal-50 ring-2 ring-teal-300/60 dark:border-teal-600 dark:bg-teal-950/30'
            : 'border-slate-200/90 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/50',
        ]"
        @click="filterStatus = filterStatus === box.key ? '' : box.key"
      >
        <div class="flex min-w-0 items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="box.iconWrap">
            <component :is="box.icon" class="h-5 w-5" :class="box.iconClass" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white sm:text-2xl">
              {{ loading ? '…' : box.value }}
            </div>
            <div class="mt-0.5 text-xs font-medium leading-snug text-slate-600 dark:text-slate-400">{{ box.label }}</div>
          </div>
        </div>
      </button>
    </div>

    <!-- SSE / polling status bar -->
    <div class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs"
      :class="sseStatus === 'connected'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-300'
        : sseStatus === 'polling'
        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-300'
        : 'border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-700 dark:bg-slate-800/30 dark:text-slate-400'
      "
    >
      <span class="relative flex h-2 w-2 shrink-0">
        <span v-if="sseStatus === 'connected'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
        <span class="relative inline-flex h-2 w-2 rounded-full" :class="sseStatus === 'connected' ? 'bg-emerald-500' : sseStatus === 'polling' ? 'bg-amber-400' : 'bg-slate-300'" />
      </span>
      <span v-if="sseStatus === 'connected'">Realtime qua SSE — cập nhật tự động</span>
      <span v-else-if="sseStatus === 'polling'">SSE không khả dụng — polling mỗi 30s (<code>GET /api/policy-trips/live-updates</code>)</span>
      <span v-else>Realtime chưa kết nối — chờ backend SSE endpoint</span>
      <span class="ml-auto text-slate-400 dark:text-slate-500">Cập nhật lần cuối: {{ lastRefreshed }}</span>
    </div>

    <!-- Filter Bar -->
    <div class="relative z-40">
      <AppFilterBar>
        <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <!-- Funnel -->
          <details class="group relative">
            <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden">
              <span class="relative inline-flex">
                <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                <span v-if="activeFilterCount > 0" class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white">{{ activeFilterCount }}</span>
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
            </summary>
            <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[220px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50">
              <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">Bộ lọc đang áp dụng</p>
              <div class="p-3">
                <ul class="space-y-1.5 text-sm">
                  <li v-if="filterSlot"><span class="text-slate-500">Ca:</span> <span class="font-medium">{{ labelTimeSlot(filterSlot) }}</span></li>
                  <li v-if="filterStatus"><span class="text-slate-500">Trạng thái:</span> <span class="font-medium">{{ labelPolicyTripStatus(filterStatus) }}</span></li>
                  <li v-if="filterRouteId"><span class="text-slate-500">Tuyến:</span> <span class="font-medium">{{ filterRouteName }}</span></li>
                  <li v-if="!activeFilterCount" class="text-slate-400 text-xs">Chưa chọn điều kiện lọc.</li>
                </ul>
                <button type="button" class="mt-3 w-full rounded-xl border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="resetFilters">Xóa tất cả</button>
              </div>
            </div>
          </details>

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" />

          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <!-- Slot -->
            <AppFilterDropdown :summary-text="filterSlot ? labelTimeSlot(filterSlot) : 'Tất cả ca'" panel-class="min-w-[160px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in slotOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterSlot === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterSlot = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>

            <!-- Status (also controlled by KPI click) -->
            <AppFilterDropdown :summary-text="filterStatus ? labelPolicyTripStatus(filterStatus) : 'Trạng thái'" panel-class="min-w-[180px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in statusOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterStatus === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterStatus = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>

            <!-- Route filter -->
            <AppFilterDropdown :summary-text="filterRouteName || 'Tuyến'" panel-class="min-w-[220px] py-1">
              <div class="px-2 pt-2 pb-1">
                <input v-model="routeSearch" type="search" placeholder="Tìm tuyến…" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800" />
              </div>
              <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-1 pb-1">
                <li>
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', !filterRouteId ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterRouteId = ''; filterRouteName = ''; $event.currentTarget.closest('details').open = false">Tất cả tuyến</button>
                </li>
                <li v-for="r in filteredRoutes" :key="r.id">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterRouteId === String(r.id) ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterRouteId = String(r.id); filterRouteName = r.name; $event.currentTarget.closest('details').open = false">{{ r.name }}</button>
                </li>
                <li v-if="!filteredRoutes.length" class="px-3 py-2 text-xs text-slate-400">Không tìm thấy tuyến.</li>
              </ul>
            </AppFilterDropdown>
          </div>

          <div class="ml-auto flex shrink-0 items-center gap-2 pl-2">
            <button type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" :disabled="loading" @click="loadTrips">
              <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
              <span class="hidden sm:inline">Làm mới</span>
            </button>
          </div>
        </div>
      </AppFilterBar>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
      </div>
      <div v-else-if="!items.length" class="flex flex-col items-center justify-center py-16 text-center">
        <AcademicCapIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.empty') }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ t('p2p_policy_page.developing') }}</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400">
              <th class="whitespace-nowrap py-3 pl-4 pr-3">Ca</th>
              <th class="whitespace-nowrap py-3 pr-3">Tuyến</th>
              <th class="whitespace-nowrap py-3 pr-3">Giờ</th>
              <th class="whitespace-nowrap py-3 pr-3">Tài xế / SĐT</th>
              <th class="whitespace-nowrap py-3 pr-3">Xe</th>
              <th class="whitespace-nowrap py-3 pr-3">HS</th>
              <th class="whitespace-nowrap py-3 pr-3">Trạng thái</th>
              <th class="whitespace-nowrap py-3 pr-4 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr
              v-for="row in items"
              :key="row.id"
              :class="[
                'transition',
                isAllAbsent(row) ? 'bg-rose-50/40 dark:bg-rose-950/10' : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40',
                !row.driver_id ? 'ring-1 ring-inset ring-amber-200/60 dark:ring-amber-800/30' : '',
              ]"
            >
              <td class="whitespace-nowrap py-3 pl-4 pr-3">
                <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium', row.time_slot === 'morning' ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200']">
                  {{ labelTimeSlot(row.time_slot) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 font-medium text-slate-900 dark:text-slate-100">{{ row.route_name ?? '—' }}</td>
              <td class="whitespace-nowrap py-3 pr-3 tabular-nums text-slate-700 dark:text-slate-300">{{ row.planned_departure ?? '—' }}</td>
              <td class="whitespace-nowrap py-3 pr-3">
                <template v-if="row.driver_name">
                  <div class="font-medium text-slate-800 dark:text-slate-200">{{ row.driver_name }}</div>
                  <div v-if="row.driver_phone" class="text-xs text-slate-500 dark:text-slate-400">{{ row.driver_phone }}</div>
                </template>
                <span v-else class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                  <ExclamationTriangleIcon class="h-3 w-3" /> Chưa gán
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-600 dark:text-slate-400">{{ row.vehicle_plate ?? '—' }}</td>
              <td class="whitespace-nowrap py-3 pr-3">
                <!-- Progress: boarded / expected, absent highlighted -->
                <div class="flex items-center gap-1.5">
                  <div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" :title="`${row.boarded_count ?? 0}/${row.expected_count ?? 0} đã lên xe`">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" :style="{ width: progressWidth(row) }" />
                  </div>
                  <span class="tabular-nums text-xs text-slate-600 dark:text-slate-400">
                    <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ row.boarded_count ?? 0 }}</span>/{{ row.expected_count ?? 0 }}
                  </span>
                  <span v-if="(row.absent_count ?? 0) > 0" class="tabular-nums text-xs font-medium text-rose-600 dark:text-rose-400">−{{ row.absent_count }}</span>
                </div>
                <!-- All-absent warning -->
                <div v-if="isAllAbsent(row)" class="mt-1 flex items-center gap-1 text-[11px] font-medium text-rose-600 dark:text-rose-400">
                  <ExclamationCircleIcon class="h-3.5 w-3.5 shrink-0" /> Toàn bộ HS báo vắng
                </div>
              </td>
              <td class="whitespace-nowrap py-3 pr-3">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', policyTripStatusPillClass(row.status)]">
                  {{ labelPolicyTripStatus(row.status) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button type="button" class="rounded-lg px-2 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="openStudentDetail(row)">
                    <UsersIcon class="h-3.5 w-3.5 inline mr-0.5" />HS
                  </button>
                  <button v-if="row.status === 'scheduled' || row.status === 'assigned'" type="button" class="rounded-lg px-2 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/30" @click="openAssignDriver(row)">
                    <TruckIcon class="h-3.5 w-3.5 inline mr-0.5" />Gán
                  </button>
                  <button v-if="row.status !== 'cancelled' && row.status !== 'completed'" type="button" class="rounded-lg px-2 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/30" @click="openCancel(row)">
                    <XMarkIcon class="h-3.5 w-3.5 inline mr-0.5" />Hủy
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ───────── Modals ───────── -->

    <!-- §8.2 — Danh sách học sinh trong chuyến + đánh vắng dispatcher (§7.1 Kênh 1) -->
    <Modal
      :open="!!selectedTrip"
      :title="`Học sinh — ${selectedTrip ? labelTimeSlot(selectedTrip.time_slot) + ' · ' + (selectedTrip.route_name ?? '') : ''}`"
      :description="`${selectedTrip?.trip_date ?? ''} · Dự kiến: ${selectedTrip?.expected_count ?? 0} · Đã lên: ${selectedTrip?.boarded_count ?? 0} · Vắng: ${selectedTrip?.absent_count ?? 0}`"
      wide
      @close="selectedTrip = null; absentTarget = null"
    >
      <div v-if="studentsLoading" class="flex items-center gap-2 py-6 text-sm text-slate-500">
        <ArrowPathIcon class="h-4 w-4 animate-spin" /> Đang tải…
      </div>
      <div v-else-if="!tripStudents.length" class="py-6 text-center text-sm text-slate-500">Chưa có học sinh trong chuyến này.</div>
      <div v-else>
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700">
              <th class="py-2 pr-3">Họ tên</th>
              <th class="py-2 pr-3">Lớp</th>
              <th class="py-2 pr-3">Trạng thái</th>
              <th class="py-2 pr-3">Lên xe</th>
              <th class="py-2 pr-3">Xuống xe</th>
              <th class="py-2 pr-3">Người báo</th>
              <th v-if="canMarkAbsent" class="py-2">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="s in tripStudents" :key="s.id" :class="s.absence_reason ? 'bg-rose-50/30 dark:bg-rose-950/10' : ''">
              <td class="py-2.5 pr-3 font-medium text-slate-900 dark:text-slate-100">{{ s.student_name ?? s.student_id }}</td>
              <td class="py-2.5 pr-3 text-slate-500 dark:text-slate-400">{{ s.class_name ?? '—' }}</td>
              <td class="py-2.5 pr-3">
                <span v-if="s.absence_reason" :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', absenceReasonPillClass(s.absence_reason)]">{{ labelAbsenceReason(s.absence_reason) }}</span>
                <span v-else-if="s.alighted_at" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">Đã xuống xe</span>
                <span v-else-if="s.boarded_at" class="inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-900 dark:bg-teal-950/40 dark:text-teal-100">Đã lên xe</span>
                <span v-else class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">Dự kiến</span>
              </td>
              <td class="py-2.5 pr-3 tabular-nums text-xs text-slate-500">{{ s.boarded_at ? fmtTime(s.boarded_at) : '—' }}</td>
              <td class="py-2.5 pr-3 tabular-nums text-xs text-slate-500">{{ s.alighted_at ? fmtTime(s.alighted_at) : '—' }}</td>
              <td class="py-2.5 pr-3 text-xs text-slate-500">{{ s.reported_by_name ?? '—' }}</td>
              <td v-if="canMarkAbsent" class="py-2.5">
                <div v-if="!s.absence_reason && !s.boarded_at" class="flex items-center gap-1">
                  <button type="button" class="rounded-lg bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950/30 dark:text-sky-300" :disabled="absentLoading === s.id" @click="markAbsent(s, 'absent_reported')">
                    <ArrowPathIcon v-if="absentLoading === s.id" class="h-3 w-3 inline animate-spin" />
                    Vắng có phép
                  </button>
                  <button type="button" class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 transition hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-300" :disabled="absentLoading === s.id" @click="markAbsent(s, 'late_cancellation')">
                    Hủy muộn
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Modal>

    <!-- §6 — Gán tài xế -->
    <Modal
      :open="!!assignTarget"
      title="Gán tài xế cho chuyến"
      :description="assignTarget ? `${labelTimeSlot(assignTarget.time_slot)} · ${assignTarget.route_name ?? ''} · ${assignTarget.trip_date ?? filterDate}` : ''"
      wide
      @close="closeAssignModal"
    >
      <div class="space-y-4">
        <!-- Driver search -->
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tài xế <span class="text-rose-500">*</span></label>
          <div class="relative">
            <input v-model="driverSearch" type="search" placeholder="Tìm tên tài xế…" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" @input="searchDrivers" />
            <ul v-if="driverResults.length && !assignForm.driver_id" class="absolute left-0 top-full z-50 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
              <li v-for="d in driverResults" :key="d.id">
                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800" @click="selectDriver(d)">
                  <TruckIcon class="h-4 w-4 shrink-0 text-slate-400" />
                  <div>
                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ d.full_name }}</div>
                    <div v-if="d.phone" class="text-xs text-slate-500">{{ d.phone }}</div>
                  </div>
                </button>
              </li>
            </ul>
          </div>
          <div v-if="assignForm.driver_id" class="mt-1.5 flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-sm dark:border-indigo-800 dark:bg-indigo-950/30">
            <span class="font-medium text-indigo-800 dark:text-indigo-200">{{ assignForm.driver_name }}</span>
            <button type="button" class="ml-auto text-xs text-indigo-500 hover:text-indigo-700" @click="assignForm.driver_id = ''; assignForm.driver_name = ''; driverSearch = ''">Đổi</button>
          </div>
        </div>

        <!-- Vehicle search -->
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Xe <span class="text-rose-500">*</span></label>
          <div class="relative">
            <input v-model="vehicleSearch" type="search" placeholder="Tìm biển số xe…" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" @input="searchVehicles" />
            <ul v-if="vehicleResults.length && !assignForm.vehicle_id" class="absolute left-0 top-full z-50 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
              <li v-for="v in vehicleResults" :key="v.id">
                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800" @click="selectVehicle(v)">
                  <div>
                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ v.license_plate }}</div>
                    <div class="text-xs text-slate-500">Sức chứa: {{ v.capacity ?? '—' }} chỗ</div>
                  </div>
                </button>
              </li>
            </ul>
          </div>
          <div v-if="assignForm.vehicle_id" class="mt-1.5 flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-sm dark:border-indigo-800 dark:bg-indigo-950/30">
            <span class="font-medium text-indigo-800 dark:text-indigo-200">{{ assignForm.vehicle_plate }}</span>
            <span v-if="assignTarget && assignForm.vehicle_capacity && assignForm.vehicle_capacity < (assignTarget.expected_count ?? 0)" class="ml-1 text-xs text-amber-700 dark:text-amber-400">⚠ Sức chứa ({{ assignForm.vehicle_capacity }}) &lt; HS dự kiến ({{ assignTarget.expected_count }})</span>
            <button type="button" class="ml-auto text-xs text-indigo-500 hover:text-indigo-700" @click="assignForm.vehicle_id = ''; assignForm.vehicle_plate = ''; vehicleSearch = ''">Đổi</button>
          </div>
        </div>

        <div v-if="assignError" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300">{{ assignError }}</div>

        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="closeAssignModal">Đóng</button>
          <button type="button" :disabled="!assignForm.driver_id || !assignForm.vehicle_id || assignLoading" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50" @click="confirmAssign">
            <ArrowPathIcon v-if="assignLoading" class="h-4 w-4 animate-spin" />
            Xác nhận gán
          </button>
        </div>
      </div>
    </Modal>

    <!-- Hủy chuyến -->
    <Modal :open="!!cancelTarget" title="Hủy chuyến" description="Nhập lý do hủy chuyến policy này." @close="cancelTarget = null; cancelReason = ''">
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Lý do hủy <span class="text-rose-500">*</span></label>
          <textarea v-model="cancelReason" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" placeholder="Nhập lý do hủy chuyến…" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="cancelTarget = null; cancelReason = ''">Đóng</button>
          <button type="button" :disabled="!cancelReason.trim() || actionLoading" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-50" @click="confirmCancel">
            <ArrowPathIcon v-if="actionLoading" class="h-4 w-4 animate-spin" /> Xác nhận hủy
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  ArrowPathIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  TruckIcon,
  UsersIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Modal from '../../components/ui/Modal.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import {
  absenceReasonPillClass,
  labelAbsenceReason,
  labelPolicyTripStatus,
  labelTimeSlot,
  policyTripStatusPillClass,
} from '../../constants/policyTripStatus.js'
import {
  assignDriverToPolicyTrip,
  cancelPolicyTrip,
  listPolicyTrips,
  listPolicyTripStudents,
  markPolicyTripStudentAbsent,
} from '../../api/p2p.js'
import { listRoutes } from '../../api/d2d.js'
import { listDrivers, listVehicles } from '../../api/operational.js'

const { t } = useI18n()

const filterBarRef = ref(null)
useDetailsAutoCloseWithin(filterBarRef)

// ── Dates ────────────────────────────────────────────────────────────────────
const todayIso = new Date().toISOString().slice(0, 10)
const filterDate = ref(todayIso)

function shiftDate(delta) {
  const d = new Date(filterDate.value)
  d.setDate(d.getDate() + delta)
  filterDate.value = d.toISOString().slice(0, 10)
}

// ── Filters ──────────────────────────────────────────────────────────────────
const filterSlot = ref('')
const filterStatus = ref('')
const filterRouteId = ref('')
const filterRouteName = ref('')
const routeSearch = ref('')
const routes = ref([])
const filteredRoutes = computed(() => {
  if (!routeSearch.value.trim()) return routes.value
  const q = routeSearch.value.toLowerCase()
  return routes.value.filter((r) => r.name.toLowerCase().includes(q))
})

const slotOptions = [
  { value: '', label: 'Tất cả ca' },
  { value: 'morning', label: 'Sáng' },
  { value: 'afternoon', label: 'Chiều' },
]
const statusOptions = [
  { value: '', label: 'Tất cả trạng thái' },
  { value: 'scheduled', label: 'Chờ gán tài xế' },
  { value: 'assigned', label: 'Đã phân công' },
  { value: 'in_progress', label: 'Đang chạy' },
  { value: 'completed', label: 'Hoàn thành' },
  { value: 'cancelled', label: 'Đã huỷ' },
]

const activeFilterCount = computed(() =>
  [filterSlot.value, filterStatus.value, filterRouteId.value].filter(Boolean).length,
)

function resetFilters() {
  filterSlot.value = ''
  filterStatus.value = ''
  filterRouteId.value = ''
  filterRouteName.value = ''
}

// ── Data ─────────────────────────────────────────────────────────────────────
const loading = ref(false)
const items = ref([])
const lastRefreshed = ref('—')

async function loadTrips() {
  loading.value = true
  try {
    const params = { date: filterDate.value }
    if (filterSlot.value) params.time_slot = filterSlot.value
    if (filterStatus.value) params.status = filterStatus.value
    if (filterRouteId.value) params.route_id = filterRouteId.value
    const res = await listPolicyTrips(params)
    items.value = res?.items ?? res ?? []
    lastRefreshed.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

// ── KPI ───────────────────────────────────────────────────────────────────────
const kpiBoxes = computed(() => [
  { key: 'scheduled', label: 'Chờ gán tài xế', value: items.value.filter((r) => r.status === 'scheduled').length, icon: ClockIcon, iconWrap: 'bg-slate-100 dark:bg-slate-800', iconClass: 'text-slate-500' },
  { key: 'assigned', label: 'Đã phân công', value: items.value.filter((r) => r.status === 'assigned').length, icon: TruckIcon, iconWrap: 'bg-indigo-100 dark:bg-indigo-950/40', iconClass: 'text-indigo-600 dark:text-indigo-300' },
  { key: 'in_progress', label: 'Đang chạy', value: items.value.filter((r) => r.status === 'in_progress').length, icon: CalendarDaysIcon, iconWrap: 'bg-teal-100 dark:bg-teal-950/40', iconClass: 'text-teal-600 dark:text-teal-300' },
  { key: 'completed', label: 'Hoàn thành', value: items.value.filter((r) => r.status === 'completed').length, icon: CheckCircleIcon, iconWrap: 'bg-emerald-100 dark:bg-emerald-950/40', iconClass: 'text-emerald-600 dark:text-emerald-300' },
])

function isAllAbsent(row) {
  return (row.expected_count ?? 0) > 0 && (row.absent_count ?? 0) >= (row.expected_count ?? 0)
}

function progressWidth(row) {
  const exp = row.expected_count ?? 0
  if (!exp) return '0%'
  return `${Math.min(100, Math.round(((row.boarded_count ?? 0) / exp) * 100))}%`
}

// ── SSE + polling fallback ────────────────────────────────────────────────────
const sseStatus = ref('idle') // 'idle' | 'connected' | 'polling'
let sseSource = null
let pollTimer = null

function startLiveUpdates() {
  stopLiveUpdates()
  if (typeof EventSource === 'undefined') {
    startPolling()
    return
  }
  try {
    const url = `/api/policy-trips/live-updates?date=${filterDate.value}${filterSlot.value ? `&time_slot=${filterSlot.value}` : ''}`
    sseSource = new EventSource(url)
    sseSource.onopen = () => { sseStatus.value = 'connected' }
    sseSource.onmessage = (e) => {
      try {
        const patch = JSON.parse(e.data)
        applyLivePatch(patch)
      } catch { /* ignore malformed */ }
    }
    sseSource.onerror = () => {
      sseStatus.value = 'polling'
      sseSource?.close()
      sseSource = null
      startPolling()
    }
  } catch {
    startPolling()
  }
}

function applyLivePatch(patch) {
  // patch = { id, boarded_count, absent_count, status }
  const idx = items.value.findIndex((r) => r.id === patch.id)
  if (idx === -1) return
  const updated = { ...items.value[idx], ...patch }
  items.value = [...items.value.slice(0, idx), updated, ...items.value.slice(idx + 1)]
  lastRefreshed.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function startPolling() {
  sseStatus.value = 'polling'
  pollTimer = setInterval(loadTrips, 30_000)
}

function stopLiveUpdates() {
  sseSource?.close()
  sseSource = null
  clearInterval(pollTimer)
  pollTimer = null
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([
    loadTrips(),
    listRoutes({ per_page: 100 }).then((r) => { routes.value = r?.items ?? r ?? [] }).catch(() => {}),
  ])
  startLiveUpdates()
})

onUnmounted(stopLiveUpdates)
watch([filterDate, filterSlot, filterStatus, filterRouteId], () => {
  loadTrips()
  stopLiveUpdates()
  startLiveUpdates()
})

// ── Trip detail modal §8.2 ────────────────────────────────────────────────────
const selectedTrip = ref(null)
const studentsLoading = ref(false)
const tripStudents = ref([])
const absentLoading = ref(null)
const absentTarget = ref(null)
const canMarkAbsent = computed(() => selectedTrip.value && ['scheduled', 'assigned'].includes(selectedTrip.value.status))

async function openStudentDetail(row) {
  selectedTrip.value = row
  studentsLoading.value = true
  tripStudents.value = []
  try {
    const res = await listPolicyTripStudents(row.id)
    tripStudents.value = res?.items ?? res ?? []
  } catch {
    tripStudents.value = []
  } finally {
    studentsLoading.value = false
  }
}

async function markAbsent(student, reason) {
  absentLoading.value = student.id
  try {
    await markPolicyTripStudentAbsent(student.id, reason)
    // Refresh student list
    const res = await listPolicyTripStudents(selectedTrip.value.id)
    tripStudents.value = res?.items ?? res ?? []
    await loadTrips()
  } catch {
    // handled by http interceptor
  } finally {
    absentLoading.value = null
  }
}

// ── Assign driver §6 ──────────────────────────────────────────────────────────
const assignTarget = ref(null)
const assignLoading = ref(false)
const assignError = ref('')
const driverSearch = ref('')
const driverResults = ref([])
const vehicleSearch = ref('')
const vehicleResults = ref([])
const assignForm = reactive({ driver_id: '', driver_name: '', vehicle_id: '', vehicle_plate: '', vehicle_capacity: null })

function openAssignDriver(row) {
  assignTarget.value = row
  assignError.value = ''
  driverSearch.value = ''
  vehicleSearch.value = ''
  driverResults.value = []
  vehicleResults.value = []
  Object.assign(assignForm, { driver_id: '', driver_name: '', vehicle_id: '', vehicle_plate: '', vehicle_capacity: null })
}

function closeAssignModal() {
  assignTarget.value = null
  assignError.value = ''
}

let driverDebounce = null
function searchDrivers() {
  clearTimeout(driverDebounce)
  assignForm.driver_id = ''
  assignForm.driver_name = ''
  if (!driverSearch.value.trim()) { driverResults.value = []; return }
  driverDebounce = setTimeout(async () => {
    try {
      const res = await listDrivers({ q: driverSearch.value, per_page: 10 })
      driverResults.value = res?.items ?? res ?? []
    } catch { driverResults.value = [] }
  }, 300)
}

function selectDriver(d) {
  assignForm.driver_id = String(d.id)
  assignForm.driver_name = d.full_name
  driverSearch.value = d.full_name
  driverResults.value = []
}

let vehicleDebounce = null
function searchVehicles() {
  clearTimeout(vehicleDebounce)
  assignForm.vehicle_id = ''
  assignForm.vehicle_plate = ''
  if (!vehicleSearch.value.trim()) { vehicleResults.value = []; return }
  vehicleDebounce = setTimeout(async () => {
    try {
      const res = await listVehicles({ q: vehicleSearch.value, per_page: 10 })
      vehicleResults.value = res?.items ?? res ?? []
    } catch { vehicleResults.value = [] }
  }, 300)
}

function selectVehicle(v) {
  assignForm.vehicle_id = String(v.id)
  assignForm.vehicle_plate = v.license_plate
  assignForm.vehicle_capacity = v.capacity ?? null
  vehicleSearch.value = v.license_plate
  vehicleResults.value = []
}

async function confirmAssign() {
  if (!assignTarget.value || !assignForm.driver_id || !assignForm.vehicle_id) return
  assignLoading.value = true
  assignError.value = ''
  try {
    await assignDriverToPolicyTrip(assignTarget.value.id, {
      driver_id: assignForm.driver_id,
      vehicle_id: assignForm.vehicle_id,
    })
    closeAssignModal()
    await loadTrips()
  } catch (err) {
    const status = err?.response?.status
    if (status === 409) {
      assignError.value = err?.response?.data?.message ?? 'Tài xế đã có chuyến khác trong khung giờ này (E2). Vui lòng chọn tài xế khác.'
    } else {
      assignError.value = err?.response?.data?.message ?? 'Không thực hiện được. Thử lại sau.'
    }
  } finally {
    assignLoading.value = false
  }
}

// ── Cancel trip ───────────────────────────────────────────────────────────────
const cancelTarget = ref(null)
const cancelReason = ref('')
const actionLoading = ref(false)

function openCancel(row) { cancelTarget.value = row; cancelReason.value = '' }

async function confirmCancel() {
  if (!cancelTarget.value || !cancelReason.value.trim()) return
  actionLoading.value = true
  try {
    await cancelPolicyTrip(cancelTarget.value.id, cancelReason.value.trim())
    cancelTarget.value = null
    cancelReason.value = ''
    await loadTrips()
  } catch { /* handled by interceptor */ } finally {
    actionLoading.value = false
  }
}

function fmtTime(ts) {
  if (!ts) return '—'
  try { return new Date(ts).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) } catch { return ts }
}
</script>
