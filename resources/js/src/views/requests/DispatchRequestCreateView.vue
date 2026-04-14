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
        <p class="mt-1 max-w-xl text-xs text-slate-500">
          Mẫu tham chiếu BM.03/MH.QT.04 — Điều chuyển hàng hóa &amp; các loại chuyến. Dữ liệu chi tiết được đính kèm trong phần ghi chú khi gửi.
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
          v-if="hasDraftSnapshot"
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-800"
          title="Xóa bản nháp lưu trên trình duyệt (theo tài khoản hiện tại)"
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

    <!-- Stepper -->
    <nav class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-2" aria-label="Các bước">
      <template v-for="(s, i) in steps" :key="s.id">
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm transition sm:text-base"
          :class="
            step === i
              ? 'bg-white text-slate-900 shadow-sm ring-1 ring-va-800/25'
              : i < step
                ? 'text-slate-700 hover:bg-slate-100'
                : 'text-slate-400 hover:text-slate-600'
          "
          @click="goStep(i)"
        >
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
            :class="
              step === i
                ? 'bg-va-800 text-white'
                : i < step
                  ? 'bg-emerald-100 text-emerald-800'
                  : 'bg-slate-200 text-slate-500'
            "
          >
            {{ i + 1 }}
          </span>
          <span class="font-medium">{{ s.title }}</span>
        </button>
        <ChevronDownIcon v-if="i < steps.length - 1" class="mx-auto h-4 w-4 text-slate-400 sm:hidden" />
        <ChevronRightIcon v-if="i < steps.length - 1" class="mx-1 hidden h-4 w-4 text-slate-400 sm:inline" />
      </template>
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
            <p class="mt-1 text-sm text-slate-600">Khớp mục A–D trên mẫu BM.03.</p>
          </div>

          <div class="grid gap-8 xl:grid-cols-12">
            <!-- A + C -->
            <div class="space-y-6 xl:col-span-5">
              <div class="dw-fieldset">
                <h3 class="dw-section-title">A. Người đề nghị</h3>
                <p class="dw-hint mb-3">Gõ họ tên để tìm trong hệ thống — hệ thống tự điền các trường bên dưới.</p>
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
                <h3 class="dw-section-title">C. Thời gian</h3>
                <label class="block">
                  <span class="dw-label-text">Ngày đề xuất <span class="dw-req" aria-hidden="true">*</span></span>
                  <span
                    class="ml-1 inline-flex cursor-help align-middle text-slate-400 hover:text-slate-600"
                    title="Ngày lập đề xuất thực tế. Click bất kỳ đâu trong ô để mở lịch."
                  >
                    <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                  </span>
                  <div class="dw-date-hit" @click="openDatePicker($event)">
                    <input v-model="form.proposed_date" type="date" class="dw-input dw-date-input" />
                  </div>
                </label>
                <p class="dw-callout mt-3">
                  Lưu ý: từ khi bộ phận Điều vận nhận đề nghị, tối thiểu
                  <strong class="text-amber-900">03 ngày làm việc</strong>
                  (trừ đề xuất xe từ 2000kg: báo trước ít nhất
                  <strong class="text-amber-900">05 ngày làm việc</strong>
                  ). Nhu cầu ngắn hơn được xem là gấp — bật mục Gấp bên dưới.
                </p>
                <label class="mt-4 block">
                  <span class="dw-label-text">Ngày cần sử dụng xe <span class="dw-req" aria-hidden="true">*</span></span>
                  <span
                    class="ml-1 inline-flex cursor-help align-middle text-slate-400 hover:text-slate-600"
                    title="Tự điền theo ngày đề xuất; có thể chỉnh lại nếu khác."
                  >
                    <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                  </span>
                  <div class="dw-date-hit" @click="openDatePicker($event)">
                    <input v-model="form.date_needed" type="date" class="dw-input dw-date-input" />
                  </div>
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

            <!-- B + D -->
            <div class="space-y-6 xl:col-span-7">
              <div class="dw-fieldset">
                <h3 class="dw-section-title">B. Mục đích sử dụng</h3>
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
                  <span class="dw-label-text">Căn cứ đề xuất — đính kèm</span>
                  <span
                    class="ml-1 inline-flex cursor-help align-middle text-slate-400 hover:text-slate-600"
                    title="Tải tờ trình, văn bản căn cứ (PDF, ảnh). Tối đa 10MB."
                  >
                    <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
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
                <h3 class="dw-section-title">D. Đối tượng được phân bổ</h3>
                <p class="dw-hint mb-2">Chọn một hoặc nhiều đơn vị / đối tượng (cuộn để xem hết).</p>
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
                <h3 class="dw-section-title">d.2 Nhân sự phụ trách điều phối</h3>
                <p class="dw-hint mb-3">Tìm theo tên để điền nhanh (không bắt buộc).</p>
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

        <!-- Step 3 -->
        <div v-show="step === 2" class="space-y-6">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">3. Chi tiết chuyến / hàng hóa</h2>
            <p class="mt-1 text-sm text-slate-600">
              {{
                isCargo
                  ? 'Bảng theo mục E — Điều chuyển hàng hóa (có thể thêm nhiều dòng).'
                  : 'Bảng chuyến: giờ đi/điểm đón — giờ về/điểm trả (có thể thêm dòng).'
              }}
            </p>
          </div>

          <!-- Passenger / business table -->
          <div v-if="!isCargo" class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-[720px] w-full border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-600">
                  <th class="px-2 py-2">STT</th>
                  <th class="px-2 py-2">Giờ đi</th>
                  <th class="px-2 py-2">Điểm đón</th>
                  <th class="px-2 py-2">Giờ về</th>
                  <th class="px-2 py-2">Điểm trả</th>
                  <th class="px-2 py-2">Số khách</th>
                  <th class="px-2 py-2">Đơn giá (VNĐ)</th>
                  <th class="px-2 py-2 w-10"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in passengerRows" :key="idx" class="border-b border-slate-100">
                  <td class="px-2 py-1.5 text-slate-500">{{ idx + 1 }}</td>
                  <td class="p-1"><input v-model="row.depart_at" type="datetime-local" class="dw-cell" /></td>
                  <td class="p-1"><input v-model="row.pickup" type="text" placeholder="Điểm đón" class="dw-cell" /></td>
                  <td class="p-1"><input v-model="row.return_at" type="datetime-local" class="dw-cell" /></td>
                  <td class="p-1"><input v-model="row.dropoff" type="text" placeholder="Điểm trả" class="dw-cell" /></td>
                  <td class="p-1"><input v-model="row.guests" type="number" min="1" class="dw-cell w-20" /></td>
                  <td class="p-1"><input v-model="row.unit_price" type="number" min="0" step="1000" placeholder="0" class="dw-cell" /></td>
                  <td class="px-1">
                    <button
                      v-if="passengerRows.length > 1"
                      type="button"
                      class="rounded p-1 text-rose-600 hover:bg-rose-50"
                      title="Xóa dòng"
                      @click="removePassengerRow(idx)"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-slate-50">
                  <td colspan="6" class="px-3 py-2 text-right font-medium text-slate-700">Tổng (ước tính)</td>
                  <td class="px-2 py-2 font-semibold text-va-800">{{ formatCurrency(passengerTotal) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
            <div class="flex flex-wrap gap-2 border-t border-slate-200 bg-white p-3">
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-va-800 shadow-sm hover:bg-slate-50"
                @click="addPassengerRow"
              >
                <PlusIcon class="h-4 w-4" />
                Thêm chuyến
              </button>
              <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                <input v-model="form.multi_day" type="checkbox" class="rounded border-slate-300 text-va-800" />
                Dùng cho 3+ ngày (ghi chú trong tóm tắt)
              </label>
            </div>
          </div>

          <!-- Cargo table -->
          <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-[1100px] w-full border-collapse text-left text-xs sm:text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase leading-tight text-slate-600 sm:text-xs">
                  <th class="px-1 py-2">STT</th>
                  <th class="px-1 py-2">Tên HH</th>
                  <th class="px-1 py-2">SL</th>
                  <th class="px-1 py-2">Kích thước (1 kiện)</th>
                  <th class="px-1 py-2">KL (1 kiện)</th>
                  <th class="px-1 py-2">Ghi chú HH</th>
                  <th class="border-l border-slate-200 px-1 py-2" colspan="3">Điểm tập kết</th>
                  <th class="border-l border-slate-200 px-1 py-2" colspan="3">Điểm giao</th>
                  <th class="px-1 py-2">VC / ghi chú NV</th>
                  <th class="px-1 py-2">Chi phí</th>
                  <th class="w-8"></th>
                </tr>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[10px] normal-case text-slate-600">
                  <th colspan="6"></th>
                  <th class="border-l border-slate-200 px-1 py-1">Thời gian</th>
                  <th class="px-1 py-1">Địa điểm</th>
                  <th class="px-1 py-1">Người giao</th>
                  <th class="border-l border-slate-200 px-1 py-1">Thời gian</th>
                  <th class="px-1 py-1">Địa điểm</th>
                  <th class="px-1 py-1">Người nhận</th>
                  <th colspan="2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in cargoRows" :key="idx" class="border-b border-slate-100 align-top">
                  <td class="px-1 py-1 text-slate-500">{{ idx + 1 }}</td>
                  <td class="p-0.5"><input v-model="row.name" type="text" placeholder="Tên" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.qty" type="text" class="dw-cell w-14" /></td>
                  <td class="p-0.5"><input v-model="row.dimensions" type="text" placeholder="cm" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.weight" type="text" placeholder="kg" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.item_notes" type="text" class="dw-cell" /></td>
                  <td class="border-l border-slate-200 p-0.5"><input v-model="row.pickup_at" type="datetime-local" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.pickup_place" type="text" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.pickup_contact" type="text" class="dw-cell" /></td>
                  <td class="border-l border-slate-200 p-0.5"><input v-model="row.delivery_at" type="datetime-local" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.delivery_place" type="text" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.delivery_contact" type="text" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.transport_note" type="text" placeholder="Xe VA / NCC…" class="dw-cell" /></td>
                  <td class="p-0.5"><input v-model="row.cost" type="number" min="0" step="1000" class="dw-cell w-24" /></td>
                  <td class="px-0.5">
                    <button
                      v-if="cargoRows.length > 1"
                      type="button"
                      class="rounded p-1 text-rose-600 hover:bg-rose-50"
                      @click="removeCargoRow(idx)"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-slate-50">
                  <td colspan="13" class="px-3 py-2 text-right font-medium text-slate-700">Tổng</td>
                  <td class="px-2 py-2 font-semibold text-va-800">{{ formatCurrency(cargoTotal) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
            <div class="border-t border-slate-200 bg-white p-3">
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-va-800 shadow-sm hover:bg-slate-50"
                @click="addCargoRow"
              >
                <PlusIcon class="h-4 w-4" />
                Thêm dòng hàng
              </button>
            </div>
          </div>

          <div v-if="isCargo" class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase text-slate-600">e.1.1 Ghi chú &amp; phát sinh</div>
            <label class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">Ghi chú khác (nếu có)</span>
              <textarea v-model="form.cargo_extra_notes" rows="2" class="dw-input min-h-[3.5rem] resize-y" />
            </label>
            <label class="flex flex-wrap items-center gap-3 text-sm text-slate-800">
              <input v-model="form.need_porters" type="checkbox" class="rounded border-slate-300 text-va-800" />
              Yêu cầu bốc xếp / nhân công hỗ trợ
              <input v-model="form.porter_qty" type="text" placeholder="SL" class="dw-cell w-20" />
              <span class="text-slate-500">Chi phí phát sinh</span>
              <input v-model="form.porter_cost" type="number" min="0" step="1000" class="dw-cell w-32" />
            </label>
            <label class="flex flex-wrap items-center gap-3 text-sm text-slate-800">
              <input v-model="form.interprovincial" type="checkbox" class="rounded border-slate-300 text-va-800" />
              Gửi chành xe đi tỉnh
              <span class="text-slate-500">Chi phí phát sinh</span>
              <input v-model="form.interprovincial_cost" type="number" min="0" step="1000" class="dw-cell w-32" />
            </label>
          </div>

          <!-- BR-001 -->
          <div class="grid gap-3 md:grid-cols-2">
            <div v-if="!isCargo">
              <label class="mb-1 block text-xs font-medium text-slate-600">Giờ xuất phát (áp dụng BR-001)</label>
              <p class="mb-2 text-[11px] text-slate-500">Lấy từ dòng đầu hoặc chỉnh tay — kiểm tra tối thiểu 2 giờ trước giờ đi (trừ gấp).</p>
              <input
                v-model="form.depart_at"
                type="datetime-local"
                :min="departMin"
                class="dw-input"
              />
            </div>
            <div v-else>
              <label class="mb-1 block text-xs font-medium text-slate-600">Giờ xuất phát hệ thống (BR-001)</label>
              <p class="mb-2 text-[11px] text-slate-500">Tự động theo thời gian lấy hàng sớm nhất; có thể chỉnh.</p>
              <input
                v-model="form.depart_at"
                type="datetime-local"
                :min="departMin"
                class="dw-input"
              />
            </div>
          </div>

          <div
            v-if="br001.kind !== 'skipped'"
            role="status"
            class="rounded-lg border px-3 py-2.5 text-sm"
            :class="br001Ui.boxClass"
          >
            <div class="font-medium" :class="br001Ui.titleClass">{{ br001Ui.title }}</div>
            <p class="mt-0.5 opacity-90">{{ br001.message }}</p>
          </div>
        </div>

        <!-- Step 4 -->
        <div v-show="step === 3" class="space-y-6">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">4. Xác nhận &amp; nộp</h2>
            <p class="mt-1 text-sm text-slate-600">Kiểm tra tóm tắt. Phần ký nhận thực hiện sau khi phê duyệt (mẫu giấy / quy trình nội bộ).</p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-700">
            <div class="font-semibold text-slate-900">Tóm tắt yêu cầu</div>
            <ul class="mt-3 list-inside list-disc space-y-1 text-slate-600">
              <li>Loại: <span class="font-medium text-slate-900">{{ tripTypeLabel }}</span></li>
              <li>Người đề nghị: {{ form.requester_name || '—' }} — {{ form.requester_email || '—' }}</li>
              <li>Ngày đề xuất / cần dùng: {{ form.proposed_date || '—' }} → {{ form.date_needed || '—' }}</li>
              <li v-if="form.is_urgent">Gấp: {{ form.urgent_reason || '(chưa ghi lý do)' }}</li>
              <li v-if="!isCargo">Tổng khách (ước tính): {{ passengerGuestTotal || '—' }}</li>
              <li v-else>Tổng chi phí hàng (ước tính): {{ formatCurrency(cargoTotal + extraCosts) }}</li>
              <li>Kênh: {{ form.source_channel }}</li>
            </ul>
            <pre class="mt-4 max-h-48 overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600">{{ summaryPreview }}</pre>
          </div>

          <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">F &amp; G — Phần xác nhận (minh họa)</div>
            <div class="grid gap-3 sm:grid-cols-3">
              <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-600">
                <div class="font-medium text-slate-800">Người đề xuất</div>
                <div class="mt-8 min-h-[3rem] text-xs text-slate-500">Chữ ký điện tử / xác nhận sau</div>
              </div>
              <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-600">
                <div class="font-medium text-slate-800">Trưởng đơn vị</div>
                <div class="mt-8 min-h-[3rem] text-xs text-slate-500">Theo thẩm quyền</div>
              </div>
              <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-600">
                <div class="font-medium text-slate-800">Trưởng phòng Mua hàng</div>
                <div class="mt-8 min-h-[3rem] text-xs text-slate-500">PO / xác nhận vận đơn</div>
              </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">G.1 Mã vận đơn (PO), G.2 Ngày nhận đề nghị đã phê duyệt — cập nhật tại bước xử lý sau.</p>
          </div>

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
                  Xóa bản nháp?
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                  Xóa bản nháp đã lưu trên trình duyệt và làm mới form. Thao tác này không thể hoàn tác.
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
</template>

<script setup>
import { computed, onMounted, ref, watch, watchEffect } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  AcademicCapIcon,
  ArrowRightIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  ChevronDownIcon,
  ChevronRightIcon,
  CloudArrowUpIcon,
  CubeIcon,
  DocumentArrowDownIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  PaperClipIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { uploadAttachment } from '../../api/attachments'
import { createDispatchRequest } from '../../api/requests'
import { searchUsersForDispatchForm } from '../../api/operational'
import { formatApiError } from '../../api/http'
import { newIdempotencyKey } from '../../util/idempotency'
import {
  br001Status,
  minDepartDatetimeLocalValue,
  suggestBr001CompliantLocal,
  toDatetimeLocalValue,
} from '../../util/datetime'

/** Một nháp / user để tránh chồng nhiều bản khi đổi tài khoản hoặc lặp khóa cũ. */
const LEGACY_DRAFT_KEY = 'dispatch-request-wizard-draft-v1'

const router = useRouter()
const auth = useAuthStore()

function draftKeyForUser(userId) {
  return userId != null ? `${LEGACY_DRAFT_KEY}-u${userId}` : LEGACY_DRAFT_KEY
}

function currentDraftStorageKey() {
  return draftKeyForUser(auth.user?.id)
}

const steps = [
  { id: 'type', title: 'Loại dịch vụ' },
  { id: 'info', title: 'Người đề nghị & thời gian' },
  { id: 'detail', title: 'Chi tiết' },
  { id: 'confirm', title: 'Xác nhận' },
]

const step = ref(0)
const loading = ref(false)
const error = ref('')
const created = ref(null)
const draftSavedAt = ref(null)
/** Đã từng có nháp trong phiên (để hiện nút Xóa nháp). */
const hasDraftSnapshot = ref(false)
const clearDraftModalOpen = ref(false)

const targetOptions = [
  'TiH Tân Bình',
  'MN Phú Định',
  'P. Kinh Doanh',
  'Vườn Trường',
  'THCS Tân Bình',
  'TiH-THCS Phú Định',
  'P. Công Nghệ',
  'Ban TA TiHo',
  'MN Bình Thới',
  'MN Vĩnh Hội',
  'BP.CUHC',
  'Ban TA THCS',
  'TiH Bình Thới',
  'MN Thông Tây Hội',
  'P. Kế Toán',
  'Ban TA THPT',
  'THCS Bình Thới',
  'TiH-THCS Thông Tây Hội',
  'P. Mua Hàng',
  'VA - Cần Thơ',
  'THPT VMA',
  'MN Hạnh Thông',
  'P. Đầu Tư',
  'VA - Vũng Tàu',
  'MN Hòa Bình',
  'P.CSVC',
  'Khóa Hè',
  'Viễn Đông',
  'P.HCNS',
  'Tham vấn học đường',
  'Ban Pháp chế (P.CSVC)',
]

function emptyPassengerRow() {
  return {
    depart_at: '',
    pickup: '',
    return_at: '',
    dropoff: '',
    guests: '1',
    unit_price: '',
  }
}

function emptyCargoRow() {
  return {
    name: '',
    qty: '1',
    dimensions: '',
    weight: '',
    item_notes: '',
    pickup_at: '',
    pickup_place: '',
    pickup_contact: '',
    delivery_at: '',
    delivery_place: '',
    delivery_contact: '',
    transport_note: '',
    cost: '',
  }
}

function todayISODate() {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** Chỉ giữ chữ số, tối đa 11 ký tự; hỗ trợ +84 → 0… */
function sanitizeVnPhoneDigits(raw) {
  if (raw == null) return ''
  let d = String(raw).replace(/\D/g, '')
  if (d.startsWith('84') && d.length >= 10) d = `0${d.slice(2)}`
  if (d.length > 11) d = d.slice(0, 11)
  return d
}

function formatOrgUnitFromUser(u) {
  const parts = [u.unit_name, u.department_name].filter(Boolean)
  if (parts.length) return parts.join(' — ')
  if (u.employee_code) return `Mã NV: ${u.employee_code}`
  return ''
}

function formatFileSize(n) {
  if (n == null || Number.isNaN(n)) return ''
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function createInitialForm() {
  const today = todayISODate()
  return {
    trip_type: 'point_to_point',
    source_channel: 'portal',
    requester_name: '',
    requester_email: '',
    requester_phone: '',
    requester_unit: '',
    purpose: '',
    proposed_date: today,
    date_needed: today,
    is_urgent: false,
    urgent_reason: '',
    targets: [],
    coordinator_name: '',
    coordinator_email: '',
    coordinator_phone: '',
    depart_at: suggestBr001CompliantLocal(15),
    multi_day: false,
    cargo_extra_notes: '',
    need_porters: false,
    porter_qty: '',
    porter_cost: '',
    interprovincial: false,
    interprovincial_cost: '',
  }
}

function isPassengerRowFilled(r) {
  if (r.pickup?.trim() || r.dropoff?.trim() || r.depart_at || r.return_at) return true
  if (r.unit_price && String(r.unit_price).trim() !== '') return true
  const g = String(r.guests ?? '').trim()
  return !!(g && g !== '1')
}

function isCargoRowFilled(r) {
  if (r.name?.trim()) return true
  return !!(
    r.pickup_place?.trim() ||
    r.delivery_place?.trim() ||
    r.pickup_at ||
    r.delivery_at ||
    r.pickup_contact?.trim() ||
    r.delivery_contact?.trim() ||
    (r.cost && String(r.cost).trim() !== '') ||
    (r.qty && String(r.qty).trim() !== '' && String(r.qty).trim() !== '1') ||
    r.dimensions?.trim() ||
    r.weight?.trim() ||
    r.item_notes?.trim() ||
    r.transport_note?.trim()
  )
}

function trimPassengerRowsInPlace() {
  const kept = passengerRows.value.filter(isPassengerRowFilled)
  passengerRows.value = kept.length ? kept : [emptyPassengerRow()]
}

function trimCargoRowsInPlace() {
  const kept = cargoRows.value.filter(isCargoRowFilled)
  cargoRows.value = kept.length ? kept : [emptyCargoRow()]
}

const form = ref(createInitialForm())

const basisFile = ref(null)
const basisFileInput = ref(null)
const basisDragOver = ref(false)
const basisFileError = ref('')

let requesterSearchTimer = null
const requesterSearchQ = ref('')
const requesterSearchResults = ref([])
const requesterSearchLoading = ref(false)
const requesterDropdownOpen = ref(false)
let requesterBlurTimer = null

let coordinatorSearchTimer = null
const coordinatorSearchQ = ref('')
const coordinatorSearchResults = ref([])
const coordinatorSearchLoading = ref(false)
const coordinatorDropdownOpen = ref(false)
let coordinatorBlurTimer = null

const passengerRows = ref([emptyPassengerRow()])
const cargoRows = ref([emptyCargoRow()])

const tripTypeOptions = [
  {
    value: 'door_to_door',
    label: 'Đưa đón (Door-to-door)',
    hint: 'Học sinh — tuyến cố định.',
    icon: AcademicCapIcon,
    iconClass: 'text-violet-600',
    selectedClass: 'border-violet-500 bg-violet-50 shadow-sm ring-1 ring-violet-200',
  },
  {
    value: 'point_to_point',
    label: 'Điểm — Điểm',
    hint: 'Nội bộ, hoạt ngoại khóa.',
    icon: BuildingOffice2Icon,
    iconClass: 'text-sky-600',
    selectedClass: 'border-sky-500 bg-sky-50 shadow-sm ring-1 ring-sky-200',
  },
  {
    value: 'business',
    label: 'Công tác',
    hint: 'Họp, sân bay, công tác ngoài.',
    icon: BriefcaseIcon,
    iconClass: 'text-emerald-600',
    selectedClass: 'border-emerald-500 bg-emerald-50 shadow-sm ring-1 ring-emerald-200',
  },
  {
    value: 'cargo',
    label: 'Hàng hóa',
    hint: 'Chuyển hàng giữa cơ sở.',
    icon: CubeIcon,
    iconClass: 'text-orange-600',
    selectedClass: 'border-orange-500 bg-orange-50 shadow-sm ring-1 ring-orange-200',
    badge: 'SLA 3h',
  },
]

const isCargo = computed(() => form.value.trip_type === 'cargo')

const tripTypeLabel = computed(() => {
  const o = tripTypeOptions.find((x) => x.value === form.value.trip_type)
  return o?.label ?? form.value.trip_type
})

function openDatePicker(evt) {
  const wrap = evt?.currentTarget
  const inp = wrap?.querySelector?.('input[type="date"]')
  if (!inp) return
  if (typeof inp.showPicker === 'function') {
    try {
      inp.showPicker()
      return
    } catch {
      /* fallback */
    }
  }
  inp.focus()
}

function onRequesterPhoneInput(e) {
  form.value.requester_phone = sanitizeVnPhoneDigits(e?.target?.value)
}

function onCoordinatorPhoneInput(e) {
  form.value.coordinator_phone = sanitizeVnPhoneDigits(e?.target?.value)
}

function scheduleRequesterSearch() {
  clearTimeout(requesterSearchTimer)
  requesterSearchTimer = setTimeout(runRequesterSearch, 350)
}

async function runRequesterSearch() {
  const q = requesterSearchQ.value.trim()
  if (q.length < 2) {
    requesterSearchResults.value = []
    requesterDropdownOpen.value = false
    return
  }
  requesterSearchLoading.value = true
  try {
    requesterSearchResults.value = await searchUsersForDispatchForm(q)
    requesterDropdownOpen.value = requesterSearchResults.value.length > 0
  } catch {
    requesterSearchResults.value = []
    requesterDropdownOpen.value = false
  } finally {
    requesterSearchLoading.value = false
  }
}

function onRequesterSearchFocus() {
  clearTimeout(requesterBlurTimer)
  if (requesterSearchResults.value.length) requesterDropdownOpen.value = true
}

function onRequesterSearchBlur() {
  requesterBlurTimer = setTimeout(() => {
    requesterDropdownOpen.value = false
  }, 200)
}

function pickRequester(u) {
  form.value.requester_name = u.name || ''
  form.value.requester_email = u.email || ''
  form.value.requester_phone = sanitizeVnPhoneDigits(u.phone || '')
  form.value.requester_unit = formatOrgUnitFromUser(u)
  requesterSearchQ.value = u.name || ''
  requesterSearchResults.value = []
  requesterDropdownOpen.value = false
}

function scheduleCoordinatorSearch() {
  clearTimeout(coordinatorSearchTimer)
  coordinatorSearchTimer = setTimeout(runCoordinatorSearch, 350)
}

async function runCoordinatorSearch() {
  const q = coordinatorSearchQ.value.trim()
  if (q.length < 2) {
    coordinatorSearchResults.value = []
    coordinatorDropdownOpen.value = false
    return
  }
  coordinatorSearchLoading.value = true
  try {
    coordinatorSearchResults.value = await searchUsersForDispatchForm(q)
    coordinatorDropdownOpen.value = coordinatorSearchResults.value.length > 0
  } catch {
    coordinatorSearchResults.value = []
    coordinatorDropdownOpen.value = false
  } finally {
    coordinatorSearchLoading.value = false
  }
}

function onCoordinatorSearchFocus() {
  clearTimeout(coordinatorBlurTimer)
  if (coordinatorSearchResults.value.length) coordinatorDropdownOpen.value = true
}

function onCoordinatorSearchBlur() {
  coordinatorBlurTimer = setTimeout(() => {
    coordinatorDropdownOpen.value = false
  }, 200)
}

function pickCoordinator(u) {
  form.value.coordinator_name = u.name || ''
  form.value.coordinator_email = u.email || ''
  form.value.coordinator_phone = sanitizeVnPhoneDigits(u.phone || '')
  coordinatorSearchQ.value = u.name || ''
  coordinatorSearchResults.value = []
  coordinatorDropdownOpen.value = false
}

function onBasisFileChange(e) {
  basisFileError.value = ''
  const f = e?.target?.files?.[0]
  if (!f) return
  if (f.size > 10 * 1024 * 1024) {
    basisFile.value = null
    basisFileError.value = 'Tệp vượt quá 10MB.'
    if (basisFileInput.value) basisFileInput.value.value = ''
    return
  }
  basisFile.value = f
}

function onBasisDrop(e) {
  basisDragOver.value = false
  basisFileError.value = ''
  const f = e?.dataTransfer?.files?.[0]
  if (!f) return
  if (f.size > 10 * 1024 * 1024) {
    basisFileError.value = 'Tệp vượt quá 10MB.'
    return
  }
  basisFile.value = f
}

function clearBasisFile() {
  basisFile.value = null
  basisFileError.value = ''
  if (basisFileInput.value) basisFileInput.value.value = ''
}

const draftLabel = computed(() => {
  if (created.value) return 'Đã gửi'
  if (draftSavedAt.value) {
    try {
      return `Bản nháp • Lưu ${new Date(draftSavedAt.value).toLocaleString('vi-VN')}`
    } catch {
      return 'Bản nháp'
    }
  }
  return 'Bản nháp'
})

const departMin = computed(() => (form.value.is_urgent ? '' : minDepartDatetimeLocalValue(15)))

const br001 = computed(() => br001Status(form.value.depart_at, form.value.is_urgent))

const br001Ui = computed(() => {
  const k = br001.value.kind
  if (k === 'ok' || k === 'skipped') {
    return {
      title: k === 'skipped' ? 'Lệnh gấp' : 'BR-001: đạt',
      boxClass: k === 'skipped' ? 'border-slate-200 bg-slate-50 text-slate-700' : 'border-emerald-200 bg-emerald-50 text-emerald-900',
      titleClass: k === 'skipped' ? 'text-slate-800' : 'text-emerald-900',
    }
  }
  if (k === 'viol') {
    return {
      title: 'BR-001: chưa đạt',
      boxClass: 'border-rose-200 bg-rose-50 text-rose-900',
      titleClass: 'text-rose-900',
    }
  }
  return {
    title: 'BR-001',
    boxClass: 'border-slate-200 bg-slate-50 text-slate-700',
    titleClass: 'text-slate-800',
  }
})

function parseMoney(v) {
  const n = Number(String(v).replace(/\s/g, ''))
  return Number.isFinite(n) ? n : 0
}

const passengerTotal = computed(() =>
  passengerRows.value.reduce((s, r) => s + parseMoney(r.unit_price), 0),
)

const passengerGuestTotal = computed(() =>
  passengerRows.value.reduce((s, r) => s + parseMoney(r.guests), 0),
)

const cargoTotal = computed(() => cargoRows.value.reduce((s, r) => s + parseMoney(r.cost), 0))

const extraCosts = computed(() => {
  let x = 0
  if (form.value.need_porters) x += parseMoney(form.value.porter_cost)
  if (form.value.interprovincial) x += parseMoney(form.value.interprovincial_cost)
  return x
})

function formatCurrency(n) {
  if (n == null || Number.isNaN(Number(n))) return '—'
  try {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(n))
  } catch {
    return `${n} ₫`
  }
}

function earliestDatetimeLocal(rows, key) {
  const vals = rows.map((r) => r[key]).filter(Boolean)
  if (!vals.length) return ''
  let min = null
  for (const v of vals) {
    const t = new Date(v).getTime()
    if (!Number.isNaN(t) && (min === null || t < min)) min = t
  }
  if (min === null) return ''
  return toDatetimeLocalValue(new Date(min))
}

const canGoNext = computed(() => {
  if (step.value === 0) return !!form.value.trip_type
  if (step.value === 1) {
    return (
      !!form.value.requester_name?.trim() &&
      !!form.value.requester_email?.trim() &&
      !!form.value.purpose?.trim() &&
      !!form.value.proposed_date &&
      !!form.value.date_needed &&
      (!form.value.is_urgent || !!form.value.urgent_reason?.trim())
    )
  }
  if (step.value === 2) {
    if (isCargo.value) {
      const ok = cargoRows.value.some((r) => r.name?.trim())
      return ok && br001.value.kind !== 'viol' && br001.value.kind !== 'empty' && br001.value.kind !== 'invalid'
    }
    const ok = passengerRows.value.some((r) => r.pickup?.trim() || r.dropoff?.trim())
    return ok && br001.value.kind !== 'viol' && br001.value.kind !== 'empty' && br001.value.kind !== 'invalid'
  }
  return true
})

function goStep(i) {
  if (i <= step.value) step.value = i
}

function syncDepartFromTable() {
  const e = isCargo.value
    ? earliestDatetimeLocal(cargoRows.value, 'pickup_at')
    : earliestDatetimeLocal(passengerRows.value, 'depart_at')
  if (e) form.value.depart_at = e
}

function nextStep() {
  if (!canGoNext.value) return
  if (step.value === 2) syncDepartFromTable()
  if (step.value < 3) step.value++
}

function addPassengerRow() {
  passengerRows.value.push(emptyPassengerRow())
}

function removePassengerRow(i) {
  passengerRows.value.splice(i, 1)
  if (!passengerRows.value.length) passengerRows.value.push(emptyPassengerRow())
}

function addCargoRow() {
  cargoRows.value.push(emptyCargoRow())
}

function removeCargoRow(i) {
  cargoRows.value.splice(i, 1)
  if (!cargoRows.value.length) cargoRows.value.push(emptyCargoRow())
}

const summaryPreview = computed(() => buildNotesBody())

const canSubmitApi = computed(() => {
  if (form.value.is_urgent) return true
  const k = br001.value.kind
  return k !== 'viol' && k !== 'empty' && k !== 'invalid'
})

const headerPrimaryLabel = computed(() => {
  if (loading.value) return 'Đang gửi…'
  if (step.value < 3) return 'Tiếp tới xác nhận'
  return 'Gửi yêu cầu'
})

const headerPrimaryDisabled = computed(() => {
  if (loading.value) return true
  if (step.value < 3) return !canGoNext.value
  return !canSubmitApi.value
})

function buildNotesBody() {
  const f = form.value
  const lines = []
  lines.push('=== ĐỀ NGHỊ ĐIỀU VẬN (BM.03/MH.QT.04 — bản điện tử) ===')
  lines.push('')
  lines.push('A. Người đề nghị')
  lines.push(`- Họ tên: ${f.requester_name || '—'}`)
  lines.push(`- Email: ${f.requester_email || '—'}`)
  lines.push(`- Điện thoại: ${f.requester_phone || '—'}`)
  lines.push(`- Đơn vị: ${f.requester_unit || '—'}`)
  lines.push('')
  lines.push('B. Mục đích sử dụng')
  lines.push(`- Mục đích: ${f.purpose || '—'}`)
  if (basisFile.value) {
    lines.push(`- Căn cứ đề xuất: đính kèm tệp «${basisFile.value.name}»`)
  } else {
    lines.push('- Căn cứ đề xuất: (chưa đính kèm tệp)')
  }
  lines.push('')
  lines.push('C. Thời gian')
  lines.push(`- Ngày đề xuất: ${f.proposed_date || '—'}`)
  lines.push(`- Ngày cần sử dụng xe: ${f.date_needed || '—'}`)
  if (f.is_urgent) lines.push(`- GẤP — Lý do: ${f.urgent_reason || '—'}`)
  lines.push('')
  lines.push('D. Đối tượng / điều phối')
  lines.push(`- Đối tượng: ${f.targets?.length ? f.targets.join(', ') : '—'}`)
  lines.push(
    `- Điều phối: ${f.coordinator_name || '—'} | ${f.coordinator_email || '—'} | ${f.coordinator_phone || '—'}`,
  )
  lines.push('')

  if (isCargo.value) {
    lines.push('E. Nội dung điều chuyển hàng hóa')
    cargoRows.value.forEach((r, i) => {
      if (!r.name?.trim()) return
      lines.push(
        `${i + 1}. ${r.name} | SL ${r.qty || '—'} | ${r.dimensions || '—'} | ${r.weight || '—'} | ${r.item_notes || ''}`,
      )
      lines.push(
        `   Lấy: ${r.pickup_at || '—'} @ ${r.pickup_place || '—'} — ${r.pickup_contact || '—'}`,
      )
      lines.push(
        `   Giao: ${r.delivery_at || '—'} @ ${r.delivery_place || '—'} — ${r.delivery_contact || '—'}`,
      )
      lines.push(`   Vận chuyển: ${r.transport_note || '—'} | Chi phí: ${r.cost || '0'}`)
    })
    lines.push(`Tổng hàng: ${formatCurrency(cargoTotal.value)}`)
    if (f.cargo_extra_notes?.trim()) lines.push(`Ghi chú khác: ${f.cargo_extra_notes}`)
    if (f.need_porters) {
      lines.push(
        `- Bốc xếp: SL ${f.porter_qty || '—'} — phát sinh ${f.porter_cost || '0'} VNĐ`,
      )
    }
    if (f.interprovincial) {
      lines.push(`- Chành xe tỉnh — phát sinh ${f.interprovincial_cost || '0'} VNĐ`)
    }
    lines.push(`Tổng cộng (ước tính): ${formatCurrency(cargoTotal.value + extraCosts.value)}`)
  } else {
    lines.push('E. Chi tiết chuyến')
    if (f.multi_day) lines.push('(Dùng nhiều ngày — chi tiết bổ sung khi điều phối.)')
    passengerRows.value.forEach((r, i) => {
      if (!r.pickup?.trim() && !r.dropoff?.trim()) return
      lines.push(
        `${i + 1}. ${r.depart_at || '—'} ${r.pickup || '—'} → ${r.return_at || '—'} ${r.dropoff || '—'} | ${r.guests || '0'} khách | ${r.unit_price || '0'} VNĐ`,
      )
    })
    lines.push(`Tổng (ước tính): ${formatCurrency(passengerTotal.value)}`)
  }

  lines.push('')
  lines.push('--- Hệ thống: các trường trên được gửi kèm để bộ phận Điều vận xử lý.')
  return lines.join('\n')
}

function computeApiOriginDestination() {
  if (isCargo.value) {
    const r = cargoRows.value.find((x) => x.name?.trim())
    return {
      origin: r?.pickup_place?.trim() || '',
      destination: r?.delivery_place?.trim() || '',
    }
  }
  const r = passengerRows.value.find((x) => x.pickup?.trim() || x.dropoff?.trim())
  return {
    origin: r?.pickup?.trim() || '',
    destination: r?.dropoff?.trim() || '',
  }
}

function toIsoMaybe(v) {
  if (!v) return null
  try {
    return new Date(v).toISOString()
  } catch {
    return v
  }
}

let submitInFlight = false

function primaryAction() {
  if (step.value < 3) nextStep()
  else doSubmit()
}

function validateBeforeApi() {
  if (!form.value.trip_type) {
    step.value = 0
    return 'Chọn loại dịch vụ.'
  }
  if (
    !form.value.requester_name?.trim() ||
    !form.value.requester_email?.trim() ||
    !form.value.purpose?.trim() ||
    !form.value.proposed_date ||
    !form.value.date_needed
  ) {
    step.value = 1
    return 'Điền đủ thông tin bước 2 (A–C, mục đích).'
  }
  if (form.value.is_urgent && !form.value.urgent_reason?.trim()) {
    step.value = 1
    return 'Ghi lý do khi chọn Gấp.'
  }
  if (isCargo.value) {
    if (!cargoRows.value.some((r) => r.name?.trim())) {
      step.value = 2
      return 'Thêm ít nhất một dòng hàng hóa (tên hàng).'
    }
  } else if (!passengerRows.value.some((r) => r.pickup?.trim() || r.dropoff?.trim())) {
    step.value = 2
    return 'Thêm ít nhất một chuyến (điểm đón hoặc điểm trả).'
  }
  if (!form.value.depart_at?.trim()) {
    step.value = 2
    return 'Chọn giờ xuất phát (mục BR-001 ở bước 3).'
  }
  if (!canSubmitApi.value) {
    step.value = 2
    return 'BR-001: điều chỉnh giờ xuất phát hoặc chọn Gấp có lý do.'
  }
  return ''
}

async function doSubmit() {
  if (submitInFlight || loading.value) return
  const v = validateBeforeApi()
  if (v) {
    error.value = v
    return
  }
  error.value = ''
  created.value = null
  submitInFlight = true
  loading.value = true
  const idempotencyKey = newIdempotencyKey()
  try {
    const { origin, destination } = computeApiOriginDestination()
    const notes = buildNotesBody()
    const payload = {
      trip_type: form.value.trip_type,
      source_channel: form.value.source_channel,
      origin: origin || undefined,
      destination: destination || undefined,
      depart_at: toIsoMaybe(form.value.depart_at),
      arrive_by: null,
      passenger_count: isCargo.value
        ? null
        : passengerGuestTotal.value > 0
          ? Math.round(passengerGuestTotal.value)
          : null,
      notes,
      is_urgent: !!form.value.is_urgent,
    }
    Object.keys(payload).forEach((k) => (payload[k] === '' ? delete payload[k] : null))
    created.value = await createDispatchRequest(payload, { idempotencyKey })
    if (basisFile.value && created.value?.id) {
      try {
        await uploadAttachment({
          attachable_type: 'dispatch_request',
          attachable_id: created.value.id,
          kind: 'proposal_basis',
          file: basisFile.value,
        })
      } catch (attachErr) {
        error.value = formatApiError(
          attachErr,
          'Đã tạo yêu cầu nhưng không tải được file căn cứ. Bạn có thể thử lại từ chi tiết yêu cầu (nếu được phép).',
        )
      }
    }
    try {
      localStorage.removeItem(currentDraftStorageKey())
      localStorage.removeItem(LEGACY_DRAFT_KEY)
    } catch {
      /* ignore */
    }
    draftSavedAt.value = null
    hasDraftSnapshot.value = false
  } catch (e) {
    error.value = formatApiError(e, 'Tạo yêu cầu thất bại.')
  } finally {
    loading.value = false
    submitInFlight = false
  }
}

function migrateLegacyDraft() {
  if (typeof localStorage === 'undefined') return
  const uid = auth.user?.id
  if (uid == null) return
  const userKey = draftKeyForUser(uid)
  if (localStorage.getItem(userKey)) return
  const legacy = localStorage.getItem(LEGACY_DRAFT_KEY)
  if (!legacy) return
  localStorage.setItem(userKey, legacy)
  localStorage.removeItem(LEGACY_DRAFT_KEY)
}

function saveDraft() {
  try {
    trimPassengerRowsInPlace()
    trimCargoRowsInPlace()
    const savedAt = Date.now()
    const data = {
      form: form.value,
      passengerRows: passengerRows.value,
      cargoRows: cargoRows.value,
      step: step.value,
      savedAt,
    }
    localStorage.setItem(currentDraftStorageKey(), JSON.stringify(data))
    draftSavedAt.value = savedAt
    hasDraftSnapshot.value = true
  } catch {
    /* ignore */
  }
}

function loadDraft() {
  try {
    const raw = localStorage.getItem(currentDraftStorageKey())
    if (!raw) {
      hasDraftSnapshot.value = false
      return
    }
    const data = JSON.parse(raw)
    if (data.form) form.value = { ...form.value, ...data.form }
    if (Array.isArray(data.passengerRows) && data.passengerRows.length) passengerRows.value = data.passengerRows
    if (Array.isArray(data.cargoRows) && data.cargoRows.length) cargoRows.value = data.cargoRows
    if (typeof data.step === 'number') step.value = data.step
    draftSavedAt.value = data.savedAt ?? Date.now()
    trimPassengerRowsInPlace()
    trimCargoRowsInPlace()
    hasDraftSnapshot.value = true
  } catch {
    hasDraftSnapshot.value = false
  }
}

function resetWizardForm() {
  form.value = createInitialForm()
  passengerRows.value = [emptyPassengerRow()]
  cargoRows.value = [emptyCargoRow()]
  step.value = 0
  error.value = ''
  created.value = null
  draftSavedAt.value = null
  hasDraftSnapshot.value = false
  basisFile.value = null
  basisFileError.value = ''
  requesterSearchQ.value = ''
  coordinatorSearchQ.value = ''
}

function openClearDraftModal() {
  clearDraftModalOpen.value = true
}

function closeClearDraftModal() {
  clearDraftModalOpen.value = false
}

function confirmClearDraft() {
  closeClearDraftModal()
  try {
    localStorage.removeItem(currentDraftStorageKey())
    localStorage.removeItem(LEGACY_DRAFT_KEY)
  } catch {
    /* ignore */
  }
  resetWizardForm()
}

watchEffect((onCleanup) => {
  if (typeof document === 'undefined') return
  if (!clearDraftModalOpen.value) {
    document.body.style.overflow = ''
    return
  }
  document.body.style.overflow = 'hidden'
  if (typeof window === 'undefined') return
  const onKey = (e) => {
    if (e.key === 'Escape') closeClearDraftModal()
  }
  window.addEventListener('keydown', onKey)
  onCleanup(() => {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', onKey)
  })
})

function onCancel() {
  if (created.value) {
    router.push('/requests')
    return
  }
  router.back()
}

onMounted(async () => {
  try {
    if (!auth.user) await auth.fetchMe()
  } catch {
    /* router guard / 401 */
  }
  migrateLegacyDraft()
  loadDraft()
  if (auth.user) {
    if (!form.value.requester_name?.trim() && auth.user.name) form.value.requester_name = auth.user.name
    if (!form.value.requester_email?.trim() && auth.user.email) form.value.requester_email = auth.user.email
  }
  if (form.value.requester_name?.trim()) requesterSearchQ.value = form.value.requester_name
  if (form.value.coordinator_name?.trim()) coordinatorSearchQ.value = form.value.coordinator_name
  form.value.requester_phone = sanitizeVnPhoneDigits(form.value.requester_phone)
  form.value.coordinator_phone = sanitizeVnPhoneDigits(form.value.coordinator_phone)
  if (!hasDraftSnapshot.value && typeof localStorage !== 'undefined') {
    hasDraftSnapshot.value = !!localStorage.getItem(currentDraftStorageKey())
  }
})

watch(
  () => form.value.is_urgent,
  (urgent) => {
    if (!urgent && form.value.depart_at) {
      const minStr = minDepartDatetimeLocalValue(15)
      const cur = new Date(form.value.depart_at).getTime()
      const minT = new Date(minStr).getTime()
      if (!Number.isNaN(cur) && !Number.isNaN(minT) && cur < minT) {
        form.value.depart_at = minStr
      }
    }
  },
)

watch(
  () => form.value.proposed_date,
  (v) => {
    if (v) form.value.date_needed = v
  },
)
</script>

<style scoped>
.dw-input {
  @apply w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800/25;
}
.dw-cell {
  @apply w-full min-w-0 rounded border border-slate-200 bg-white px-1.5 py-1 text-xs text-slate-900 placeholder:text-slate-400 focus:border-va-800 focus:outline-none focus:ring-1 focus:ring-va-800/20 sm:text-sm;
}
.dw-fieldset {
  @apply rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm sm:p-5;
}
.dw-section-title {
  @apply mb-3 border-b border-slate-100 pb-2 text-xs font-semibold uppercase tracking-wider text-va-800;
}
.dw-label {
  @apply mb-1 flex items-center gap-1.5 text-xs font-medium text-slate-700;
}
.dw-label-text {
  @apply mb-1 block text-xs font-medium text-slate-700;
}
.dw-req {
  @apply font-semibold text-rose-600;
}
.dw-hint {
  @apply text-xs leading-relaxed text-slate-500;
}
.dw-callout {
  @apply rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-950;
}
.dw-date-hit {
  @apply mt-1 cursor-pointer rounded-lg ring-offset-2 transition hover:ring-2 hover:ring-va-800/15;
}
.dw-date-input {
  @apply cursor-pointer;
}
</style>
