<template>
  <div class="space-y-4" data-testid="trip-cost-control-center">
    <TripCostSummaryBar
      :revenue="fin.revenue.value"
      :budget="fin.budget.value"
      :actual-total="fin.actualTotal.value"
      :variance="fin.variance.value"
      :variance-pct="fin.variancePct.value"
      :profit="fin.profit.value"
      :profit-margin="fin.profitMargin.value"
      :pending-count="fin.pendingCount.value"
      :pending-total="fin.pendingTotal.value"
      :budget-used-pct="fin.budgetUsedPct.value"
      :currency="currency"
      :active-status-filter="gridStatusFilter"
      @quick-filter="onKpiQuickFilter"
    />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <!-- ─────────── SECTION 2 · HEALTH STATUS ─────────── -->
      <section
        class="rounded-2xl border p-4 shadow-sm"
        :class="healthCard.wrapClass"
        data-testid="financial-health"
      >
        <div class="flex items-center gap-2">
          <span class="text-lg" aria-hidden="true">{{ healthCard.emoji }}</span>
          <h3 class="text-sm font-bold" :class="healthCard.titleClass">{{ healthCard.title }}</h3>
        </div>
        <p class="mt-1.5 text-xs leading-relaxed" :class="healthCard.descClass">{{ healthCard.desc }}</p>
        <div v-if="fin.budgetUsedPct.value != null" class="mt-3">
          <div class="flex items-center justify-between text-[11px] font-semibold" :class="healthCard.descClass">
            <span>{{ t('cost_center.budget_used') }}</span>
            <span class="tabular-nums">{{ Math.round(fin.budgetUsedPct.value) }}%</span>
          </div>
          <div class="mt-1 h-2 overflow-hidden rounded-full bg-white/60 dark:bg-slate-900/60">
            <div
              class="h-full rounded-full transition-all"
              :class="healthCard.barClass"
              :style="{ width: `${Math.min(100, Math.max(2, fin.budgetUsedPct.value))}%` }"
            />
          </div>
        </div>
      </section>

      <!-- ─────────── SECTION 3 · COST BREAKDOWN ─────────── -->
      <section
        class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40 lg:col-span-2"
        data-testid="cost-breakdown"
      >
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
          {{ t('cost_center.breakdown_title') }}
        </h3>
        <div v-if="fin.breakdown.value.rows.length" class="mt-3 space-y-2.5">
          <div v-for="row in fin.breakdown.value.rows" :key="row.key" class="flex items-center gap-3 text-xs">
            <span class="flex w-28 shrink-0 items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
              <span class="h-2.5 w-2.5 shrink-0 rounded-sm" :class="row.dotClass" />
              <span class="truncate">{{ groupLabel(row.labelKey) }}</span>
            </span>
            <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
              <div class="h-full rounded-full" :class="row.barClass" :style="{ width: `${Math.max(3, row.pct)}%` }" />
            </div>
            <span class="w-9 shrink-0 text-right tabular-nums text-slate-500">{{ Math.round(row.pct) }}%</span>
            <span class="w-24 shrink-0 text-right font-semibold tabular-nums text-slate-800 dark:text-slate-200">
              {{ fmtMoney(row.amount) }}
            </span>
          </div>
        </div>
        <p v-else class="mt-4 text-sm italic text-slate-400">{{ t('cost_center.breakdown_empty') }}</p>
      </section>
    </div>

    <!-- ─────────── INSIGHTS ─────────── -->
    <section
      v-if="fin.insights.value.length"
      class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40"
      data-testid="cost-insights"
    >
      <h3 class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ t('cost_center.insights_title') }}</h3>
      <ul class="mt-2.5 space-y-1.5">
        <li
          v-for="ins in fin.insights.value"
          :key="ins.key"
          class="flex items-start gap-2 rounded-lg px-2.5 py-1.5 text-xs"
          :class="insightToneClass(ins.tone)"
        >
          <span class="mt-px shrink-0" aria-hidden="true">{{ insightIcon(ins.tone) }}</span>
          <span>{{ insightText(ins) }}</span>
        </li>
      </ul>
    </section>

    <!-- Datagrid -->
    <section
      class="overflow-visible rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/40"
      data-testid="cost-grid"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="gridSearch"
              input-id="trip-cost-grid-search"
              :placeholder="t('cost_center.search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
            />
          </div>

          <div class="flex shrink-0 flex-wrap items-center gap-2">
            <template v-if="selectedIds.size">
              <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ t('cost_center.selected_n', { n: selectedIds.size }) }}
              </span>
              <button
                v-if="canReconcile && selectedPendingCount"
                type="button"
                class="inline-flex h-10 items-center rounded-lg bg-emerald-600 px-2.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                :disabled="bulkBusy"
                data-testid="trip-cost-bulk-approve"
                @click="bulkDecide('confirm')"
              >
                {{ t('cost_center.bulk_approve') }} ({{ selectedPendingCount }})
              </button>
              <button
                v-if="canReconcile && selectedPendingCount"
                type="button"
                class="inline-flex h-10 items-center rounded-lg border border-rose-300 bg-white px-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
                :disabled="bulkBusy"
                data-testid="trip-cost-bulk-reject"
                @click="openReject(null)"
              >
                {{ t('cost_center.bulk_reject') }} ({{ selectedPendingCount }})
              </button>
            </template>

            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('cost_center.filter_show_controls_title')"
              :hint="t('cost_center.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="trip-cost-toolbar-filter"
                  @click="openFilterPanel()"
                >
                  {{ t('cost_center.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li
                v-for="fd in filterControlDefs"
                :key="'trip-cost-vis-' + fd.key"
                class="flex items-start gap-2"
              >
                <input
                  :id="`trip-cost-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`trip-cost-filter-vis-${fd.key}`"
                />
                <label
                  :for="`trip-cost-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <DatagridToolbarActionButton
              icon="export"
              test-id="trip-cost-toolbar-export"
              @click="exportCsv"
            >
              {{ t('cost_center.toolbar_export') }}
            </DatagridToolbarActionButton>
          </div>

          <div class="ml-auto flex shrink-0 items-center gap-2">
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
              :title="t('cost_center.filter_clear_all')"
              data-testid="trip-cost-reset-filters"
              @click="resetGridFilters"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
            <button
              v-if="canSubmit"
              type="button"
              class="inline-flex h-10 shrink-0 items-center rounded-lg px-3 text-xs font-semibold transition"
              :class="showQuickAdd ? 'border border-slate-200 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100' : 'bg-va-800 text-white hover:bg-va-900'"
              data-testid="cost-toggle-add"
              @click="toggleQuickAdd"
            >
              {{ showQuickAdd ? t('cost_center.add_cancel') : t('cost_center.add_cost') }}
            </button>
          </div>
        </div>

        <div
          v-if="hasFilterRow"
          class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
        >
          <DatagridFilterField v-if="visibleFilters.status">
            <select
              v-model="gridStatusFilter"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('cost_center.col_status')"
              data-testid="trip-cost-filter-status"
            >
              <option value="">{{ t('cost_center.col_status') }}</option>
              <option value="__pending__">{{ t('cost_center.kpi_pending') }}</option>
              <option value="confirmed">{{ t('trip_detail.costs.status_confirmed') }}</option>
              <option value="submitted">{{ t('trip_detail.costs.status_submitted') }}</option>
              <option value="rejected">{{ t('trip_detail.costs.status_rejected') }}</option>
              <option value="paid">{{ t('trip_detail.costs.status_paid') }}</option>
            </select>
          </DatagridFilterField>
          <DatagridFilterField v-if="visibleFilters.type">
            <select
              v-model="gridTypeFilter"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('cost_center.filter_type')"
              data-testid="trip-cost-filter-type"
            >
              <option value="">{{ t('cost_center.filter_type') }}</option>
              <option v-for="opt in QUICK_TYPES" :key="opt.value" :value="opt.value">
                {{ groupLabel(opt.labelKey) }}
              </option>
            </select>
          </DatagridFilterField>
        </div>
      </div>

      <p
        v-if="rows.length && !filteredGridRows.length"
        class="border-b border-slate-100 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400"
      >
        {{ t('cost_center.grid_filter_empty') }}
      </p>

      <!-- Quick add -->
      <Transition
        enter-active-class="transition-all duration-150 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition-all duration-100 ease-in"
        leave-to-class="opacity-0 -translate-y-1"
      >
        <div
          v-if="showQuickAdd && canSubmit"
          class="border-b border-blue-200/70 bg-blue-50/40 px-4 py-3 dark:border-blue-900/40 dark:bg-blue-950/20"
          @dragover.prevent
          @drop.prevent="onDropPending"
        >
          <div v-if="hasLegs" class="mb-3">
            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('cost_center.leg_label') }}</span>
            <select
              v-model="selectedLegKey"
              class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
              data-testid="cost-leg-select"
            >
              <option value="">{{ t('cost_center.leg_all') }}</option>
              <option v-for="l in legOptions" :key="l.key" :value="l.key">
                {{ l.label }}<template v-if="l.route"> · {{ l.route }}</template>
              </option>
            </select>
          </div>
          <div class="flex flex-wrap gap-1.5" role="radiogroup" :aria-label="t('cost_center.quick_type')">
            <button
              v-for="opt in QUICK_TYPES"
              :key="opt.value"
              type="button"
              role="radio"
              :aria-checked="selectedType === opt.value"
              class="rounded-full px-2.5 py-1 text-xs font-semibold transition"
              :class="selectedType === opt.value ? 'bg-blue-600 text-white ring-2 ring-blue-300' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600'"
              @click="selectedType = opt.value"
            >
              {{ groupLabel(opt.labelKey) }}
            </button>
          </div>
          <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(7rem,9rem)_1fr_auto]">
            <label class="block min-w-0">
              <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('cost_center.quick_amount') }}</span>
              <input
                ref="amountInputRef"
                v-model="amount"
                type="number"
                min="0"
                step="1"
                :placeholder="t('cost_center.quick_amount_ph')"
                class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm tabular-nums shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                data-testid="cost-amount-input"
              />
            </label>
            <label class="block min-w-0">
              <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('cost_center.quick_desc') }}</span>
              <input
                v-model="description"
                type="text"
                :placeholder="t('cost_center.quick_desc_ph')"
                class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                data-testid="cost-desc-input"
              />
            </label>
            <div class="flex items-end gap-2">
              <input ref="pendingFileRef" type="file" accept="image/*,application/pdf" class="hidden" @change="onPendingFile" />
              <button
                type="button"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-600 dark:bg-slate-900"
                :aria-label="t('cost_center.attach_receipt')"
                @click="pendingFileRef?.click()"
              >
                <PaperClipIcon class="h-4 w-4" />
              </button>
              <Button type="button" class="!h-9 !shrink-0 !px-4 !py-0 !text-xs" :loading="submitting" data-testid="cost-submit" @click="submit">
                {{ t('cost_center.quick_submit') }}
              </Button>
            </div>
          </div>
          <div v-if="pendingFile" class="mt-2 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
            <PaperClipIcon class="h-3.5 w-3.5" />
            <span class="min-w-0 flex-1 truncate">{{ pendingFile.name }}</span>
            <button type="button" class="text-slate-400 hover:text-rose-600" @click="pendingFile = null">✕</button>
          </div>
          <p v-if="formMsg" class="mt-2 text-xs text-rose-600">{{ formMsg }}</p>
        </div>
      </Transition>

      <!-- Grid (desktop table) -->
      <div v-if="filteredGridRows.length" class="hidden overflow-x-auto md:block">
        <table class="w-full min-w-[860px] text-left text-xs">
          <thead class="border-b border-slate-100 bg-slate-50/60 text-[11px] uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-900/40">
            <tr>
              <th class="w-9 px-3 py-2.5">
                <input
                  type="checkbox"
                  class="h-3.5 w-3.5 rounded border-slate-300"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                  :aria-label="t('cost_center.select_all')"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_type') }}</th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_desc') }}</th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_creator') }}</th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_created') }}</th>
              <th class="px-3 py-2.5 text-right font-semibold">{{ t('cost_center.col_amount') }}</th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_receipt') }}</th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_status') }}</th>
              <th class="px-3 py-2.5 font-semibold">{{ t('cost_center.col_approver') }}</th>
              <th class="px-3 py-2.5 text-right font-semibold">{{ t('cost_center.col_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr
              v-for="c in filteredGridRows"
              :key="c.id"
              class="group transition hover:bg-slate-50/80 dark:hover:bg-slate-900/40"
              :class="selectedIds.has(c.id) ? 'bg-blue-50/50 dark:bg-blue-950/20' : ''"
            >
              <td class="px-3 py-2.5">
                <input
                  type="checkbox"
                  class="h-3.5 w-3.5 rounded border-slate-300"
                  :checked="selectedIds.has(c.id)"
                  :aria-label="t('cost_center.select_row')"
                  @change="toggleSelect(c.id)"
                />
              </td>
              <td class="px-3 py-2.5">
                <span class="flex items-center gap-1.5 font-medium text-slate-800 dark:text-slate-200">
                  <span class="h-2 w-2 shrink-0 rounded-sm" :class="groupDot(c.type)" />
                  {{ groupLabelOf(c.type) }}
                </span>
              </td>
              <td class="max-w-[14rem] px-3 py-2.5 text-slate-600 dark:text-slate-300">
                <span
                  v-if="hasLegs && legLabelOf(c)"
                  class="mr-1.5 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-200"
                >
                  {{ legLabelOf(c) }}
                </span>
                <span class="truncate align-middle">{{ c.description?.trim() || '—' }}</span>
              </td>
              <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">{{ c.creator?.name || '—' }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-slate-500">{{ fmtDate(c.created_at) }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right font-bold tabular-nums text-slate-900 dark:text-white">
                {{ fmtMoney(c.amount) }}
              </td>
              <td class="px-3 py-2.5">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium"
                  :class="receiptCount(c) ? 'text-sky-700 hover:bg-sky-50 dark:text-sky-300' : 'text-slate-400'"
                  @click="openDrawer(c)"
                >
                  <PaperClipIcon class="h-3.5 w-3.5" />
                  {{ receiptCount(c) ? t('cost_center.receipt_n', { n: receiptCount(c) }) : t('cost_center.receipt_none') }}
                </button>
              </td>
              <td class="px-3 py-2.5">
                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="statusClass(c.status)">
                  {{ statusLabel(c.status) }}
                </span>
              </td>
              <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">{{ c.confirmer?.name || '—' }}</td>
              <td class="px-3 py-2.5">
                <div class="flex items-center justify-end gap-0.5">
                  <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800" :title="t('cost_center.action_view')" @click="openDrawer(c)">
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <template v-if="canReconcile && isPending(c)">
                    <button type="button" class="rounded-md p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40" :title="t('cost_center.action_approve')" :disabled="rowBusyId === c.id" @click="decideOne(c, 'confirm')">
                      <CheckIcon class="h-4 w-4" />
                    </button>
                    <button type="button" class="rounded-md p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" :title="t('cost_center.action_reject')" :disabled="rowBusyId === c.id" @click="openReject(c)">
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </template>
                  <button v-if="canReconcile" type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40" :title="t('cost_center.action_delete')" :disabled="rowBusyId === c.id" @click="deleteOne(c)">
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div v-if="filteredGridRows.length" class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
        <div v-for="c in filteredGridRows" :key="c.id" class="px-4 py-3" @click="openDrawer(c)">
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-800 dark:text-slate-200">
              <span class="h-2 w-2 rounded-sm" :class="groupDot(c.type)" />
              {{ groupLabelOf(c.type) }}
            </span>
            <span class="text-sm font-bold tabular-nums text-slate-900 dark:text-white">{{ fmtMoney(c.amount) }}</span>
          </div>
          <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-slate-500">
            <div class="flex items-center gap-1.5">
              <span
                v-if="hasLegs && legLabelOf(c)"
                class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-1.5 py-0.5 font-semibold text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-200"
              >{{ legLabelOf(c) }}</span>
              <span class="inline-flex rounded-full px-1.5 py-0.5 font-semibold uppercase" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
            </div>
            <span class="tabular-nums">{{ fmtDate(c.created_at) }}</span>
          </div>
        </div>
      </div>
      <div v-else-if="!rows.length" class="px-4 py-10 text-center">
        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ t('cost_center.empty_title') }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ t('cost_center.empty_hint') }}</p>
      </div>
    </section>

    <!-- Reject reason modal -->
    <Teleport to="body">
      <div
        v-if="rejectModalOpen"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click.self="closeReject"
      >
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('cost_center.reject_title') }}</h3>
          <textarea
            v-model="rejectReason"
            rows="3"
            :placeholder="t('cost_center.reject_reason_ph')"
            class="mt-3 w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm outline-none focus:ring-2 focus:ring-rose-300 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
          />
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="closeReject">
              {{ t('cost_center.reject_cancel') }}
            </button>
            <Button type="button" variant="danger" class="!py-2 !text-sm" :loading="bulkBusy || rowBusyId != null" @click="confirmReject">
              {{ t('cost_center.reject_confirm') }}
            </Button>
          </div>
        </div>
      </div>
    </Teleport>

    <CostDetailDrawer
      :open="drawerOpen"
      :row="drawerRow"
      :can-reconcile="canReconcile"
      :busy="rowBusyId != null"
      @close="drawerOpen = false"
      @approve="(r) => decideOne(r, 'confirm')"
      @reject="(r) => openReject(r)"
      @delete="(r) => deleteOne(r)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  PaperClipIcon,
  EyeIcon,
  CheckIcon,
  XMarkIcon,
  TrashIcon,
  FunnelIcon,
} from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import CostDetailDrawer from './CostDetailDrawer.vue'
import TripCostSummaryBar from './TripCostSummaryBar.vue'
import DatagridToolbarSearch from '../shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../shared/ui/FilterVisibilityDropdown.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { submitTripCost, uploadTripCostReceipt, decideTripCost, deleteTripCost } from '../../api/costs'
import { newIdempotencyKey } from '../../util/idempotency'
import { confirmAction } from '../../composables/useConfirm'
import { showAppSuccess, showAppError, showAppErrorFromApi } from '../../composables/appMessage'
import {
  useTripFinancials,
  costGroupOf,
  COST_GROUPS,
  isPendingCost,
  type TripCostRow,
} from '../../composables/useTripFinancials'

