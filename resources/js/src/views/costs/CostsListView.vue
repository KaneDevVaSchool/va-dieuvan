<template>
  <div class="costs-page space-y-6 pb-12 text-slate-900">
    <!-- KPI -->
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Tổng bản ghi</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ meta.total ?? 0 }}</p>
        <p class="mt-0.5 text-[10px] text-slate-400">Theo bộ lọc và quyền truy cập</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Đã gửi</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-800">{{ countOnPage('submitted') }}</p>
        <p class="mt-0.5 text-[10px] text-slate-400">Trang hiện tại</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Đã xác nhận</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800">{{ countOnPage('confirmed') }}</p>
        <p class="mt-0.5 text-[10px] text-slate-400">Trang hiện tại</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Từ chối</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-800">{{ countOnPage('rejected') }}</p>
        <p class="mt-0.5 text-[10px] text-slate-400">Trang hiện tại</p>
      </div>
    </div>

    <!-- Filters: cùng pattern danh sách yêu cầu -->
    <div
      class="rounded-2xl border border-violet-100/90 bg-gradient-to-r from-slate-50 via-violet-50/40 to-indigo-50/25 px-2 py-2 shadow-sm sm:px-3 sm:py-2"
    >
      <div class="flex min-w-0 items-center gap-1.5 sm:gap-2">
        <details ref="filterMenuRef" class="group relative shrink-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5 text-slate-600" aria-hidden="true" />
              <span
                v-if="activeFilterCount > 0"
                class="absolute -right-1.5 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-semibold leading-none text-white"
              >
                {{ activeFilterCount > 9 ? '9+' : activeFilterCount }}
              </span>
            </span>
            <ChevronDownIcon class="h-4 w-4 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[260px] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5"
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bộ lọc đang áp dụng</p>
            <ul class="mt-2 space-y-2 text-sm text-slate-700">
              <li v-if="filters.status" class="flex justify-between gap-2">
                <span class="text-slate-500">Trạng thái</span>
                <span class="font-medium">{{ statusLabel(filters.status) }}</span>
              </li>
              <li v-if="filters.type" class="flex justify-between gap-2">
                <span class="text-slate-500">Loại chi phí</span>
                <span class="font-medium">{{ typeLabel(filters.type) }}</span>
              </li>
              <li v-if="filters.trip_id" class="flex justify-between gap-2">
                <span class="text-slate-500">Chuyến</span>
                <span class="max-w-[12rem] truncate text-right font-medium" :title="tripFilterSummaryFull">{{
                  tripFilterSummaryFull
                }}</span>
              </li>
              <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                <span class="text-slate-500">Ngày ghi nhận</span>
                <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
              </li>
              <li v-if="filters.per_page !== DEFAULT_PER_PAGE" class="flex justify-between gap-2">
                <span class="text-slate-500">Số dòng/trang</span>
                <span class="font-medium">{{ filters.per_page }}</span>
              </li>
              <li v-if="searchQ.trim()" class="flex justify-between gap-2">
                <span class="text-slate-500">Tìm nhanh</span>
                <span class="max-w-[10rem] truncate font-medium" :title="searchQ">{{ searchQ }}</span>
              </li>
              <li v-if="activeFilterCount === 0" class="text-slate-400">Chưa chọn điều kiện lọc.</li>
            </ul>
            <button
              type="button"
              class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              @click="resetFilters(); closeFilterMenu()"
            >
              Xóa tất cả bộ lọc
            </button>
          </div>
        </details>

        <div class="hidden h-6 w-px shrink-0 bg-slate-200/90 sm:block" aria-hidden="true" />

        <div
          class="costs-filter-scroll flex min-w-0 flex-1 flex-nowrap items-center gap-1.5 overflow-x-auto overscroll-x-contain py-0.5 [-ms-overflow-style:none] [scrollbar-width:thin] sm:gap-2 [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300/80"
        >
          <details class="group relative shrink-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">Trạng thái</span>
              <span class="min-w-0 max-w-[10rem] truncate text-sm font-medium text-slate-900">{{
                filters.status ? statusLabel(filters.status) : 'Tất cả'
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5"
            >
              <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                <li v-for="opt in statusFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      filters.status === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900'
                        : 'text-slate-700 hover:bg-slate-50'
                    "
                    @click="applyFilterPatch($event, { status: opt.value })"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </div>
          </details>

          <details class="group relative shrink-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">Loại chi phí</span>
              <span class="min-w-0 max-w-[10rem] truncate text-sm font-medium text-slate-900">{{
                filters.type ? typeLabel(filters.type) : 'Tất cả'
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li v-for="opt in typeFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      filters.type === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900'
                        : 'text-slate-700 hover:bg-slate-50'
                    "
                    @click="applyFilterPatch($event, { type: opt.value })"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </div>
          </details>

          <details class="group relative shrink-0">
            <summary
              class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">Ngày ghi nhận</span>
              <span class="min-w-0 truncate text-sm font-medium text-slate-900">{{ filterDateSummary }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 w-[min(100vw-1.5rem,320px)] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 sm:w-max"
            >
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                  v-model="filters.from"
                  type="date"
                  class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto"
                  @change="onFilterDropdownChange"
                />
                <span class="hidden text-slate-300 sm:inline">—</span>
                <input
                  v-model="filters.to"
                  type="date"
                  class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto"
                  @change="onFilterDropdownChange"
                />
              </div>
            </div>
          </details>

          <details class="group relative shrink-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">Chuyến</span>
              <span class="min-w-0 max-w-[11rem] truncate text-sm font-medium text-slate-900" :title="tripFilterSummaryFull">{{
                tripFilterSummaryShort
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 w-[min(100vw-1.5rem,320px)] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 sm:w-max sm:min-w-[280px]"
            >
              <input
                v-model="filterTripSearch"
                type="search"
                class="costs-input mb-2 h-9 w-full text-sm"
                placeholder="Tìm mã trip, điểm đi hoặc điểm đến…"
                autocomplete="off"
                @click.stop
              />
              <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-0.5 py-0.5">
                <li>
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      !filters.trip_id
                        ? 'bg-teal-50 font-medium text-teal-900'
                        : 'text-slate-700 hover:bg-slate-50'
                    "
                    @click="applyFilterPatch($event, { trip_id: '' })"
                  >
                    Tất cả chuyến
                  </button>
                </li>
                <li v-for="t in filteredTripsForFilter" :key="t.id">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      String(filters.trip_id) === String(t.id)
                        ? 'bg-teal-50 font-medium text-teal-900'
                        : 'text-slate-700 hover:bg-slate-50'
                    "
                    @click="applyFilterPatch($event, { trip_id: String(t.id) })"
                  >
                    {{ formatTripPickerLabel(t) }}
                  </button>
                </li>
              </ul>
              <p
                v-if="!tripsForModalLoading && tripOptionsRaw.length && !filteredTripsForFilter.length"
                class="mt-2 text-[11px] text-amber-800"
              >
                Không có chuyến khớp — xóa ô tìm hoặc chọn “Tất cả chuyến”.
              </p>
              <p v-else-if="tripsForModalLoading" class="mt-2 text-[11px] text-slate-500">Đang tải danh sách chuyến…</p>
              <p v-else-if="!tripsForModalLoading && !tripOptionsRaw.length" class="mt-2 text-[11px] text-slate-500">
                Không có chuyến trong phạm vi quyền.
              </p>
            </div>
          </details>

          <input
            v-model="searchQ"
            type="search"
            aria-label="Tìm trong trang hiện tại"
            placeholder="Tìm trong trang…"
            title="Tìm trong trang hiện tại"
            class="costs-input h-9 w-[9.5rem] shrink-0 text-sm sm:w-44"
          />

          <label class="inline-flex shrink-0 items-center gap-1.5">
            <span class="sr-only">Số dòng mỗi trang</span>
            <select
              v-model.number="filters.per_page"
              class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
              aria-label="Số dòng mỗi trang"
              @change="onPerPageChange"
            >
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="hidden whitespace-nowrap text-xs text-slate-500 sm:inline" aria-hidden="true">dòng</span>
          </label>
        </div>

        <div
          class="flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 sm:gap-2 sm:pl-3"
        >
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800"
            title="Xóa bộ lọc"
            aria-label="Xóa bộ lọc"
            @click="resetFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100" />
            </span>
          </button>
          <button
            type="button"
            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-va-800 px-3 text-sm font-semibold text-white shadow-sm ring-1 ring-black/5 transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/35"
            @click="openAddCostModal"
          >
            Thêm chi phí
          </button>
        </div>
      </div>
    </div>

    <!-- Bảng -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.05]">
      <div class="border-b border-slate-200 bg-slate-100 px-4 py-3 sm:px-5">
        <h2 class="text-sm font-semibold text-slate-800">Danh sách chi phí</h2>
      </div>
      <div class="costs-table-wrap overflow-x-auto">
        <table class="costs-sheet min-w-[1100px] w-full border-collapse text-left text-xs sm:text-sm">
          <thead>
            <tr class="bg-slate-100 text-[10px] font-semibold uppercase tracking-wide text-slate-600 sm:text-[11px]">
              <th class="costs-th w-10 text-center">STT</th>
              <th class="costs-th min-w-[7rem]">Đơn vị</th>
              <th class="costs-th min-w-[7rem]">Phân loại</th>
              <th class="costs-th min-w-[8rem]">Người đề xuất</th>
              <th class="costs-th min-w-[14rem]">Nội dung</th>
              <th class="costs-th min-w-[8rem]">Nhà cung cấp</th>
              <th class="costs-th costs-th--money min-w-[7rem] text-right">Tạm ứng</th>
              <th class="costs-th costs-th--money min-w-[8rem] text-right">Thanh toán</th>
              <th class="costs-th min-w-[6.5rem] whitespace-nowrap">Thời gian</th>
              <th class="costs-th min-w-[8rem]">Người phụ trách</th>
              <th class="costs-th min-w-[6rem]">Chứng từ</th>
              <th class="costs-th min-w-[7rem]">Pháp nhân TT</th>
              <th class="costs-th min-w-[6rem]">Ghi chú</th>
              <th class="costs-th min-w-[5rem]">Trip</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(c, idx) in displayedItems"
              :key="c.id"
              :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
              class="transition-colors hover:bg-sky-50/50"
            >
              <td class="costs-td text-center text-slate-500">{{ rowIndex(idx) }}</td>
              <td class="costs-td">
                <span class="costs-pill">{{ UNIT_LABEL }}</span>
              </td>
              <td class="costs-td">
                <span class="costs-pill costs-pill--type">{{ typeLabel(c.type) }}</span>
              </td>
              <td class="costs-td text-slate-800">{{ c.creator?.name || '—' }}</td>
              <td class="costs-td max-w-[20rem] text-slate-800">
                <span class="line-clamp-2" :title="c.description || ''">{{ c.description || '—' }}</span>
              </td>
              <td class="costs-td text-slate-700">{{ c.trip?.transport_provider?.name ?? '—' }}</td>
              <td class="costs-td costs-td--money text-right text-slate-400">—</td>
              <td class="costs-td costs-td--money text-right font-medium tabular-nums text-slate-900">
                {{ formatVnd(c.amount) }}
              </td>
              <td class="costs-td whitespace-nowrap text-slate-700">{{ formatDateDMY(c.created_at) }}</td>
              <td class="costs-td">
                <span v-if="c.confirmer?.name" class="costs-pill">{{ c.confirmer.name }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="costs-td">
                <a
                  v-if="c.receipt_url"
                  :href="c.receipt_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-va-800 underline decoration-va-800/30 underline-offset-2 hover:text-va-900"
                  >Xem</a
                >
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="costs-td text-slate-500">{{ LEGAL_ENTITY_PLACEHOLDER }}</td>
              <td class="costs-td max-w-[12rem] text-slate-600">
                <span v-if="c.rejection_reason" class="line-clamp-2 text-rose-700" :title="c.rejection_reason">{{
                  c.rejection_reason
                }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="costs-td">
                <RouterLink
                  v-if="c.trip_id"
                  class="font-medium text-va-800 underline decoration-va-800/30 underline-offset-2 hover:text-va-900"
                  :to="`/trips/${c.trip_id}`"
                  >#{{ c.trip_id }}</RouterLink
                >
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && !displayedItems.length" class="px-4 py-12 text-center text-sm text-slate-500">
          Không có bản ghi phù hợp.
        </div>
        <div v-if="loading" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
          <span
            class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700"
            aria-hidden="true"
          />
          Đang tải…
        </div>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <span class="text-sm text-slate-600">
          Trang <strong class="font-semibold text-slate-900">{{ meta.current_page ?? 1 }}</strong> /
          {{ meta.last_page ?? 1 }}
          <span class="text-slate-400"> · </span>
          {{ meta.total ?? 0 }} bản ghi
        </span>
        <div class="flex gap-2">
          <button
            type="button"
            class="costs-btn-ghost"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="page(-1)"
          >
            Trước
          </button>
          <button
            type="button"
            class="costs-btn-ghost"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="page(1)"
          >
            Sau
          </button>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="addCostModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="costs-add-title"
      >
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]" aria-hidden="true" @click="closeAddCostModal" />
        <div
          class="relative z-10 flex max-h-[min(92vh,640px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/10"
          @click.stop
        >
          <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
              <h2 id="costs-add-title" class="text-base font-semibold text-slate-900">Thêm chi phí</h2>
              <p class="mt-0.5 text-xs text-slate-500">Chọn chuyến và nhập khoản phát sinh (gửi để đối soát).</p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
              aria-label="Đóng"
              @click="closeAddCostModal"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <form class="flex min-h-0 flex-1 flex-col overflow-y-auto px-5 py-4" @submit.prevent="submitCost">
            <div class="grid gap-4">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Chuyến <span class="text-rose-600">*</span></label>
                <input
                  v-model="tripPickerSearch"
                  type="search"
                  class="costs-input mb-2 w-full"
                  placeholder="Gõ để lọc theo mã trip, điểm đi hoặc điểm đến…"
                  autocomplete="off"
                />
                <select
                  v-model="costForm.trip_id"
                  class="costs-input w-full font-medium"
                  :required="!tripsForModalLoading"
                  :disabled="tripsForModalLoading"
                >
                  <option disabled value="">
                    {{
                      tripsForModalLoading
                        ? 'Đang tải danh sách chuyến…'
                        : '— Chọn một chuyến —'
                    }}
                  </option>
                  <option v-for="t in filteredTripsForPicker" :key="t.id" :value="String(t.id)">
                    {{ formatTripPickerLabel(t) }}
                  </option>
                </select>
                <p
                  v-if="!tripsForModalLoading && tripOptionsRaw.length && !filteredTripsForPicker.length"
                  class="mt-1 text-[11px] text-amber-800"
                >
                  Không có chuyến khớp từ khóa — xóa ô tìm hoặc thử từ khác.
                </p>
                <p v-else-if="!tripsForModalLoading && !tripOptionsRaw.length" class="mt-1 text-[11px] text-slate-500">
                  Không có chuyến khả dụng trong phạm vi quyền.
                </p>
                <p v-else-if="!tripsForModalLoading && tripOptionsRaw.length" class="mt-1 text-[11px] text-slate-500">
                  Danh sách theo quyền xem chuyến (tối đa 100 chuyến gần nhất).
                </p>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Loại chi phí</label>
                <select v-model="costForm.type" class="costs-input w-full">
                  <option value="fuel">Xăng / dầu</option>
                  <option value="toll">Phí cầu đường</option>
                  <option value="parking">Bãi xe</option>
                  <option value="other">Khác</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Số tiền ({{ costForm.currency }})</label>
                <input
                  v-model.number="costForm.amount"
                  type="number"
                  min="0"
                  step="1000"
                  required
                  class="costs-input w-full"
                  placeholder="Ví dụ: 150000"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">Mô tả</label>
                <input
                  v-model="costForm.description"
                  type="text"
                  class="costs-input w-full"
                  placeholder="Ví dụ: Phí gửi xe tháng 1/2025 — bãi X"
                />
              </div>
            </div>
            <p v-if="costMsg" class="mt-3 text-sm" :class="costMsgIsError ? 'text-rose-700' : 'text-emerald-800'">
              {{ costMsg }}
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
              <button type="button" class="costs-btn-ghost" :disabled="submitting" @click="closeAddCostModal">Hủy</button>
              <button type="submit" class="costs-btn-primary" :disabled="submitting || tripsForModalLoading">
                <span
                  v-if="submitting"
                  class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                />
                Gửi chi phí
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronDownIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { listTripCosts, submitTripCost } from '../../api/costs'
import { listTrips } from '../../api/trips'
import { newIdempotencyKey } from '../../util/idempotency'
import { formatVnd } from '../../util/labels'

const DEFAULT_PER_PAGE = 25

const UNIT_LABEL = 'Chi phí vận hành'
const LEGAL_ENTITY_PLACEHOLDER = '—'

const TYPE_LABELS = {
  fuel: 'Xăng / dầu',
  toll: 'Phí cầu đường',
  parking: 'Bãi xe',
  other: 'Khác',
}

const STATUS_LABELS = {
  draft: 'Nháp',
  submitted: 'Đã gửi',
  confirmed: 'Đã xác nhận',
  rejected: 'Từ chối',
}

const loading = ref(false)
const items = ref([])
const meta = ref({})
const searchQ = ref('')
const filterMenuRef = ref(null)

const addCostModalOpen = ref(false)
const tripPickerSearch = ref('')
const filterTripSearch = ref('')
const tripOptionsRaw = ref([])
const tripsForModalLoading = ref(false)
const costMsgIsError = ref(false)

const filters = reactive({
  status: '',
  type: '',
  trip_id: '',
  from: '',
  to: '',
  page: 1,
  per_page: DEFAULT_PER_PAGE,
})

const costForm = ref({ trip_id: '', type: 'fuel', amount: '', description: '', currency: 'VND' })
const submitting = ref(false)
const costMsg = ref('')

const statusFilterOptions = [
  { value: '', label: 'Tất cả' },
  { value: 'draft', label: STATUS_LABELS.draft },
  { value: 'submitted', label: STATUS_LABELS.submitted },
  { value: 'confirmed', label: STATUS_LABELS.confirmed },
  { value: 'rejected', label: STATUS_LABELS.rejected },
]

const typeFilterOptions = [
  { value: '', label: 'Tất cả' },
  { value: 'fuel', label: TYPE_LABELS.fuel },
  { value: 'toll', label: TYPE_LABELS.toll },
  { value: 'parking', label: TYPE_LABELS.parking },
  { value: 'other', label: TYPE_LABELS.other },
]

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.type) n++
  if (filters.trip_id) n++
  if (filters.from || filters.to) n++
  if (filters.per_page !== DEFAULT_PER_PAGE) n++
  if (searchQ.value.trim()) n++
  return n
})

const filterDateSummary = computed(() => {
  if (!filters.from && !filters.to) return 'Tất cả'
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

function tripMatchesSearch(t, qRaw) {
  const q = qRaw.trim().toLowerCase()
  if (!q) return true
  const dr = t.dispatch_request ?? t.dispatchRequest
  const id = String(t.id)
  const o = String(dr?.origin ?? '').toLowerCase()
  const d = String(dr?.destination ?? '').toLowerCase()
  return id.includes(q) || o.includes(q) || d.includes(q)
}

const filteredTripsForPicker = computed(() => {
  const q = tripPickerSearch.value
  const list = tripOptionsRaw.value
  if (!q.trim()) return list
  return list.filter((t) => tripMatchesSearch(t, q))
})

const filteredTripsForFilter = computed(() => {
  const q = filterTripSearch.value
  const list = tripOptionsRaw.value
  if (!q.trim()) return list
  return list.filter((t) => tripMatchesSearch(t, q))
})

const selectedFilterTrip = computed(() => {
  if (!filters.trip_id) return null
  const id = Number(filters.trip_id)
  if (!Number.isFinite(id)) return null
  return tripOptionsRaw.value.find((t) => Number(t.id) === id) ?? null
})

const tripFilterSummaryFull = computed(() => {
  if (!filters.trip_id) return 'Tất cả chuyến'
  const t = selectedFilterTrip.value
  return t ? formatTripPickerLabel(t) : `Chuyến #${filters.trip_id}`
})

const tripFilterSummaryShort = computed(() => {
  if (!filters.trip_id) return 'Tất cả'
  const t = selectedFilterTrip.value
  if (t) {
    const dr = t.dispatch_request ?? t.dispatchRequest
    const o = (dr?.origin ?? '—').trim().slice(0, 22)
    const d = (dr?.destination ?? '—').trim().slice(0, 22)
    return `#${t.id} · ${o} → ${d}`
  }
  return `#${filters.trip_id}`
})

const displayedItems = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((c) => {
    const d = String(c.description ?? '').toLowerCase()
    const creator = String(c.creator?.name ?? '').toLowerCase()
    const ty = String(c.type ?? '').toLowerCase()
    const trip = String(c.trip_id ?? '')
    return d.includes(q) || creator.includes(q) || ty.includes(q) || trip.includes(q)
  })
})

function rowIndex(idx) {
  const page = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? DEFAULT_PER_PAGE
  return (page - 1) * per + idx + 1
}

function countOnPage(status) {
  return items.value.filter((c) => c.status === status).length
}

function typeLabel(t) {
  return TYPE_LABELS[t] ?? t ?? '—'
}

function statusLabel(s) {
  return STATUS_LABELS[s] ?? s ?? '—'
}

function formatDateDMY(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  } catch {
    return '—'
  }
}

function formatTripPickerLabel(t) {
  const dr = t.dispatch_request ?? t.dispatchRequest
  const o = (dr?.origin ?? '—').trim().slice(0, 48)
  const d = (dr?.destination ?? '—').trim().slice(0, 48)
  const dep = formatDateDMY(t.depart_at)
  return `#${t.id} · ${o} → ${d} · ${dep}`
}

async function loadTripPickerOptions() {
  tripsForModalLoading.value = true
  try {
    const res = await listTrips({ per_page: 100, page: 1 })
    tripOptionsRaw.value = res.items ?? []
  } catch {
    tripOptionsRaw.value = []
  } finally {
    tripsForModalLoading.value = false
  }
}

async function openAddCostModal() {
  costMsg.value = ''
  costMsgIsError.value = false
  tripPickerSearch.value = ''
  costForm.value = { trip_id: '', type: 'fuel', amount: '', description: '', currency: 'VND' }
  addCostModalOpen.value = true
  await loadTripPickerOptions()
}

function closeAddCostModal() {
  addCostModalOpen.value = false
  costMsg.value = ''
  costMsgIsError.value = false
}

let escapeCloseModal = null
watch(addCostModalOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
  if (typeof window === 'undefined') return
  if (escapeCloseModal) {
    window.removeEventListener('keydown', escapeCloseModal)
    escapeCloseModal = null
  }
  if (open) {
    escapeCloseModal = (e) => {
      if (e.key === 'Escape') closeAddCostModal()
    }
    window.addEventListener('keydown', escapeCloseModal)
  }
})

onUnmounted(() => {
  if (typeof document !== 'undefined') document.body.style.overflow = ''
  if (typeof window !== 'undefined' && escapeCloseModal) {
    window.removeEventListener('keydown', escapeCloseModal)
  }
})

function closeParentDetails(ev) {
  const el = ev?.currentTarget
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function closeFilterMenu() {
  const el = filterMenuRef.value
  if (el && 'open' in el) el.open = false
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  filters.page = 1
  closeParentDetails(ev)
  reload()
}

function onFilterDropdownChange(ev) {
  closeParentDetails(ev)
  filters.page = 1
  reload()
}

function onPerPageChange() {
  filters.page = 1
  reload()
}

function resetFilters() {
  filters.status = ''
  filters.type = ''
  filters.trip_id = ''
  filters.from = ''
  filters.to = ''
  filters.page = 1
  filters.per_page = DEFAULT_PER_PAGE
  searchQ.value = ''
  filterTripSearch.value = ''
  reload()
}

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    Object.keys(p).forEach((k) => (p[k] === '' || p[k] === null ? delete p[k] : null))
    const res = await listTripCosts(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

async function submitCost() {
  costMsg.value = ''
  costMsgIsError.value = false
  const tid = Number(costForm.value.trip_id)
  if (!tid) {
    costMsg.value = 'Vui lòng chọn chuyến.'
    costMsgIsError.value = true
    return
  }
  submitting.value = true
  try {
    await submitTripCost(
      tid,
      {
        type: costForm.value.type,
        amount: costForm.value.amount,
        description: costForm.value.description || null,
      },
      { idempotencyKey: newIdempotencyKey() },
    )
    closeAddCostModal()
    await reload()
  } catch (e) {
    costMsgIsError.value = true
    costMsg.value = e?.response?.data?.message ?? 'Không gửi được chi phí.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await loadTripPickerOptions()
  await reload()
})
</script>

<style scoped>
.costs-page {
  --cost-border: rgb(226 232 240);
}

.costs-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/20;
}

.costs-btn-primary {
  @apply inline-flex items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50;
}

.costs-btn-ghost {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.costs-table-wrap {
  @apply max-w-full;
}

.costs-sheet .costs-th,
.costs-sheet .costs-td {
  border: 1px dashed var(--cost-border);
  padding: 0.5rem 0.6rem;
  vertical-align: top;
}

@media (min-width: 640px) {
  .costs-sheet .costs-th,
  .costs-sheet .costs-td {
    padding: 0.55rem 0.75rem;
  }
}

.costs-th {
  background: linear-gradient(to bottom, rgb(241 245 249), rgb(226 232 240 / 0.85));
}

.costs-td--money {
  font-variant-numeric: tabular-nums;
}

.costs-pill {
  @apply inline-flex max-w-full items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 sm:text-xs;
}

.costs-pill--type {
  @apply bg-slate-200/90 text-slate-800;
}
</style>
