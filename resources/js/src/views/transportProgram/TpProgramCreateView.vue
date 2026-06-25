<template>
  <div
    class="tp-program-create w-full max-w-none min-w-0 text-slate-900"
    data-testid="tp-program-create-page"
  >
    <div
      class="mx-auto w-full max-w-none space-y-5 px-4 py-4 sm:space-y-6 sm:px-6 sm:py-5 lg:px-8 lg:py-6 supports-[padding:max(0px)]:pl-[max(1rem,env(safe-area-inset-left))] supports-[padding:max(0px)]:pr-[max(1rem,env(safe-area-inset-right))] supports-[padding:max(0px)]:pb-[max(1rem,env(safe-area-inset-bottom))]"
    >
      <!-- Top bar -->
      <div class="border-b border-slate-200 pb-3 sm:pb-4">
        <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3">
          <nav class="flex min-w-0 flex-1 items-center gap-1.5 text-sm">
            <button
              type="button"
              class="shrink-0 font-medium text-slate-500 transition hover:text-va-800"
              data-testid="tp-create-back"
              @click="goBack"
            >
              Chương trình Đưa đón
            </button>
            <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-300" aria-hidden="true" />
            <span class="truncate font-semibold text-slate-900">Tạo chương trình mới</span>
          </nav>
          <button
            type="button"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            data-testid="tp-create-cancel"
            @click="goBack"
          >
            <XMarkIcon class="h-4 w-4" aria-hidden="true" /> Hủy
          </button>
        </div>
      </div>

      <!-- Step progress -->
      <div class="w-full" aria-label="Tiến trình tạo chương trình">
        <p class="mb-2 text-sm font-semibold text-slate-900 sm:hidden">
          Bước {{ currentStep + 1 }}/{{ STEPS.length }} — {{ STEPS[currentStep].label }}
        </p>
        <ol class="flex items-start">
          <li
            v-for="(step, idx) in STEPS"
            :key="step.key"
            class="flex min-w-0 flex-1 flex-col items-center gap-1 sm:gap-1.5"
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
                :aria-current="currentStep === idx ? 'step' : undefined"
                :class="[
                  'mx-1 grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold transition-all duration-200 sm:mx-2 sm:h-9 sm:w-9 sm:text-sm',
                  currentStep === idx
                    ? 'bg-va-800 text-white shadow-md ring-4 ring-va-800/20'
                    : currentStep > idx
                    ? 'cursor-pointer bg-emerald-500 text-white hover:ring-2 hover:ring-emerald-400/40'
                    : 'cursor-default bg-slate-100 text-slate-400',
                ]"
                @click="currentStep > idx ? jumpTo(idx) : undefined"
              >
                <CheckIcon v-if="currentStep > idx" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
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
                'hidden text-center text-[11px] font-medium leading-tight sm:block',
                currentStep === idx ? 'text-va-800' : currentStep > idx ? 'text-emerald-600' : 'text-slate-400',
              ]"
            >
              {{ step.label }}
            </span>
          </li>
        </ol>
      </div>

      <!-- Hướng dẫn — full width, không sticky -->
      <aside class="w-full" aria-labelledby="tp-create-guide-title">
        <div class="rounded-2xl border border-sky-200/80 bg-gradient-to-br from-sky-50/95 via-white to-white p-4 shadow-sm ring-1 ring-sky-900/[0.04] sm:p-5 lg:flex lg:items-start lg:gap-5">
          <div class="flex items-start gap-3 lg:min-w-[14rem] lg:shrink-0">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100 text-sky-600">
              <LightBulbIcon class="h-5 w-5" aria-hidden="true" />
            </span>
            <div class="min-w-0 flex-1">
              <p id="tp-create-guide-title" class="text-xs font-bold uppercase tracking-wide text-sky-950 sm:text-sm">
                Hướng dẫn
              </p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ currentStepGuide.title }}</p>
            </div>
          </div>
          <ol class="mt-4 space-y-2 text-[13px] leading-relaxed text-slate-600 lg:mt-0 lg:flex-1 lg:columns-1 xl:columns-2 xl:gap-x-8">
            <li
              v-for="(tip, i) in currentStepGuide.tips"
              :key="i"
              class="mb-2.5 flex gap-2 break-inside-avoid"
            >
              <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-800">
                {{ i + 1 }}
              </span>
              <span>{{ tip }}</span>
            </li>
          </ol>
          <p
            v-if="currentStepGuide.note"
            class="mt-4 rounded-xl border border-sky-100 bg-sky-50/80 px-3 py-2.5 text-xs leading-relaxed text-sky-900 lg:mt-0 lg:min-w-[12rem] lg:max-w-xs lg:shrink-0 lg:self-center"
          >
            {{ currentStepGuide.note }}
          </p>
        </div>
      </aside>

      <!-- Nội dung bước — full width -->
      <div class="min-w-0 w-full space-y-5 pb-4 sm:space-y-6">

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
      <section v-show="currentStep === 2" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Xe -->
          <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
              <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-50 text-teal-600">
                  <TruckIcon class="h-5 w-5" />
                </span>
                <div>
                  <h2 class="text-base font-semibold text-slate-900">Danh sách xe</h2>
                  <p class="text-sm text-slate-500">Tích chọn một hoặc nhiều xe; đặt một xe làm mặc định</p>
                </div>
              </div>
              <span
                v-if="selectedVehicles.length"
                class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800"
              >
                Đã chọn {{ selectedVehicles.length }}
              </span>
            </header>
            <div class="space-y-4 p-5">
              <div v-if="loadingVehicles" class="flex items-center gap-2 py-6 text-sm text-slate-500">
                <ArrowPathIcon class="h-4 w-4 animate-spin" /> Đang tải danh sách xe…
              </div>
              <div
                v-else-if="!vehicles.length"
                class="flex items-center gap-2.5 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-4 text-sm text-slate-500"
              >
                <ExclamationCircleIcon class="h-5 w-5 shrink-0 text-slate-400" />
                <span>
                  Chưa có xe nào.
                  <a :href="vehiclesHref" target="_blank" rel="noopener" class="font-medium text-va-800 hover:underline">Thêm xe</a>
                  rồi quay lại chọn.
                </span>
              </div>
              <template v-else>
                <div class="relative">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="vehicleSearch"
                    type="search"
                    placeholder="Tìm biển số, loại xe…"
                    class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                    data-testid="tp-create-vehicle-search"
                  />
                </div>
                <div class="grid max-h-[min(28rem,55vh)] grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                  <div
                    v-for="v in filteredVehicleOptions"
                    :key="v.id"
                    :class="[
                      'flex flex-col rounded-xl border p-3 transition',
                      isVehicleSelected(v.id)
                        ? 'border-teal-300 bg-teal-50/80 ring-1 ring-teal-200/80'
                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/80',
                    ]"
                    :data-testid="`tp-create-vehicle-${v.id}`"
                  >
                    <label class="flex cursor-pointer items-start gap-3">
                      <input
                        type="checkbox"
                        :checked="isVehicleSelected(v.id)"
                        class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-700 focus:ring-teal-600/30"
                        @change="toggleVehicle(v.id)"
                      />
                      <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-1.5">
                          <span class="text-sm font-semibold text-slate-900">{{ v.license_plate || `Xe #${v.id}` }}</span>
                          <span
                            v-if="isPrimaryVehicle(v.id)"
                            class="rounded-full bg-teal-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                          >Mặc định</span>
                          <span
                            v-else-if="isVehicleSelected(v.id)"
                            class="rounded-full border border-teal-200 bg-white px-2 py-0.5 text-[10px] font-medium text-teal-800"
                          >Phụ</span>
                          <span
                            v-if="vehicleStatusLabel(v.status)"
                            :class="['rounded-full px-2 py-0.5 text-[10px] font-medium', vehicleStatusBadge(v.status)]"
                          >{{ vehicleStatusLabel(v.status) }}</span>
                        </span>
                        <span class="mt-0.5 block text-xs text-slate-500">
                          {{ v.type || 'Chưa rõ loại xe' }}<span v-if="v.seat_count"> · {{ v.seat_count }} chỗ</span>
                        </span>
                      </span>
                    </label>
                    <button
                      v-if="isVehicleSelected(v.id) && !isPrimaryVehicle(v.id)"
                      type="button"
                      class="mt-3 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-teal-300 hover:text-teal-700"
                      :data-testid="`tp-create-vehicle-default-${v.id}`"
                      @click="setAsDefaultVehicle(v.id)"
                    >
                      Đặt làm xe mặc định
                    </button>
                    <span
                      v-else-if="isPrimaryVehicle(v.id)"
                      class="mt-3 w-full rounded-lg bg-teal-700 px-2 py-1.5 text-center text-xs font-semibold text-white"
                    >
                      Xe mặc định
                    </span>
                  </div>
                  <p v-if="!filteredVehicleOptions.length" class="col-span-full py-4 text-center text-sm text-slate-400">
                    Không có xe khớp tìm kiếm.
                  </p>
                </div>
                <p class="text-xs text-slate-400">
                  Nguồn:
                  <a :href="vehiclesHref" target="_blank" rel="noopener" class="font-medium text-va-800 hover:underline">Quản lý nguồn lực</a>.
                  Bấm «Đặt làm xe mặc định» trên thẻ xe để chọn xe chính cho chương trình.
                </p>
              </template>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                  Sức chứa tối đa (chương trình) <span class="text-rose-500">*</span>
                </label>
                <div class="relative w-full max-w-md sm:max-w-lg">
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
                    data-testid="tp-create-max-capacity"
                    @input="errors.max_capacity = ''"
                  />
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">chỗ</span>
                </div>
                <p v-if="errors.max_capacity" class="mt-1 flex items-center gap-1 text-sm text-rose-600">
                  <ExclamationCircleIcon class="h-4 w-4" /> {{ errors.max_capacity }}
                </p>
                <p v-else class="mt-1 text-xs text-slate-400">
                  <template v-if="totalVehicleSeats">
                    Gợi ý {{ totalVehicleSeats }} chỗ (tổng số chỗ các xe đã chọn). Có thể chỉnh nếu không dùng hết.
                  </template>
                  <template v-else>Mặc định theo xe đã chọn; có thể điều chỉnh thủ công.</template>
                </p>
              </div>
            </div>
          </div>

          <!-- Tài xế -->
          <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
              <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600">
                  <IdentificationIcon class="h-5 w-5" />
                </span>
                <div>
                  <h2 class="text-base font-semibold text-slate-900">Danh sách tài xế</h2>
                  <p class="text-sm text-slate-500">Chọn nhiều tài xế — một người làm chính, còn lại dự phòng</p>
                </div>
              </div>
              <span
                v-if="selectedDriverCount"
                class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900"
              >
                {{ selectedDriverCount }} tài xế
              </span>
            </header>
            <div class="space-y-4 p-5">
              <div v-if="loadingDrivers" class="flex items-center gap-2 py-6 text-sm text-slate-500">
                <ArrowPathIcon class="h-4 w-4 animate-spin" /> Đang tải danh sách tài xế…
              </div>
              <div
                v-else-if="!drivers.length"
                class="flex items-center gap-2.5 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-4 text-sm text-slate-500"
              >
                <ExclamationCircleIcon class="h-5 w-5 shrink-0 text-slate-400" />
                <span>
                  Chưa có tài xế nào khả dụng.
                  <a :href="driversHref" target="_blank" rel="noopener" class="font-medium text-va-800 hover:underline">Thêm tài xế</a>
                  rồi quay lại chọn.
                </span>
              </div>
              <template v-else>
                <div class="relative">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="driverSearch"
                    type="search"
                    placeholder="Tìm tên tài xế, GPLX…"
                    class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                    data-testid="tp-create-driver-search"
                  />
                </div>
                <p v-if="errors.driver" class="text-sm text-rose-600">{{ errors.driver }}</p>
                <div class="grid max-h-[min(28rem,55vh)] grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                  <div
                    v-for="d in filteredDrivers"
                    :key="d.id"
                    :class="[
                      'flex flex-col rounded-xl border p-3 transition',
                      isDriverSelected(d.id)
                        ? 'border-va-800/30 bg-va-800/[0.04] ring-1 ring-va-800/10'
                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/80',
                    ]"
                    :data-testid="`tp-create-driver-${d.id}`"
                  >
                    <label class="flex cursor-pointer items-start gap-3">
                      <input
                        type="checkbox"
                        :checked="isDriverSelected(d.id)"
                        class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-800/30"
                        @change="toggleDriverSelected(d.id)"
                      />
                      <DriverAvatar :driver="d" small />
                      <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900">{{ d.full_name }}</span>
                        <span class="block truncate text-xs text-slate-500">GPLX: {{ d.license_class || 'Chưa cập nhật' }}</span>
                        <span :class="['mt-1 inline-block', availabilityBadgeClass(d.availability_status)]">
                          {{ availabilityLabel(d.availability_status) }}
                        </span>
                      </span>
                    </label>
                    <button
                      v-if="isDriverSelected(d.id)"
                      type="button"
                      :class="[
                        'mt-3 w-full rounded-lg px-2 py-1.5 text-xs font-semibold transition',
                        isMainDriver(d.id)
                          ? 'bg-va-800 text-white'
                          : 'border border-slate-200 bg-white text-slate-600 hover:border-va-800/30 hover:text-va-800',
                      ]"
                      :data-testid="`tp-create-driver-main-${d.id}`"
                      @click="setAsMainDriver(d.id)"
                    >
                      {{ isMainDriver(d.id) ? 'Tài xế chính' : 'Đặt làm chính' }}
                    </button>
                  </div>
                  <p v-if="!filteredDrivers.length" class="col-span-full py-4 text-center text-sm text-slate-400">
                    {{ drivers.length ? 'Không có tài xế khớp tìm kiếm.' : 'Không có tài xế khả dụng.' }}
                  </p>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Người phụ trách -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-slate-600">
              <UserGroupIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Người phụ trách / Giám sát</h2>
              <p class="text-sm text-slate-500">Tùy chọn — liên hệ điều vận chương trình</p>
            </div>
          </header>
          <div class="p-5">
            <div
              v-if="selectedResponsibleUser"
              class="flex w-full items-center gap-3 rounded-xl border border-va-800/30 bg-va-800/5 px-4 py-3 sm:max-w-xl"
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
                data-testid="tp-create-clear-responsible"
                @click="clearResponsibleUser"
              >
                Xóa
              </button>
            </div>

            <div v-else class="relative w-full sm:max-w-xl">
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="userQuery"
                type="text"
                placeholder="Tìm theo tên hoặc email…"
                autocomplete="off"
                class="w-full rounded-lg border border-slate-200 bg-white py-3 pl-9 pr-9 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                data-testid="tp-create-responsible-search"
                @input="onUserQueryInput"
                @focus="onUserQueryFocus"
                @blur="onUserQueryBlur"
              />
              <div v-if="userSearchLoading" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                <ArrowPathIcon class="h-4 w-4 animate-spin text-slate-400" />
              </div>

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
              <dd class="min-w-0 text-right font-semibold text-slate-900">
                <span v-if="form.name">{{ form.name }}</span>
                <span v-else class="font-normal italic text-slate-400">Chưa đặt tên</span>
              </dd>
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
                <span v-if="form.origin_name || form.destination_name">
                  {{ [form.origin_name, form.destination_name].filter(Boolean).join(' → ') }}
                </span>
                <span v-else class="italic text-slate-400">Chưa nhập tuyến đường</span>
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Thời gian</dt>
              <dd class="text-slate-700">
                <span v-if="form.start_date || form.end_date">
                  {{ form.start_date || 'Chưa chọn' }} → {{ form.end_date || 'Chưa chọn' }}
                </span>
                <span v-else class="italic text-slate-400">Chưa chọn thời gian</span>
              </dd>
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
                <span v-if="!form.morning_enabled && !form.afternoon_enabled" class="italic text-slate-400">Chưa bật chuyến nào</span>
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Xe</dt>
              <dd class="min-w-0 text-right text-slate-700">
                <template v-if="selectedVehicles.length">
                  <span v-for="(v, i) in selectedVehicles" :key="v.id">
                    <span v-if="i"> · </span>
                    {{ v.license_plate || v.type || `#${v.id}` }}<span v-if="i === 0" class="text-xs text-teal-700"> (mặc định)</span>
                  </span>
                </template>
                <span v-else class="italic text-slate-400">Chưa chọn xe</span>
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Sức chứa</dt>
              <dd class="text-slate-700">{{ form.max_capacity }} chỗ</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Tài xế chính</dt>
              <dd class="text-slate-700">
                <span v-if="mainDriver">{{ mainDriver.full_name }}</span>
                <span v-else class="italic text-slate-400">Chưa chọn tài xế chính</span>
              </dd>
            </div>
            <div v-if="form.secondary_driver_ids.length" class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Tài xế dự phòng</dt>
              <dd class="min-w-0 text-right text-slate-700">{{ secondaryDriverNames }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3 py-3">
              <dt class="shrink-0 font-medium text-slate-500">Người phụ trách</dt>
              <dd class="text-slate-700">
                <span v-if="selectedResponsibleUser">{{ selectedResponsibleUser.name }}</span>
                <span v-else class="italic text-slate-400">Chưa phân công</span>
              </dd>
            </div>
          </dl>
        </div>

        <div class="flex items-start gap-2.5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
          <InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-sky-500" />
          <span>Khi lưu, hệ thống sẽ tự sinh các ngày vận hành theo khoảng ngày &amp; lịch chạy đã chọn.</span>
        </div>
      </section>
      </div>

      <!-- Footer navigation -->
      <div class="border-t border-slate-200 pt-4 sm:pt-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <button
            v-if="currentStep > 0"
            type="button"
            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 sm:w-auto"
            data-testid="tp-create-prev"
            @click="prevStep"
          >
            <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" /> Quay lại
          </button>
          <div v-else class="hidden sm:block" aria-hidden="true" />

          <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3"
            :class="currentStep === 0 ? 'sm:ml-auto' : ''"
          >
            <span class="text-center text-sm text-slate-400 sm:text-left">{{ currentStep + 1 }} / {{ STEPS.length }}</span>
            <Button
              v-if="currentStep < STEPS.length - 1"
              class="w-full sm:w-auto"
              data-testid="tp-create-next"
              @click="nextStep"
            >
              Tiếp theo <ChevronRightIcon class="ml-1 h-4 w-4" />
            </Button>
            <Button
              v-else
              class="w-full sm:w-auto"
              data-testid="tp-create-submit"
              :loading="saving"
              @click="submit"
            >
              Lưu &amp; sinh lịch
            </Button>
          </div>
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
const vehiclesHref = router.resolve({ name: 'resourcesList', query: { tab: 'vehicles' } }).href
const driversHref = router.resolve({ name: 'resourcesList', query: { tab: 'drivers' } }).href

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
      'Tích chọn một hoặc nhiều xe cho chương trình; dùng ô tìm kiếm để lọc nhanh theo biển số hoặc loại xe.',
      'Bấm «Đặt làm xe mặc định» ngay trên thẻ xe để chọn xe chính; các xe còn lại là xe phụ / dự phòng.',
      'Sức chứa tự gợi ý bằng tổng số chỗ các xe đã chọn; có thể giảm nếu không ghép hết xe mỗi ngày.',
      'Tích chọn nhiều tài xế rồi bấm «Đặt làm chính» cho người chạy chính — những người còn lại là dự phòng / thay ca.',
      'Người phụ trách/giám sát (tùy chọn): nhân sự trường theo dõi chương trình, tìm theo tên hoặc email.',
    ],
    note: 'Chưa có xe hoặc tài xế? Mở Quản lý nguồn lực ở tab mới, thêm rồi quay lại — danh sách sẽ tự cập nhật khi bạn chọn lại.',
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

  vehicle_ids: [],
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
}[form.program_type] || 'Chưa chọn loại'))

