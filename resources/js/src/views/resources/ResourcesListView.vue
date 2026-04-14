<template>
  <div
    class="resources-shell mx-auto max-w-[1600px] space-y-4 rounded-xl border border-slate-200 bg-white px-3 py-4 text-slate-900 shadow-sm sm:px-4 md:px-6 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
  >
    <!-- Header -->
    <div class="border-b border-slate-200/80 pb-4 dark:border-slate-700">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ t('resources.page_title') }}</h1>
            <span
              class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-800 ring-1 ring-inset ring-teal-600/20 dark:bg-teal-950/50 dark:text-teal-300 dark:ring-teal-600/40"
            >
              {{ t('resources.workspace_badge') }}
            </span>
          </div>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('resources.page_subtitle') }}</p>
        </div>
        <div class="relative w-full min-w-0 flex-1 sm:max-w-xs lg:max-w-sm">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
            aria-hidden="true"
          />
          <input
            v-model="search"
            type="search"
            class="min-h-[44px] w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-base text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 sm:min-h-0 sm:py-2 sm:text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
            :placeholder="searchPlaceholder"
          />
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-center gap-2 sm:gap-3">
        <!-- Xe: danh sách / thùng rác (cùng hàng với tab + nút) -->
        <div
          v-if="activeTab === 'vehicles'"
          class="inline-flex shrink-0 gap-0.5 rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-600 dark:bg-slate-800"
        >
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
              vehiclesViewMode === 'active'
                ? 'bg-white text-teal-800 shadow-sm dark:bg-slate-700 dark:text-teal-300'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
            @click="setVehiclesViewMode('active')"
          >
            {{ t('resources.vehicles_view_active') }}
          </button>
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
              vehiclesViewMode === 'trash'
                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
            @click="setVehiclesViewMode('trash')"
          >
            <TrashIcon class="h-4 w-4" aria-hidden="true" />
            {{ t('resources.vehicles_view_trash') }}
          </button>
        </div>

        <div
          v-if="activeTab === 'drivers'"
          class="inline-flex shrink-0 gap-0.5 rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-600 dark:bg-slate-800"
        >
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
              driversViewMode === 'active'
                ? 'bg-white text-teal-800 shadow-sm dark:bg-slate-700 dark:text-teal-300'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
            @click="setDriversViewMode('active')"
          >
            {{ t('resources.drivers_view_active') }}
          </button>
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
              driversViewMode === 'trash'
                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
            @click="setDriversViewMode('trash')"
          >
            <TrashIcon class="h-4 w-4" aria-hidden="true" />
            {{ t('resources.vehicles_view_trash') }}
          </button>
        </div>

        <div
          v-if="activeTab === 'suppliers'"
          class="inline-flex shrink-0 gap-0.5 rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-600 dark:bg-slate-800"
        >
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
              suppliersViewMode === 'active'
                ? 'bg-white text-teal-800 shadow-sm dark:bg-slate-700 dark:text-teal-300'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
            @click="setSuppliersViewMode('active')"
          >
            {{ t('resources.suppliers_view_active') }}
          </button>
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
              suppliersViewMode === 'trash'
                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
            @click="setSuppliersViewMode('trash')"
          >
            <TrashIcon class="h-4 w-4" aria-hidden="true" />
            {{ t('resources.vehicles_view_trash') }}
          </button>
        </div>

        <div class="min-w-0 flex-1 overflow-x-auto overscroll-x-contain sm:flex-initial">
          <div class="inline-flex shrink-0 gap-0.5 rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-600 dark:bg-slate-800">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              :class="[
                'shrink-0 snap-start rounded-md px-3 py-2 text-xs font-medium transition sm:py-1.5',
                activeTab === tab.id
                  ? 'bg-teal-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
              @click="setTab(tab.id)"
            >
              {{ t(tab.labelKey) }}
            </button>
          </div>
        </div>

        <div class="flex min-w-0 flex-wrap items-center justify-end gap-2 sm:ml-auto sm:shrink-0">
          <button
            v-if="activeTab === 'drivers' && driversViewMode === 'active' && canManageDrivers"
            type="button"
            class="inline-flex min-h-[44px] w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500 sm:min-h-0 sm:w-auto sm:py-2"
            @click="openAssignModal(null)"
          >
            {{ t('resources.assign_driver') }}
          </button>
          <button
            v-else-if="activeTab === 'vehicles' && canManageVehicles && vehiclesViewMode === 'active'"
            type="button"
            class="inline-flex min-h-[44px] w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500 sm:min-h-0 sm:w-auto sm:py-2"
            @click="openVehicleForm(null)"
          >
            {{ t('resources.add_vehicle') }}
          </button>
          <button
            v-else-if="activeTab === 'suppliers' && canManageProviders && suppliersViewMode === 'active'"
            type="button"
            class="inline-flex min-h-[44px] w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500 sm:min-h-0 sm:w-auto sm:py-2"
            @click="openProviderForm(null)"
          >
            {{ t('resources.add_supplier') }}
          </button>
          <button
            v-else-if="!(activeTab === 'vehicles' && vehiclesViewMode === 'trash') && !(activeTab === 'drivers' && driversViewMode === 'trash') && !(activeTab === 'suppliers' && suppliersViewMode === 'trash')"
            type="button"
            class="inline-flex min-h-[44px] w-full shrink-0 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 sm:min-h-0 sm:w-auto sm:py-2"
            disabled
          >
            {{ addButtonLabel }}
          </button>
        </div>
      </div>
    </div>

    <!-- Filters: horizontal bar (same pattern as Requests) -->
    <div
      v-show="!(activeTab === 'vehicles' && vehiclesViewMode === 'trash') && !(activeTab === 'drivers' && driversViewMode === 'trash') && !(activeTab === 'suppliers' && suppliersViewMode === 'trash')"
      class="rounded-2xl border border-violet-100/90 bg-gradient-to-r from-slate-50 via-violet-50/40 to-indigo-50/25 px-2 py-2 shadow-sm sm:px-3 sm:py-2.5 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900"
    >
      <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <details ref="resourceFilterMenuRef" class="group relative">
          <summary
            class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
              <span
                v-if="activeResourceFilterCount > 0"
                class="absolute -right-1.5 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-semibold leading-none text-white"
              >
                {{ activeResourceFilterCount > 9 ? '9+' : activeResourceFilterCount }}
              </span>
            </span>
            <ChevronDownIcon class="h-4 w-4 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[260px] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('resources.filter_menu_title') }}</p>
            <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
              <li v-if="filters.status" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_status') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterStatusLabel }}</span>
              </li>
              <li v-if="activeTab === 'vehicles' && filters.type" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_type') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterTypeLabel }}</span>
              </li>
              <li v-if="activeTab === 'vehicles' && filters.driver_default" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_driver_default') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterDriverDefaultLabel }}</span>
              </li>
              <li v-if="activeTab === 'vehicles' && filters.insurance" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_insurance') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterDocStateLabel(filters.insurance) }}</span>
              </li>
              <li v-if="activeTab === 'vehicles' && filters.inspection" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_inspection') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterDocStateLabel(filters.inspection) }}</span>
              </li>
              <li v-if="activeTab === 'vehicles' && filters.road_fee" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_road_fee') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterDocStateLabel(filters.road_fee) }}</span>
              </li>
              <li v-if="activeTab === 'suppliers' && filters.contract" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_contract') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterContractLabel }}</span>
              </li>
              <li v-if="activeTab === 'drivers' && filters.driver_license" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_driver_license') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterDocStateLabel(filters.driver_license) }}</span>
              </li>
              <li v-if="activeTab === 'drivers' && filters.driver_availability" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('resources.filter_driver_availability') }}</span>
                <span class="max-w-[60%] text-right font-medium">{{ resourceFilterDriverAvailabilityLabel }}</span>
              </li>
              <li v-if="activeResourceFilterCount === 0" class="text-slate-400">{{ t('resources.filter_menu_empty') }}</li>
            </ul>
            <button
              type="button"
              class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="resetResourceFilters(); closeResourceFilterMenu()"
            >
              {{ t('resources.filter_clear_all') }}
            </button>
          </div>
        </details>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-600" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <!-- Trạng thái -->
          <details class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ t('resources.filter_status') }}</span>
              <span class="min-w-0 max-w-[10rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                filters.status ? resourceFilterStatusLabel : t('resources.filter_all')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
            >
              <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                <li v-for="opt in resourceStatusFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      filters.status === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                    "
                    @click="applyResourceFilterPatch($event, { status: opt.value })"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </div>
          </details>

          <!-- Xe: loại, tài xế mặc định -->
          <template v-if="activeTab === 'vehicles'">
            <details class="group relative min-w-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ t('resources.filter_type') }}</span>
                <span class="min-w-0 max-w-[10rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                  filters.type ? resourceFilterTypeLabel : t('resources.filter_all')
                }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                  <li v-for="opt in resourceVehicleTypeFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filters.type === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="applyResourceFilterPatch($event, { type: opt.value })"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </details>

            <details class="group relative min-w-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ t('resources.filter_driver_default') }}</span>
                <span class="min-w-0 max-w-[9rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                  filters.driver_default ? resourceFilterDriverDefaultLabel : t('resources.filter_all')
                }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in resourceDriverDefaultFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filters.driver_default === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="applyResourceFilterPatch($event, { driver_default: opt.value })"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </details>
          </template>

          <!-- NCC: hợp đồng -->
          <template v-if="activeTab === 'drivers' && driversViewMode === 'active'">
            <details class="group relative min-w-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ t('resources.filter_driver_license') }}</span>
                <span class="min-w-0 max-w-[9rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                  filters.driver_license ? resourceFilterDocStateLabel(filters.driver_license) : t('resources.filter_all')
                }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in resourceDocStateFilterOptions" :key="'dl-' + (opt.value === '' ? '_all' : opt.value)">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filters.driver_license === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="applyResourceFilterPatch($event, { driver_license: opt.value })"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </details>

            <details class="group relative min-w-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ t('resources.filter_driver_availability') }}</span>
                <span class="min-w-0 max-w-[9rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                  filters.driver_availability ? resourceFilterDriverAvailabilityLabel : t('resources.filter_all')
                }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in resourceDriverAvailabilityFilterOptions" :key="'da-' + (opt.value === '' ? '_all' : opt.value)">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filters.driver_availability === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="applyResourceFilterPatch($event, { driver_availability: opt.value })"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </details>
          </template>

          <details v-if="activeTab === 'suppliers'" class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ t('resources.filter_contract') }}</span>
              <span class="min-w-0 max-w-[9rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                filters.contract ? resourceFilterContractLabel : t('resources.filter_all')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li v-for="opt in resourceDocStateFilterOptions" :key="'contract-' + (opt.value === '' ? '_all' : opt.value)">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      filters.contract === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                    "
                    @click="applyResourceFilterPatch($event, { contract: opt.value })"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </div>
          </details>

        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:hover:bg-slate-800 dark:hover:text-slate-200"
            :title="t('resources.filter_clear')"
            @click="resetResourceFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/50" />
            </span>
          </button>

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-600" aria-hidden="true" />

          <button
            v-if="activeTab === 'vehicles' && vehiclesViewMode === 'active'"
            type="button"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
            :aria-expanded="resourceFiltersExtraOpen"
            @click="resourceFiltersExtraOpen = !resourceFiltersExtraOpen"
          >
            {{ t('requests_page.filter_extra') }}
            <PlusCircleIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
          </button>
        </div>
      </div>

      <!-- Thuộc tính khác: BH / ĐK / phí (xe) -->
      <div
        v-show="resourceFiltersExtraOpen && activeTab === 'vehicles' && vehiclesViewMode === 'active'"
        class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-2 border-t border-violet-100/80 pt-3 dark:border-slate-700"
      >
        <details v-for="spec in resourceVehicleDocFiltersExtra" :key="'extra-' + spec.key" class="group relative min-w-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
          >
            <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ spec.label }}</span>
            <span class="min-w-0 max-w-[8rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
              filters[spec.key] ? resourceFilterDocStateLabel(filters[spec.key]) : t('resources.filter_all')
            }}</span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[200px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in resourceDocStateFilterOptions" :key="'ex-' + spec.key + '-' + (opt.value === '' ? '_all' : opt.value)">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters[spec.key] === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-200'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyResourceFilterPatch($event, { [spec.key]: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </div>
        </details>
      </div>
    </div>

    <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">{{ t('resources.loading') }}</div>
    <div v-else-if="error" class="px-4 py-12 text-center text-sm text-rose-600">{{ error }}</div>

    <div v-else class="relative flex min-h-[280px] flex-col xl:min-h-[420px] xl:flex-row">
      <!-- Table -->
      <div class="min-w-0 flex-1 overflow-x-auto p-2 sm:p-4 md:p-5">
        <!-- Xe: thẻ (mobile / tablet) -->
        <div v-if="activeTab === 'vehicles'" class="space-y-3 lg:hidden">
          <button
            v-for="v in paginatedVehicles"
            :key="v.id"
            type="button"
            class="group relative flex w-full items-stretch gap-0 overflow-hidden rounded-2xl border border-slate-200/90 bg-white text-left shadow-sm ring-1 ring-slate-900/5 transition active:scale-[0.99] dark:border-slate-700 dark:bg-slate-900/40 dark:ring-slate-900/40"
            :class="
              selectedVehicle?.id === v.id
                ? 'border-teal-300 ring-2 ring-teal-500/40 dark:border-teal-700'
                : 'hover:border-slate-300 hover:shadow-md dark:hover:border-slate-600'
            "
            @click="selectVehicle(v)"
          >
            <span
              class="w-1 shrink-0 rounded-l-2xl bg-gradient-to-b from-teal-500 to-teal-600"
              :class="selectedVehicle?.id === v.id ? 'opacity-100' : 'opacity-80 group-hover:opacity-100'"
              aria-hidden="true"
            />
            <div class="flex min-w-0 flex-1 items-start gap-3 p-3.5 pl-3 sm:p-4">
              <input
                v-if="canManageVehicles && (vehiclesViewMode === 'active' || vehiclesViewMode === 'trash')"
                type="checkbox"
                class="mt-1.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                :checked="bulkVehicleIds.includes(v.id)"
                @click.stop
                @change="toggleBulkVehicle(v.id)"
              />
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-50 to-teal-100/80 text-teal-700 shadow-inner dark:from-teal-950/60 dark:to-teal-900/40 dark:text-teal-400"
              >
                <component :is="vehicleIconComponent(v.iconKind)" class="h-6 w-6" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <div class="font-semibold tracking-tight text-slate-900 dark:text-white">{{ v.code }}</div>
                    <div class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-500 dark:text-slate-400">
                      {{ v.model }} · {{ v.typeLabel }}
                    </div>
                  </div>
                  <span :class="statusBadgeClass(v.status)">{{ labelVehicleStatus(v.status) }}</span>
                </div>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                  <span :class="compliancePillClass(v.insurance)">{{ t('resources.tag_ins') }} {{ insuranceHint(v.insurance) }}</span>
                  <span :class="compliancePillClass(v.inspection)">{{ t('resources.tag_reg') }} {{ insuranceHint(v.inspection) }}</span>
                </div>
                <div v-if="v.driverName" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                  <span class="font-medium text-slate-600 dark:text-slate-300">{{ t('resources.col_driver') }}:</span>
                  {{ v.driverName }}
                </div>
                <div
                  v-if="vehiclesViewMode === 'trash' && canManageVehicles"
                  class="mt-3 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-700"
                  @click.stop
                >
                  <button
                    type="button"
                    class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                    :disabled="vehicleRestoring"
                    @click="submitRestoreVehicleById(v.id)"
                  >
                    {{ t('resources.action_restore_vehicle') }}
                  </button>
                  <button
                    type="button"
                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"
                    :disabled="forcePermDeleting"
                    @click="openForceDeleteModal('vehicle', v.id, v.code)"
                  >
                    {{ t('resources.action_force_delete') }}
                  </button>
                </div>
              </div>
              <ChevronRightIcon
                v-if="vehiclesViewMode === 'active'"
                class="h-5 w-5 shrink-0 self-center text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-teal-600 dark:text-slate-600 dark:group-hover:text-teal-400"
                aria-hidden="true"
              />
            </div>
          </button>
        </div>
        <div
          v-if="activeTab === 'vehicles' && vehicleTotalFiltered"
          class="flex flex-wrap items-center justify-between gap-2 px-1 lg:hidden"
        >
          <p class="text-[11px] text-slate-500 dark:text-slate-400">
            {{ t('resources.pagination_showing', { from: vehicleRangeFrom, to: vehicleRangeTo, total: vehicleTotalFiltered }) }}
          </p>
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-if="vehiclesViewMode === 'active' && canManageVehicles && bulkVehicleIds.length"
              type="button"
              class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"
              @click="openBulkTrashModal('vehicle')"
            >
              {{ t('resources.bulk_move_to_trash', { n: bulkVehicleIds.length }) }}
            </button>
            <button
              v-else-if="vehiclesViewMode === 'trash' && canManageVehicles && bulkVehicleIds.length"
              type="button"
              class="rounded-lg border border-rose-300 bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200"
              @click="openBulkForceModal('vehicle')"
            >
              {{ t('resources.bulk_permanent_delete_n', { n: bulkVehicleIds.length }) }}
            </button>
            <label class="flex items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-400">
              {{ t('resources.pagination_per_page') }}
              <select
                v-model.number="vehiclePerPage"
                class="rounded-lg border border-slate-200 bg-white py-1 pl-2 pr-7 text-xs dark:border-slate-600 dark:bg-slate-800"
              >
                <option v-for="n in vehiclePerPageOptions" :key="n" :value="n">{{ n }}</option>
              </select>
            </label>
            <div class="flex items-center gap-0.5">
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-1.5 text-slate-600 enabled:hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="vehicleListPage <= 1"
                :aria-label="t('resources.pagination_prev')"
                @click="vehicleListPage = Math.max(1, vehicleListPage - 1)"
              >
                <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
              </button>
              <span class="min-w-[3.5rem] text-center text-[11px] tabular-nums text-slate-600 dark:text-slate-400">
                {{ vehicleListPage }} / {{ vehicleTotalPages }}
              </span>
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-1.5 text-slate-600 enabled:hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="vehicleListPage >= vehicleTotalPages"
                :aria-label="t('resources.pagination_next')"
                @click="vehicleListPage = Math.min(vehicleTotalPages, vehicleListPage + 1)"
              >
                <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
        <div
          v-if="activeTab === 'vehicles'"
          class="hidden overflow-visible rounded-xl border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900/50 lg:block"
        >
          <div
            v-if="vehicleTotalFiltered"
            class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 bg-slate-50/60 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/50"
          >
            <p class="text-xs text-slate-600 dark:text-slate-400">
              {{ t('resources.pagination_showing', { from: vehicleRangeFrom, to: vehicleRangeTo, total: vehicleTotalFiltered }) }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <button
                v-if="vehiclesViewMode === 'active' && canManageVehicles && bulkVehicleIds.length"
                type="button"
                class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                @click="openBulkTrashModal('vehicle')"
              >
                {{ t('resources.bulk_move_to_trash', { n: bulkVehicleIds.length }) }}
              </button>
              <button
                v-else-if="vehiclesViewMode === 'trash' && canManageVehicles && bulkVehicleIds.length"
                type="button"
                class="rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-900 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200 dark:hover:bg-rose-950/70"
                @click="openBulkForceModal('vehicle')"
              >
                {{ t('resources.bulk_permanent_delete_n', { n: bulkVehicleIds.length }) }}
              </button>
              <details ref="vehicleColumnPickerRef" class="relative">
                <summary
                  class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
                >
                  <ViewColumnsIcon class="h-4 w-4 text-slate-500" aria-hidden="true" />
                  {{ t('resources.table_columns') }}
                  <ChevronDownIcon class="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
                </summary>
                <div
                  class="absolute right-0 top-[calc(100%+6px)] z-[100] min-w-[220px] rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900"
                  @click.stop
                >
                  <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('resources.table_columns_hint') }}</p>
                  <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-slate-700 dark:text-slate-300">
                    <li v-for="opt in vehicleColumnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                      <input
                        :id="`vcol-${opt.id}`"
                        type="checkbox"
                        class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                        :checked="vehicleColumnVisible[opt.id]"
                        @change="setVehicleColumn(opt.id, $event.target.checked)"
                      />
                      <label :for="`vcol-${opt.id}`" class="cursor-pointer text-xs">{{ t(opt.labelKey) }}</label>
                    </li>
                  </ul>
                </div>
              </details>
              <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                {{ t('resources.pagination_per_page') }}
                <select
                  v-model.number="vehiclePerPage"
                  class="rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-8 text-xs font-medium dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option v-for="n in vehiclePerPageOptions" :key="n" :value="n">{{ n }}</option>
                </select>
              </label>
            </div>
          </div>
          <div class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
            <table class="w-max min-w-full border-separate border-spacing-0 text-left text-sm">
              <thead>
                <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                  <th
                    v-if="canManageVehicles && (vehiclesViewMode === 'active' || vehiclesViewMode === 'trash')"
                    class="w-10 border-b border-slate-200 px-2 py-3 first:rounded-tl-xl dark:border-slate-700"
                    :aria-label="t('resources.col_select')"
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                      :checked="vehiclePageAllSelected"
                      @change="toggleVehiclePageSelectAll"
                    />
                  </th>
                  <th
                    class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700"
                    :class="canManageVehicles && (vehiclesViewMode === 'active' || vehiclesViewMode === 'trash') ? '' : 'first:rounded-tl-xl'"
                  >
                    {{ t('resources.col_vehicle') }}
                  </th>
                  <th v-if="vehicleColOn('type_capacity')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">
                    {{ t('resources.col_type_capacity') }}
                  </th>
                  <th v-if="vehicleColOn('status')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_status') }}</th>
                  <th v-if="vehicleColOn('compliance')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_compliance') }}</th>
                  <th v-if="vehicleColOn('insurance_exp')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">
                    {{ t('resources.col_insurance_exp') }}
                  </th>
                  <th v-if="vehicleColOn('inspection_exp')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">
                    {{ t('resources.col_inspection_exp') }}
                  </th>
                  <th v-if="vehicleColOn('road_fee')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_road_fee_exp') }}</th>
                  <th v-if="vehicleColOn('owner')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_owner') }}</th>
                  <th v-if="vehicleColOn('year')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_year_mfg') }}</th>
                  <th v-if="vehicleColOn('purchase')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_purchase') }}</th>
                  <th v-if="vehicleColOn('driver')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_driver') }}</th>
                  <th v-if="vehicleColOn('maintenance')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">
                    {{ t('resources.col_maintenance') }}
                  </th>
                  <th v-if="vehicleColOn('notes')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.vehicle_form_notes') }}</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-3 py-3 text-right last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                  v-for="v in paginatedVehicles"
                  :key="v.id"
                  class="cursor-pointer transition hover:bg-teal-50/40 dark:hover:bg-slate-800/60"
                  :class="selectedVehicle?.id === v.id ? 'bg-teal-50/80 dark:bg-slate-800/80' : ''"
                  @click="selectVehicle(v)"
                >
                  <td
                    v-if="canManageVehicles && (vehiclesViewMode === 'active' || vehiclesViewMode === 'trash')"
                    class="w-10 px-2 py-3 align-middle"
                    @click.stop
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                      :checked="bulkVehicleIds.includes(v.id)"
                      @change="toggleBulkVehicle(v.id)"
                    />
                  </td>
                  <td class="px-3 py-3 align-middle">
                    <div class="flex items-center gap-2">
                      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400">
                        <component :is="vehicleIconComponent(v.iconKind)" class="h-5 w-5" aria-hidden="true" />
                      </div>
                      <div>
                        <div class="whitespace-nowrap font-semibold text-slate-900 dark:text-white">{{ v.code }}</div>
                        <div class="whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ v.model }}</div>
                      </div>
                    </div>
                  </td>
                  <td v-if="vehicleColOn('type_capacity')" class="whitespace-nowrap px-3 py-3 align-middle text-slate-700 dark:text-slate-300">
                    {{ v.typeLabel }}
                  </td>
                  <td v-if="vehicleColOn('status')" class="px-3 py-3 align-middle whitespace-nowrap">
                    <span :class="statusBadgeClass(v.status)">{{ labelVehicleStatus(v.status) }}</span>
                  </td>
                  <td v-if="vehicleColOn('compliance')" class="px-3 py-3 align-middle">
                    <div class="flex flex-nowrap gap-1">
                      <span :class="compliancePillClass(v.insurance)">{{ t('resources.tag_ins') }} {{ insuranceHint(v.insurance) }}</span>
                      <span :class="compliancePillClass(v.inspection)">{{ t('resources.tag_reg') }} {{ insuranceHint(v.inspection) }}</span>
                    </div>
                  </td>
                  <td v-if="vehicleColOn('insurance_exp')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    {{ fmtVehicleTableDate(v.insurance_expires_at) }}
                  </td>
                  <td v-if="vehicleColOn('inspection_exp')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    {{ fmtVehicleTableDate(v.inspection_expires_at) }}
                  </td>
                  <td v-if="vehicleColOn('road_fee')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    {{ fmtVehicleTableDate(v.road_fee_expires_at) }}
                  </td>
                  <td v-if="vehicleColOn('owner')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    {{ v.owner_name || '—' }}
                  </td>
                  <td v-if="vehicleColOn('year')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    {{ v.year_manufactured ?? '—' }}
                  </td>
                  <td v-if="vehicleColOn('purchase')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    {{ fmtVehicleTableDate(v.purchased_at) }}
                  </td>
                  <td v-if="vehicleColOn('driver')" class="whitespace-nowrap px-3 py-3 align-middle">
                    <span v-if="v.driverName" class="text-slate-800 dark:text-slate-200">{{ v.driverName }}</span>
                    <span v-else class="italic text-slate-500">{{ t('resources.unassigned') }}</span>
                  </td>
                  <td v-if="vehicleColOn('maintenance')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-600 dark:text-slate-400">
                    {{ vehicleMaintenanceLine(v) }}
                  </td>
                  <td v-if="vehicleColOn('notes')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-600 dark:text-slate-400">
                    {{ v.notes || '—' }}
                  </td>
                  <td class="px-3 py-3 align-middle text-right text-slate-400" @click.stop>
                    <div v-if="vehiclesViewMode === 'trash' && canManageVehicles" class="flex flex-wrap justify-end gap-1.5">
                      <button
                        type="button"
                        class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                        :disabled="vehicleRestoring"
                        @click="submitRestoreVehicleById(v.id)"
                      >
                        {{ t('resources.action_restore_vehicle') }}
                      </button>
                      <button
                        type="button"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                        :disabled="forcePermDeleting"
                        @click="openForceDeleteModal('vehicle', v.id, v.code)"
                      >
                        {{ t('resources.action_force_delete') }}
                      </button>
                    </div>
                    <ChevronRightIcon v-else class="ml-auto inline h-5 w-5" aria-hidden="true" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="vehicleTotalFiltered"
            class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/80 bg-slate-50/40 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40"
          >
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ t('resources.pagination_page_of', { page: vehicleListPage, total: vehicleTotalPages }) }}</span>
            <div class="flex items-center gap-1">
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-2 text-slate-600 enabled:hover:bg-white disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="vehicleListPage <= 1"
                :aria-label="t('resources.pagination_prev')"
                @click="vehicleListPage = Math.max(1, vehicleListPage - 1)"
              >
                <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-2 text-slate-600 enabled:hover:bg-white disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="vehicleListPage >= vehicleTotalPages"
                :aria-label="t('resources.pagination_next')"
                @click="vehicleListPage = Math.min(vehicleTotalPages, vehicleListPage + 1)"
              >
                <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <div
          v-else-if="activeTab === 'drivers'"
          class="overflow-visible rounded-xl border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <div
            v-if="driverTotalFiltered"
            class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 bg-slate-50/60 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/50"
          >
            <p class="text-xs text-slate-600 dark:text-slate-400">
              {{ t('resources.pagination_showing', { from: driverRangeFrom, to: driverRangeTo, total: driverTotalFiltered }) }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <button
                v-if="driversViewMode === 'active' && canManageDrivers && bulkDriverIds.length"
                type="button"
                class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                @click="openBulkTrashModal('driver')"
              >
                {{ t('resources.bulk_move_to_trash', { n: bulkDriverIds.length }) }}
              </button>
              <button
                v-else-if="driversViewMode === 'trash' && canManageDrivers && bulkDriverIds.length"
                type="button"
                class="rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-900 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200 dark:hover:bg-rose-950/70"
                @click="openBulkForceModal('driver')"
              >
                {{ t('resources.bulk_permanent_delete_n', { n: bulkDriverIds.length }) }}
              </button>
              <details ref="driverColumnPickerRef" class="relative">
                <summary
                  class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
                >
                  <ViewColumnsIcon class="h-4 w-4 text-slate-500" aria-hidden="true" />
                  {{ t('resources.table_columns') }}
                  <ChevronDownIcon class="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
                </summary>
                <div
                  class="absolute right-0 top-[calc(100%+6px)] z-[100] min-w-[220px] rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900"
                  @click.stop
                >
                  <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('resources.table_columns_hint') }}</p>
                  <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-slate-700 dark:text-slate-300">
                    <li v-for="opt in driverColumnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                      <input
                        :id="`dcol-${opt.id}`"
                        type="checkbox"
                        class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                        :checked="driverColumnVisible[opt.id]"
                        @change="setDriverColumn(opt.id, $event.target.checked)"
                      />
                      <label :for="`dcol-${opt.id}`" class="cursor-pointer text-xs">{{ t(opt.labelKey) }}</label>
                    </li>
                  </ul>
                </div>
              </details>
              <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                {{ t('resources.pagination_per_page') }}
                <select
                  v-model.number="driverPerPage"
                  class="rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-8 text-xs font-medium dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option v-for="n in driverPerPageOptions" :key="n" :value="n">{{ n }}</option>
                </select>
              </label>
            </div>
          </div>
          <div class="overflow-x-auto overscroll-x-contain rounded-b-xl [-webkit-overflow-scrolling:touch]">
            <table class="w-max min-w-full border-separate border-spacing-0 text-left text-sm">
              <thead>
                <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                  <th
                    v-if="canManageDrivers && (driversViewMode === 'active' || driversViewMode === 'trash')"
                    class="w-10 border-b border-slate-200 px-2 py-3 first:rounded-tl-xl dark:border-slate-700"
                    :aria-label="t('resources.col_select')"
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                      :checked="driverPageAllSelected"
                      @change="toggleDriverPageSelectAll"
                    />
                  </th>
                  <th
                    class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700"
                    :class="canManageDrivers && (driversViewMode === 'active' || driversViewMode === 'trash') ? '' : 'first:rounded-tl-xl'"
                  >
                    {{ t('resources.col_driver_name') }}
                  </th>
                  <th v-if="driverColOn('email')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_user_email') }}</th>
                  <th v-if="driverColOn('employee_code')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_employee_code') }}</th>
                  <th v-if="driverColOn('license_class')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('driver_detail.license_class') }}</th>
                  <th v-if="driverColOn('license_expires')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('driver_detail.license_expires') }}</th>
                  <th v-if="driverColOn('phone')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_phone') }}</th>
                  <th v-if="driverColOn('employment')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.filter_status') }}</th>
                  <th v-if="driverColOn('availability')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('driver_detail.availability') }}</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-3 py-3 text-right last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                  v-for="d in paginatedDrivers"
                  :key="d.id"
                  :class="
                    driversViewMode === 'active'
                      ? 'cursor-pointer transition hover:bg-teal-50/50 dark:hover:bg-slate-800/60'
                      : 'transition hover:bg-slate-50 dark:hover:bg-slate-800/40'
                  "
                  @click="goDriverDetail(d)"
                >
                  <td
                    v-if="canManageDrivers && (driversViewMode === 'active' || driversViewMode === 'trash')"
                    class="w-10 px-2 py-3 align-middle"
                    @click.stop
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                      :checked="bulkDriverIds.includes(d.id)"
                      @change="toggleBulkDriver(d.id)"
                    />
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900 dark:text-white">
                    <div class="flex items-center gap-2">
                      <img
                        v-if="d.avatarUrl"
                        :src="d.avatarUrl"
                        :alt="d.name"
                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-600"
                      />
                      <div
                        v-else
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-[11px] font-semibold text-teal-800 dark:bg-teal-950/60 dark:text-teal-300"
                        aria-hidden="true"
                      >
                        {{ driverRowInitials(d.name) }}
                      </div>
                      <span>{{ d.name }}</span>
                    </div>
                  </td>
                  <td v-if="driverColOn('email')" class="whitespace-nowrap px-3 py-3 text-slate-600 dark:text-slate-400">{{ d.email || '—' }}</td>
                  <td v-if="driverColOn('employee_code')" class="whitespace-nowrap px-3 py-3 font-mono text-xs text-slate-700 dark:text-slate-300">{{ d.employeeCode || '—' }}</td>
                  <td v-if="driverColOn('license_class')" class="whitespace-nowrap px-3 py-3 text-slate-700 dark:text-slate-300">{{ d.license_class || '—' }}</td>
                  <td v-if="driverColOn('license_expires')" class="whitespace-nowrap px-3 py-3 align-middle text-xs text-slate-700 dark:text-slate-300">
                    <span v-if="d.license_expires_at" :class="compliancePillClass(d.licenseExpiry)">{{ fmtVehicleTableDate(d.license_expires_at) }}</span>
                    <span v-else class="italic text-slate-500">{{ t('resources.unassigned') }}</span>
                  </td>
                  <td v-if="driverColOn('phone')" class="whitespace-nowrap px-3 py-3 text-slate-600 dark:text-slate-400">{{ d.phone || '—' }}</td>
                  <td v-if="driverColOn('employment')" class="whitespace-nowrap px-3 py-3">
                    <span :class="statusBadgeClass(d.uiStatus)">{{ labelVehicleStatus(d.uiStatus) }}</span>
                  </td>
                  <td v-if="driverColOn('availability')" class="whitespace-nowrap px-3 py-3 text-xs text-slate-700 dark:text-slate-300">
                    {{ labelDriverAvailability(d.availability_status) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-right text-slate-400" @click.stop>
                    <div v-if="driversViewMode === 'trash' && canManageDrivers" class="flex flex-wrap justify-end gap-1.5">
                      <button
                        type="button"
                        class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                        :disabled="driverRestoring"
                        @click="submitRestoreDriverById(d.id)"
                      >
                        {{ t('resources.action_restore_driver') }}
                      </button>
                      <button
                        type="button"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                        :disabled="forcePermDeleting"
                        @click="openForceDeleteModal('driver', d.id, d.name)"
                      >
                        {{ t('resources.action_force_delete') }}
                      </button>
                    </div>
                    <button
                      v-else-if="driversViewMode === 'active' && canManageDrivers"
                      type="button"
                      class="inline-flex rounded-lg border border-slate-200 p-2 text-slate-500 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-600 dark:hover:border-rose-900 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                      :title="t('resources.driver_delete_action')"
                      @click="openDriverDeleteModal(d)"
                    >
                      <TrashIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="driverTotalFiltered"
            class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/80 bg-slate-50/40 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40"
          >
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ t('resources.pagination_page_of', { page: driverListPage, total: driverTotalPages }) }}</span>
            <div class="flex items-center gap-1">
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-2 text-slate-600 enabled:hover:bg-white disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="driverListPage <= 1"
                :aria-label="t('resources.pagination_prev')"
                @click="driverListPage = Math.max(1, driverListPage - 1)"
              >
                <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-2 text-slate-600 enabled:hover:bg-white disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="driverListPage >= driverTotalPages"
                :aria-label="t('resources.pagination_next')"
                @click="driverListPage = Math.min(driverTotalPages, driverListPage + 1)"
              >
                <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <div
          v-else-if="activeTab === 'suppliers'"
          class="overflow-visible rounded-xl border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <div
            v-if="supplierTotalFiltered"
            class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 bg-slate-50/60 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/50"
          >
            <p class="text-xs text-slate-600 dark:text-slate-400">
              {{ t('resources.pagination_showing', { from: supplierRangeFrom, to: supplierRangeTo, total: supplierTotalFiltered }) }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <button
                v-if="suppliersViewMode === 'active' && canManageProviders && bulkSupplierIds.length"
                type="button"
                class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                @click="openBulkTrashModal('supplier')"
              >
                {{ t('resources.bulk_move_to_trash', { n: bulkSupplierIds.length }) }}
              </button>
              <button
                v-else-if="suppliersViewMode === 'trash' && canManageProviders && bulkSupplierIds.length"
                type="button"
                class="rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-900 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200 dark:hover:bg-rose-950/70"
                @click="openBulkForceModal('supplier')"
              >
                {{ t('resources.bulk_permanent_delete_n', { n: bulkSupplierIds.length }) }}
              </button>
              <details ref="supplierColumnPickerRef" class="relative">
                <summary
                  class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
                >
                  <ViewColumnsIcon class="h-4 w-4 text-slate-500" aria-hidden="true" />
                  {{ t('resources.table_columns') }}
                  <ChevronDownIcon class="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
                </summary>
                <div
                  class="absolute right-0 top-[calc(100%+6px)] z-[100] min-w-[220px] rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900"
                  @click.stop
                >
                  <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('resources.table_columns_hint') }}</p>
                  <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-slate-700 dark:text-slate-300">
                    <li v-for="opt in supplierColumnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                      <input
                        :id="`scol-${opt.id}`"
                        type="checkbox"
                        class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                        :checked="supplierColumnVisible[opt.id]"
                        @change="setSupplierColumn(opt.id, $event.target.checked)"
                      />
                      <label :for="`scol-${opt.id}`" class="cursor-pointer text-xs">{{ t(opt.labelKey) }}</label>
                    </li>
                  </ul>
                </div>
              </details>
              <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                {{ t('resources.pagination_per_page') }}
                <select
                  v-model.number="supplierPerPage"
                  class="rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-8 text-xs font-medium dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                  <option v-for="n in supplierPerPageOptions" :key="n" :value="n">{{ n }}</option>
                </select>
              </label>
            </div>
          </div>
          <div class="overflow-x-auto overscroll-x-contain rounded-b-xl [-webkit-overflow-scrolling:touch]">
            <table class="w-max min-w-full border-separate border-spacing-0 text-left text-sm">
              <thead>
                <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                  <th
                    v-if="canManageProviders && (suppliersViewMode === 'active' || suppliersViewMode === 'trash')"
                    class="w-10 border-b border-slate-200 px-2 py-3 first:rounded-tl-xl dark:border-slate-700"
                    :aria-label="t('resources.col_select')"
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                      :checked="supplierPageAllSelected"
                      @change="toggleSupplierPageSelectAll"
                    />
                  </th>
                  <th
                    class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700"
                    :class="canManageProviders && (suppliersViewMode === 'active' || suppliersViewMode === 'trash') ? '' : 'first:rounded-tl-xl'"
                  >
                    {{ t('resources.col_supplier') }}
                  </th>
                  <th v-if="supplierColOn('services')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_solutions_services') }}</th>
                  <th v-if="supplierColOn('contract')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_contract') }}</th>
                  <th v-if="supplierColOn('status')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_status') }}</th>
                  <th v-if="supplierColOn('contact')" class="whitespace-nowrap border-b border-slate-200 px-3 py-3 dark:border-slate-700">{{ t('resources.col_contact') }}</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-3 py-3 text-right last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                  v-for="s in paginatedSuppliers"
                  :key="s.id"
                  class="transition hover:bg-teal-50/40 dark:hover:bg-slate-800/60"
                  :class="[
                    'cursor-pointer',
                    selectedSupplier?.id === s.id ? 'bg-teal-50/80 dark:bg-slate-800/80' : '',
                  ]"
                  @click="selectSupplier(s)"
                >
                  <td
                    v-if="canManageProviders && (suppliersViewMode === 'active' || suppliersViewMode === 'trash')"
                    class="w-10 px-2 py-3 align-middle"
                    @click.stop
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                      :checked="bulkSupplierIds.includes(s.id)"
                      @change="toggleBulkSupplier(s.id)"
                    />
                  </td>
                  <td class="px-3 py-3 align-middle">
                    <div class="flex items-center gap-2">
                      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                        <BuildingOffice2Icon class="h-5 w-5" aria-hidden="true" />
                      </div>
                      <div>
                        <div class="whitespace-nowrap font-semibold text-slate-900 dark:text-white">{{ s.name }}</div>
                        <div class="whitespace-nowrap text-xs text-slate-500">{{ s.typeLabel }}</div>
                      </div>
                    </div>
                  </td>
                  <td v-if="supplierColOn('services')" class="max-w-[14rem] px-3 py-3 align-middle text-xs text-slate-600 dark:text-slate-400">
                    <span class="line-clamp-2">{{ s.serviceSummary }}</span>
                  </td>
                  <td v-if="supplierColOn('contract')" class="whitespace-nowrap px-3 py-3 align-middle">
                    <span :class="compliancePillClass(s.contract)">{{ t('resources.tag_contract') }} {{ insuranceHint(s.contract) }}</span>
                  </td>
                  <td v-if="supplierColOn('status')" class="whitespace-nowrap px-3 py-3 align-middle">
                    <span :class="statusBadgeClass(s.uiStatus)">{{ labelProviderStatus(s.uiStatus) }}</span>
                  </td>
                  <td v-if="supplierColOn('contact')" class="max-w-[12rem] px-3 py-3 align-middle text-xs text-slate-600 dark:text-slate-400">
                    <span class="line-clamp-2">{{ s.contact || '—' }}</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 align-middle text-right text-slate-400" @click.stop>
                    <div v-if="suppliersViewMode === 'trash' && canManageProviders" class="flex flex-wrap justify-end gap-1.5">
                      <button
                        type="button"
                        class="rounded-lg bg-teal-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                        :disabled="supplierRestoring"
                        @click="submitRestoreSupplierById(s.id)"
                      >
                        {{ t('resources.action_restore_supplier') }}
                      </button>
                      <button
                        type="button"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-800 hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                        :disabled="forcePermDeleting"
                        @click="openForceDeleteModal('supplier', s.id, s.name)"
                      >
                        {{ t('resources.action_force_delete') }}
                      </button>
                    </div>
                    <template v-else-if="suppliersViewMode === 'active' && canManageProviders">
                      <button
                        type="button"
                        class="inline-flex rounded-lg border border-slate-200 p-2 text-slate-500 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-600 dark:hover:border-rose-900 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                        :title="t('resources.provider_delete_action')"
                        @click="openProviderDeleteModal(s)"
                      >
                        <TrashIcon class="h-4 w-4" aria-hidden="true" />
                      </button>
                    </template>
                    <ChevronRightIcon v-else class="ml-auto inline h-5 w-5" aria-hidden="true" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="supplierTotalFiltered"
            class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/80 bg-slate-50/40 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40"
          >
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ t('resources.pagination_page_of', { page: supplierListPage, total: supplierTotalPages }) }}</span>
            <div class="flex items-center gap-1">
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-2 text-slate-600 enabled:hover:bg-white disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="supplierListPage <= 1"
                :aria-label="t('resources.pagination_prev')"
                @click="supplierListPage = Math.max(1, supplierListPage - 1)"
              >
                <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-2 text-slate-600 enabled:hover:bg-white disabled:opacity-40 dark:border-slate-600 dark:text-slate-400 dark:enabled:hover:bg-slate-800"
                :disabled="supplierListPage >= supplierTotalPages"
                :aria-label="t('resources.pagination_next')"
                @click="supplierListPage = Math.min(supplierTotalPages, supplierListPage + 1)"
              >
                <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <p v-if="activeTab === 'vehicles' && !filteredVehicles.length" class="py-8 text-center text-sm text-slate-500">
          {{ vehiclesViewMode === 'trash' ? t('resources.trash_empty') : t('resources.empty') }}
        </p>
        <p v-if="activeTab === 'drivers' && !filteredDrivers.length" class="py-8 text-center text-sm text-slate-500">
          {{ driversViewMode === 'trash' ? t('resources.trash_empty') : t('resources.empty') }}
        </p>
        <p v-if="activeTab === 'suppliers' && !filteredSuppliers.length" class="py-8 text-center text-sm text-slate-500">
          {{ suppliersViewMode === 'trash' ? t('resources.trash_empty') : t('resources.empty') }}
        </p>
      </div>

      <!-- Detail panel (vehicles) -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-4 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-4 opacity-0"
      >
        <aside
          v-if="activeTab === 'vehicles' && selectedVehicle"
          class="fixed inset-0 z-50 flex justify-end bg-black/40 p-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur-sm sm:p-3 xl:static xl:z-auto xl:inset-auto xl:flex xl:w-[min(100%,440px)] xl:max-w-none xl:shrink-0 xl:bg-transparent xl:p-0 xl:pt-0 xl:pb-0 xl:backdrop-blur-0"
          @click.self="closePanel"
        >
          <div
            class="flex h-full max-h-[100dvh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40 xl:max-h-none xl:max-w-none xl:rounded-none xl:border-l xl:border-y-0 xl:border-r-0 xl:shadow-none xl:ring-0"
            @click.stop
          >
            <div class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-3 py-3 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 sm:px-4">
              <div class="flex items-start justify-between gap-2 sm:gap-3">
                <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400">
                    <component :is="vehicleIconComponent(selectedVehicle.iconKind)" class="h-6 w-6" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <div class="text-base font-semibold leading-tight tracking-tight text-slate-900 dark:text-white">{{ selectedVehicle.code }}</div>
                    <div class="line-clamp-2 text-xs leading-snug text-slate-600 dark:text-slate-400 sm:text-sm">{{ selectedVehicle.model }}</div>
                  </div>
                </div>
                <button
                  type="button"
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
                  :aria-label="t('resources.close_panel')"
                  @click="closePanel"
                >
                  <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </div>
              <div class="mt-2 flex flex-wrap gap-1.5">
                <span
                  v-if="vehiclesViewMode === 'trash'"
                  class="rounded-full border border-slate-300 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                >
                  {{ t('resources.vehicles_view_trash') }}
                </span>
                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', statusOutlineClass(selectedVehicle.status)]">
                  {{ labelVehicleStatus(selectedVehicle.status) }}
                </span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700 dark:bg-slate-700 dark:text-slate-300 sm:px-2.5 sm:text-xs">{{ selectedVehicle.capacityLabel }}</span>
              </div>
              <div v-if="vehiclesViewMode === 'active'" class="mt-3 grid grid-cols-3 gap-1.5 sm:gap-2">
                <button
                  v-if="canManageVehicles"
                  type="button"
                  class="rounded-lg bg-teal-600 py-2 text-xs font-medium text-white hover:bg-teal-500 sm:text-sm"
                  @click="openVehicleForm(selectedVehicle)"
                >
                  {{ t('resources.action_edit') }}
                </button>
                <button
                  v-else
                  type="button"
                  class="rounded-lg bg-teal-600 py-2 text-xs font-medium text-white opacity-50 sm:text-sm"
                  disabled
                >
                  {{ t('resources.action_edit') }}
                </button>
                <button
                  v-if="canManageVehicles"
                  type="button"
                  class="rounded-lg border border-amber-200 bg-amber-50 py-2 text-xs font-medium text-amber-900 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-amber-800/80 dark:bg-amber-950/40 dark:text-amber-200 dark:hover:bg-amber-950/60 sm:text-sm"
                  :disabled="vehicleDeactivating || selectedVehicle.apiStatus === 'broken'"
                  @click="confirmDeactivateVehicle"
                >
                  {{ vehicleDeactivating ? t('resources.loading') : t('resources.action_deactivate') }}
                </button>
                <button
                  v-if="canManageVehicles"
                  type="button"
                  class="rounded-lg border border-rose-200 bg-white py-2 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-900/60 dark:bg-slate-800 dark:text-rose-400 dark:hover:bg-rose-950/30 sm:text-sm"
                  :disabled="vehicleDeleting"
                  @click="openDeleteVehicleModal"
                >
                  {{ t('resources.action_delete_vehicle') }}
                </button>
              </div>
              <div v-else-if="canManageVehicles" class="mt-3">
                <button
                  type="button"
                  class="w-full rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                  :disabled="vehicleRestoring"
                  @click="submitRestoreVehicle"
                >
                  {{ vehicleRestoring ? t('resources.loading') : t('resources.action_restore_vehicle') }}
                </button>
              </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-3 py-3 sm:px-4">
              <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('resources.section_default_driver') }}</div>
              <div class="mt-1.5 rounded-lg border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-700 dark:bg-slate-800/40">
                <div v-if="selectedVehicle.defaultDriver" class="space-y-1 text-sm">
                  <div class="font-medium text-slate-900 dark:text-white">{{ selectedVehicle.defaultDriver.full_name }}</div>
                  <div v-if="selectedVehicle.defaultDriver.user" class="space-y-0.5 text-xs text-slate-600 dark:text-slate-400">
                    <div>{{ selectedVehicle.defaultDriver.user.email }}</div>
                    <div v-if="selectedVehicle.defaultDriver.user.employee_code" class="font-mono">
                      {{ t('resources.col_employee_code') }}: {{ selectedVehicle.defaultDriver.user.employee_code }}
                    </div>
                    <div v-if="selectedVehicle.defaultDriver.user.phone">{{ selectedVehicle.defaultDriver.user.phone }}</div>
                  </div>
                  <div v-else class="text-xs text-slate-500">{{ t('resources.unassigned') }}</div>
                </div>
                <div v-else class="text-sm text-slate-500">{{ t('resources.unassigned') }}</div>
                <button
                  v-if="vehiclesViewMode === 'active' && canManageVehicles"
                  type="button"
                  class="mt-2 w-full rounded-lg border border-teal-200 bg-white py-1.5 text-xs font-medium text-teal-800 hover:bg-teal-50 dark:border-teal-800 dark:bg-slate-800 dark:text-teal-300 dark:hover:bg-slate-700 sm:py-2 sm:text-sm"
                  @click="openAssignModal(selectedVehicle.id)"
                >
                  {{ selectedVehicle.defaultDriver ? t('resources.assign_driver_change') : t('resources.assign_driver') }}
                </button>
              </div>

              <div v-if="vehicleSheetDetailVisible(selectedVehicle)" class="mt-4">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                  {{ t('resources.section_vehicle_sheet') }}
                </div>
                <dl class="mt-1.5 divide-y divide-slate-200/90 overflow-hidden rounded-lg border border-slate-200 bg-slate-50/80 text-sm dark:divide-slate-600/80 dark:border-slate-700 dark:bg-slate-800/40 [&>div]:px-2.5 [&>div]:py-2 sm:[&>div]:px-3">
                  <template v-if="selectedVehicle.owner_name">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_owner') }}</dt>
                      <dd class="text-slate-900 dark:text-slate-100">{{ selectedVehicle.owner_name }}</dd>
                    </div>
                  </template>
                  <template v-if="selectedVehicle.frame_engine_number">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_frame_engine') }}</dt>
                      <dd class="whitespace-pre-wrap text-slate-800 dark:text-slate-200">{{ selectedVehicle.frame_engine_number }}</dd>
                    </div>
                  </template>
                  <div v-if="selectedVehicle.year_manufactured || selectedVehicle.purchased_at || selectedVehicle.usage_expires_year" class="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                    <span v-if="selectedVehicle.year_manufactured" class="text-slate-700 dark:text-slate-300">
                      {{ t('resources.vehicle_form_year_mfg') }}: {{ selectedVehicle.year_manufactured }}
                    </span>
                    <span v-if="selectedVehicle.purchased_at" class="text-slate-700 dark:text-slate-300">
                      {{ t('resources.vehicle_form_purchased') }}: {{ selectedVehicle.purchased_at }}
                    </span>
                    <span v-if="selectedVehicle.usage_expires_year" class="text-slate-700 dark:text-slate-300">
                      {{ t('resources.vehicle_form_usage_until_year') }}: {{ selectedVehicle.usage_expires_year }}
                    </span>
                  </div>
                  <template v-if="selectedVehicle.insurance_provider">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_insurance_provider') }}</dt>
                      <dd class="text-slate-900 dark:text-slate-100">{{ selectedVehicle.insurance_provider }}</dd>
                    </div>
                  </template>
                  <template v-if="selectedVehicle.insurance_policy_note">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_insurance_policy_note') }}</dt>
                      <dd class="whitespace-pre-wrap text-xs text-slate-700 dark:text-slate-300">{{ selectedVehicle.insurance_policy_note }}</dd>
                    </div>
                  </template>
                  <template v-if="selectedVehicle.road_fee_expires_at">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_road_fee') }}</dt>
                      <dd class="flex flex-wrap items-center gap-2">
                        <span class="text-slate-900 dark:text-slate-100">{{ selectedVehicle.road_fee_expires_at }}</span>
                        <span :class="compliancePillClass(selectedVehicle.road_fee)">{{ insuranceHint(selectedVehicle.road_fee) }}</span>
                      </dd>
                    </div>
                  </template>
                  <template v-if="selectedVehicle.registration_cycle_note">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_reg_cycle') }}</dt>
                      <dd class="text-slate-800 dark:text-slate-200">{{ selectedVehicle.registration_cycle_note }}</dd>
                    </div>
                  </template>
                  <div v-if="selectedVehicle.last_maintenance_at || selectedVehicle.maintenance_schedule_note" class="space-y-1">
                    <div v-if="selectedVehicle.last_maintenance_at" class="text-xs text-slate-700 dark:text-slate-300">
                      {{ t('resources.vehicle_form_last_maint') }}: {{ selectedVehicle.last_maintenance_at }}
                    </div>
                    <div v-if="selectedVehicle.maintenance_schedule_note" class="text-xs text-slate-600 dark:text-slate-400">
                      {{ selectedVehicle.maintenance_schedule_note }}
                    </div>
                  </div>
                  <div v-if="selectedVehicle.caretaker_name || selectedVehicle.caretaker_phone" class="text-sm">
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_caretaker') }}: </span>
                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedVehicle.caretaker_name || '—' }}</span>
                    <span v-if="selectedVehicle.caretaker_phone" class="text-slate-600 dark:text-slate-400"> · {{ selectedVehicle.caretaker_phone }}</span>
                  </div>
                  <template v-if="selectedVehicle.notes">
                    <div>
                      <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_form_notes') }}</dt>
                      <dd class="whitespace-pre-wrap text-xs text-slate-700 dark:text-slate-300">{{ selectedVehicle.notes }}</dd>
                    </div>
                  </template>
                </dl>
              </div>

              <div class="mt-4 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('resources.section_compliance') }}</div>
              <div class="mt-1.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div
                  class="rounded-lg border p-2.5 sm:p-3"
                  :class="
                    selectedVehicle.insurance.state === 'exp'
                      ? 'border-rose-300 bg-rose-50 dark:border-rose-500/70 dark:bg-rose-950/20'
                      : selectedVehicle.insurance.state === 'soon'
                        ? 'border-amber-300 bg-amber-50 dark:border-amber-500/70 dark:bg-amber-950/20'
                        : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                  "
                >
                  <div class="text-xs font-semibold text-slate-900 dark:text-white sm:text-sm">{{ t('resources.doc_insurance') }}</div>
                  <div class="mt-0.5 text-[11px] leading-snug text-slate-600 dark:text-slate-400 sm:text-xs">{{ complianceDocLine(selectedVehicle.insurance) }}</div>
                  <button type="button" class="mt-1.5 text-[11px] font-medium text-teal-700 hover:text-teal-800 disabled:opacity-50 dark:text-teal-400 dark:hover:text-teal-300" disabled>
                    {{ t('resources.update_document') }}
                  </button>
                </div>
                <div
                  class="rounded-lg border p-2.5 sm:p-3"
                  :class="
                    selectedVehicle.inspection.state === 'exp'
                      ? 'border-rose-300 bg-rose-50 dark:border-rose-500/70 dark:bg-rose-950/20'
                      : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                  "
                >
                  <div class="text-xs font-semibold text-slate-900 dark:text-white sm:text-sm">{{ t('resources.doc_inspection') }}</div>
                  <div class="mt-0.5 text-[11px] leading-snug text-slate-600 dark:text-slate-400 sm:text-xs">{{ complianceDocLine(selectedVehicle.inspection) }}</div>
                </div>
              </div>

              <div v-if="canViewVehicleComplianceDocs && vehiclesViewMode === 'active'" class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                      {{ t('resources.vehicle_compliance_attachments') }}
                    </div>
                    <p class="mt-0.5 text-[11px] leading-snug text-slate-600 dark:text-slate-400 sm:text-xs">{{ t('resources.vehicle_compliance_attachments_hint') }}</p>
                  </div>
                  <button
                    v-if="canManageVehicles"
                    type="button"
                    class="shrink-0 rounded-lg bg-teal-600 px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-teal-500 sm:px-3 sm:py-2 sm:text-xs"
                    @click="openVehicleDocModal(null)"
                  >
                    {{ t('resources.vehicle_compliance_quick_add') }}
                  </button>
                </div>
                <ul class="mt-2 space-y-1.5">
                  <li
                    v-for="doc in vehicleComplianceDocs"
                    :key="doc.id"
                    class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-xs dark:border-slate-700 dark:bg-slate-800/40"
                  >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                      <div class="min-w-0">
                        <div class="font-medium text-slate-900 dark:text-white">{{ vehicleDocTypeLabel(doc.doc_type) }}</div>
                        <div v-if="doc.title" class="mt-0.5 text-slate-600 dark:text-slate-400">{{ doc.title }}</div>
                        <div class="mt-1 flex flex-wrap gap-1">
                          <span :class="compliancePillClass(doc.expiry)">{{ vehicleDocExpiryLabel(doc.expiry) }}</span>
                          <span v-if="doc.expires_at" class="text-slate-500">{{ doc.expires_at }}</span>
                        </div>
                        <div v-if="doc.notes" class="mt-1 text-slate-600 dark:text-slate-400">{{ doc.notes }}</div>
                        <div v-if="doc.attachments?.length" class="mt-2 flex flex-col gap-2">
                          <div
                            v-for="a in doc.attachments"
                            :key="a.id"
                            class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200/90 bg-white/70 px-2 py-1.5 dark:border-slate-600/80 dark:bg-slate-900/40"
                          >
                            <a
                              :href="a.url"
                              target="_blank"
                              rel="noopener noreferrer"
                              class="min-w-0 flex-1 truncate text-teal-700 underline dark:text-teal-400"
                            >
                              {{ a.original_name || 'file' }}
                            </a>
                            <button
                              v-if="isPdfAttachment(a)"
                              type="button"
                              class="shrink-0 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                              :title="t('resources.attachment_pdf_preview_title')"
                              @click.stop="openPdfPreview(a)"
                            >
                              {{ t('resources.attachment_pdf_preview') }}
                            </button>
                          </div>
                        </div>
                      </div>
                      <div v-if="canManageVehicles" class="flex shrink-0 gap-2">
                        <button type="button" class="text-teal-700 dark:text-teal-400" @click="openVehicleDocModal(doc)">{{ t('resources.action_edit') }}</button>
                        <button type="button" class="text-rose-600" @click="confirmDeleteVehicleDoc(doc)">{{ t('resources.delete') }}</button>
                      </div>
                    </div>
                  </li>
                </ul>
                <p v-if="!vehicleComplianceDocs.length" class="mt-1.5 text-[11px] text-slate-500 sm:text-xs">{{ t('resources.empty') }}</p>
              </div>

            </div>

            <div class="border-t border-slate-200 px-3 py-2.5 dark:border-slate-700 sm:px-4">
              <button
                type="button"
                class="w-full rounded-lg border border-slate-200 py-1.5 text-xs text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 sm:py-2 sm:text-sm"
                disabled
              >
                {{ t('resources.view_history') }}
              </button>
            </div>
          </div>
        </aside>
      </Transition>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-4 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-4 opacity-0"
      >
        <aside
          v-if="activeTab === 'suppliers' && selectedSupplier"
          class="fixed inset-0 z-50 flex justify-end bg-black/40 p-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur-sm sm:p-3 xl:static xl:z-auto xl:inset-auto xl:flex xl:w-[min(100%,440px)] xl:shrink-0 xl:bg-transparent xl:p-0 xl:backdrop-blur-0"
          @click.self="closePanel"
        >
          <div
            class="flex h-full max-h-[100dvh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40 xl:max-h-none xl:max-w-none xl:rounded-none xl:border-l xl:border-y-0 xl:border-r-0 xl:shadow-none xl:ring-0"
            @click.stop
          >
            <div class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 p-4 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
              <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <BuildingOffice2Icon class="h-7 w-7" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <div class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ selectedSupplier.name }}</div>
                    <div class="text-sm text-slate-600 dark:text-slate-400">{{ selectedSupplier.typeLabel }}</div>
                  </div>
                </div>
                <button
                  type="button"
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
                  :aria-label="t('resources.close_panel')"
                  @click="closePanel"
                >
                  <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span
                  v-if="suppliersViewMode === 'trash' || selectedSupplier.deleted_at"
                  class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                >
                  {{ t('resources.badge_in_trash') }}
                </span>
                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', statusOutlineClass(selectedSupplier.uiStatus)]">
                  {{ labelProviderStatus(selectedSupplier.uiStatus) }}
                </span>
                <span :class="compliancePillClass(selectedSupplier.contract)">{{ t('resources.tag_contract') }} {{ insuranceHint(selectedSupplier.contract) }}</span>
              </div>
              <div class="mt-4">
                <div v-if="canManageProviders && suppliersViewMode === 'trash'" class="flex flex-col gap-2 sm:flex-row">
                  <button
                    type="button"
                    class="flex-1 rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                    :disabled="supplierRestoring"
                    @click="submitRestoreSupplierById(selectedSupplier.id)"
                  >
                    {{ t('resources.action_restore_supplier') }}
                  </button>
                  <button
                    type="button"
                    class="flex-1 rounded-lg border border-rose-200 bg-rose-50 py-2.5 text-sm font-medium text-rose-800 hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/60"
                    :disabled="forcePermDeleting"
                    @click="openForceDeleteModal('supplier', selectedSupplier.id, selectedSupplier.name)"
                  >
                    {{ t('resources.action_force_delete') }}
                  </button>
                </div>
                <template v-else-if="suppliersViewMode === 'active'">
                  <button
                    v-if="canManageProviders"
                    type="button"
                    class="w-full rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-500"
                    @click="openProviderForm(selectedSupplier)"
                  >
                    {{ t('resources.action_edit') }}
                  </button>
                  <button
                    v-else
                    type="button"
                    class="w-full rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white opacity-50"
                    disabled
                  >
                    {{ t('resources.action_edit') }}
                  </button>
                  <div v-if="canManageProviders" class="mt-2">
                    <button
                      type="button"
                      class="w-full rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-rose-950/30 dark:hover:text-rose-300"
                      @click="openProviderDeleteModal(selectedSupplier)"
                    >
                      {{ t('resources.provider_delete_action') }}
                    </button>
                  </div>
                </template>
              </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_supplier_contact') }}</div>
              <div class="mt-2 space-y-1 rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-sm dark:border-slate-700 dark:bg-slate-800/40">
                <div v-if="selectedSupplier.contact_name" class="font-medium text-slate-900 dark:text-white">{{ selectedSupplier.contact_name }}</div>
                <div v-if="selectedSupplier.contact_phone" class="text-slate-700 dark:text-slate-300">{{ selectedSupplier.contact_phone }}</div>
                <div v-if="selectedSupplier.contact_email" class="text-xs text-slate-600 dark:text-slate-400">{{ selectedSupplier.contact_email }}</div>
                <p v-if="selectedSupplier.notes" class="mt-2 border-t border-slate-200 pt-2 text-xs text-slate-600 dark:border-slate-600 dark:text-slate-400">{{ selectedSupplier.notes }}</p>
                <p v-if="!selectedSupplier.contact_name && !selectedSupplier.contact_phone && !selectedSupplier.contact_email && !selectedSupplier.notes" class="text-xs text-slate-500">
                  {{ t('resources.empty') }}
                </p>
              </div>

              <div class="mt-6 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_supplier_contract') }}</div>
              <div
                class="mt-2 rounded-lg border p-3"
                :class="
                  selectedSupplier.contract.state === 'exp'
                    ? 'border-rose-300 bg-rose-50 dark:border-rose-500/70 dark:bg-rose-950/20'
                    : selectedSupplier.contract.state === 'soon'
                      ? 'border-amber-300 bg-amber-50 dark:border-amber-500/70 dark:bg-amber-950/20'
                      : selectedSupplier.contract.state === 'none'
                        ? 'border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-800/40'
                        : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                "
              >
                <div v-if="selectedSupplier.contract_number" class="text-sm font-medium text-slate-900 dark:text-white">
                  {{ t('resources.provider_form_contract_number') }}: {{ selectedSupplier.contract_number }}
                </div>
                <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ complianceDocLine(selectedSupplier.contract) }}</div>
                <div v-if="selectedSupplier.contract_signed_at" class="mt-1 text-xs text-slate-500">
                  {{ t('resources.provider_form_contract_signed') }}: {{ selectedSupplier.contract_signed_at }}
                </div>
              </div>

              <div class="mt-6 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_supplier_services') }}</div>
              <p v-if="!selectedSupplier.servicesList?.length" class="mt-2 text-xs text-slate-500">{{ t('resources.empty') }}</p>
              <ul v-else class="mt-2 space-y-2">
                <li
                  v-for="(svc, i) in selectedSupplier.servicesList"
                  :key="i"
                  class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/60"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold"
                      :class="
                        svc.kind === 'solution'
                          ? 'bg-violet-100 text-violet-900 dark:bg-violet-950/60 dark:text-violet-300'
                          : 'bg-sky-100 text-sky-900 dark:bg-sky-950/60 dark:text-sky-300'
                      "
                    >
                      {{ svc.kind === 'solution' ? t('resources.provider_kind_solution') : t('resources.provider_kind_service') }}
                    </span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ svc.name }}</span>
                  </div>
                  <p v-if="svc.note" class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ svc.note }}</p>
                </li>
              </ul>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <!-- Modal: thêm / sửa xe -->
    <Teleport to="body">
      <div
        v-if="vehicleModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="vehicle-modal-title"
        @click.self="vehicleModalOpen = false"
      >
        <div
          class="flex max-h-[min(92vh,960px)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div
            class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200/90 bg-gradient-to-r from-slate-50 via-white to-teal-50/40 px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-teal-950/20"
          >
            <div class="min-w-0">
              <h2 id="vehicle-modal-title" class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ vehicleForm.id ? t('resources.vehicle_form_title_edit') : t('resources.vehicle_form_title_add') }}
              </h2>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_modal_subtitle') }}</p>
            </div>
            <button
              type="button"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
              :aria-label="t('resources.close_panel')"
              @click="vehicleModalOpen = false"
            >
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitVehicleForm">
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-5 py-5 sm:px-6 sm:py-6">
              <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                  <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('resources.vehicle_modal_section_identity') }}
                  </h3>
                  <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_plate') }}</span>
                      <input
                        v-model="vehicleForm.license_plate"
                        type="text"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_plate')"
                      />
                    </label>
                    <label class="block sm:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_type') }}</span>
                      <input
                        v-model="vehicleForm.type"
                        type="text"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_type')"
                      />
                    </label>
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_seats') }}</span>
                      <input
                        v-model="vehicleForm.seat_count"
                        type="number"
                        min="0"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_seats')"
                      />
                    </label>
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_payload') }}</span>
                      <input
                        v-model="vehicleForm.payload_kg"
                        type="number"
                        min="0"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_payload')"
                      />
                    </label>
                    <label class="block sm:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.filter_status') }} (API)</span>
                      <select
                        v-model="vehicleForm.status"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      >
                        <option value="ready">{{ t('resources.vehicle_status_ready') }}</option>
                        <option value="in_use">{{ t('resources.vehicle_status_in_use') }}</option>
                        <option value="maintenance">{{ t('resources.vehicle_status_maintenance') }}</option>
                        <option value="broken">{{ t('resources.vehicle_status_broken') }}</option>
                      </select>
                    </label>
                    <label class="block sm:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_odometer') }}</span>
                      <input
                        v-model="vehicleForm.odometer_km"
                        type="number"
                        min="0"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_odometer')"
                      />
                    </label>
                  </div>
                </section>
                <section class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                  <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('resources.vehicle_modal_section_dates') }}
                  </h3>
                  <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_inspection') }}</span>
                      <input
                        v-model="vehicleForm.inspection_expires_at"
                        type="date"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      />
                    </label>
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_insurance') }}</span>
                      <input
                        v-model="vehicleForm.insurance_expires_at"
                        type="date"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      />
                    </label>
                    <label class="block sm:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_road_fee') }}</span>
                      <input
                        v-model="vehicleForm.road_fee_expires_at"
                        type="date"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      />
                    </label>
                  </div>
                </section>
                <section class="rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/60 lg:col-span-2">
                  <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('resources.vehicle_modal_section_registry') }}
                  </h3>
                  <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_owner') }}</span>
                      <input
                        v-model="vehicleForm.owner_name"
                        type="text"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_owner')"
                      />
                    </label>
                    <div class="grid gap-4 sm:grid-cols-3 lg:col-span-2">
                      <label class="block">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_year_mfg') }}</span>
                        <input
                          v-model="vehicleForm.year_manufactured"
                          type="number"
                          min="1900"
                          max="2100"
                          class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                          :placeholder="t('resources.vehicle_ph_year')"
                        />
                      </label>
                      <label class="block">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_purchased') }}</span>
                        <input
                          v-model="vehicleForm.purchased_at"
                          type="date"
                          class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        />
                      </label>
                      <label class="block">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_usage_until_year') }}</span>
                        <input
                          v-model="vehicleForm.usage_expires_year"
                          type="number"
                          min="1900"
                          max="2100"
                          class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                          :placeholder="t('resources.vehicle_ph_usage_year')"
                        />
                      </label>
                    </div>
                    <label class="block lg:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_frame_engine') }}</span>
                      <textarea
                        v-model="vehicleForm.frame_engine_number"
                        rows="3"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_frame')"
                      />
                    </label>
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_insurance_provider') }}</span>
                      <input
                        v-model="vehicleForm.insurance_provider"
                        type="text"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_insurer')"
                      />
                    </label>
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_reg_cycle') }}</span>
                      <input
                        v-model="vehicleForm.registration_cycle_note"
                        type="text"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_reg_cycle')"
                      />
                    </label>
                    <label class="block lg:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_insurance_policy_note') }}</span>
                      <textarea
                        v-model="vehicleForm.insurance_policy_note"
                        rows="2"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_insurance_policy')"
                      />
                    </label>
                  </div>
                </section>
                <section class="rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/60 lg:col-span-2">
                  <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('resources.vehicle_modal_section_ops') }}
                  </h3>
                  <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <label class="block">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_last_maint') }}</span>
                      <input
                        v-model="vehicleForm.last_maintenance_at"
                        type="date"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      />
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                      <label class="block">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_caretaker') }}</span>
                        <input
                          v-model="vehicleForm.caretaker_name"
                          type="text"
                          class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                          :placeholder="t('resources.vehicle_ph_caretaker')"
                        />
                      </label>
                      <label class="block">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_caretaker_phone') }}</span>
                        <input
                          v-model="vehicleForm.caretaker_phone"
                          type="text"
                          class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                          :placeholder="t('resources.vehicle_ph_caretaker_phone')"
                        />
                      </label>
                    </div>
                    <label class="block lg:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_maint_note') }}</span>
                      <textarea
                        v-model="vehicleForm.maintenance_schedule_note"
                        rows="2"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_maint_schedule')"
                      />
                    </label>
                    <label class="block lg:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('resources.vehicle_form_notes') }}</span>
                      <textarea
                        v-model="vehicleForm.notes"
                        rows="3"
                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="t('resources.vehicle_ph_notes')"
                      />
                    </label>
                    <label class="block lg:col-span-2">
                      <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{
                        t('resources.vehicle_form_attachment_label')
                      }}</span>
                      <p class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
                        {{ t('resources.vehicle_form_attachment_hint') }}
                      </p>
                      <input
                        ref="vehicleFormAuxInputRef"
                        type="file"
                        accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/*"
                        class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-300 dark:file:bg-slate-700 dark:file:text-slate-200"
                        @change="onVehicleAuxFileChange"
                      />
                    </label>
                  </div>
                </section>
              </div>
              <p v-if="vehicleFormError" class="mt-4 text-xs text-rose-600">{{ vehicleFormError }}</p>
            </div>
            <div
              class="flex shrink-0 flex-wrap gap-2 border-t border-slate-200/90 bg-slate-50/90 px-5 py-4 dark:border-slate-700 dark:bg-slate-900/90 sm:px-6"
            >
              <button
                type="button"
                class="min-h-[44px] flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 sm:min-h-0 sm:flex-initial sm:px-6"
                @click="vehicleModalOpen = false"
              >
                {{ t('app.cancel') }}
              </button>
              <button
                type="submit"
                class="min-h-[44px] flex-[2] rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50 sm:min-h-0 sm:flex-initial sm:px-10"
                :disabled="vehicleSaving"
              >
                {{ vehicleSaving ? t('resources.loading') : t('resources.vehicle_form_save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal: tab Tài xế = thêm tài xế từ user; chi tiết xe = gán TX mặc định (select) -->
    <Teleport to="body">
      <div
        v-if="assignModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="closeAssignModal"
      >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40" @click.stop>
          <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
              <template v-if="assignVehicleId != null">{{ t('resources.assign_driver_modal_title') }}</template>
              <template v-else-if="addDriverMode === 'external'">{{ t('resources.add_driver_modal_title_external') }}</template>
              <template v-else>{{ t('resources.assign_driver_modal_title_add') }}</template>
            </h2>
            <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              <template v-if="assignVehicleId != null">{{ t('resources.assign_driver_modal_hint_vehicle') }}</template>
              <template v-else-if="addDriverMode === 'external'">{{ t('resources.add_driver_hint_external') }}</template>
              <template v-else>{{ t('resources.assign_driver_modal_hint_add') }}</template>
            </p>
          </div>

          <!-- Thêm tài xế: từ user HOẶC ngoài hệ thống (tab Tài xế) -->
          <div v-if="assignVehicleId == null" class="max-h-[min(70vh,560px)] overflow-y-auto px-5 py-4 sm:px-6">
            <div class="mb-4 flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-slate-50/80 p-1 dark:border-slate-700 dark:bg-slate-800/50">
              <button
                type="button"
                class="min-h-[40px] flex-1 rounded-lg px-3 py-2 text-xs font-medium transition sm:text-sm"
                :class="
                  addDriverMode === 'user'
                    ? 'bg-white text-teal-800 shadow-sm dark:bg-slate-900 dark:text-teal-300'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                "
                @click="setAddDriverMode('user')"
              >
                {{ t('resources.add_driver_mode_user') }}
              </button>
              <button
                type="button"
                class="min-h-[40px] flex-1 rounded-lg px-3 py-2 text-xs font-medium transition sm:text-sm"
                :class="
                  addDriverMode === 'external'
                    ? 'bg-white text-teal-800 shadow-sm dark:bg-slate-900 dark:text-teal-300'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                "
                @click="setAddDriverMode('external')"
              >
                {{ t('resources.add_driver_mode_external') }}
              </button>
            </div>

            <template v-if="addDriverMode === 'user'">
              <input
                v-model="userSearchQuery"
                type="search"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.assign_driver_search_placeholder')"
                @input="scheduleUserSearch"
              />
              <div v-if="userSearchLoading" class="mt-3 text-xs text-slate-500">{{ t('resources.loading') }}</div>
              <ul v-else-if="userSearchResults.length" class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-200 dark:divide-slate-700 dark:border-slate-700">
                <li v-for="u in userSearchResults" :key="u.id">
                  <button
                    type="button"
                    class="flex w-full items-start gap-3 px-3 py-2.5 text-left text-sm transition hover:bg-teal-50 dark:hover:bg-slate-800"
                    :class="pickedUser?.id === u.id ? 'bg-teal-50 dark:bg-slate-800' : ''"
                    @click="pickedUser = u"
                  >
                    <div class="min-w-0 flex-1">
                      <div class="font-medium text-slate-900 dark:text-white">{{ u.name }}</div>
                      <div class="truncate text-xs text-slate-500">{{ u.email }}</div>
                      <div class="mt-0.5 flex flex-wrap gap-2 text-[11px] text-slate-500">
                        <span v-if="u.employee_code" class="font-mono">{{ u.employee_code }}</span>
                        <span v-if="u.phone">{{ u.phone }}</span>
                      </div>
                    </div>
                  </button>
                </li>
              </ul>
              <p v-else-if="userSearchQuery.trim().length >= 2 && !userSearchLoading" class="mt-3 text-xs text-slate-500">{{ t('resources.empty') }}</p>
            </template>

            <form v-else class="space-y-3" @submit.prevent>
              <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-name"
                  >{{ t('resources.add_driver_full_name_label') }} <span class="text-rose-600" aria-hidden="true">*</span></label
                >
                <input
                  id="ext-driver-name"
                  v-model="externalDriverForm.full_name"
                  type="text"
                  autocomplete="name"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :placeholder="t('driver_detail.ph_full_name')"
                />
              </div>
              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-phone">{{ t('resources.col_phone') }}</label>
                  <input
                    id="ext-driver-phone"
                    v-model="externalDriverForm.phone"
                    type="tel"
                    autocomplete="tel"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    :placeholder="t('driver_detail.ph_phone')"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-nid">{{ t('driver_detail.national_id') }}</label>
                  <input
                    id="ext-driver-nid"
                    v-model="externalDriverForm.national_id"
                    type="text"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    :placeholder="t('driver_detail.ph_national_id')"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-lic">{{ t('driver_detail.license_class') }}</label>
                  <input
                    id="ext-driver-lic"
                    v-model="externalDriverForm.license_class"
                    type="text"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    :placeholder="t('driver_detail.ph_license_class')"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-licexp">{{ t('driver_detail.license_expires') }}</label>
                  <input
                    id="ext-driver-licexp"
                    v-model="externalDriverForm.license_expires_at"
                    type="date"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-emp">{{ t('driver_detail.employment') }}</label>
                  <select
                    id="ext-driver-emp"
                    v-model="externalDriverForm.employment_status"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  >
                    <option value="active">{{ t('driver_detail.emp_active') }}</option>
                    <option value="on_leave">{{ t('driver_detail.emp_on_leave') }}</option>
                    <option value="terminated">{{ t('driver_detail.emp_terminated') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="ext-driver-avail">{{ t('driver_detail.availability') }}</label>
                  <select
                    id="ext-driver-avail"
                    v-model="externalDriverForm.availability_status"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  >
                    <option value="available">{{ t('driver_detail.avail_available') }}</option>
                    <option value="busy">{{ t('driver_detail.avail_busy') }}</option>
                    <option value="offline">{{ t('driver_detail.avail_offline') }}</option>
                  </select>
                </div>
              </div>
            </form>

            <p v-if="assignError" class="mt-3 text-xs text-rose-600">{{ assignError }}</p>
          </div>

          <!-- Gán tài xế mặc định cho xe (chi tiết xe) -->
          <div v-else class="max-h-[55vh] space-y-4 overflow-y-auto px-5 py-4 sm:px-6">
            <div class="rounded-lg border border-slate-200 bg-slate-50/90 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800/60">
              <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.col_vehicle') }}</div>
              <div v-if="assignVehicleDisplay" class="mt-0.5 font-semibold text-slate-900 dark:text-white">
                {{ assignVehicleDisplay.code }} <span class="font-normal text-slate-600 dark:text-slate-400">· {{ assignVehicleDisplay.model }}</span>
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400" for="assign-driver-select">{{
                t('resources.assign_driver_modal_label_driver')
              }}</label>
              <select
                id="assign-driver-select"
                v-model.number="pickedDriverId"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              >
                <option :value="null">{{ t('resources.assign_driver_modal_placeholder_driver') }}</option>
                <option v-for="d in driversForAssignSelect" :key="d.id" :value="d.id">{{ driverAssignOptionLabel(d) }}</option>
              </select>
              <p v-if="!driversForAssignSelect.length" class="text-xs text-amber-700 dark:text-amber-400">
                {{ t('resources.assign_driver_modal_no_drivers') }}
              </p>
            </div>
            <p v-if="assignError" class="text-xs text-rose-600">{{ assignError }}</p>
          </div>

          <div class="flex flex-wrap gap-2 border-t border-slate-200/90 bg-slate-50/90 px-5 py-4 dark:border-slate-700 dark:bg-slate-900/90 sm:px-6">
            <button
              type="button"
              class="min-h-[44px] flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 sm:min-h-0 sm:flex-initial sm:px-6"
              @click="closeAssignModal"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="min-h-[44px] flex-[2] rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50 sm:min-h-0 sm:flex-initial sm:px-10"
              :disabled="assignSubmitDisabled"
              @click="submitAssignDriver"
            >
              {{
                assignSubmitting
                  ? t('resources.assign_driver_assigning')
                  : assignVehicleId != null
                    ? t('resources.assign_driver_confirm')
                    : t('resources.assign_driver_confirm_add')
              }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: thêm / sửa nhà cung cấp -->
    <Teleport to="body">
      <div
        v-if="providerModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="provider-modal-title"
        @click.self="providerModalOpen = false"
      >
        <div
          class="flex max-h-[min(92vh,880px)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div
            class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200/90 bg-gradient-to-r from-slate-50 via-white to-indigo-50/30 px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20"
          >
            <div class="min-w-0">
              <h2 id="provider-modal-title" class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ providerForm.id ? t('resources.provider_form_title_edit') : t('resources.provider_form_title_add') }}
              </h2>
              <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                {{ t('resources.provider_form_subtitle') }}
              </p>
            </div>
            <button
              type="button"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
              :aria-label="t('resources.close_panel')"
              @click="providerModalOpen = false"
            >
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitProviderForm">
            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-y-contain px-5 py-4 sm:px-6">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              <span>{{ t('resources.provider_form_name') }} <span class="text-rose-600" aria-hidden="true">*</span></span>
              <input
                v-model="providerForm.name"
                type="text"
                required
                autocomplete="organization"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.provider_form_ph_name')"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_type') }}
              <select v-model="providerForm.type" class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                <option value="vendor">{{ t('resources.provider_form_type_vendor') }}</option>
                <option value="taxi">{{ t('resources.provider_form_type_taxi') }}</option>
              </select>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contact_name') }}
                <input
                  v-model="providerForm.contact_name"
                  type="text"
                  autocomplete="name"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :placeholder="t('resources.provider_form_ph_contact_name')"
                />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contact_phone') }}
                <input
                  v-model="providerForm.contact_phone"
                  type="tel"
                  autocomplete="tel"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :placeholder="t('resources.provider_form_ph_contact_phone')"
                />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_contact_email') }}
              <input
                v-model="providerForm.contact_email"
                type="email"
                autocomplete="email"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.provider_form_ph_contact_email')"
              />
            </label>
            <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
              <input v-model="providerForm.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600" />
              {{ t('resources.provider_active') }}
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_notes') }}
              <textarea
                v-model="providerForm.notes"
                rows="2"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.provider_form_ph_notes')"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contract_number') }}
                <input
                  v-model="providerForm.contract_number"
                  type="text"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :placeholder="t('resources.provider_form_ph_contract')"
                />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contract_signed') }}
                <input v-model="providerForm.contract_signed_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_contract_expires') }}
              <input v-model="providerForm.contract_expires_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            </label>

            <div class="border-t border-slate-200 pt-3 dark:border-slate-700">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('resources.section_supplier_services') }}</span>
                <button type="button" class="text-xs font-medium text-teal-700 hover:text-teal-800 dark:text-teal-400" @click="addProviderServiceRow">
                  {{ t('resources.provider_form_add_row') }}
                </button>
              </div>
              <div v-for="(row, idx) in providerForm.services" :key="row._key" class="mb-2 grid gap-2 rounded-lg border border-slate-100 p-2 dark:border-slate-700">
                <div class="flex flex-wrap items-center gap-2">
                  <select v-model="row.kind" class="rounded border border-slate-200 py-1 pl-2 pr-6 text-xs dark:border-slate-600 dark:bg-slate-800">
                    <option value="solution">{{ t('resources.provider_kind_solution') }}</option>
                    <option value="service">{{ t('resources.provider_kind_service') }}</option>
                  </select>
                  <button
                    v-if="providerForm.services.length > 1"
                    type="button"
                    class="ml-auto text-xs text-rose-600 hover:underline"
                    @click="removeProviderServiceRow(idx)"
                  >
                    {{ t('resources.provider_form_remove_row') }}
                  </button>
                </div>
                <input
                  v-model="row.name"
                  type="text"
                  :placeholder="t('resources.provider_form_service_name')"
                  class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                />
                <input
                  v-model="row.note"
                  type="text"
                  :placeholder="t('resources.provider_form_service_note')"
                  class="w-full rounded border border-slate-200 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800"
                />
              </div>
            </div>
            </div>

            <p v-if="providerFormError" class="shrink-0 border-t border-slate-200/90 bg-white px-5 pb-2 pt-3 text-xs text-rose-600 dark:border-slate-700 dark:bg-slate-900 sm:px-6">
              {{ providerFormError }}
            </p>
            <div
              class="flex shrink-0 flex-wrap gap-2 border-t border-slate-200/90 bg-slate-50/90 px-5 py-4 dark:border-slate-700 dark:bg-slate-900/90 sm:px-6"
            >
              <button
                type="button"
                class="min-h-[44px] flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 sm:min-h-0 sm:flex-initial sm:px-6"
                @click="providerModalOpen = false"
              >
                {{ t('app.cancel') }}
              </button>
              <button
                type="submit"
                class="min-h-[44px] flex-[2] rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50 sm:min-h-0 sm:flex-initial sm:px-10"
                :disabled="providerSaving"
              >
                {{ providerSaving ? t('resources.loading') : t('resources.provider_form_save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal: giấy tờ xe -->
    <Teleport to="body">
      <div
        v-if="vehicleDocModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="vehicleDocModalOpen = false"
      >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40" @click.stop>
          <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
              {{ editingVehicleDocId ? t('resources.vehicle_doc_modal_edit') : t('resources.vehicle_doc_modal_add') }}
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('resources.vehicle_doc_modal_hint') }}</p>
          </div>
          <form class="flex max-h-[min(85vh,720px)] flex-col" @submit.prevent="submitVehicleDocForm">
            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-y-contain px-5 py-4 sm:px-6">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              <span>{{ t('driver_detail.col_doc_type') }} <span class="text-rose-600" aria-hidden="true">*</span></span>
              <select
                v-model="vehicleDocForm.doc_type"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              >
                <option v-for="opt in vehicleDocTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_title') }}
              <input
                v-model="vehicleDocForm.title"
                type="text"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.vehicle_doc_ph_title')"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.notes') }}
              <textarea
                v-model="vehicleDocForm.notes"
                rows="2"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('resources.vehicle_doc_ph_notes')"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.issued_at') }}
                <input v-model="vehicleDocForm.issued_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.expires_at') }}
                <input v-model="vehicleDocForm.expires_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.upload_file') }}
              <input type="file" class="mt-1 w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-800 dark:file:bg-teal-950 dark:file:text-teal-300" @change="onVehicleDocFile" />
            </label>
            <label v-if="editingVehicleDocId" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
              <input v-model="vehicleDocForm.replace_file" type="checkbox" class="rounded border-slate-300 text-teal-600" />
              {{ t('driver_detail.replace_file') }}
            </label>
            </div>
            <p v-if="vehicleDocFormError" class="shrink-0 border-t border-slate-200/90 bg-white px-5 pb-2 pt-3 text-xs text-rose-600 dark:border-slate-700 dark:bg-slate-900 sm:px-6">
              {{ vehicleDocFormError }}
            </p>
            <div class="flex shrink-0 flex-wrap gap-2 border-t border-slate-200/90 bg-slate-50/90 px-5 py-4 dark:border-slate-700 dark:bg-slate-900/90 sm:px-6">
              <button
                type="button"
                class="min-h-[44px] flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 sm:min-h-0 sm:flex-initial sm:px-6"
                @click="vehicleDocModalOpen = false"
              >
                {{ t('app.cancel') }}
              </button>
              <button
                type="submit"
                class="min-h-[44px] flex-[2] rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50 sm:min-h-0 sm:flex-initial sm:px-10"
                :disabled="vehicleDocSaving"
              >
                {{ vehicleDocSaving ? t('resources.loading') : t('resources.vehicle_doc_save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal: chuyển xe vào thùng rác -->
    <Teleport to="body">
      <div
        v-if="vehicleDeleteModalOpen"
        class="fixed inset-0 z-[110] flex items-end justify-center bg-black/50 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:items-center"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="vehicle-delete-title"
        @click.self="vehicleDeleteModalOpen = false"
      >
        <div
          class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-700">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">
                <TrashIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <h2 id="vehicle-delete-title" class="text-base font-semibold text-slate-900 dark:text-white">
                  {{ t('resources.vehicle_delete_modal_title') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                  {{ t('resources.vehicle_delete_modal_body', { plate: vehicleDeleteTarget?.code ?? '' }) }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex gap-2 px-4 pb-4 pt-2">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="vehicleDeleteModalOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-rose-600 py-2.5 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-50"
              :disabled="vehicleDeleting"
              @click="confirmMoveVehicleToTrash"
            >
              {{ vehicleDeleting ? t('resources.loading') : t('resources.vehicle_delete_modal_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="driverDeleteModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="driverDeleteModalOpen = false"
      >
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40">
          <div class="flex gap-3 border-b border-slate-100 px-4 py-4 dark:border-slate-700">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
              <TrashIcon class="h-5 w-5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <h2 id="driver-delete-title" class="text-base font-semibold text-slate-900 dark:text-white">
                {{ t('resources.driver_delete_modal_title') }}
              </h2>
              <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                {{ t('resources.driver_delete_modal_body', { name: driverDeleteTarget?.name ?? '' }) }}
              </p>
            </div>
          </div>
          <div class="flex gap-2 px-4 pb-4 pt-2">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="driverDeleteModalOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-rose-600 py-2.5 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-50"
              :disabled="driverDeleting"
              @click="confirmMoveDriverToTrash"
            >
              {{ driverDeleting ? t('resources.loading') : t('resources.driver_delete_modal_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="providerDeleteModalOpen"
        class="fixed inset-0 z-[110] flex items-end justify-center bg-black/50 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:items-center"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="provider-delete-title"
        @click.self="providerDeleteModalOpen = false"
      >
        <div
          class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-700">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">
                <TrashIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <h2 id="provider-delete-title" class="text-base font-semibold text-slate-900 dark:text-white">
                  {{ t('resources.provider_delete_modal_title') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                  {{ t('resources.provider_delete_modal_body', { name: providerDeleteTarget?.name ?? '' }) }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex gap-2 px-4 pb-4 pt-2">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="providerDeleteModalOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-rose-600 py-2.5 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-50"
              :disabled="providerDeleting"
              @click="confirmMoveProviderToTrash"
            >
              {{ providerDeleting ? t('resources.loading') : t('resources.provider_delete_modal_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="bulkTrashModalOpen"
        class="fixed inset-0 z-[115] flex items-end justify-center bg-black/50 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:items-center"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="bulk-trash-title"
        @click.self="!bulkTrashSubmitting && (bulkTrashModalOpen = false)"
      >
        <div
          class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-700">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">
                <TrashIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <h2 id="bulk-trash-title" class="text-base font-semibold text-slate-900 dark:text-white">
                  {{ t('resources.bulk_move_to_trash_title') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                  {{
                    t('resources.bulk_move_to_trash_confirm', {
                      n: bulkTrashPendingIds.length,
                    })
                  }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex gap-2 px-4 pb-4 pt-2">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              :disabled="bulkTrashSubmitting"
              @click="bulkTrashModalOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-rose-600 py-2.5 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-50"
              :disabled="bulkTrashSubmitting"
              @click="confirmBulkTrash"
            >
              {{ bulkTrashSubmitting ? t('resources.loading') : t('resources.vehicle_delete_modal_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="bulkForceModalOpen"
        class="fixed inset-0 z-[115] flex items-end justify-center bg-black/50 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:items-center"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="bulk-force-title"
        @click.self="!bulkForceSubmitting && (bulkForceModalOpen = false)"
      >
        <div
          class="w-full max-w-lg overflow-hidden rounded-2xl border border-rose-200/80 bg-white shadow-2xl ring-1 ring-rose-900/10 dark:border-rose-900/50 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div class="border-b border-rose-100 bg-rose-50/80 px-4 py-4 dark:border-rose-900/40 dark:bg-rose-950/40">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                <TrashIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <h2 id="bulk-force-title" class="text-base font-semibold text-slate-900 dark:text-white">
                  {{ t('resources.bulk_force_resources_title') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                  {{ t('resources.bulk_force_resources_confirm', { n: bulkForcePendingIds.length }) }}
                </p>
                <p class="mt-2 text-xs text-rose-800 dark:text-rose-300/90">{{ t('resources.force_delete_modal_hint') }}</p>
              </div>
            </div>
          </div>
          <div class="flex gap-2 px-4 pb-4 pt-2">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              :disabled="bulkForceSubmitting"
              @click="bulkForceModalOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-rose-600 py-2.5 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-50"
              :disabled="bulkForceSubmitting"
              @click="confirmBulkForceDelete"
            >
              {{ bulkForceSubmitting ? t('resources.loading') : t('resources.force_delete_modal_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="forceDeleteModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:items-center"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="force-delete-title"
        @click.self="forceDeleteModalOpen = false"
      >
        <div
          class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-900/40"
          @click.stop
        >
          <div class="border-b border-rose-200/60 bg-rose-50/50 px-4 py-4 dark:border-rose-900/40 dark:bg-rose-950/30">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                <TrashIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <h2 id="force-delete-title" class="text-base font-semibold text-slate-900 dark:text-white">
                  {{ t('resources.force_delete_modal_title') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                  {{ t('resources.force_delete_modal_body', { name: forceDeleteContext.name }) }}
                </p>
                <p class="mt-2 text-xs text-rose-700 dark:text-rose-300">
                  {{ t('resources.force_delete_modal_hint') }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex gap-2 px-4 pb-4 pt-2">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              :disabled="forcePermDeleting"
              @click="forceDeleteModalOpen = false"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-rose-700 py-2.5 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50"
              :disabled="forcePermDeleting"
              @click="confirmForceDeletePerm"
            >
              {{ forcePermDeleting ? t('resources.loading') : t('resources.force_delete_modal_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BuildingOffice2Icon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  PlusCircleIcon,
  TrashIcon,
  ViewColumnsIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import {
  bulkDeleteDrivers,
  bulkDeleteTransportProviders,
  bulkDeleteVehicles,
  bulkForceDeleteDrivers,
  bulkForceDeleteTransportProviders,
  bulkForceDeleteVehicles,
  createDriver,
  createDriverFromUser,
  createTransportProvider,
  createVehicle,
  createVehicleComplianceDocument,
  deleteDriver,
  deleteTransportProvider,
  deleteVehicle,
  deleteVehicleComplianceDocument,
  forceDeleteDriver as forceDeleteDriverRequest,
  forceDeleteTransportProvider as forceDeleteTransportProviderRequest,
  forceDeleteVehicle as forceDeleteVehicleRequest,
  listDrivers,
  listTransportProviders,
  listVehicleComplianceDocuments,
  listVehicles,
  restoreDriver as restoreDriverRequest,
  restoreTransportProvider as restoreTransportProviderRequest,
  restoreVehicle as restoreVehicleRequest,
  searchUsersForDriverAssignment,
  updateTransportProvider,
  updateVehicle,
  updateVehicleComplianceDocument,
} from '../../api/operational'
import { formatApiError } from '../../api/http'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import { VEHICLE_ICON_COMPONENTS, vehicleIconKind } from '../../util/vehicleIcon'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const canManageVehicles = computed(() => auth.hasPermission('resource.vehicle.manage'))
/** Xem danh sách giấy tờ / đính kèm (cùng quyền gần với xem danh sách xe) */
const canViewVehicleComplianceDocs = computed(
  () => auth.hasPermission('resource.vehicle.manage') || auth.hasPermission('trip.assign'),
)
const canManageDrivers = computed(() => auth.hasPermission('resource.driver.manage'))
const canManageProviders = computed(() => auth.hasPermission('resource.provider.manage'))

