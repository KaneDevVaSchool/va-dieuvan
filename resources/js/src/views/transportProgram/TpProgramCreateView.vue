<template>
  <div class="w-full">
    <!-- Sticky top bar: breadcrumb + actions -->
    <div class="sticky top-0 z-30 -mx-3 mb-5 border-b border-slate-200 bg-white/90 px-3 py-3 backdrop-blur sm:-mx-4 sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
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
        <div class="flex shrink-0 items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            @click="goBack"
          >
            <XMarkIcon class="h-4 w-4" /> Hủy
          </button>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-[110rem]">
      <!-- Section tabs (anchors) -->
      <div class="sticky top-[57px] z-20 -mx-1 mb-5 overflow-x-auto px-1 pb-1">
        <div class="flex w-max items-center gap-1.5 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
          <button
            v-for="s in sections"
            :key="s.key"
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition',
              activeSection === s.key
                ? 'bg-va-800 text-white shadow-sm'
                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700',
            ]"
            @click="scrollTo(s.key)"
          >
            <component :is="s.icon" class="h-4 w-4" />
            {{ s.label }}
          </button>
        </div>
      </div>

      <!-- Full-width two-column layout -->
      <div class="grid grid-cols-1 gap-5 pb-6 xl:grid-cols-12">
        <!-- LEFT column: identity / route / schedule -->
        <div class="space-y-5 xl:col-span-7">
          <!-- 1. Thông tin cơ bản -->
          <section :ref="setSectionRef('basic')" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white shadow-sm">
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
          </section>

          <!-- 2. Tuyến đường -->
          <section :ref="setSectionRef('route')" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white shadow-sm">
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
              <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-600">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-600">
                  <MapPinIcon class="h-4 w-4" />
                </span>
                <span class="truncate font-medium text-slate-800">{{ form.origin_name || 'Điểm đi' }}</span>
                <ArrowRightIcon class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="truncate font-medium text-slate-800">{{ form.destination_name || 'Điểm đến' }}</span>
              </div>
            </div>
          </section>

          <!-- 3. Lịch trình -->
          <section :ref="setSectionRef('schedule')" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white shadow-sm">
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
          </section>
        </div>

        <!-- RIGHT column: resources / config -->
        <div class="space-y-5 xl:col-span-5">
          <!-- 4. Sức chứa & Xe -->
          <section :ref="setSectionRef('capacity')" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                  Loại xe <span class="text-rose-500">*</span>
                </label>
                <select v-model="form.vehicle_type" :class="selectClass" @change="onVehicleTypeChange">
                  <option value="">— Chọn loại xe —</option>
                  <option v-for="t in vehicleTypeOptions" :key="t.label" :value="t.label">{{ t.label }}</option>
                </select>
                <p class="mt-1 text-xs text-slate-400">Chọn loại xe để tự điền sức chứa tương ứng.</p>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-600">Biển số xe</label>
                  <input
                    v-model.trim="form.plate_number"
                    type="text"
                    placeholder="30A-56789"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-base uppercase outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                  />
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
                      class="w-full rounded-lg border border-slate-200 bg-white py-3 pl-3.5 pr-16 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
                    />
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">chỗ</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-3 rounded-xl border border-teal-200 bg-teal-50/60 px-4 py-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-teal-600 shadow-sm">
                  <TruckIcon class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                  <div class="truncate text-sm font-semibold text-slate-800">
                    {{ form.vehicle_type || 'Chưa chọn xe' }}
                    <span v-if="form.plate_number" class="font-normal text-slate-500">· {{ form.plate_number }}</span>
                  </div>
                  <div class="text-xs text-teal-700">Tối đa {{ form.max_capacity || 0 }} chỗ ngồi</div>
                </div>
              </div>
            </div>
          </section>

          <!-- 5. Tài xế phụ trách -->
          <section :ref="setSectionRef('drivers')" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
              <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600">
                <IdentificationIcon class="h-5 w-5" />
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Tài xế phụ trách</h2>
                <p class="text-sm text-slate-500">Phân công tài xế và người giám sát</p>
              </div>
            </header>
            <div class="space-y-4 p-5">
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

                <!-- Người phụ trách -->
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-600">Người phụ trách / Giám sát</label>
                  <select v-model="form.responsible_user_id" :class="selectClass">
                    <option value="">— Chọn người phụ trách —</option>
                    <option v-for="u in users" :key="u.id" :value="String(u.id)">
                      {{ u.name }}{{ u.department_name ? ' - ' + u.department_name : (u.email ? ' - ' + u.email : '') }}
                    </option>
                  </select>
                </div>
              </template>
            </div>
          </section>

          <!-- 6. Ghi chú -->
          <section :ref="setSectionRef('notes')" class="scroll-mt-32 rounded-2xl border border-slate-200 bg-white shadow-sm">
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
          </section>

          <div class="flex items-start gap-2.5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            <InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-sky-500" />
            <span>Khi lưu, hệ thống sẽ tự sinh các ngày vận hành theo khoảng ngày &amp; lịch chạy đã chọn.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky footer actions (stays within content, never overlaps sidebar) -->
    <div class="sticky bottom-0 z-30 -mx-3 border-t border-slate-200 bg-white/95 px-3 py-3 backdrop-blur sm:-mx-4 sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
      <div class="mx-auto flex max-w-[110rem] items-center justify-between gap-3">
        <p class="hidden text-sm text-slate-500 sm:block">
          {{ form.name || 'Chương trình mới' }}
          <span v-if="form.start_date && form.end_date" class="text-slate-400">· {{ form.start_date }} → {{ form.end_date }}</span>
        </p>
        <div class="flex items-center gap-2">
          <Button variant="secondary" @click="goBack">Hủy</Button>
          <Button :loading="saving" @click="submit">Lưu &amp; sinh lịch</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  XMarkIcon,
  ChevronRightIcon,
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
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import { createProgram } from '../../api/transportProgram'
import { listDrivers } from '../../api/operational'
import { searchUsersForDispatchForm } from '../../api/operational'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const router = useRouter()
const saving = ref(false)
const loadingDrivers = ref(false)
const drivers = ref([])
const users = ref([])

