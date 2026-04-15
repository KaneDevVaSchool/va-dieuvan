<template>
  <div class="dispatch-wizard space-y-6 pb-10 text-slate-900">
    <!-- Header -->
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Tạo yêu cầu điều vận</h1>
        <p class="mt-1 text-sm text-slate-600">
          <span class="font-medium text-va-800">Yêu cầu mới</span>
          <span class="text-slate-400"> • </span>
          {{ draftLabel }}
        </p>
     
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          @click="onCancel"
        >
          Hủy
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
          @click="saveDraft"
        >
          <DocumentArrowDownIcon class="h-4 w-4 text-slate-500" />
          Lưu nháp
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
          title="Xem và mở các bản nháp đã lưu trên trình duyệt"
          @click="openDraftsModal"
        >
          <ClipboardDocumentListIcon class="h-4 w-4 text-slate-500" />
          Bản nháp đã lưu
        </button>
        <button
          v-if="activeDraftId"
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-800"
          title="Xóa bản nháp đang mở (không xóa các bản khác trong danh sách)"
          @click="openClearDraftModal"
        >
          Xóa nháp
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="headerPrimaryDisabled"
          @click="primaryAction"
        >
          <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
          {{ headerPrimaryLabel }}
          <ArrowRightIcon v-if="!loading" class="h-4 w-4" />
        </button>
      </div>
    </header>

    <!-- Stepper — timeline -->
    <nav
      class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm sm:p-3"
      aria-label="Các bước"
    >
      <ol
        class="flex snap-x snap-mandatory items-stretch gap-0 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:gap-0 [&::-webkit-scrollbar]:hidden"
      >
        <template v-for="(s, i) in steps" :key="s.id">
          <li class="flex min-w-[44%] shrink-0 snap-start flex-col sm:min-w-0 sm:flex-1">
            <div class="flex items-center">
              <button
                type="button"
                class="group flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left antialiased transition sm:flex-col sm:items-center sm:gap-2 sm:px-2 sm:py-2"
                :class="
                  i > maxReachedStep
                    ? 'cursor-not-allowed opacity-45'
                    : step === i
                      ? 'border border-va-800/25 bg-slate-50 text-slate-900 shadow-sm'
                      : i < step
                        ? 'border border-transparent text-slate-800 hover:bg-emerald-50'
                        : 'border border-transparent text-slate-500 hover:bg-slate-50'
                "
                :disabled="i > maxReachedStep"
                :aria-current="step === i ? 'step' : undefined"
                @click="goStep(i)"
              >
                <span
                  class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold shadow-sm transition sm:h-8 sm:w-8 sm:text-[11px]"
                  :class="
                    step === i
                      ? 'bg-va-800 text-white ring-1 ring-va-900/20'
                      : i < step
                        ? 'bg-emerald-500 text-white'
                        : i <= maxReachedStep
                          ? 'bg-slate-200 text-slate-600 group-hover:bg-slate-300'
                          : 'bg-slate-100 text-slate-400'
                  "
                >
                  <CheckIcon v-if="i < step" class="h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" />
                  <span v-else>{{ i + 1 }}</span>
                </span>
                <span
                  class="min-w-0 flex-1 text-[11px] font-semibold leading-snug sm:text-center sm:text-xs sm:leading-snug"
                  :class="step === i ? 'text-slate-900' : i < step ? 'text-slate-800' : 'text-slate-500'"
                >
                  {{ s.title }}
                </span>
              </button>
              <div
                v-if="i < steps.length - 1"
                class="mx-0.5 hidden h-0.5 w-5 shrink-0 rounded-full bg-slate-200 sm:block md:w-8 lg:w-11"
                aria-hidden="true"
              />
            </div>
          </li>
        </template>
      </ol>
    </nav>

    <div class="mx-auto max-w-6xl">
      <!-- Main card -->
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8 lg:p-10">
        <!-- Step 1 -->
        <div v-show="step === 0">
          <h2 class="text-lg font-semibold text-slate-900">1. Chọn loại dịch vụ</h2>
          <p class="mt-1 text-sm text-slate-600">Chọn đúng loại để form bước sau hiển thị đúng (hành khách / hàng hóa).</p>
          <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <button
              v-for="opt in tripTypeOptions"
              :key="opt.value"
              type="button"
              class="group relative flex flex-col items-center rounded-xl border-2 p-4 text-center transition"
              :class="
                form.trip_type === opt.value
                  ? opt.selectedClass
                  : 'border-slate-200 bg-slate-50 hover:border-slate-300'
              "
              @click="form.trip_type = opt.value"
            >
              <component :is="opt.icon" class="mb-3 h-10 w-10 opacity-90" :class="opt.iconClass" />
              <span class="font-semibold text-slate-900">{{ opt.label }}</span>
              <span class="mt-1 text-xs leading-snug text-slate-600">{{ opt.hint }}</span>
              <span
                v-if="opt.badge"
                class="mt-2 rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-orange-800"
              >
                {{ opt.badge }}
              </span>
            </button>
          </div>
        </div>

        <!-- Step 2 -->
        <div v-show="step === 1" class="space-y-8">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">2. Thông tin người đề nghị &amp; thời gian</h2>
          </div>

          <div class="grid gap-8 xl:grid-cols-12">
            <!-- A + C -->
            <div class="space-y-6 xl:col-span-5">
              <div class="dw-fieldset">
                <h3 class="dw-section-title">Người đề nghị</h3>
                <div class="relative">
                  <label class="dw-label">
                    <span>Tìm theo tên <span class="dw-req" aria-hidden="true">*</span></span>
                    <span
                      class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
                      title="Tối thiểu 2 ký tự. Chọn một kết quả để điền email, SĐT, đơn vị / phòng ban."
                    >
                      <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                    </span>
                  </label>
                  <input
                    v-model="requesterSearchQ"
                    type="search"
                    autocomplete="off"
                    placeholder="Ví dụ: Nguyễn Văn…"
                    class="dw-input"
                    @input="scheduleRequesterSearch"
                    @focus="onRequesterSearchFocus"
                    @blur="onRequesterSearchBlur"
                  />
                  <div
                    v-if="requesterSearchLoading"
                    class="absolute right-3 top-[2.125rem] h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-va-800"
                  />
                  <ul
                    v-if="requesterDropdownOpen && requesterSearchResults.length"
                    class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-black/5"
                    role="listbox"
                  >
                    <li v-for="u in requesterSearchResults" :key="u.id">
                      <button
                        type="button"
                        class="flex w-full flex-col gap-0.5 px-3 py-2.5 text-left transition hover:bg-va-800/5"
                        @mousedown.prevent="pickRequester(u)"
                      >
                        <span class="font-medium text-slate-900">{{ u.name }}</span>
                        <span class="truncate text-xs text-slate-500">{{ u.email }}</span>
                      </button>
                    </li>
                  </ul>
                </div>
                <label class="mt-4 block">
                  <span class="dw-label-text">Họ và tên <span class="dw-req" aria-hidden="true">*</span></span>
                  <input v-model="form.requester_name" type="text" class="dw-input" placeholder="Điền hoặc chọn từ tìm kiếm" />
                </label>
                <label class="mt-3 block">
                  <span class="dw-label-text">Email VA <span class="dw-req" aria-hidden="true">*</span></span>
                  <input v-model="form.requester_email" type="email" placeholder="ten@va.edu.vn" class="dw-input" />
                </label>
                <label class="mt-3 block">
                  <span class="dw-label-text">Số điện thoại</span>
                  <input
                    v-model="form.requester_phone"
                    type="text"
                    inputmode="numeric"
                    autocomplete="tel"
                    placeholder="Ví dụ: 0901234567"
                    class="dw-input"
                    maxlength="11"
                    @input="onRequesterPhoneInput"
                  />
                </label>
                <label class="mt-3 block">
                  <span class="dw-label-text">Đơn vị / phòng ban</span>
                  <input v-model="form.requester_unit" type="text" placeholder="Tự điền khi chọn nhân sự" class="dw-input" />
                </label>
              </div>

              <div class="dw-fieldset">
                <h3 class="dw-section-title">Thời gian</h3>
                <label class="block">
                  <span class="dw-label-text" title="Ngày lập đề xuất thực tế. Bấm vào ô để mở lịch.">
                    Ngày đề xuất <span class="dw-req" aria-hidden="true">*</span>
                  </span>
                  <input
                    v-model="form.proposed_date"
                    type="date"
                    lang="vi"
                    class="dw-input dw-date-input mt-1"
                    @click="openDatePickerFromInput($event)"
                  />
                </label>
                <label class="mt-4 block">
                  <span class="dw-label-text" title="Tự điền theo ngày đề xuất; có thể chỉnh lại nếu khác.">
                    Ngày cần sử dụng xe <span class="dw-req" aria-hidden="true">*</span>
                  </span>
                  <input
                    v-model="form.date_needed"
                    type="date"
                    lang="vi"
                    class="dw-input dw-date-input mt-1"
                    @click="openDatePickerFromInput($event)"
                  />
                </label>
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex min-w-[8rem] items-center justify-between gap-3 sm:flex-col sm:items-stretch">
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-900">Gấp</span>
                        <span
                          class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
                          title="Bật khi cần xử lý nhanh hơn quy định; ghi rõ lý do."
                        >
                          <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                        </span>
                      </div>
                      <button
                        type="button"
                        role="switch"
                        :aria-checked="form.is_urgent"
                        class="inline-flex h-7 w-12 shrink-0 cursor-pointer items-center rounded-full px-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-va-800/30"
                        :class="form.is_urgent ? 'justify-end bg-va-800' : 'justify-start bg-slate-300'"
                        @click="form.is_urgent = !form.is_urgent"
                      >
                        <span class="pointer-events-none h-6 w-6 rounded-full bg-white shadow-sm ring-1 ring-black/5" />
                      </button>
                    </div>
                    <div class="min-w-0 flex-1">
                      <label class="block">
                        <span class="dw-label-text">Lý do <span v-if="form.is_urgent" class="dw-req" aria-hidden="true">*</span></span>
                        <input
                          v-model="form.urgent_reason"
                          type="text"
                          placeholder="Bắt buộc khi bật Gấp"
                          :disabled="!form.is_urgent"
                          class="dw-input disabled:cursor-not-allowed disabled:opacity-45"
                        />
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-6 xl:col-span-7">
              <div class="dw-fieldset">
                <h3 class="dw-section-title">Mục đích sử dụng</h3>
                <div
                  v-if="form.trip_type === 'point_to_point'"
                  class="mb-4 flex flex-wrap gap-3"
                  role="radiogroup"
                  aria-label="Phân loại mục đích (Điểm — Điểm)"
                >
                  <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-800 shadow-sm transition hover:border-va-800/25 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50 has-[:checked]:ring-1 has-[:checked]:ring-sky-200"
                  >
                    <input
                      v-model="form.point_purpose_kind"
                      type="radio"
                      value="point_to_point"
                      class="h-4 w-4 border-slate-300 text-va-800 focus:ring-va-800"
                    />
                    <span>Điểm — Điểm</span>
                  </label>
                  <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-800 shadow-sm transition hover:border-va-800/25 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50 has-[:checked]:ring-1 has-[:checked]:ring-sky-200"
                  >
                    <input
                      v-model="form.point_purpose_kind"
                      type="radio"
                      value="extracurricular"
                      class="h-4 w-4 border-slate-300 text-va-800 focus:ring-va-800"
                    />
                    <span>Hoạt động ngoại khóa</span>
                  </label>
                </div>
                <label class="block">
                  <span class="dw-label-text">Mục đích sử dụng <span class="dw-req" aria-hidden="true">*</span></span>
                  <textarea
                    v-model="form.purpose"
                    rows="4"
                    placeholder="Mô tả ngắn gọn mục đích sử dụng xe / chuyến…"
                    class="dw-input min-h-[6rem] resize-y"
                  />
                </label>
                <div class="mt-4">
                  <span class="mb-2 flex flex-nowrap items-center gap-1.5">
                    <span class="text-xs font-medium text-slate-700">Căn cứ đề xuất — đính kèm</span>
                    <span
                      class="inline-flex shrink-0 cursor-help text-slate-400 hover:text-slate-600"
                      title="Tải tờ trình, văn bản căn cứ (PDF, ảnh). Tối đa 10MB."
                    >
                      <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                    </span>
                  </span>
                  <div
                    class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 transition"
                    :class="
                      basisDragOver
                        ? 'border-va-600 bg-va-800/5'
                        : 'border-slate-200 bg-slate-50/80 hover:border-slate-300 hover:bg-slate-50'
                    "
                    @dragover.prevent="basisDragOver = true"
                    @dragleave.prevent="basisDragOver = false"
                    @drop.prevent="onBasisDrop"
                    @click="basisFileInput?.click()"
                  >
                    <CloudArrowUpIcon class="h-10 w-10 text-slate-400" aria-hidden="true" />
                    <p class="mt-2 text-center text-sm font-medium text-slate-800">Kéo thả tệp vào đây hoặc bấm để chọn</p>
                    <p class="mt-1 text-center text-xs text-slate-500">PDF, ảnh (JPG, PNG) — tối đa 10MB</p>
                    <input
                      ref="basisFileInput"
                      type="file"
                      class="sr-only"
                      accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"
                      @change="onBasisFileChange"
                    />
                  </div>
                  <p v-if="basisFileError" class="mt-2 text-xs font-medium text-rose-600">{{ basisFileError }}</p>
                  <div
                    v-if="basisFile"
                    class="mt-3 flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm"
                  >
                    <PaperClipIcon class="h-5 w-5 shrink-0 text-va-800" aria-hidden="true" />
                    <div class="min-w-0 flex-1">
                      <div class="truncate font-medium text-slate-900">{{ basisFile.name }}</div>
                      <div class="text-xs text-slate-500">{{ formatFileSize(basisFile.size) }}</div>
                    </div>
                    <button
                      type="button"
                      class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                      @click.stop="clearBasisFile"
                    >
                      Gỡ
                    </button>
                  </div>
                </div>
              </div>

              <div class="dw-fieldset">
                <h3 class="dw-section-title">Đối tượng được phân bổ</h3>
    
                <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/80 p-2">
                  <label
                    v-for="t in targetOptions"
                    :key="t"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-white"
                  >
                    <input v-model="form.targets" type="checkbox" :value="t" class="h-3.5 w-3.5 rounded border-slate-300 text-va-800" />
                    <span class="text-slate-800">{{ t }}</span>
                  </label>
                </div>
                <p v-if="form.targets.length" class="mt-2 text-xs text-slate-500">Đã chọn {{ form.targets.length }} mục.</p>
              </div>

              <div class="dw-fieldset">
                <h3 class="dw-section-title">Nhân sự phụ trách điều phối</h3>
    
                <div class="relative">
                  <label class="dw-label">
                    <span>Tìm theo tên</span>
                    <span
                      class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
                      title="Giống mục A: chọn nhân sự để điền họ tên, email, SĐT."
                    >
                      <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                    </span>
                  </label>
                  <input
                    v-model="coordinatorSearchQ"
                    type="search"
                    autocomplete="off"
                    placeholder="Gõ họ tên nhân sự điều phối…"
                    class="dw-input"
                    @input="scheduleCoordinatorSearch"
                    @focus="onCoordinatorSearchFocus"
                    @blur="onCoordinatorSearchBlur"
                  />
                  <div
                    v-if="coordinatorSearchLoading"
                    class="absolute right-3 top-[2.125rem] h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-va-800"
                  />
                  <ul
                    v-if="coordinatorDropdownOpen && coordinatorSearchResults.length"
                    class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-black/5"
                  >
                    <li v-for="u in coordinatorSearchResults" :key="u.id">
                      <button
                        type="button"
                        class="flex w-full flex-col gap-0.5 px-3 py-2.5 text-left transition hover:bg-va-800/5"
                        @mousedown.prevent="pickCoordinator(u)"
                      >
                        <span class="font-medium text-slate-900">{{ u.name }}</span>
                        <span class="truncate text-xs text-slate-500">{{ u.email }}</span>
                      </button>
                    </li>
                  </ul>
                </div>
                <label class="mt-4 block">
                  <span class="dw-label-text">Họ tên</span>
                  <input
                    v-model="form.coordinator_name"
                    type="text"
                    class="dw-input"
                    placeholder="Họ và tên người phụ trách"
                  />
                </label>
                <label class="mt-3 block">
                  <span class="dw-label-text">Email nhân viên</span>
                  <input
                    v-model="form.coordinator_email"
                    type="email"
                    class="dw-input"
                    placeholder="email@va.edu.vn"
                  />
                </label>
                <label class="mt-3 block">
                  <span class="dw-label-text">SĐT</span>
                  <input
                    v-model="form.coordinator_phone"
                    type="text"
                    inputmode="numeric"
                    autocomplete="tel"
                    placeholder="Chỉ số, ví dụ 090…"
                    class="dw-input"
                    maxlength="11"
                    @input="onCoordinatorPhoneInput"
                  />
                </label>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                <label class="mb-1 block text-xs font-medium text-slate-600">Kênh gửi</label>
                <select
                  v-model="form.source_channel"
                  class="dw-input bg-white"
                >
                  <option value="portal">Portal</option>
                  <option value="zalo">Zalo</option>
                  <option value="paper">Phiếu giấy</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3 — lazy chunk + chỉ mount khi step === 2 -->
        <DispatchWizardStep3 v-if="step === 2" />

        <!-- Step 4 -->
        <div v-if="step === 3" class="space-y-6">
          <h2 class="text-lg font-semibold text-slate-900">4. Xác nhận &amp; nộp</h2>

          <div
            v-if="isPointToPointTrip"
            class="overflow-hidden rounded-xl border border-va-800/20 bg-white shadow-sm ring-1 ring-slate-900/5"
          >
            <div class="border-b border-slate-100 bg-va-800/5 px-4 py-3">
              <div class="font-semibold text-slate-900">Phiếu BM.02 / MH.QT.04 (Điểm — Điểm)</div>
              <p class="mt-0.5 text-xs text-slate-600">
                Điền tự động theo mẫu Excel BM.02/MH.QT.04 (Điểm — Điểm). Dùng <span class="font-medium">Làm mới</span> sau khi sửa
                ở các bước trước.
              </p>
            </div>
            <div class="p-4">
              <div v-if="bm02Loading" class="flex items-center gap-2 text-sm text-slate-600">
                <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-va-800" />
                Đang tạo bản xem trước…
              </div>
              <div v-else-if="bm02PreviewError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                {{ bm02PreviewError }}
              </div>
              <template v-else>
                <div class="flex flex-wrap items-center gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 disabled:opacity-40"
                    :disabled="bm02Loading"
                    title="Tạo lại bản xem trước sau khi sửa dữ liệu các bước trước"
                    @click="loadBm02Preview"
                  >
                    <ArrowPathIcon class="h-4 w-4 text-slate-600" :class="{ 'animate-spin': bm02Loading }" />
                    Làm mới
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 disabled:opacity-40"
                    :disabled="!bm02ExcelBase64"
                    @click="downloadBm02Excel"
                  >
                    <DocumentArrowDownIcon class="h-4 w-4 text-slate-500" />
                    Tải Excel
                  </button>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-slate-500">
                  Xem trước dạng <span class="font-medium">sheet như Google Sheet</span> bên dưới. Dùng
                  <span class="font-medium">Tải Excel</span> để tải file gốc.
                </p>
                <div v-if="bm02SheetPreviewHtml" class="mt-4 space-y-1.5">
                  <div class="text-xs font-medium text-slate-700">Xem trước dạng sheet (Excel)</div>
                  <div class="bm02-excel-sheet-preview overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="bm02-excel-sheet-preview__scroll dw-table-wrap max-h-[min(62vh,520px)] min-h-[240px]">
                      <div class="bm02-excel-sheet-preview__html" v-html="bm02SheetPreviewHtml" />
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500">
            Mẫu BM.02 (Excel/PDF) được tạo tự động cho loại <span class="font-medium">Điểm — Điểm</span>. Với loại dịch vụ khác, nội dung chi tiết nằm trong phần ghi chú đã gửi.
          </p>

          <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
            {{ error }}
          </div>
        </div>

        <!-- Nav buttons -->
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-6">
          <button
            type="button"
            class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-40"
            :disabled="step === 0"
            @click="step--"
          >
            ← Quay lại
          </button>
          <div class="flex gap-2">
            <button
              v-if="step < 3"
              type="button"
              class="rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:opacity-40"
              :disabled="!canGoNext"
              @click="nextStep"
            >
              Tiếp theo
            </button>
          </div>
        </div>
      </section>

      <aside v-if="created" class="mt-6">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm shadow-sm">
          <div class="font-semibold text-emerald-900">Đã tạo yêu cầu #{{ created.id }}</div>
          <div class="mt-1 text-emerald-800">Trạng thái: {{ created.status }}</div>
          <p v-if="created.trip_type === 'point_to_point'" class="mt-2 text-xs leading-relaxed text-emerald-900/90">
            File BM.02 (Excel và PDF) đã được đính kèm — mở chi tiết yêu cầu để tải.
          </p>
          <RouterLink
            class="mt-3 inline-flex rounded-lg border border-emerald-300 bg-white px-3 py-2 text-emerald-900 shadow-sm hover:bg-emerald-100/80"
            to="/requests"
          >
            Về danh sách
          </RouterLink>
        </div>
      </aside>
    </div>
  </div>

  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="clearDraftModalOpen"
        class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/45 p-4 backdrop-blur-[3px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="clear-draft-modal-title"
        @click.self="closeClearDraftModal"
      >
        <div
          class="w-full max-w-[420px] overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5"
          @click.stop
        >
          <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-amber-50/30 px-5 pb-4 pt-5">
            <div class="flex gap-4">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-100 to-amber-50 text-amber-700 shadow-inner shadow-amber-900/5"
              >
                <ExclamationTriangleIcon class="h-6 w-6" aria-hidden="true" />
              </div>
              <div class="min-w-0 pt-0.5">
                <h3 id="clear-draft-modal-title" class="text-base font-semibold leading-snug text-slate-900">
                  Xóa bản nháp đang mở?
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                  Xóa bản nháp hiện tại trên trình duyệt và làm mới form. Các bản nháp khác trong danh sách vẫn được giữ. Thao tác
                  này không thể hoàn tác.
                </p>
              </div>
            </div>
          </div>
          <div class="flex flex-col-reverse gap-2 bg-slate-50/90 px-4 py-4 sm:flex-row sm:justify-end sm:gap-3">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 sm:w-auto"
              @click="closeClearDraftModal"
            >
              Hủy
            </button>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-b from-rose-600 to-rose-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-rose-900/20 transition hover:from-rose-500 hover:to-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 sm:w-auto"
              @click="confirmClearDraft"
            >
              Xóa nháp
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="draftsModalOpen"
        class="fixed inset-0 z-[201] flex items-center justify-center bg-slate-900/45 p-4 backdrop-blur-[3px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="drafts-library-title"
        @click.self="closeDraftsModal"
      >
        <div
          class="flex max-h-[min(85vh,560px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5"
          @click.stop
        >
          <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-sky-50/30 px-5 pb-4 pt-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 id="drafts-library-title" class="text-base font-semibold leading-snug text-slate-900">
                  Bản nháp đã lưu
                </h3>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">
                  Lưu trên trình duyệt theo tài khoản hiện tại (tối đa 25 bản). Chọn một bản để tiếp tục hoặc tạo form mới.
                </p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
                @click="startNewDraftSession"
              >
                Form mới
              </button>
            </div>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:px-4">
            <p v-if="!savedDraftsList.length" class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-4 py-8 text-center text-sm text-slate-600">
              Chưa có bản nháp. Dùng <span class="font-medium">Lưu nháp</span> để lưu tại đây.
            </p>
            <ul v-else class="space-y-2">
              <li
                v-for="d in savedDraftsList"
                :key="d.id"
                class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm ring-1 ring-slate-900/[0.04]"
              >
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-xs font-semibold uppercase tracking-wide text-va-800">{{ d.tripLabel }}</span>
                      <span
                        v-if="activeDraftId === d.id"
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-emerald-800"
                      >
                        Đang mở
                      </span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-sm text-slate-800">{{ d.purposeLine }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ formatDraftTime(d.savedAt) }}</p>
                  </div>
                  <div class="flex shrink-0 flex-wrap gap-1.5">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-800 hover:bg-slate-50"
                      @click="loadDraftById(d.id)"
                    >
                      Mở
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                      @click="deleteDraftById(d.id)"
                    >
                      Xóa
                    </button>
                  </div>
                </div>
              </li>
            </ul>
          </div>
          <div class="border-t border-slate-100 bg-slate-50/90 px-4 py-3 sm:flex sm:justify-end">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 sm:w-auto"
              @click="closeDraftsModal"
            >
              Đóng
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { defineAsyncComponent, provide } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowPathIcon,
  ArrowRightIcon,
  CheckIcon,
  ClipboardDocumentListIcon,
  CloudArrowUpIcon,
  DocumentArrowDownIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  PaperClipIcon,
} from '@heroicons/vue/24/outline'
import { useDispatchRequestWizard } from '../../composables/useDispatchRequestWizard'
import { DISPATCH_WIZARD_KEY } from './dispatch-wizard/injectionKeys'