const tabs = [
  { id: 'vehicles', labelKey: 'resources.tab_vehicles' },
  { id: 'drivers', labelKey: 'resources.tab_drivers' },
  { id: 'suppliers', labelKey: 'resources.tab_suppliers' },
]

const activeTab = ref('vehicles')
const vehiclesViewMode = ref('active')
const driversViewMode = ref('active')
const suppliersViewMode = ref('active')
const search = ref('')
const selectedVehicle = ref(null)
const selectedSupplier = ref(null)
const loading = ref(true)
const error = ref('')

const vehicles = ref([])
const drivers = ref([])
const suppliers = ref([])

const filters = ref({
  status: '',
  type: '',
  driver_default: '',
  insurance: '',
  inspection: '',
  road_fee: '',
  contract: '',
  driver_license: '',
  driver_availability: '',
})

const activeResourceFilterCount = computed(() => {
  let n = 0
  if (filters.value.status) n++
  if (activeTab.value === 'vehicles') {
    if (filters.value.type) n++
    if (filters.value.driver_default) n++
    if (filters.value.insurance) n++
    if (filters.value.inspection) n++
    if (filters.value.road_fee) n++
  }
  if (activeTab.value === 'suppliers' && filters.value.contract) n++
  if (activeTab.value === 'drivers') {
    if (filters.value.driver_license) n++
    if (filters.value.driver_availability) n++
  }
  return n
})

