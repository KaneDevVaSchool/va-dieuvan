<template>
  <div
    class="dispatch-wizard portal-create w-full max-w-none space-y-5 px-4 py-5 text-slate-900 supports-[padding:max(0px)]:pl-[max(1rem,env(safe-area-inset-left))] supports-[padding:max(0px)]:pr-[max(1rem,env(safe-area-inset-right))] sm:px-6 sm:py-6 lg:py-7"
    :class="step === 3 && !created ? 'pb-28 sm:pb-24' : 'pb-10'"
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
      <div class="flex flex-col items-stretch gap-2 sm:items-end">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm outline-none transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:outline-none"
            @click="onCancel"
          >
            {{ t('dispatch_wizard.create.cancel') }}
          </button>

          <!-- Draft dropdown -->
          <div ref="draftMenuEl" class="relative">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm outline-none transition hover:bg-slate-50 focus:outline-none focus-visible:outline-none"
              :class="{ 'border-slate-300 bg-slate-50': draftMenuOpen }"
              @click.stop="draftMenuOpen = !draftMenuOpen"
            >
              <DocumentArrowDownIcon class="h-4 w-4 text-slate-500" />
              {{ t('dispatch_wizard.create.draft_menu_label') }}
              <ChevronDownIcon
                class="h-3.5 w-3.5 text-slate-400 transition-transform duration-150"
                :class="{ 'rotate-180': draftMenuOpen }"
              />
            </button>

            <span
              v-if="draftSaveFlash"
              role="status"
              class="absolute -bottom-5 left-0 whitespace-nowrap text-xs font-semibold text-emerald-700"
            >
              {{ t('dispatch_wizard.create.draft_saved_flash') }}
            </span>

            <div
              v-show="draftMenuOpen"
              class="absolute right-0 top-full z-50 mt-1.5 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5"
              role="menu"
            >
              <button
                type="button"
                role="menuitem"
                class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50"
                @click="draftMenuAction(saveDraft)"
              >
                <DocumentArrowDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
                {{ t('dispatch_wizard.create.save_draft') }}
              </button>
              <button
                type="button"
                role="menuitem"
                class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50"
                @click="draftMenuAction(openDraftsModal)"
              >
                <ClipboardDocumentListIcon class="h-4 w-4 shrink-0 text-slate-400" />
                {{ t('dispatch_wizard.create.drafts_title') }}
              </button>
              <template v-if="activeDraftId">
                <div class="my-1 border-t border-slate-100" />
                <button
                  type="button"
                  role="menuitem"
                  class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm text-rose-600 transition hover:bg-rose-50"
                  @click="draftMenuAction(openClearDraftModal)"
                >
                  <TrashIcon class="h-4 w-4 shrink-0 text-rose-400" />
                  {{ t('dispatch_wizard.create.clear_draft') }}
                </button>
              </template>
            </div>
          </div>

          <button
            v-if="!(step === 3 && !created)"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm outline-none transition hover:bg-va-900 focus:outline-none focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="headerPrimaryDisabled"
            @click="primaryAction"
          >
            <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
            {{ headerPrimaryLabel }}
            <ArrowRightIcon v-if="!loading" class="h-4 w-4" />
          </button>
        </div>
        <p v-if="draftSaveError" class="text-xs font-medium text-rose-600 sm:text-right">{{ draftSaveError }}</p>
      </div>
    </header>

    <PortalStepper
      :steps="stepperSteps"
      :current="step"
      :steps-nav-label="t('dispatch_wizard.create.steps_nav_aria')"
      interactive
      :max-reached-step="maxReachedStep"
      @select="goStepFromStepper"
    />

    <div>
      <!-- Main card -->
      <section class="dw-portal-surface">
        <!-- Step 1 -->
        <div v-show="step === 0">
          <PortalTripTypeGrid
            :trip-types="TRIP_TYPES"
            :trip-type="form.trip_type"
            :hint="t('dispatch_wizard.create.step1_title')"
            :double-tap-hint="t('portal.double_tap_hint')"
            @select="onTripTypeClick"
          />
        </div>

        <!-- Step 2 -->
        <div v-show="step === 1" class="dw-portal-step2">
          <p
            v-if="replaceDraftRequestId"
            class="rounded-xl border border-amber-200/90 bg-amber-50/90 px-3 py-2 text-xs font-medium text-amber-950"
          >
            {{ t('dispatch_wizard.create.replace_banner') }}
          </p>
          <div class="dw-portal-step-head">
            <h2>{{ t('dispatch_wizard.create.step2_title') }}</h2>
            <p class="dw-portal-step-lead">{{ t('dispatch_wizard.create.step2_lead') }}</p>
          </div>

          <div
            class="dw-portal-subtabs"
            role="tablist"
            :aria-label="t('dispatch_wizard.create.step2_sub_nav_aria')"
          >
            <button
              v-for="(tab, idx) in step2SubTabs"
              :key="tab.id"
              type="button"
              role="tab"
              class="dw-portal-subtab"
              :aria-selected="step2Sub === tab.id"
              :aria-controls="`staff-step2-panel-${tab.id}`"
              :tabindex="step2Sub === tab.id ? 0 : -1"
              :disabled="idx > maxReachedStep2Sub"
              @click="setStep2Sub(tab.id)"
            >
              <span class="dw-portal-subtab__num" aria-hidden="true">{{ idx + 1 }}</span>
              <span class="dw-portal-subtab__label">{{ tab.label }}</span>
            </button>
          </div>

          <!-- Panel: Người đề nghị -->
          <div
            v-show="step2Sub === 'requester'"
            id="staff-step2-panel-requester"
            role="tabpanel"
            class="dw-portal-panel dw-form-stack"
          >
            <p class="dw-portal-panel-title">{{ t('dispatch_wizard.create.sec_requester') }}</p>

            <div class="relative">
              <label class="dw-label">
                <span>{{ t('dispatch_wizard.create.search_by_name') }}</span>
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
                role="combobox"
                :aria-expanded="requesterDropdownOpen && requesterSearchQ.trim().length >= 2"
                aria-controls="dw-requester-search-list"
                :placeholder="t('dispatch_wizard.create.search_ph')"
                class="dw-input"
                @input="scheduleRequesterSearch"
                @focus="onRequesterSearchFocus"
                @blur="onRequesterSearchBlur"
              />
              <div
                v-if="requesterSearchLoading"
                class="absolute right-3 top-[2.625rem] h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-va-800"
              />
              <ul
                v-if="requesterDropdownOpen && requesterSearchQ.trim().length >= 2"
                id="dw-requester-search-list"
                class="dw-combobox__menu"
                role="listbox"
              >
                <li v-if="requesterSearchLoading" class="dw-combobox__empty">{{ t('dispatch_wizard.create.searching') }}</li>
                <template v-else-if="requesterSearchResults.length">
                  <li v-for="u in requesterSearchResults" :key="u.id">
                    <button
                      type="button"
                      class="dw-combobox__option"
                      @mousedown.prevent="pickRequester(u)"
                    >
                      <span class="font-medium text-slate-900">{{ u.name }}</span>
                      <span class="truncate text-xs text-slate-500">{{ u.email }}</span>
                    </button>
                  </li>
                </template>
                <li v-else class="dw-combobox__empty">{{ t('dispatch_wizard.create.no_staff') }}</li>
              </ul>
              <p v-if="requesterSearchError" class="dw-field-error">{{ requesterSearchError }}</p>
            </div>

            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.full_name') }} <span class="dw-req" aria-hidden="true">*</span></span>
              <input v-model="form.requester_name" type="text" class="dw-input mt-1" :placeholder="t('dispatch_wizard.create.full_name_ph')" />
            </label>

            <div class="dw-form-grid-2">
              <div>
                <label class="block">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.requester_email_label') }} <span class="dw-req" aria-hidden="true">*</span></span>
                  <input
                    v-model="form.requester_email"
                    type="email"
                    :placeholder="t('dispatch_wizard.create.requester_email_ph')"
                    :class="['dw-input mt-1', step2RequesterEmailInvalid ? 'dw-input--invalid' : '']"
                    @blur="onRequesterEmailBlur"
                  />
                </label>
                <p v-if="step2RequesterEmailInvalid" class="dw-field-error">
                  {{ t('dispatch_wizard.create.email_invalid') }}
                </p>
              </div>
              <label class="block">
                <span class="dw-label-text">{{ t('dispatch_wizard.create.phone') }}</span>
                <input
                  v-model="form.requester_phone"
                  type="text"
                  inputmode="numeric"
                  autocomplete="tel"
                  :placeholder="t('dispatch_wizard.create.phone_ph')"
                  class="dw-input mt-1"
                  maxlength="11"
                  @input="onRequesterPhoneInput"
                />
              </label>
            </div>

            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.unit') }}</span>
              <input v-model="form.requester_unit" type="text" :placeholder="t('dispatch_wizard.create.unit_ph')" class="dw-input mt-1" />
            </label>
          </div>

          <!-- Panel: Thời gian -->
          <div
            v-show="step2Sub === 'time'"
            id="staff-step2-panel-time"
            role="tabpanel"
            class="dw-portal-panel dw-form-stack"
          >
            <p class="dw-portal-panel-title">{{ t('dispatch_wizard.create.sec_time') }}</p>

            <div class="dw-form-grid-2">
              <label class="block">
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
              <label class="block">
                <span class="dw-label-text" :title="t('dispatch_wizard.create.date_needed_title')">
                  {{ t('dispatch_wizard.create.date_needed') }} <span class="dw-req" aria-hidden="true">*</span>
                </span>
                <input
                  v-model="requestedDateTime"
                  type="datetime-local"
                  lang="vi"
                  :class="['dw-input mt-1 min-h-[2.75rem]', step2DateOrderInvalid ? 'dw-input--invalid' : '']"
                />
              </label>
            </div>
            <p v-if="step2DateOrderInvalid" class="dw-field-error">
              {{ t('dispatch_wizard.create.date_order_error') }}
            </p>

            <div
              class="dw-portal-card-muted"
              :class="form.is_urgent ? 'dw-portal-card-muted--urgent' : ''"
            >
              <div class="flex items-center justify-between gap-3">
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
                    class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
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
                  class="inline-flex h-8 w-14 shrink-0 cursor-pointer items-center rounded-full px-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-va-700/30 disabled:cursor-not-allowed disabled:opacity-60"
                  :class="form.is_urgent ? 'justify-end bg-va-800' : 'justify-start bg-slate-300'"
                  @click="toggleUrgentManual"
                >
                  <span class="pointer-events-none h-7 w-7 rounded-full bg-white shadow-sm ring-1 ring-black/5" />
                </button>
              </div>

              <div v-show="form.is_urgent" class="mt-3">
                <label class="block">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.reason') }} <span class="dw-req" aria-hidden="true">*</span></span>
                  <textarea
                    v-model="form.urgent_reason"
                    rows="3"
                    :placeholder="t('dispatch_wizard.create.reason_ph')"
                    class="dw-input mt-1 min-h-[4.75rem] resize-y"
                  />
                </label>
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

          <!-- Panel: Mục đích -->
          <div
            v-show="step2Sub === 'purpose'"
            id="staff-step2-panel-purpose"
            role="tabpanel"
            class="dw-portal-panel dw-form-stack"
          >
            <p class="dw-portal-panel-title">{{ t('dispatch_wizard.create.sec_purpose') }}</p>

            <div
              v-if="form.trip_type === 'point_to_point'"
              class="dw-portal-segment"
              role="radiogroup"
              :aria-label="t('dispatch_wizard.create.purpose_tab_aria')"
            >
              <label class="dw-portal-segment__opt">
                <input
                  v-model="form.point_purpose_kind"
                  type="radio"
                  value="point_to_point"
                  class="h-4 w-4 shrink-0 border-slate-300 text-va-800 focus:ring-va-800"
                />
                <span>{{ t('dispatch_wizard.create.purpose_point') }}</span>
              </label>
              <label class="dw-portal-segment__opt">
                <input
                  v-model="form.point_purpose_kind"
                  type="radio"
                  value="extracurricular"
                  class="h-4 w-4 shrink-0 border-slate-300 text-va-800 focus:ring-va-800"
                />
                <span>{{ t('dispatch_wizard.create.purpose_extra') }}</span>
              </label>
            </div>

            <RecurringConfigSection
              :trip-type="form.trip_type"
              :point-purpose-kind="form.point_purpose_kind"
              :replace-draft-request-id="replaceDraftRequestId"
              v-model:recurring-enabled="form.recurring_enabled"
              v-model:start-date="form.recurrence_start_date"
              v-model:depart-time="form.recurrence_depart_time"
              v-model:return-time="form.recurrence_return_time"
              v-model:recurrence-end-date="form.recurrence_end_date"
              v-model:recurrence-end-mode="form.recurrence_end_mode"
              v-model:repeat-count="form.recurrence_repeat_count"
              :weekday-options="e1WeekdayOptions"
              :weekdays="form.e1_weekdays"
              end-mode-radio-name="staff_recurrence_end_mode"
              @toggle-weekday="toggleE1Weekday"
              @go-schedule-step="goStep(2)"
            />

            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.purpose_label') }} <span class="dw-req" aria-hidden="true">*</span></span>
              <textarea
                v-model="form.purpose"
                rows="3"
                class="dw-input mt-1 min-h-[4.5rem] resize-y"
                :placeholder="t('dispatch_wizard.create.purpose_ph')"
              />
            </label>

            <div>
              <span class="mb-1.5 flex flex-nowrap items-center gap-1.5">
                <span class="dw-label-text mb-0">{{ t('dispatch_wizard.create.basis_label') }}</span>
                <span
                  class="inline-flex shrink-0 cursor-help text-slate-400 hover:text-slate-600"
                  :title="t('dispatch_wizard.create.basis_title')"
                >
                  <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
                </span>
              </span>
              <div
                class="dw-portal-dropzone"
                :class="basisDragOver ? 'dw-portal-dropzone--active' : ''"
                @dragover.prevent="basisDragOver = true"
                @dragleave.prevent="basisDragOver = false"
                @drop.prevent="onBasisDrop"
                @click="basisFileInput?.click()"
              >
                <CloudArrowUpIcon class="h-8 w-8 text-slate-400" aria-hidden="true" />
                <p class="mt-2 text-center text-sm font-medium text-slate-800">{{ t('dispatch_wizard.create.basis_drop') }}</p>
                <p class="mt-0.5 text-center text-xs text-slate-500">{{ t('dispatch_wizard.create.basis_types') }}</p>
                <input
                  ref="basisFileInput"
                  type="file"
                  class="sr-only"
                  accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"
                  @change="onBasisFileChange"
                />
              </div>
              <p v-if="basisFileError" class="dw-field-error">{{ basisFileError }}</p>
              <div v-if="basisFile" class="dw-portal-file-row">
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

            <!-- Kênh tiếp nhận (chỉ dành cho nhân viên) -->
            <div>
              <label for="dw-source-channel" class="dw-label-text">{{ t('dispatch_wizard.create.channel_label') }}</label>
              <select
                id="dw-source-channel"
                v-model="form.source_channel"
                class="dw-input mt-1 bg-white"
              >
                <option value="portal">{{ t('dispatch_wizard.create.option_portal') }}</option>
                <option value="zalo">{{ t('dispatch_wizard.create.option_zalo') }}</option>
                <option value="paper">{{ t('dispatch_wizard.create.option_paper') }}</option>
              </select>
            </div>
          </div>

          <!-- Panel: Phối hợp -->
          <div
            v-show="step2Sub === 'coordination'"
            id="staff-step2-panel-coordination"
            role="tabpanel"
            class="dw-portal-panel-stack"
          >
            <!-- Đối tượng phân bổ -->
            <div class="dw-portal-panel dw-form-stack">
              <p class="dw-portal-panel-title">{{ t('dispatch_wizard.create.sec_targets') }}</p>

              <div v-if="form.targets.length" class="flex flex-wrap gap-1.5">
                <span
                  v-for="chip in form.targets"
                  :key="chip"
                  class="dw-portal-chip"
                >
                  {{ chip }}
                </span>
              </div>

              <div class="relative min-w-0">
                <input
                  type="search"
                  readonly
                  autocomplete="off"
                  role="combobox"
                  :aria-expanded="targetPickerModalOpen"
                  aria-controls="dw-staff-target-picker-modal"
                  :placeholder="t('dispatch_wizard.create.targets_search_ph')"
                  class="dw-input cursor-pointer"
                  data-testid="staff-targets-search"
                  @focus="openTargetPickerModal"
                  @click="openTargetPickerModal"
                  @keydown.enter.prevent="openTargetPickerModal"
                />
              </div>

              <p v-if="form.targets.length" class="dw-field-hint">
                {{ t('dispatch_wizard.create.targets_selected', { n: form.targets.length }) }}
              </p>
              <p
                v-else-if="form.trip_type === 'point_to_point'"
                class="dw-portal-callout-warn"
              >
                {{ t('dispatch_wizard.create.targets_warn') }}
              </p>
            </div>

            <!-- Người phối hợp -->
            <div class="dw-portal-panel dw-form-stack">
              <p class="dw-portal-panel-title">{{ t('dispatch_wizard.create.sec_coordinator') }}</p>

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
                  role="combobox"
                  :aria-expanded="coordinatorDropdownOpen && coordinatorSearchQ.trim().length >= 2"
                  aria-controls="dw-coordinator-search-list"
                  :placeholder="t('dispatch_wizard.create.coord_search_ph')"
                  class="dw-input"
                  @input="scheduleCoordinatorSearch"
                  @focus="onCoordinatorSearchFocus"
                  @blur="onCoordinatorSearchBlur"
                />
                <div
                  v-if="coordinatorSearchLoading"
                  class="absolute right-3 top-[2.625rem] h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-va-800"
                />
                <ul
                  v-if="coordinatorDropdownOpen && coordinatorSearchQ.trim().length >= 2"
                  id="dw-coordinator-search-list"
                  class="dw-combobox__menu"
                  role="listbox"
                >
                  <li v-if="coordinatorSearchLoading" class="dw-combobox__empty">{{ t('dispatch_wizard.create.searching') }}</li>
                  <template v-else-if="coordinatorSearchResults.length">
                    <li v-for="u in coordinatorSearchResults" :key="u.id">
                      <button
                        type="button"
                        class="dw-combobox__option"
                        @mousedown.prevent="pickCoordinator(u)"
                      >
                        <span class="font-medium text-slate-900">{{ u.name }}</span>
                        <span class="truncate text-xs text-slate-500">{{ u.email }}</span>
                      </button>
                    </li>
                  </template>
                  <li v-else class="dw-combobox__empty">{{ t('dispatch_wizard.create.no_staff') }}</li>
                </ul>
                <p v-if="coordinatorSearchError" class="dw-field-error">{{ coordinatorSearchError }}</p>
              </div>

              <label class="block">
                <span class="dw-label-text">{{ t('dispatch_wizard.create.full_name') }}</span>
                <input
                  v-model="form.coordinator_name"
                  type="text"
                  class="dw-input mt-1"
                  :placeholder="t('dispatch_wizard.create.coord_name_ph')"
                />
              </label>

              <div class="dw-form-grid-2">
                <div>
                  <label class="block">
                    <span class="dw-label-text">{{ t('dispatch_wizard.create.coord_email_label') }}</span>
                    <input
                      v-model="form.coordinator_email"
                      type="email"
                      :class="['dw-input mt-1', step2CoordinatorEmailInvalid ? 'dw-input--invalid' : '']"
                      :placeholder="t('dispatch_wizard.create.coord_email_ph')"
                      @blur="onCoordinatorEmailBlur"
                    />
                  </label>
                  <p v-if="step2CoordinatorEmailInvalid" class="dw-field-error">
                    {{ t('dispatch_wizard.create.coord_email_invalid') }}
                  </p>
                </div>
                <label class="block">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.coord_phone') }}</span>
                  <input
                    v-model="form.coordinator_phone"
                    type="text"
                    inputmode="numeric"
                    autocomplete="tel"
                    :placeholder="t('dispatch_wizard.create.coord_phone_ph')"
                    class="dw-input mt-1"
                    maxlength="11"
                    @input="onCoordinatorPhoneInput"
                  />
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3 — lazy chunk, chỉ mount khi step === 2 -->
        <DispatchWizardStep3 v-if="step === 2" />

        <!-- Step 4 -->
        <ConfirmSummary v-if="step === 3" />

        <!-- Nav buttons -->
        <div v-if="step !== 3" class="dw-portal-footer">
          <button
            type="button"
            class="dw-portal-btn-ghost"
            :disabled="step === 0"
            @click="portalPrevStep"
          >
            {{ t('dispatch_wizard.create.back') }}
          </button>
          <button
            v-if="step < 3"
            type="button"
            class="dw-portal-btn-primary"
            :disabled="!portalCanGoNext"
            @click="portalNextStep"
          >
            {{ t('dispatch_wizard.create.next') }}
          </button>
        </div>
      </section>
    </div>
  </div>

  <!-- Modal: Xoá nháp -->
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

  <!-- Modal: Kết quả gửi -->
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
        v-if="submitResultModalOpen"
        class="fixed inset-0 z-[202] flex items-center justify-center bg-slate-900/45 p-4 backdrop-blur-[3px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="submit-result-modal-title"
        @click.self="closeSubmitResultModal"
      >
        <div
          class="w-full max-w-[420px] overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5"
          @click.stop
        >
          <div
            class="border-b border-slate-100 px-5 pb-4 pt-5"
            :class="
              submitResultOk
                ? 'bg-gradient-to-br from-emerald-50 via-white to-slate-50/80'
                : 'bg-gradient-to-br from-rose-50 via-white to-slate-50/80'
            "
          >
            <div class="flex gap-4">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-inner"
                :class="
                  submitResultOk
                    ? 'bg-gradient-to-br from-emerald-100 to-emerald-50 text-emerald-700 shadow-emerald-900/5'
                    : 'bg-gradient-to-br from-rose-100 to-rose-50 text-rose-700 shadow-rose-900/5'
                "
              >
                <CheckCircleIcon v-if="submitResultOk" class="h-6 w-6" aria-hidden="true" />
                <XCircleIcon v-else class="h-6 w-6" aria-hidden="true" />
              </div>
              <div class="min-w-0 pt-0.5">
                <h3 id="submit-result-modal-title" class="text-base font-semibold leading-snug text-slate-900">
                  {{
                    submitResultOk
                      ? t('dispatch_wizard.confirm.submit_modal_success_title')
                      : t('dispatch_wizard.confirm.submit_modal_fail_title')
                  }}
                </h3>
                <p v-if="submitResultOk" class="mt-2 text-sm leading-relaxed text-slate-600">
                  {{ t('dispatch_wizard.confirm.submit_modal_success_body', { id: created?.id ?? '—' }) }}
                </p>
                <p v-else class="mt-2 text-sm leading-relaxed text-slate-600">
                  {{ submitResultDetail || t('dispatch_wizard.confirm.submit_modal_fail_body') }}
                </p>
                <p
                  v-if="submitResultOk && submitResultDetail"
                  class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium leading-relaxed text-amber-950 ring-1 ring-amber-100"
                >
                  {{ submitResultDetail }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex flex-col gap-2 bg-slate-50/90 px-4 py-4 sm:flex-row sm:flex-wrap sm:justify-end sm:gap-3">
            <template v-if="submitResultOk && created?.id">
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-va-800/25 focus:ring-offset-2 sm:w-auto"
                @click="navigateToSubmittedRequestDetail"
              >
                {{ t('dispatch_wizard.confirm.submit_modal_view_request') }}
              </button>
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-va-800/25 focus:ring-offset-2 sm:w-auto"
                @click="closeSubmitModalAndStartNewDraft"
              >
                {{ t('dispatch_wizard.confirm.submit_modal_new_request') }}
              </button>
            </template>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-xl bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/30 focus:ring-offset-2 sm:w-auto"
              @click="closeSubmitResultModal"
            >
              {{ t('dispatch_wizard.confirm.submit_modal_close') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Modal: Thư viện nháp -->
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
          class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5"
          @click.stop
        >
          <div class="shrink-0 border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-sky-50/30 px-5 pb-4 pt-5">
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
          <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-3 py-3 sm:px-4">
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

  <!-- Modal: Chọn đối tượng phân bổ -->
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
        v-if="targetPickerModalOpen"
        id="dw-staff-target-picker-modal"
        class="fixed inset-0 z-[201] flex items-center justify-center bg-slate-900/45 p-4 backdrop-blur-[3px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dw-staff-target-picker-title"
        data-testid="staff-targets-picker-modal"
        @click.self="closeTargetPickerModal"
      >
        <div
          class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-black/5"
          @click.stop
        >
          <div class="shrink-0 border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-sky-50/30 px-5 pb-4 pt-5">
            <h3 id="dw-staff-target-picker-title" class="text-base font-semibold leading-snug text-slate-900">
              {{ t('dispatch_wizard.create.sec_targets') }}
            </h3>
            <p class="mt-1 text-xs leading-relaxed text-slate-600">
              {{ t('dispatch_wizard.create.targets_hint') }}
            </p>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-4 py-4 sm:px-5">
            <label class="sr-only" for="dw-staff-target-modal-filter">{{
              t('dispatch_wizard.create.targets_search_ph')
            }}</label>
            <input
              id="dw-staff-target-modal-filter"
              ref="targetModalFilterEl"
              v-model="targetModalFilterQ"
              type="search"
              autocomplete="off"
              class="dw-input mb-4 w-full"
              :placeholder="t('dispatch_wizard.create.targets_search_ph')"
              data-testid="staff-targets-modal-filter"
            />
            <p
              v-if="targetModalOptions.length === 0"
              class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-4 py-6 text-center text-sm text-slate-600"
            >
              {{ t('dispatch_wizard.create.targets_no_match') }}
            </p>
            <div
              v-else
              class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2"
              role="group"
              :aria-label="t('dispatch_wizard.create.sec_targets')"
            >
              <label
                v-for="opt in targetModalOptions"
                :key="opt"
                class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-transparent px-2 py-1.5 text-sm text-slate-800 transition hover:border-slate-200 hover:bg-slate-50"
                :data-testid="`staff-target-option-${opt}`"
              >
                <input
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                  :checked="form.targets.includes(opt)"
                  @change="toggleTargetCheckbox(opt, $event.target.checked)"
                />
                <span class="leading-snug">{{ opt }}</span>
              </label>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-4">
              <label class="mb-1.5 block text-xs font-medium text-slate-600" for="dw-staff-target-custom-input">
                {{ t('dispatch_wizard.create.targets_add_custom') }}
              </label>
              <div class="flex gap-2">
                <input
                  id="dw-staff-target-custom-input"
                  v-model="customTargetInput"
                  type="text"
                  class="dw-input min-w-0 flex-1"
                  :placeholder="t('dispatch_wizard.create.targets_custom_ph')"
                  maxlength="100"
                  data-testid="staff-targets-custom-input"
                  @keydown.enter.prevent="addCustomTarget"
                />
                <button
                  type="button"
                  class="shrink-0 rounded-xl bg-va-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-va-700 active:scale-95 disabled:opacity-50"
                  :disabled="!customTargetInput.trim()"
                  data-testid="staff-targets-custom-add"
                  @click="addCustomTarget"
                >
                  {{ t('dispatch_wizard.create.targets_add_custom') }}
                </button>
              </div>
              <div v-if="selectedCustomTargets.length" class="mt-3 flex flex-wrap gap-1.5">
                <span
                  v-for="chip in selectedCustomTargets"
                  :key="chip"
                  class="dw-portal-chip"
                >
                  {{ chip }}
                  <button
                    type="button"
                    class="dw-portal-chip__remove"
                    :data-testid="`staff-target-custom-remove-${chip}`"
                    @click="removeTarget(chip)"
                  >
                    <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
                  </button>
                </span>
              </div>
            </div>
          </div>
          <div class="border-t border-slate-100 bg-slate-50/90 px-4 py-3 sm:flex sm:items-center sm:justify-between sm:gap-3">
            <p v-if="form.targets.length" class="mb-2 text-xs text-slate-600 sm:mb-0">
              {{ t('dispatch_wizard.create.targets_selected', { n: form.targets.length }) }}
            </p>
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 sm:ml-auto sm:w-auto"
              data-testid="staff-targets-picker-close"
              @click="closeTargetPickerModal"
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
import { computed, defineAsyncComponent, onMounted, onUnmounted, provide, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ClipboardDocumentListIcon,
  CloudArrowUpIcon,
  DocumentArrowDownIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  PaperClipIcon,
  TrashIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useDispatchRequestWizard } from '../../composables/useDispatchRequestWizard'
import { DISPATCH_WIZARD_KEY } from './dispatch-wizard/injectionKeys'
import ConfirmSummary from './dispatch-wizard/ConfirmSummary.vue'
import RecurringConfigSection from '../../components/recurring/RecurringConfigSection.vue'
import PortalStepper from '../../components/portal/PortalStepper.vue'
import PortalTripTypeGrid from '../../components/portal/PortalTripTypeGrid.vue'

const TRIP_TYPES = ['door_to_door', 'point_to_point', 'business', 'cargo']

const { t } = useI18n()
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
  created,
  replaceDraftRequestId,
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
  requesterEmailFormatInvalid,
  coordinatorEmailFormatInvalid,
  draftSaveFlash,
  draftSaveError,
  step2CoordinatorEmailInvalid,
  requestedDateTime,
  e1WeekdayOptions,
  toggleE1Weekday,
  openDatePickerFromInput,
  onRequesterPhoneInput,
  onCoordinatorPhoneInput,
  onRequesterEmailBlur,
  onCoordinatorEmailBlur,
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
  formatDraftTime,
  dispatchFormSettingsLoading,
  dispatchFormSettingsError,
  urgentAutoActive,
  toggleUrgentManual,
  appliedUrgentThresholdHours,
  canGoNext,
  goStep,
  goStepFromStepper,
  prevStep,
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
  navigateToSubmittedRequestDetail,
  closeSubmitModalAndStartNewDraft,
  onCancel,
  submitResultModalOpen,
  submitResultOk,
  submitResultDetail,
  closeSubmitResultModal,
} = wizard

const urgentExplainTooltip = computed(() =>
  t('dispatch_wizard.create.urgent_explain_tooltip', {
    hours: appliedUrgentThresholdHours.value ?? '—',
  }),
)

const stepperSteps = computed(() => steps.value.map((s) => ({ key: s.id, label: s.title })))

// --- Draft dropdown ---
const draftMenuOpen = ref(false)
const draftMenuEl = ref(null)

function onDocClick(e) {
  if (draftMenuEl.value && !draftMenuEl.value.contains(e.target)) draftMenuOpen.value = false
}
onMounted(() => document.addEventListener('click', onDocClick, true))
onUnmounted(() => document.removeEventListener('click', onDocClick, true))

function draftMenuAction(fn) {
  draftMenuOpen.value = false
  fn()
}

// --- Step 1: chọn loại chuyến (nhấn lại để tiếp tục) ---
function onTripTypeClick(value) {
  if (form.value.trip_type === value) {
    nextStep()
    return
  }
  form.value.trip_type = value
}

// --- Bước 2: tab con ---
const STEP2_SUB_IDS = ['requester', 'time', 'purpose', 'coordination']

const step2Sub = ref('requester')
const maxReachedStep2Sub = ref(0)

const step2SubTabs = computed(() =>
  STEP2_SUB_IDS.map((id) => ({
    id,
    label: t(`dispatch_wizard.create.step2_sub_${id}`),
  })),
)

const step2SubIndex = computed(() => STEP2_SUB_IDS.indexOf(step2Sub.value))

function portalStep2SubComplete(subId) {
  if (subId === 'requester') {
    return (
      !!form.value.requester_name?.trim() &&
      !!form.value.requester_email?.trim() &&
      !requesterEmailFormatInvalid.value
    )
  }
  if (subId === 'time') {
    return (
      !!form.value.proposed_date &&
      !!form.value.date_needed &&
      !step2DateOrderInvalid.value &&
      (!form.value.is_urgent || !!form.value.urgent_reason?.trim())
    )
  }
  if (subId === 'purpose') {
    return !!form.value.purpose?.trim()
  }
  return !coordinatorEmailFormatInvalid.value
}

const portalCanGoNext = computed(() => {
  if (step.value !== 1) return canGoNext.value
  if (step2SubIndex.value === STEP2_SUB_IDS.length - 1) return canGoNext.value
  return portalStep2SubComplete(step2Sub.value)
})

function setStep2Sub(id) {
  const idx = STEP2_SUB_IDS.indexOf(id)
  if (idx === -1 || idx > maxReachedStep2Sub.value) return
  step2Sub.value = id
}

function portalNextStep() {
  if (step.value !== 1) {
    nextStep()
    return
  }
  if (!portalCanGoNext.value) return
  const idx = step2SubIndex.value
  if (idx < STEP2_SUB_IDS.length - 1) {
    maxReachedStep2Sub.value = Math.max(maxReachedStep2Sub.value, idx + 1)
    step2Sub.value = STEP2_SUB_IDS[idx + 1]
    return
  }
  nextStep()
}

function portalPrevStep() {
  if (step.value === 1 && step2SubIndex.value > 0) {
    step2Sub.value = STEP2_SUB_IDS[step2SubIndex.value - 1]
    return
  }
  prevStep()
}

watch(step, (v, oldV) => {
  if (v === 1 && oldV === 0) {
    step2Sub.value = 'requester'
    maxReachedStep2Sub.value = 0
  }
})

watch(
  () => [
    form.value.requester_name,
    form.value.requester_email,
    form.value.proposed_date,
    form.value.date_needed,
    form.value.purpose,
    step2RequesterEmailInvalid.value,
    step2DateOrderInvalid.value,
  ],
  () => {
    if (step.value !== 1) return
    let max = 0
    if (portalStep2SubComplete('requester')) max = 1
    if (max >= 1 && portalStep2SubComplete('time')) max = 2
    if (max >= 2 && portalStep2SubComplete('purpose')) max = 3
    maxReachedStep2Sub.value = Math.max(maxReachedStep2Sub.value, max)
  },
)

// --- Modal chọn đối tượng phân bổ ---
const targetPickerModalOpen = ref(false)
const targetModalFilterQ = ref('')
const targetModalFilterEl = ref(null)
const customTargetInput = ref('')

function normalizeTargetSearch(s) {
  return String(s)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

const targetModalOptions = computed(() => {
  const q = normalizeTargetSearch(targetModalFilterQ.value)
  if (!q) return targetOptions
  return targetOptions.filter((opt) => normalizeTargetSearch(opt).includes(q))
})

const selectedCustomTargets = computed(() =>
  form.value.targets.filter((name) => !targetOptions.includes(name)),
)

function openTargetPickerModal() {
  targetPickerModalOpen.value = true
  window.requestAnimationFrame(() => {
    targetModalFilterEl.value?.focus?.()
  })
}

function closeTargetPickerModal() {
  targetPickerModalOpen.value = false
  targetModalFilterQ.value = ''
  customTargetInput.value = ''
}

function toggleTargetCheckbox(val, checked) {
  if (!val) return
  const list = form.value.targets
  const idx = list.indexOf(val)
  if (checked) {
    if (idx === -1) list.push(val)
  } else if (idx !== -1) {
    list.splice(idx, 1)
  }
}

function addCustomTarget() {
  const val = customTargetInput.value.trim()
  if (!val || form.value.targets.includes(val)) return
  form.value.targets.push(val)
  customTargetInput.value = ''
}

function removeTarget(val) {
  const list = form.value.targets
  const idx = list.indexOf(val)
  if (idx !== -1) list.splice(idx, 1)
}
</script>

<style src="./dispatch-wizard/dispatchWizard.styles.css"></style>
