<template>
  <div class="space-y-6 pb-10">
    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
          {{ t('tp_programs_page.hero_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('tp_programs_page.hero_subtitle') }}</p>
      </div>
      <Button
        class="inline-flex shrink-0 items-center gap-2"
        data-testid="tp-programs-create"
        @click="goCreate"
      >
        <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
        {{ t('tp_programs_page.cta_create') }}
      </Button>
    </div>

    <TpProgramSummaryBar
      :stats="kpiStats"
      :loading="loading"
      :active-status="filters.status"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="tpProgramsDatagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="filters.search"
              input-id="tp-programs-list-search"
              :placeholder="t('tp_programs_page.search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              data-testid="tp-programs-toolbar-search"
            />
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('tp_programs_page.filter_show_controls_title')"
              :hint="t('tp_programs_page.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="tp-programs-toolbar-filter"
                  @click="openFilterPanel"
                >
                  {{ t('tp_programs_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'tp-prog-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`tp-prog-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`tp-prog-filter-vis-${fd.key}`"
                />
                <label
                  :for="`tp-prog-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <button
              v-if="canManagePrograms && selectedProgramIds.length"
              type="button"
              class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-800 shadow-sm transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200"
              :disabled="bulkDeleting"
              data-testid="tp-programs-bulk-delete"
              @click="confirmBulkDeletePrograms"
            >
              {{ t('tp_programs_page.bulk_delete', { n: selectedProgramIds.length }) }}
            </button>

            <div v-if="canManagePrograms" class="relative" data-tp-programs-data-panel>
              <DatagridToolbarActionButton
                icon="data"
                :active="showDataMenu"
                test-id="tp-programs-toolbar-data"
                @click="toggleDataMenu"
              >
                {{ t('tp_programs_page.toolbar_data') }}
              </DatagridToolbarActionButton>
              <div
                v-if="showDataMenu"
                class="absolute right-0 top-[calc(100%+6px)] z-50 min-w-[280px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800"
                  data-testid="tp-programs-data-import-sample"
                  @click="downloadProgramSample(); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-slate-800 dark:text-slate-100">
                    {{ t('tp_programs_page.data_menu_import_sample') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
                    {{ t('tp_programs_page.data_menu_import_sample_hint') }}
                  </span>
                </button>
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800"
                  data-testid="tp-programs-data-import"
                  @click="triggerProgramImport(); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-slate-800 dark:text-slate-100">
                    {{ t('tp_programs_page.data_menu_import') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
                    {{ t('tp_programs_page.data_menu_import_hint') }}
                  </span>
                </button>
                <div class="my-1 border-t border-slate-100 dark:border-slate-700" role="separator" />
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-slate-800"
                  :disabled="exporting || !(listMeta.total ?? 0)"
                  data-testid="tp-programs-data-export"
                  @click="exportProgramList(); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-slate-800 dark:text-slate-100">
                    {{ t('tp_programs_page.data_menu_export') }}
                  </span>
                </button>
                <div class="my-1 border-t border-slate-100 dark:border-slate-700" role="separator" />
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-rose-950/30"
                  :disabled="!(listMeta.total ?? 0) || bulkSubmitting"
                  data-testid="tp-programs-data-purge-soft"
                  @click="openPurgeAll(false); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-rose-900 dark:text-rose-200">
                    {{ t('tp_programs_page.purge_all_soft') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-rose-700/80 dark:text-rose-300/80">
                    {{ t('tp_programs_page.purge_all_soft_hint', { n: listMeta.total ?? 0 }) }}
                  </span>
                </button>
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-red-950/30"
                  :disabled="!(listMeta.total ?? 0) || bulkSubmitting"
                  data-testid="tp-programs-data-purge-permanent"
                  @click="openPurgeAll(true); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-red-950 dark:text-red-200">
                    {{ t('tp_programs_page.purge_all_permanent') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-red-800/80 dark:text-red-300/80">
                    {{ t('tp_programs_page.purge_all_permanent_hint', { n: listMeta.total ?? 0 }) }}
                  </span>
                </button>
              </div>
              <input
                ref="programImportInputRef"
                type="file"
                accept=".xlsx,.xls"
                class="hidden"
                data-testid="tp-programs-import-file"
                @change="onProgramImportFile"
              />
            </div>
          </div>

          <div
            class="ml-auto flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 p-1 dark:border-slate-600"
            role="group"
            :aria-label="t('tp_programs_page.view_grid')"
          >
            <button
              type="button"
              class="grid h-10 w-10 place-items-center rounded-lg transition"
              :class="view === 'grid' ? 'bg-va-800 text-white' : 'text-slate-400 hover:text-slate-600'"
              :aria-label="t('tp_programs_page.view_grid')"
              :aria-pressed="view === 'grid'"
              data-testid="tp-programs-view-grid"
              @click="view = 'grid'"
            >
              <Squares2X2Icon class="h-5 w-5" />
            </button>
            <button
              type="button"
              class="grid h-10 w-10 place-items-center rounded-lg transition"
              :class="view === 'list' ? 'bg-va-800 text-white' : 'text-slate-400 hover:text-slate-600'"
              :aria-label="t('tp_programs_page.view_list')"
              :aria-pressed="view === 'list'"
              data-testid="tp-programs-view-list"
              @click="view = 'list'"
            >
              <ListBulletIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <DatagridFilterField v-if="visibleFilters.status">
          <select
            v-model="filters.status"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('tp_programs_page.filter_status')"
            data-testid="tp-programs-filter-status"
            @change="load"
          >
            <option value="">{{ t('tp_programs_page.filter_status') }}</option>
            <option v-for="opt in statusFilterOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.schoolYear">
          <select
            v-model="filters.schoolYear"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('tp_programs_page.filter_school_year')"
            data-testid="tp-programs-filter-school-year"
          >
            <option value="">{{ t('tp_programs_page.filter_school_year') }}</option>
            <option v-for="y in schoolYearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.route">
          <select
            v-model="filters.route"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('tp_programs_page.filter_route')"
            data-testid="tp-programs-filter-route"
          >
            <option value="">{{ t('tp_programs_page.filter_route') }}</option>
            <option v-for="r in routeOptions" :key="r" :value="r">{{ r }}</option>
          </select>
        </DatagridFilterField>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-5 dark:border-slate-700">
        <p class="text-sm text-slate-500">{{ listSummaryText }}</p>
        <label class="flex items-center gap-2 text-sm text-slate-500">
          {{ t('tp_programs_page.sort_label') }}:
          <select
            v-model="sort"
            :aria-label="t('tp_programs_page.sort_label')"
            class="h-10 rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-700 outline-none ring-va-800/20 focus:ring dark:border-slate-600 dark:bg-slate-950"
            data-testid="tp-programs-sort"
          >
            <option value="newest">{{ t('tp_programs_page.sort_newest') }}</option>
            <option value="oldest">{{ t('tp_programs_page.sort_oldest') }}</option>
            <option value="name">{{ t('tp_programs_page.sort_name') }}</option>
            <option value="students">{{ t('tp_programs_page.sort_students') }}</option>
          </select>
        </label>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16 text-base text-slate-500">
        <ArrowPathIcon class="mr-2 h-6 w-6 animate-spin" aria-hidden="true" />
        {{ t('tp_programs_page.loading') }}
      </div>
      <div v-else-if="!visibleItems.length" class="flex flex-col items-center justify-center py-16 text-center">
        <AcademicCapIcon class="mb-3 h-14 w-14 text-slate-300" aria-hidden="true" />
        <p class="text-base font-medium text-slate-600">{{ t('tp_programs_page.empty_title') }}</p>
        <Button class="mt-4" data-testid="tp-programs-empty-cta" @click="goCreate">
          {{ t('tp_programs_page.empty_cta') }}
        </Button>
      </div>

      <div
        v-else-if="view === 'grid'"
        class="grid grid-cols-1 gap-3 px-4 pb-4 sm:grid-cols-2 sm:px-5 xl:grid-cols-3"
      >
        <article
          v-for="p in visibleItems"
          :key="p.id"
          class="group relative flex cursor-pointer flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-va-800/30 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/40"
          :data-testid="`tp-program-card-${p.id}`"
          @click="goWorkspace(p.id)"
        >
          <div
            v-if="canManagePrograms"
            class="absolute left-2 top-2 z-10 flex items-center gap-1"
            @click.stop
          >
            <input
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 bg-white accent-va-800 shadow-sm"
              :checked="selectedProgramIds.includes(p.id)"
              :aria-label="t('tp_programs_page.select_row')"
              :data-testid="`tp-program-select-${p.id}`"
              @change="toggleProgramSelection(p.id, $event)"
            />
          </div>
          <div class="flex min-h-0 flex-1 flex-col gap-2.5 p-3.5">
            <div class="flex items-start justify-between gap-2">
              <div class="flex min-w-0 items-start gap-2.5">
                <div
                  class="grid h-9 w-9 shrink-0 place-items-center rounded-lg ring-1 ring-inset ring-black/5"
                  :class="accent(p.status).iconBg"
                >
                  <TruckIcon class="h-5 w-5" :class="accent(p.status).iconText" />
                </div>
                <div class="min-w-0">
                  <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 dark:text-white">
                    {{ p.name }}
                  </h3>
                  <p class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-400">
                    {{ t('tp_programs_page.card_school_year', { year: schoolYear(p) }) }}
                  </p>
                </div>
              </div>
              <span :class="statusClassCompact(p.status)" class="shrink-0">
                <span class="h-1 w-1 rounded-full" :class="accent(p.status).dot"></span>
                {{ statusLabel(p.status) }}
              </span>
            </div>

            <div
              class="grid grid-cols-3 divide-x divide-slate-200/80 overflow-hidden rounded-lg border border-slate-200/80 bg-slate-50/50 dark:divide-slate-600 dark:border-slate-700 dark:bg-slate-800/30"
            >
              <div class="px-1.5 py-2 text-center">
                <div class="font-display text-lg font-bold tabular-nums leading-none text-slate-900 dark:text-white">
                  {{ p.enrolled_count ?? 0 }}
                </div>
                <div class="mt-0.5 text-[10px] font-medium leading-tight text-slate-500">
                  {{ t('tp_programs_page.card_students') }}
                </div>
              </div>
              <div class="px-1.5 py-2 text-center">
                <div class="font-display text-lg font-bold tabular-nums leading-none text-slate-900 dark:text-white">
                  {{ p.day_count ?? 0 }}
                </div>
                <div class="mt-0.5 text-[10px] font-medium leading-tight text-slate-500">
                  {{ t('tp_programs_page.card_days') }}
                </div>
              </div>
              <div class="px-1.5 py-2 text-center">
                <div class="font-display text-lg font-bold tabular-nums leading-none" :class="accent(p.status).iconText">
                  {{ runsPerWeek(p) }}
                </div>
                <div class="mt-0.5 text-[10px] font-medium leading-tight text-slate-500">
                  {{ t('tp_programs_page.card_sessions_per_week') }}
                </div>
              </div>
            </div>

            <div class="rounded-lg border border-slate-200/80 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-900/50">
              <div class="flex gap-2">
                <div class="flex w-3 shrink-0 flex-col items-center pt-0.5" aria-hidden="true">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 ring-2 ring-emerald-500/15"></span>
                  <span class="my-0.5 w-px min-h-4 flex-1 bg-gradient-to-b from-emerald-300/80 to-va-800/40 dark:from-emerald-600/50"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-va-800 ring-2 ring-va-800/15"></span>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                  <div>
                    <div class="text-[9px] font-semibold uppercase tracking-wide text-emerald-700/90 dark:text-emerald-400">
                      {{ t('tp_programs_page.card_origin') }}
                    </div>
                    <p class="mt-0.5 line-clamp-2 text-xs font-medium leading-snug text-slate-800 dark:text-slate-100">
                      {{ p.origin_name || t('tp_programs_page.card_route_empty') }}
                    </p>
                  </div>
                  <div>
                    <div class="text-[9px] font-semibold uppercase tracking-wide text-va-800/90 dark:text-va-300">
                      {{ t('tp_programs_page.card_destination') }}
                    </div>
                    <p class="mt-0.5 line-clamp-2 text-xs font-medium leading-snug text-slate-800 dark:text-slate-100">
                      {{ p.destination_name || t('tp_programs_page.card_destination_default') }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
              <span
                class="inline-flex max-w-full items-center gap-1 rounded-md border border-slate-200/80 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-200"
              >
                <ClockIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
                <span class="shrink-0 text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                  {{ t('tp_programs_page.card_time') }}
                </span>
                <span class="truncate tabular-nums text-slate-800 dark:text-slate-100">{{ timeRange(p) }}</span>
              </span>
            </div>

            <div class="mt-auto">
              <div class="mb-1 flex items-center justify-between text-[10px]">
                <span class="font-medium text-slate-500">{{ t('tp_programs_page.card_progress') }}</span>
                <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ progress(p) }}%</span>
              </div>
              <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                <div
                  class="h-full rounded-full transition-all"
                  :class="accent(p.status).bar"
                  :style="{ width: progress(p) + '%' }"
                ></div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-3.5 py-2 dark:border-slate-700">
            <div class="flex min-w-0 items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
              <span
                class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-500 dark:bg-slate-800"
              >
                {{ initials(p.responsible_user_name) }}
              </span>
              <span class="truncate">{{ p.responsible_user_name || t('tp_programs_page.card_responsible') }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <button
                v-if="canManagePrograms"
                type="button"
                class="inline-flex h-8 items-center gap-1 rounded-lg border border-rose-200 px-2 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300"
                :data-testid="`tp-program-delete-${p.id}`"
                @click="removeProgram(p, $event)"
              >
                <TrashIcon class="h-3.5 w-3.5" aria-hidden="true" />
                {{ t('tp_programs_page.action_delete') }}
              </button>
              <span
                class="inline-flex shrink-0 items-center gap-0.5 text-xs font-medium text-va-800 group-hover:underline"
              >
                {{ t('tp_programs_page.card_view_detail') }}
                <ArrowRightIcon class="h-3.5 w-3.5" aria-hidden="true" />
              </span>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="overflow-x-auto border-t border-slate-100 dark:border-slate-700">
        <table class="w-full min-w-[56rem] text-left text-base">
          <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-500 dark:bg-slate-800/60">
            <tr>
              <th v-if="canManagePrograms" class="w-10 px-3 py-3.5">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 accent-va-800"
                  :checked="programsPageAllSelected"
                  :aria-label="t('tp_programs_page.select_all_page')"
                  data-testid="tp-programs-select-all"
                  @change="toggleSelectAllProgramsPage"
                />
              </th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_program') }}</th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_route') }}</th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_time') }}</th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_students') }}</th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_days') }}</th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_responsible') }}</th>
              <th class="px-4 py-3.5 font-medium">{{ t('tp_programs_page.col_status') }}</th>
              <th v-if="canManagePrograms" class="px-4 py-3.5 font-medium text-right">{{ t('tp_programs_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <tr
              v-for="p in pagedListItems"
              :key="p.id"
              class="cursor-pointer hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
              :data-testid="`tp-program-row-${p.id}`"
              @click="goWorkspace(p.id)"
            >
              <td v-if="canManagePrograms" class="px-3 py-3.5" @click.stop>
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 accent-va-800"
                  :checked="selectedProgramIds.includes(p.id)"
                  :data-testid="`tp-program-row-select-${p.id}`"
                  @change="toggleProgramSelection(p.id, $event)"
                />
              </td>
              <td class="px-4 py-3.5">
                <div class="font-medium text-slate-900 dark:text-white">{{ p.name }}</div>
                <div class="font-mono text-xs text-slate-400">{{ p.code }}</div>
              </td>
              <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">
                <div class="text-[11px] uppercase text-slate-400">{{ t('tp_programs_page.card_origin') }}</div>
                <div>{{ p.origin_name || t('tp_programs_page.card_route_empty') }}</div>
                <div class="mt-1 text-[11px] uppercase text-slate-400">{{ t('tp_programs_page.card_destination') }}</div>
                <div>{{ p.destination_name || t('tp_programs_page.card_destination_default') }}</div>
              </td>
              <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ timeRange(p) }}</td>
              <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ p.enrolled_count ?? 0 }}</td>
              <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ p.day_count ?? 0 }}</td>
              <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ p.responsible_user_name || t('tp_programs_page.card_responsible') }}</td>
              <td class="px-4 py-3.5">
                <span :class="statusClass(p.status)">
                  <span class="h-1.5 w-1.5 rounded-full" :class="accent(p.status).dot"></span>
                  {{ statusLabel(p.status) }}
                </span>
              </td>
              <td v-if="canManagePrograms" class="px-4 py-3.5 text-right" @click.stop>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40"
                  :data-testid="`tp-program-row-delete-${p.id}`"
                  @click="removeProgram(p, $event)"
                >
                  <TrashIcon class="h-4 w-4" aria-hidden="true" />
                  {{ t('tp_programs_page.action_delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm dark:border-slate-700">
          <div class="flex flex-wrap items-center gap-3 text-slate-500">
            <span v-if="listTotal > 0">{{ listRangeText }}</span>
            <label class="inline-flex items-center gap-2">
              <span>{{ t('tp_programs_page.per_page_label') }}</span>
              <select
                v-model.number="listPerPage"
                :aria-label="t('tp_programs_page.per_page_label')"
                class="h-10 rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-700 outline-none ring-va-800/20 focus:ring dark:border-slate-600 dark:bg-slate-950"
                data-testid="tp-programs-per-page"
              >
                <option v-for="n in LIST_PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
              </select>
              <span>{{ t('tp_programs_page.per_page_suffix') }}</span>
            </label>
          </div>
          <div v-if="listLastPage > 1" class="flex items-center gap-2">
            <span class="text-xs text-slate-500">
              {{ t('tp_programs_page.page_of', { page: listPage, last: listLastPage }) }}
            </span>
            <Button variant="secondary" :disabled="listPage <= 1" data-testid="tp-programs-page-prev" @click="changeListPage(listPage - 1)">
              {{ t('tp_programs_page.page_prev') }}
            </Button>
            <Button variant="secondary" :disabled="listPage >= listLastPage" data-testid="tp-programs-page-next" @click="changeListPage(listPage + 1)">
              {{ t('tp_programs_page.page_next') }}
            </Button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="purgeAllOpen"
      class="fixed inset-0 z-[200] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="tp-programs-purge-all-title"
      data-testid="tp-programs-purge-all-modal"
      @click.self="closePurgeAll"
    >
      <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true" />
      <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
        <h2 id="tp-programs-purge-all-title" class="text-base font-semibold text-slate-900 dark:text-white">
          {{ t('tp_programs_page.purge_all_modal_title') }}
        </h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ t('tp_programs_page.purge_all_modal_lead') }}</p>
        <p class="mt-2 text-sm font-medium text-slate-800 dark:text-slate-200">
          {{
            purgePermanent
              ? t('tp_programs_page.purge_all_mode_permanent')
              : t('tp_programs_page.purge_all_mode_soft')
          }}
        </p>
        <label for="tp-programs-purge-confirm" class="mt-4 block text-xs font-medium text-slate-700 dark:text-slate-300">
          {{ t('tp_programs_page.purge_all_confirm_label') }}
        </label>
        <p class="mt-1 text-[11px] text-slate-500">{{ t('tp_programs_page.purge_all_confirm_hint', { phrase: purgeRequiredPhrase }) }}</p>
        <p
          v-if="purgeCooldownSeconds > 0"
          class="mt-2 text-xs text-amber-700"
          data-testid="tp-programs-purge-rate-limit"
        >
          {{ t('tp_programs_page.purge_all_rate_limit_wait', { seconds: purgeCooldownSeconds }) }}
        </p>
        <input
          id="tp-programs-purge-confirm"
          v-model="purgeConfirmPhrase"
          type="text"
          autocomplete="off"
          class="input mt-2 h-10 w-full text-sm"
          data-testid="tp-programs-purge-confirm-input"
        />
        <div class="mt-5 flex justify-end gap-2">
          <Button variant="secondary" data-testid="tp-programs-purge-cancel" @click="closePurgeAll">
            {{ t('common.cancel') }}
          </Button>
          <Button
            variant="danger"
            :disabled="
              bulkSubmitting ||
              purgeCooldownSeconds > 0 ||
              purgeConfirmPhrase !== purgeRequiredPhrase
            "
            data-testid="tp-programs-purge-submit"
            @click="submitPurgeAll"
          >
            {{
              bulkSubmitting
                ? t('tp_programs_page.purge_all_submitting')
                : t('tp_programs_page.purge_all_submit', { n: listMeta.total ?? 0 })
            }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onActivated, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  PlusIcon,
  Squares2X2Icon,
  ListBulletIcon,
  ArrowPathIcon,
  AcademicCapIcon,
  ClockIcon,
  TruckIcon,
  ArrowRightIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import TpProgramSummaryBar from '../../components/transportProgram/TpProgramSummaryBar.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import {
  listPrograms,
  deleteProgram,
  bulkDeletePrograms,
  purgeAllTpPrograms,
  exportProgramsList,
  downloadProgramImportSample,
  importProgramsFile,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const items = ref([])
const selectedProgramIds = ref([])
const bulkDeleting = ref(false)
const bulkSubmitting = ref(false)
const exporting = ref(false)
const showDataMenu = ref(false)
const purgeAllOpen = ref(false)
const purgePermanent = ref(false)
const purgeConfirmPhrase = ref('')
const purgeCooldownSeconds = ref(0)
const programImportInputRef = ref(null)
const listMeta = ref({ total: 0 })
let purgeCooldownTimer = null
const canManagePrograms = computed(() => auth.hasPermission('tp_program.manage'))
const kpiStats = ref({ total: 0, by_status: {}, operating_days: 0 })
const view = ref('grid')
const sort = ref('newest')
const filters = reactive({ search: '', status: '', schoolYear: '', route: '' })

const FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const TP_PROG_FILTER_CONTROLS = [
  { key: 'status', label: '', default: false },
  { key: 'schoolYear', label: '', default: false },
  { key: 'route', label: '', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
} = useVisibleFilterControls(TP_PROG_FILTER_CONTROLS, 'va-dieuvan.tp-programs.visible-filters.v1')

const FILTER_CONTROL_LABEL_KEYS = {
  status: 'tp_programs_page.filter_status',
  schoolYear: 'tp_programs_page.filter_school_year',
  route: 'tp_programs_page.filter_route',
}

const filterControlDefs = computed(() =>
  TP_PROG_FILTER_CONTROLS.map((fd) => ({
    key: fd.key,
    label: t(FILTER_CONTROL_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

const statusFilterOptions = computed(() => [
  { value: 'draft', label: t('tp_programs_page.status_draft') },
  { value: 'active', label: t('tp_programs_page.status_active') },
  { value: 'paused', label: t('tp_programs_page.status_paused') },
  { value: 'completed', label: t('tp_programs_page.status_completed') },
  { value: 'cancelled', label: t('tp_programs_page.status_cancelled') },
])

const tpProgramsDatagridRef = ref(null)
useDetailsAutoCloseWithin(tpProgramsDatagridRef)

const purgeRequiredPhrase = computed(() => `XOA ${listMeta.value.total ?? 0}`)

function programListParams() {
  return {
    status: filters.status || undefined,
    search: filters.search?.trim() || undefined,
    destination_name: filters.route || undefined,
    school_year: filters.schoolYear || undefined,
  }
}

function closeDataMenu() {
  showDataMenu.value = false
}

function toggleDataMenu() {
  closeFilterPanel()
  showDataMenu.value = !showDataMenu.value
}

function onDatagridDocMouseDown(ev) {
  const t = ev.target
  if (!(t instanceof Element)) return
  if (t.closest('[data-tp-programs-data-panel]')) return
  closeDataMenu()
}

function clearPurgeCooldownTimer() {
  if (purgeCooldownTimer) {
    clearInterval(purgeCooldownTimer)
    purgeCooldownTimer = null
  }
  purgeCooldownSeconds.value = 0
}

function startPurgeCooldown(seconds) {
  clearPurgeCooldownTimer()
  const n = Math.max(1, Math.min(120, Math.floor(Number(seconds) || 35)))
  purgeCooldownSeconds.value = n
  purgeCooldownTimer = setInterval(() => {
    purgeCooldownSeconds.value -= 1
    if (purgeCooldownSeconds.value <= 0) {
      clearPurgeCooldownTimer()
    }
  }, 1000)
}

function openPurgeAll(permanent) {
  purgePermanent.value = permanent
  purgeConfirmPhrase.value = ''
  purgeAllOpen.value = true
}

function closePurgeAll() {
  if (bulkSubmitting.value) return
  purgeAllOpen.value = false
  purgeConfirmPhrase.value = ''
}

async function submitPurgeAll() {
  if (
    bulkSubmitting.value ||
    purgeCooldownSeconds.value > 0 ||
    purgeConfirmPhrase.value !== purgeRequiredPhrase.value
  ) {
    return
  }
  bulkSubmitting.value = true
  try {
    const res = await purgeAllTpPrograms({
      ...programListParams(),
      permanent: purgePermanent.value,
      confirm_phrase: purgeConfirmPhrase.value,
      expected_count: listMeta.value.total ?? 0,
    })
    const n = res.deleted_count ?? 0
    showAppSuccess(
      purgePermanent.value
        ? t('tp_programs_page.purge_all_done_permanent', { n })
        : t('tp_programs_page.purge_all_done_soft', { n }),
    )
    purgeAllOpen.value = false
    purgeConfirmPhrase.value = ''
    selectedProgramIds.value = []
    await load()
  } catch (err) {
    const retry = err?.response?.headers?.['retry-after'] ?? err?.response?.data?.retry_after
    if (err?.response?.status === 429 && retry) {
      startPurgeCooldown(Number(retry))
    }
    showAppErrorFromApi(err)
  } finally {
    bulkSubmitting.value = false
  }
}

async function exportProgramList() {
  if (exporting.value) return
  exporting.value = true
  try {
    await exportProgramsList(programListParams())
    showAppSuccess(t('tp_programs_page.export_done'))
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    exporting.value = false
  }
}

async function downloadProgramSample() {
  try {
    await downloadProgramImportSample()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

function triggerProgramImport() {
  programImportInputRef.value?.click()
}

async function onProgramImportFile(ev) {
  const file = ev.target?.files?.[0]
  ev.target.value = ''
  if (!file) return
  bulkSubmitting.value = true
  try {
    const res = await importProgramsFile(file)
    const created = res.created ?? 0
    const skipped = res.skipped ?? 0
    const errCount = (res.errors ?? []).length
    showAppSuccess(t('tp_programs_page.import_done', { created, skipped, errors: errCount }))
    await load()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    bulkSubmitting.value = false
  }
}

const LIST_PER_PAGE_OPTIONS = [5, 10, 15, 20]
const listPage = ref(1)
const listPerPage = ref(10)
let searchTimer = null

async function load() {
  loading.value = true
  try {
    const res = await listPrograms({
      ...programListParams(),
      per_page: 100,
    })
    items.value = res?.items ?? []
    listMeta.value = { total: res?.meta?.total ?? items.value.length }
    if (res?.summary) {
      kpiStats.value = {
        total: res.summary.total ?? 0,
        by_status: res.summary.by_status ?? {},
        operating_days: res.summary.operating_days ?? 0,
      }
    }
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(() => filters.status, load)
watch(() => filters.route, load)
watch(() => filters.schoolYear, load)
watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(load, 350)
  },
)

function onKpiQuickFilter(payload) {
  if (payload.kind === 'reset') {
    filters.status = ''
  } else if (payload.kind === 'status') {
    filters.status = payload.value === filters.status ? '' : payload.value
  }
  load()
}

function resetFilters() {
  filters.search = ''
  filters.status = ''
  filters.schoolYear = ''
  filters.route = ''
  load()
}

function schoolYear(p) {
  const s = p.start_date ? new Date(p.start_date).getFullYear() : null
  const e = p.end_date ? new Date(p.end_date).getFullYear() : null
  if (!s) return '—'
  return s === e || !e ? String(s) : `${s}–${e}`
}

function runsPerWeek(p) {
  return Array.isArray(p.runs_on) ? p.runs_on.length : 0
}

function timeRange(p) {
  const fmt = (time) => (time ? String(time).slice(0, 5) : null)
  const a = fmt(p.departure_time)
  const b = fmt(p.return_time)
  if (a && b) return `${a} – ${b}`
  return a || t('tp_programs_page.card_time_empty')
}

function progress(p) {
  if (p.status === 'completed') return 100
  if (p.status === 'draft' || p.status === 'cancelled') return 0
  if (!p.start_date || !p.end_date) return 0
  const start = new Date(p.start_date).getTime()
  const end = new Date(p.end_date).getTime()
  const now = Date.now()
  if (now <= start) return 0
  if (now >= end) return 100
  return Math.round(((now - start) / (end - start)) * 100)
}

function initials(name) {
  if (!name) return '?'
  return name
    .trim()
    .split(/\s+/)
    .slice(-2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
}

const schoolYearOptions = computed(() => {
  const set = new Set(items.value.map((p) => schoolYear(p)).filter((y) => y && y !== '—'))
  return [...set].sort().reverse()
})

const routeOptions = computed(() => {
  const set = new Set(items.value.map((p) => p.destination_name).filter(Boolean))
  return [...set].sort()
})

const visibleItems = computed(() => {
  let rows = [...items.value]
  switch (sort.value) {
    case 'oldest':
      rows.sort((a, b) => a.id - b.id)
      break
    case 'name':
      rows.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'vi'))
      break
    case 'students':
      rows.sort((a, b) => (b.enrolled_count ?? 0) - (a.enrolled_count ?? 0))
      break
    default:
      rows.sort((a, b) => b.id - a.id)
  }
  return rows
})

const listTotal = computed(() => visibleItems.value.length)

const listLastPage = computed(() => Math.max(1, Math.ceil(listTotal.value / listPerPage.value)))

const pagedListItems = computed(() => {
  const start = (listPage.value - 1) * listPerPage.value
  return visibleItems.value.slice(start, start + listPerPage.value)
})

const listRangeStart = computed(() => (listTotal.value ? (listPage.value - 1) * listPerPage.value + 1 : 0))

const listRangeEnd = computed(() =>
  listTotal.value ? Math.min(listPage.value * listPerPage.value, listTotal.value) : 0,
)

const listRangeText = computed(() =>
  listTotal.value
    ? t('tp_programs_page.list_range', {
        from: listRangeStart.value,
        to: listRangeEnd.value,
        total: listTotal.value,
      })
    : '',
)

const listSummaryText = computed(() => {
  const n = visibleItems.value.length
  if (view.value === 'list' && n > 0) {
    return listRangeText.value
  }
  return t('tp_programs_page.list_summary', { n })
})

function changeListPage(p) {
  listPage.value = Math.min(Math.max(1, p), listLastPage.value)
}

watch([visibleItems, listPerPage], () => {
  listPage.value = 1
})

watch(listLastPage, (last) => {
  if (listPage.value > last) listPage.value = last
})

function goCreate() {
  router.push({ name: 'tpProgramCreate' })
}
function goWorkspace(id) {
  router.push({ name: 'tpProgramWorkspace', params: { id } })
}

function toggleProgramSelection(id, event) {
  event?.stopPropagation?.()
  const i = selectedProgramIds.value.indexOf(id)
  if (i === -1) selectedProgramIds.value = [...selectedProgramIds.value, id]
  else selectedProgramIds.value = selectedProgramIds.value.filter((x) => x !== id)
}

const visiblePageIds = computed(() => {
  if (view.value === 'list') return pagedListItems.value.map((p) => p.id)
  return visibleItems.value.map((p) => p.id)
})

const programsPageAllSelected = computed(() => {
  const ids = visiblePageIds.value
  return ids.length > 0 && ids.every((id) => selectedProgramIds.value.includes(id))
})

function toggleSelectAllProgramsPage(event) {
  const ids = visiblePageIds.value
  if (!ids.length) return
  if (event.target.checked) {
    selectedProgramIds.value = [...new Set([...selectedProgramIds.value, ...ids])]
  } else {
    selectedProgramIds.value = selectedProgramIds.value.filter((id) => !ids.includes(id))
  }
}

async function removeProgram(p, event) {
  event?.stopPropagation?.()
  const ok = await confirmAction({
    title: t('tp_programs_page.delete_title'),
    message: t('tp_programs_page.delete_message', { name: p.name }),
    confirmLabel: t('tp_programs_page.delete_confirm'),
    danger: true,
  })
  if (!ok) return
  try {
    await deleteProgram(p.id)
    selectedProgramIds.value = selectedProgramIds.value.filter((id) => id !== p.id)
    showAppSuccess(t('tp_programs_page.delete_success'))
    await load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

async function confirmBulkDeletePrograms() {
  const ids = [...selectedProgramIds.value]
  if (!ids.length || bulkDeleting.value) return
  const ok = await confirmAction({
    title: t('tp_programs_page.bulk_delete_title'),
    message: t('tp_programs_page.bulk_delete_message', { n: ids.length }),
    confirmLabel: t('tp_programs_page.delete_confirm'),
    danger: true,
  })
  if (!ok) return
  bulkDeleting.value = true
  try {
    const res = await bulkDeletePrograms(ids)
    const n = res?.deleted_count ?? ids.length
    selectedProgramIds.value = []
    showAppSuccess(t('tp_programs_page.bulk_delete_success', { n }))
    await load()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    bulkDeleting.value = false
  }
}

function statusLabel(s) {
  const key = {
    draft: 'status_draft',
    active: 'status_active',
    paused: 'status_paused',
    completed: 'status_completed',
    cancelled: 'status_cancelled',
  }[s]
  return key ? t(`tp_programs_page.${key}`) : s
}
function statusClass(s) {
  const base = 'inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium '
  return (
    base +
    ({
      draft: 'bg-slate-100 text-slate-600',
      active: 'bg-emerald-50 text-emerald-700',
      paused: 'bg-amber-50 text-amber-700',
      completed: 'bg-sky-50 text-sky-700',
      cancelled: 'bg-rose-50 text-rose-700',
    }[s] || 'bg-slate-100 text-slate-600')
  )
}

function statusClassCompact(s) {
  const base = 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium leading-none '
  return (
    base +
    ({
      draft: 'bg-slate-100 text-slate-600',
      active: 'bg-emerald-50 text-emerald-700',
      paused: 'bg-amber-50 text-amber-700',
      completed: 'bg-sky-50 text-sky-700',
      cancelled: 'bg-rose-50 text-rose-700',
    }[s] || 'bg-slate-100 text-slate-600')
  )
}
function accent(s) {
  return (
    {
      draft: { iconBg: 'bg-slate-100', iconText: 'text-slate-500', dot: 'bg-slate-400', bar: 'bg-slate-400' },
      active: { iconBg: 'bg-emerald-50', iconText: 'text-emerald-600', dot: 'bg-emerald-500', bar: 'bg-emerald-500' },
      paused: { iconBg: 'bg-amber-50', iconText: 'text-amber-600', dot: 'bg-amber-500', bar: 'bg-amber-500' },
      completed: { iconBg: 'bg-sky-50', iconText: 'text-sky-600', dot: 'bg-sky-500', bar: 'bg-sky-500' },
      cancelled: { iconBg: 'bg-rose-50', iconText: 'text-rose-600', dot: 'bg-rose-500', bar: 'bg-rose-500' },
    }[s] || { iconBg: 'bg-slate-100', iconText: 'text-slate-500', dot: 'bg-slate-400', bar: 'bg-slate-400' }
  )
}

onMounted(() => {
  document.addEventListener('mousedown', onDatagridDocMouseDown)
  load()
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDatagridDocMouseDown)
  clearPurgeCooldownTimer()
})
onActivated(load)
</script>