const resourceFilterStatusLabel = computed(() => {
  const f = filters.value.status
  if (!f) return t('resources.filter_all')
  const map = {
    active: t('resources.status_active'),
    maintenance: t('resources.status_maintenance'),
    inactive: t('resources.status_inactive'),
  }
  return map[f] ?? f
})

const resourceFilterTypeLabel = computed(() => {
  const f = filters.value.type
  if (!f) return t('resources.filter_all')
  const map = {
    van: t('resources.type_van'),
    truck: t('resources.type_truck'),
    bus: t('resources.type_bus'),
  }
  return map[f] ?? f
})

const resourceFilterDriverDefaultLabel = computed(() => {
  const f = filters.value.driver_default
  if (f === 'assigned') return t('resources.filter_driver_assigned')
  if (f === 'unassigned') return t('resources.filter_driver_unassigned')
  return t('resources.filter_all')
})

function resourceFilterDocStateLabel(state) {
  if (!state) return t('resources.filter_all')
  const map = {
    ok: t('resources.compliance_ok'),
    soon: t('resources.compliance_soon'),
    exp: t('resources.compliance_exp'),
  }
  return map[state] ?? state
}

const resourceFilterContractLabel = computed(() => {
  const f = filters.value.contract
  if (!f) return t('resources.filter_all')
  const map = {
    ok: t('resources.compliance_ok'),
    soon: t('resources.compliance_soon'),
    exp: t('resources.compliance_exp'),
  }
  return map[f] ?? f
})