const wizard = useDispatchRequestWizard()
provide(DISPATCH_WIZARD_KEY, wizard)

const DispatchWizardStep3 = defineAsyncComponent(() =>
  import('./dispatch-wizard/DispatchWizardStep3.vue'),
)

const {
  steps,
  step,
  maxReachedStep,
  loading,
  error,
  bm02Loading,
  bm02PreviewError,
  bm02ExcelBase64,
  bm02SheetPreviewHtml,
  created,
  hasDraftSnapshot,
  clearDraftModalOpen,
  activeDraftId,
  savedDraftsList,
  draftsModalOpen,
  targetOptions,
  form,
  basisFile,
  basisFileInput,
  basisDragOver,
  basisFileError,
  requesterSearchQ,
  requesterSearchResults,
  requesterSearchLoading,
  requesterDropdownOpen,
  coordinatorSearchQ,
  coordinatorSearchResults,
  coordinatorSearchLoading,
  coordinatorDropdownOpen,
  tripTypeOptions,
  isPointToPointTrip,
  openDatePickerFromInput,
  onRequesterPhoneInput,
  onCoordinatorPhoneInput,
  scheduleRequesterSearch,
  onRequesterSearchFocus,
  onRequesterSearchBlur,
  pickRequester,
  scheduleCoordinatorSearch,
  onCoordinatorSearchFocus,
  onCoordinatorSearchBlur,
  pickCoordinator,
  onBasisFileChange,
  onBasisDrop,
  clearBasisFile,
  draftLabel,
  formatFileSize,
  canGoNext,
  goStep,
  nextStep,
  headerPrimaryLabel,
  headerPrimaryDisabled,
  primaryAction,
  loadBm02Preview,
  downloadBm02Excel,
  saveDraft,
  openClearDraftModal,
  closeClearDraftModal,
  confirmClearDraft,
  loadDraftById,
  deleteDraftById,
  startNewDraftSession,
  openDraftsModal,
  closeDraftsModal,
  onCancel,
} = wizard

function formatDraftTime(ts) {
  try {
    return new Date(ts).toLocaleString('vi-VN')
  } catch {
    return '—'
  }
}
</script>

<style src="./dispatch-wizard/dispatchWizard.styles.css"></style>
