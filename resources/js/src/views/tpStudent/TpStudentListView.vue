<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Danh sách Học sinh</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý toàn bộ học sinh trong hệ thống đưa đón</p>
      </div>

      <div class="flex flex-col items-stretch gap-3 sm:items-end">
        <div class="flex flex-wrap gap-2">
          <Button variant="secondary" :disabled="exporting" @click="exportList">
            <ArrowPathIcon v-if="exporting" class="h-4 w-4 animate-spin" />
            <ArrowDownTrayIcon v-else class="h-4 w-4" />
            {{ exporting ? 'Đang xuất…' : 'Xuất danh sách' }}
          </Button>
          <Button variant="secondary" @click="goImport">
            <ArrowUpTrayIcon class="h-4 w-4" /> Import học sinh
          </Button>
          <Button @click="openCreate"><PlusIcon class="h-4 w-4" /> Thêm học sinh</Button>
        </div>

        <div class="flex flex-wrap gap-2">
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
            <UsersIcon class="h-4 w-4 text-slate-400" />
            <span class="font-bold text-slate-900">{{ stats.total }}</span> Tổng học sinh
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">
            <TruckIcon class="h-4 w-4 text-sky-500" />
            <span class="font-bold">{{ stats.transporting }}</span> Đang đưa đón
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
            <ClockIcon class="h-4 w-4 text-amber-500" />
            <span class="font-bold">{{ stats.pending }}</span> Chờ duyệt
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500">
            <UserMinusIcon class="h-4 w-4 text-slate-400" />
            <span class="font-bold text-slate-700">{{ stats.unregistered }}</span> Chưa đăng ký
          </span>
        </div>
      </div>
    </div>

    <AppFilterBar>
      <div ref="tpStudentFilterBarRef" class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bộ lọc đang áp dụng</p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700">
            <li v-if="filters.search" class="flex justify-between gap-2"><span class="text-slate-500">Tìm kiếm</span><span class="truncate font-medium">{{ filters.search }}</span></li>
            <li v-if="filters.class_name" class="flex justify-between gap-2"><span class="text-slate-500">Lớp</span><span class="font-medium">{{ filters.class_name }}</span></li>
            <li v-if="filters.transport_status" class="flex justify-between gap-2"><span class="text-slate-500">Trạng thái</span><span class="font-medium">{{ transportStatusLabel(filters.transport_status) }}</span></li>
            <li v-if="filters.program_id" class="flex justify-between gap-2"><span class="text-slate-500">Chương trình</span><span class="truncate font-medium">{{ programFilterLabel }}</span></li>
            <li v-if="filters.grade" class="flex justify-between gap-2"><span class="text-slate-500">Khối</span><span class="font-medium">{{ filters.grade }}</span></li>
            <li v-if="filters.student_status" class="flex justify-between gap-2"><span class="text-slate-500">Hồ sơ HS</span><span class="font-medium">{{ studentStatusLabel(filters.student_status) }}</span></li>
            <li v-if="filters.gender" class="flex justify-between gap-2"><span class="text-slate-500">Giới tính</span><span class="font-medium">{{ genderLabel(filters.gender) }}</span></li>
            <li v-if="filters.pickup_point" class="flex justify-between gap-2"><span class="text-slate-500">Điểm đón</span><span class="truncate font-medium">{{ filters.pickup_point }}</span></li>
            <li v-if="filters.parent_phone" class="flex justify-between gap-2"><span class="text-slate-500">SĐT PH</span><span class="font-medium">{{ filters.parent_phone }}</span></li>
            <li v-if="filters.address_contains" class="flex justify-between gap-2"><span class="text-slate-500">Địa chỉ</span><span class="truncate font-medium">{{ filters.address_contains }}</span></li>
            <li v-if="filters.per_page !== DEFAULT_PER_PAGE" class="flex justify-between gap-2"><span class="text-slate-500">Số dòng/trang</span><span class="font-medium">{{ filters.per_page }}</span></li>
            <li v-if="activeFilterCount === 0" class="text-slate-400">Chưa có điều kiện lọc</li>
          </ul>
          <div class="mt-3 border-t border-slate-100 pt-3">
            <p class="text-[11px] font-semibold uppercase text-violet-700">Hiển thị bộ lọc trên thanh</p>
            <ul class="mt-2 space-y-2">
              <li v-for="opt in filterBarVisibilityOptions" :key="opt.id" class="flex gap-2">
                <input :id="'tp-stu-vis-' + opt.id" v-model="filterBarVisible[opt.id]" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                <label :for="'tp-stu-vis-' + opt.id" class="text-sm text-slate-700">{{ opt.label }}</label>
              </li>
            </ul>
          </div>
          <button type="button" class="mt-3 w-full rounded-lg border py-2 text-sm" @click="clearFilters(); closeFilterMenu()">Xóa tất cả bộ lọc</button>
        </AppFilterFunnelMenu>
        <div class="hidden h-6 w-px bg-slate-200 sm:block" />
        <button type="button" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" @click="clearFilters">
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5" aria-hidden="true" />
            <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100" aria-hidden="true" />
          </span>
        </button>
        <details ref="columnPickerRef" class="group relative shrink-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            aria-label="Chọn cột hiển thị"
          >
            <ViewColumnsIcon class="h-5 w-5 shrink-0 text-slate-600" />
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
          </summary>
          <div
            class="absolute right-0 top-[calc(100%+6px)] z-50 min-w-[220px] rounded-xl border border-slate-200/90 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5"
            @click.stop
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cột hiển thị</p>
            <ul class="mt-2 max-h-[min(50vh,280px)] space-y-2 overflow-y-auto">
              <li v-for="opt in columnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                <input
                  :id="`tp-stu-col-${opt.id}`"
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600"
                  :checked="colOn(opt.id)"
                  @change="setColumn(opt.id, $event.target.checked)"
                />
                <label :for="`tp-stu-col-${opt.id}`" class="cursor-pointer text-xs text-slate-700">{{ opt.label }}</label>
              </li>
            </ul>
          </div>
        </details>
        <div class="relative min-w-0 flex-1 basis-[10rem] sm:min-w-[12rem] sm:max-w-md">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            v-model="filters.search"
            type="search"
            placeholder="Tên, mã, SĐT, điểm đón, PH…"
            aria-label="Tìm học sinh"
            class="h-9 w-full rounded-md border-0 bg-white/90 py-0 pl-9 pr-3 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
          />
        </div>
        <span class="ml-auto text-xs text-slate-500">
          <template v-if="paginationTotal">
            <span class="font-semibold text-slate-700">{{ pageFrom }}–{{ pageTo }}</span>
            / {{ formatInt(paginationTotal) }} kết quả
          </template>
          <template v-else>0 kết quả</template>
          <span class="mx-1 text-slate-300">·</span>
          Tổng hệ thống {{ formatInt(stats.total) }}
        </span>
      </div>
      <div v-if="hasVisibleBarFilters" class="mt-2 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-2">
        <select v-if="filterBarVisible.class_name" v-model="filters.class_name" class="filter-select" :class="filters.class_name ? 'text-slate-900' : 'text-slate-500'">
          <option value="">Lớp</option>
          <option v-for="c in filterOptions.classes" :key="c" :value="c">{{ c }}</option>
        </select>
        <select v-if="filterBarVisible.transport_status" v-model="filters.transport_status" class="filter-select" :class="filters.transport_status ? 'text-slate-900' : 'text-slate-500'">
          <option value="">Trạng thái vận chuyển</option>
          <option value="transporting">Đang đưa đón</option>
          <option value="pending">Chờ duyệt</option>
          <option value="paused">Tạm dừng</option>
          <option value="unregistered">Chưa đăng ký</option>
        </select>
        <select v-if="filterBarVisible.program_id" v-model="filters.program_id" class="filter-select" :class="filters.program_id ? 'text-slate-900' : 'text-slate-500'">
          <option value="">Chương trình</option>
          <option v-for="p in filterOptions.programs" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-if="filterBarVisible.grade" v-model="filters.grade" class="filter-select" :class="filters.grade ? 'text-slate-900' : 'text-slate-500'">
          <option value="">Khối</option>
          <option v-for="g in filterOptions.grades" :key="g" :value="g">{{ g }}</option>
        </select>
        <select v-if="filterBarVisible.student_status" v-model="filters.student_status" class="filter-select" :class="filters.student_status ? 'text-slate-900' : 'text-slate-500'">
          <option value="">Hồ sơ HS</option>
          <option value="active">Đang theo học</option>
          <option value="inactive">Ngưng học</option>
          <option value="transferred">Chuyển trường</option>
          <option value="graduated">Đã tốt nghiệp</option>
        </select>
        <select v-if="filterBarVisible.gender" v-model="filters.gender" class="filter-select" :class="filters.gender ? 'text-slate-900' : 'text-slate-500'">
          <option value="">Giới tính</option>
          <option v-for="g in filterOptions.genders || []" :key="g.value" :value="g.value">{{ g.label }}</option>
        </select>
        <select
          v-if="filterBarVisible.pickup_point && (filterOptions.pickup_points || []).length"
          v-model="filters.pickup_point"
          class="filter-select max-w-[14rem]"
          :class="filters.pickup_point ? 'text-slate-900' : 'text-slate-500'"
        >
          <option value="">Điểm đón</option>
          <option v-for="p in filterOptions.pickup_points" :key="p" :value="p">{{ p }}</option>
        </select>
        <input
          v-if="filterBarVisible.parent_phone"
          v-model="filters.parent_phone"
          type="search"
          placeholder="SĐT phụ huynh"
          class="h-9 min-w-[9rem] rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20"
        />
        <input
          v-if="filterBarVisible.address_contains"
          v-model="filters.address_contains"
          type="search"
          placeholder="Địa chỉ chứa…"
          class="h-9 min-w-[9rem] max-w-xs flex-1 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20"
        />
        <select v-if="filterBarVisible.per_page" v-model.number="filters.per_page" class="filter-select" :class="filters.per_page !== DEFAULT_PER_PAGE ? 'text-slate-900' : 'text-slate-500'">
          <option :value="DEFAULT_PER_PAGE">Số dòng/trang</option>
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>
    </AppFilterBar>

    <!-- Table -->
    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
      Không có học sinh phù hợp.
    </div>
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[60rem] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 px-3 py-3">
              <input type="checkbox" :checked="allSelected" class="h-4 w-4 rounded border-slate-300 accent-va-800" @change="toggleAll" />
            </th>
            <th class="w-12 px-3 py-3 font-semibold">#</th>
            <th class="px-3 py-3 font-semibold">Học sinh</th>
            <th v-if="colOn('code')" class="px-3 py-3 font-semibold">Mã HS</th>
            <th v-if="colOn('gender')" class="px-3 py-3 font-semibold">Giới tính</th>
            <th v-if="colOn('date_of_birth')" class="px-3 py-3 font-semibold">Ngày sinh</th>
            <th v-if="colOn('grade')" class="px-3 py-3 font-semibold">Khối</th>
            <th v-if="colOn('class_name')" class="px-3 py-3 font-semibold">Lớp</th>
            <th v-if="colOn('program')" class="px-3 py-3 font-semibold">Chương trình</th>
            <th v-if="colOn('parent_contact')" class="px-3 py-3 font-semibold">Liên hệ chính</th>
            <th v-if="colOn('father')" class="px-3 py-3 font-semibold">Cha</th>
            <th v-if="colOn('mother')" class="px-3 py-3 font-semibold">Mẹ</th>
            <th v-if="colOn('address')" class="px-3 py-3 font-semibold">Địa chỉ</th>
            <th v-if="colOn('pickup_point')" class="px-3 py-3 font-semibold">Điểm đón</th>
            <th v-if="colOn('note')" class="px-3 py-3 font-semibold">Ghi chú</th>
            <th v-if="colOn('transport_status')" class="px-3 py-3 font-semibold">Trạng thái</th>
            <th class="w-16 px-3 py-3 text-right font-semibold">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(s, i) in items" :key="s.id" class="transition hover:bg-slate-50/70">
            <td class="px-3 py-3">
              <input type="checkbox" :value="s.id" v-model="selected" class="h-4 w-4 rounded border-slate-300 accent-va-800" />
            </td>
            <td class="px-3 py-3 text-xs font-medium text-slate-400">{{ rowNumber(i) }}</td>

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
                  <div class="truncate text-xs text-slate-400">{{ studentMeta(s) }}</div>
                </div>
              </div>
            </td>

            <td v-if="colOn('code')" class="px-3 py-3 font-mono text-xs text-slate-700">{{ s.code || '—' }}</td>
            <td v-if="colOn('gender')" class="px-3 py-3 text-slate-700">{{ genderLabel(s.gender) || '—' }}</td>
            <td v-if="colOn('date_of_birth')" class="px-3 py-3 tabular-nums text-slate-700">{{ formatDob(s.date_of_birth) }}</td>
            <td v-if="colOn('grade')" class="px-3 py-3 font-medium text-slate-700">{{ s.grade || '—' }}</td>
            <td v-if="colOn('class_name')" class="px-3 py-3 font-medium text-slate-700">{{ s.class_name || '—' }}</td>

            <td v-if="colOn('program')" class="px-3 py-3">
              <template v-if="s.program">
                <div class="font-medium text-va-800">{{ s.program.name }}</div>
                <div class="mt-0.5 text-xs" :class="programSubClass(s.program.status)">
                  <span class="inline-flex items-center gap-1">
                    <component :is="programSubIcon(s.program.status)" class="h-3.5 w-3.5" />
                    {{ programSubLabel(s.program) }}
                  </span>
                </div>
              </template>
              <span v-else class="text-xs italic text-slate-400">Chưa gán chương trình</span>
            </td>

            <td v-if="colOn('parent_contact')" class="px-3 py-3">
              <div class="font-medium text-slate-700">{{ s.parent_name || '—' }}</div>
              <a v-if="s.parent_phone" :href="`tel:${s.parent_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.parent_phone }}</a>
            </td>
            <td v-if="colOn('father')" class="px-3 py-3">
              <div class="text-slate-700">{{ s.father_name || '—' }}</div>
              <a v-if="s.father_phone" :href="`tel:${s.father_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.father_phone }}</a>
            </td>
            <td v-if="colOn('mother')" class="px-3 py-3">
              <div class="text-slate-700">{{ s.mother_name || '—' }}</div>
              <a v-if="s.mother_phone" :href="`tel:${s.mother_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.mother_phone }}</a>
            </td>
            <td v-if="colOn('address')" class="max-w-[14rem] px-3 py-3 text-slate-600">
              <span class="line-clamp-2">{{ s.address || '—' }}</span>
            </td>
            <td v-if="colOn('pickup_point')" class="max-w-[12rem] px-3 py-3 text-slate-600">
              <span class="line-clamp-2">{{ s.pickup_point || '—' }}</span>
            </td>
            <td v-if="colOn('note')" class="max-w-[12rem] px-3 py-3 text-xs text-slate-500">
              <span class="line-clamp-2">{{ s.note || '—' }}</span>
            </td>

            <td v-if="colOn('transport_status')" class="px-3 py-3">
              <span :class="transportBadgeClass(s.transport_status)">
                <span class="h-1.5 w-1.5 rounded-full" :class="transportDotClass(s.transport_status)"></span>
                {{ transportLabel(s.transport_status) }}
              </span>
            </td>

            <td class="px-3 py-3 text-right">
              <AppRowActionsMenu :aria-label="`Hành động cho ${s.full_name}`">
                <button class="menu-item" @click="openEdit(s)"><PencilSquareIcon class="h-4 w-4" /> Chỉnh sửa</button>
                <button class="menu-item" @click="goEnroll(s)"><AcademicCapIcon class="h-4 w-4" /> Đăng ký tuyến</button>
                <button class="menu-item text-rose-600" @click="remove(s)"><TrashIcon class="h-4 w-4" /> Xóa</button>
              </AppRowActionsMenu>
            </td>
          </tr>
        </tbody>
      </table>

    </div>

    <nav
      v-if="!loading && paginationTotal > 0"
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
      aria-label="Phân trang danh sách học sinh"
    >
      <p class="text-sm text-slate-600">
        Hiển thị
        <span class="font-semibold text-slate-900">{{ pageFrom }}–{{ pageTo }}</span>
        trong
        <span class="font-semibold text-slate-900">{{ formatInt(paginationTotal) }}</span>
        học sinh
        <span class="text-slate-400">(trang {{ meta.current_page }}/{{ meta.last_page }})</span>
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <label class="mr-1 flex items-center gap-2 text-sm text-slate-600">
          <span class="whitespace-nowrap text-xs font-medium text-slate-500">Dòng/trang</span>
          <select
            v-model.number="filters.per_page"
            class="h-9 cursor-pointer rounded-lg border border-slate-200 bg-white px-2 pr-7 text-sm font-medium text-slate-800 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20"
            aria-label="Số dòng mỗi trang"
          >
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
        <Button variant="secondary" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">
          Trước
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
            @click="changePage(p)"
          >
            {{ p }}
          </button>
        </div>
        <Button variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)">
          Sau
        </Button>
      </div>
    </nav>

    <TpStudentFormModal v-if="showForm" :student="editing" @close="showForm = false" @saved="onSaved" />

    <TpStudentDetailModal
      :open="studentDetailOpen"
      :student-id="studentDetailId"
      @close="closeStudentDetail"
    />
  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  PlusIcon, ArrowPathIcon, ArrowUpTrayIcon, ArrowDownTrayIcon, MagnifyingGlassIcon,
  XMarkIcon, FunnelIcon, UsersIcon, TruckIcon, ClockIcon, UserMinusIcon, PencilSquareIcon,
  TrashIcon, AcademicCapIcon, CalendarDaysIcon, PauseCircleIcon, ExclamationCircleIcon,
  ViewColumnsIcon, ChevronDownIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useTpStudentListColumns } from '../../composables/useTpStudentListColumns.js'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import TpStudentFormModal from '../../components/transportProgram/TpStudentFormModal.vue'