function resetResourceFilters() {
  filters.value = {
    status: '',
    type: '',
    driver_default: '',
    insurance: '',
    inspection: '',
    road_fee: '',
    contract: '',
    driver_license: '',
    driver_availability: '',
  }
}

function closeResourceFilterMenu() {
  const el = resourceFilterMenuRef.value
  if (el && 'open' in el) el.open = false
}

function emptyExternalDriverForm() {
  return {
    full_name: '',
    phone: '',
    national_id: '',
    license_class: '',
    license_expires_at: '',
    employment_status: 'active',
    availability_status: 'available',
  }
}

const assignModalOpen = ref(false)
const assignVehicleId = ref(null)
const pickedDriverId = ref(null)
const assignSubmitting = ref(false)
const assignError = ref('')
const addDriverMode = ref('user')
const externalDriverForm = ref(emptyExternalDriverForm())
const userSearchQuery = ref('')
const userSearchResults = ref([])
const userSearchLoading = ref(false)
const pickedUser = ref(null)
let userSearchTimer = null

const driversForAssignSelect = computed(() =>
  [...drivers.value]
    .filter((d) => !d.deleted_at)
    .sort((a, b) => String(a.name).localeCompare(String(b.name), undefined, { sensitivity: 'base' })),
)

const assignVehicleDisplay = computed(() => {
  const id = assignVehicleId.value
  if (id == null) return null
  return vehicles.value.find((v) => v.id === id) ?? null
})

