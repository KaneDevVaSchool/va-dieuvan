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
              v-else-if="activeTab === 'vehicles' && canManageVehicles"
              type="button"
              class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500"
              @click="openVehicleForm(null)"
            >
              {{ t('resources.add_vehicle') }}
            </button>
            <button
              v-else-if="activeTab === 'suppliers' && canManageProviders"
              type="button"
              class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500"
              @click="openProviderForm(null)"
            >
              {{ t('resources.add_supplier') }}
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
        <label v-if="activeTab === 'suppliers'" class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_contract') }}
          <select
            v-model="filters.contract"
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
                      <component :is="vehicleIconComponent(v.iconKind)" class="h-5 w-5" aria-hidden="true" />
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
          <table class="w-full min-w-[900px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-slate-50 to-slate-100/90 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:from-slate-800 dark:to-slate-800/80 dark:text-slate-400">
                <th class="border-b border-slate-200 px-4 py-3 first:rounded-tl-xl dark:border-slate-700">{{ t('resources.col_supplier') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_solutions_services') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_contract') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">{{ t('resources.col_status') }}</th>
                <th class="border-b border-slate-200 px-4 py-3 text-right last:rounded-tr-xl dark:border-slate-700">{{ t('resources.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="s in filteredSuppliers"
                :key="s.id"
                class="cursor-pointer transition hover:bg-teal-50/40 dark:hover:bg-slate-800/60"
                :class="selectedSupplier?.id === s.id ? 'bg-teal-50/80 dark:bg-slate-800/80' : ''"
                @click="selectSupplier(s)"
              >
                <td class="px-4 py-3 align-middle">
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                      <BuildingOffice2Icon class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div>
                      <div class="font-semibold text-slate-900 dark:text-white">{{ s.name }}</div>
                      <div class="text-xs text-slate-500">{{ s.typeLabel }}</div>
                    </div>
                  </div>
                </td>
                <td class="max-w-[240px] px-4 py-3 align-middle text-slate-600 dark:text-slate-400">
                  <span class="line-clamp-2 text-xs">{{ s.serviceSummary }}</span>
                </td>
                <td class="px-4 py-3 align-middle">
                  <span :class="compliancePillClass(s.contract)">{{ t('resources.tag_contract') }} {{ insuranceHint(s.contract) }}</span>
                </td>
                <td class="px-4 py-3 align-middle">
                  <span :class="statusBadgeClass(s.uiStatus)">{{ labelProviderStatus(s.uiStatus) }}</span>
                </td>
                <td class="px-4 py-3 align-middle text-right text-slate-400">
                  <ChevronRightIcon class="ml-auto inline h-5 w-5" aria-hidden="true" />
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
                    <component :is="vehicleIconComponent(selectedVehicle.iconKind)" class="h-7 w-7" aria-hidden="true" />
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
                  v-if="canManageVehicles"
                  type="button"
                  class="rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-500"
                  @click="openVehicleForm(selectedVehicle)"
                >
                  {{ t('resources.action_edit') }}
                </button>
                <button
                  v-else
                  type="button"
                  class="rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white opacity-50"
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
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <BuildingOffice2Icon class="h-7 w-7" aria-hidden="true" />
                  </div>
                  <div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ selectedSupplier.name }}</div>
                    <div class="text-sm text-slate-600 dark:text-slate-400">{{ selectedSupplier.typeLabel }}</div>
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
                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', statusOutlineClass(selectedSupplier.uiStatus)]">
                  {{ labelProviderStatus(selectedSupplier.uiStatus) }}
                </span>
                <span :class="compliancePillClass(selectedSupplier.contract)">{{ t('resources.tag_contract') }} {{ insuranceHint(selectedSupplier.contract) }}</span>
              </div>
              <div class="mt-4">
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

    <p class="border-t border-slate-200 px-4 py-3 text-center text-[11px] text-slate-500 dark:border-slate-700">{{ t('resources.demo_note') }}</p>

    <!-- Modal: thêm / sửa xe -->
    <Teleport to="body">
      <div
        v-if="vehicleModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="vehicleModalOpen = false"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900" @click.stop>
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">
              {{ vehicleForm.id ? t('resources.vehicle_form_title_edit') : t('resources.vehicle_form_title_add') }}
            </h2>
          </div>
          <form class="max-h-[70vh] space-y-3 overflow-y-auto p-4" @submit.prevent="submitVehicleForm">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.vehicle_form_plate') }}
              <input
                v-model="vehicleForm.license_plate"
                type="text"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.vehicle_form_type') }}
              <input v-model="vehicleForm.type" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.vehicle_form_seats') }}
                <input v-model="vehicleForm.seat_count" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.vehicle_form_payload') }}
                <input v-model="vehicleForm.payload_kg" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.filter_status') }} (API)
              <select v-model="vehicleForm.status" class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                <option value="ready">{{ t('resources.vehicle_status_ready') }}</option>
                <option value="in_use">{{ t('resources.vehicle_status_in_use') }}</option>
                <option value="maintenance">{{ t('resources.vehicle_status_maintenance') }}</option>
                <option value="broken">{{ t('resources.vehicle_status_broken') }}</option>
              </select>
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.vehicle_form_odometer') }}
              <input v-model="vehicleForm.odometer_km" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.vehicle_form_inspection') }}
                <input v-model="vehicleForm.inspection_expires_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.vehicle_form_insurance') }}
                <input v-model="vehicleForm.insurance_expires_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <p v-if="vehicleFormError" class="text-xs text-rose-600">{{ vehicleFormError }}</p>
            <div class="flex gap-2 pt-2">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="vehicleModalOpen = false"
              >
                {{ t('app.cancel') }}
              </button>
              <button
                type="submit"
                class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                :disabled="vehicleSaving"
              >
                {{ vehicleSaving ? t('resources.loading') : t('resources.vehicle_form_save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

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

    <!-- Modal: thêm / sửa nhà cung cấp -->
    <Teleport to="body">
      <div
        v-if="providerModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="providerModalOpen = false"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900" @click.stop>
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">
              {{ providerForm.id ? t('resources.provider_form_title_edit') : t('resources.provider_form_title_add') }}
            </h2>
          </div>
          <form class="max-h-[70vh] space-y-3 overflow-y-auto p-4" @submit.prevent="submitProviderForm">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_name') }}
              <input
                v-model="providerForm.name"
                type="text"
                required
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_type') }}
              <select v-model="providerForm.type" class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                <option value="vendor">{{ t('resources.provider_form_type_vendor') }}</option>
                <option value="taxi">{{ t('resources.provider_form_type_taxi') }}</option>
              </select>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contact_name') }}
                <input v-model="providerForm.contact_name" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contact_phone') }}
                <input v-model="providerForm.contact_phone" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_contact_email') }}
              <input v-model="providerForm.contact_email" type="email" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            </label>
            <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
              <input v-model="providerForm.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600" />
              {{ t('resources.provider_active') }}
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_notes') }}
              <textarea v-model="providerForm.notes" rows="2" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contract_number') }}
                <input v-model="providerForm.contract_number" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('resources.provider_form_contract_signed') }}
                <input v-model="providerForm.contract_signed_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
              </label>
            </div>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('resources.provider_form_contract_expires') }}
              <input v-model="providerForm.contract_expires_at" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            </label>

            <div class="border-t border-slate-200 pt-3 dark:border-slate-700">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('resources.section_supplier_services') }}</span>
                <button type="button" class="text-xs font-medium text-teal-700 hover:text-teal-800 dark:text-teal-400" @click="addProviderServiceRow">
                  {{ t('resources.provider_form_add_row') }}
                </button>
              </div>
              <div v-for="(row, idx) in providerForm.services" :key="idx" class="mb-2 grid gap-2 rounded-lg border border-slate-100 p-2 dark:border-slate-700">
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

            <p v-if="providerFormError" class="text-xs text-rose-600">{{ providerFormError }}</p>
            <div class="flex gap-2 pt-2">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="providerModalOpen = false"
              >
                {{ t('app.cancel') }}
              </button>
              <button
                type="submit"
                class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                :disabled="providerSaving"
              >
                {{ providerSaving ? t('resources.loading') : t('resources.provider_form_save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { BuildingOffice2Icon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import {
  createDriverFromUser,
  createTransportProvider,
  createVehicle,
  listDrivers,
  listTransportProviders,
  listVehicles,
  searchUsersForDriverAssignment,
  updateTransportProvider,
  updateVehicle,
} from '../../api/operational'
import { useAuthStore } from '../../store'
import { VEHICLE_ICON_COMPONENTS, vehicleIconKind } from '../../util/vehicleIcon'

const { t } = useI18n()
const auth = useAuthStore()
const canManageVehicles = computed(() => auth.hasPermission('resource.vehicle.manage'))
const canManageProviders = computed(() => auth.hasPermission('resource.provider.manage'))

const tabs = [
  { id: 'vehicles', labelKey: 'resources.tab_vehicles' },
  { id: 'drivers', labelKey: 'resources.tab_drivers' },
  { id: 'suppliers', labelKey: 'resources.tab_suppliers' },
]

const activeTab = ref('vehicles')
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
  compliance: '',
  contract: '',
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

const vehicleModalOpen = ref(false)
const vehicleSaving = ref(false)
const vehicleFormError = ref('')

function emptyVehicleForm() {
  return {
    id: null,
    license_plate: '',
    type: '',
    seat_count: '',
    payload_kg: '',
    status: 'ready',
    odometer_km: 0,
    inspection_expires_at: '',
    insurance_expires_at: '',
  }
}

const vehicleForm = ref(emptyVehicleForm())

const providerModalOpen = ref(false)
const providerSaving = ref(false)
const providerFormError = ref('')

function emptyProviderServiceRow() {
  return { kind: 'solution', name: '', note: '' }
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
    model: raw.type || '—',
    type: raw.type ?? '',
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
    inspection_expires_at: raw.inspection_expires_at,
    insurance_expires_at: raw.insurance_expires_at,
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
    if (selectedSupplier.value) {
      const sid = selectedSupplier.value.id
      selectedSupplier.value = suppliers.value.find((x) => x.id === sid) ?? null
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
  selectedSupplier.value = null
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
    if (filters.value.type && v.iconKind !== filters.value.type) return false
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
    if (!matchesSupplierContract(s)) return false
    if (!q) return true
    const svcHay = (s.servicesList || []).map((x) => `${x.name} ${x.note || ''}`).join(' ')
    const hay = `${s.name} ${s.contact} ${s.service} ${s.contract_number || ''} ${s.contact_email || ''} ${svcHay}`.toLowerCase()
    return hay.includes(q)
  })
})

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
  filters.value = { status: '', type: '', compliance: '', contract: '' }
})

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
    const rows = (s.servicesList && s.servicesList.length ? s.servicesList : [emptyProviderServiceRow()]).map((r) => ({
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
    const msg = e?.response?.data?.message
    const errs = e?.response?.data?.errors
    providerFormError.value =
      (typeof msg === 'string' && msg) ||
      (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
      t('resources.load_error')
  } finally {
    providerSaving.value = false
  }
}

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

function openVehicleForm(v) {
  vehicleFormError.value = ''
  if (v) {
    vehicleForm.value = {
      id: v.id,
      license_plate: v.license_plate,
      type: v.type || '',
      seat_count: v.seat_count ?? '',
      payload_kg: v.payload_kg ?? '',
      status: v.apiStatus,
      odometer_km: v.odometer_km ?? 0,
      inspection_expires_at: v.inspection_expires_at || '',
      insurance_expires_at: v.insurance_expires_at || '',
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

async function submitVehicleForm() {
  vehicleSaving.value = true
  vehicleFormError.value = ''
  try {
    const f = vehicleForm.value
    const payload = {
      license_plate: f.license_plate.trim(),
      type: f.type || null,
      seat_count: numOrNull(f.seat_count),
      payload_kg: numOrNull(f.payload_kg),
      status: f.status,
      odometer_km: Number(f.odometer_km) || 0,
      inspection_expires_at: f.inspection_expires_at || null,
      insurance_expires_at: f.insurance_expires_at || null,
    }
    if (f.id) {
      await updateVehicle(f.id, payload)
    } else {
      await createVehicle(payload)
    }
    vehicleModalOpen.value = false
    await loadAll()
  } catch (e) {
    const msg = e?.response?.data?.message
    const errs = e?.response?.data?.errors
    vehicleFormError.value =
      (typeof msg === 'string' && msg) ||
      (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
      t('resources.load_error')
  } finally {
    vehicleSaving.value = false
  }
}
</script>
