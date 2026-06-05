<template>
  <div class="mx-auto max-w-5xl space-y-4">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <button class="mb-1 inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600" @click="goBack">
          <ArrowLeftIcon class="h-3.5 w-3.5" /> Danh sách học sinh
        </button>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Import Danh sách Học sinh</h1>
        <p class="mt-1 text-sm text-slate-500">Tải lên tệp Excel hoặc CSV để nhập hàng loạt dữ liệu học sinh</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-va-300 hover:text-va-700"
        @click="toggleHistory"
      >
        <ClockIcon class="h-3.5 w-3.5" />
        Lịch sử import
        <ChevronDownIcon class="h-3 w-3 transition-transform" :class="showHistory ? 'rotate-180' : ''" />
      </button>
    </div>

    <!-- ── Import history panel ───────────────────────────────────── -->
    <div v-if="showHistory" class="rounded-xl border border-slate-200 bg-white">
      <div class="border-b border-slate-100 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
        5 lần import gần nhất
      </div>
      <div v-if="historyLoading" class="px-4 py-6 text-center text-sm text-slate-400">Đang tải…</div>
      <div v-else-if="!historyBatches.length" class="px-4 py-6 text-center text-sm text-slate-400">Chưa có lần import nào.</div>
      <div v-else class="divide-y divide-slate-100">
        <div
          v-for="b in historyBatches"
          :key="b.id"
          class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2.5 text-sm"
        >
          <span class="font-medium text-slate-800 truncate max-w-[14rem]" :title="b.original_filename">{{ b.original_filename }}</span>
          <span :class="historyStatusClass(b.status)" class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium">{{ historyStatusLabel(b.status) }}</span>
          <span class="text-slate-500 text-xs">{{ b.imported_rows ?? 0 }} nhập · {{ b.error_rows ?? 0 }} lỗi</span>
          <span class="ml-auto text-xs text-slate-400">{{ formatDate(b.completed_at || b.created_at) }}</span>
          <button
            v-if="b.has_error_report"
            type="button"
            class="inline-flex items-center gap-1 text-xs text-rose-600 hover:underline"
            @click="doDownloadErrorReportById(b.id)"
          >
            <ArrowDownTrayIcon class="h-3 w-3" /> Báo cáo lỗi
          </button>
        </div>
      </div>
    </div>

    <!-- ── Stepper — 4 bước ───────────────────────────────────────── -->
    <div class="flex items-center rounded-xl border border-slate-200 bg-white px-4 py-3">
      <template v-for="(p, i) in phases" :key="p.key">
        <div class="flex items-center gap-2">
          <span
            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
            :class="i < step ? 'bg-emerald-500 text-white' : i === step ? 'bg-va-800 text-white' : 'bg-slate-100 text-slate-400'"
          >
            <CheckIcon v-if="i < step" class="h-4 w-4" />
            <span v-else>{{ i + 1 }}</span>
          </span>
          <div class="hidden sm:block">
            <div class="text-[11px] font-medium" :class="i === step ? 'text-va-800' : 'text-slate-500'">Bước {{ i + 1 }}</div>
            <div class="text-xs font-semibold" :class="i === step ? 'text-slate-900' : 'text-slate-400'">{{ p.label }}</div>
          </div>
        </div>
        <div v-if="i < phases.length - 1" class="mx-3 h-px flex-1" :class="i < step ? 'bg-emerald-300' : 'bg-slate-200'" />
      </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         BƯỚC 0 — TẢI LÊN + CẤU HÌNH
    ══════════════════════════════════════════════════════════════════ -->
    <template v-if="step === 0">
      <div class="grid gap-4 lg:grid-cols-5">

        <!-- Upload + client preview -->
        <div class="space-y-4 lg:col-span-3">
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
              <div>
                <h2 class="text-sm font-semibold text-slate-900">Tệp import</h2>
                <p class="text-xs text-slate-500">.xlsx, .xls, .csv — tối đa 10MB</p>
              </div>
              <button
                type="button"
                :disabled="sampleDownloading"
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:border-va-300 hover:text-va-700 disabled:opacity-60"
                @click="doDownloadSample"
              >
                <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                {{ sampleDownloading ? 'Đang tải…' : 'File mẫu (.xlsx)' }}
              </button>
            </div>

            <div
              class="flex flex-col items-center rounded-lg border-2 border-dashed px-4 py-6 text-center transition"
              :class="dragging ? 'border-va-500 bg-va-50/50' : 'border-slate-200 bg-slate-50/30'"
              @dragover.prevent="dragging = true"
              @dragleave.prevent="dragging = false"
              @drop.prevent="onDrop"
            >
              <CloudArrowUpIcon class="h-8 w-8 text-va-500" />
              <p class="mt-2 text-sm font-medium text-slate-700">Kéo thả hoặc chọn tệp</p>
              <div v-if="file" class="mt-2 inline-flex max-w-full items-center gap-2 rounded-md bg-white px-2 py-1 text-xs text-slate-700 ring-1 ring-slate-200">
                <DocumentIcon class="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                <span class="max-w-[14rem] truncate">{{ file.name }}</span>
                <button type="button" class="shrink-0 text-slate-400 hover:text-rose-500" @click="clearFile"><XMarkIcon class="h-3.5 w-3.5" /></button>
              </div>
              <Button class="mt-3" variant="secondary" @click="fileInput?.click()">
                <FolderOpenIcon class="h-4 w-4" /> Chọn tệp
              </Button>
              <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="hidden" @change="onFile" />
            </div>

            <!-- Client-side preview -->
            <div v-if="file" class="mt-4">
              <div class="mb-2 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Xem trước</h3>
                <span v-if="previewLoading" class="text-xs text-slate-400">Đang đọc…</span>
                <span v-else-if="filePreview.totalRows" class="text-xs text-slate-500">{{ filePreview.totalRows }} dòng dữ liệu</span>
              </div>
              <!-- Shimmer skeleton while loading -->
              <div v-if="previewLoading" class="space-y-1.5">
                <div v-for="n in 3" :key="n" class="h-6 animate-pulse rounded bg-slate-100" />
              </div>
              <p v-else-if="filePreview.note" class="text-xs text-amber-700">{{ filePreview.note }}</p>
              <div v-else-if="filePreview.headers.length" class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full min-w-[32rem] text-left text-xs">
                  <thead class="bg-slate-800 text-white">
                    <tr>
                      <th v-for="(h, hi) in filePreview.headers" :key="hi" class="whitespace-nowrap px-2 py-1.5 font-medium">{{ h || `Cột ${hi + 1}` }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="(row, ri) in filePreview.rows" :key="ri" class="hover:bg-slate-50/80">
                      <td v-for="(cell, ci) in row" :key="ci" class="max-w-[10rem] truncate px-2 py-1.5 text-slate-700" :title="cell">{{ cell }}</td>
                    </tr>
                  </tbody>
                </table>
                <p v-if="filePreview.totalRows > filePreview.rows.length" class="border-t border-slate-100 bg-slate-50 px-2 py-1.5 text-[11px] text-slate-500">
                  Hiển thị {{ filePreview.rows.length }}/{{ filePreview.totalRows }} dòng
                </p>
              </div>
            </div>
          </div>

          <details class="rounded-lg border border-sky-100 bg-sky-50/60 px-3 py-2 text-xs text-slate-600">
            <summary class="cursor-pointer font-semibold text-sky-900">Hướng dẫn nhập file</summary>
            <ol class="mt-2 list-decimal space-y-1 pl-4 leading-relaxed">
              <li>Sheet <strong>Danh sách học sinh</strong> — dòng 1 là tiêu đề cột.</li>
              <li><strong>Họ tên</strong> bắt buộc (2–100 ký tự); <strong>Mã học sinh</strong> nên có để tránh trùng và hỗ trợ cập nhật.</li>
              <li>Kiểm tra bảng xem trước trước khi bấm tải lên. Tối đa 10MB.</li>
            </ol>
          </details>
        </div>

        <!-- Config sidebar -->
        <div class="space-y-4 lg:col-span-2">
          <div class="rounded-xl border border-va-200 bg-va-50/40 p-4">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-va-700/80">Chương trình đích</div>
            <select
              v-model="targetProgramId"
              class="mt-1.5 h-9 w-full rounded-md border border-va-200 bg-white px-2 text-sm text-va-900 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20"
            >
              <option :value="null">Không chỉ định</option>
              <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Cấu hình import</h2>
            <div class="mt-3 space-y-2">
              <label
                v-for="m in importModes"
                :key="m.value"
                class="flex cursor-pointer items-start gap-2 rounded-lg border p-2.5 text-xs transition"
                :class="config.mode === m.value ? 'border-va-500 bg-va-50/50' : 'border-slate-200 hover:border-slate-300'"
              >
                <input type="radio" :value="m.value" v-model="config.mode" class="mt-0.5 accent-va-800" />
                <span>
                  <span class="font-medium text-slate-800">{{ m.label }}</span>
                  <span class="block text-slate-400">{{ m.desc }}</span>
                </span>
              </label>
            </div>
            <div class="mt-3 space-y-2 border-t border-slate-100 pt-3">
              <label class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-700">Dòng đầu là tiêu đề cột</span>
                <Toggle v-model="config.has_header" />
              </label>
              <label class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-700">Bỏ qua dòng lỗi khi nhập</span>
                <Toggle v-model="config.skip_errors" />
              </label>
            </div>
            <Button class="mt-4 w-full" :loading="busy" :disabled="!file" @click="doUpload">
              Tải lên &amp; tiếp tục <ArrowRightIcon class="h-4 w-4" />
            </Button>
          </div>
        </div>

      </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════
         BƯỚC 1 — GHÉP CỘT
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else-if="step === 1" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
      <div>
        <h2 class="text-base font-semibold text-slate-900">Ghép cột dữ liệu</h2>
        <p class="text-xs text-slate-500">
          {{ batch.original_filename }} — {{ batch.total_rows }} dòng
          · Ghép tên cột trong file với trường tương ứng.
        </p>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <!-- Mapping selects: highlight state -->
        <div class="grid gap-2 sm:grid-cols-2">
          <div v-for="field in mappableFields" :key="field.key">
            <label class="mb-0.5 block text-[11px] font-semibold uppercase tracking-wide" :class="field.required && !mapping[field.key] ? 'text-rose-600' : 'text-slate-500'">
              {{ field.label }}{{ field.required ? ' *' : '' }}
            </label>
            <select
              v-model="mapping[field.key]"
              class="h-9 w-full rounded-md border px-2 text-sm focus:outline-none focus:ring-1"
              :class="mapping[field.key] ? 'border-emerald-300 bg-emerald-50/40 text-slate-800 focus:border-emerald-400 focus:ring-emerald-400/30' : field.required ? 'border-rose-300 bg-rose-50/30 focus:border-rose-400 focus:ring-rose-400/30' : 'border-slate-200 bg-white focus:border-va-500 focus:ring-va-500/30'"
            >
              <option value="">— Không ghép —</option>
              <option v-for="col in batch.header_row" :key="col" :value="col">{{ col }}</option>
            </select>
          </div>
        </div>

        <!-- Raw data preview -->
        <div class="overflow-x-auto rounded-lg border border-slate-200">
          <div class="border-b border-slate-100 bg-slate-50 px-2 py-1.5 text-[11px] font-semibold uppercase text-slate-500">
            Dữ liệu gốc — 5 dòng đầu
          </div>
          <table class="w-full min-w-[24rem] text-left text-xs">
            <thead class="bg-slate-800 text-white">
              <tr>
                <th v-for="col in batch.header_row" :key="col" class="whitespace-nowrap px-2 py-1 font-medium">
                  <span class="flex items-center gap-1">
                    <span>{{ col }}</span>
                    <CheckIcon v-if="isMappedColumn(col)" class="h-3 w-3 text-emerald-300" />
                  </span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in rawPreviewRows" :key="r.id">
                <td v-for="col in batch.header_row" :key="col" class="max-w-[8rem] truncate px-2 py-1 text-slate-700" :title="cellRaw(r, col)">{{ cellRaw(r, col) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex justify-between">
        <Button variant="secondary" @click="step = 0">Quay lại</Button>
        <Button :loading="busy" @click="doMapping">
          Kiểm tra dữ liệu <ArrowRightIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         BƯỚC 2 — KIỂM TRA & SỬA (validate + autofix gộp)
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else-if="step === 2" class="space-y-3">

      <!-- Stat cards — clickable để lọc -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <button
          type="button"
          class="rounded-xl border p-3 text-center transition ring-2 ring-transparent"
          :class="rowStatusFilter === '' ? 'border-slate-400 ring-slate-300 bg-slate-50' : 'border-slate-200 hover:border-slate-300 bg-white'"
          @click="setStatusFilter('')"
        >
          <div class="text-2xl font-bold text-slate-800">{{ batch.total_rows ?? 0 }}</div>
          <div class="text-xs text-slate-500">Tổng cộng</div>
        </button>
        <button
          type="button"
          class="rounded-xl border p-3 text-center transition ring-2 ring-transparent"
          :class="rowStatusFilter === 'valid' ? 'border-emerald-400 ring-emerald-200 bg-emerald-50' : 'border-emerald-200 hover:border-emerald-300 bg-emerald-50/60'"
          @click="setStatusFilter('valid')"
        >
          <div class="text-2xl font-bold text-emerald-700">{{ batch.valid_rows ?? 0 }}</div>
          <div class="text-xs text-emerald-600">Hợp lệ</div>
        </button>
        <button
          type="button"
          class="rounded-xl border p-3 text-center transition ring-2 ring-transparent"
          :class="rowStatusFilter === 'warning' ? 'border-amber-400 ring-amber-200 bg-amber-50' : 'border-amber-200 hover:border-amber-300 bg-amber-50/60'"
          @click="setStatusFilter('warning')"
        >
          <div class="text-2xl font-bold text-amber-700">{{ batch.warning_rows ?? 0 }}</div>
          <div class="text-xs text-amber-600">Cảnh báo</div>
        </button>
        <button
          type="button"
          class="rounded-xl border p-3 text-center transition ring-2 ring-transparent"
          :class="rowStatusFilter === 'error' ? 'border-rose-400 ring-rose-200 bg-rose-50' : 'border-rose-200 hover:border-rose-300 bg-rose-50/60'"
          @click="setStatusFilter('error')"
        >
          <div class="text-2xl font-bold text-rose-700">{{ batch.error_rows ?? 0 }}</div>
          <div class="text-xs text-rose-600">Lỗi</div>
        </button>
      </div>

      <!-- Auto-fix collapsible panel -->
      <details class="rounded-xl border border-slate-200 bg-white" :open="showAutoFix || undefined">
        <summary
          class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 [&::-webkit-details-marker]:hidden"
          @click.prevent="showAutoFix = !showAutoFix"
        >
          <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <WrenchScrewdriverIcon class="h-4 w-4 text-va-600" />
            Sửa tự động
            <span v-if="batch.warning_rows || batch.error_rows" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
              {{ (batch.warning_rows ?? 0) + (batch.error_rows ?? 0) }} dòng cần xem
            </span>
          </span>
          <ChevronDownIcon class="h-4 w-4 text-slate-400 transition-transform" :class="showAutoFix ? 'rotate-180' : ''" />
        </summary>
        <div v-if="showAutoFix" class="border-t border-slate-100 px-4 py-3">
          <p class="mb-3 text-xs text-slate-500">Chọn quy tắc rồi bấm Áp dụng. Hệ thống sẽ sửa và validate lại toàn bộ dữ liệu.</p>
          <div class="grid gap-2 sm:grid-cols-2">
            <label v-for="rule in fixRules" :key="rule.key" class="flex items-start gap-2 text-sm">
              <input type="checkbox" v-model="rules[rule.key]" class="mt-0.5 h-4 w-4 accent-va-800" />
              <span>
                <span class="font-medium text-slate-700">{{ rule.label }}</span>
                <span class="block text-xs text-slate-400">{{ rule.desc }}</span>
              </span>
            </label>
          </div>
          <Button class="mt-3" variant="secondary" :loading="busy" @click="doFixes">
            <WrenchScrewdriverIcon class="h-4 w-4" /> Áp dụng &amp; kiểm tra lại
          </Button>
        </div>
      </details>

      <!-- Table card -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-3 py-2.5">
          <!-- Status chips -->
          <div class="flex flex-wrap items-center gap-1">
            <button
              v-for="chip in statusChips"
              :key="chip.value"
              type="button"
              class="rounded-full px-2.5 py-0.5 text-xs font-medium transition"
              :class="rowStatusFilter === chip.value ? chip.activeClass : chip.idleClass"
              @click="setStatusFilter(chip.value)"
            >
              {{ chip.label }}
            </button>
          </div>
          <div class="ml-auto flex items-center gap-2">
            <!-- Search -->
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 pointer-events-none" />
              <input
                v-model="rowSearch"
                type="search"
                placeholder="Tìm họ tên, mã…"
                class="h-8 w-40 rounded-md border border-slate-200 bg-white pl-7 pr-2 text-sm placeholder:text-slate-400 focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
              />
            </div>
            <!-- Rows per page -->
            <label class="flex items-center gap-1.5 text-xs text-slate-600">
              <select
                v-model.number="rowsPerPage"
                class="h-8 rounded-md border border-slate-200 bg-white px-1.5 text-sm text-slate-800 focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                :disabled="rowsLoading || editingRowId != null"
                @change="onRowsPerPageChange"
              >
                <option v-for="n in rowPageSizeOptions" :key="n" :value="n">{{ n }}</option>
              </select>
              <span class="hidden sm:inline">dòng/trang</span>
            </label>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full min-w-[52rem] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-3 py-2 font-medium w-12">#</th>
                <th class="px-3 py-2 font-medium">Họ tên</th>
                <th class="px-3 py-2 font-medium">Mã</th>
                <th class="px-3 py-2 font-medium">Khối / Lớp</th>
                <th class="px-3 py-2 font-medium">SĐT PH</th>
                <th class="px-3 py-2 font-medium">Trạng thái</th>
                <th class="px-3 py-2 font-medium max-w-[12rem]">Ghi chú</th>
                <th class="px-3 py-2 font-medium text-center w-20">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="rowsLoading">
                <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-400">Đang tải dữ liệu…</td>
              </tr>
              <tr v-else-if="!displayRows.length">
                <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-400">Không có dòng nào.</td>
              </tr>
              <template v-else v-for="r in displayRows" :key="r.id">
                <!-- Display row -->
                <tr
                  class="transition-colors"
                  :class="[
                    r.import_status === 'skipped' ? 'opacity-50 bg-slate-50/60' : editingRowId === r.id ? 'bg-va-50/40' : 'hover:bg-slate-50/40',
                  ]"
                >
                  <td class="px-3 py-2 text-xs text-slate-400">{{ r.row_number }}</td>
                  <td class="px-3 py-2 text-slate-900" :class="r.import_status === 'skipped' ? 'line-through' : ''">
                    {{ displayCell(r, 'full_name') }}
                  </td>
                  <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ displayCell(r, 'code') }}</td>
                  <td class="px-3 py-2 text-slate-600">
                    <span v-if="r.data?.grade || r.data?.class_name">{{ [r.data?.grade, r.data?.class_name].filter(Boolean).join(' / ') }}</span>
                    <span v-else class="text-slate-300">—</span>
                  </td>
                  <td class="px-3 py-2 text-slate-600">{{ displayCell(r, 'parent_phone') }}</td>
                  <td class="px-3 py-2">
                    <span v-if="r.import_status === 'skipped'" class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Bỏ qua</span>
                    <span v-else :class="rowStatusClass(r.validation_status)">{{ statusLabel(r.validation_status) }}</span>
                  </td>
                  <td class="px-3 py-2 text-xs text-slate-500 max-w-[12rem] truncate" :title="(r.validation_errors || []).map((e) => e.message).join('; ')">
                    {{ (r.validation_errors || []).map((e) => e.message).join('; ') || '—' }}
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center justify-center gap-1">
                      <!-- Edit/Save/Cancel -->
                      <template v-if="editingRowId === r.id">
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50"
                          :disabled="rowSaving"
                          aria-label="Lưu"
                          @click="saveRowEdit(r)"
                        >
                          <CheckIcon class="h-3.5 w-3.5" />
                        </button>
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50"
                          :disabled="rowSaving"
                          aria-label="Hủy"
                          @click="cancelRowEdit"
                        >
                          <XMarkIcon class="h-3.5 w-3.5" />
                        </button>
                      </template>
                      <template v-else>
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:border-va-300 hover:text-va-700 disabled:opacity-40"
                          :disabled="editingRowId != null || rowSaving || r.import_status === 'skipped'"
                          aria-label="Chỉnh sửa"
                          @click="startRowEdit(r)"
                        >
                          <PencilSquareIcon class="h-3.5 w-3.5" />
                        </button>
                        <!-- Skip / Restore -->
                        <button
                          type="button"
                          class="inline-flex h-7 w-7 items-center justify-center rounded-md border text-xs disabled:opacity-40 transition"
                          :class="r.import_status === 'skipped'
                            ? 'border-emerald-200 text-emerald-600 hover:bg-emerald-50'
                            : 'border-slate-200 text-slate-400 hover:border-rose-300 hover:text-rose-600'"
                          :disabled="editingRowId != null || rowSaving"
                          :aria-label="r.import_status === 'skipped' ? 'Khôi phục' : 'Bỏ qua dòng này'"
                          :title="r.import_status === 'skipped' ? 'Khôi phục dòng' : 'Đánh dấu bỏ qua'"
                          @click="toggleSkipRow(r)"
                        >
                          <ArrowUturnLeftIcon v-if="r.import_status === 'skipped'" class="h-3.5 w-3.5" />
                          <TrashIcon v-else class="h-3.5 w-3.5" />
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>

                <!-- Expanded edit form row -->
                <tr v-if="editingRowId === r.id" class="bg-va-50/30">
                  <td colspan="8" class="px-3 pb-3 pt-0">
                    <div class="rounded-lg border border-va-200 bg-white p-3">
                      <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-va-700">Chỉnh sửa dòng {{ r.row_number }} — Enter lưu · Esc hủy</p>
                      <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Họ tên *</label>
                          <input
                            v-model="editDraft.full_name"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="Họ tên"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Mã học sinh</label>
                          <input
                            v-model="editDraft.code"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm font-mono focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="VD: HS001"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Khối</label>
                          <input
                            v-model="editDraft.grade"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="VD: 3"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Lớp</label>
                          <input
                            v-model="editDraft.class_name"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="VD: 3A1"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Phụ huynh</label>
                          <input
                            v-model="editDraft.parent_name"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="Họ tên phụ huynh"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">SĐT phụ huynh</label>
                          <input
                            v-model="editDraft.parent_phone"
                            type="tel"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="09x…"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Giới tính</label>
                          <input
                            v-model="editDraft.gender"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="Nam / Nữ"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Ngày sinh</label>
                          <input
                            v-model="editDraft.date_of_birth"
                            type="date"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Họ tên cha</label>
                          <input
                            v-model="editDraft.father_name"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">SĐT cha</label>
                          <input
                            v-model="editDraft.father_phone"
                            type="tel"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Họ tên mẹ</label>
                          <input
                            v-model="editDraft.mother_name"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div>
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">SĐT mẹ</label>
                          <input
                            v-model="editDraft.mother_phone"
                            type="tel"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div class="sm:col-span-2">
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Địa chỉ nhà</label>
                          <input
                            v-model="editDraft.address"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            placeholder="Địa chỉ"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div class="sm:col-span-2">
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Điểm đón</label>
                          <input
                            v-model="editDraft.pickup_point"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                          <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Ghi chú</label>
                          <input
                            v-model="editDraft.note"
                            type="text"
                            class="h-8 w-full rounded-md border border-slate-300 px-2 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                            @keydown.enter.prevent="saveRowEdit(r)"
                            @keydown.escape.prevent="cancelRowEdit"
                          />
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-3 py-2.5">
          <span class="text-xs text-slate-500">
            <template v-if="rowMeta.total">
              {{ paginationLabel }} · {{ rowMeta.total }} dòng
            </template>
            <template v-else>Chưa có dòng</template>
          </span>
          <div class="flex items-center gap-2">
            <Button
              variant="secondary"
              :disabled="rowsLoading || rowMeta.current_page <= 1 || editingRowId != null"
              @click="changeRowsPage(rowMeta.current_page - 1)"
            >
              Trước
            </Button>
            <span class="text-xs text-slate-500">{{ rowMeta.current_page }} / {{ rowMeta.last_page }}</span>
            <Button
              variant="secondary"
              :disabled="rowsLoading || rowMeta.current_page >= rowMeta.last_page || editingRowId != null"
              @click="changeRowsPage(rowMeta.current_page + 1)"
            >
              Sau
            </Button>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="flex justify-between">
        <Button variant="secondary" @click="step = 1">Quay lại</Button>
        <Button @click="step = 3">
          Tiếp tục nhập <ArrowRightIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         BƯỚC 3 — XÁC NHẬN & NHẬP
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else-if="step === 3" class="rounded-xl border border-slate-200 bg-white p-5">

      <!-- Pre-execute -->
      <template v-if="!result && !executing">
        <h2 class="text-base font-semibold text-slate-900">Xác nhận nhập dữ liệu</h2>
        <p class="mt-0.5 text-sm text-slate-500">Kiểm tra lại tóm tắt dữ liệu trước khi nhập chính thức.</p>

        <!-- Summary -->
        <div class="mt-4 grid grid-cols-3 gap-3">
          <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-center">
            <div class="text-xl font-bold text-emerald-700">{{ batch.valid_rows ?? 0 }}</div>
            <div class="text-xs text-emerald-600">Sẽ nhập</div>
          </div>
          <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-center">
            <div class="text-xl font-bold text-amber-700">{{ batch.warning_rows ?? 0 }}</div>
            <div class="text-xs text-amber-600">Cảnh báo</div>
          </div>
          <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-center">
            <div class="text-xl font-bold text-rose-700">{{ batch.error_rows ?? 0 }}</div>
            <div class="text-xs text-rose-600">Lỗi (bỏ qua)</div>
          </div>
        </div>

        <div class="mt-4 space-y-3">
          <label class="flex items-start gap-3 rounded-lg border p-3 text-sm transition"
            :class="options.include_warnings ? 'border-va-500 bg-va-50/40' : 'border-slate-200'"
          >
            <input type="checkbox" v-model="options.include_warnings" class="mt-0.5 h-4 w-4 accent-va-800" />
            <span>
              <span class="font-medium text-slate-800">Nhập cả dòng cảnh báo</span>
              <span class="block text-xs text-slate-400">{{ batch.warning_rows }} dòng cảnh báo sẽ được nhập (cùng dữ liệu gốc)</span>
            </span>
          </label>
          <label class="flex items-start gap-3 rounded-lg border p-3 text-sm transition"
            :class="options.update_existing ? 'border-va-500 bg-va-50/40' : 'border-slate-200'"
          >
            <input type="checkbox" v-model="options.update_existing" class="mt-0.5 h-4 w-4 accent-va-800" />
            <span>
              <span class="font-medium text-slate-800">Cập nhật học sinh đã tồn tại</span>
              <span class="block text-xs text-slate-400">Học sinh trùng mã sẽ được cập nhật thay vì bỏ qua</span>
            </span>
          </label>
        </div>

        <div class="mt-5 flex justify-between">
          <Button variant="secondary" @click="step = 2">Quay lại</Button>
          <Button :loading="false" @click="doExecute">
            <ArrowDownTrayIcon class="h-4 w-4" /> Bắt đầu nhập
          </Button>
        </div>
      </template>

      <!-- Executing — progress animation -->
      <template v-else-if="executing">
        <div class="flex flex-col items-center gap-4 py-6">
          <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-va-100">
            <ArrowDownTrayIcon class="h-7 w-7 animate-bounce text-va-600" />
          </div>
          <div class="text-center">
            <p class="text-base font-semibold text-slate-900">Đang xử lý…</p>
            <p class="mt-1 text-sm text-slate-500">Vui lòng không đóng tab này</p>
          </div>
          <!-- Animated progress bar -->
          <div class="w-full max-w-xs overflow-hidden rounded-full bg-slate-100">
            <div
              class="h-2.5 rounded-full bg-va-700 transition-all duration-500"
              :style="{ width: execProgress + '%' }"
            />
          </div>
          <p class="text-xs text-slate-500">{{ Math.round(execProgress) }}%</p>
        </div>
      </template>

      <!-- Result -->
      <template v-else-if="result">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100">
            <CheckIcon class="h-6 w-6 text-emerald-600" />
          </div>
          <div>
            <h2 class="text-base font-semibold text-slate-900">Import hoàn tất</h2>
            <p class="text-sm text-slate-500">{{ batch.original_filename }}</p>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-3">
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
            <div class="text-3xl font-bold text-emerald-700">{{ result.imported }}</div>
            <div class="mt-1 text-xs font-medium text-emerald-600">Đã nhập</div>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
            <div class="text-3xl font-bold text-slate-600">{{ result.skipped }}</div>
            <div class="mt-1 text-xs font-medium text-slate-500">Bỏ qua</div>
          </div>
          <div class="rounded-xl border p-4 text-center" :class="result.failed > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50'">
            <div class="text-3xl font-bold" :class="result.failed > 0 ? 'text-rose-700' : 'text-slate-400'">{{ result.failed }}</div>
            <div class="mt-1 text-xs font-medium" :class="result.failed > 0 ? 'text-rose-600' : 'text-slate-400'">Lỗi</div>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button
            v-if="result.failed > 0 || batch.has_error_report"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-100"
            @click="doDownloadErrorReport"
          >
            <ArrowDownTrayIcon class="h-4 w-4" /> Tải báo cáo lỗi (.xlsx)
          </button>
          <button
            v-if="result.imported > 0"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-va-300 bg-va-50 px-3 py-1.5 text-sm font-medium text-va-800 hover:bg-va-100"
            @click="goToStudentList"
          >
            <UsersIcon class="h-4 w-4" /> Xem danh sách học sinh
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-300"
            @click="resetWizard"
          >
            <ArrowPathIcon class="h-4 w-4" /> Import thêm
          </button>
        </div>

        <div class="mt-4 flex justify-end">
          <Button @click="goBack">Hoàn tất</Button>
        </div>
      </template>
    </div>

  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowDownTrayIcon, ArrowLeftIcon, ArrowRightIcon, ArrowUturnLeftIcon,
  CheckIcon, ChevronDownIcon, ClockIcon, CloudArrowUpIcon,
  DocumentIcon, FolderOpenIcon, MagnifyingGlassIcon,
  PencilSquareIcon, TrashIcon, UsersIcon, WrenchScrewdriverIcon,
  XMarkIcon, ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Toggle from '../../components/ui/Toggle.vue'
