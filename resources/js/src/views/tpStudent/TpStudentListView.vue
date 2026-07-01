<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">{{ t('tp_student_page.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('tp_student_page.subtitle') }}</p>
      </div>

      <div class="flex flex-wrap gap-2">
        <Button data-testid="tp-student-add" @click="openCreate">
          <PlusIcon class="h-4 w-4" /> {{ t('tp_student_page.btn_add') }}
        </Button>
      </div>
    </div>

    <TpStudentSummaryBar
      :stats="stats"
      :loading="loading"
      :transport-status="filters.transport_status"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="datagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="filters.search"
              input-id="tp-student-list-search"
              :placeholder="t('tp_student_page.search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              data-testid="tp-student-toolbar-search"
            />
          </div>

          <div class="flex shrink-0 flex-wrap items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('trips_page.filter_show_controls_title')"
              :hint="t('trips_page.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="tp-student-toolbar-filter"
                  @click="toggleFilterPanel"
                >
                  {{ t('tp_student_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'tp-stu-vis-' + fd.id" class="flex items-start gap-2">
                <input
                  :id="'tp-student-filter-vis-' + fd.id"
                  :checked="filterControlVisible[fd.id]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`tp-student-filter-vis-${fd.id}`"
                  @change="onToggleFilterControl(fd.id, $event.target.checked)"
                />
                <label
                  :for="'tp-student-filter-vis-' + fd.id"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <FilterVisibilityDropdown
              :open="showColPanelDd"
              :title="t('tp_student_page.column_visibility_title')"
              @close="closeColPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="columns"
                  :active="showColPanelDd"
                  test-id="tp-student-toolbar-columns"
                  @click="toggleColPanel"
                >
                  {{ t('tp_student_page.toolbar_columns') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="opt in columnToggleOptions" :key="'tp-stu-col-' + opt.id" class="flex items-start gap-2">
                <input
                  :id="`tp-stu-col-${opt.id}`"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :checked="colOn(opt.id)"
                  :data-testid="`tp-student-col-vis-${opt.id}`"
                  @change="setColumn(opt.id, $event.target.checked)"
                />
                <label :for="`tp-stu-col-${opt.id}`" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">
                  {{ opt.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <button
              v-if="selected.length"
              type="button"
              class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-800 shadow-sm transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200"
              :disabled="bulkDeleting"
              data-testid="tp-student-bulk-delete"
              @click="confirmBulkDeleteStudents"
            >
              {{ t('tp_student_page.bulk_delete', { n: selected.length }) }}
            </button>
          </div>

          <div class="ml-auto flex shrink-0 flex-wrap items-center gap-2">
            <div v-if="canManageStudentData" class="relative" data-tp-student-data-panel>
              <DatagridToolbarActionButton
                icon="data"
                :active="showDataMenu"
                test-id="tp-student-toolbar-data"
                @click="toggleDataMenu"
              >
                {{ t('tp_student_page.toolbar_data') }}
              </DatagridToolbarActionButton>
              <div
                v-if="showDataMenu"
                class="absolute right-0 top-[calc(100%+6px)] z-[110] min-w-[280px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
                @click.stop
              >
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800"
                  data-testid="tp-student-data-import-wizard"
                  @click="goImport(); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-slate-800 dark:text-slate-100">
                    {{ t('tp_student_page.data_menu_import') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
                    {{ t('tp_student_page.data_menu_import_hint') }}
                  </span>
                </button>
                <button
                  type="button"
                  class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                  data-testid="tp-student-data-import-sample"
                  @click="downloadStudentSample(); showDataMenu = false"
                >
                  {{ t('tp_student_page.data_menu_import_sample') }}
                </button>
                <div class="my-1 border-t border-slate-100 dark:border-slate-700" role="separator" />
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-slate-800"
                  :disabled="exporting || !(meta.total ?? 0)"
                  data-testid="tp-student-data-export"
                  @click="exportList(); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-slate-800 dark:text-slate-100">
                    {{ t('tp_student_page.data_menu_export') }}
                  </span>
                </button>
                <div class="my-1 border-t border-slate-100 dark:border-slate-700" role="separator" />
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-rose-950/30"
                  :disabled="!(meta.total ?? 0) || bulkSubmitting"
                  data-testid="tp-student-data-purge-soft"
                  @click="openPurgeAll(false); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-rose-900 dark:text-rose-200">
                    {{ t('tp_student_page.purge_all_soft') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-rose-700/80 dark:text-rose-300/80">
                    {{ t('tp_student_page.purge_all_soft_hint', { n: meta.total ?? 0 }) }}
                  </span>
                </button>
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-red-950/30"
                  :disabled="!(meta.total ?? 0) || bulkSubmitting"
                  data-testid="tp-student-data-purge-permanent"
                  @click="openPurgeAll(true); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-red-950 dark:text-red-200">
                    {{ t('tp_student_page.purge_all_permanent') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-red-800/80 dark:text-red-300/80">
                    {{ t('tp_student_page.purge_all_permanent_hint', { n: meta.total ?? 0 }) }}
                  </span>
                </button>
              </div>
            </div>

            <div v-else ref="exportMenuRef" class="relative">
              <DatagridToolbarActionButton
                icon="export"
                :active="showExportMenu"
                :disabled="exporting"
                test-id="tp-student-toolbar-export"
                @click="toggleExportMenu"
              >
                {{ t('tp_student_page.toolbar_export') }}
              </DatagridToolbarActionButton>
              <div
                v-if="showExportMenu"
                class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[200px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
                @click.stop
              >
                <button
                  type="button"
                  class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                  data-testid="tp-student-export-xlsx"
                  :disabled="exporting"
                  @click="exportList"
                >
                  {{ exporting ? t('tp_student_page.btn_export_busy') : t('tp_student_page.btn_export') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <p v-if="paginationTotal" class="mt-2 text-xs text-slate-500">
          {{ t('tp_student_page.results_count', { from: pageFrom, to: pageTo, total: formatInt(paginationTotal) }) }}
          <span class="mx-1 text-slate-300">·</span>
          {{ t('tp_student_page.results_system_total', { total: formatInt(stats.total) }) }}
        </p>
      </div>

      <TpStudentListFilters
        :filters="filters"
        :filter-control-visible="filterControlVisible"
        :filter-options="filterOptions"
        :has-visible-bar-filters="hasVisibleBarFilters"
        :transport-status-options="transportStatusOptions"
        :student-status-options="studentStatusOptions"
        @patch-filter="onPatchFilter"
      />

    <!-- Table -->
    <div v-if="loading" class="flex items-center justify-center px-4 py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" aria-hidden="true" /> {{ t('tp_student_page.loading') }}
    </div>
    <div v-else-if="!items.length" class="px-4 py-16 text-center text-sm text-slate-500">
      {{ t('tp_student_page.empty') }}
    </div>
    <div v-else class="overflow-x-auto overscroll-x-contain">
      <table class="w-full min-w-[60rem] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 px-3 py-3">
              <input type="checkbox" :checked="allSelected" class="h-4 w-4 rounded border-slate-300 accent-va-800" @change="toggleAll" />
            </th>
            <th class="w-12 px-3 py-3 font-semibold">#</th>
            <th class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_student') }}</th>
            <th v-if="colOn('code')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_code') }}</th>
            <th v-if="colOn('gender')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_gender') }}</th>
            <th v-if="colOn('date_of_birth')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_dob') }}</th>
            <th v-if="colOn('grade')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_grade') }}</th>
            <th v-if="colOn('class_name')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_class') }}</th>
            <th v-if="colOn('parent_contact')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_parent') }}</th>
            <th v-if="colOn('father')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_father') }}</th>
            <th v-if="colOn('mother')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_mother') }}</th>
            <th v-if="colOn('address')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_address') }}</th>
            <th v-if="colOn('pickup_point')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_pickup') }}</th>
            <th v-if="colOn('note')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_note') }}</th>
            <th v-if="colOn('transport_status')" class="px-3 py-3 font-semibold">{{ t('tp_student_page.col_transport_status') }}</th>
            <th class="w-16 px-3 py-3 text-right font-semibold">{{ t('tp_student_page.col_actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template v-for="group in itemGroups" :key="'grp-block-' + group.key">
            <tr
              class="border-y border-slate-200/90 bg-gradient-to-r from-slate-50/95 via-slate-50/70 to-white dark:border-slate-600 dark:from-slate-800/60 dark:via-slate-800/40 dark:to-slate-900/20"
              :data-testid="`tp-student-group-row-${group.key}`"
            >
              <td :colspan="tableColSpan" class="p-0">
                <button
                  type="button"
                  class="flex w-full min-w-0 items-center gap-3 px-4 py-3 text-left transition hover:bg-va-50/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-va-700/25 dark:hover:bg-va-950/20 sm:px-5"
                  :aria-expanded="isGroupOpen(group.key)"
                  :aria-label="t('tp_student_page.group_toggle_aria', { name: groupTitle(group) })"
                  :data-testid="`tp-student-group-toggle-${group.key}`"
                  @click="toggleGroup(group.key)"
                >
                  <ChevronRightIcon
                    class="h-4 w-4 shrink-0 text-va-800 transition"
                    :class="{ 'rotate-90': isGroupOpen(group.key) }"
                    aria-hidden="true"
                  />
                  <span class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900 dark:text-white">
                    {{ groupTitle(group) }}
                  </span>
                  <span
                    v-if="group.program"
                    class="hidden shrink-0 text-xs font-medium sm:inline"
                    :class="programSubClass(group.program.status)"
                  >
                    {{ programSubLabel(group.program) }}
                  </span>
                  <span
                    class="shrink-0 rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold tabular-nums text-slate-600 ring-1 ring-slate-200/80 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-600"
                  >
                    {{ t('tp_student_page.group_count', { count: group.items.length }) }}
                  </span>
                </button>
              </td>
            </tr>
            <template v-if="isGroupOpen(group.key)">
              <tr v-for="(s, gi) in group.items" :key="s.id" class="transition hover:bg-slate-50/70">
                <td class="px-3 py-3">
                  <input type="checkbox" :value="s.id" v-model="selected" class="h-4 w-4 rounded border-slate-300 accent-va-800" />
                </td>
                <td class="px-3 py-3 text-xs font-medium text-slate-400">{{ rowNumber(group.startIndex + gi) }}</td>

                <td class="px-3 py-3">
                  <div class="flex items-center gap-3">
                    <span
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                      :style="{ backgroundColor: avatarColor(s.full_name) }"
                    >{{ initials(s.full_name) }}</span>
                    <div class="min-w-0">
                      <button
                        type="button"
                        class="max-w-full truncate rounded font-semibold text-left text-slate-900 hover:text-teal-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/40"
                        :aria-label="t('tp_attendance_page.student_detail_open', { name: s.full_name })"
                        @click="openStudentDetail(s)"
                      >
                        {{ s.full_name }}
                      </button>
                      <div class="truncate text-xs text-slate-400">{{ studentMeta(s) || t('tp_student_page.empty_not_available') }}</div>
                    </div>
                  </div>
                </td>

                <td v-if="colOn('code')" class="px-3 py-3 font-mono text-xs" :class="cellValueClass(s.code)">{{ fieldText(s.code, 'code') }}</td>
                <td v-if="colOn('gender')" class="px-3 py-3" :class="cellValueClass(s.gender)">{{ fieldText(s.gender, 'gender', genderLabel(s.gender)) }}</td>
                <td v-if="colOn('date_of_birth')" class="px-3 py-3 tabular-nums" :class="cellValueClass(s.date_of_birth)">{{ formatDob(s.date_of_birth) }}</td>
                <td v-if="colOn('grade')" class="px-3 py-3 font-medium" :class="cellValueClass(s.grade)">{{ fieldText(s.grade, 'grade') }}</td>
                <td v-if="colOn('class_name')" class="px-3 py-3 font-medium" :class="cellValueClass(s.class_name)">{{ fieldText(s.class_name, 'class') }}</td>

                <td v-if="colOn('parent_contact')" class="px-3 py-3">
                  <div class="font-medium" :class="cellValueClass(s.parent_name)">{{ fieldText(s.parent_name, 'parent_name') }}</div>
                  <a v-if="s.parent_phone" :href="`tel:${s.parent_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.parent_phone }}</a>
                  <span v-else class="text-xs italic text-slate-400">{{ t('tp_student_page.empty_phone') }}</span>
                </td>
                <td v-if="colOn('father')" class="px-3 py-3">
                  <div :class="cellValueClass(s.father_name)">{{ fieldText(s.father_name, 'father_name') }}</div>
                  <a v-if="s.father_phone" :href="`tel:${s.father_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.father_phone }}</a>
                  <span v-else class="text-xs italic text-slate-400">{{ t('tp_student_page.empty_phone') }}</span>
                </td>
                <td v-if="colOn('mother')" class="px-3 py-3">
                  <div :class="cellValueClass(s.mother_name)">{{ fieldText(s.mother_name, 'mother_name') }}</div>
                  <a v-if="s.mother_phone" :href="`tel:${s.mother_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.mother_phone }}</a>
                  <span v-else class="text-xs italic text-slate-400">{{ t('tp_student_page.empty_phone') }}</span>
                </td>
                <td v-if="colOn('address')" class="max-w-[14rem] px-3 py-3">
                  <span class="line-clamp-2" :class="cellValueClass(s.address)">{{ fieldText(s.address, 'address') }}</span>
                </td>
                <td v-if="colOn('pickup_point')" class="max-w-[12rem] px-3 py-3">
                  <span class="line-clamp-2" :class="cellValueClass(s.pickup_point)">{{ fieldText(s.pickup_point, 'pickup') }}</span>
                </td>
                <td v-if="colOn('note')" class="max-w-[12rem] px-3 py-3 text-xs">
                  <span class="line-clamp-2" :class="cellValueClass(s.note)">{{ fieldText(s.note, 'note') }}</span>
                </td>

                <td v-if="colOn('transport_status')" class="px-3 py-3">
                  <span :class="transportBadgeClass(s.transport_status)">
                    <span class="h-1.5 w-1.5 rounded-full" :class="transportDotClass(s.transport_status)"></span>
                    {{ transportLabel(s.transport_status) }}
                  </span>
                </td>

                <td class="px-3 py-3 text-right">
                  <AppRowActionsMenu :aria-label="t('tp_student_page.row_actions_aria', { name: s.full_name })">
                    <button class="menu-item" data-testid="tp-student-action-edit" @click="openEdit(s)"><PencilSquareIcon class="h-4 w-4" /> {{ t('tp_student_page.action_edit') }}</button>
                    <button class="menu-item" data-testid="tp-student-action-enroll" @click="goEnroll(s)"><AcademicCapIcon class="h-4 w-4" /> {{ t('tp_student_page.action_enroll') }}</button>
                    <button class="menu-item text-rose-600" data-testid="tp-student-action-delete" @click="remove(s)"><TrashIcon class="h-4 w-4" /> {{ t('tp_student_page.action_delete') }}</button>
                  </AppRowActionsMenu>
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </table>

    </div>

    <nav
      v-if="!loading && paginationTotal > 0"
      class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-5"
      :aria-label="t('tp_student_page.pagination_aria')"
    >
      <p class="text-sm text-slate-600">
        {{ t('tp_student_page.pagination_showing', {
          from: pageFrom,
          to: pageTo,
          total: formatInt(paginationTotal),
          page: meta.current_page,
          last: meta.last_page,
        }) }}
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <Button variant="secondary" data-testid="tp-student-page-prev" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">
          {{ t('tp_student_page.pagination_prev') }}
        </Button>
        <div class="flex items-center gap-1">
          <button
            v-for="p in pageNumbers"
            :key="p"
            type="button"
            class="min-w-[2.25rem] rounded-lg px-2 py-1.5 text-sm tabular-nums transition"
            :class="
              p === meta.current_page
                ? 'bg-va-800 font-semibold text-white shadow-sm'
                : 'text-slate-600 hover:bg-slate-100'
            "
            :aria-current="p === meta.current_page ? 'page' : undefined"
            :data-testid="`tp-student-page-${p}`"
            @click="changePage(p)"
          >
            {{ p }}
          </button>
        </div>
        <Button variant="secondary" data-testid="tp-student-page-next" :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)">
          {{ t('tp_student_page.pagination_next') }}
        </Button>
      </div>
    </nav>
    </div>

    <TpStudentFormModal v-if="showForm" :student="editing" @close="showForm = false" @saved="onSaved" />

    <TpStudentDetailModal
      :open="studentDetailOpen"
      :student-id="studentDetailId"
      @close="closeStudentDetail"
    />

    <div
      v-if="purgeAllOpen"
      class="fixed inset-0 z-[200] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="tp-student-purge-all-title"
      data-testid="tp-student-purge-all-modal"
      @click.self="closePurgeAll"
    >
      <div class="absolute inset-0 bg-slate-900/50" aria-hidden="true" />
      <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
        <h2 id="tp-student-purge-all-title" class="text-base font-semibold text-slate-900 dark:text-white">
          {{ t('tp_student_page.purge_all_modal_title') }}
        </h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ t('tp_student_page.purge_all_modal_lead') }}</p>
        <p class="mt-2 text-sm font-medium text-slate-800 dark:text-slate-200">
          {{
            purgePermanent
              ? t('tp_student_page.purge_all_mode_permanent')
              : t('tp_student_page.purge_all_mode_soft')
          }}
        </p>
        <label for="tp-student-purge-confirm" class="mt-4 block text-xs font-medium text-slate-700 dark:text-slate-300">
          {{ t('tp_student_page.purge_all_confirm_label') }}
        </label>
        <p class="mt-1 text-[11px] text-slate-500">{{ t('tp_student_page.purge_all_confirm_hint', { phrase: purgeRequiredPhrase }) }}</p>
        <p
          v-if="purgeCooldownSeconds > 0"
          class="mt-2 text-xs text-amber-700"
          data-testid="tp-student-purge-rate-limit"
        >
          {{ t('tp_student_page.purge_all_rate_limit_wait', { seconds: purgeCooldownSeconds }) }}
        </p>
        <input
          id="tp-student-purge-confirm"
          v-model="purgeConfirmPhrase"
          type="text"
          autocomplete="off"
          class="input mt-2 h-10 w-full text-sm"
          data-testid="tp-student-purge-confirm-input"
        />
        <div class="mt-5 flex justify-end gap-2">
          <Button variant="secondary" data-testid="tp-student-purge-cancel" @click="closePurgeAll">
            {{ t('common.cancel') }}
          </Button>
          <Button
            variant="danger"
            :disabled="
              bulkSubmitting ||
              purgeCooldownSeconds > 0 ||
              purgeConfirmPhrase !== purgeRequiredPhrase
            "
            data-testid="tp-student-purge-submit"
            @click="submitPurgeAll"
          >
            {{
              bulkSubmitting
                ? t('tp_student_page.purge_all_submitting')
                : t('tp_student_page.purge_all_submit', { n: meta.total ?? 0 })
            }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  PlusIcon, ArrowPathIcon,
  PencilSquareIcon,
  TrashIcon, AcademicCapIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import TpStudentSummaryBar from '../../components/transportProgram/TpStudentSummaryBar.vue'
import TpStudentListFilters from '../../components/transportProgram/TpStudentListFilters.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useExportDetailsMenu } from '../../composables/useExportDetailsMenu.js'
import { useTpStudentListColumns, TP_STUDENT_COL_DEFAULTS } from '../../composables/useTpStudentListColumns.js'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import TpStudentFormModal from '../../components/transportProgram/TpStudentFormModal.vue'
import TpStudentDetailModal from '../../components/transportProgram/TpStudentDetailModal.vue'
import { listStudents, getStudent, deleteStudent, bulkDeleteStudents, exportStudentsList, purgeAllTpStudents, downloadImportSample } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { useAuthStore } from '../../store'

const router = useRouter()
const { t } = useI18n()
const auth = useAuthStore()
const canManageStudentData = computed(() => auth.hasPermission('tp_student.manage'))
const loading = ref(false)
const exporting = ref(false)
const items = ref([])
const selected = ref([])
const bulkDeleting = ref(false)
const bulkSubmitting = ref(false)
const showDataMenu = ref(false)
const purgeAllOpen = ref(false)
const purgePermanent = ref(false)
const purgeConfirmPhrase = ref('')
const purgeCooldownSeconds = ref(0)
let purgeCooldownTimer = null
const showForm = ref(false)
const editing = ref(null)
const studentDetailOpen = ref(false)
const studentDetailId = ref(null)

function openStudentDetail(s) {
  studentDetailId.value = s?.id ?? null
  studentDetailOpen.value = true
}

function closeStudentDetail() {
  studentDetailOpen.value = false
  studentDetailId.value = null
}
const stats = reactive({ total: 0, transporting: 0, pending: 0, unregistered: 0 })
const filterOptions = reactive({ grades: [], classes: [], programs: [], pickup_points: [], genders: [] })
const meta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const DEFAULT_PER_PAGE = 15
const filters = reactive({
  search: '',
  class_name: '',
  transport_status: '',
  program_id: '',
  grade: '',
  student_status: '',
  gender: '',
  pickup_point: '',
  parent_phone: '',
  address_contains: '',
  per_page: DEFAULT_PER_PAGE,
  page: 1,
})
let timer = null

const TP_FILTER_CONTROL_IDS = [
  'class_name',
  'transport_status',
  'program_id',
  'grade',
  'student_status',
  'gender',
  'pickup_point',
  'parent_phone',
  'address_contains',
]
const TP_FILTER_VIS_KEY = 'tp-student-list-filter-vis.v1'

function loadFilterControlVisibility() {
  const defaults = {
    class_name: false,
    transport_status: false,
    program_id: false,
    grade: false,
    student_status: false,
    gender: true,
    pickup_point: true,
    parent_phone: false,
    address_contains: false,
  }
  try {
    const raw = localStorage.getItem(TP_FILTER_VIS_KEY)
    if (raw) {
      return { ...defaults, ...JSON.parse(raw) }
    }
  } catch {
    /* ignore */
  }
  return { ...defaults }
}

const { colOn, setColumn, columnToggleOptions } = useTpStudentListColumns()
const filterControlVisible = reactive(loadFilterControlVisibility())
const showFilterPanelDd = ref(false)
const showColPanelDd = ref(false)
const { exportMenuRef, showExportMenu, toggleExportMenu, closeExportMenu } = useExportDetailsMenu()
const datagridRef = ref(null)
useDetailsAutoCloseWithin(datagridRef)

const purgeRequiredPhrase = computed(() => `XOA ${meta.total ?? 0}`)

function studentListFilterParams() {
  return {
    search: filters.search || undefined,
    class_name: filters.class_name || undefined,
    transport_status: filters.transport_status || undefined,
    program_id: filters.program_id || undefined,
    grade: filters.grade || undefined,
    status: filters.student_status || undefined,
    gender: filters.gender || undefined,
    pickup_point: filters.pickup_point || undefined,
    parent_phone: filters.parent_phone?.trim() || undefined,
    address_contains: filters.address_contains?.trim() || undefined,
  }
}

function closeDataMenu() {
  showDataMenu.value = false
}

function toggleDataMenu() {
  closeFilterPanel()
  closeColPanel()
  closeExportMenu()
  showDataMenu.value = !showDataMenu.value
}

function onDatagridDocMouseDown(ev) {
  const t = ev.target
  if (!(t instanceof Element)) return
  if (t.closest('[data-tp-student-data-panel]')) return
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
    const res = await purgeAllTpStudents({
      ...studentListFilterParams(),
      permanent: purgePermanent.value,
      confirm_phrase: purgeConfirmPhrase.value,
      expected_count: meta.total ?? 0,
    })
    const n = res.deleted_count ?? 0
    showAppSuccess(
      purgePermanent.value
        ? t('tp_student_page.purge_all_done_permanent', { n })
        : t('tp_student_page.purge_all_done_soft', { n }),
    )
    purgeAllOpen.value = false
    purgeConfirmPhrase.value = ''
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

async function downloadStudentSample() {
  try {
    await downloadImportSample()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

const filterControlDefs = computed(() => [
  { id: 'class_name', label: t('tp_student_page.filter_vis_class_name') },
  { id: 'transport_status', label: t('tp_student_page.filter_vis_transport_status') },
  { id: 'program_id', label: t('tp_student_page.filter_vis_program_id') },
  { id: 'grade', label: t('tp_student_page.filter_vis_grade') },
  { id: 'student_status', label: t('tp_student_page.filter_vis_student_status') },
  { id: 'gender', label: t('tp_student_page.filter_vis_gender') },
  { id: 'pickup_point', label: t('tp_student_page.filter_vis_pickup_point') },
  { id: 'parent_phone', label: t('tp_student_page.filter_vis_parent_phone') },
  { id: 'address_contains', label: t('tp_student_page.filter_vis_address_contains') },
])

const transportStatusOptions = computed(() => [
  { value: 'transporting', label: t('tp_student_page.transport_transporting') },
  { value: 'pending', label: t('tp_student_page.transport_pending') },
  { value: 'paused', label: t('tp_student_page.transport_paused') },
  { value: 'unregistered', label: t('tp_student_page.transport_unregistered') },
])

const studentStatusOptions = computed(() => [
  { value: 'active', label: t('tp_student_page.student_status_active') },
  { value: 'inactive', label: t('tp_student_page.student_status_inactive') },
  { value: 'transferred', label: t('tp_student_page.student_status_transferred') },
  { value: 'graduated', label: t('tp_student_page.student_status_graduated') },
])

const hasVisibleBarFilters = computed(() =>
  TP_FILTER_CONTROL_IDS.some((id) => filterControlVisible[id] === true),
)

const NONE_PROGRAM_GROUP_KEY = '__none__'
const openGroups = reactive({})

const itemGroups = computed(() => {
  const map = new Map()
  for (const s of items.value) {
    const key = s.program?.id ?? NONE_PROGRAM_GROUP_KEY
    if (!map.has(key)) {
      map.set(key, { key, program: s.program ?? null, items: [] })
    }
    map.get(key).items.push(s)
  }
  const groups = [...map.values()]
  groups.sort((a, b) => {
    if (a.key === NONE_PROGRAM_GROUP_KEY) return 1
    if (b.key === NONE_PROGRAM_GROUP_KEY) return -1
    return String(a.program?.name ?? '').localeCompare(String(b.program?.name ?? ''), 'vi')
  })
  let idx = 0
  for (const g of groups) {
    g.startIndex = idx
    idx += g.items.length
  }
  return groups
})

const tableColSpan = computed(() => {
  let n = 4
  for (const id of Object.keys(TP_STUDENT_COL_DEFAULTS)) {
    if (colOn(id)) n += 1
  }
  return n
})

watch(
  itemGroups,
  (groups) => {
    for (const g of groups) {
      if (!(g.key in openGroups)) openGroups[g.key] = true
    }
  },
  { immediate: true },
)

function isGroupOpen(key) {
  return openGroups[key] !== false
}

function toggleGroup(key) {
  openGroups[key] = !isGroupOpen(key)
}

function groupTitle(group) {
  if (group.program?.name) return group.program.name
  return t('tp_student_page.no_program')
}

function onPatchFilter(patch) {
  Object.assign(filters, patch)
  if ('parent_phone' in patch || 'address_contains' in patch) {
    clearTimeout(timer)
    filters.page = 1
    timer = setTimeout(load, 300)
    return
  }
  filters.page = 1
  load()
}

function onKpiQuickFilter({ transport_status }) {
  filters.transport_status = transport_status ?? ''
  filters.page = 1
  load()
}

function toggleFilterPanel() {
  showColPanelDd.value = false
  showFilterPanelDd.value = !showFilterPanelDd.value
}

function closeFilterPanel() {
  showFilterPanelDd.value = false
}

function toggleColPanel() {
  showFilterPanelDd.value = false
  showColPanelDd.value = !showColPanelDd.value
}

function closeColPanel() {
  showColPanelDd.value = false
}

function onToggleFilterControl(id, checked) {
  filterControlVisible[id] = checked
  try {
    localStorage.setItem(TP_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
  } catch {
    /* ignore */
  }
}
const allSelected = computed(() => items.value.length > 0 && selected.value.length === items.value.length)

const paginationTotal = computed(() => meta.total ?? 0)

const pageFrom = computed(() => {
  const total = paginationTotal.value
  if (total === 0) return 0
  const cur = meta.current_page ?? 1
  const per = meta.per_page ?? filters.per_page
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const total = paginationTotal.value
  if (total === 0) return 0
  const cur = meta.current_page ?? 1
  const per = meta.per_page ?? filters.per_page
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.last_page ?? 1
  const cur = meta.current_page ?? 1
  const window = 5
  let start = Math.max(1, cur - Math.floor(window / 2))
  let end = Math.min(last, start + window - 1)
  start = Math.max(1, end - window + 1)
  const list = []
  for (let p = start; p <= end; p++) list.push(p)
  return list
})

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

async function load() {
  loading.value = true
  try {
    const res = await listStudents({
      ...studentListFilterParams(),
      per_page: filters.per_page,
      page: filters.page,
    })
    items.value = res?.items ?? []
    Object.assign(stats, res?.stats ?? {})
    Object.assign(filterOptions, res?.filter_options ?? {})
    Object.assign(meta, res?.meta ?? {})
    if (
      meta.last_page > 0 &&
      meta.current_page > meta.last_page &&
      filters.page !== meta.last_page
    ) {
      filters.page = meta.last_page
      return load()
    }
    selected.value = []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(
  () => [
    filters.class_name,
    filters.transport_status,
    filters.program_id,
    filters.grade,
    filters.student_status,
    filters.gender,
    filters.pickup_point,
  ],
  () => {
    filters.page = 1
    load()
  },
)
watch(
  () => [filters.parent_phone, filters.address_contains],
  () => {
    clearTimeout(timer)
    filters.page = 1
    timer = setTimeout(load, 300)
  },
)
watch(() => filters.search, () => {
  clearTimeout(timer)
  filters.page = 1
  timer = setTimeout(load, 300)
})

function changePage(p) {
  const last = meta.last_page ?? 1
  const next = Math.min(Math.max(1, p), last)
  if (next === filters.page) return
  filters.page = next
  load()
}
function clearFilters() {
  filters.search = ''
  filters.class_name = ''
  filters.transport_status = ''
  filters.program_id = ''
  filters.grade = ''
  filters.student_status = ''
  filters.gender = ''
  filters.pickup_point = ''
  filters.parent_phone = ''
  filters.address_contains = ''
  filters.per_page = DEFAULT_PER_PAGE
  filters.page = 1
  showFilterPanelDd.value = false
  load()
}
function toggleAll(e) {
  selected.value = e.target.checked ? items.value.map((s) => s.id) : []
}
function rowNumber(i) {
  return String((meta.current_page - 1) * meta.per_page + i + 1).padStart(3, '0')
}

function openCreate() {
  editing.value = null
  showForm.value = true
}
async function openEdit(s) {
  try {
    editing.value = await getStudent(s.id)
  } catch {
    editing.value = { ...s }
  }
  showForm.value = true
}
function onSaved() {
  showForm.value = false
  load()
}
function goImport() {
  router.push({ name: 'tpImportWizard' })
}
function goEnroll(s) {
  if (s.program?.id) router.push({ name: 'tpEnrollStudents', params: { id: s.program.id } })
  else router.push({ name: 'tpPrograms' })
}
async function exportList() {
  if (exporting.value) return
  closeExportMenu()
  exporting.value = true
  try {
    await exportStudentsList(studentListFilterParams())
    showAppSuccess(t('tp_student_page.export_success'))
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    exporting.value = false
  }
}

async function remove(s) {
  const ok = await confirmAction({
    title: t('tp_student_page.delete_title'),
    message: t('tp_student_page.delete_message', { name: s.full_name }),
    danger: true,
    confirmLabel: t('tp_student_page.delete_confirm'),
  })
  if (!ok) return
  try {
    await deleteStudent(s.id)
    selected.value = selected.value.filter((id) => id !== s.id)
    showAppSuccess(t('tp_student_page.delete_success'))
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

async function confirmBulkDeleteStudents() {
  const ids = [...selected.value]
  if (!ids.length || bulkDeleting.value) return
  const ok = await confirmAction({
    title: t('tp_student_page.bulk_delete_title'),
    message: t('tp_student_page.bulk_delete_message', { n: ids.length }),
    confirmLabel: t('tp_student_page.delete_confirm'),
    danger: true,
  })
  if (!ok) return
  bulkDeleting.value = true
  try {
    const res = await bulkDeleteStudents(ids)
    const n = res?.deleted_count ?? ids.length
    selected.value = []
    showAppSuccess(t('tp_student_page.bulk_delete_success', { n }))
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    bulkDeleting.value = false
  }
}

// ── Presentation helpers ──────────────────────────────────────────────
function initials(name) {
  if (!name) return '?'
  const parts = String(name).trim().split(/\s+/)
  const last = parts[parts.length - 1]?.[0] ?? ''
  const first = parts.length > 1 ? parts[parts.length - 2]?.[0] ?? '' : parts[0]?.[1] ?? ''
  return (last + first).toUpperCase() || name[0].toUpperCase()
}
const AVATAR_COLORS = ['#2563eb', '#0891b2', '#7c3aed', '#db2777', '#ea580c', '#16a34a', '#0d9488', '#4f46e5']
function avatarColor(name) {
  let h = 0
  for (const ch of String(name || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0
  return AVATAR_COLORS[h % AVATAR_COLORS.length]
}
function studentMeta(s) {
  const parts = [s.code, genderLabel(s.gender), s.age ? `${s.age} tuổi` : null].filter(Boolean)
  return parts.join(' · ')
}
function genderLabel(g) {
  if (!g) return ''
  const key = String(g).toLowerCase()
  return { male: 'Nam', female: 'Nữ', other: 'Khác', nam: 'Nam', nu: 'Nữ' }[key] || g
}
function isFieldEmpty(value) {
  if (value == null) return true
  return String(value).trim() === ''
}
function emptyFieldLabel(key) {
  return t(`tp_student_page.empty_${key}`)
}
function fieldText(value, emptyKey, formatted) {
  if (isFieldEmpty(value)) return emptyFieldLabel(emptyKey)
  if (formatted != null && formatted !== '') return formatted
  return String(value).trim()
}
function cellValueClass(value) {
  return isFieldEmpty(value) ? 'text-xs italic text-slate-400' : 'text-slate-700'
}
function formatDob(iso) {
  if (!iso) return emptyFieldLabel('dob')
  const [y, m, d] = String(iso).slice(0, 10).split('-')
  if (!y || !m || !d) return iso
  return `${d}/${m}/${y}`
}
function formatDate(d) {
  if (!d) return ''
  const [y, m, day] = String(d).slice(0, 10).split('-')
  return `${day}/${m}/${y}`
}
function programSubLabel(p) {
  if (p.status === 'paused') return 'Tạm dừng'
  if (p.status === 'draft') return 'Chờ xác nhận'
  return p.start_date ? `Từ ${formatDate(p.start_date)}` : 'Đang hoạt động'
}
function programSubClass(status) {
  return { paused: 'text-rose-500', draft: 'text-amber-600' }[status] || 'text-slate-400'
}

function transportLabel(s) {
  const key = {
    transporting: 'tp_student_page.transport_transporting',
    pending: 'tp_student_page.transport_pending',
    paused: 'tp_student_page.transport_paused',
    unregistered: 'tp_student_page.transport_unregistered',
  }[s]
  return key ? t(key) : s
}
function transportBadgeClass(s) {
  const base = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium '
  return base + ({
    transporting: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    paused: 'bg-rose-50 text-rose-700',
    unregistered: 'bg-slate-100 text-slate-500',
  }[s] || 'bg-slate-100 text-slate-500')
}
function transportDotClass(s) {
  return {
    transporting: 'bg-emerald-500',
    pending: 'bg-amber-500',
    paused: 'bg-rose-500',
    unregistered: 'bg-slate-400',
  }[s] || 'bg-slate-400'
}

onMounted(() => {
  document.addEventListener('mousedown', onDatagridDocMouseDown)
  load()
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDatagridDocMouseDown)
  clearPurgeCooldownTimer()
})
</script>

<style scoped>
.menu-item {
  @apply flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50;
}
</style>