import TpStudentDetailModal from '../../components/transportProgram/TpStudentDetailModal.vue'
import { listStudents, getStudent, deleteStudent, exportStudentsList } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'

const router = useRouter()
const { t } = useI18n()
const loading = ref(false)
const exporting = ref(false)
const items = ref([])
const selected = ref([])
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

const TP_STU_FILTER_VIS_IDS = [
  'class_name',
  'transport_status',
  'program_id',
  'grade',
  'student_status',
  'gender',
  'pickup_point',
  'parent_phone',
  'address_contains',
  'per_page',
]
const TP_STU_FILTER_VIS_DEFAULTS = {
  ...Object.fromEntries(TP_STU_FILTER_VIS_IDS.map((id) => [id, false])),
  gender: true,
  pickup_point: true,
  per_page: true,
}
const { colOn, setColumn, columnToggleOptions } = useTpStudentListColumns()
const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(TP_STU_FILTER_VIS_IDS, TP_STU_FILTER_VIS_DEFAULTS)

const filterMenuRef = ref(null)
const columnPickerRef = ref(null)
const tpStudentFilterBarRef = ref(null)
useDetailsAutoClose(columnPickerRef)
useDetailsAutoCloseWithin(tpStudentFilterBarRef)

const filterBarVisibilityOptions = [
  { id: 'class_name', label: 'Lớp' },
  { id: 'transport_status', label: 'Trạng thái vận chuyển' },
  { id: 'program_id', label: 'Chương trình' },
  { id: 'grade', label: 'Khối' },
  { id: 'student_status', label: 'Hồ sơ học sinh' },
  { id: 'gender', label: 'Giới tính' },
  { id: 'pickup_point', label: 'Điểm đón' },
  { id: 'parent_phone', label: 'SĐT phụ huynh' },
  { id: 'address_contains', label: 'Địa chỉ' },
  { id: 'per_page', label: 'Số dòng/trang' },
]

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.search?.trim()) n++
  if (filters.class_name) n++
  if (filters.transport_status) n++
  if (filters.program_id) n++
  if (filters.grade) n++
  if (filters.student_status) n++
  if (filters.gender) n++
  if (filters.pickup_point) n++
  if (filters.parent_phone?.trim()) n++
  if (filters.address_contains?.trim()) n++
  if (filters.per_page !== DEFAULT_PER_PAGE) n++
  return n
})