const assignSubmitDisabled = computed(() => {
  if (assignSubmitting.value) return true
  if (assignVehicleId.value != null) {
    return pickedDriverId.value == null
  }
  if (addDriverMode.value === 'external') {
    return !String(externalDriverForm.value.full_name || '').trim()
  }
  return !pickedUser.value
})

const vehicleModalOpen = ref(false)
const vehicleSaving = ref(false)
const vehicleDeleting = ref(false)
const vehicleDeactivating = ref(false)
const vehicleDeleteModalOpen = ref(false)
const vehicleDeleteTarget = ref(null)
const vehicleRestoring = ref(false)
const vehicleFormError = ref('')
const driverDeleteModalOpen = ref(false)
const driverDeleteTarget = ref(null)
const driverDeleting = ref(false)
const driverRestoring = ref(false)
const supplierRestoring = ref(false)
const providerDeleteModalOpen = ref(false)
const providerDeleteTarget = ref(null)
const providerDeleting = ref(false)
const forceDeleteModalOpen = ref(false)
const forceDeleteContext = ref({ kind: '', id: null, name: '' })
const forcePermDeleting = ref(false)
const bulkVehicleIds = ref([])
const bulkDriverIds = ref([])
const bulkSupplierIds = ref([])
const bulkTrashModalOpen = ref(false)
const bulkTrashKind = ref('')
const bulkTrashPendingIds = ref([])
const bulkTrashSubmitting = ref(false)
const bulkForceModalOpen = ref(false)
const bulkForceKind = ref('')
const bulkForcePendingIds = ref([])
const bulkForceSubmitting = ref(false)
const resourceFilterMenuRef = ref(null)
const resourceFiltersExtraOpen = ref(false)

