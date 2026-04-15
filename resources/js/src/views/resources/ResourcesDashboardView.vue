<template>
  <div class="mx-auto max-w-[1920px] space-y-4 pb-6 text-slate-900">
    <!-- Header -->
    <header
      class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-start lg:justify-between"
    >
      <div>
        <h1 class="text-lg font-semibold tracking-tight md:text-xl">{{ t('resources_dashboard.title') }}</h1>
        <p class="mt-0.5 text-sm text-slate-600">{{ t('resources_dashboard.subtitle') }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-0.5 text-xs shadow-sm">
          <button
            v-for="mode in viewModes"
            :key="mode.id"
            type="button"
            class="rounded-md px-2.5 py-1.5 font-medium transition"
            :class="
              viewMode === mode.id
                ? 'bg-teal-600 text-white shadow-sm'
                : mode.id === 'day'
                  ? 'text-slate-600 hover:bg-slate-50'
                  : 'cursor-not-allowed text-slate-400'
            "
            :disabled="mode.id !== 'day'"
            :title="mode.id !== 'day' ? t('resources_dashboard.view_coming') : ''"
            @click="viewMode = mode.id"
          >
            {{ mode.label }}
          </button>
        </div>
        <div class="flex flex-wrap items-center gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 shadow-sm hover:bg-slate-50"
            @click="shiftDay(-1)"
          >
            ‹
          </button>
          <span class="min-w-[10rem] text-center text-sm font-medium tabular-nums text-slate-900">{{ dayTitle }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 shadow-sm hover:bg-slate-50"
            @click="shiftDay(1)"
          >
            ›
          </button>
          <button
            type="button"
            class="rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-800 hover:bg-teal-100"
            @click="goToday"
          >
            {{ t('dispatcher_board.today') }}
          </button>
        </div>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-sm hover:bg-slate-50"
          to="/dispatcher"
        >
          {{ t('resources_dashboard.open_dispatcher') }}
        </RouterLink>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-teal-600 bg-teal-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-teal-700"
          to="/resources/list"
        >
          {{ t('resources_dashboard.open_list') }}
        </RouterLink>
      </div>
    </header>

    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
      {{ loadError }}
    </div>

    <!-- KPI row: compact cards + wide forecast -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-12 xl:items-stretch">
      <div class="flex gap-2.5 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:col-span-1 xl:col-span-2">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
          <TruckIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-[11px] font-medium text-slate-500">{{ t('resources_dashboard.kpi_fleet') }}</div>
          <div v-if="statsLoading" class="mt-1 h-6 w-16 animate-pulse rounded bg-slate-200" />
          <template v-else>
            <div class="mt-0.5 text-lg font-bold tabular-nums leading-tight text-slate-900">{{ vehiclesOperational }} / {{ vehiclesTotal }}</div>
            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-100">
              <div class="h-full rounded-full bg-teal-500" :style="{ width: pct(vehiclesOperational, vehiclesTotal) }" />
            </div>
          </template>
        </div>
      </div>
      <div class="flex gap-2.5 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:col-span-1 xl:col-span-2">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
          <UsersIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-[11px] font-medium text-slate-500">{{ t('resources_dashboard.kpi_drivers') }}</div>
          <div v-if="statsLoading" class="mt-1 h-6 w-16 animate-pulse rounded bg-slate-200" />
          <template v-else>
            <div class="mt-0.5 text-lg font-bold tabular-nums leading-tight text-slate-900">{{ driversAvailable }} / {{ driversEmployed }}</div>
            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-100">
              <div class="h-full rounded-full bg-amber-400" :style="{ width: pct(driversAvailable, driversEmployed) }" />
            </div>
          </template>
        </div>
      </div>
      <div class="flex gap-2.5 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:col-span-2 xl:col-span-2">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
          <ExclamationTriangleIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-[11px] font-medium text-slate-500">{{ t('resources_dashboard.kpi_gaps') }}</div>
          <div v-if="statsLoading" class="mt-1 h-6 w-10 animate-pulse rounded bg-slate-200" />
          <template v-else>
            <div class="mt-0.5 flex items-baseline gap-1.5">
              <span class="text-lg font-bold tabular-nums text-rose-600">{{ coverageAlertCount }}</span>
              <span class="text-[10px] text-slate-500">{{ t('resources_dashboard.kpi_gaps_unit') }}</span>
            </div>
            <p class="mt-0.5 line-clamp-2 text-[10px] leading-snug text-slate-500">{{ t('resources_dashboard.kpi_gaps_hint') }}</p>
          </template>
        </div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:col-span-2 xl:col-span-6">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
              <ChartBarIcon class="h-4 w-4" aria-hidden="true" />
            </span>
            <div>
              <div class="text-[11px] font-medium text-slate-600">{{ t('resources_dashboard.kpi_forecast') }}</div>
              <p class="text-[10px] text-slate-400">{{ t('resources_dashboard.kpi_forecast_hint') }}</p>
            </div>
          </div>
        </div>
        <div class="mt-2 h-[88px] w-full sm:h-[96px]" role="img" :aria-label="t('resources_dashboard.kpi_forecast')">
          <svg class="h-full w-full overflow-visible" viewBox="0 0 120 48" preserveAspectRatio="none">
            <defs>
              <linearGradient id="rd-fill-dash" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="rgb(13 148 136)" stop-opacity="0.2" />
                <stop offset="100%" stop-color="rgb(13 148 136)" stop-opacity="0" />
              </linearGradient>
            </defs>
            <polyline
              :points="forecastCapacityPoints"
              fill="none"
              stroke="rgb(148 163 184)"
              stroke-width="0.8"
              stroke-dasharray="3 2"
              vector-effect="non-scaling-stroke"
            />
            <polygon :points="forecastDemandFill" fill="url(#rd-fill-dash)" />
            <polyline
              :points="forecastDemandPoints"
              fill="none"
              stroke="rgb(13 148 136)"
              stroke-width="1.2"
              vector-effect="non-scaling-stroke"
            />
          </svg>
        </div>
      </div>
    </div>

    <!-- 3-column workspace: horizontal scroll when viewport is tight -->
    <div class="min-w-0 overflow-x-auto overscroll-x-contain pb-1 [-webkit-overflow-scrolling:touch]">
      <div class="flex w-max min-w-full flex-col gap-4 xl:flex-row xl:items-stretch">
      <!-- Left: resource lists -->
      <aside
        class="flex w-full shrink-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm xl:w-[340px] xl:max-w-[340px]"
      >
        <div class="space-y-2 border-b border-slate-200 p-3">
          <label class="relative block">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              aria-hidden="true"
            />
            <span class="sr-only">{{ t('resources_dashboard.search') }}</span>
            <input
              v-model="sideSearch"
              type="search"
              class="w-full rounded-lg border border-slate-200 bg-slate-50/80 py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              :placeholder="t('resources_dashboard.search_placeholder')"
            />
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="block">
              <span class="mb-0.5 block text-[10px] font-medium text-slate-500">{{ t('resources_dashboard.filter_vehicle_type') }}</span>
              <select
                v-model="filterVehicleType"
                class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-7 text-[11px] text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              >
                <option value="">{{ t('resources_dashboard.filter_all') }}</option>
                <option value="van">{{ t('resources.type_van') }}</option>
                <option value="truck">{{ t('resources.type_truck') }}</option>
                <option value="bus">{{ t('resources.type_bus') }}</option>
              </select>
            </label>
            <label class="block">
              <span class="mb-0.5 block text-[10px] font-medium text-slate-500">{{ t('resources_dashboard.filter_vehicle_status') }}</span>
              <select
                v-model="filterVehicleStatus"
                class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-7 text-[11px] text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              >
                <option value="">{{ t('resources_dashboard.filter_all') }}</option>
                <option value="ready">{{ t('resources.vehicle_status_ready') }}</option>
                <option value="in_use">{{ t('resources.vehicle_status_in_use') }}</option>
                <option value="maintenance">{{ t('resources.status_maintenance') }}</option>
                <option value="broken">{{ t('resources_dashboard.vehicle_broken') }}</option>
              </select>
            </label>
            <label class="block">
              <span class="mb-0.5 block text-[10px] font-medium text-slate-500">{{ t('resources_dashboard.filter_driver_status') }}</span>
              <select
                v-model="filterDriverAvail"
                class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-7 text-[11px] text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              >
                <option value="">{{ t('resources_dashboard.filter_all') }}</option>
                <option value="available">{{ t('resources_dashboard.avail_available') }}</option>
                <option value="busy">{{ t('resources_dashboard.avail_busy') }}</option>
                <option value="offline">{{ t('resources_dashboard.avail_offline') }}</option>
              </select>
            </label>
            <label class="block">
              <span class="mb-0.5 block text-[10px] font-medium text-slate-500">{{ t('resources_dashboard.filter_supplier') }}</span>
              <select
                v-model="filterSupplier"
                class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-7 text-[11px] text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              >
                <option value="">{{ t('resources_dashboard.filter_all') }}</option>
                <option value="active">{{ t('resources_dashboard.supplier_active') }}</option>
                <option value="inactive">{{ t('resources_dashboard.supplier_inactive') }}</option>
              </select>
            </label>
          </div>
        </div>
        <div class="min-h-[200px] flex-1 space-y-2 overflow-y-auto p-2 xl:max-h-[calc(100dvh-16rem)]">
          <details class="group rounded-lg border border-slate-100 bg-slate-50/50 open:bg-white" open>
            <summary
              class="flex cursor-pointer list-none items-center justify-between gap-1 px-2 py-2 text-xs font-semibold text-slate-800 marker:content-none [&::-webkit-details-marker]:hidden"
            >
              <span>{{ t('resources_dashboard.col_drivers') }}</span>
              <span class="font-normal text-slate-500">({{ driversFiltered.length }}/{{ drivers.length }})</span>
            </summary>
            <ul class="space-y-1 border-t border-slate-100 px-1 pb-2 pt-1">
              <li v-for="d in driversFiltered" :key="'d' + d.id">
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-xs transition"
                  :class="
                    sidebarRowActive('driver', d.id)
                      ? 'bg-teal-50 ring-1 ring-teal-300'
                      : driverHoursWarn(d.id)
                        ? 'bg-rose-50/90 ring-1 ring-rose-200/80 hover:bg-rose-50'
                        : 'hover:bg-slate-50'
                  "
                  @click="setPanelDriver(d)"
                >
                  <span class="relative shrink-0">
                    <span
                      class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-[11px] font-semibold text-teal-900"
                    >
                      {{ initials(d.full_name) }}
                    </span>
                    <span
                      class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white"
                      :class="driverAvailDotClass(d)"
                    />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-1">
                      <TruckIcon class="h-3 w-3 shrink-0 text-slate-400" aria-hidden="true" />
                      <span class="block truncate font-medium text-slate-900">{{ d.full_name }}</span>
                    </span>
                    <span class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-[10px] text-slate-500">
                      <span class="rounded bg-teal-50 px-1 py-0.5 font-medium text-teal-800">{{ tripTypeLabelForDriver(d.id) }}</span>
                      <span class="font-mono text-slate-400">·</span>
                      <span class="font-mono text-[10px] text-slate-600">#{{ d.id }}</span>
                    </span>
                  </span>
                  <span
                    class="shrink-0 rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium tabular-nums text-slate-700"
                  >
                    {{ formatHoursShort(driverTripHours(d.id)) }}
                  </span>
                </button>
              </li>
            </ul>
          </details>
          <details class="group rounded-lg border border-slate-100 bg-slate-50/50 open:bg-white" open>
            <summary
              class="flex cursor-pointer list-none items-center justify-between gap-1 px-2 py-2 text-xs font-semibold text-slate-800 marker:content-none [&::-webkit-details-marker]:hidden"
            >
              <span>{{ t('resources_dashboard.col_vehicles') }}</span>
              <span class="font-normal text-slate-500">({{ vehiclesFiltered.length }}/{{ vehicles.length }})</span>
            </summary>
            <ul class="space-y-1 border-t border-slate-100 px-1 pb-2 pt-1">
              <li v-for="v in vehiclesFiltered" :key="'v' + v.id">
                <button
                  type="button"
                  class="flex w-full items-start gap-2 rounded-lg px-2 py-2 text-left text-xs transition"
                  :class="sidebarRowActive('vehicle', v.id) ? 'bg-teal-50 ring-1 ring-teal-300' : 'hover:bg-slate-50'"
                  @click="setPanelVehicle(v)"
                >
                  <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600"
                  >
                    <component :is="vehicleIconComponent(v)" class="h-4 w-4" aria-hidden="true" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="font-mono text-sm font-semibold text-slate-900">{{ v.license_plate }}</span>
                    <span class="mt-0.5 block text-[10px] text-slate-500">{{ vehicleSubtitle(v) }}</span>
                  </span>
                  <span
                    v-if="v.status === 'maintenance'"
                    class="shrink-0 rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-semibold text-amber-900"
                  >
                    {{ t('resources_dashboard.vehicle_maint_badge') }}
                  </span>
                </button>
              </li>
            </ul>
          </details>
          <details class="group rounded-lg border border-slate-100 bg-slate-50/50 open:bg-white" open>
            <summary
              class="flex cursor-pointer list-none items-center justify-between gap-1 px-2 py-2 text-xs font-semibold text-slate-800 marker:content-none [&::-webkit-details-marker]:hidden"
            >
              <span>{{ t('resources_dashboard.col_suppliers') }}</span>
              <span class="font-normal text-slate-500">({{ suppliersFiltered.length }}/{{ suppliers.length }})</span>
            </summary>
            <ul class="space-y-1 border-t border-slate-100 px-1 pb-2 pt-1">
              <li v-for="p in suppliersFiltered" :key="'p' + p.id">
                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-xs transition"
                  :class="sidebarRowActive('supplier', p.id) ? 'bg-teal-50 ring-1 ring-teal-300' : 'hover:bg-slate-50'"
                  @click="setPanelSupplier(p)"
                >
                  <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600"
                  >
                    <BuildingOffice2Icon class="h-4 w-4" aria-hidden="true" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium text-slate-900">{{ p.name }}</span>
                    <span class="block truncate text-[10px] text-slate-500">{{ supplierSubtitle(p) }}</span>
                  </span>
                  <span
                    class="shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-semibold"
                    :class="p.is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ p.is_active ? t('resources_dashboard.supplier_active') : t('resources_dashboard.supplier_inactive') }}
                  </span>
                </button>
              </li>
            </ul>
          </details>
        </div>
      </aside>

      <!-- Center: timeline — cố định min-width xl, không co khi cột chi tiết render -->
      <section
        class="w-full shrink-0 rounded-xl border border-slate-200 bg-white shadow-sm min-w-[min(100%,520px)] xl:min-w-[560px] xl:w-[560px] xl:max-w-[560px]"
      >
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-3">
          <div
            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-600"
            role="list"
            :aria-label="t('dispatcher_board.legend_aria')"
          >
            <span role="listitem" class="inline-flex items-center gap-1.5">
              <span class="h-2 w-2 shrink-0 rounded-sm bg-emerald-500" />
              {{ t('dispatcher_board.legend_assigned') }}
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5">
              <span class="h-2 w-2 shrink-0 rounded-sm bg-sky-500" />
              {{ t('dispatcher_board.legend_progress') }}
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5">
              <span class="h-2 w-2 shrink-0 rounded-sm bg-rose-500" />
              {{ t('dispatcher_board.legend_conflict') }}
            </span>
            <span v-if="canAssignTrip" class="text-slate-400">· {{ t('resources_dashboard.timeline_drag_hint') }}</span>
          </div>
        </div>
        <div class="overflow-x-auto">
          <div class="min-w-[1000px] p-3">
            <div class="mb-1 flex text-[10px] text-slate-500">
              <div class="w-[160px] shrink-0" />
              <div class="grid min-w-0 flex-1" :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }">
                <div
                  v-for="h in hourSlots"
                  :key="h"
                  class="border-l border-slate-200 pl-1 text-left tabular-nums"
                >
                  {{ String(h).padStart(2, '0') }}:00
                </div>
              </div>
            </div>
            <div v-if="loading && !trips.length" class="py-12 text-center text-sm text-slate-500">
              {{ t('dispatcher_board.loading') }}
            </div>
            <template v-else>
              <div
                v-for="(row, rowIdx) in timelineRows"
                :key="row.key"
                class="flex border-b border-slate-100"
              >
                <div class="flex w-[160px] shrink-0 flex-col justify-center border-r border-slate-200 py-2 pr-2 text-xs">
                  <span class="truncate font-medium text-slate-800">{{ row.label }}</span>
                  <span v-if="row.sub" class="truncate text-[10px] text-rose-600">{{ row.sub }}</span>
                  <span v-else-if="row.meta" class="truncate text-[10px] text-slate-500">{{ row.meta }}</span>
                </div>
                <div data-timeline-track class="relative min-h-[52px] min-w-0 flex-1 bg-slate-50/50">
                  <div
                    class="pointer-events-none absolute inset-0 grid"
                    :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }"
                  >
                    <div v-for="h in hourSlots" :key="`g-${row.key}-${h}`" class="border-l border-slate-200/90" />
                  </div>
                  <div
                    v-if="nowLinePct !== null"
                    class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-teal-500"
                    :style="{ left: `${nowLinePct}%` }"
                  >
                    <span
                      v-if="rowIdx === 0"
                      class="absolute -top-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded border border-slate-200 bg-white px-1 py-0.5 text-[9px] font-medium text-slate-700 shadow-sm"
                    >
                      {{ nowLabel }}
                    </span>
                  </div>
                  <button
                    v-for="bar in row.bars"
                    :key="bar.trip.id"
                    type="button"
                    class="absolute top-1.5 z-[5] flex h-9 items-center overflow-hidden rounded border px-1.5 text-left text-[10px] font-medium leading-tight shadow-sm transition hover:opacity-95"
                    :class="[
                      bar.toneClass,
                      isPanelTrip(bar.trip) ? 'ring-2 ring-teal-500 ring-offset-1' : '',
                      canAssignTrip ? 'cursor-grab active:cursor-grabbing touch-none' : '',
                    ]"
                    :style="tripBarStyle(bar)"
                    :title="`#${bar.trip.id}`"
                    @pointerdown="onTripBarPointerDown($event, bar.trip)"
                  >
                    <span class="truncate">#{{ bar.trip.id }}</span>
                    <span
                      v-if="bar.conflict"
                      class="ml-0.5 inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[8px] text-white"
                    >
                      !
                    </span>
                  </button>
                </div>
              </div>
              <p v-if="!timelineRows.length && !loading" class="py-8 text-center text-sm text-slate-500">
                {{ t('dispatcher_board.timeline_empty') }}
              </p>
            </template>
          </div>
        </div>
      </section>

      <!-- Right: action & constraints (chỉ hiện khi đã chọn tài xế / xe / NCC / chuyến) -->
      <aside
        v-if="panel"
        class="flex w-full shrink-0 flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-gradient-to-b from-slate-50/90 to-white shadow-md ring-1 ring-slate-900/5 xl:w-[460px] xl:min-w-[460px] xl:max-w-[460px]"
      >
        <div class="border-b border-slate-200/80 bg-white/80 backdrop-blur-sm">
          <div class="flex items-center justify-between gap-2 px-3 py-2.5">
            <div class="min-w-0 flex flex-1 items-center gap-2">
              <h2 class="text-sm font-semibold tracking-tight text-slate-900">{{ t('resources_dashboard.panel_title') }}</h2>
              <button
                type="button"
                class="inline-flex shrink-0 items-center gap-0.5 rounded-md px-1.5 py-0.5 text-[11px] font-medium text-teal-700 hover:bg-teal-50"
                :aria-expanded="panelHeaderHelpOpen"
                :aria-label="t('resources_dashboard.panel_header_hint_toggle')"
                @click="panelHeaderHelpOpen = !panelHeaderHelpOpen"
              >
                {{ t('resources_dashboard.action_details') }}
                <ChevronDownIcon
                  class="h-3.5 w-3.5 transition"
                  :class="panelHeaderHelpOpen ? 'rotate-180' : ''"
                  aria-hidden="true"
                />
              </button>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-800"
              :aria-label="t('resources_dashboard.panel_close')"
              @click="clearPanel"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <p
            v-show="panelHeaderHelpOpen"
            class="border-t border-slate-100/80 px-3 py-2 text-[11px] leading-snug text-slate-500"
          >
            {{ t('resources_dashboard.panel_subtitle_v2') }}
          </p>
        </div>

        <!-- Trip -->
        <div v-if="panel.type === 'trip'" class="flex flex-1 flex-col overflow-hidden">
          <div class="flex-1 space-y-4 overflow-y-auto px-3 py-3 text-sm">
            <!-- Trip hero card -->
            <div
              class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5"
            >
              <div class="border-l-4 border-teal-500 pl-4 pr-3 pt-3.5 pb-4">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                      {{ t('resources_dashboard.trip_label') }}
                    </p>
                    <h3 class="mt-1 line-clamp-2 text-[15px] font-semibold leading-snug text-slate-900">
                      {{ tripTitle(panel.trip) }}
                    </h3>
                    <p class="mt-1 font-mono text-xs text-teal-700">#{{ panel.trip.id }}</p>
                  </div>
                  <span
                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="tripStatusBadgeClass(panel.trip)"
                  >
                    {{ labelTripStatus(panel.trip.status) }}
                  </span>
                </div>
                <dl class="mt-4 space-y-2.5 border-t border-slate-100 pt-3 text-xs">
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">{{ t('resources_dashboard.field_trip_type') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ tripTypeLine(panel.trip) }}</dd>
                  </div>
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">{{ t('resources_dashboard.field_resource') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ tripResourceLabel(panel.trip) }}</dd>
                  </div>
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">{{ t('resources_dashboard.field_time') }}</dt>
                    <dd class="text-right tabular-nums font-medium text-slate-900">
                      {{ fmtTime(panel.trip.depart_at) }} – {{ fmtTime(tripEndAt(panel.trip)) }}
                    </dd>
                  </div>
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">{{ t('resources_dashboard.field_vehicle') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ panel.trip.vehicle?.license_plate ?? '—' }}</dd>
                  </div>
                </dl>
                <div class="mt-4 grid grid-cols-2 gap-2">
                  <RouterLink
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-800 px-3 py-2.5 text-center text-xs font-semibold text-white shadow-sm transition hover:bg-slate-900"
                    :to="`/trips/${panel.trip.id}`"
                  >
                    <PencilSquareIcon class="h-4 w-4 opacity-90" aria-hidden="true" />
                    {{ t('resources_dashboard.action_edit_trip') }}
                  </RouterLink>
                  <RouterLink
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                    to="/dispatcher"
                  >
                    {{ t('resources_dashboard.action_dispatch_board') }}
                  </RouterLink>
                </div>
                <button
                  type="button"
                  class="mt-2 w-full rounded-lg py-2 text-[11px] font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                  @click="clearPanel"
                >
                  {{ t('resources_dashboard.action_clear') }}
                </button>
              </div>
            </div>

            <!-- Compliance -->
            <section>
              <h4 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                {{ t('resources_dashboard.panel_section_compliance') }}
              </h4>
              <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-900/5">
                <ul class="space-y-2.5 text-[11px]">
                  <li class="flex items-start gap-2 text-slate-800">
                    <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" aria-hidden="true" />
                    <span>{{ t('resources_dashboard.panel_license_ok') }}</span>
                  </li>
                  <li class="flex items-start gap-2 text-slate-800">
                    <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" aria-hidden="true" />
                    <span>{{ t('resources_dashboard.panel_vehicle_ok') }}</span>
                  </li>
                </ul>

                <div
                  v-if="tripShiftLimitWarn"
                  class="mt-3 rounded-lg border border-rose-200/90 bg-rose-50/90 p-2.5"
                >
                  <div class="flex items-start gap-2">
                    <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
                    <div>
                      <p class="text-[11px] font-semibold text-rose-900">{{ t('resources_dashboard.shift_limit_title') }}</p>
                      <p class="mt-0.5 text-[11px] leading-snug text-rose-800/95">
                        {{ t('resources_dashboard.shift_limit_body') }}
                      </p>
                    </div>
                  </div>
                </div>

                <div
                  v-if="tripPanelConflict"
                  class="mt-3 rounded-lg border border-rose-200/90 bg-rose-50/90 p-2.5"
                >
                  <div class="flex items-start gap-2">
                    <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
                    <div class="min-w-0 flex-1">
                      <p class="text-[11px] font-semibold text-rose-900">{{ t('resources_dashboard.conflict_block_title') }}</p>
                      <p class="mt-0.5 text-[11px] leading-snug text-rose-800/95">
                        {{ t('resources_dashboard.panel_trip_conflict') }}
                      </p>
                      <button
                        v-if="suggestedProvider"
                        type="button"
                        class="mt-2 text-left text-[11px] font-semibold text-teal-700 hover:text-teal-800 hover:underline"
                        @click="scrollToSuggestedCoverage"
                      >
                        {{ t('resources_dashboard.action_view_alternatives') }} →
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <!-- Suggested coverage -->
            <section
              v-if="tripPanelConflict && suggestedProvider"
              id="panel-suggested-coverage"
              class="scroll-mt-4"
            >
              <h4 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                {{ t('resources_dashboard.panel_section_suggested') }}
              </h4>
              <p class="mb-2 text-[10px] text-slate-400">{{ t('resources_dashboard.panel_suggested_context') }}</p>
              <div
                class="flex items-stretch gap-3 rounded-xl border border-teal-200/90 bg-gradient-to-br from-teal-50/90 to-white p-3 shadow-sm ring-1 ring-teal-900/5"
              >
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white shadow-inner"
                >
                  <BuildingOffice2Icon class="h-5 w-5" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-slate-900">{{ suggestedProvider.name }}</p>
                  <p class="mt-0.5 text-[11px] text-slate-500">
                    {{ t('resources_dashboard.est_cost_label') }}:
                    <span class="font-medium text-slate-700">{{ t('resources_dashboard.est_cost_na') }}</span>
                  </p>
                  <p class="mt-1 text-[10px] leading-snug text-slate-500">
                    {{ t('resources_dashboard.panel_suggested_ncc_hint') }}
                  </p>
                </div>
                <button
                  type="button"
                  class="shrink-0 self-center rounded-lg bg-teal-600 px-3 py-2 text-[11px] font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
                  :disabled="assigningNcc || !canAssignTrip"
                  @click="assignSuggestedNcc"
                >
                  {{ t('resources_dashboard.action_assign_ncc') }}
                </button>
              </div>
            </section>
          </div>
        </div>

        <!-- Driver -->
        <div v-else-if="panel.type === 'driver'" class="flex flex-1 flex-col overflow-hidden">
          <div class="flex-1 space-y-3 overflow-y-auto px-3 py-3 text-sm">
            <!-- Compact summary: avatar + label + name + id on one row -->
            <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm ring-1 ring-slate-900/5">
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <span
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 text-sm font-semibold text-teal-900 ring-2 ring-offset-2 ring-offset-white"
                    :class="driverHoursWarn(panel.data.id) ? 'ring-rose-400' : 'ring-transparent'"
                  >
                    {{ initials(panel.data.full_name) }}
                  </span>
                  <span
                    class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white"
                    :class="driverAvailDotClass(panel.data)"
                  />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    {{ t('resources_dashboard.panel_driver_section') }}
                  </p>
                  <div class="mt-0.5 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <span class="truncate text-base font-semibold text-slate-900">{{ panel.data.full_name }}</span>
                    <span class="font-mono text-xs text-slate-500">#{{ panel.data.id }}</span>
                  </div>
                </div>
              </div>
              <div class="mt-3 flex gap-2">
                <button
                  type="button"
                  class="inline-flex flex-1 items-center justify-center gap-1 rounded-lg border border-slate-200 bg-slate-50/80 py-2 text-[11px] font-medium text-slate-700 hover:bg-slate-100"
                  :aria-expanded="panelDetailOpen"
                  @click="panelDetailOpen = !panelDetailOpen"
                >
                  {{ panelDetailOpen ? t('resources_dashboard.action_hide_details') : t('resources_dashboard.action_details') }}
                  <ChevronDownIcon
                    class="h-3.5 w-3.5 shrink-0 transition"
                    :class="panelDetailOpen ? 'rotate-180' : ''"
                    aria-hidden="true"
                  />
                </button>
                <button
                  type="button"
                  class="rounded-lg px-3 py-2 text-[11px] font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                  @click="clearPanel"
                >
                  {{ t('resources_dashboard.action_clear') }}
                </button>
              </div>
            </div>

            <div v-show="panelDetailOpen" class="space-y-3">
              <div
                v-if="driverHoursWarn(panel.data.id)"
                class="rounded-xl border border-rose-200 bg-rose-50/95 p-3 text-[11px] text-rose-900 shadow-sm"
              >
                <div class="flex items-start gap-2">
                  <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" />
                  <div>
                    <p class="font-semibold">{{ t('resources_dashboard.shift_limit_title') }}</p>
                    <p class="mt-1 leading-snug opacity-95">{{ t('resources_dashboard.panel_driver_hours_warn') }}</p>
                  </div>
                </div>
              </div>

              <section>
                <h4 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                  {{ t('resources_dashboard.panel_section_compliance') }}
                </h4>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                  <dl class="space-y-2.5 text-xs">
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500">{{ t('resources_dashboard.panel_driver_phone') }}</dt>
                      <dd class="text-right font-medium text-slate-900">{{ panel.data.phone ?? panel.data.user?.phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500">{{ t('resources_dashboard.panel_driver_license') }}</dt>
                      <dd class="text-right tabular-nums text-slate-900">{{ panel.data.license_expires_at ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500">{{ t('resources_dashboard.panel_driver_hours') }}</dt>
                      <dd class="text-right font-medium tabular-nums text-slate-900">
                        {{ formatHoursShort(driverTripHours(panel.data.id)) }}
                      </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500">{{ t('resources_dashboard.field_status') }}</dt>
                      <dd class="text-right text-slate-900">{{ availabilityLabel(panel.data) }}</dd>
                    </div>
                  </dl>
                </div>
              </section>

              <div>
                <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                  {{ t('resources_dashboard.panel_quick_actions') }}
                </p>
                <div class="flex flex-col gap-2">
                  <RouterLink
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-700"
                    :to="`/resources/drivers/${panel.data.id}`"
                  >
                    <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                    {{ t('resources_dashboard.panel_driver_open') }}
                  </RouterLink>
                  <RouterLink
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                    to="/dispatcher"
                  >
                    {{ t('resources_dashboard.action_dispatch_board') }}
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Vehicle -->
        <div v-else-if="panel.type === 'vehicle'" class="flex flex-1 flex-col overflow-hidden">
          <div class="flex-1 space-y-4 overflow-y-auto px-3 py-3 text-sm">
            <div
              class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5"
            >
              <div class="border-l-4 border-sky-400 pl-4 pr-3 pt-3.5 pb-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                  {{ t('resources_dashboard.panel_vehicle_section') }}
                </p>
                <p class="mt-1 font-mono text-xl font-bold tracking-tight text-slate-900">{{ panel.data.license_plate }}</p>
                <span
                  class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                  :class="vehicleStatusPillClass(panel.data.status)"
                >
                  {{ vehicleStatusLabel(panel.data.status) }}
                </span>
              </div>
            </div>

            <section>
              <h4 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                {{ t('resources_dashboard.panel_section_compliance') }}
              </h4>
              <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <ul class="space-y-2 text-[11px]">
                  <li class="flex items-start gap-2">
                    <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                    <span class="text-slate-700">{{ t('resources_dashboard.panel_vehicle_ok') }}</span>
                  </li>
                </ul>
                <dl class="mt-3 space-y-2.5 border-t border-slate-100 pt-3 text-xs">
                  <div class="flex justify-between gap-2">
                    <dt class="text-slate-500">{{ t('resources_dashboard.field_type') }}</dt>
                    <dd class="text-right text-slate-900">{{ panel.data.type ?? '—' }}</dd>
                  </div>
                  <div class="flex justify-between gap-2">
                    <dt class="text-slate-500">{{ t('resources_dashboard.panel_vehicle_seats') }}</dt>
                    <dd class="text-right tabular-nums text-slate-900">
                      {{ [panel.data.seat_count, panel.data.payload_kg].filter(Boolean).join(' · ') || '—' }}
                    </dd>
                  </div>
                  <div v-if="panel.data.maintenance_schedule_note || panel.data.last_maintenance_at" class="flex flex-col gap-1">
                    <dt class="text-slate-500">{{ t('resources_dashboard.panel_vehicle_note') }}</dt>
                    <dd class="text-slate-800">{{ panel.data.maintenance_schedule_note || panel.data.last_maintenance_at }}</dd>
                  </div>
                </dl>
              </div>
            </section>

            <div class="flex flex-wrap gap-2">
              <RouterLink
                class="inline-flex flex-1 min-w-[8rem] items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                to="/resources/list?tab=vehicles"
              >
                {{ t('resources_dashboard.panel_vehicle_open') }}
              </RouterLink>
              <button
                type="button"
                class="w-full rounded-lg py-2 text-[11px] font-medium text-slate-500 hover:bg-slate-50"
                @click="clearPanel"
              >
                {{ t('resources_dashboard.action_clear') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Supplier -->
        <div v-else-if="panel.type === 'supplier'" class="flex flex-1 flex-col overflow-hidden">
          <div class="flex-1 space-y-4 overflow-y-auto px-3 py-3 text-sm">
            <div
              class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5"
            >
              <div class="border-l-4 border-emerald-500 pl-4 pr-3 pt-3.5 pb-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                  {{ t('resources_dashboard.panel_supplier_section') }}
                </p>
                <h3 class="mt-1 text-base font-semibold text-slate-900">{{ panel.data.name }}</h3>
                <span
                  class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                  :class="panel.data.is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-700'"
                >
                  {{ panel.data.is_active ? t('resources_dashboard.supplier_active') : t('resources_dashboard.supplier_inactive') }}
                </span>
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
              <dl class="space-y-2.5 text-xs">
                <div class="flex justify-between gap-2">
                  <dt class="text-slate-500">{{ t('resources_dashboard.panel_supplier_contact') }}</dt>
                  <dd class="text-right text-slate-900">
                    {{ [panel.data.contact_name, panel.data.contact_phone].filter(Boolean).join(' · ') || '—' }}
                  </dd>
                </div>
                <div class="flex justify-between gap-2">
                  <dt class="text-slate-500">{{ t('resources_dashboard.panel_supplier_contract') }}</dt>
                  <dd class="text-right tabular-nums text-slate-900">{{ panel.data.contract_expires_at ?? '—' }}</dd>
                </div>
              </dl>
              <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] leading-snug text-slate-500">
                {{ t('resources_dashboard.supplier_sla_hint') }}
              </p>
            </div>

            <div class="flex flex-wrap gap-2">
              <RouterLink
                class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                to="/resources/list?tab=suppliers"
              >
                {{ t('resources_dashboard.open_list') }}
              </RouterLink>
              <button
                type="button"
                class="w-full rounded-lg py-2 text-[11px] font-medium text-slate-500 hover:bg-slate-50"
                @click="clearPanel"
              >
                {{ t('resources_dashboard.action_clear') }}
              </button>
            </div>
          </div>
        </div>
      </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BuildingOffice2Icon,
  ChartBarIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  TruckIcon,
  UsersIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { listDrivers, listTransportProviders, listVehicles } from '../../api/operational'
import { assignTrip, getTrip, listTrips, rescheduleTrip } from '../../api/trips'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import { labelTripStatus, labelTripType } from '../../util/labels'
import { shortViDayLabel, toLocalDateKey } from '../../util/dates'
import { VEHICLE_ICON_COMPONENTS, vehicleIconKind } from '../../util/vehicleIcon'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const canAssignTrip = computed(() => auth.hasPermission('trip.assign'))

const GRID_START = 6
const GRID_END = 22
const hourSlots = []
for (let h = GRID_START; h < GRID_END; h++) hourSlots.push(h)

const viewMode = ref('day')
const viewModes = computed(() => [
  { id: 'day', label: t('resources_dashboard.view_day') },
  { id: 'week', label: t('resources_dashboard.view_week') },
  { id: 'month', label: t('resources_dashboard.view_month') },
])

const loading = ref(false)
const loadError = ref('')
const trips = ref([])
/** @type {import('vue').Ref<null | { type: 'trip'; trip: object } | { type: 'driver'; data: object } | { type: 'vehicle'; data: object } | { type: 'supplier'; data: object }>} */
const panel = ref(null)
const selectedDate = ref(new Date())
const sideSearch = ref('')
const filterVehicleType = ref('')
const filterVehicleStatus = ref('')
const filterDriverAvail = ref('')
const filterSupplier = ref('')

const syncingFromRoute = ref(false)
/** Mô tả dài dưới tiêu đề panel — chỉ hiện khi bấm "Chi tiết" */
const panelHeaderHelpOpen = ref(false)
/** Nội dung chi tiết (tuân thủ, cảnh báo, …) — tài xế và có thể dùng cho các panel khác */
const panelDetailOpen = ref(false)
const sidebarNavIndex = ref(-1)
const assigningNcc = ref(false)
/** @type {import('vue').Ref<null | { trip: object; startX: number; startDepart: number; lockVersion: number; track: Element; pointerId: number; barEl: Element }>} */
const dragState = ref(null)
const dragOffsetPx = ref(0)

const statsLoading = ref(false)
const vehiclesTotal = ref(0)
const vehiclesReady = ref(0)
const vehiclesInUse = ref(0)
const driversEmployed = ref(0)
const driversAvailable = ref(0)

const drivers = ref([])
const vehicles = ref([])
const suppliers = ref([])

const dayKey = computed(() => toLocalDateKey(selectedDate.value))

const dayTitle = computed(() => {
  const k = dayKey.value
  const today = toLocalDateKey(new Date())
  if (k === today) return `${t('dispatcher_board.today')}, ${shortViDayLabel(k)}`
  return shortViDayLabel(k)
})

const vehiclesOperational = computed(() => vehiclesReady.value + vehiclesInUse.value)

const searchLower = computed(() => sideSearch.value.trim().toLowerCase())

const driversFiltered = computed(() => {
  let list = drivers.value
  if (filterDriverAvail.value) {
    list = list.filter((d) => d.availability_status === filterDriverAvail.value)
  }
  const q = searchLower.value
  if (q) {
    list = list.filter((d) => {
      const name = (d.full_name ?? '').toLowerCase()
      return name.includes(q) || String(d.id).includes(q)
    })
  }
  return list
})

const vehiclesFiltered = computed(() => {
  let list = vehicles.value
  if (filterVehicleType.value) {
    list = list.filter((v) => (v.type || '').toLowerCase() === filterVehicleType.value)
  }
  if (filterVehicleStatus.value) {
    list = list.filter((v) => v.status === filterVehicleStatus.value)
  }
  const q = searchLower.value
  if (q) {
    list = list.filter((v) => (v.license_plate ?? '').toLowerCase().includes(q))
  }
  return list
})

const suppliersFiltered = computed(() => {
  let list = suppliers.value
  if (filterSupplier.value === 'active') list = list.filter((p) => p.is_active)
  else if (filterSupplier.value === 'inactive') list = list.filter((p) => !p.is_active)
  const q = searchLower.value
  if (q) list = list.filter((p) => (p.name ?? '').toLowerCase().includes(q))
  return list
})

const sidebarNavList = computed(() => {
  const out = []
  for (const d of driversFiltered.value) out.push({ kind: 'driver', data: d })
  for (const v of vehiclesFiltered.value) out.push({ kind: 'vehicle', data: v })
  for (const p of suppliersFiltered.value) out.push({ kind: 'supplier', data: p })
  return out
})

function panelToQuery(p) {
  if (!p) return null
  if (p.type === 'trip') return `trip:${p.trip.id}`
  if (p.type === 'driver') return `driver:${p.data.id}`
  if (p.type === 'vehicle') return `vehicle:${p.data.id}`
  if (p.type === 'supplier') return `supplier:${p.data.id}`
  return null
}

function panelsEqual(a, b) {
  if (!a && !b) return true
  if (!a || !b) return false
  if (a.type !== b.type) return false
  if (a.type === 'trip') return a.trip.id === b.trip.id
  return a.data.id === b.data.id
}

function sidebarRowActive(kind, id) {
  const i = sidebarNavIndex.value
  if (i < 0) return false
  const e = sidebarNavList.value[i]
  return Boolean(e && e.kind === kind && e.data.id === id)
}

function setPanelTrip(trip) {
  panel.value = { type: 'trip', trip }
  sidebarNavIndex.value = -1
}

function setPanelDriver(d) {
  panel.value = { type: 'driver', data: d }
  const idx = sidebarNavList.value.findIndex((e) => e.kind === 'driver' && e.data.id === d.id)
  sidebarNavIndex.value = idx
}

function setPanelVehicle(v) {
  panel.value = { type: 'vehicle', data: v }
  const idx = sidebarNavList.value.findIndex((e) => e.kind === 'vehicle' && e.data.id === v.id)
  sidebarNavIndex.value = idx
}

function setPanelSupplier(p) {
  panel.value = { type: 'supplier', data: p }
  const idx = sidebarNavList.value.findIndex((e) => e.kind === 'supplier' && e.data.id === p.id)
  sidebarNavIndex.value = idx
}

function clearPanel() {
  panel.value = null
  sidebarNavIndex.value = -1
  panelHeaderHelpOpen.value = false
  panelDetailOpen.value = false
}

function navigateSidebar(delta) {
  const list = sidebarNavList.value
  const n = list.length
  if (!n) return
  let i = sidebarNavIndex.value
  if (i < 0) i = delta > 0 ? 0 : n - 1
  else i = Math.max(0, Math.min(n - 1, i + delta))
  const entry = list[i]
  if (!entry) return
  if (entry.kind === 'driver') setPanelDriver(entry.data)
  else if (entry.kind === 'vehicle') setPanelVehicle(entry.data)
  else setPanelSupplier(entry.data)
}

function onGlobalKeydown(e) {
  if (e.key === 'Escape') {
    if (panel.value) {
      e.preventDefault()
      clearPanel()
    }
    return
  }
  const tag = e.target?.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    navigateSidebar(1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    navigateSidebar(-1)
  }
}

async function applyPanelFromQuery() {
  const raw = route.query.panel
  if (!raw || typeof raw !== 'string') {
    if (panel.value) {
      syncingFromRoute.value = true
      panel.value = null
      sidebarNavIndex.value = -1
      syncingFromRoute.value = false
    }
    return
  }
  const colon = raw.indexOf(':')
  if (colon === -1) return
  const kind = raw.slice(0, colon)
  const id = Number(raw.slice(colon + 1))
  if (!id) return

  let next = null
  if (kind === 'driver') {
    const d = drivers.value.find((x) => x.id === id)
    if (d) next = { type: 'driver', data: d }
  } else if (kind === 'vehicle') {
    const v = vehicles.value.find((x) => x.id === id)
    if (v) next = { type: 'vehicle', data: v }
  } else if (kind === 'supplier') {
    const p = suppliers.value.find((x) => x.id === id)
    if (p) next = { type: 'supplier', data: p }
  } else if (kind === 'trip') {
    let tr = trips.value.find((x) => x.id === id)
    if (!tr) {
      try {
        tr = await getTrip(id)
      } catch {
        tr = null
      }
    }
    if (tr) next = { type: 'trip', trip: tr }
  }
  if (!next) return
  if (panelsEqual(panel.value, next)) return

  syncingFromRoute.value = true
  try {
    if (next.type === 'trip') setPanelTrip(next.trip)
    else if (next.type === 'driver') setPanelDriver(next.data)
    else if (next.type === 'vehicle') setPanelVehicle(next.data)
    else setPanelSupplier(next.data)
  } finally {
    syncingFromRoute.value = false
  }
}

watch(panel, (p) => {
  panelHeaderHelpOpen.value = false
  panelDetailOpen.value = false
  if (syncingFromRoute.value) return
  const want = panelToQuery(p)
  const cur = route.query.panel
  if (cur === want || (!cur && !want)) return
  const q = { ...route.query }
  if (want) q.panel = want
  else delete q.panel
  router.replace({ query: q })
})

watch(
  () => route.query.panel,
  () => {
    applyPanelFromQuery()
  },
)

function isPanelTrip(trip) {
  return panel.value?.type === 'trip' && panel.value.trip?.id === trip.id
}

function isPanelDriver(d) {
  return panel.value?.type === 'driver' && panel.value.data?.id === d.id
}

function isPanelVehicle(v) {
  return panel.value?.type === 'vehicle' && panel.value.data?.id === v.id
}

function isPanelSupplier(p) {
  return panel.value?.type === 'supplier' && panel.value.data?.id === p.id
}

function driverTripHours(driverId) {
  let ms = 0
  for (const trip of timelineTrips.value) {
    if (trip.driver_id !== driverId) continue
    ms += tripEndAt(trip).getTime() - new Date(trip.depart_at).getTime()
  }
  return ms / 3600000
}

function formatHoursShort(h) {
  const n = Number(h) || 0
  if (n <= 0) return '0m'
  const hh = Math.floor(n)
  const mm = Math.round((n - hh) * 60)
  if (hh) return `${hh}h ${mm}m`
  return `${mm}m`
}

function driverHoursWarn(driverId) {
  return driverTripHours(driverId) > 10
}

function driverAvailDotClass(d) {
  const a = d.availability_status
  if (a === 'available') return 'bg-emerald-500'
  if (a === 'busy') return 'bg-amber-500'
  if (a === 'offline') return 'bg-slate-400'
  return 'bg-slate-300'
}

function tripTypeLabelForDriver(driverId) {
  const list = timelineTrips.value.filter((x) => x.driver_id === driverId)
  const first = list[0]
  if (!first) return '—'
  const tt = dr(first)?.trip_type
  if (!tt) return '—'
  return labelTripType(tt)
}

function vehicleIconComponent(v) {
  const k = vehicleIconKind(v)
  return VEHICLE_ICON_COMPONENTS[k] || TruckIcon
}

function vehicleSubtitle(v) {
  const parts = []
  if (v.seat_count != null) parts.push(`${v.seat_count} chỗ`)
  if (v.payload_kg != null) parts.push(`${v.payload_kg} kg`)
  parts.push(vehicleStatusLabel(v.status))
  return parts.join(' · ')
}

function supplierSubtitle(p) {
  const kind = p.type === 'taxi' ? 'Taxi' : 'Vendor'
  return [kind, p.contact_phone].filter(Boolean).join(' · ') || '—'
}

function vehicleStatusPillClass(s) {
  if (s === 'maintenance') return 'bg-amber-100 text-amber-900'
  if (s === 'broken') return 'bg-rose-100 text-rose-900'
  if (s === 'in_use') return 'bg-sky-100 text-sky-800'
  return 'bg-emerald-100 text-emerald-800'
}

function dr(trip) {
  return trip.dispatch_request ?? trip.dispatchRequest
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

function fmtTime(v) {
  if (!v) return '—'
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Date(v).toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })
}

function hourValue(d) {
  const x = new Date(d)
  return x.getHours() + x.getMinutes() / 60 + x.getSeconds() / 3600
}

function tripEndAt(trip) {
  const r = dr(trip)
  const end = r?.arrive_by ?? trip.arrive_by
  if (end) return new Date(end)
  const s = new Date(trip.depart_at)
  return new Date(s.getTime() + 60 * 60 * 1000)
}

function pctRange(trip) {
  const start = hourValue(trip.depart_at)
  const end = hourValue(tripEndAt(trip))
  const span = GRID_END - GRID_START
  const left = ((Math.max(GRID_START, start) - GRID_START) / span) * 100
  const right = ((Math.min(GRID_END, end) - GRID_START) / span) * 100
  const width = Math.max(right - left, 1.5)
  return { left, width: Math.min(width, 100 - left) }
}

function barTone(trip, conflict) {
  if (conflict) return 'border-rose-400 bg-rose-100 text-rose-900'
  if (trip.status === 'in_progress') return 'border-sky-400 bg-sky-100 text-sky-900'
  if (['assigned', 'driver_confirmed'].includes(trip.status)) {
    return 'border-emerald-400 bg-emerald-100 text-emerald-900'
  }
  return 'border-slate-300 bg-slate-200 text-slate-800'
}

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
        const aEnd = tripEndAt(a).getTime()
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

const timelineTrips = computed(() =>
  trips.value.filter((x) => !['pending', 'approved', 'cancelled'].includes(x.status)),
)

const conflictIds = computed(() => computeConflicts(timelineTrips.value))

const tripPanelConflict = computed(() => {
  if (panel.value?.type !== 'trip') return false
  return conflictIds.value.has(panel.value.trip.id)
})

const suggestedProvider = computed(() => {
  if (panel.value?.type !== 'trip' || !tripPanelConflict.value) return null
  if (!canAssignTrip.value) return null
  const trip = panel.value.trip
  const currentPid = trip.transport_provider_id ?? trip.transport_provider?.id ?? null
  const pool = suppliers.value.filter((p) => p.is_active)
  if (!pool.length) return null
  const other = pool.find((p) => p.id !== currentPid)
  return other ?? null
})

const tripShiftLimitWarn = computed(() => {
  if (panel.value?.type !== 'trip') return false
  const id = panel.value.trip.driver_id
  if (!id) return false
  return driverTripHours(id) > 10
})

function tripStatusBadgeClass(trip) {
  if (trip.status === 'in_progress') return 'bg-sky-100 text-sky-900'
  if (['assigned', 'driver_confirmed'].includes(trip.status)) return 'bg-teal-100 text-teal-900'
  return 'bg-slate-100 text-slate-800'
}

function tripResourceLabel(trip) {
  const tpId = trip.transport_provider_id ?? trip.transport_provider?.id ?? trip.transportProvider?.id
  if (tpId) {
    const name = trip.transport_provider?.name ?? trip.transportProvider?.name
    if (name) return `${name} (NCC)`
  }
  if (trip.driver?.full_name) return trip.driver.full_name
  return t('dispatcher_board.row_unassigned')
}

function tripTypeLine(trip) {
  const tt = dr(trip)?.trip_type
  if (!tt) return '—'
  return labelTripType(tt)
}

function scrollToSuggestedCoverage() {
  document.getElementById('panel-suggested-coverage')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
}

function tripBarStyle(bar) {
  const { left, width } = pctRange(bar.trip)
  const base = { left: `${left}%`, width: `max(${width}%, 2%)` }
  const tid = bar.trip.id
  if (dragState.value?.trip?.id === tid && dragOffsetPx.value) {
    return { ...base, transform: `translateX(${dragOffsetPx.value}px)` }
  }
  return base
}

function onTripBarPointerMove(e) {
  if (!dragState.value) return
  dragOffsetPx.value = e.clientX - dragState.value.startX
}

async function onTripBarPointerUp(e) {
  if (!dragState.value) return
  const st = dragState.value
  const trip = st.trip
  const deltaX = e.clientX - st.startX
  const track = st.track
  const { pointerId, barEl } = st
  dragState.value = null
  dragOffsetPx.value = 0
  window.removeEventListener('pointermove', onTripBarPointerMove)
  window.removeEventListener('pointerup', onTripBarPointerUp, true)
  try {
    barEl.releasePointerCapture(pointerId)
  } catch {
    /* ignore */
  }

  if (!canAssignTrip.value) {
    setPanelTrip(trip)
    return
  }
  if (Math.abs(deltaX) < 6) {
    setPanelTrip(trip)
    return
  }
  const rect = track.getBoundingClientRect()
  if (rect.width < 1) return
  const spanH = GRID_END - GRID_START
  const deltaHours = (deltaX / rect.width) * spanH
  const newMs = st.startDepart + deltaHours * 3600000
  try {
    await rescheduleTrip(trip.id, {
      depart_at: new Date(newMs).toISOString(),
      lock_version: st.lockVersion,
    })
    showAppSuccess(t('resources_dashboard.reschedule_success'))
    await loadTrips()
    const t2 = trips.value.find((x) => x.id === trip.id)
    if (t2 && panel.value?.type === 'trip' && panel.value.trip.id === trip.id) {
      panel.value = { type: 'trip', trip: t2 }
    }
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

function onTripBarPointerDown(e, trip) {
  if (e.button !== 0) return
  const track = e.currentTarget.closest('[data-timeline-track]')
  if (!track) return
  dragState.value = {
    trip,
    startX: e.clientX,
    startDepart: new Date(trip.depart_at).getTime(),
    lockVersion: trip.lock_version ?? 0,
    track,
    pointerId: e.pointerId,
    barEl: e.currentTarget,
  }
  dragOffsetPx.value = 0
  try {
    e.currentTarget.setPointerCapture(e.pointerId)
  } catch {
    /* ignore */
  }
  window.addEventListener('pointermove', onTripBarPointerMove)
  window.addEventListener('pointerup', onTripBarPointerUp, true)
}

async function assignSuggestedNcc() {
  const trip = panel.value?.type === 'trip' ? panel.value.trip : null
  const sp = suggestedProvider.value
  if (!trip || !sp) return
  assigningNcc.value = true
  try {
    const full = await getTrip(trip.id)
    await assignTrip(trip.id, {
      lock_version: full.lock_version ?? 0,
      vehicle_id: null,
      driver_id: null,
      transport_provider_id: sp.id,
    })
    showAppSuccess(t('resources_dashboard.assign_ncc_success'))
    await loadTrips()
    const updated = trips.value.find((x) => x.id === trip.id) ?? (await getTrip(trip.id))
    if (updated) setPanelTrip(updated)
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    assigningNcc.value = false
  }
}

const coverageAlertCount = computed(() => conflictIds.value.size)

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
      byDriver.set(key, { key, label: name, meta: plate ?? '', trips: [] })
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
    rows.push({ key: r.key, label: r.label, sub: '', meta: r.meta, trips: r.trips })
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

const nowLabel = computed(() => {
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Date().toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })
})

/** 24 buckets: trips starting in that hour (timeline day) */
const tripsPerHour = computed(() => {
  const buckets = Array(24).fill(0)
  for (const trip of timelineTrips.value) {
    const h = new Date(trip.depart_at).getHours()
    buckets[h]++
  }
  return buckets
})

const forecastDemandPoints = computed(() => buildSparklinePoints(tripsPerHour.value, 120, 40, 4))
const forecastDemandFill = computed(() => {
  const pts = forecastDemandPoints.value
  if (!pts) return ''
  const base = pts.split(' ')
  if (!base.length) return ''
  const first = base[0].split(',')[0]
  const last = base[base.length - 1].split(',')[0]
  return `${pts} ${last},48 ${first},48`
})

/** Flat “capacity” curve from active drivers (normalized) */
const forecastCapacityPoints = computed(() => {
  const cap = Math.max(1, driversEmployed.value || 1)
  const flat = Array(24).fill(cap * 0.6)
  return buildSparklinePoints(flat, 120, 40, 4)
})

function buildSparklinePoints(values, w, h, pad) {
  const max = Math.max(...values, 1)
  const pts = values.map((v, i) => {
    const x = pad + ((w - pad * 2) * i) / (values.length - 1 || 1)
    const y = h - pad - ((h - pad * 2) * v) / max
    return `${x.toFixed(1)},${y.toFixed(1)}`
  })
  return pts.join(' ')
}

function pct(part, total) {
  const t = Number(total) || 0
  const p = Number(part) || 0
  if (t <= 0) return '0%'
  return `${Math.min(100, Math.round((p / t) * 100))}%`
}

function initials(name) {
  if (!name || typeof name !== 'string') return '?'
  const p = name.trim().split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return name.slice(0, 2).toUpperCase()
}

function availabilityLabel(d) {
  const a = d.availability_status
  const e = d.employment_status
  if (e && e !== 'active') return e
  if (a === 'available') return t('resources_dashboard.avail_available')
  if (a === 'busy') return t('resources_dashboard.avail_busy')
  if (a === 'offline') return t('resources_dashboard.avail_offline')
  return a ?? '—'
}

function vehicleStatusLabel(s) {
  const map = {
    ready: t('resources.vehicle_status_ready'),
    in_use: t('resources.vehicle_status_in_use'),
    maintenance: t('resources.status_maintenance'),
    broken: t('resources_dashboard.vehicle_broken'),
  }
  return map[s] ?? s ?? '—'
}

function shiftDay(delta) {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() + delta)
  selectedDate.value = d
}

function goToday() {
  selectedDate.value = new Date()
}

async function loadStats() {
  statsLoading.value = true
  try {
    const [vAll, vReady, vUse, dEmployed, dAvail] = await Promise.all([
      listVehicles({ per_page: 1 }),
      listVehicles({ per_page: 1, status: 'ready' }),
      listVehicles({ per_page: 1, status: 'in_use' }),
      listDrivers({ per_page: 1, employment_status: 'active' }),
      listDrivers({ per_page: 1, employment_status: 'active', availability_status: 'available' }),
    ])
    vehiclesTotal.value = vAll.meta?.total ?? 0
    vehiclesReady.value = vReady.meta?.total ?? 0
    vehiclesInUse.value = vUse.meta?.total ?? 0
    driversEmployed.value = dEmployed.meta?.total ?? 0
    driversAvailable.value = dAvail.meta?.total ?? 0
  } finally {
    statsLoading.value = false
  }
}

async function loadLists() {
  const [dRes, vRes, pRes] = await Promise.all([
    listDrivers({ per_page: 80, employment_status: 'active' }),
    listVehicles({ per_page: 80 }),
    listTransportProviders({ per_page: 40 }),
  ])
  drivers.value = dRes.items ?? []
  vehicles.value = vRes.items ?? []
  suppliers.value = pRes.items ?? []
}

async function loadTrips() {
  loading.value = true
  loadError.value = ''
  try {
    const k = dayKey.value
    const tr = await listTrips({ from: k, to: k, per_page: 100, page: 1 })
    trips.value = tr.items ?? []
  } catch (e) {
    loadError.value = e?.response?.data?.message ?? t('dispatcher_board.load_error')
    trips.value = []
  } finally {
    loading.value = false
  }
}

async function loadAll() {
  await Promise.all([loadStats(), loadLists(), loadTrips()])
}

watch(dayKey, () => {
  panel.value = null
  sidebarNavIndex.value = -1
  loadTrips()
})

onMounted(async () => {
  await loadAll()
  await applyPanelFromQuery()
  window.addEventListener('keydown', onGlobalKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onGlobalKeydown)
})
</script>