import {
  uploadImport,
  saveImportMapping,
  listImportRows,
  updateImportRow,
  skipImportRow,
  applyImportFixes,
  executeImport,
  downloadImportErrorReport,
  downloadImportSample,
  listImportBatches,
  listPrograms,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { parseImportFilePreview } from '../../composables/useTpImportFilePreview'

const router = useRouter()

// ── Wizard navigation ───────────────────────────────────────────────
const step = ref(0)  // 0 upload | 1 mapping | 2 validate | 3 confirm+result

const phases = [
  { key: 'upload', label: 'Tải lên' },
  { key: 'mapping', label: 'Ghép cột' },
  { key: 'validate', label: 'Kiểm tra & Sửa' },
  { key: 'import', label: 'Nhập dữ liệu' },
]

// ── Upload state ────────────────────────────────────────────────────
const busy = ref(false)
const file = ref(null)
const fileInput = ref(null)
const dragging = ref(false)
const programs = ref([])
const targetProgramId = ref(null)
const sampleDownloading = ref(false)
const previewLoading = ref(false)
const filePreview = reactive({ headers: [], rows: [], totalRows: 0, note: null })

// ── Batch & mapping ─────────────────────────────────────────────────
const batch = ref({ header_row: [], valid_rows: 0, warning_rows: 0, error_rows: 0, total_rows: 0 })
const rawPreviewRows = ref([])
const mapping = reactive({})

// ── Validate & table ────────────────────────────────────────────────
const rows = ref([])
const rowsPage = ref(1)
const rowsPerPage = ref(10)
const rowPageSizeOptions = [5, 10, 15, 20]
const rowMeta = ref({ current_page: 1, last_page: 1, total: 0 })
const rowsLoading = ref(false)
const rowStatusFilter = ref('')
const rowSearch = ref('')

// ── Inline edit ─────────────────────────────────────────────────────
const editingRowId = ref(null)
const editDraft = ref(null)
const rowSaving = ref(false)

// ── Auto-fix ────────────────────────────────────────────────────────
const showAutoFix = ref(false)
const fixRules = [
  { key: 'trim_whitespace', label: 'Cắt khoảng trắng thừa', desc: 'Bỏ space thừa đầu/cuối ô' },
  { key: 'normalize_phone', label: 'Chuẩn hóa số điện thoại', desc: 'Chuyển về dạng 0xx hoặc +84xx' },
  { key: 'capitalize_name', label: 'Viết hoa tên chuẩn', desc: 'Viết hoa chữ đầu mỗi từ trong Họ tên' },
  { key: 'uppercase_code', label: 'Viết hoa mã học sinh', desc: 'Chuyển mã về chữ hoa (VD: hs001 → HS001)' },
]
const rules = reactive({ trim_whitespace: true, normalize_phone: true, capitalize_name: false, uppercase_code: false })

// ── Execute & result ────────────────────────────────────────────────
const options = reactive({ include_warnings: true, update_existing: false })
const result = ref(null)
const executing = ref(false)
const execProgress = ref(0)

// ── History ─────────────────────────────────────────────────────────
const showHistory = ref(false)
const historyBatches = ref([])
const historyLoading = ref(false)

// ── Import config ────────────────────────────────────────────────────
const config = reactive({ mode: 'create', has_header: true, skip_errors: false })
const importModes = [
  { value: 'create', label: 'Thêm mới', desc: 'Chỉ thêm học sinh mới, bỏ qua bản ghi đã tồn tại' },
  { value: 'update', label: 'Cập nhật', desc: 'Cập nhật học sinh đã tồn tại theo mã' },
  { value: 'upsert', label: 'Thêm & Cập nhật', desc: 'Thêm mới và cập nhật học sinh trùng mã' },
]

const mappableFields = [
  { key: 'full_name', label: 'Họ tên', required: true },
  { key: 'code', label: 'Mã học sinh' },
  { key: 'grade', label: 'Khối' },
  { key: 'class_name', label: 'Lớp' },
  { key: 'gender', label: 'Giới tính' },
  { key: 'date_of_birth', label: 'Ngày sinh' },
  { key: 'father_name', label: 'Họ tên cha' },
  { key: 'father_phone', label: 'SĐT cha' },
  { key: 'mother_name', label: 'Họ tên mẹ' },
  { key: 'mother_phone', label: 'SĐT mẹ' },
  { key: 'parent_name', label: 'Liên hệ chính' },
  { key: 'parent_phone', label: 'SĐT liên hệ chính' },
  { key: 'address', label: 'Địa chỉ nhà' },
  { key: 'pickup_point', label: 'Điểm đón' },
  { key: 'note', label: 'Ghi chú' },
]

// ── Status chips config ──────────────────────────────────────────────
const statusChips = [
  { value: '', label: 'Tất cả', activeClass: 'bg-slate-800 text-white', idleClass: 'bg-slate-100 text-slate-600 hover:bg-slate-200' },
  { value: 'valid', label: 'Hợp lệ', activeClass: 'bg-emerald-600 text-white', idleClass: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' },
  { value: 'warning', label: 'Cảnh báo', activeClass: 'bg-amber-500 text-white', idleClass: 'bg-amber-50 text-amber-700 hover:bg-amber-100' },
  { value: 'error', label: 'Lỗi', activeClass: 'bg-rose-600 text-white', idleClass: 'bg-rose-50 text-rose-700 hover:bg-rose-100' },
  { value: 'skipped', label: 'Bỏ qua', activeClass: 'bg-slate-500 text-white', idleClass: 'bg-slate-100 text-slate-600 hover:bg-slate-200' },
]

// ── Computed ──────────────────────────────────────────────────────────
const displayRows = computed(() => {
  if (!rowSearch.value.trim()) return rows.value
  const q = rowSearch.value.trim().toLowerCase()
  return rows.value.filter((r) => {
    const d = r.data || {}
    return (
      String(d.full_name ?? '').toLowerCase().includes(q) ||
      String(d.code ?? '').toLowerCase().includes(q) ||
      String(d.class_name ?? '').toLowerCase().includes(q)
    )
  })
})

const paginationLabel = computed(() => {
  const { current_page, last_page, total } = rowMeta.value
  const start = (current_page - 1) * rowsPerPage.value + 1
  const end = Math.min(current_page * rowsPerPage.value, total)
  return `${start}–${end} / ${total}`
})

// ── Watchers ──────────────────────────────────────────────────────────
watch(file, async (f) => {
  filePreview.headers = []
  filePreview.rows = []
  filePreview.totalRows = 0
  filePreview.note = null
  if (!f) return
  previewLoading.value = true
  try {
    const p = await parseImportFilePreview(f)
    filePreview.headers = p.headers
    filePreview.rows = p.rows
    filePreview.totalRows = p.totalRows
    filePreview.note = p.note
  } catch {
    filePreview.note = 'Không đọc được file để xem trước — vẫn có thể tải lên.'
  } finally {
    previewLoading.value = false
  }
})

// ── File helpers ──────────────────────────────────────────────────────
function onFile(e) { file.value = e.target.files?.[0] ?? null }
function clearFile() {
  file.value = null
  if (fileInput.value) fileInput.value.value = ''
}
function onDrop(e) {
  dragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) file.value = f
}

// ── History ───────────────────────────────────────────────────────────
async function toggleHistory() {
  showHistory.value = !showHistory.value
  if (showHistory.value && !historyBatches.value.length) loadHistory()
}

async function loadHistory() {
  historyLoading.value = true
  try {
    const res = await listImportBatches({ limit: 5 })
    historyBatches.value = Array.isArray(res) ? res : []
  } catch {
    historyBatches.value = []
  } finally {
    historyLoading.value = false
  }
}

function historyStatusLabel(s) {
  return { completed: 'Hoàn thành', failed: 'Lỗi', importing: 'Đang nhập', uploaded: 'Tải lên', parsed: 'Đã parse', mapped: 'Đã map' }[s] || s
}
function historyStatusClass(s) {
  return s === 'completed'
    ? 'bg-emerald-100 text-emerald-700'
    : s === 'failed'
      ? 'bg-rose-100 text-rose-700'
      : 'bg-slate-100 text-slate-600'
}
function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return iso
  }
}