const resourceStatusFilterOptions = computed(() => [
  { value: '', label: t('resources.filter_all') },
  { value: 'active', label: t('resources.status_active') },
  { value: 'maintenance', label: t('resources.status_maintenance') },
  { value: 'inactive', label: t('resources.status_inactive') },
])

const resourceVehicleTypeFilterOptions = computed(() => [
  { value: '', label: t('resources.filter_all') },
  { value: 'van', label: t('resources.type_van') },
  { value: 'truck', label: t('resources.type_truck') },
  { value: 'bus', label: t('resources.type_bus') },
])

const resourceDriverDefaultFilterOptions = computed(() => [
  { value: '', label: t('resources.filter_all') },
  { value: 'assigned', label: t('resources.filter_driver_assigned') },
  { value: 'unassigned', label: t('resources.filter_driver_unassigned') },
])

const resourceFilterDriverAvailabilityLabel = computed(() => {
  const f = filters.value.driver_availability
  if (f === 'available') return t('driver_detail.avail_available')
  if (f === 'busy') return t('driver_detail.avail_busy')
  if (f === 'offline') return t('driver_detail.avail_offline')
  return t('resources.filter_all')
})

const resourceDriverAvailabilityFilterOptions = computed(() => [
  { value: '', label: t('resources.filter_all') },
  { value: 'available', label: t('driver_detail.avail_available') },
  { value: 'busy', label: t('driver_detail.avail_busy') },
  { value: 'offline', label: t('driver_detail.avail_offline') },
])

const resourceDocStateFilterOptions = computed(() => [
  { value: '', label: t('resources.filter_all') },
  { value: 'ok', label: t('resources.compliance_ok') },
  { value: 'soon', label: t('resources.compliance_soon') },
  { value: 'exp', label: t('resources.compliance_exp') },
])

const resourceVehicleDocFiltersExtra = computed(() => [
  { key: 'insurance', label: t('resources.filter_insurance') },
  { key: 'inspection', label: t('resources.filter_inspection') },
  { key: 'road_fee', label: t('resources.filter_road_fee') },
])

