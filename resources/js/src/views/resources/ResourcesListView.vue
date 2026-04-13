<template>
  <div
    class="resources-shell rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
  >
    <!-- Header -->
    <div class="border-b border-slate-200 px-4 py-4 sm:px-5 dark:border-slate-700">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ t('resources.page_title') }}</h1>
          <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ t('resources.page_subtitle') }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-600 dark:bg-slate-800">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              :class="[
                'rounded-md px-3 py-1.5 text-xs font-medium transition',
                activeTab === tab.id
                  ? 'bg-teal-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
              @click="setTab(tab.id)"
            >
              {{ t(tab.labelKey) }}
            </button>
          </div>
          <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
            <input
              v-model="search"
              type="search"
              class="w-full min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 sm:min-w-[200px] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
              :placeholder="searchPlaceholder"
            />
            <button
              v-if="activeTab === 'drivers'"
              type="button"
              class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500"
              @click="openAssignModal(null)"
            >
              {{ t('resources.assign_driver') }}
            </button>
            <button
              v-else
              type="button"
              class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
              disabled
            >
              {{ addButtonLabel }}
            </button>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_status') }}
          <select
            v-model="filters.status"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">{{ t('resources.filter_all') }}</option>
            <option value="active">{{ t('resources.status_active') }}</option>
            <option value="maintenance">{{ t('resources.status_maintenance') }}</option>
            <option value="inactive">{{ t('resources.status_inactive') }}</option>
          </select>
        </label>
        <label v-if="activeTab === 'vehicles'" class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_type') }}
          <select
            v-model="filters.type"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">{{ t('resources.filter_all') }}</option>
            <option value="van">{{ t('resources.type_van') }}</option>
            <option value="truck">{{ t('resources.type_truck') }}</option>
            <option value="bus">{{ t('resources.type_bus') }}</option>
          </select>
        </label>
        <label v-if="activeTab === 'vehicles'" class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_compliance') }}
          <select
            v-model="filters.compliance"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">{{ t('resources.filter_all') }}</option>
            <option value="ok">{{ t('resources.compliance_ok') }}</option>
            <option value="soon">{{ t('resources.compliance_soon') }}</option>
            <option value="exp">{{ t('resources.compliance_exp') }}</option>
          </select>
        </label>
      </div>
    </div>

    <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">{{ t('resources.loading') }}</div>
    <div v-else-if="error" class="px-4 py-12 text-center text-sm text-rose-600">{{ error }}</div>

    <div v-else class="relative flex min-h-[420px]">
      <!-- Table -->
      <div class="min-w-0 flex-1 overflow-x-auto p-3 sm:p-4">
        <div
          v-if="activeTab === 'vehicles'"
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <table class="w-full min-w-[780px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                <th class="border-b border-slate-200 px-4 py-3 first:rounded-tl-xl dark:border-slate-700">{{ t('resources.col_vehicle') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_type_capacity') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_status') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_compliance') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_driver') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 text-right last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="v in filteredVehicles"
                :key="v.id"
                class="cursor-pointer transition hover:bg-teal-50/40 dark:hover:bg-slate-800/60"
                :class="selectedVehicle?.id === v.id ? 'bg-teal-50/80 dark:bg-slate-800/80' : ''"
                @click="selectVehicle(v)"
              >
                <td class="px-4 py-3 align-middle">
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400">
                      <TruckIcon class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div>
                      <div class="font-semibold text-slate-900 dark:text-white">{{ v.code }}</div>
                      <div class="text-xs text-slate-500 dark:text-slate-400">{{ v.model }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 align-middle text-slate-700 dark:text-slate-300">{{ v.typeLabel }}</td>
                <td class="px-4 py-3 align-middle">
                  <span :class="statusBadgeClass(v.status)">{{ labelVehicleStatus(v.status) }}</span>
                </td>
                <td class="px-4 py-3 align-middle">
                  <div class="flex flex-wrap gap-1">
                    <span :class="compliancePillClass(v.insurance)">{{ t('resources.tag_ins') }} {{ insuranceHint(v.insurance) }}</span>
                    <span :class="compliancePillClass(v.inspection)">{{ t('resources.tag_reg') }} {{ insuranceHint(v.inspection) }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 align-middle">
                  <span v-if="v.driverName" class="text-slate-800 dark:text-slate-200">{{ v.driverName }}</span>
                  <span v-else class="italic text-slate-500">{{ t('resources.unassigned') }}</span>
                </td>
                <td class="px-4 py-3 align-middle text-right text-slate-400">
                  <ChevronRightIcon class="ml-auto inline h-5 w-5" aria-hidden="true" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else-if="activeTab === 'drivers'"
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <table class="w-full min-w-[880px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                <th class="border-b border-slate-200 px-4 py-3 first:rounded-tl-xl dark:border-slate-700">{{ t('resources.col_driver_name') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_user_email') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_employee_code') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_license') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_phone') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="d in filteredDrivers" :key="d.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ d.name }}</td>
                <td class="max-w-[200px] truncate px-4 py-3 text-slate-600 dark:text-slate-400">{{ d.email || '—' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-700 dark:text-slate-300">{{ d.employeeCode || '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ d.license }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ d.phone || '—' }}</td>
                <td class="px-4 py-3">
                  <span :class="statusBadgeClass(d.uiStatus)">{{ labelVehicleStatus(d.uiStatus) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <table class="w-full min-w-[720px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                <th class="border-b border-slate-200 px-4 py-3 first:rounded-tl-xl dark:border-slate-700">{{ t('resources.col_supplier') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_contact') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_service') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="s in filteredSuppliers" :key="s.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ s.name }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ s.contact }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ s.service }}</td>
                <td class="px-4 py-3">
                  <span :class="statusBadgeClass(s.uiStatus)">{{ labelVehicleStatus(s.uiStatus) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-if="activeTab === 'vehicles' && !filteredVehicles.length" class="py-8 text-center text-sm text-slate-500">
          {{ t('resources.empty') }}
        </p>
        <p v-if="activeTab === 'drivers' && !filteredDrivers.length" class="py-8 text-center text-sm text-slate-500">
          {{ t('resources.empty') }}
        </p>
        <p v-if="activeTab === 'suppliers' && !filteredSuppliers.length" class="py-8 text-center text-sm text-slate-500">
          {{ t('resources.empty') }}
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
          class="fixed inset-0 z-50 flex justify-end bg-black/40 p-3 backdrop-blur-sm lg:static lg:z-auto lg:inset-auto lg:flex lg:w-[420px] lg:shrink-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-0"
          @click.self="closePanel"
        >
          <div
            class="flex h-full w-full max-w-md flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-600 dark:bg-slate-900 lg:max-w-none lg:rounded-none lg:border-l lg:border-y-0 lg:border-r-0"
            @click.stop
          >
            <div class="border-b border-slate-200 p-4 dark:border-slate-700">
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-3">
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400">
                    <TruckIcon class="h-7 w-7" aria-hidden="true" />
                  </div>
                  <div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ selectedVehicle.code }}</div>
                    <div class="text-sm text-slate-600 dark:text-slate-400">{{ selectedVehicle.model }}</div>
                  </div>
                </div>
                <button
                  type="button"
                  class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden dark:hover:bg-slate-800 dark:hover:text-white"
                  :aria-label="t('resources.close_panel')"
                  @click="closePanel"
                >
                  <span class="text-xl leading-none">×</span>
                </button>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', statusOutlineClass(selectedVehicle.status)]">
                  {{ labelVehicleStatus(selectedVehicle.status) }}
                </span>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ selectedVehicle.capacityLabel }}</span>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-500"
                  disabled
                >
                  {{ t('resources.action_edit') }}
                </button>
                <button
                  type="button"
                  class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                  disabled
                >
                  <span class="text-rose-600 dark:text-rose-400">⏻</span>
                  {{ t('resources.action_deactivate') }}
                </button>
              </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_default_driver') }}</div>
              <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
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
                  type="button"
                  class="mt-3 w-full rounded-lg border border-teal-200 bg-white py-2 text-sm font-medium text-teal-800 hover:bg-teal-50 dark:border-teal-800 dark:bg-slate-800 dark:text-teal-300 dark:hover:bg-slate-700"
                  @click="openAssignModal(selectedVehicle.id)"
                >
                  {{ selectedVehicle.defaultDriver ? t('resources.assign_driver_change') : t('resources.assign_driver') }}
                </button>
              </div>

              <div class="mt-6 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_compliance') }}</div>
              <div class="mt-2 space-y-2">
                <div
                  class="rounded-lg border p-3"
                  :class="
                    selectedVehicle.insurance.state === 'exp'
                      ? 'border-rose-300 bg-rose-50 dark:border-rose-500/70 dark:bg-rose-950/20'
                      : selectedVehicle.insurance.state === 'soon'
                        ? 'border-amber-300 bg-amber-50 dark:border-amber-500/70 dark:bg-amber-950/20'
                        : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                  "
                >
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ t('resources.doc_insurance') }}</div>
                  <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ complianceDocLine(selectedVehicle.insurance) }}</div>
                  <button type="button" class="mt-2 text-xs font-medium text-teal-700 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300" disabled>
                    {{ t('resources.update_document') }}
                  </button>
                </div>
                <div
                  class="rounded-lg border p-3"
                  :class="
                    selectedVehicle.inspection.state === 'exp'
                      ? 'border-rose-300 bg-rose-50 dark:border-rose-500/70 dark:bg-rose-950/20'
                      : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                  "
                >
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ t('resources.doc_inspection') }}</div>
                  <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ complianceDocLine(selectedVehicle.inspection) }}</div>
                </div>
              </div>

              <div class="mt-6 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_assignments') }}</div>
              <p v-if="!selectedVehicle.assignments?.length" class="mt-2 text-xs text-slate-500">{{ t('resources.no_assignments') }}</p>
              <ul v-else class="relative mt-3 space-y-4 border-l border-slate-200 pl-4 dark:border-slate-700">
                <li v-for="(a, i) in selectedVehicle.assignments" :key="i" class="relative">
                  <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-teal-600 dark:border-slate-900" />
                  <div class="text-xs text-slate-500">{{ a.when }}</div>
                  <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ a.title }}</div>
                  <div class="mt-1 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                    <span>{{ a.driver }}</span>
                    <span class="text-emerald-700 dark:text-emerald-400">{{ t('resources.assignment_done') }}</span>
                  </div>
                </li>
              </ul>
            </div>

            <div class="border-t border-slate-200 p-3 dark:border-slate-700">
              <button
                type="button"
                class="w-full rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                disabled
              >
                {{ t('resources.view_history') }}
              </button>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <p class="border-t border-slate-200 px-4 py-3 text-center text-[11px] text-slate-500 dark:border-slate-700">{{ t('resources.demo_note') }}</p>

    <!-- Modal: gán tài xế từ user -->
    <Teleport to="body">
      <div
        v-if="assignModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="closeAssignModal"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900" @click.stop>
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('resources.assign_driver_modal_title') }}</h2>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('resources.assign_driver_modal_hint') }}</p>
          </div>
          <div class="max-h-[55vh] overflow-y-auto p-4">
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
            <p v-if="assignError" class="mt-2 text-xs text-rose-600">{{ assignError }}</p>
          </div>
          <div class="flex gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-700">
            <button
              type="button"
              class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="closeAssignModal"
            >
              {{ t('app.cancel') }}
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
              :disabled="!pickedUser || assignSubmitting"
              @click="submitAssignDriver"
            >
              {{ assignSubmitting ? t('resources.assign_driver_assigning') : t('resources.assign_driver_confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronRightIcon, TruckIcon } from '@heroicons/vue/24/outline'
import {
  createDriverFromUser,
  listDrivers,
  listTransportProviders,
  listVehicles,
  searchUsersForDriverAssignment,
  updateVehicle,
} from '../../api/operational'

const { t } = useI18n()

const tabs = [
  { id: 'vehicles', labelKey: 'resources.tab_vehicles' },
  { id: 'drivers', labelKey: 'resources.tab_drivers' },
  { id: 'suppliers', labelKey: 'resources.tab_suppliers' },
]

const activeTab = ref('vehicles')
const search = ref('')
const selectedVehicle = ref(null)
const loading = ref(true)
const error = ref('')

const vehicles = ref([])
const drivers = ref([])
const suppliers = ref([])

const filters = ref({
  status: '',
  type: '',
  compliance: '',
})

const assignModalOpen = ref(false)
const assignVehicleId = ref(null)
const userSearchQuery = ref('')
const userSearchResults = ref([])
const userSearchLoading = ref(false)
const pickedUser = ref(null)
const assignSubmitting = ref(false)
const assignError = ref('')
let userSearchTimer = null

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

function inferTypeKey(v) {
  if (v.payload_kg) return 'truck'
  const n = v.seat_count
  if (n != null && n >= 28) return 'bus'
  const s = (v.type || '').toLowerCase()
  if (/van|500|1000|tải|kg/.test(s)) return 'truck'
  if (/28|45|bus|coach|thaco|xe khách lớn/.test(s)) return 'bus'
  return 'van'
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
    model: raw.type || '—',
    typeLabel,
    capacityLabel,
    typeKey: inferTypeKey(raw),
    status: vehicleUiStatus(raw.status),
    insurance: docStateFromDate(raw.insurance_expires_at),
    inspection: docStateFromDate(raw.inspection_expires_at),
    defaultDriver: dd,
    driverName,
    assignments: [],
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
    phone: raw.phone || raw.user?.phone || '',
    employment_status: raw.employment_status,
    uiStatus: driverUiStatus(raw.employment_status),
  }
}

function enrichProvider(raw) {
  const contact = [raw.contact_name, raw.contact_phone].filter(Boolean).join(' · ') || '—'
  const service = raw.type === 'taxi' ? 'Taxi' : 'Vendor / nhà xe'
  return {
    id: raw.id,
    name: raw.name,
    contact,
    service,
    uiStatus: raw.is_active ? 'active' : 'inactive',
  }
}

async function loadAll() {
  loading.value = true
  error.value = ''
  try {
    const [vRes, dRes, pRes] = await Promise.all([
      listVehicles({ per_page: 200 }),
      listDrivers({ per_page: 200 }),
      listTransportProviders({ per_page: 200 }),
    ])
    vehicles.value = (vRes.items || []).map(enrichVehicle)
    drivers.value = (dRes.items || []).map(enrichDriver)
    suppliers.value = (pRes.items || []).map(enrichProvider)

    if (selectedVehicle.value) {
      const id = selectedVehicle.value.id
      selectedVehicle.value = vehicles.value.find((x) => x.id === id) ?? null
    }
  } catch {
    error.value = t('resources.load_error')
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

function setTab(id) {
  activeTab.value = id
  selectedVehicle.value = null
}

function closePanel() {
  selectedVehicle.value = null
}

function selectVehicle(v) {
  selectedVehicle.value = v
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
  if (doc.state === 'ok') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  }
  if (doc.state === 'soon') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  }
  return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
}

function insuranceHint(doc) {
  if (doc.state === 'ok') return ''
  if (doc.state === 'soon') return `${doc.days}d`
  return t('resources.exp_short')
}

function complianceDocLine(doc) {
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
  if (f === 'active') return s.uiStatus === 'active'
  if (f === 'inactive') return s.uiStatus === 'inactive'
  return true
}

function matchesCompliance(v) {
  const c = filters.value.compliance
  if (!c) return true
  const states = [v.insurance.state, v.inspection.state]
  if (c === 'ok') return states.every((s) => s === 'ok')
  if (c === 'soon') return states.some((s) => s === 'soon')
  if (c === 'exp') return states.some((s) => s === 'exp')
  return true
}

const filteredVehicles = computed(() => {
  const q = search.value.trim().toLowerCase()
  return vehicles.value.filter((v) => {
    if (!matchesVehicleStatus(v)) return false
    if (filters.value.type && v.typeKey !== filters.value.type) return false
    if (!matchesCompliance(v)) return false
    if (!q) return true
    const hay = `${v.code} ${v.model} ${v.driverName ?? ''}`.toLowerCase()
    return hay.includes(q)
  })
})

const filteredDrivers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return drivers.value.filter((d) => {
    if (!matchesDriverEmployment(d)) return false
    if (!q) return true
    return `${d.name} ${d.email} ${d.license} ${d.phone} ${d.employeeCode}`.toLowerCase().includes(q)
  })
})