function studentStatusLabel(s) {
  const map = {
    active: 'Đang theo học',
    inactive: 'Ngưng học',
    transferred: 'Chuyển trường',
    graduated: 'Đã tốt nghiệp',
  }
  return map[s] ?? s
}

const programFilterLabel = computed(() => {
  const id = filters.program_id
  if (!id) return ''
  return filterOptions.programs.find((p) => String(p.id) === String(id))?.name ?? String(id)
})

function transportStatusLabel(s) {
  const map = { transporting: 'Đang đưa đón', pending: 'Chờ duyệt', paused: 'Tạm dừng', unregistered: 'Chưa đăng ký' }
  return map[s] ?? s
}

function onTpStudentFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

const hasActiveFilters = computed(() => activeFilterCount.value > 0)
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
    filters.per_page,
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
  exporting.value = true
  try {
    await exportStudentsList({
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
    })
    showAppSuccess('Đã tải xuống danh sách học sinh (.xlsx).')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    exporting.value = false
  }
}

async function remove(s) {
  const ok = await confirmAction({ title: 'Xóa học sinh', message: `Xóa ${s.full_name}?`, danger: true, confirmLabel: 'Xóa' })
  if (!ok) return
  try {
    await deleteStudent(s.id)
    showAppSuccess('Đã xóa.')
    load()
  } catch (err) {
    showAppErrorFromApi(err)
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
function formatDob(iso) {
  if (!iso) return '—'
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
function programSubIcon(status) {
  return { paused: PauseCircleIcon, draft: ExclamationCircleIcon }[status] || CalendarDaysIcon
}

const TRANSPORT_LABELS = { transporting: 'Đang đưa đón', pending: 'Chờ duyệt', paused: 'Tạm dừng', unregistered: 'Chưa đăng ký' }
function transportLabel(s) {
  return TRANSPORT_LABELS[s] || s
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
  onTpStudentFilterBarEnter()
  load()
})

onActivated(() => {
  onTpStudentFilterBarEnter()
})
</script>

<style scoped>
.filter-select {
  @apply h-9 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 pr-8 text-sm font-medium text-slate-700 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20;
}
.menu-item {
  @apply flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50;
}
</style>