function closeParentDetails(ev) {
  const el = ev?.target
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function applyResourceFilterPatch(ev, patch) {
  filters.value = { ...filters.value, ...patch }
  closeParentDetails(ev)
}

const VEHICLE_COL_STORAGE_KEY = 'va-resources-vehicle-cols-v1'
const VEHICLE_COL_DEFAULTS = {
  type_capacity: true,
  status: true,
  compliance: true,
  driver: true,
  owner: false,
  insurance_exp: false,
  inspection_exp: false,
  road_fee: false,
  year: false,
  purchase: false,
  maintenance: false,
  notes: false,
}

function loadVehicleColumnPrefs() {
  try {
    const raw = localStorage.getItem(VEHICLE_COL_STORAGE_KEY)
    if (!raw) return { ...VEHICLE_COL_DEFAULTS }
    return { ...VEHICLE_COL_DEFAULTS, ...JSON.parse(raw) }
  } catch {
    return { ...VEHICLE_COL_DEFAULTS }
  }
}

const vehicleColumnVisible = ref(loadVehicleColumnPrefs())
watch(
  vehicleColumnVisible,
  (v) => {
    try {
      localStorage.setItem(VEHICLE_COL_STORAGE_KEY, JSON.stringify(v))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function vehicleColOn(id) {
  if (id === 'vehicle' || id === 'actions') return true
  return vehicleColumnVisible.value[id] !== false
}

function setVehicleColumn(id, checked) {
  vehicleColumnVisible.value = { ...vehicleColumnVisible.value, [id]: checked }
}

const vehicleColumnToggleOptions = computed(() => [
  { id: 'type_capacity', labelKey: 'resources.col_type_capacity' },
  { id: 'status', labelKey: 'resources.col_status' },
  { id: 'compliance', labelKey: 'resources.col_compliance' },
  { id: 'insurance_exp', labelKey: 'resources.col_insurance_exp' },
  { id: 'inspection_exp', labelKey: 'resources.col_inspection_exp' },
  { id: 'road_fee', labelKey: 'resources.col_road_fee_exp' },
  { id: 'owner', labelKey: 'resources.col_owner' },
  { id: 'year', labelKey: 'resources.col_year_mfg' },
  { id: 'purchase', labelKey: 'resources.col_purchase' },
  { id: 'driver', labelKey: 'resources.col_driver' },
  { id: 'maintenance', labelKey: 'resources.col_maintenance' },
  { id: 'notes', labelKey: 'resources.col_notes_short' },
])

const vehicleColumnPickerRef = ref(null)

const DRIVER_COL_STORAGE_KEY = 'va-resources-driver-cols-v1'
const DRIVER_COL_DEFAULTS = {
  email: true,
  employee_code: true,
  license_class: true,
  license_expires: true,
  phone: true,
  employment: true,
  availability: false,
}

function loadDriverColumnPrefs() {
  try {
    const raw = localStorage.getItem(DRIVER_COL_STORAGE_KEY)
    if (!raw) return { ...DRIVER_COL_DEFAULTS }
    return { ...DRIVER_COL_DEFAULTS, ...JSON.parse(raw) }
  } catch {
    return { ...DRIVER_COL_DEFAULTS }
  }
}

const driverColumnVisible = ref(loadDriverColumnPrefs())
watch(
  driverColumnVisible,
  (v) => {
    try {
      localStorage.setItem(DRIVER_COL_STORAGE_KEY, JSON.stringify(v))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function driverColOn(id) {
  if (id === 'name' || id === 'actions') return true
  return driverColumnVisible.value[id] !== false
}

function setDriverColumn(id, checked) {
  driverColumnVisible.value = { ...driverColumnVisible.value, [id]: checked }
}

const driverColumnToggleOptions = computed(() => [
  { id: 'email', labelKey: 'resources.col_user_email' },
  { id: 'employee_code', labelKey: 'resources.col_employee_code' },
  { id: 'license_class', labelKey: 'driver_detail.license_class' },
  { id: 'license_expires', labelKey: 'driver_detail.license_expires' },
  { id: 'phone', labelKey: 'resources.col_phone' },
  { id: 'employment', labelKey: 'resources.filter_status' },
  { id: 'availability', labelKey: 'driver_detail.availability' },
])

const driverColumnPickerRef = ref(null)

const SUPPLIER_COL_STORAGE_KEY = 'va-resources-supplier-cols-v1'
const SUPPLIER_COL_DEFAULTS = {
  services: true,
  contract: true,
  status: true,
  contact: false,
}

function loadSupplierColumnPrefs() {
  try {
    const raw = localStorage.getItem(SUPPLIER_COL_STORAGE_KEY)
    if (!raw) return { ...SUPPLIER_COL_DEFAULTS }
    return { ...SUPPLIER_COL_DEFAULTS, ...JSON.parse(raw) }
  } catch {
    return { ...SUPPLIER_COL_DEFAULTS }
  }
}

const supplierColumnVisible = ref(loadSupplierColumnPrefs())
watch(
  supplierColumnVisible,
  (v) => {
    try {
      localStorage.setItem(SUPPLIER_COL_STORAGE_KEY, JSON.stringify(v))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function supplierColOn(id) {
  if (id === 'supplier' || id === 'actions') return true
  return supplierColumnVisible.value[id] !== false
}

function setSupplierColumn(id, checked) {
  supplierColumnVisible.value = { ...supplierColumnVisible.value, [id]: checked }
}

const supplierColumnToggleOptions = computed(() => [
  { id: 'services', labelKey: 'resources.col_solutions_services' },
  { id: 'contract', labelKey: 'resources.col_contract' },
  { id: 'status', labelKey: 'resources.col_status' },
  { id: 'contact', labelKey: 'resources.col_contact' },
])

const supplierColumnPickerRef = ref(null)
const supplierListPage = ref(1)
const supplierPerPage = ref(10)
const supplierPerPageOptions = [5, 10, 15, 20]

const vehicleListPage = ref(1)
const vehiclePerPage = ref(10)
const vehiclePerPageOptions = [5, 10, 15, 20]

const driverListPage = ref(1)
const driverPerPage = ref(10)
const driverPerPageOptions = [5, 10, 15, 20]

function fmtVehicleTableDate(iso) {
  if (!iso) return '—'
  return String(iso)
}

function vehicleMaintenanceLine(v) {
  const parts = []
  if (v.last_maintenance_at) parts.push(fmtVehicleTableDate(v.last_maintenance_at))
  if (v.maintenance_schedule_note) parts.push(String(v.maintenance_schedule_note).trim())
  return parts.length ? parts.join(' · ') : '—'
}

function emptyVehicleForm() {
  return {
    id: null,
    license_plate: '',
    owner_name: '',
    frame_engine_number: '',
    type: '',
    year_manufactured: '',
    purchased_at: '',
    usage_expires_year: '',
    seat_count: '',
    payload_kg: '',
    insurance_provider: '',
    insurance_policy_note: '',
    status: 'ready',
    odometer_km: 0,
    inspection_expires_at: '',
    insurance_expires_at: '',
    road_fee_expires_at: '',
    registration_cycle_note: '',
    last_maintenance_at: '',
    maintenance_schedule_note: '',
    caretaker_name: '',
    caretaker_phone: '',
    notes: '',
  }
}

const vehicleForm = ref(emptyVehicleForm())
const vehicleFormAuxFile = ref(null)
const vehicleFormAuxInputRef = ref(null)

function onVehicleAuxFileChange(ev) {
  const file = ev?.target?.files?.[0]
  vehicleFormAuxFile.value = file || null
}

const providerModalOpen = ref(false)
const providerSaving = ref(false)
const providerFormError = ref('')

function emptyProviderServiceRow() {
  return {
    _key: `svc-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`,
    kind: 'solution',
    name: '',
    note: '',
  }
}

function emptyProviderForm() {
  return {
    id: null,
    name: '',
    type: 'vendor',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    notes: '',
    is_active: true,
    contract_number: '',
    contract_signed_at: '',
    contract_expires_at: '',
    services: [emptyProviderServiceRow()],
  }
}

const providerForm = ref(emptyProviderForm())

const VEHICLE_DOC_TYPES = [
  'registration',
  'insurance_certificate',
  'inspection_certificate',
  'transport_permit',
  'ownership_proof',
  'lease_contract',
  'maintenance_record',
  'other',
]

const vehicleComplianceDocs = ref([])
const vehicleDocModalOpen = ref(false)
const vehicleDocSaving = ref(false)
const vehicleDocFormError = ref('')
const editingVehicleDocId = ref(null)
const vehicleDocFile = ref(null)
const vehicleDocForm = ref({
  doc_type: 'registration',
  title: '',
  notes: '',
  issued_at: '',
  expires_at: '',
  replace_file: false,
})

const vehicleDocTypeOptions = computed(() =>
  VEHICLE_DOC_TYPES.map((value) => ({
    value,
    label: t(`vehicle_compliance_doc_type.${value}`),
  })),
)

const searchPlaceholder = computed(() => {
  if (activeTab.value === 'vehicles') return t('resources.search_vehicles')
  if (activeTab.value === 'drivers') return t('resources.search_drivers')
  return t('resources.search_suppliers')
})

const addButtonLabel = computed(() => {
  if (activeTab.value === 'vehicles') return t('resources.add_vehicle')
  if (activeTab.value === 'drivers') return t('resources.assign_driver')
  return t('resources.add_supplier')
})

function docStateFromDate(iso) {
  if (!iso) {
    return { state: 'exp', days: null, until: null }
  }
  const d = new Date(`${iso}T12:00:00`)
  const ms = d.getTime() - Date.now()
  const days = Math.ceil(ms / 86400000)
  const until = iso
  if (days < 0) return { state: 'exp', days, until }
  if (days <= 30) return { state: 'soon', days, until }
  return { state: 'ok', days: null, until }
}

/** Hợp đồng NCC: không có ngày hết hạn → trạng thái riêng (không gộp với hết hạn xe). */
function contractStateFromDate(iso) {
  if (!iso) {
    return { state: 'none', days: null, until: null }
  }
  const d = new Date(`${iso}T12:00:00`)
  const ms = d.getTime() - Date.now()
  const days = Math.ceil(ms / 86400000)
  const until = iso
  if (days < 0) return { state: 'exp', days, until }
  if (days <= 30) return { state: 'soon', days, until }
  return { state: 'ok', days: null, until }
}

function vehicleIconComponent(kind) {
  return VEHICLE_ICON_COMPONENTS[kind] || VEHICLE_ICON_COMPONENTS.van
}

function vehicleUiStatus(apiStatus) {
  if (apiStatus === 'ready' || apiStatus === 'in_use') return 'active'
  if (apiStatus === 'maintenance') return 'maintenance'
  return 'inactive'
}

function enrichVehicle(raw) {
  const parts = []
  if (raw.type) parts.push(raw.type)
  if (raw.seat_count) parts.push(`${raw.seat_count} chỗ`)
  if (raw.payload_kg) parts.push(`${raw.payload_kg} kg`)
  const typeLabel = parts.length ? parts.join(' · ') : '—'
  let capacityLabel = '—'
  if (raw.seat_count) capacityLabel = `${raw.seat_count} chỗ`
  else if (raw.payload_kg) capacityLabel = `${raw.payload_kg} kg tải`

  const dd = raw.default_driver
  const driverName = dd?.full_name || dd?.user?.name || null

  return {
    id: raw.id,
    code: raw.license_plate,
    license_plate: raw.license_plate,
    owner_name: raw.owner_name ?? '',
    frame_engine_number: raw.frame_engine_number ?? '',
    model: raw.type || '—',
    type: raw.type ?? '',
    year_manufactured: raw.year_manufactured ?? null,
    purchased_at: raw.purchased_at ?? '',
    usage_expires_year: raw.usage_expires_year ?? null,
    insurance_provider: raw.insurance_provider ?? '',
    insurance_policy_note: raw.insurance_policy_note ?? '',
    typeLabel,
    capacityLabel,
    iconKind: vehicleIconKind(raw),
    seat_count: raw.seat_count,
    payload_kg: raw.payload_kg,
    odometer_km: raw.odometer_km ?? 0,
    apiStatus: raw.status,
    status: vehicleUiStatus(raw.status),
    insurance: docStateFromDate(raw.insurance_expires_at),
    inspection: docStateFromDate(raw.inspection_expires_at),
    road_fee: docStateFromDate(raw.road_fee_expires_at),
    inspection_expires_at: raw.inspection_expires_at,
    insurance_expires_at: raw.insurance_expires_at,
    road_fee_expires_at: raw.road_fee_expires_at,
    registration_cycle_note: raw.registration_cycle_note ?? '',
    last_maintenance_at: raw.last_maintenance_at ?? '',
    maintenance_schedule_note: raw.maintenance_schedule_note ?? '',
    caretaker_name: raw.caretaker_name ?? '',
    caretaker_phone: raw.caretaker_phone ?? '',
    notes: raw.notes ?? '',
    defaultDriver: dd,
    driverName,
    deleted_at: raw.deleted_at ?? null,
  }
}

function driverUiStatus(emp) {
  if (emp === 'on_leave') return 'maintenance'
  if (emp === 'terminated') return 'inactive'
  return 'active'
}

function enrichDriver(raw) {
  const lic = [raw.license_class, raw.license_expires_at].filter(Boolean).join(' — ')
  return {
    id: raw.id,
    name: raw.full_name,
    email: raw.user?.email ?? '',
    employeeCode: raw.user?.employee_code ?? '',
    license: lic || '—',
    license_class: raw.license_class ?? '',
    license_expires_at: raw.license_expires_at ?? '',
    licenseExpiry: docStateFromDate(raw.license_expires_at),
    phone: raw.phone || raw.user?.phone || '',
    employment_status: raw.employment_status,
    availability_status: raw.availability_status ?? '',
    uiStatus: driverUiStatus(raw.employment_status),
    deleted_at: raw.deleted_at ?? null,
    avatarUrl: raw.user?.avatar_url ?? null,
  }
}

function enrichProvider(raw) {
  const typeLabel = raw.type === 'taxi' ? t('resources.provider_form_type_taxi') : t('resources.provider_form_type_vendor')
  const contact = [raw.contact_name, raw.contact_phone].filter(Boolean).join(' · ') || '—'
  const servicesList = Array.isArray(raw.services) ? raw.services.filter((x) => x && String(x.name || '').trim()) : []
  const serviceSummary = servicesList.length ? servicesList.map((s) => s.name).join(' · ') : '—'
  const contract = contractStateFromDate(raw.contract_expires_at)
  return {
    id: raw.id,
    name: raw.name,
    type: raw.type,
    typeLabel,
    contact_name: raw.contact_name ?? '',
    contact_phone: raw.contact_phone ?? '',
    contact_email: raw.contact_email ?? '',
    notes: raw.notes ?? '',
    is_active: raw.is_active,
    contract_number: raw.contract_number ?? '',
    contract_signed_at: raw.contract_signed_at ?? '',
    contract_expires_at: raw.contract_expires_at ?? '',
    contract,
    servicesList,
    serviceSummary,
    contact,
    service: serviceSummary,
    uiStatus: raw.is_active ? 'active' : 'inactive',
    deleted_at: raw.deleted_at ?? null,
  }
}

async function loadAll() {
  loading.value = true
  error.value = ''
  try {
    const [vRes, dRes, pRes] = await Promise.all([
      listVehicles({ per_page: 200, only_trashed: vehiclesViewMode.value === 'trash' }),
      listDrivers({ per_page: 200, only_trashed: driversViewMode.value === 'trash' }),
      listTransportProviders({ per_page: 200, only_trashed: suppliersViewMode.value === 'trash' }),
    ])
    vehicles.value = (vRes.items || []).map(enrichVehicle)
    drivers.value = (dRes.items || []).map(enrichDriver)
    suppliers.value = (pRes.items || []).map(enrichProvider)

    if (selectedVehicle.value) {
      const id = selectedVehicle.value.id
      selectedVehicle.value = vehicles.value.find((x) => x.id === id) ?? null
    }
    if (selectedSupplier.value) {
      const sid = selectedSupplier.value.id
      selectedSupplier.value = suppliers.value.find((x) => x.id === sid) ?? null
    }
  } catch (e) {
    error.value = ''
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

function setTab(id) {
  activeTab.value = id
  selectedVehicle.value = null
  selectedSupplier.value = null
  bulkVehicleIds.value = []
  bulkDriverIds.value = []
  bulkSupplierIds.value = []
  if (id !== 'vehicles') vehiclesViewMode.value = 'active'
  if (id !== 'drivers') driversViewMode.value = 'active'
  if (id !== 'suppliers') suppliersViewMode.value = 'active'
}

function setSuppliersViewMode(mode) {
  suppliersViewMode.value = mode
  supplierListPage.value = 1
  selectedSupplier.value = null
  bulkSupplierIds.value = []
  loadAll()
}

function setDriversViewMode(mode) {
  driversViewMode.value = mode
  driverListPage.value = 1
  bulkDriverIds.value = []
  loadAll()
}

function setVehiclesViewMode(mode) {
  vehiclesViewMode.value = mode
  selectedVehicle.value = null
  bulkVehicleIds.value = []
  resourceFiltersExtraOpen.value = false
  loadAll()
}

function closePanel() {
  selectedVehicle.value = null
  selectedSupplier.value = null
}

function selectVehicle(v) {
  selectedVehicle.value = v
}

function selectSupplier(s) {
  selectedSupplier.value = s
}

function labelVehicleStatus(s) {
  const map = {
    active: t('resources.status_active'),
    maintenance: t('resources.status_maintenance'),
    inactive: t('resources.status_inactive'),
  }
  return map[s] ?? s
}

function statusBadgeClass(s) {
  if (s === 'active') {
    return 'inline-flex rounded-md bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-950/80 dark:text-sky-300'
  }
  if (s === 'maintenance') {
    return 'inline-flex rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-950/80 dark:text-amber-300'
  }
  if (s === 'inactive') {
    return 'inline-flex rounded-md bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-900 dark:bg-rose-950/80 dark:text-rose-300'
  }
  return 'inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

function statusOutlineClass(s) {
  if (s === 'active') return 'border-sky-400 text-sky-800 dark:border-sky-500/50 dark:text-sky-300'
  if (s === 'maintenance') return 'border-amber-400 text-amber-900 dark:border-amber-500/60 dark:text-amber-300'
  if (s === 'inactive') return 'border-rose-400 text-rose-900 dark:border-rose-500/50 dark:text-rose-300'
  return 'border-slate-300 text-slate-700 dark:border-slate-600 dark:text-slate-300'
}

function compliancePillClass(doc) {
  if (doc.state === 'none') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  if (doc.state === 'ok') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  }
  if (doc.state === 'soon') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  }
  return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
}

function insuranceHint(doc) {
  if (doc.state === 'none' || doc.state === 'ok') return ''
  if (doc.state === 'soon') return `${doc.days}d`
  return t('resources.exp_short')
}

function complianceDocLine(doc) {
  if (doc.state === 'none') return t('resources.contract_date_unset')
  if (!doc.until) return '—'
  if (doc.state === 'ok') return t('resources.valid_until', { date: doc.until })
  if (doc.state === 'soon') return t('resources.exp_in_days', { n: doc.days })
  return t('resources.expired_on', { date: doc.until })
}

function matchesVehicleStatus(v) {
  const f = filters.value.status
  if (!f) return true
  return v.status === f
}

function matchesDriverEmployment(d) {
  const f = filters.value.status
  if (!f) return true
  const want = f === 'active' ? 'active' : f === 'maintenance' ? 'on_leave' : 'terminated'
  return d.employment_status === want
}

function matchesSupplierActive(s) {
  const f = filters.value.status
  if (!f) return true
  if (f === 'maintenance') return false
  if (f === 'active') return s.uiStatus === 'active'
  if (f === 'inactive') return s.uiStatus === 'inactive'
  return true
}

function matchesSupplierContract(s) {
  const f = filters.value.contract
  if (!f) return true
  return s.contract.state === f
}

function matchesVehicleDriverDefault(v) {
  const f = filters.value.driver_default
  if (!f) return true
  const has = Boolean(v.driverName)
  if (f === 'assigned') return has
  if (f === 'unassigned') return !has
  return true
}

function matchesVehicleDocField(doc, f) {
  if (!f) return true
  return doc?.state === f
}

function matchesDriverLicenseFilter(d) {
  const f = filters.value.driver_license
  if (!f) return true
  return d.licenseExpiry?.state === f
}

function matchesDriverAvailabilityFilter(d) {
  const f = filters.value.driver_availability
  if (!f) return true
  return d.availability_status === f
}

function labelDriverAvailability(s) {
  if (s === 'available') return t('driver_detail.avail_available')
  if (s === 'busy') return t('driver_detail.avail_busy')
  if (s === 'offline') return t('driver_detail.avail_offline')
  return '—'
}

function driverRowInitials(name) {
  const n = String(name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/).filter(Boolean)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return n.slice(0, 2).toUpperCase()
}

function goDriverDetail(d) {
  if (!d || driversViewMode.value !== 'active') return
  router.push({ name: 'driverDetail', params: { id: d.id } })
}

const filteredVehicles = computed(() => {
  const q = search.value.trim().toLowerCase()
  return vehicles.value.filter((v) => {
    if (!matchesVehicleStatus(v)) return false
    if (filters.value.type && v.iconKind !== filters.value.type) return false
    if (!matchesVehicleDriverDefault(v)) return false
    if (!matchesVehicleDocField(v.insurance, filters.value.insurance)) return false
    if (!matchesVehicleDocField(v.inspection, filters.value.inspection)) return false
    if (!matchesVehicleDocField(v.road_fee, filters.value.road_fee)) return false
    if (!q) return true
    const hay = `${v.code} ${v.model} ${v.driverName ?? ''} ${v.owner_name ?? ''} ${v.caretaker_name ?? ''} ${v.notes ?? ''} ${v.caretaker_phone ?? ''} ${v.insurance_provider ?? ''} ${v.year_manufactured ?? ''} ${v.frame_engine_number ?? ''}`
      .toLowerCase()
    return hay.includes(q)
  })
})

const vehicleTotalFiltered = computed(() => filteredVehicles.value.length)
const vehicleTotalPages = computed(() => Math.max(1, Math.ceil(vehicleTotalFiltered.value / vehiclePerPage.value)))

const paginatedVehicles = computed(() => {
  const list = filteredVehicles.value
  const per = vehiclePerPage.value
  const tp = vehicleTotalPages.value
  const page = Math.min(Math.max(1, vehicleListPage.value), tp)
  const start = (page - 1) * per
  return list.slice(start, start + per)
})

const vehicleRangeFrom = computed(() => {
  if (!vehicleTotalFiltered.value) return 0
  return (vehicleListPage.value - 1) * vehiclePerPage.value + 1
})
const vehicleRangeTo = computed(() => Math.min(vehicleTotalFiltered.value, vehicleListPage.value * vehiclePerPage.value))

watch(vehicleTotalPages, (tp) => {
  if (vehicleListPage.value > tp) vehicleListPage.value = tp
})

watch(vehiclePerPage, () => {
  vehicleListPage.value = 1
})

watch(
  () => [search.value, filters.value, vehiclesViewMode.value],
  () => {
    vehicleListPage.value = 1
  },
  { deep: true },
)

const filteredDrivers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return drivers.value.filter((d) => {
    if (!matchesDriverEmployment(d)) return false
    if (!matchesDriverLicenseFilter(d)) return false
    if (!matchesDriverAvailabilityFilter(d)) return false
    if (!q) return true
    return `${d.name} ${d.email} ${d.license} ${d.phone} ${d.employeeCode} ${d.license_class}`.toLowerCase().includes(q)
  })
})

const driverTotalFiltered = computed(() => filteredDrivers.value.length)
const driverTotalPages = computed(() => Math.max(1, Math.ceil(driverTotalFiltered.value / driverPerPage.value)))

const paginatedDrivers = computed(() => {
  const list = filteredDrivers.value
  const per = driverPerPage.value
  const tp = driverTotalPages.value
  const page = Math.min(Math.max(1, driverListPage.value), tp)
  const start = (page - 1) * per
  return list.slice(start, start + per)
})

const driverRangeFrom = computed(() => {
  if (!driverTotalFiltered.value) return 0
  return (driverListPage.value - 1) * driverPerPage.value + 1
})
const driverRangeTo = computed(() => Math.min(driverTotalFiltered.value, driverListPage.value * driverPerPage.value))

watch(driverTotalPages, (tp) => {
  if (driverListPage.value > tp) driverListPage.value = tp
})

watch(driverPerPage, () => {
  driverListPage.value = 1
})

watch(
  () => [search.value, filters.value, driversViewMode.value],
  () => {
    driverListPage.value = 1
  },
  { deep: true },
)

const filteredSuppliers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return suppliers.value.filter((s) => {
    if (!matchesSupplierActive(s)) return false
    if (!matchesSupplierContract(s)) return false
    if (!q) return true
    const svcHay = (s.servicesList || []).map((x) => `${x.name} ${x.note || ''}`).join(' ')
    const hay = `${s.name} ${s.contact} ${s.service} ${s.contract_number || ''} ${s.contact_email || ''} ${svcHay}`.toLowerCase()
    return hay.includes(q)
  })
})

const supplierTotalFiltered = computed(() => filteredSuppliers.value.length)
const supplierTotalPages = computed(() => Math.max(1, Math.ceil(supplierTotalFiltered.value / supplierPerPage.value)))

const paginatedSuppliers = computed(() => {
  const list = filteredSuppliers.value
  const per = supplierPerPage.value
  const tp = supplierTotalPages.value
  const page = Math.min(Math.max(1, supplierListPage.value), tp)
  const start = (page - 1) * per
  return list.slice(start, start + per)
})

const vehiclePageIdList = computed(() => paginatedVehicles.value.map((v) => v.id))
const vehiclePageAllSelected = computed(() => {
  const ids = vehiclePageIdList.value
  if (!ids.length) return false
  return ids.every((id) => bulkVehicleIds.value.includes(id))
})

const driverPageIdList = computed(() => paginatedDrivers.value.map((d) => d.id))
const driverPageAllSelected = computed(() => {
  const ids = driverPageIdList.value
  if (!ids.length) return false
  return ids.every((id) => bulkDriverIds.value.includes(id))
})

const supplierPageIdList = computed(() => paginatedSuppliers.value.map((s) => s.id))
const supplierPageAllSelected = computed(() => {
  const ids = supplierPageIdList.value
  if (!ids.length) return false
  return ids.every((id) => bulkSupplierIds.value.includes(id))
})

const supplierRangeFrom = computed(() => {
  if (!supplierTotalFiltered.value) return 0
  return (supplierListPage.value - 1) * supplierPerPage.value + 1
})
const supplierRangeTo = computed(() => Math.min(supplierTotalFiltered.value, supplierListPage.value * supplierPerPage.value))

watch(supplierTotalPages, (tp) => {
  if (supplierListPage.value > tp) supplierListPage.value = tp
})

watch(supplierPerPage, () => {
  supplierListPage.value = 1
})

watch(
  () => [search.value, filters.value, suppliersViewMode.value],
  () => {
    supplierListPage.value = 1
  },
  { deep: true },
)

watch(filteredVehicles, (list) => {
  if (!selectedVehicle.value) return
  if (!list.some((x) => x.id === selectedVehicle.value.id)) selectedVehicle.value = null
})

watch(filteredSuppliers, (list) => {
  if (!selectedSupplier.value) return
  if (!list.some((x) => x.id === selectedSupplier.value.id)) selectedSupplier.value = null
})

watch(activeTab, () => {
  search.value = ''
  resourceFiltersExtraOpen.value = false
  filters.value = {
    status: '',
    type: '',
    driver_default: '',
    insurance: '',
    inspection: '',
    road_fee: '',
    contract: '',
    driver_license: '',
    driver_availability: '',
  }
})

watch(
  () => [selectedVehicle.value?.id, canViewVehicleComplianceDocs.value, vehiclesViewMode.value],
  async ([id, can, mode]) => {
    vehicleComplianceDocs.value = []
    if (!id || !can || mode !== 'active') return
    try {
      const res = await listVehicleComplianceDocuments(id)
      vehicleComplianceDocs.value = res.items || []
    } catch {
      vehicleComplianceDocs.value = []
    }
  },
  { immediate: true },
)

function isPdfAttachment(a) {
  const mime = String(a?.mime_type || '').toLowerCase()
  if (mime.includes('pdf')) return true
  if (mime === 'application/octet-stream' || mime === 'binary/octet-stream') {
    const name = String(a?.original_name || '').toLowerCase()
    const path = String(a?.url || '').split('?')[0].toLowerCase()
    if (name.endsWith('.pdf') || path.endsWith('.pdf')) return true
  }
  const name = String(a?.original_name || '').toLowerCase()
  if (name.endsWith('.pdf')) return true
  try {
    const path = String(a?.url || '').split('?')[0].toLowerCase()
    return path.endsWith('.pdf')
  } catch {
    return false
  }
}

function resolveAttachmentAbsoluteUrl(a) {
  const u = a?.url
  if (!u) return ''
  if (/^https?:\/\//i.test(u)) return u
  const path = u.startsWith('/') ? u : `/${u}`
  return `${window.location.origin}${path}`
}

/** Mở PDF bằng trình xem mặc định của trình duyệt (tab mới) — không iframe/blob, tránh Vue Router. */
function openPdfPreview(a) {
  const resolved = resolveAttachmentAbsoluteUrl(a)
  if (!resolved) return
  const w = window.open(resolved, '_blank', 'noopener,noreferrer')
  if (!w) window.location.assign(resolved)
}

function vehicleDocTypeLabel(type) {
  const k = `vehicle_compliance_doc_type.${type}`
  return t(k) !== k ? t(k) : type
}

function vehicleDocExpiryLabel(exp) {
  if (!exp || exp.state === 'none') return t('driver_detail.expiry_none')
  if (exp.state === 'ok') return t('resources.compliance_ok')
  if (exp.state === 'soon') return t('resources.exp_in_days', { n: exp.days })
  return t('resources.compliance_exp')
}

function emptyVehicleDocForm() {
  vehicleDocForm.value = {
    doc_type: 'registration',
    title: '',
    notes: '',
    issued_at: '',
    expires_at: '',
    replace_file: false,
  }
  vehicleDocFile.value = null
}

function openVehicleDocModal(doc) {
  vehicleDocFormError.value = ''
  if (doc) {
    editingVehicleDocId.value = doc.id
    vehicleDocForm.value = {
      doc_type: doc.doc_type,
      title: doc.title || '',
      notes: doc.notes || '',
      issued_at: doc.issued_at || '',
      expires_at: doc.expires_at || '',
      replace_file: false,
    }
    vehicleDocFile.value = null
  } else {
    editingVehicleDocId.value = null
    emptyVehicleDocForm()
  }
  vehicleDocModalOpen.value = true
}

function onVehicleDocFile(e) {
  vehicleDocFile.value = e.target.files?.[0] || null
}

async function submitVehicleDocForm() {
  if (!selectedVehicle.value) return
  vehicleDocSaving.value = true
  vehicleDocFormError.value = ''
  const vid = selectedVehicle.value.id
  try {
    const fd = new FormData()
    fd.append('doc_type', vehicleDocForm.value.doc_type)
    if (vehicleDocForm.value.title) fd.append('title', vehicleDocForm.value.title)
    if (vehicleDocForm.value.notes) fd.append('notes', vehicleDocForm.value.notes)
    if (vehicleDocForm.value.issued_at) fd.append('issued_at', vehicleDocForm.value.issued_at)
    if (vehicleDocForm.value.expires_at) fd.append('expires_at', vehicleDocForm.value.expires_at)
    if (vehicleDocFile.value) fd.append('file', vehicleDocFile.value)
    if (editingVehicleDocId.value) {
      fd.append('replace_file', vehicleDocForm.value.replace_file ? '1' : '0')
      await updateVehicleComplianceDocument(vid, editingVehicleDocId.value, fd)
    } else {
      await createVehicleComplianceDocument(vid, fd)
    }
    vehicleDocModalOpen.value = false
    const res = await listVehicleComplianceDocuments(vid)
    vehicleComplianceDocs.value = res.items || []
  } catch (e) {
    const st = e?.response?.status
    if (st === 422) {
      const msg = e?.response?.data?.message
      const errs = e?.response?.data?.errors
      vehicleDocFormError.value =
        (typeof msg === 'string' && msg) ||
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        t('resources.load_error')
    } else {
      showAppErrorFromApi(e, t('resources.load_error'))
    }
  } finally {
    vehicleDocSaving.value = false
  }
}

async function confirmDeleteVehicleDoc(doc) {
  if (!selectedVehicle.value || !canManageVehicles.value) return
  if (!window.confirm(t('resources.vehicle_doc_confirm_delete'))) return
  try {
    await deleteVehicleComplianceDocument(selectedVehicle.value.id, doc.id)
    const res = await listVehicleComplianceDocuments(selectedVehicle.value.id)
    vehicleComplianceDocs.value = res.items || []
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  }
}

function labelProviderStatus(s) {
  if (s === 'active') return t('resources.provider_active')
  return t('resources.provider_inactive')
}

function addProviderServiceRow() {
  providerForm.value.services.push(emptyProviderServiceRow())
}

function removeProviderServiceRow(idx) {
  if (providerForm.value.services.length <= 1) return
  providerForm.value.services.splice(idx, 1)
}

function openProviderForm(s) {
  providerFormError.value = ''
  if (s) {
    const rows = (s.servicesList && s.servicesList.length ? s.servicesList : [emptyProviderServiceRow()]).map((r, i) => ({
      _key: `svc-edit-${i}-${r.kind}-${String(r.name || '').slice(0, 32)}`,
      kind: r.kind === 'service' ? 'service' : 'solution',
      name: r.name || '',
      note: r.note || '',
    }))
    providerForm.value = {
      id: s.id,
      name: s.name,
      type: s.type === 'taxi' ? 'taxi' : 'vendor',
      contact_name: s.contact_name || '',
      contact_phone: s.contact_phone || '',
      contact_email: s.contact_email || '',
      notes: s.notes || '',
      is_active: !!s.is_active,
      contract_number: s.contract_number || '',
      contract_signed_at: s.contract_signed_at || '',
      contract_expires_at: s.contract_expires_at || '',
      services: rows,
    }
  } else {
    providerForm.value = emptyProviderForm()
  }
  providerModalOpen.value = true
}

function buildProviderPayload() {
  const f = providerForm.value
  const services = (f.services || [])
    .map((r) => ({
      kind: r.kind === 'service' ? 'service' : 'solution',
      name: String(r.name || '').trim(),
      note: String(r.note || '').trim() || null,
    }))
    .filter((r) => r.name.length > 0)
  return {
    name: f.name.trim(),
    type: f.type,
    contact_name: f.contact_name?.trim() || null,
    contact_phone: f.contact_phone?.trim() || null,
    contact_email: f.contact_email?.trim() || null,
    notes: f.notes?.trim() || null,
    is_active: !!f.is_active,
    contract_number: f.contract_number?.trim() || null,
    contract_signed_at: f.contract_signed_at || null,
    contract_expires_at: f.contract_expires_at || null,
    services,
  }
}

async function submitProviderForm() {
  providerSaving.value = true
  providerFormError.value = ''
  try {
    const payload = buildProviderPayload()
    const f = providerForm.value
    if (f.id) {
      await updateTransportProvider(f.id, payload)
    } else {
      await createTransportProvider(payload)
    }
    providerModalOpen.value = false
    await loadAll()
  } catch (e) {
    const st = e?.response?.status
    if (st === 422) {
      const msg = e?.response?.data?.message
      const errs = e?.response?.data?.errors
      providerFormError.value =
        (typeof msg === 'string' && msg) ||
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        t('resources.load_error')
    } else {
      showAppErrorFromApi(e, t('resources.load_error'))
    }
  } finally {
    providerSaving.value = false
  }
}

function driverAssignOptionLabel(d) {
  const sub = d.employeeCode || d.email
  return sub ? `${d.name} — ${sub}` : d.name
}

function setAddDriverMode(mode) {
  addDriverMode.value = mode
  assignError.value = ''
}

function openAssignModal(vehicleId) {
  assignVehicleId.value = vehicleId
  assignError.value = ''
  addDriverMode.value = 'user'
  externalDriverForm.value = emptyExternalDriverForm()
  userSearchQuery.value = ''
  userSearchResults.value = []
  pickedUser.value = null
  if (vehicleId != null) {
    const v = vehicles.value.find((x) => x.id === vehicleId) ?? selectedVehicle.value
    pickedDriverId.value = v?.defaultDriver?.id ?? null
  } else {
    pickedDriverId.value = null
  }
  assignModalOpen.value = true
}

function closeAssignModal() {
  assignModalOpen.value = false
  assignVehicleId.value = null
  pickedDriverId.value = null
  assignSubmitting.value = false
  addDriverMode.value = 'user'
  externalDriverForm.value = emptyExternalDriverForm()
}

function scheduleUserSearch() {
  clearTimeout(userSearchTimer)
  userSearchTimer = setTimeout(runUserSearch, 350)
}

async function runUserSearch() {
  const q = userSearchQuery.value.trim()
  if (q.length < 2) {
    userSearchResults.value = []
    return
  }
  userSearchLoading.value = true
  assignError.value = ''
  try {
    userSearchResults.value = await searchUsersForDriverAssignment(q)
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
    userSearchResults.value = []
  } finally {
    userSearchLoading.value = false
  }
}

function buildExternalDriverPayload() {
  const f = externalDriverForm.value
  const trim = (v) => {
    const s = String(v ?? '').trim()
    return s || null
  }
  return {
    full_name: String(f.full_name || '').trim(),
    phone: trim(f.phone),
    national_id: trim(f.national_id),
    license_class: trim(f.license_class),
    license_expires_at: trim(f.license_expires_at),
    employment_status: f.employment_status || 'active',
    availability_status: f.availability_status || 'available',
  }
}

async function submitAssignDriver() {
  if (assignVehicleId.value != null) {
    const vid = assignVehicleId.value
    const did = pickedDriverId.value
    if (did == null) return
    assignSubmitting.value = true
    assignError.value = ''
    try {
      await updateVehicle(vid, { default_driver_id: did })
      await loadAll()
      closeAssignModal()
    } catch (e) {
      showAppErrorFromApi(e, t('resources.load_error'))
    } finally {
      assignSubmitting.value = false
    }
    return
  }
  if (addDriverMode.value === 'external') {
    const payload = buildExternalDriverPayload()
    if (!payload.full_name) return
    assignSubmitting.value = true
    assignError.value = ''
    try {
      await createDriver(payload)
      await loadAll()
      closeAssignModal()
    } catch (e) {
      const st = e?.response?.status
      if (st === 422) {
        const msg = e?.response?.data?.message
        const errs = e?.response?.data?.errors
        assignError.value =
          (typeof msg === 'string' && msg) ||
          (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
          t('resources.load_error')
      } else {
        showAppErrorFromApi(e, t('resources.load_error'))
      }
    } finally {
      assignSubmitting.value = false
    }
    return
  }
  if (!pickedUser.value) return
  assignSubmitting.value = true
  assignError.value = ''
  try {
    await createDriverFromUser(pickedUser.value.id)
    await loadAll()
    closeAssignModal()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    assignSubmitting.value = false
  }
}

watch(assignModalOpen, (open) => {
  if (!open) {
    userSearchQuery.value = ''
    userSearchResults.value = []
    pickedUser.value = null
    pickedDriverId.value = null
    addDriverMode.value = 'user'
    externalDriverForm.value = emptyExternalDriverForm()
  }
})

watch(vehicleModalOpen, (open) => {
  if (!open) {
    vehicleFormAuxFile.value = null
    if (vehicleFormAuxInputRef.value) vehicleFormAuxInputRef.value.value = ''
  }
})

function openVehicleForm(v) {
  vehicleFormError.value = ''
  vehicleFormAuxFile.value = null
  if (vehicleFormAuxInputRef.value) vehicleFormAuxInputRef.value.value = ''
  if (v) {
    vehicleForm.value = {
      id: v.id,
      license_plate: v.license_plate,
      owner_name: v.owner_name || '',
      frame_engine_number: v.frame_engine_number || '',
      type: v.type || '',
      year_manufactured: v.year_manufactured ?? '',
      purchased_at: v.purchased_at || '',
      usage_expires_year: v.usage_expires_year ?? '',
      seat_count: v.seat_count ?? '',
      payload_kg: v.payload_kg ?? '',
      insurance_provider: v.insurance_provider || '',
      insurance_policy_note: v.insurance_policy_note || '',
      status: v.apiStatus,
      odometer_km: v.odometer_km ?? 0,
      inspection_expires_at: v.inspection_expires_at || '',
      insurance_expires_at: v.insurance_expires_at || '',
      road_fee_expires_at: v.road_fee_expires_at || '',
      registration_cycle_note: v.registration_cycle_note || '',
      last_maintenance_at: v.last_maintenance_at || '',
      maintenance_schedule_note: v.maintenance_schedule_note || '',
      caretaker_name: v.caretaker_name || '',
      caretaker_phone: v.caretaker_phone || '',
      notes: v.notes || '',
    }
  } else {
    vehicleForm.value = emptyVehicleForm()
  }
  vehicleModalOpen.value = true
}

function numOrNull(v) {
  if (v === '' || v === null || v === undefined) return null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
}

function intOrNull(v) {
  if (v === '' || v === null || v === undefined) return null
  const n = parseInt(String(v), 10)
  return Number.isFinite(n) ? n : null
}

function strOrNull(v) {
  const s = String(v ?? '').trim()
  return s ? s : null
}

function vehicleSheetDetailVisible(v) {
  if (!v) return false
  return Boolean(
    v.owner_name ||
      v.frame_engine_number ||
      v.year_manufactured ||
      v.purchased_at ||
      v.usage_expires_year ||
      v.insurance_provider ||
      v.insurance_policy_note ||
      v.road_fee_expires_at ||
      v.registration_cycle_note ||
      v.last_maintenance_at ||
      v.maintenance_schedule_note ||
      v.caretaker_name ||
      v.caretaker_phone ||
      v.notes,
  )
}

async function confirmDeactivateVehicle() {
  if (!selectedVehicle.value || !canManageVehicles.value) return
  if (selectedVehicle.value.apiStatus === 'broken') return
  const plate = selectedVehicle.value.code
  if (!window.confirm(t('resources.vehicle_deactivate_confirm', { plate }))) return
  vehicleDeactivating.value = true
  try {
    await updateVehicle(selectedVehicle.value.id, { status: 'broken' })
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    vehicleDeactivating.value = false
  }
}

function openDeleteVehicleModal() {
  if (!selectedVehicle.value || !canManageVehicles.value) return
  vehicleDeleteTarget.value = selectedVehicle.value
  vehicleDeleteModalOpen.value = true
}

async function confirmMoveVehicleToTrash() {
  if (!vehicleDeleteTarget.value || !canManageVehicles.value) return
  vehicleDeleting.value = true
  try {
    await deleteVehicle(vehicleDeleteTarget.value.id)
    vehicleDeleteModalOpen.value = false
    vehicleDeleteTarget.value = null
    selectedVehicle.value = null
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    vehicleDeleting.value = false
  }
}

async function submitRestoreVehicle() {
  if (!selectedVehicle.value || !canManageVehicles.value) return
  await submitRestoreVehicleById(selectedVehicle.value.id)
}

async function submitRestoreVehicleById(id) {
  if (!canManageVehicles.value) return
  vehicleRestoring.value = true
  try {
    await restoreVehicleRequest(id)
    if (selectedVehicle.value?.id === id) selectedVehicle.value = null
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    vehicleRestoring.value = false
  }
}

function openDriverDeleteModal(d) {
  if (!d || !canManageDrivers.value) return
  driverDeleteTarget.value = d
  driverDeleteModalOpen.value = true
}

async function confirmMoveDriverToTrash() {
  if (!driverDeleteTarget.value || !canManageDrivers.value) return
  driverDeleting.value = true
  try {
    await deleteDriver(driverDeleteTarget.value.id)
    driverDeleteModalOpen.value = false
    driverDeleteTarget.value = null
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    driverDeleting.value = false
  }
}

async function submitRestoreDriverById(id) {
  if (!canManageDrivers.value) return
  driverRestoring.value = true
  try {
    await restoreDriverRequest(id)
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    driverRestoring.value = false
  }
}

function openForceDeleteModal(kind, id, name) {
  forceDeleteContext.value = { kind, id, name: name || String(id) }
  forceDeleteModalOpen.value = true
}

function toggleBulkVehicle(id) {
  const i = bulkVehicleIds.value.indexOf(id)
  if (i === -1) bulkVehicleIds.value = [...bulkVehicleIds.value, id]
  else bulkVehicleIds.value = bulkVehicleIds.value.filter((x) => x !== id)
}

function toggleVehiclePageSelectAll() {
  const ids = vehiclePageIdList.value
  if (!ids.length) return
  if (vehiclePageAllSelected.value) {
    bulkVehicleIds.value = bulkVehicleIds.value.filter((id) => !ids.includes(id))
  } else {
    bulkVehicleIds.value = [...new Set([...bulkVehicleIds.value, ...ids])]
  }
}

function toggleBulkDriver(id) {
  const i = bulkDriverIds.value.indexOf(id)
  if (i === -1) bulkDriverIds.value = [...bulkDriverIds.value, id]
  else bulkDriverIds.value = bulkDriverIds.value.filter((x) => x !== id)
}

function toggleDriverPageSelectAll() {
  const ids = driverPageIdList.value
  if (!ids.length) return
  if (driverPageAllSelected.value) {
    bulkDriverIds.value = bulkDriverIds.value.filter((id) => !ids.includes(id))
  } else {
    bulkDriverIds.value = [...new Set([...bulkDriverIds.value, ...ids])]
  }
}

function toggleBulkSupplier(id) {
  const i = bulkSupplierIds.value.indexOf(id)
  if (i === -1) bulkSupplierIds.value = [...bulkSupplierIds.value, id]
  else bulkSupplierIds.value = bulkSupplierIds.value.filter((x) => x !== id)
}

function toggleSupplierPageSelectAll() {
  const ids = supplierPageIdList.value
  if (!ids.length) return
  if (supplierPageAllSelected.value) {
    bulkSupplierIds.value = bulkSupplierIds.value.filter((id) => !ids.includes(id))
  } else {
    bulkSupplierIds.value = [...new Set([...bulkSupplierIds.value, ...ids])]
  }
}

function openBulkTrashModal(kind) {
  bulkTrashKind.value = kind
  if (kind === 'vehicle') bulkTrashPendingIds.value = [...bulkVehicleIds.value]
  else if (kind === 'driver') bulkTrashPendingIds.value = [...bulkDriverIds.value]
  else if (kind === 'supplier') bulkTrashPendingIds.value = [...bulkSupplierIds.value]
  else bulkTrashPendingIds.value = []
  if (!bulkTrashPendingIds.value.length) return
  bulkTrashModalOpen.value = true
}

async function confirmBulkTrash() {
  const ids = [...bulkTrashPendingIds.value]
  const kind = bulkTrashKind.value
  if (!ids.length || !kind) return
  bulkTrashSubmitting.value = true
  try {
    let n = 0
    if (kind === 'vehicle') {
      const r = await bulkDeleteVehicles(ids)
      n = r?.deleted_count ?? ids.length
      bulkVehicleIds.value = []
    } else if (kind === 'driver') {
      const r = await bulkDeleteDrivers(ids)
      n = r?.deleted_count ?? ids.length
      bulkDriverIds.value = []
    } else if (kind === 'supplier') {
      const r = await bulkDeleteTransportProviders(ids)
      n = r?.deleted_count ?? ids.length
      bulkSupplierIds.value = []
    }
    bulkTrashModalOpen.value = false
    showAppSuccess(t('resources.bulk_move_to_trash_done', { n: String(n) }))
    selectedVehicle.value = null
    selectedSupplier.value = null
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    bulkTrashSubmitting.value = false
  }
}

function openBulkForceModal(kind) {
  bulkForceKind.value = kind
  if (kind === 'vehicle') bulkForcePendingIds.value = [...bulkVehicleIds.value]
  else if (kind === 'driver') bulkForcePendingIds.value = [...bulkDriverIds.value]
  else if (kind === 'supplier') bulkForcePendingIds.value = [...bulkSupplierIds.value]
  else bulkForcePendingIds.value = []
  if (!bulkForcePendingIds.value.length) return
  bulkForceModalOpen.value = true
}

async function confirmBulkForceDelete() {
  const ids = [...bulkForcePendingIds.value]
  const kind = bulkForceKind.value
  if (!ids.length || !kind) return
  bulkForceSubmitting.value = true
  try {
    let n = 0
    if (kind === 'vehicle') {
      const r = await bulkForceDeleteVehicles(ids)
      n = r?.deleted_count ?? ids.length
      bulkVehicleIds.value = []
    } else if (kind === 'driver') {
      const r = await bulkForceDeleteDrivers(ids)
      n = r?.deleted_count ?? ids.length
      bulkDriverIds.value = []
    } else if (kind === 'supplier') {
      const r = await bulkForceDeleteTransportProviders(ids)
      n = r?.deleted_count ?? ids.length
      bulkSupplierIds.value = []
    }
    bulkForceModalOpen.value = false
    showAppSuccess(t('resources.bulk_force_resources_done', { n: String(n) }))
    selectedVehicle.value = null
    selectedSupplier.value = null
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    bulkForceSubmitting.value = false
  }
}

async function confirmForceDeletePerm() {
  const ctx = forceDeleteContext.value
  if (!ctx?.id || !ctx.kind) return
  forcePermDeleting.value = true
  try {
    if (ctx.kind === 'vehicle' && canManageVehicles.value) {
      await forceDeleteVehicleRequest(ctx.id)
      if (selectedVehicle.value?.id === ctx.id) selectedVehicle.value = null
    } else if (ctx.kind === 'driver' && canManageDrivers.value) {
      await forceDeleteDriverRequest(ctx.id)
    } else if (ctx.kind === 'supplier' && canManageProviders.value) {
      await forceDeleteTransportProviderRequest(ctx.id)
      if (selectedSupplier.value?.id === ctx.id) selectedSupplier.value = null
    }
    forceDeleteModalOpen.value = false
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    forcePermDeleting.value = false
  }
}

function openProviderDeleteModal(s) {
  if (!s || !canManageProviders.value) return
  providerDeleteTarget.value = s
  providerDeleteModalOpen.value = true
}

async function confirmMoveProviderToTrash() {
  if (!providerDeleteTarget.value || !canManageProviders.value) return
  const sid = providerDeleteTarget.value.id
  providerDeleting.value = true
  try {
    await deleteTransportProvider(sid)
    providerDeleteModalOpen.value = false
    providerDeleteTarget.value = null
    if (selectedSupplier.value?.id === sid) selectedSupplier.value = null
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    providerDeleting.value = false
  }
}

async function submitRestoreSupplierById(id) {
  if (!canManageProviders.value) return
  supplierRestoring.value = true
  try {
    await restoreTransportProviderRequest(id)
    await loadAll()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    supplierRestoring.value = false
  }
}

async function submitVehicleForm() {
  vehicleSaving.value = true
  vehicleFormError.value = ''
  try {
    const f = vehicleForm.value
    const payload = {
      license_plate: f.license_plate.trim(),
      owner_name: strOrNull(f.owner_name),
      frame_engine_number: strOrNull(f.frame_engine_number),
      type: f.type || null,
      year_manufactured: intOrNull(f.year_manufactured),
      purchased_at: f.purchased_at || null,
      usage_expires_year: intOrNull(f.usage_expires_year),
      seat_count: numOrNull(f.seat_count),
      payload_kg: numOrNull(f.payload_kg),
      insurance_provider: strOrNull(f.insurance_provider),
      insurance_policy_note: strOrNull(f.insurance_policy_note),
      status: f.status,
      odometer_km: Number(f.odometer_km) || 0,
      inspection_expires_at: f.inspection_expires_at || null,
      insurance_expires_at: f.insurance_expires_at || null,
      road_fee_expires_at: f.road_fee_expires_at || null,
      registration_cycle_note: strOrNull(f.registration_cycle_note),
      last_maintenance_at: f.last_maintenance_at || null,
      maintenance_schedule_note: strOrNull(f.maintenance_schedule_note),
      caretaker_name: strOrNull(f.caretaker_name),
      caretaker_phone: strOrNull(f.caretaker_phone),
      notes: strOrNull(f.notes),
    }
    let saved
    if (f.id) {
      saved = await updateVehicle(f.id, payload)
    } else {
      saved = await createVehicle(payload)
    }
    const vehicleId = saved?.id ?? f.id
    if (vehicleFormAuxFile.value && vehicleId) {
      try {
        const fd = new FormData()
        fd.append('doc_type', 'other')
        fd.append('title', t('resources.vehicle_form_attachment_doc_title'))
        fd.append('file', vehicleFormAuxFile.value)
        await createVehicleComplianceDocument(vehicleId, fd)
      } catch (docErr) {
        showAppErrorFromApi(docErr, t('resources.vehicle_form_attachment_upload_failed'))
      }
      vehicleFormAuxFile.value = null
      if (vehicleFormAuxInputRef.value) vehicleFormAuxInputRef.value.value = ''
    }
    vehicleModalOpen.value = false
    await loadAll()
  } catch (e) {
    const st = e?.response?.status
    if (st === 422) {
      const msg = e?.response?.data?.message
      const errs = e?.response?.data?.errors
      vehicleFormError.value =
        (typeof msg === 'string' && msg) ||
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        t('resources.load_error')
    } else {
      showAppErrorFromApi(e, t('resources.load_error'))
    }
  } finally {
    vehicleSaving.value = false
  }
}
</script>