const filteredSuppliers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return suppliers.value.filter((s) => {
    if (!matchesSupplierActive(s)) return false
    if (!q) return true
    return `${s.name} ${s.contact} ${s.service}`.toLowerCase().includes(q)
  })
})

watch(filteredVehicles, (list) => {
  if (!selectedVehicle.value) return
  if (!list.some((x) => x.id === selectedVehicle.value.id)) selectedVehicle.value = null
})

watch(activeTab, () => {
  search.value = ''
  filters.value = { status: '', type: '', compliance: '' }
})

function openAssignModal(vehicleId) {
  assignVehicleId.value = vehicleId
  assignModalOpen.value = true
  userSearchQuery.value = ''
  userSearchResults.value = []
  pickedUser.value = null
  assignError.value = ''
}

function closeAssignModal() {
  assignModalOpen.value = false
  assignVehicleId.value = null
  assignSubmitting.value = false
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
  } catch {
    assignError.value = t('resources.load_error')
    userSearchResults.value = []
  } finally {
    userSearchLoading.value = false
  }
}

async function submitAssignDriver() {
  if (!pickedUser.value) return
  assignSubmitting.value = true
  assignError.value = ''
  try {
    const driver = await createDriverFromUser(pickedUser.value.id)
    const vid = assignVehicleId.value
    if (vid != null) {
      await updateVehicle(vid, { default_driver_id: driver.id })
    }
    await loadAll()
    closeAssignModal()
  } catch (e) {
    assignError.value = e?.response?.data?.message || t('resources.load_error')
  } finally {
    assignSubmitting.value = false
  }
}

watch(assignModalOpen, (open) => {
  if (!open) {
    userSearchQuery.value = ''
    userSearchResults.value = []
    pickedUser.value = null
  }
})
</script>