async function doDownloadErrorReportById(id) {
  try {
    await downloadImportErrorReport(id)
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

// ── Sample download ────────────────────────────────────────────────────
async function doDownloadSample() {
  sampleDownloading.value = true
  try {
    await downloadImportSample()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    sampleDownloading.value = false
  }
}

// ── Programs ───────────────────────────────────────────────────────────
async function loadPrograms() {
  try {
    const res = await listPrograms({ status: 'active' })
    programs.value = res?.items ?? res ?? []
  } catch {
    programs.value = []
  }
}

// ── Upload ─────────────────────────────────────────────────────────────
async function doUpload() {
  busy.value = true
  try {
    options.update_existing = config.mode !== 'create'
    options.include_warnings = config.skip_errors ? true : options.include_warnings
    batch.value = await uploadImport(file.value, targetProgramId.value)
    autoGuessMapping()
    await loadRawPreview()
    step.value = 1
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

// ── Mapping ────────────────────────────────────────────────────────────
function autoGuessMapping() {
  const guess = {
    full_name: ['họ tên', 'hoten', 'name', 'tên'],
    code: ['mã', 'code', 'mshs'],
    grade: ['khối', 'grade'],
    class_name: ['lớp', 'class'],
    gender: ['giới tính', 'gioi tinh', 'gender'],
    date_of_birth: ['ngày sinh', 'ngay sinh', 'dob', 'birth'],
    father_name: ['họ tên cha', 'ten cha', 'father'],
    father_phone: ['sđt cha', 'sdt cha', 'father phone'],
    mother_name: ['họ tên mẹ', 'ten me', 'mother'],
    mother_phone: ['sđt mẹ', 'sdt me', 'mother phone'],
    parent_name: ['phụ huynh', 'liên hệ', 'parent', 'người liên hệ'],
    parent_phone: ['sđt phụ huynh', 'sđt liên hệ', 'phone', 'điện thoại'],
    address: ['địa chỉ nhà', 'địa chỉ', 'address'],
    pickup_point: ['điểm đón', 'diem don', 'pickup'],
    note: ['ghi chú', 'ghichu', 'note'],
  }
  for (const f of mappableFields) {
    const found = (batch.value.header_row || []).find((c) =>
      (guess[f.key] || []).some((k) => String(c).toLowerCase().includes(k)),
    )
    if (found) mapping[f.key] = found
  }
}

function isMappedColumn(col) {
  return Object.values(mapping).includes(col)
}

function cellRaw(row, col) {
  const v = (row.data || {})[col]
  return v == null ? '' : String(v)
}

async function loadRawPreview() {
  rawPreviewRows.value = (await listImportRows(batch.value.id, { per_page: 5 }))?.items ?? []
}

async function doMapping() {
  if (!mapping.full_name) {
    showAppErrorFromApi({ response: { data: { message: 'Vui lòng ghép cột Họ tên.' } } })
    return
  }
  busy.value = true
  try {
    const cleaned = {}
    for (const [k, v] of Object.entries(mapping)) if (v) cleaned[k] = v
    const res = await saveImportMapping(batch.value.id, cleaned)
    batch.value = { ...batch.value, ...res }
    rowsPage.value = 1
    rowStatusFilter.value = ''
    cancelRowEdit()
    await loadRows(1)
    step.value = 2
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

// ── Rows & pagination ──────────────────────────────────────────────────
async function loadRows(page = rowsPage.value) {
  if (!batch.value?.id) return
  rowsLoading.value = true
  try {
    const params = { per_page: rowsPerPage.value, page }
    if (rowStatusFilter.value) params.status = rowStatusFilter.value
    const res = await listImportRows(batch.value.id, params)
    rows.value = res?.items ?? []
    const meta = res?.meta ?? {}
    rowMeta.value = {
      current_page: meta.current_page ?? page,
      last_page: meta.last_page ?? 1,
      total: meta.total ?? rows.value.length,
    }
    rowsPage.value = rowMeta.value.current_page
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    rowsLoading.value = false
  }
}

function setStatusFilter(status) {
  rowStatusFilter.value = status
  rowsPage.value = 1
  rowSearch.value = ''
  cancelRowEdit()
  loadRows(1)
}

function onRowsPerPageChange() {
  cancelRowEdit()
  rowsPage.value = 1
  loadRows(1)
}

function changeRowsPage(page) {
  if (page < 1 || page > rowMeta.value.last_page) return
  cancelRowEdit()
  rowsPage.value = page
  loadRows(page)
}

// ── Auto-fix ───────────────────────────────────────────────────────────
async function doFixes() {
  busy.value = true
  try {
    const res = await applyImportFixes(batch.value.id, rules)
    showAppSuccess(`Đã sửa ${res?.fixed ?? 0} dòng.`)
    batch.value = {
      ...batch.value,
      valid_rows: res.valid ?? batch.value.valid_rows,
      warning_rows: res.warning ?? batch.value.warning_rows,
      error_rows: res.error ?? batch.value.error_rows,
    }
    await loadRows(1)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

// ── Inline edit ────────────────────────────────────────────────────────
function startRowEdit(row) {
  editingRowId.value = row.id
  const d = row.data || {}
  editDraft.value = {
    full_name: d.full_name ?? '',
    code: d.code ?? '',
    grade: d.grade ?? '',
    class_name: d.class_name ?? '',
    gender: d.gender ?? '',
    date_of_birth: d.date_of_birth ?? '',
    father_name: d.father_name ?? '',
    father_phone: d.father_phone ?? '',
    mother_name: d.mother_name ?? '',
    mother_phone: d.mother_phone ?? '',
    parent_name: d.parent_name ?? '',
    parent_phone: d.parent_phone ?? '',
    address: d.address ?? '',
    pickup_point: d.pickup_point ?? '',
    note: d.note ?? '',
  }
}

function cancelRowEdit() {
  editingRowId.value = null
  editDraft.value = null
}

async function saveRowEdit(row) {
  if (!editDraft.value) return
  rowSaving.value = true
  try {
    const res = await updateImportRow(batch.value.id, row.id, { ...editDraft.value })
    if (res?.row) {
      const idx = rows.value.findIndex((x) => x.id === row.id)
      if (idx >= 0) rows.value[idx] = { ...rows.value[idx], ...res.row }
    }
    if (res) {
      batch.value = {
        ...batch.value,
        valid_rows: res.valid_rows ?? batch.value.valid_rows,
        warning_rows: res.warning_rows ?? batch.value.warning_rows,
        error_rows: res.error_rows ?? batch.value.error_rows,
      }
    }
    cancelRowEdit()
    showAppSuccess('Đã cập nhật dòng.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    rowSaving.value = false
  }
}

async function toggleSkipRow(row) {
  const newSkip = row.import_status !== 'skipped'
  rowSaving.value = true
  try {
    const res = await skipImportRow(batch.value.id, row.id, newSkip)
    if (res?.row) {
      const idx = rows.value.findIndex((x) => x.id === row.id)
      if (idx >= 0) rows.value[idx] = { ...rows.value[idx], ...res.row }
    }
    if (res) {
      batch.value = {
        ...batch.value,
        valid_rows: res.valid_rows ?? batch.value.valid_rows,
        warning_rows: res.warning_rows ?? batch.value.warning_rows,
        error_rows: res.error_rows ?? batch.value.error_rows,
      }
    }
    showAppSuccess(newSkip ? 'Đã đánh dấu bỏ qua.' : 'Đã khôi phục dòng.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    rowSaving.value = false
  }
}

// ── Execute ────────────────────────────────────────────────────────────
async function doExecute() {
  executing.value = true
  execProgress.value = 0
  result.value = null

  // Animate fake progress 0 → 85%
  const timer = setInterval(() => {
    if (execProgress.value < 85) {
      execProgress.value = Math.min(85, execProgress.value + Math.random() * 6 + 1)
    }
  }, 400)

  try {
    result.value = await executeImport(batch.value.id, options)
    clearInterval(timer)
    execProgress.value = 100
    batch.value = { ...batch.value, has_error_report: result.value.failed > 0 }
    showAppSuccess(`Nhập thành công ${result.value.imported} học sinh.`)
    loadHistory()
  } catch (err) {
    clearInterval(timer)
    execProgress.value = 0
    showAppErrorFromApi(err)
  } finally {
    executing.value = false
  }
}

// ── Result actions ─────────────────────────────────────────────────────
async function doDownloadErrorReport() {
  try {
    await downloadImportErrorReport(batch.value.id)
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

function goToStudentList() {
  router.push({ name: 'tpStudents' })
}

function resetWizard() {
  step.value = 0
  file.value = null
  result.value = null
  executing.value = false
  execProgress.value = 0
  batch.value = { header_row: [], valid_rows: 0, warning_rows: 0, error_rows: 0, total_rows: 0 }
  rows.value = []
  Object.keys(mapping).forEach((k) => delete mapping[k])
  rowStatusFilter.value = ''
  rowSearch.value = ''
  rowsPage.value = 1
  cancelRowEdit()
}

// ── Display helpers ────────────────────────────────────────────────────
function rowStatusClass(s) {
  const base = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium '
  return base + ({ valid: 'bg-emerald-100 text-emerald-700', warning: 'bg-amber-100 text-amber-700', error: 'bg-rose-100 text-rose-700' }[s] || 'bg-slate-100 text-slate-600')
}

function statusLabel(s) {
  return { valid: 'Hợp lệ', warning: 'Cảnh báo', error: 'Lỗi', pending: 'Chờ' }[s] || s
}

function displayCell(row, key) {
  const v = row.data?.[key]
  return v == null || v === '' ? '—' : String(v)
}

function goBack() { router.push({ name: 'tpStudents' }) }

onMounted(() => {
  loadPrograms()
})
</script>