// ── Section anchors / scroll spy ───────────────────────────────────────────────
const sections = [
  { key: 'basic', label: 'Thông tin cơ bản', icon: InformationCircleIcon },
  { key: 'route', label: 'Tuyến đường', icon: MapIcon },
  { key: 'schedule', label: 'Lịch trình', icon: CalendarDaysIcon },
  { key: 'capacity', label: 'Sức chứa', icon: UserGroupIcon },
  { key: 'drivers', label: 'Tài xế', icon: IdentificationIcon },
  { key: 'notes', label: 'Ghi chú', icon: DocumentTextIcon },
]
const sectionEls = {}
const activeSection = ref('basic')
let observer = null

function setSectionRef(key) {
  return (el) => {
    if (el) sectionEls[key] = el
  }
}

function scrollTo(key) {
  sectionEls[key]?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

// ── Shared input classes ───────────────────────────────────────────────────────
const selectClass =
  'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-base text-slate-700 outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'
const textareaClass =
  'w-full resize-y rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'
const timeClass =
  'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'

function switchClass(on) {
  return [
    'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition',
    on ? 'bg-va-800' : 'bg-slate-300',
  ]
}
function switchKnobClass(on) {
  return [
    'inline-block h-5 w-5 transform rounded-full bg-white shadow transition',
    on ? 'translate-x-5' : 'translate-x-0.5',
  ]
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

const vehicleTypeOptions = [
  { label: 'Xe 7 chỗ', seats: 7 },
  { label: 'Xe 16 chỗ', seats: 16 },
  { label: 'Minibus 16 chỗ', seats: 16 },
  { label: 'Xe 29 chỗ', seats: 29 },
  { label: 'Xe 35 chỗ', seats: 35 },
  { label: 'Xe 45 chỗ', seats: 45 },
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
  driver: '',
})

// Auto-fill capacity from chosen vehicle type
function onVehicleTypeChange() {
  const match = vehicleTypeOptions.find((t) => t.label === form.vehicle_type)
  if (match) form.max_capacity = match.seats
}

// ── Drivers ──────────────────────────────────────────────────────────────────
const mainDriverId = ref('')
const changingMain = ref(false)

const mainDriver = computed(() => drivers.value.find((d) => String(d.id) === String(mainDriverId.value)) || null)
const secondaryCandidates = computed(() =>
  drivers.value.filter((d) => String(d.id) !== String(mainDriverId.value)),
)

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

function availabilityLabel(s) {
  return { available: 'Rảnh', busy: 'Bận', offline: 'Không có' }[s] || '—'
}
function availabilityTextClass(s) {
  return { available: 'text-emerald-600', busy: 'text-amber-600', offline: 'text-slate-400' }[s] || 'text-slate-400'
}
function availabilityBadgeClass(s) {
  const base = 'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium '
  return base + ({
    available: 'bg-emerald-50 text-emerald-600',
    busy: 'bg-amber-50 text-amber-600',
    offline: 'bg-rose-50 text-rose-500',
  }[s] || 'bg-slate-100 text-slate-500')
}

// Small avatar render component
const DriverAvatar = (props) => {
  const d = props.driver || {}
  const url = d.user?.avatar_url
  const size = props.small ? 'h-9 w-9' : 'h-11 w-11'
  if (url) {
    return h('img', { src: url, alt: d.full_name, class: `${size} shrink-0 rounded-full object-cover` })
  }
  const initials = (d.full_name || '?')
    .trim()
    .split(/\s+/)
    .slice(-2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
  const tone = props.muted ? 'bg-slate-100 text-slate-400' : 'bg-va-800/10 text-va-800'
  return h('span', { class: `${size} grid shrink-0 place-items-center rounded-full text-xs font-semibold ${tone}` }, initials)
}
DriverAvatar.props = ['driver', 'small', 'muted']

function toggleDay(key) {
  const i = form.runs_on.indexOf(key)
  if (i >= 0) form.runs_on.splice(i, 1)
  else form.runs_on.push(key)
  if (form.runs_on.length) errors.runs_on = ''
}

// ── Navigation ─────────────────────────────────────────────────────────────────
function goBack() {
  router.push({ name: 'tpPrograms' })
}

// ── Validation + submit ──────────────────────────────────────────────────────
function validate() {
  errors.name = form.name ? '' : 'Tên chương trình không được để trống'
  errors.start_date = form.start_date ? '' : 'Chọn ngày bắt đầu'
  errors.end_date = form.end_date ? '' : 'Chọn ngày kết thúc'
  if (form.start_date && form.end_date && form.end_date < form.start_date) {
    errors.end_date = 'Ngày kết thúc phải sau ngày bắt đầu'
  }
  errors.runs_on = form.runs_on.length ? '' : 'Chọn ít nhất một ngày hoạt động'
  errors.trips = form.morning_enabled || form.afternoon_enabled ? '' : 'Bật ít nhất một chiều (sáng hoặc chiều)'
  errors.driver = mainDriverId.value ? '' : 'Chọn tài xế chính'

  const firstBad = ['name', 'start_date', 'end_date', 'runs_on', 'trips', 'driver'].find((k) => errors[k])
  if (firstBad) {
    const sectionByField = {
      name: 'basic',
      start_date: 'schedule',
      end_date: 'schedule',
      runs_on: 'schedule',
      trips: 'schedule',
      driver: 'drivers',
    }
    scrollTo(sectionByField[firstBad])
  }
  return !firstBad
}

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
    responsible_user_id: form.responsible_user_id ? Number(form.responsible_user_id) : null,
    notes: form.notes || null,
    settings: {
      school_year: form.school_year,
      program_type: form.program_type,
      morning: {
        enabled: form.morning_enabled,
        departure: form.morning_departure,
        arrival: form.morning_arrival,
      },
      afternoon: {
        enabled: form.afternoon_enabled,
        departure: form.afternoon_departure,
        arrival: form.afternoon_arrival,
      },
      vehicle: {
        type: form.vehicle_type || null,
        plate_number: form.plate_number || null,
        max_capacity: Number(form.max_capacity || 0),
      },
      secondary_driver_ids: form.secondary_driver_ids.map((id) => Number(id)),
    },
  }
}

async function submit() {
  if (!validate()) return
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

  try {
    users.value = (await searchUsersForDispatchForm('')) ?? []
  } catch {
    users.value = []
  }

  observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((e) => e.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)
      if (visible[0]) {
        const key = Object.keys(sectionEls).find((k) => sectionEls[k] === visible[0].target)
        if (key) activeSection.value = key
      }
    },
    { rootMargin: '-30% 0px -60% 0px', threshold: [0, 0.25, 0.5] },
  )
  Object.values(sectionEls).forEach((el) => observer.observe(el))
})

onBeforeUnmount(() => observer?.disconnect())
</script>