const props = defineProps<{
  tripId: number
  costs?: TripCostRow[] | null
  legs?: Array<{ key: string; seq?: number; label: string; route?: string }> | null
  canSubmit: boolean
  canReconcile: boolean
  revenue?: number
  budget?: number
  currency?: string
}>()

const emit = defineEmits<{ updated: [] }>()

const { t, te, locale } = useI18n()

const FILTER_CONTROL_CLASS =
  'input h-10 w-full rounded-lg border border-slate-200 bg-white text-sm text-slate-900 shadow-sm outline-none focus:border-va-700 focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const COST_FILTER_CONTROLS = [
  { key: 'status', label: t('cost_center.col_status'), default: false },
  { key: 'type', label: t('cost_center.filter_type'), default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(COST_FILTER_CONTROLS, 'trip-detail-costs-filter.v1')

const gridSearch = ref('')
const gridStatusFilter = ref('')
const gridTypeFilter = ref('')

const QUICK_TYPES = [
  { value: 'fuel', labelKey: 'trip_detail.costs.type_fuel' },
  { value: 'driver_salary', labelKey: 'cost_center.group_driver_salary' },
  { value: 'toll', labelKey: 'trip_detail.costs.type_toll' },
  { value: 'parking', labelKey: 'trip_detail.costs.type_parking' },
  { value: 'food', labelKey: 'cost_center.group_food' },
  { value: 'hotel', labelKey: 'cost_center.group_hotel' },
  { value: 'other', labelKey: 'trip_detail.costs.type_other' },
]

const rows = computed<TripCostRow[]>(() => props.costs ?? [])
const currency = computed(() => props.currency || props.costs?.[0]?.currency || 'VND')

// ─── per-leg (chặng) ───
const legOptions = computed(() => props.legs ?? [])
const hasLegs = computed(() => legOptions.value.length > 1)
const legLabelByKey = computed(() => {
  const m = new Map<string, string>()
  for (const l of legOptions.value) m.set(l.key, l.label)
  return m
})
function legKeyOf(c: TripCostRow): string {
  return String((c as { leg_key?: string | null }).leg_key ?? '')
}
function legLabelOf(c: TripCostRow): string {
  const key = legKeyOf(c)
  return key ? (legLabelByKey.value.get(key) ?? '') : ''
}
const selectedLegKey = ref('')

const fin = useTripFinancials(
  rows,
  computed(() => Number(props.revenue ?? 0)),
  computed(() => Number(props.budget ?? 0)),
)

// ─── formatting ───
function fmtMoney(v: unknown) {
  const n = Number(v)
  if (!Number.isFinite(n)) return '—'
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n)} ${currency.value}`
}
function fmtDate(iso: unknown) {
  if (!iso) return '—'
  const d = new Date(iso as string)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString(locale.value === 'en' ? 'en-GB' : 'vi-VN', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
function groupLabel(key: string) {
  return te(key) ? t(key) : key
}
function groupLabelOf(type: string | null | undefined) {
  return groupLabel(COST_GROUPS[costGroupOf(type)].labelKey)
}
function groupDot(type: string | null | undefined) {
  return COST_GROUPS[costGroupOf(type)].dotClass
}

function rowMatchesStatusFilter(c: TripCostRow, filter: string) {
  if (!filter) return true
  if (filter === '__pending__') return isPendingCost(c)
  const s = String(c.status ?? '')
    .trim()
    .toLowerCase()
  if (filter === 'confirmed') return s === 'confirmed' || s === 'approved'
  return s === filter
}

const filteredGridRows = computed(() => {
  let list = rows.value
  if (gridStatusFilter.value) {
    list = list.filter((c) => rowMatchesStatusFilter(c, gridStatusFilter.value))
  }
  if (gridTypeFilter.value) {
    const type = gridTypeFilter.value.toLowerCase()
    list = list.filter((c) => String(c.type ?? '').toLowerCase() === type)
  }
  const q = gridSearch.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((c) =>
    [groupLabelOf(c.type), c.description, c.creator?.name, statusLabel(c.status), String(c.amount ?? '')]
      .join(' ')
      .toLowerCase()
      .includes(q),
  )
})

function onKpiQuickFilter(payload: { status: string }) {
  const next = payload.status ?? ''
  gridStatusFilter.value = gridStatusFilter.value === next ? '' : next
  if (gridStatusFilter.value) visibleFilters.status = true
}

function resetGridFilters() {
  gridSearch.value = ''
  gridStatusFilter.value = ''
  gridTypeFilter.value = ''
}

// ─── Health card ───
const healthCard = computed(() => {
  const level = fin.health.value.level
  if (level === 'danger') {
    return {
      emoji: '🔴',
      title: t('cost_center.health_danger_title'),
      desc: t('cost_center.health_danger_desc', { pct: Math.round(fin.budgetUsedPct.value ?? 0) }),
      wrapClass: 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/30',
      titleClass: 'text-rose-800 dark:text-rose-200',
      descClass: 'text-rose-700 dark:text-rose-300',
      barClass: 'bg-rose-500',
    }
  }
  if (level === 'warning') {
    return {
      emoji: '🟡',
      title: t('cost_center.health_warning_title'),
      desc: t('cost_center.health_warning_desc', { pct: Math.round(fin.budgetUsedPct.value ?? 0) }),
      wrapClass: 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30',
      titleClass: 'text-amber-900 dark:text-amber-200',
      descClass: 'text-amber-800 dark:text-amber-300',
      barClass: 'bg-amber-500',
    }
  }
  if (level === 'good') {
    return {
      emoji: '🟢',
      title: t('cost_center.health_good_title'),
      desc: t('cost_center.health_good_desc', { pct: Math.round(fin.budgetUsedPct.value ?? 0) }),
      wrapClass: 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30',
      titleClass: 'text-emerald-800 dark:text-emerald-200',
      descClass: 'text-emerald-700 dark:text-emerald-300',
      barClass: 'bg-emerald-500',
    }
  }
  return {
    emoji: '⚪',
    title: t('cost_center.health_unknown_title'),
    desc: t('cost_center.health_unknown_desc'),
    wrapClass: 'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/40',
    titleClass: 'text-slate-700 dark:text-slate-200',
    descClass: 'text-slate-500 dark:text-slate-400',
    barClass: 'bg-slate-400',
  }
})

// ─── Insights ───
function insightText(ins: { i18nKey: string; params?: Record<string, unknown> }) {
  const params = { ...(ins.params ?? {}) }
  if (typeof params.groupKey === 'string') params.group = groupLabel(params.groupKey)
  return te(ins.i18nKey) ? t(ins.i18nKey, params) : ins.i18nKey
}
function insightToneClass(tone: string) {
  if (tone === 'danger') return 'bg-rose-50 text-rose-800 dark:bg-rose-950/30 dark:text-rose-200'
  if (tone === 'warning') return 'bg-amber-50 text-amber-900 dark:bg-amber-950/30 dark:text-amber-200'
  return 'bg-sky-50 text-sky-800 dark:bg-sky-950/30 dark:text-sky-200'
}
function insightIcon(tone: string) {
  if (tone === 'danger') return '⚠️'
  if (tone === 'warning') return '🟡'
  return 'ℹ️'
}

// ─── status helpers ───
function isPending(c: TripCostRow) {
  return isPendingCost(c)
}
function statusLabel(status: unknown) {
  const raw = String(status ?? '').trim().toLowerCase()
  if (!raw) return '—'
  const key = `trip_detail.costs.status_${raw}`
  return te(key) ? t(key) : raw
}
function statusClass(status: unknown) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'confirmed' || s === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
  if (s === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200'
  if (s === 'paid') return 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200'
  if (s === 'submitted' || s === 'pending') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}
function receiptCount(c: TripCostRow) {
  const n = Number(c.attachments_count)
  if (Number.isFinite(n) && n > 0) return n
  return c.receipt_url ? 1 : 0
}

// ─── selection ───
const selectedIds = ref<Set<number>>(new Set())
const selectableIds = computed(() =>
  filteredGridRows.value.map((c) => c.id).filter((id): id is number => id != null),
)
const allSelected = computed(() => selectableIds.value.length > 0 && selectableIds.value.every((id) => selectedIds.value.has(id)))
const someSelected = computed(() => selectedIds.value.size > 0 && !allSelected.value)
const selectedPendingCount = computed(
  () => rows.value.filter((c) => c.id != null && selectedIds.value.has(c.id) && isPending(c)).length,
)
function toggleSelect(id?: number | null) {
  if (id == null) return
  const next = new Set(selectedIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  selectedIds.value = next
}
function toggleSelectAll() {
  selectedIds.value = allSelected.value ? new Set() : new Set(selectableIds.value)
}

watch([gridSearch, gridStatusFilter, gridTypeFilter], () => {
  selectedIds.value = new Set()
})

// ─── drawer ───
const drawerOpen = ref(false)
const drawerRow = ref<TripCostRow | null>(null)
function openDrawer(c: TripCostRow) {
  drawerRow.value = c
  drawerOpen.value = true
}

// ─── decisions ───
const rowBusyId = ref<number | null>(null)
const bulkBusy = ref(false)

async function decideOne(c: TripCostRow | null, decision: 'confirm' | 'reject', reason?: string) {
  if (!c?.id) return
  rowBusyId.value = c.id
  try {
    await decideTripCost(c.id, { decision, reason: reason ?? null }, { idempotencyKey: newIdempotencyKey() })
    showAppSuccess(decision === 'confirm' ? t('cost_center.toast_approved') : t('cost_center.toast_rejected'))
    drawerOpen.value = false
    emit('updated')
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    rowBusyId.value = null
  }
}

async function bulkDecide(decision: 'confirm' | 'reject', reason?: string) {
  const targets = rows.value.filter((c) => c.id != null && selectedIds.value.has(c.id) && isPending(c))
  if (!targets.length) return
  bulkBusy.value = true
  let ok = 0
  let fail = 0
  for (const c of targets) {
    try {
      await decideTripCost(c.id!, { decision, reason: reason ?? null }, { idempotencyKey: newIdempotencyKey() })
      ok++
    } catch {
      fail++
    }
  }
  bulkBusy.value = false
  selectedIds.value = new Set()
  if (ok) showAppSuccess(t('cost_center.toast_bulk_done', { ok, fail }))
  if (fail && !ok) showAppError(t('cost_center.toast_bulk_failed'))
  emit('updated')
}

async function deleteOne(c: TripCostRow | null) {
  if (!c?.id) return
  const ok = await confirmAction({
    title: t('cost_center.delete_title'),
    message: t('cost_center.delete_message'),
    confirmLabel: t('cost_center.action_delete'),
    danger: true,
  })
  if (!ok) return
  rowBusyId.value = c.id
  try {
    await deleteTripCost(c.id)
    showAppSuccess(t('cost_center.toast_deleted'))
    drawerOpen.value = false
    emit('updated')
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    rowBusyId.value = null
  }
}

// ─── reject modal (single + bulk) ───
const rejectModalOpen = ref(false)
const rejectReason = ref('')
const rejectTarget = ref<TripCostRow | null>(null)
function openReject(c: TripCostRow | null) {
  rejectTarget.value = c
  rejectReason.value = ''
  rejectModalOpen.value = true
}
function closeReject() {
  rejectModalOpen.value = false
  rejectTarget.value = null
}
async function confirmReject() {
  const reason = rejectReason.value.trim() || undefined
  const target = rejectTarget.value
  rejectModalOpen.value = false
  if (target) await decideOne(target, 'reject', reason)
  else await bulkDecide('reject', reason)
  rejectTarget.value = null
}

// ─── quick add ───
const showQuickAdd = ref(false)
const selectedType = ref('fuel')
const amount = ref('')
const description = ref('')
const submitting = ref(false)
const formMsg = ref('')
const pendingFile = ref<File | null>(null)
const pendingFileRef = ref<HTMLInputElement | null>(null)
const amountInputRef = ref<HTMLInputElement | null>(null)

function toggleQuickAdd() {
  showQuickAdd.value = !showQuickAdd.value
  if (showQuickAdd.value) nextTick(() => amountInputRef.value?.focus())
}
function onDropPending(e: DragEvent) {
  const f = e.dataTransfer?.files?.[0]
  if (f) pendingFile.value = f
}
function onPendingFile(e: Event) {
  const input = e.target as HTMLInputElement
  pendingFile.value = input.files?.[0] ?? null
  input.value = ''
}
async function submit() {
  if (!props.canSubmit) return
  formMsg.value = ''
  const type = selectedType.value.trim().toLowerCase()
  const n = Number(amount.value)
  if (!type || !Number.isFinite(n) || n <= 0) {
    formMsg.value = t('cost_center.quick_invalid')
    return
  }
  submitting.value = true
  try {
    const legKey = hasLegs.value ? selectedLegKey.value.trim() : ''
    const created = await submitTripCost(
      props.tripId,
      {
        type,
        amount: n,
        currency: 'VND',
        description: description.value.trim() || undefined,
        leg_key: legKey || undefined,
      },
      { idempotencyKey: newIdempotencyKey() },
    )
    amount.value = ''
    description.value = ''
    selectedLegKey.value = ''
    const file = pendingFile.value
    pendingFile.value = null
    emit('updated')
    const costId = created && typeof created === 'object' && 'id' in created ? Number((created as { id?: number }).id) : NaN
    if (file && Number.isFinite(costId)) {
      try {
        await uploadTripCostReceipt(props.tripId, costId, file, { idempotencyKey: newIdempotencyKey() })
      } catch {
        /* receipt upload best-effort */
      }
      emit('updated')
    }
    showQuickAdd.value = false
    showAppSuccess(t('cost_center.toast_added'))
  } catch (e) {
    const err = e as { response?: { data?: { message?: string } } }
    formMsg.value = err?.response?.data?.message ?? t('cost_center.quick_error')
  } finally {
    submitting.value = false
  }
}

// ─── export ───
function exportCsv() {
  const headers = [
    t('cost_center.col_type'),
    t('cost_center.col_desc'),
    t('cost_center.col_creator'),
    t('cost_center.col_created'),
    t('cost_center.col_amount'),
    t('cost_center.col_status'),
    t('cost_center.col_approver'),
  ]
  const esc = (v: unknown) => `"${String(v ?? '').replace(/"/g, '""')}"`
  const lines = filteredGridRows.value.map((c) =>
    [
      groupLabelOf(c.type),
      c.description?.trim() ?? '',
      c.creator?.name ?? '',
      fmtDate(c.created_at),
      Number(c.amount) || 0,
      statusLabel(c.status),
      c.confirmer?.name ?? '',
    ]
      .map(esc)
      .join(','),
  )
  const csv = '﻿' + [headers.map(esc).join(','), ...lines].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `trip-${props.tripId}-costs.csv`
  a.click()
  URL.revokeObjectURL(url)
}
</script>
