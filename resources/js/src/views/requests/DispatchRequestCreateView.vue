<template>
  <div
    class="dispatch-wizard space-y-6 text-slate-900"
    :class="
      step === 3 && !created
        ? form.is_urgent
          ? 'rounded-xl pb-28 ring-2 ring-rose-200/90 ring-offset-2 ring-offset-slate-100 sm:pb-24'
          : 'pb-28 sm:pb-24'
        : form.is_urgent
          ? 'rounded-xl pb-10 ring-2 ring-rose-200/90 ring-offset-2 ring-offset-slate-100'
          : 'pb-10'
    "
    :aria-label="
      form.is_urgent && !loading ? t('dispatch_wizard.create.form_priority_frame_aria') : undefined
    "
  >
    <!-- Header -->
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ t('dispatch_wizard.create.title') }}</h1>
        <p class="mt-1 text-sm text-slate-600">
          <span class="font-medium text-va-800">{{ t('dispatch_wizard.create.badge_new') }}</span>
          <span class="text-slate-400"> • </span>
          {{ draftLabel }}
        </p>
        <p v-if="dispatchFormSettingsError && !dispatchFormSettingsLoading" class="mt-1 text-xs text-amber-700">
          {{ dispatchFormSettingsError }}
        </p>
        <p v-if="form.is_urgent" class="mt-2 inline-flex flex-wrap items-center gap-2 text-sm text-rose-700">
          <span class="inline-flex shrink-0" aria-hidden="true" title="">⚠️</span>
          <span class="inline-flex shrink-0 cursor-help items-center gap-1" :title="urgentExplainTooltip">{{
            t('dispatch_wizard.create.urgent')
          }}</span>
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          @click="onCancel"
        >
          {{ t('dispatch_wizard.create.cancel') }}
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
          @click="saveDraft"
        >
          <DocumentArrowDownIcon class="h-4 w-4 text-slate-500" />
          {{ t('dispatch_wizard.create.save_draft') }}
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
          :title="t('dispatch_wizard.create.drafts_tooltip')"
          @click="openDraftsModal"
        >
          <ClipboardDocumentListIcon class="h-4 w-4 text-slate-500" />
          {{ t('dispatch_wizard.create.drafts_title') }}
        </button>
        <button
          v-if="activeDraftId"
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-800"
          :title="t('dispatch_wizard.create.clear_draft_tooltip')"
          @click="openClearDraftModal"
        >
          {{ t('dispatch_wizard.create.clear_draft') }}
        </button>
        <button
          v-if="!(step === 3 && !created)"
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
      :aria-label="t('dispatch_wizard.create.steps_nav_aria')"
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
          <h2 class="text-lg font-semibold text-slate-900">{{ t('dispatch_wizard.create.step1_title') }}</h2>
          <p class="mt-1 text-sm text-slate-600">{{ t('dispatch_wizard.create.step1_hint') }}</p>
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
        <div v-show="step === 1" class="space-y-5 sm:space-y-6">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ t('dispatch_wizard.create.step2_title') }}</h2>
            <p class="mt-1 text-sm leading-relaxed text-slate-600">
              {{ t('dispatch_wizard.create.step2_lead') }}
            </p>
          </div>

          <!-- Người đề nghị + Thời gian: cạnh nhau desktop, xếp dọc mobile -->
          <div class="grid gap-5 lg:grid-cols-2 lg:items-start lg:gap-6">
            <div class="dw-fieldset">
              <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_requester') }}</h3>
              <div class="relative">
                <label class="dw-label">
                  <span>{{ t('dispatch_wizard.create.search_by_name') }} <span class="dw-req" aria-hidden="true">*</span></span>
                  <span
                    class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
                    :title="t('dispatch_wizard.create.search_name_hint')"
                  >
                    <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                  </span>
                </label>
                <input
                  v-model="requesterSearchQ"
                  type="search"
                  autocomplete="off"
                  :placeholder="t('dispatch_wizard.create.search_ph')"
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
                  v-if="requesterDropdownOpen && requesterSearchQ.trim().length >= 2"
                  class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-black/5"
                  role="listbox"
                >
                  <li v-if="requesterSearchLoading" class="px-3 py-2.5 text-slate-500">{{ t('dispatch_wizard.create.searching') }}</li>
                  <template v-else-if="requesterSearchResults.length">
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
                  </template>
                  <li v-else class="px-3 py-2.5 text-slate-500">{{ t('dispatch_wizard.create.no_staff') }}</li>
                </ul>
                <p v-if="requesterSearchError" class="mt-2 text-xs font-medium text-rose-600">{{ requesterSearchError }}</p>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="block min-w-0 sm:col-span-2">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.full_name') }} <span class="dw-req" aria-hidden="true">*</span></span>
                  <input v-model="form.requester_name" type="text" class="dw-input" :placeholder="t('dispatch_wizard.create.full_name_ph')" />
                </label>
                <label class="block min-w-0">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.requester_email_label') }} <span class="dw-req" aria-hidden="true">*</span></span>
                  <input
                    v-model="form.requester_email"
                    type="email"
                    :placeholder="t('dispatch_wizard.create.requester_email_ph')"
                    :class="['dw-input', step2RequesterEmailInvalid ? 'ring-1 ring-rose-300' : '']"
                  />
                </label>
                <label class="block min-w-0">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.phone') }}</span>
                  <input
                    v-model="form.requester_phone"
                    type="text"
                    inputmode="numeric"
                    autocomplete="tel"
                    :placeholder="t('dispatch_wizard.create.phone_ph')"
                    class="dw-input"
                    maxlength="11"
                    @input="onRequesterPhoneInput"
                  />
                </label>
                <p
                  v-if="step2RequesterEmailInvalid"
                  class="text-xs text-rose-600 sm:col-span-2"
                >
                  {{ t('dispatch_wizard.create.email_invalid') }}
                </p>
                <label class="block min-w-0 sm:col-span-2">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.unit') }}</span>
                  <input v-model="form.requester_unit" type="text" :placeholder="t('dispatch_wizard.create.unit_ph')" class="dw-input" />
                </label>
              </div>
            </div>

            <div class="dw-fieldset">
              <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_time') }}</h3>
              <div class="grid gap-3 sm:grid-cols-2">
                <label class="block min-w-0">
                  <span class="dw-label-text" :title="t('dispatch_wizard.create.proposed_date_title')">
                    {{ t('dispatch_wizard.create.proposed_date') }} <span class="dw-req" aria-hidden="true">*</span>
                  </span>
                  <input
                    v-model="form.proposed_date"
                    type="date"
                    lang="vi"
                    class="dw-input dw-date-input mt-1 min-h-[2.75rem]"
                    @click="openDatePickerFromInput($event)"
                  />
                </label>
                <label class="block min-w-0">
                  <span class="dw-label-text" :title="t('dispatch_wizard.create.date_needed_title')">
                    {{ t('dispatch_wizard.create.date_needed') }} <span class="dw-req" aria-hidden="true">*</span>
                  </span>
                  <input
                    v-model="requestedDateTime"
                    type="datetime-local"
                    lang="vi"
                    :class="['dw-input mt-1 min-h-[2.75rem]', step2DateOrderInvalid ? 'ring-1 ring-rose-300' : '']"
                  />
                </label>
              </div>
              <p v-if="step2DateOrderInvalid" class="mt-2 text-xs font-medium text-rose-600">
                {{ t('dispatch_wizard.create.date_order_error') }}
              </p>
              <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/80 p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                  <div class="flex items-center justify-between gap-3 sm:min-w-[7.5rem] sm:flex-col sm:items-stretch sm:justify-start sm:pb-0.5">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold text-slate-900">{{ t('dispatch_wizard.create.urgent') }}</span>
                      <span
                        class="inline-flex cursor-help text-slate-500 hover:text-slate-700"
                        tabindex="0"
                        role="tooltip"
                        :title="urgentExplainTooltip"
                        aria-label="⚠️"
                      >
                        <span aria-hidden="true">⚠️</span>
                      </span>
                      <span
                        class="hidden cursor-help text-slate-400 hover:text-slate-600 sm:inline-flex"
                        :title="t('dispatch_wizard.create.urgent_title')"
                      >
                        <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                      </span>
                    </div>
                    <button
                      type="button"
                      role="switch"
                      :aria-checked="form.is_urgent"
                      :disabled="urgentAutoActive || loading || dispatchFormSettingsLoading"
                      class="inline-flex h-8 w-14 shrink-0 cursor-pointer items-center rounded-full px-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-va-800/30 disabled:cursor-not-allowed disabled:opacity-60"
                      :class="form.is_urgent ? 'justify-end bg-va-800' : 'justify-start bg-slate-300'"
                      @click="toggleUrgentManual"
                    >
                      <span class="pointer-events-none h-7 w-7 rounded-full bg-white shadow-sm ring-1 ring-black/5" />
                    </button>
                  </div>
                  <div class="min-w-0 flex-1">
                    <label class="block">
                      <span class="dw-label-text">{{ t('dispatch_wizard.create.reason') }} <span v-if="form.is_urgent" class="dw-req" aria-hidden="true">*</span></span>
                      <textarea
                        v-model="form.urgent_reason"
                        rows="3"
                        :placeholder="t('dispatch_wizard.create.reason_ph')"
                        :disabled="!form.is_urgent"
                        class="dw-input mt-1 min-h-[4.75rem] resize-y disabled:cursor-not-allowed disabled:opacity-45"
                      />
                    </label>
                  </div>
                </div>
                <p v-if="urgentAutoActive" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium leading-relaxed text-rose-800 ring-1 ring-rose-100">
                  {{
                    t('dispatch_wizard.create.urgent_auto_banner', {
                      hours: appliedUrgentThresholdHours,
                    })
                  }}
                </p>
              </div>
            </div>
          </div>

          <div class="dw-fieldset">
            <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_purpose') }}</h3>
            <div
              v-if="form.trip_type === 'point_to_point'"
              class="mb-4 flex flex-wrap gap-2 sm:gap-3"
              role="radiogroup"
              :aria-label="t('dispatch_wizard.create.purpose_tab_aria')"
            >
              <label
                class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-800 shadow-sm transition hover:border-va-800/25 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50 has-[:checked]:ring-1 has-[:checked]:ring-sky-200"
              >
                <input
                  v-model="form.point_purpose_kind"
                  type="radio"
                  value="point_to_point"
                  class="h-4 w-4 border-slate-300 text-va-800 focus:ring-va-800"
                />
                <span>{{ t('dispatch_wizard.create.purpose_point') }}</span>
              </label>
              <label
                class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-800 shadow-sm transition hover:border-va-800/25 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50 has-[:checked]:ring-1 has-[:checked]:ring-sky-200"
              >
                <input
                  v-model="form.point_purpose_kind"
                  type="radio"
                  value="extracurricular"
                  class="h-4 w-4 border-slate-300 text-va-800 focus:ring-va-800"
                />
                <span>{{ t('dispatch_wizard.create.purpose_extra') }}</span>
              </label>
            </div>
            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.purpose_label') }} <span class="dw-req" aria-hidden="true">*</span></span>
              <textarea
                v-model="form.purpose"
                rows="4"
                class="dw-input min-h-[5.25rem] resize-y sm:min-h-[6rem]"
                :placeholder="t('dispatch_wizard.create.purpose_ph')"
              />
            </label>
            <div class="mt-4">
              <span class="mb-2 flex flex-nowrap items-center gap-1.5">
                <span class="text-xs font-medium text-slate-700">{{ t('dispatch_wizard.create.basis_label') }}</span>
                <span
                  class="inline-flex shrink-0 cursor-help text-slate-400 hover:text-slate-600"
                  :title="t('dispatch_wizard.create.basis_title')"
                >
                  <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                </span>
              </span>
              <div
                class="mt-2 flex min-h-[7.5rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 transition sm:min-h-[8rem] sm:py-8"
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
                <CloudArrowUpIcon class="h-9 w-9 text-slate-400 sm:h-10 sm:w-10" aria-hidden="true" />
                <p class="mt-2 text-center text-sm font-medium text-slate-800">{{ t('dispatch_wizard.create.basis_drop') }}</p>
                <p class="mt-1 text-center text-xs text-slate-500">{{ t('dispatch_wizard.create.basis_types') }}</p>
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
                  {{ t('dispatch_wizard.create.remove_file') }}
                </button>
              </div>
            </div>
          </div>

          <div class="grid gap-5 lg:grid-cols-2 lg:items-start lg:gap-6">
            <div class="dw-fieldset">
              <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_targets') }}</h3>
              <div class="max-h-52 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/80 p-2 sm:max-h-48">
                <label
                  v-for="target in targetOptions"
                  :key="target"
                  class="flex min-h-[40px] cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm hover:bg-white sm:py-1.5"
                >
                  <input v-model="form.targets" type="checkbox" :value="target" class="h-4 w-4 shrink-0 rounded border-slate-300 text-va-800" />
                  <span class="text-slate-800">{{ target }}</span>
                </label>
              </div>
              <p v-if="form.targets.length" class="mt-2 text-xs text-slate-500">{{ t('dispatch_wizard.create.targets_selected', { n: form.targets.length }) }}</p>
              <p
                v-else-if="form.trip_type === 'point_to_point'"
                class="mt-2 text-xs leading-relaxed text-amber-800/90"
              >
                {{ t('dispatch_wizard.create.targets_warn') }}
              </p>
            </div>

            <div class="dw-fieldset">
              <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_coordinator') }}</h3>
              <div class="relative">
                <label class="dw-label">
                  <span>{{ t('dispatch_wizard.create.coord_search_label') }}</span>
                  <span
                    class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
                    :title="t('dispatch_wizard.create.coord_search_title')"
                  >
                    <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                  </span>
                </label>
                <input
                  v-model="coordinatorSearchQ"
                  type="search"
                  autocomplete="off"
                  :placeholder="t('dispatch_wizard.create.coord_search_ph')"
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
                  v-if="coordinatorDropdownOpen && coordinatorSearchQ.trim().length >= 2"
                  class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-black/5"
                  role="listbox"
                >
                  <li v-if="coordinatorSearchLoading" class="px-3 py-2.5 text-slate-500">{{ t('dispatch_wizard.create.searching') }}</li>
                  <template v-else-if="coordinatorSearchResults.length">
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
                  </template>
                  <li v-else class="px-3 py-2.5 text-slate-500">{{ t('dispatch_wizard.create.no_staff') }}</li>
                </ul>
                <p v-if="coordinatorSearchError" class="mt-2 text-xs font-medium text-rose-600">{{ coordinatorSearchError }}</p>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="block min-w-0 sm:col-span-2">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.full_name') }}</span>
                  <input
                    v-model="form.coordinator_name"
                    type="text"
                    class="dw-input"
                    :placeholder="t('dispatch_wizard.create.coord_name_ph')"
                  />
                </label>
                <label class="block min-w-0">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.coord_email_label') }}</span>
                  <input
                    v-model="form.coordinator_email"
                    type="email"
                    :class="['dw-input', step2CoordinatorEmailInvalid ? 'ring-1 ring-rose-300' : '']"
                    :placeholder="t('dispatch_wizard.create.coord_email_ph')"
                  />
                </label>
                <label class="block min-w-0">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.coord_phone') }}</span>
                  <input
                    v-model="form.coordinator_phone"
                    type="text"
                    inputmode="numeric"
                    autocomplete="tel"
                    :placeholder="t('dispatch_wizard.create.coord_phone_ph')"
                    class="dw-input"
                    maxlength="11"
                    @input="onCoordinatorPhoneInput"
                  />
                </label>
                <p v-if="step2CoordinatorEmailInvalid" class="text-xs text-rose-600 sm:col-span-2">
                  {{ t('dispatch_wizard.create.coord_email_invalid') }}
                </p>
              </div>
            </div>
          </div>

          <div
            class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:py-3.5"
          >
            <div class="min-w-0">
              <label for="dw-source-channel" class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ t('dispatch_wizard.create.channel_label') }}</label>
              <p class="mt-0.5 text-xs text-slate-500 sm:hidden">{{ t('dispatch_wizard.create.channel_hint') }}</p>
            </div>
            <select
              id="dw-source-channel"
              v-model="form.source_channel"
              class="dw-input max-w-full bg-white sm:max-w-xs lg:max-w-sm"
            >
              <option value="portal">{{ t('dispatch_wizard.create.option_portal') }}</option>
              <option value="zalo">{{ t('dispatch_wizard.create.option_zalo') }}</option>
              <option value="paper">{{ t('dispatch_wizard.create.option_paper') }}</option>
            </select>
          </div>
        </div>

        <!-- Step 3 — lazy chunk + chỉ mount khi step === 2 -->
        <DispatchWizardStep3 v-if="step === 2" />

        <!-- Step 4 -->
        <ConfirmSummary v-if="step === 3" />

        <!-- Nav buttons -->
        <div
          v-if="step !== 3"
          class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-6"
        >
          <button
            type="button"
            class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-40"
            :disabled="step === 0"
            @click="step--"
          >
            {{ t('dispatch_wizard.create.back') }}
          </button>
          <div class="flex gap-2">
            <button
              v-if="step < 3"
              type="button"
              class="rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:opacity-40"
              :disabled="!canGoNext"
              @click="nextStep"
            >
              {{ t('dispatch_wizard.create.next') }}
            </button>
          </div>
        </div>
      </section>
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
                  {{ t('dispatch_wizard.create.clear_modal_title') }}
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                  {{ t('dispatch_wizard.create.clear_modal_body') }}
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
              {{ t('dispatch_wizard.create.cancel') }}
            </button>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-b from-rose-600 to-rose-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-rose-900/20 transition hover:from-rose-500 hover:to-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 sm:w-auto"
              @click="confirmClearDraft"
            >
              {{ t('dispatch_wizard.create.clear_modal_delete') }}
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
                  {{ t('dispatch_wizard.create.library_title') }}
                </h3>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">
                  {{ t('dispatch_wizard.create.library_hint') }}
                </p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
                @click="startNewDraftSession"
              >
                {{ t('dispatch_wizard.create.new_form') }}
              </button>
            </div>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:px-4">
            <p v-if="!savedDraftsList.length" class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-4 py-8 text-center text-sm text-slate-600">
              {{ t('dispatch_wizard.create.library_empty', { action: t('dispatch_wizard.create.save_draft') }) }}
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
                        {{ t('dispatch_wizard.create.library_active') }}
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
                      {{ t('dispatch_wizard.create.library_open') }}
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                      @click="deleteDraftById(d.id)"
                    >
                      {{ t('dispatch_wizard.create.library_delete') }}
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
              {{ t('dispatch_wizard.create.library_close') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, defineAsyncComponent, provide } from 'vue'
import { useI18n } from 'vue-i18n'
import {
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
import ConfirmSummary from './dispatch-wizard/ConfirmSummary.vue'

const { t, locale } = useI18n()
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
  requesterSearchError,
  coordinatorSearchQ,
  coordinatorSearchResults,
  coordinatorSearchLoading,
  coordinatorDropdownOpen,
  coordinatorSearchError,
  step2DateOrderInvalid,
  step2RequesterEmailInvalid,
  step2CoordinatorEmailInvalid,
  requestedDateTime,
  tripTypeOptions,
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
  dispatchFormSettingsLoading,
  dispatchFormSettingsError,
  urgentAutoActive,
  toggleUrgentManual,
  appliedUrgentThresholdHours,
  canGoNext,
  goStep,
  nextStep,
  headerPrimaryLabel,
  headerPrimaryDisabled,
  primaryAction,
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

const urgentExplainTooltip = computed(() =>
  t('dispatch_wizard.create.urgent_explain_tooltip', {
    hours: appliedUrgentThresholdHours.value ?? '—',
  }),
)

function formatDraftTime(ts) {
  try {
    const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
    return new Date(ts).toLocaleString(loc)
  } catch {
    return '—'
  }
}
</script>

<style src="./dispatch-wizard/dispatchWizard.styles.css"></style>
