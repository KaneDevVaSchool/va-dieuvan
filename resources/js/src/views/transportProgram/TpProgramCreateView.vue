<template>
  <div class="w-full">
    <!-- Sticky top bar -->
    <div class="sticky top-0 z-30 -mx-3 mb-6 border-b border-slate-200 bg-white/90 px-3 py-3 backdrop-blur sm:-mx-4 sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
      <div class="flex items-center justify-between gap-3">
        <nav class="flex min-w-0 items-center gap-1.5 text-sm">
          <button
            type="button"
            class="shrink-0 font-medium text-slate-500 transition hover:text-va-800"
            @click="goBack"
          >
            Chương trình Đưa đón
          </button>
          <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-300" />
          <span class="truncate font-semibold text-slate-900">Tạo Chương trình mới</span>
        </nav>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="goBack"
        >
          <XMarkIcon class="h-4 w-4" /> Hủy
        </button>
      </div>
    </div>

    <!-- Step progress indicator -->
    <div class="mx-auto mb-8 max-w-6xl px-2 sm:px-4 lg:max-w-[calc(42rem+2rem+20rem)]">
      <ol class="flex items-start">
        <li
          v-for="(step, idx) in STEPS"
          :key="step.key"
          class="flex flex-1 flex-col items-center gap-1.5"
        >
          <div class="flex w-full items-center">
            <div
              :class="[
                'h-0.5 flex-1 transition-colors duration-300',
                idx === 0 ? 'invisible' : currentStep >= idx ? 'bg-va-800' : 'bg-slate-200',
              ]"
            />
            <button
              type="button"
              :title="step.label"
              :class="[
                'mx-2 grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-bold transition-all duration-200',
                currentStep === idx
                  ? 'bg-va-800 text-white shadow-md ring-4 ring-va-800/20'
                  : currentStep > idx
                  ? 'cursor-pointer bg-emerald-500 text-white hover:ring-2 hover:ring-emerald-400/40'
                  : 'cursor-default bg-slate-100 text-slate-400',
              ]"
              @click="currentStep > idx ? jumpTo(idx) : undefined"
            >
              <CheckIcon v-if="currentStep > idx" class="h-4 w-4" />
              <span v-else>{{ idx + 1 }}</span>
            </button>
            <div
              :class="[
                'h-0.5 flex-1 transition-colors duration-300',
                idx === STEPS.length - 1 ? 'invisible' : currentStep > idx ? 'bg-va-800' : 'bg-slate-200',
              ]"
            />
          </div>
          <span
            :class="[
              'text-center text-[11px] font-medium leading-tight',
              currentStep === idx ? 'text-va-800' : currentStep > idx ? 'text-emerald-600' : 'text-slate-400',
            ]"
          >
            {{ step.label }}
          </span>
        </li>
      </ol>
    </div>

    <div class="mx-auto grid max-w-6xl gap-6 px-2 pb-28 sm:px-4 lg:grid-cols-[minmax(0,42rem)_minmax(260px,1fr)] lg:items-start lg:gap-8 lg:max-w-[calc(42rem+2rem+20rem)]">
      <!-- Step panels (trái — giữ nguyên nội dung) -->
      <div class="min-w-0">

      <!-- ── STEP 1: Thông tin cơ bản ──────────────────── -->
      <section v-show="currentStep === 0" class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-50 text-sky-600">
              <InformationCircleIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Thông tin cơ bản</h2>
              <p class="text-sm text-slate-500">Tên chương trình, năm học và mô tả</p>
            </div>
          </header>
          <div class="space-y-4 p-5">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-600">
                Tên Chương trình <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.trim="form.name"
                type="text"
                placeholder="VD: Tuyến Bắc - Khu A, Năm học 2024-2025"
                :class="[
                  'w-full rounded-lg border bg-white px-3.5 py-3 text-base outline-none transition focus:ring',
                  errors.name
                    ? 'border-rose-300 ring-rose-200 focus:border-rose-400'
                    : 'border-slate-200 ring-va-800/20 focus:border-va-800/40',
                ]"
                @input="errors.name = ''"
              />
              <p v-if="errors.name" class="mt-1 flex items-center gap-1 text-sm text-rose-600">
                <ExclamationCircleIcon class="h-4 w-4" /> {{ errors.name }}
              </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Năm học <span class="text-rose-500">*</span>
                </label>
                <select v-model="form.school_year" :class="selectClass">
                  <option v-for="y in schoolYearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Loại chương trình <span class="text-rose-500">*</span>
                </label>
                <select v-model="form.program_type" :class="selectClass">
                  <option value="round_trip">Đưa &amp; Đón (2 chiều)</option>
                  <option value="pickup_only">Chỉ Đưa đến trường</option>
                  <option value="dropoff_only">Chỉ Đón về nhà</option>
                </select>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-600">
                Mô tả <span class="font-normal text-slate-400">(tùy chọn)</span>
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="Mô tả ngắn về chương trình, khu vực phục vụ, đặc điểm nổi bật..."
                :class="textareaClass"
              ></textarea>
            </div>
          </div>
        </div>
      </section>

      <!-- ── STEP 2: Tuyến đường & Lịch trình ──────────── -->
      <section v-show="currentStep === 1" class="space-y-5">
        <!-- Tuyến đường -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
              <MapIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Tuyến đường</h2>
              <p class="text-sm text-slate-500">Điểm xuất phát và điểm đến</p>
            </div>
          </header>
          <div class="space-y-4 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Điểm xuất phát <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <MapPinIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-emerald-500" />
                  <input
                    v-model.trim="form.origin_name"
                    type="text"
                    placeholder="Quận Tây Hồ, Hà Nội"
                    class="w-full rounded-lg border border-slate-200 bg-white py-3 pl-10 pr-3 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                  />
                </div>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Điểm đến <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <MapPinIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-rose-500" />
                  <input
                    v-model.trim="form.destination_name"
                    type="text"
                    placeholder="Trường THCS Nguyễn Du"
                    class="w-full rounded-lg border border-slate-200 bg-white py-3 pl-10 pr-3 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                  />
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm">
              <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-600">
                <MapPinIcon class="h-4 w-4" />
              </span>
              <span class="truncate font-medium text-slate-800">{{ form.origin_name || 'Điểm đi' }}</span>
              <ArrowRightIcon class="h-4 w-4 shrink-0 text-slate-400" />
              <span class="truncate font-medium text-slate-800">{{ form.destination_name || 'Điểm đến' }}</span>
            </div>
          </div>
        </div>

        <!-- Lịch trình -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600">
              <CalendarDaysIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Lịch trình</h2>
              <p class="text-sm text-slate-500">Ngày hoạt động, giờ đi và giờ về</p>
            </div>
          </header>
          <div class="space-y-4 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Ngày bắt đầu <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="form.start_date"
                  type="date"
                  :class="[
                    'w-full rounded-lg border bg-white px-3.5 py-3 text-base outline-none transition focus:ring',
                    errors.start_date ? 'border-rose-300 ring-rose-200' : 'border-slate-200 ring-va-800/20 focus:border-va-800/40',
                  ]"
                  @input="errors.start_date = ''"
                />
                <p v-if="errors.start_date" class="mt-1 text-sm text-rose-600">{{ errors.start_date }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Ngày kết thúc <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="form.end_date"
                  type="date"
                  :class="[
                    'w-full rounded-lg border bg-white px-3.5 py-3 text-base outline-none transition focus:ring',
                    errors.end_date ? 'border-rose-300 ring-rose-200' : 'border-slate-200 ring-va-800/20 focus:border-va-800/40',
                  ]"
                  @input="errors.end_date = ''"
                />
                <p v-if="errors.end_date" class="mt-1 text-sm text-rose-600">{{ errors.end_date }}</p>
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-600">
                Ngày hoạt động trong tuần <span class="text-rose-500">*</span>
              </label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="d in weekdays"
                  :key="d.key"
                  type="button"
                  :class="[
                    'h-11 w-12 rounded-lg border text-sm font-semibold transition',
                    form.runs_on.includes(d.key)
                      ? 'border-va-800 bg-va-800 text-white shadow-sm'
                      : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300',
                  ]"
                  @click="toggleDay(d.key)"
                >
                  {{ d.label }}
                </button>
              </div>
              <p v-if="errors.runs_on" class="mt-1 text-sm text-rose-600">{{ errors.runs_on }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <!-- Chuyến sáng -->
              <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <SunIcon class="h-5 w-5 text-amber-500" />
                    <span class="text-sm font-semibold text-slate-800">Chuyến Sáng</span>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form.morning_enabled"
                    :class="switchClass(form.morning_enabled)"
                    @click="form.morning_enabled = !form.morning_enabled"
                  >
                    <span :class="switchKnobClass(form.morning_enabled)"></span>
                  </button>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Đưa đến trường</p>
                <div v-if="form.morning_enabled" class="mt-3 grid gap-3 sm:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Giờ xuất phát</label>
                    <input v-model="form.morning_departure" type="time" :class="timeClass" />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Giờ đến trường</label>
                    <input v-model="form.morning_arrival" type="time" :class="timeClass" />
                  </div>
                </div>
              </div>

              <!-- Chuyến chiều -->
              <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <MoonIcon class="h-5 w-5 text-orange-500" />
                    <span class="text-sm font-semibold text-slate-800">Chuyến Chiều</span>
                  </div>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="form.afternoon_enabled"
                    :class="switchClass(form.afternoon_enabled)"
                    @click="form.afternoon_enabled = !form.afternoon_enabled"
                  >
                    <span :class="switchKnobClass(form.afternoon_enabled)"></span>
                  </button>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Đón về nhà</p>
                <div v-if="form.afternoon_enabled" class="mt-3 grid gap-3 sm:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Giờ tan học</label>
                    <input v-model="form.afternoon_departure" type="time" :class="timeClass" />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Giờ về khu vực</label>
                    <input v-model="form.afternoon_arrival" type="time" :class="timeClass" />
                  </div>
                </div>
              </div>
            </div>
            <p v-if="errors.trips" class="text-sm text-rose-600">{{ errors.trips }}</p>
          </div>
        </div>
      </section>

      <!-- ── STEP 3: Xe & Nhân sự ───────────────────────── -->
      <section v-show="currentStep === 2" class="space-y-5">
        <!-- Sức chứa & Xe -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-50 text-teal-600">
              <UserGroupIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Sức chứa &amp; Xe</h2>
              <p class="text-sm text-slate-500">Thông tin xe và số chỗ tối đa</p>
            </div>
          </header>
          <div class="space-y-4 p-5">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-600">
                Chọn xe <span class="font-normal text-slate-400">(từ Quản lý nguồn lực)</span>
              </label>
              <div v-if="loadingVehicles" class="flex items-center gap-2 py-2.5 text-sm text-slate-500">
                <ArrowPathIcon class="h-4 w-4 animate-spin" /> Đang tải danh sách xe…
              </div>
              <select
                v-else-if="vehicles.length"
                v-model="form.vehicle_id"
                :class="selectClass"
                @change="onVehicleChange"
              >
                <option value="">— Chưa chọn xe —</option>
                <option v-for="v in vehicleOptions" :key="v.id" :value="v.id">{{ vehicleOptionLabel(v) }}</option>
              </select>
              <div
                v-else
                class="flex items-center gap-2.5 rounded-lg border border-dashed border-slate-200 bg-slate-50/60 px-3.5 py-3 text-sm text-slate-500"
              >
                <ExclamationCircleIcon class="h-5 w-5 shrink-0 text-slate-400" />
                <span>
                  Chưa có xe nào trong hệ thống.
                  <a :href="vehiclesHref" target="_blank" rel="noopener" class="font-medium text-va-800 hover:underline">Thêm xe</a>
                  rồi quay lại chọn.
                </span>
              </div>
              <p v-if="vehicles.length" class="mt-1 text-xs text-slate-400">
                Xe lấy từ
                <a :href="vehiclesHref" target="_blank" rel="noopener" class="font-medium text-va-800 hover:underline">Quản lý nguồn lực</a>.
                Biển số &amp; sức chứa tự điền theo xe.
              </p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-600">
                Sức chứa tối đa <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model.number="form.max_capacity"
                  type="number"
                  min="1"
                  :class="[
                    'w-full rounded-lg border bg-white py-3 pl-3.5 pr-16 text-base outline-none transition focus:ring',
                    errors.max_capacity
                      ? 'border-rose-300 ring-rose-200 focus:border-rose-400'
                      : 'border-slate-200 ring-va-800/20 focus:border-va-800/40',
                  ]"
                  @input="errors.max_capacity = ''"
                />
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">chỗ</span>
              </div>
              <p v-if="errors.max_capacity" class="mt-1 flex items-center gap-1 text-sm text-rose-600">
                <ExclamationCircleIcon class="h-4 w-4" /> {{ errors.max_capacity }}
              </p>
              <p v-else class="mt-1 text-xs text-slate-400">Mặc định theo số chỗ của xe, có thể điều chỉnh.</p>
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-teal-200 bg-teal-50/60 px-4 py-3">
              <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-teal-600 shadow-sm">
                <TruckIcon class="h-5 w-5" />
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="truncate text-sm font-semibold text-slate-800">
                    {{ form.vehicle_type || 'Chưa chọn xe' }}
                    <span v-if="form.plate_number" class="font-normal text-slate-500">· {{ form.plate_number }}</span>
                  </span>
                  <span
                    v-if="selectedVehicle && vehicleStatusLabel(selectedVehicle.status)"
                    :class="['shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium', vehicleStatusBadge(selectedVehicle.status)]"
                  >{{ vehicleStatusLabel(selectedVehicle.status) }}</span>
                </div>
                <div class="text-xs text-teal-700">Tối đa {{ form.max_capacity || 0 }} chỗ ngồi</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tài xế & Nhân sự -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600">
              <IdentificationIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Tài xế &amp; Nhân sự</h2>
              <p class="text-sm text-slate-500">Phân công tài xế và người giám sát</p>
            </div>
          </header>
          <div class="space-y-5 p-5">
            <div v-if="loadingDrivers" class="flex items-center gap-2 py-3 text-sm text-slate-500">
              <ArrowPathIcon class="h-4 w-4 animate-spin" /> Đang tải danh sách tài xế…
            </div>
            <template v-else>
              <!-- Tài xế chính -->
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">
                  Tài xế chính <span class="text-rose-500">*</span>
                </label>
                <div
                  v-if="mainDriver"
                  class="flex items-center gap-3 rounded-xl border border-va-800/30 bg-va-800/5 px-4 py-3"
                >
                  <DriverAvatar :driver="mainDriver" />
                  <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ mainDriver.full_name }}</div>
                    <div class="truncate text-xs text-slate-500">
                      GPLX: {{ mainDriver.license_class || '—' }} ·
                      <span :class="availabilityTextClass(mainDriver.availability_status)">{{ availabilityLabel(mainDriver.availability_status) }}</span>
                    </div>
                  </div>
                  <button
                    type="button"
                    class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                    @click="changingMain = !changingMain"
                  >
                    Thay đổi
                  </button>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400">
                  Chưa chọn tài xế chính.
                  <button type="button" class="font-medium text-va-800 hover:underline" @click="changingMain = true">Chọn ngay</button>
                </div>
                <p v-if="errors.driver" class="mt-1 text-sm text-rose-600">{{ errors.driver }}</p>

                <div v-if="changingMain" class="mt-2 max-h-56 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-1">
                  <button
                    v-for="d in drivers"
                    :key="d.id"
                    type="button"
                    :class="[
                      'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left transition',
                      String(d.id) === String(mainDriverId) ? 'bg-va-800/10' : 'hover:bg-slate-50',
                    ]"
                    @click="pickMain(d.id)"
                  >
                    <DriverAvatar :driver="d" small />
                    <span class="min-w-0 flex-1">
                      <span class="block truncate text-sm font-medium text-slate-800">{{ d.full_name }}</span>
                      <span class="block truncate text-xs text-slate-400">GPLX: {{ d.license_class || '—' }}</span>
                    </span>
                    <span :class="availabilityBadgeClass(d.availability_status)">{{ availabilityLabel(d.availability_status) }}</span>
                  </button>
                  <p v-if="!drivers.length" class="px-3 py-2 text-sm text-slate-400">Không có tài xế khả dụng.</p>
                </div>
              </div>

              <!-- Tài xế phụ -->
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Tài xế phụ / dự phòng</label>
                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                  <label
                    v-for="d in secondaryCandidates"
                    :key="d.id"
                    class="flex cursor-pointer items-center gap-3 px-4 py-2.5 transition hover:bg-slate-50"
                  >
                    <input
                      type="checkbox"
                      :checked="form.secondary_driver_ids.includes(d.id)"
                      class="h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-800/30"
                      @change="toggleSecondary(d.id)"
                    />
                    <DriverAvatar :driver="d" small muted />
                    <span class="min-w-0 flex-1">
                      <span class="block truncate text-sm font-medium text-slate-700">{{ d.full_name }}</span>
                      <span class="block truncate text-xs text-slate-400">GPLX: {{ d.license_class || '—' }}</span>
                    </span>
                    <span :class="availabilityBadgeClass(d.availability_status)">{{ availabilityLabel(d.availability_status) }}</span>
                  </label>
                  <p v-if="!secondaryCandidates.length" class="px-4 py-3 text-sm text-slate-400">Không còn tài xế nào khác.</p>
                </div>
              </div>

              <!-- Người phụ trách / Giám sát — autocomplete -->
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">Người phụ trách / Giám sát</label>

                <!-- Đã chọn -->
                <div
                  v-if="selectedResponsibleUser"
                  class="flex items-center gap-3 rounded-xl border border-va-800/30 bg-va-800/5 px-4 py-3"
                >
                  <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-va-800/10 text-[11px] font-bold text-va-800">
                    {{ userInitials(selectedResponsibleUser.name) }}
                  </span>
                  <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ selectedResponsibleUser.name }}</div>
                    <div class="truncate text-xs text-slate-500">
                      {{ selectedResponsibleUser.department_name || selectedResponsibleUser.email || '' }}
                    </div>
                  </div>
                  <button
                    type="button"
                    class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                    @click="clearResponsibleUser"
                  >
                    Xóa
                  </button>
                </div>

                <!-- Ô tìm kiếm -->
                <div v-else class="relative">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="userQuery"
                    type="text"
                    placeholder="Tìm theo tên hoặc email…"
                    autocomplete="off"
                    class="w-full rounded-lg border border-slate-200 bg-white py-3 pl-9 pr-9 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                    @input="onUserQueryInput"
                    @focus="onUserQueryFocus"
                    @blur="onUserQueryBlur"
                  />
                  <div v-if="userSearchLoading" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                    <ArrowPathIcon class="h-4 w-4 animate-spin text-slate-400" />
                  </div>

                  <!-- Dropdown kết quả -->
                  <div
                    v-if="userDropdownOpen && (userResults.length || (userQuery.length >= 2 && !userSearchLoading))"
                    class="absolute left-0 right-0 top-[calc(100%+4px)] z-50 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                  >
                    <template v-if="userResults.length">
                      <button
                        v-for="u in userResults"
                        :key="u.id"
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-50"
                        @mousedown.prevent="pickResponsibleUser(u)"
                      >
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-va-800/10 text-[10px] font-bold text-va-800">
                          {{ userInitials(u.name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                          <div class="truncate text-sm font-medium text-slate-800">{{ u.name }}</div>
                          <div v-if="u.department_name || u.email" class="truncate text-xs text-slate-400">
                            {{ u.department_name || u.email }}
                          </div>
                        </div>
                      </button>
                    </template>
                    <p v-else class="px-4 py-3 text-sm text-slate-400">Không tìm thấy người dùng nào.</p>
                  </div>

                  <p v-if="userQuery.length > 0 && userQuery.length < 2" class="mt-1 text-xs text-slate-400">
                    Nhập ít nhất 2 ký tự để tìm kiếm.
                  </p>
                </div>
              </div>
            </template>
          </div>
        </div>
      </section>

      <!-- ── STEP 4: Ghi chú & Xác nhận ────────────────── -->
      <section v-show="currentStep === 3" class="space-y-5">
        <!-- Ghi chú -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-slate-500">
              <DocumentTextIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Ghi chú</h2>
              <p class="text-sm text-slate-500">Lưu ý nội bộ (tùy chọn)</p>
            </div>
          </header>
          <div class="p-5">
            <textarea
              v-model="form.notes"
              rows="3"
              placeholder="Ghi chú nội bộ, lưu ý vận hành, liên hệ khẩn cấp..."
              :class="textareaClass"
            ></textarea>
          </div>
        </div>

        <!-- Xem lại -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-va-800/10 text-va-800">
              <CheckCircleIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Xem lại thông tin</h2>
              <p class="text-sm text-slate-500">Kiểm tra trước khi lưu và sinh lịch</p>
            </div>
          </header>
          <dl class="divide-y divide-slate-100 px-5 text-sm">
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Tên chương trình</dt>
              <dd class="min-w-0 text-right font-semibold text-slate-900">{{ form.name || '—' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Năm học</dt>
              <dd class="text-slate-700">{{ form.school_year }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Loại</dt>
              <dd class="text-slate-700">{{ programTypeLabel }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Tuyến đường</dt>
              <dd class="min-w-0 text-right text-slate-700">
                {{ [form.origin_name, form.destination_name].filter(Boolean).join(' → ') || '—' }}
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Thời gian</dt>
              <dd class="text-slate-700">{{ form.start_date || '—' }} → {{ form.end_date || '—' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Lịch chạy</dt>
              <dd class="text-slate-700">{{ runsOnLabel }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Chuyến</dt>
              <dd class="text-right text-slate-700">
                <span v-if="form.morning_enabled">Sáng {{ form.morning_departure }}</span>
                <span v-if="form.morning_enabled && form.afternoon_enabled"> · </span>
                <span v-if="form.afternoon_enabled">Chiều {{ form.afternoon_departure }}</span>
                <span v-if="!form.morning_enabled && !form.afternoon_enabled">—</span>
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Xe</dt>
              <dd class="text-slate-700">
                {{ form.vehicle_type || 'Chưa chọn' }}<span v-if="form.plate_number"> · {{ form.plate_number }}</span>
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Sức chứa</dt>
              <dd class="text-slate-700">{{ form.max_capacity }} chỗ</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Tài xế chính</dt>
              <dd class="text-slate-700">{{ mainDriver?.full_name || '—' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Người phụ trách</dt>
              <dd class="text-slate-700">{{ selectedResponsibleUser?.name || '—' }}</dd>
            </div>
          </dl>
        </div>

        <div class="flex items-start gap-2.5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
          <InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-sky-500" />
          <span>Khi lưu, hệ thống sẽ tự sinh các ngày vận hành theo khoảng ngày &amp; lịch chạy đã chọn.</span>
        </div>
      </section>
      </div>

      <!-- Hướng dẫn (phải) -->
      <aside class="min-w-0 lg:sticky lg:top-24 lg:self-start" aria-labelledby="tp-create-guide-title">
        <div class="rounded-2xl border border-sky-200/80 bg-gradient-to-br from-sky-50/95 via-white to-white p-4 shadow-sm ring-1 ring-sky-900/[0.04] sm:p-5">
          <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100 text-sky-600">
              <LightBulbIcon class="h-5 w-5" aria-hidden="true" />
            </span>
            <div class="min-w-0 flex-1">
              <p id="tp-create-guide-title" class="text-xs font-bold uppercase tracking-wide text-sky-950 sm:text-sm">
                Hướng dẫn tạo chương trình
              </p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ currentStepGuide.title }}</p>
            </div>
          </div>
          <ol class="mt-4 space-y-2.5 text-[13px] leading-relaxed text-slate-600">
            <li
              v-for="(tip, i) in currentStepGuide.tips"
              :key="i"
              class="flex gap-2"
            >
              <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-800">
                {{ i + 1 }}
              </span>
              <span>{{ tip }}</span>
            </li>
          </ol>
          <p v-if="currentStepGuide.note" class="mt-4 rounded-xl border border-sky-100 bg-sky-50/80 px-3 py-2.5 text-xs leading-relaxed text-sky-900">
            {{ currentStepGuide.note }}
          </p>
        </div>
      </aside>
    </div>

    <!-- Sticky footer navigation -->
    <div class="sticky bottom-0 z-30 -mx-3 border-t border-slate-200 bg-white/95 px-3 py-3 backdrop-blur sm:-mx-4 sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
      <div class="mx-auto flex max-w-2xl items-center justify-between gap-3 px-2 sm:px-4">
        <button
          v-if="currentStep > 0"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="prevStep"
        >
          <ChevronLeftIcon class="h-4 w-4" /> Quay lại
        </button>
        <div v-else />

        <div class="flex items-center gap-3">
          <span class="text-sm text-slate-400">{{ currentStep + 1 }} / {{ STEPS.length }}</span>
          <Button v-if="currentStep < STEPS.length - 1" @click="nextStep">
            Tiếp theo <ChevronRightIcon class="ml-1 h-4 w-4" />
          </Button>
          <Button v-else :loading="saving" @click="submit">
            Lưu &amp; sinh lịch
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  XMarkIcon,
  ChevronRightIcon,
  ChevronLeftIcon,
  InformationCircleIcon,
  ExclamationCircleIcon,
  MapIcon,
  MapPinIcon,
  ArrowRightIcon,
  CalendarDaysIcon,
  SunIcon,
  MoonIcon,
  UserGroupIcon,
  TruckIcon,
  IdentificationIcon,
  DocumentTextIcon,
  ArrowPathIcon,
  MagnifyingGlassIcon,
  CheckIcon,
  CheckCircleIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import { createProgram } from '../../api/transportProgram'
import { listDrivers, listVehicles, searchUsersForDispatchForm } from '../../api/operational'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const router = useRouter()
const saving = ref(false)
const loadingDrivers = ref(false)
const loadingVehicles = ref(false)
const drivers = ref([])
const vehicles = ref([])
const vehiclesHref = router.resolve({ name: 'resourcesList' }).href

// ── Steps ──────────────────────────────────────────────────────────────────────
const STEPS = [
  { key: 'basic', label: 'Thông tin' },
  { key: 'route', label: 'Tuyến & Lịch' },
  { key: 'resources', label: 'Xe & Nhân sự' },
  { key: 'review', label: 'Hoàn tất' },
]
const currentStep = ref(0)

const STEP_GUIDES = [
  {
    title: 'Bước 1 — Thông tin cơ bản',
    tips: [
      'Đặt tên rõ ràng: khu vực/tuyến + năm học (VD: Tuyến Bắc — Khu A, 2025-2026).',
      'Chọn đúng năm học để báo cáo và lọc danh sách sau này.',
      'Loại chương trình: «2 chiều» nếu vừa đưa sáng vừa đón chiều; chọn «chỉ đưa» hoặc «chỉ đón» nếu trường chỉ cần một chiều.',
      'Mô tả là tùy chọn — nên ghi khu vực phục vụ hoặc lưu ý đặc biệt cho điều vận.',
    ],
    note: 'Sau khi lưu, bạn vẫn có thể chỉnh sửa một số thông tin trong workspace chương trình.',
  },
  {
    title: 'Bước 2 — Tuyến & lịch trình',
    tips: [
      'Điểm xuất phát thường là khu dân cư/quận; điểm đến là tên trường hoặc cổng trường.',
      'Ngày bắt đầu — kết thúc là khoảng thời gian cả năm học hoặc học kỳ; hệ thống chỉ sinh ngày vận hành trong khoảng này.',
      'Chọn các thứ trong tuần có xe chạy (mặc định T2–T6). Bỏ T7/CN nếu không đưa đón cuối tuần.',
      'Bật ít nhất một chiều (sáng hoặc chiều). Giờ xuất phát nên sớm hơn giờ vào lớp / tan học đủ buffer.',
    ],
    note: 'Ngày nghỉ lễ có thể loại trừ sau khi tạo chương trình, trong phần quản lý lịch.',
  },
  {
    title: 'Bước 3 — Xe & nhân sự',
    tips: [
      'Chọn xe từ Quản lý nguồn lực — biển số và sức chứa tự điền; có thể giảm số chỗ tối đa nếu không muốn full xe.',
      'Tài xế chính là bắt buộc; tài xế phụ giúp thay ca hoặc dự phòng.',
      'Người phụ trách/giám sát: nhân sự trường theo dõi chương trình (tìm theo tên hoặc email).',
    ],
    note: 'Chưa có xe trong hệ thống? Mở Quản lý nguồn lực ở tab mới, thêm xe rồi quay lại trang này.',
  },
  {
    title: 'Bước 4 — Hoàn tất',
    tips: [
      'Xem lại toàn bộ mục trong bảng tóm tắt — bấm số bước phía trên để quay sửa nếu sai.',
      'Ghi chú nội bộ chỉ điều vận thấy (liên hệ khẩn, quy ước điểm đón…).',
      'Bấm «Lưu & sinh lịch» để tạo chương trình và các ngày vận hành theo lịch đã chọn.',
    ],
    note: 'Tiếp theo: đăng ký học sinh vào chương trình và phân công tài xế theo ngày nếu cần.',
  },
]

const currentStepGuide = computed(() => STEP_GUIDES[currentStep.value] ?? STEP_GUIDES[0])

function nextStep() {
  if (!validateStep(currentStep.value)) return
  currentStep.value++
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function jumpTo(idx) {
  currentStep.value = idx
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ── Shared input classes ───────────────────────────────────────────────────────
const selectClass =
  'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-base text-slate-700 outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'
const textareaClass =
  'w-full resize-y rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'
const timeClass =
  'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'

function switchClass(on) {
  return ['relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition', on ? 'bg-va-800' : 'bg-slate-300']
}
function switchKnobClass(on) {
  return ['inline-block h-5 w-5 transform rounded-full bg-white shadow transition', on ? 'translate-x-5' : 'translate-x-0.5']
}

// ── Options ────────────────────────────────────────────────────────────────────
const weekdays = [
  { key: 'mon', label: 'T2' },
  { key: 'tue', label: 'T3' },
  { key: 'wed', label: 'T4' },
  { key: 'thu', label: 'T5' },
  { key: 'fri', label: 'T6' },
  { key: 'sat', label: 'T7' },
  { key: 'sun', label: 'CN' },
]

const schoolYearOptions = computed(() => {
  const base = new Date().getFullYear()
  const out = []
  for (let i = -1; i <= 2; i++) out.push(`${base + i}-${base + i + 1}`)
  return out
})

// ── Form state ─────────────────────────────────────────────────────────────────
const form = reactive({
  name: '',
  school_year: '',
  program_type: 'round_trip',
  description: '',

  origin_name: '',
  destination_name: '',

  start_date: '',
  end_date: '',
  runs_on: ['mon', 'tue', 'wed', 'thu', 'fri'],
  morning_enabled: true,
  morning_departure: '06:00',
  morning_arrival: '07:15',
  afternoon_enabled: true,
  afternoon_departure: '17:00',
  afternoon_arrival: '18:00',

  vehicle_id: '',
  vehicle_type: '',
  plate_number: '',
  max_capacity: 45,

  secondary_driver_ids: [],
  responsible_user_id: '',

  notes: '',
})

const errors = reactive({
  name: '',
  start_date: '',
  end_date: '',
  runs_on: '',
  trips: '',
  max_capacity: '',
  driver: '',
})

// ── Review computed ────────────────────────────────────────────────────────────
const programTypeLabel = computed(() => ({
  round_trip: 'Đưa & Đón (2 chiều)',
  pickup_only: 'Chỉ Đưa đến trường',
  dropoff_only: 'Chỉ Đón về nhà',
}[form.program_type] || '—'))

const runsOnLabel = computed(() => {
  const map = { mon: 'T2', tue: 'T3', wed: 'T4', thu: 'T5', fri: 'T6', sat: 'T7', sun: 'CN' }
  return form.runs_on.map((k) => map[k] || k).join(', ') || '—'
})

// ── Vehicles ──────────────────────────────────────────────────────────────────
const VEHICLE_STATUS = {
  ready: { label: 'Sẵn sàng', order: 0, badge: 'bg-emerald-50 text-emerald-600' },
  in_use: { label: 'Đang dùng', order: 1, badge: 'bg-sky-50 text-sky-600' },
  maintenance: { label: 'Bảo trì', order: 2, badge: 'bg-amber-50 text-amber-600' },
  broken: { label: 'Hỏng', order: 3, badge: 'bg-rose-50 text-rose-500' },
}

const vehicleOptions = computed(() =>
  [...vehicles.value].sort(
    (a, b) =>
      (VEHICLE_STATUS[a.status]?.order ?? 9) - (VEHICLE_STATUS[b.status]?.order ?? 9) ||
      String(a.license_plate || '').localeCompare(String(b.license_plate || ''), 'vi'),
  ),
)

const selectedVehicle = computed(
  () => vehicles.value.find((v) => String(v.id) === String(form.vehicle_id)) || null,
)

function vehicleStatusLabel(s) { return VEHICLE_STATUS[s]?.label || '' }
function vehicleStatusBadge(s) { return VEHICLE_STATUS[s]?.badge || 'bg-slate-100 text-slate-500' }

function vehicleOptionLabel(v) {
  const base =
    [v.license_plate, v.type, v.seat_count ? `${v.seat_count} chỗ` : null].filter(Boolean).join(' · ') ||
    `Xe #${v.id}`
  const st = vehicleStatusLabel(v.status)
  return st ? `${base} — ${st}` : base
}

function onVehicleChange() {
  const v = selectedVehicle.value
  if (!v) { form.vehicle_type = ''; form.plate_number = ''; return }
  form.vehicle_type = v.type || ''
  form.plate_number = v.license_plate || ''
  if (v.seat_count) { form.max_capacity = v.seat_count; errors.max_capacity = '' }
}

// ── Drivers ───────────────────────────────────────────────────────────────────
const mainDriverId = ref('')
const changingMain = ref(false)

const mainDriver = computed(() => drivers.value.find((d) => String(d.id) === String(mainDriverId.value)) || null)
const secondaryCandidates = computed(() => drivers.value.filter((d) => String(d.id) !== String(mainDriverId.value)))

function pickMain(id) {
  mainDriverId.value = id
  changingMain.value = false
  errors.driver = ''
  const i = form.secondary_driver_ids.indexOf(id)
  if (i >= 0) form.secondary_driver_ids.splice(i, 1)
}

function toggleSecondary(id) {
  const i = form.secondary_driver_ids.indexOf(id)
  if (i >= 0) form.secondary_driver_ids.splice(i, 1)
  else form.secondary_driver_ids.push(id)
}

function availabilityLabel(s) { return { available: 'Rảnh', busy: 'Bận', offline: 'Không có' }[s] || '—' }
function availabilityTextClass(s) {
  return { available: 'text-emerald-600', busy: 'text-amber-600', offline: 'text-slate-400' }[s] || 'text-slate-400'
}
function availabilityBadgeClass(s) {
  const base = 'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium '
  return base + ({ available: 'bg-emerald-50 text-emerald-600', busy: 'bg-amber-50 text-amber-600', offline: 'bg-rose-50 text-rose-500' }[s] || 'bg-slate-100 text-slate-500')
}

const DriverAvatar = (props) => {
  const d = props.driver || {}
  const url = d.user?.avatar_url
  const size = props.small ? 'h-9 w-9' : 'h-11 w-11'
  if (url) return h('img', { src: url, alt: d.full_name, class: `${size} shrink-0 rounded-full object-cover` })
  const initials = (d.full_name || '?').trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
  const tone = props.muted ? 'bg-slate-100 text-slate-400' : 'bg-va-800/10 text-va-800'
  return h('span', { class: `${size} grid shrink-0 place-items-center rounded-full text-xs font-semibold ${tone}` }, initials)
}
DriverAvatar.props = ['driver', 'small', 'muted']

// ── User autocomplete (Người phụ trách) ───────────────────────────────────────
const userQuery = ref('')
const userResults = ref([])
const userSearchLoading = ref(false)
const userDropdownOpen = ref(false)
const selectedResponsibleUser = ref(null)
let _userTimer = null

function userInitials(name) {
  return (name || '?').trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
}

function onUserQueryInput() {
  clearTimeout(_userTimer)
  const q = userQuery.value.trim()
  if (q.length < 2) {
    userResults.value = []
    userDropdownOpen.value = false
    return
  }
  _userTimer = setTimeout(async () => {
    userSearchLoading.value = true
    try {
      const res = await searchUsersForDispatchForm(q)
      userResults.value = res ?? []
      userDropdownOpen.value = true
    } catch {
      userResults.value = []
    } finally {
      userSearchLoading.value = false
    }
  }, 350)
}

function onUserQueryFocus() {
  if (userQuery.value.trim().length >= 2 && userResults.value.length) {
    userDropdownOpen.value = true
  }
}

function onUserQueryBlur() {
  setTimeout(() => { userDropdownOpen.value = false }, 200)
}

function pickResponsibleUser(u) {
  selectedResponsibleUser.value = u
  form.responsible_user_id = String(u.id)
  userQuery.value = ''
  userDropdownOpen.value = false
  userResults.value = []
}

function clearResponsibleUser() {
  selectedResponsibleUser.value = null
  form.responsible_user_id = ''
}

// ── Day toggle ─────────────────────────────────────────────────────────────────
function toggleDay(key) {
  const i = form.runs_on.indexOf(key)
  if (i >= 0) form.runs_on.splice(i, 1)
  else form.runs_on.push(key)
  if (form.runs_on.length) errors.runs_on = ''
}

// ── Navigation ─────────────────────────────────────────────────────────────────
function goBack() { router.push({ name: 'tpPrograms' }) }

// ── Per-step validation ────────────────────────────────────────────────────────
function validateStep(step) {
  if (step === 0) {
    errors.name = form.name ? '' : 'Tên chương trình không được để trống'
    return !errors.name
  }
  if (step === 1) {
    errors.start_date = form.start_date ? '' : 'Chọn ngày bắt đầu'
    errors.end_date = form.end_date ? '' : 'Chọn ngày kết thúc'
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
      errors.end_date = 'Ngày kết thúc phải sau ngày bắt đầu'
    }
    errors.runs_on = form.runs_on.length ? '' : 'Chọn ít nhất một ngày hoạt động'
    errors.trips = form.morning_enabled || form.afternoon_enabled ? '' : 'Bật ít nhất một chiều (sáng hoặc chiều)'
    return !errors.start_date && !errors.end_date && !errors.runs_on && !errors.trips
  }
  if (step === 2) {
    errors.max_capacity = form.max_capacity && Number(form.max_capacity) > 0 ? '' : 'Nhập sức chứa hợp lệ (> 0)'
    errors.driver = mainDriverId.value ? '' : 'Chọn tài xế chính'
    return !errors.max_capacity && !errors.driver
  }
  return true
}

// ── Build payload ──────────────────────────────────────────────────────────────
function buildPayload() {
  const departure = form.morning_enabled ? form.morning_departure : form.afternoon_departure
  const ret = form.afternoon_enabled ? form.afternoon_departure : null

  return {
    name: form.name,
    description: form.description || null,
    origin_name: form.origin_name || null,
    destination_name: form.destination_name || null,
    departure_time: departure,
    return_time: ret,
    start_date: form.start_date,
    end_date: form.end_date,
    runs_on: form.runs_on,
    default_driver_id: mainDriverId.value ? Number(mainDriverId.value) : null,
    default_vehicle_id: form.vehicle_id ? Number(form.vehicle_id) : null,
    responsible_user_id: form.responsible_user_id ? Number(form.responsible_user_id) : null,
    notes: form.notes || null,
    settings: {
      school_year: form.school_year,
      program_type: form.program_type,
      morning: { enabled: form.morning_enabled, departure: form.morning_departure, arrival: form.morning_arrival },
      afternoon: { enabled: form.afternoon_enabled, departure: form.afternoon_departure, arrival: form.afternoon_arrival },
      vehicle: { type: form.vehicle_type || null, plate_number: form.plate_number || null, max_capacity: Number(form.max_capacity || 0) },
      secondary_driver_ids: form.secondary_driver_ids.map((id) => Number(id)),
    },
  }
}

// ── Submit ─────────────────────────────────────────────────────────────────────
async function submit() {
  for (let i = 0; i < STEPS.length - 1; i++) {
    if (!validateStep(i)) {
      currentStep.value = i
      window.scrollTo({ top: 0, behavior: 'smooth' })
      return
    }
  }
  saving.value = true
  try {
    const res = await createProgram(buildPayload())
    showAppSuccess(`Đã tạo chương trình và sinh ${res?.day_count ?? 0} ngày vận hành.`)
    router.push({ name: 'tpProgramWorkspace', params: { id: res.program.id } })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}

// ── Lifecycle ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  form.school_year = schoolYearOptions.value[1]

  loadingDrivers.value = true
  try {
    const res = await listDrivers({ per_page: 100 })
    drivers.value = res?.items ?? []
    const firstAvailable = drivers.value.find((d) => d.availability_status === 'available') || drivers.value[0]
    if (firstAvailable) mainDriverId.value = firstAvailable.id
  } catch {
    drivers.value = []
  } finally {
    loadingDrivers.value = false
  }

  loadingVehicles.value = true
  try {
    const res = await listVehicles({ per_page: 200 })
    vehicles.value = res?.items ?? []
  } catch {
    vehicles.value = []
  } finally {
    loadingVehicles.value = false
  }
})
</script>