const runsOnLabel = computed(() => {
  const map = { mon: 'T2', tue: 'T3', wed: 'T4', thu: 'T5', fri: 'T6', sat: 'T7', sun: 'CN' }
  return form.runs_on.map((k) => map[k] || k).join(', ') || 'Chưa chọn ngày'
})

// ── Vehicles ──────────────────────────────────────────────────────────────────
const VEHICLE_STATUS = {
  ready: { label: 'Sẵn sàng', order: 0, badge: 'bg-emerald-50 text-emerald-600' },
  in_use: { label: 'Đang dùng', order: 1, badge: 'bg-sky-50 text-sky-600' },
  maintenance: { label: 'Bảo trì', order: 2, badge: 'bg-amber-50 text-amber-600' },
  broken: { label: 'Hỏng', order: 3, badge: 'bg-rose-50 text-rose-500' },
}

const vehicleSearch = ref('')
const driverSearch = ref('')

const vehicleOptions = computed(() =>
  [...vehicles.value].sort(
    (a, b) =>
      (VEHICLE_STATUS[a.status]?.order ?? 9) - (VEHICLE_STATUS[b.status]?.order ?? 9) ||
      String(a.license_plate || '').localeCompare(String(b.license_plate || ''), 'vi'),
  ),
)

const filteredVehicleOptions = computed(() => {
  const q = vehicleSearch.value.trim().toLowerCase()
  if (!q) return vehicleOptions.value
  return vehicleOptions.value.filter((v) => {
    const hay = [
      v.license_plate,
      v.type,
      v.seat_count != null ? String(v.seat_count) : '',
      vehicleStatusLabel(v.status),
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return hay.includes(q)
  })
})

const selectedVehicles = computed(() =>
  form.vehicle_ids
    .map((id) => vehicles.value.find((v) => String(v.id) === String(id)))
    .filter(Boolean),
)

const primaryVehicle = computed(() => selectedVehicles.value[0] || null)

const totalVehicleSeats = computed(() =>
  selectedVehicles.value.reduce((sum, v) => sum + (Number(v.seat_count) || 0), 0),
)

function isVehicleSelected(id) {
  return form.vehicle_ids.some((x) => String(x) === String(id))
}

function isPrimaryVehicle(id) {
  return form.vehicle_ids.length > 0 && String(form.vehicle_ids[0]) === String(id)
}

function syncPrimaryVehicleFields() {
  const v = primaryVehicle.value
  if (!v) {
    form.vehicle_type = ''
    form.plate_number = ''
    return
  }
  form.vehicle_type = v.type || ''
  form.plate_number = v.license_plate || ''
}

function syncCapacityFromVehicles() {
  const total = totalVehicleSeats.value
  if (total > 0) {
    form.max_capacity = total
    errors.max_capacity = ''
  }
}

function toggleVehicle(id) {
  const idx = form.vehicle_ids.findIndex((x) => String(x) === String(id))
  if (idx >= 0) {
    form.vehicle_ids.splice(idx, 1)
  } else {
    form.vehicle_ids.push(id)
  }
  syncPrimaryVehicleFields()
  syncCapacityFromVehicles()
}

function setAsDefaultVehicle(id) {
  const idx = form.vehicle_ids.findIndex((x) => String(x) === String(id))
  if (idx <= 0) return // not selected, or already the default (first) vehicle
  const [picked] = form.vehicle_ids.splice(idx, 1)
  form.vehicle_ids.unshift(picked)
  syncPrimaryVehicleFields()
}

function vehicleStatusLabel(s) { return VEHICLE_STATUS[s]?.label || '' }
function vehicleStatusBadge(s) { return VEHICLE_STATUS[s]?.badge || 'bg-slate-100 text-slate-500' }

function vehicleOptionLabel(v) {
  const base =
    [v.license_plate, v.type, v.seat_count ? `${v.seat_count} chỗ` : null].filter(Boolean).join(' · ') ||
    `Xe #${v.id}`
  const st = vehicleStatusLabel(v.status)
  return st ? `${base} — ${st}` : base
}

// ── Drivers ───────────────────────────────────────────────────────────────────
const mainDriverId = ref('')

const filteredDrivers = computed(() => {
  const q = driverSearch.value.trim().toLowerCase()
  const list = [...drivers.value]
  if (!q) return list
  return list.filter((d) => {
    const hay = [d.full_name, d.license_class, availabilityLabel(d.availability_status)]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return hay.includes(q)
  })
})

const mainDriver = computed(() => drivers.value.find((d) => String(d.id) === String(mainDriverId.value)) || null)

const selectedDriverCount = computed(
  () => (mainDriverId.value ? 1 : 0) + form.secondary_driver_ids.length,
)

const secondaryDriverNames = computed(() =>
  form.secondary_driver_ids
    .map((id) => drivers.value.find((d) => String(d.id) === String(id))?.full_name)
    .filter(Boolean)
    .join(', ') || 'Không có',
)

function isMainDriver(id) {
  return String(mainDriverId.value) === String(id)
}

function isDriverSelected(id) {
  return isMainDriver(id) || form.secondary_driver_ids.some((x) => String(x) === String(id))
}

function setAsMainDriver(id) {
  if (!isDriverSelected(id)) {
    toggleDriverSelected(id)
    return
  }
  const prevMain = mainDriverId.value
  const secIdx = form.secondary_driver_ids.findIndex((x) => String(x) === String(id))
  if (secIdx >= 0) form.secondary_driver_ids.splice(secIdx, 1)
  if (prevMain && String(prevMain) !== String(id)) {
    if (!form.secondary_driver_ids.some((x) => String(x) === String(prevMain))) {
      form.secondary_driver_ids.push(prevMain)
    }
  }
  mainDriverId.value = id
  errors.driver = ''
}

function toggleDriverSelected(id) {
  if (isMainDriver(id)) {
    mainDriverId.value = ''
    if (form.secondary_driver_ids.length) {
      mainDriverId.value = form.secondary_driver_ids.shift()
    }
  } else {
    const secIdx = form.secondary_driver_ids.findIndex((x) => String(x) === String(id))
    if (secIdx >= 0) {
      form.secondary_driver_ids.splice(secIdx, 1)
    } else if (!mainDriverId.value) {
      mainDriverId.value = id
    } else {
      form.secondary_driver_ids.push(id)
    }
  }
  errors.driver = mainDriverId.value ? '' : errors.driver
}

function availabilityLabel(s) { return { available: 'Rảnh', busy: 'Bận', offline: 'Không có' }[s] || 'Chưa rõ' }
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
    backup_driver_id: form.secondary_driver_ids[0] != null ? Number(form.secondary_driver_ids[0]) : null,
    default_vehicle_id: form.vehicle_ids[0] != null ? Number(form.vehicle_ids[0]) : null,
    responsible_user_id: form.responsible_user_id ? Number(form.responsible_user_id) : null,
    notes: form.notes || null,
    settings: {
      school_year: form.school_year,
      program_type: form.program_type,
      morning: { enabled: form.morning_enabled, departure: form.morning_departure, arrival: form.morning_arrival },
      afternoon: { enabled: form.afternoon_enabled, departure: form.afternoon_departure, arrival: form.afternoon_arrival },
      vehicle: {
        type: form.vehicle_type || null,
        plate_number: form.plate_number || null,
        max_capacity: Number(form.max_capacity || 0),
        vehicle_ids: form.vehicle_ids.map((id) => Number(id)),
      },
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
    const res = await listDrivers({ per_page: 200 })
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
