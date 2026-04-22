<template>
  <div class="driver-trip-detail -mx-3 flex min-h-[calc(100dvh-6rem)] w-[calc(100%+1.5rem)] flex-col bg-[#ECEEF1] sm:mx-auto sm:min-h-0 sm:w-full sm:max-w-lg sm:pb-0">
    <div class="relative shrink-0 bg-[#001a2e] px-3 pb-6 pt-2 text-white sm:rounded-b-3xl sm:px-4">
      <div class="flex items-start justify-between gap-2">
        <RouterLink
          :to="{ name: 'driverSchedule' }"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20"
          :aria-label="t('driver_trip_detail.back')"
        >
          <ArrowLeftIcon class="h-5 w-5" />
        </RouterLink>
        <div class="min-w-0 flex-1 text-center">
          <h1 class="text-base font-bold tracking-tight">{{ t('driver_trip_detail.title') }}</h1>
          <p class="text-sm font-medium text-amber-400">{{ headerStatusText }}</p>
        </div>
        <div class="relative" ref="moreRoot">
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20"
            :aria-label="t('driver_trip_detail.more')"
            @click="moreOpen = !moreOpen"
          >
            <EllipsisVerticalIcon class="h-5 w-5" />
          </button>
          <div
            v-show="moreOpen"
            class="absolute right-0 top-11 z-20 min-w-[10rem] overflow-hidden rounded-xl border border-slate-200/20 bg-slate-900 py-1 text-left text-sm shadow-lg"
          >
            <button
              type="button"
              class="w-full px-3 py-2.5 text-left text-white/90 hover:bg-white/10"
              @click="refresh(); moreOpen = false"
            >
              {{ t('driver_trip_detail.action_refresh') }}
            </button>
            <a
              v-if="dispatcherPhone"
              :href="`tel:${dispatcherPhone}`"
              class="block px-3 py-2.5 text-white/90 hover:bg-white/10"
              @click="moreOpen = false"
            >
              {{ t('driver_trip_detail.call_dispatcher') }}
            </a>
          </div>
        </div>
      </div>

      <div
        v-if="trip"
        class="mt-3 flex items-center justify-between gap-2 rounded-2xl border border-white/10 bg-white/5 px-3 py-2.5"
      >
        <div class="min-w-0">
          <p class="text-sm font-bold leading-snug text-white">
            {{ t('driver_trip_detail.trip_headline', { type: tripHeadlineType }) }}
          </p>
          <p class="mt-0.5 text-xs text-white/70">
            {{ t('driver_trip_detail.request_from', { name: requesterLine }) }}
          </p>
        </div>
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-500/20 ring-1 ring-sky-400/40"
        >
          <BriefcaseIcon class="h-5 w-5 text-sky-200" />
        </div>
      </div>

      <div
        v-if="warningBanner"
        class="mt-3 flex gap-3 rounded-2xl border border-amber-400/50 bg-gradient-to-r from-amber-500/95 to-amber-600/90 px-3 py-3 text-amber-950 shadow-md"
      >
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/30">
          <ClockIcon class="h-5 w-5 text-amber-950" />
        </div>
        <div class="min-w-0">
          <p class="text-sm font-bold">{{ t('driver_trip_detail.warn_title', { n: warningBanner.minutes }) }}</p>
          <p class="mt-0.5 text-xs leading-snug text-amber-950/90">
            {{ warningBanner.body }}
          </p>
        </div>
      </div>
    </div>

    <div class="relative z-10 -mt-3 flex-1 space-y-3 px-3 pb-36 sm:px-0 sm:pb-8">
      <p v-if="loadError" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
        {{ loadError }}
      </p>

      <div v-if="loading && !trip" class="space-y-3">
        <div class="h-40 animate-pulse rounded-2xl bg-slate-200" />
        <div class="h-48 animate-pulse rounded-2xl bg-slate-200" />
      </div>

      <template v-else-if="trip">
        <!-- Bản đồ -->
        <div v-if="embedMapSrc" class="overflow-hidden rounded-2xl border border-slate-200/90 bg-slate-200 shadow-md">
          <div class="relative h-[9.5rem] w-full sm:h-44">
            <iframe
              :title="t('driver_trip_detail.map_frame')"
              class="absolute inset-0 h-full w-full border-0"
              :src="embedMapSrc"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            />
            <a
              v-if="mapUrl"
              :href="mapUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="absolute left-2 top-2 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1.5 text-xs font-bold text-slate-900 shadow-md ring-1 ring-slate-200/80"
            >
              <MapIcon class="h-4 w-4 text-[#001a2e]" />
              {{ t('driver_trip_detail.map_nav') }}
            </a>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-md">
          <div class="flex justify-between gap-3">
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                {{ t('driver_trip_detail.trip_code') }}
              </p>
              <p class="text-lg font-bold text-[#001a2e]">{{ tripCodeDisplay }}</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                {{ t('driver_trip_detail.route') }}
              </p>
              <p class="text-sm font-bold text-[#001a2e]">{{ routeTitle }}</p>
            </div>
          </div>

          <div class="relative mt-4 pl-1">
            <div class="absolute left-[7px] top-2 bottom-2 w-px bg-slate-200" />
            <div class="space-y-4 pl-4">
              <div class="relative">
                <span
                  class="absolute -left-4 top-1.5 h-2.5 w-2.5 rounded-full border-2 border-slate-300 bg-white"
                />
                <p class="text-[11px] text-slate-500">
                  {{ t('driver_trip_detail.point_start') }} · {{ timeHm(trip.depart_at) }}
                </p>
                <p class="text-sm font-semibold text-slate-900">{{ originMain }}</p>
                <p v-if="originSub" class="text-xs text-slate-500">{{ originSub }}</p>
              </div>
              <div class="relative">
                <span
                  class="absolute -left-4 top-1.5 h-2.5 w-2.5 rounded-full border-[3px] border-[#001a2e] bg-white"
                />
                <p class="text-[11px] text-slate-500">
                  {{ t('driver_trip_detail.point_end') }} ·
                  {{ t('driver_trip_detail.planned', { t: timeHm(trip.arrive_by || dr?.arrive_by) }) }}
                </p>
                <p class="text-sm font-semibold text-slate-900">{{ destMain }}</p>
                <p v-if="destSub" class="text-xs text-slate-500">{{ destSub }}</p>
              </div>
            </div>
          </div>

          <div
            v-if="dispatchNotes"
            class="mt-4 flex gap-2 rounded-xl border border-slate-100 bg-slate-50/95 px-3 py-2.5 text-xs text-slate-700"
          >
            <InformationCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            <div>
              <p class="font-semibold text-slate-800">{{ t('driver_trip_detail.trip_notes_title') }}</p>
              <p class="mt-0.5 whitespace-pre-wrap text-slate-600">{{ dispatchNotes }}</p>
            </div>
          </div>
        </div>

        <button
          type="button"
          class="flex w-full items-center justify-between gap-2 rounded-2xl border border-slate-200/90 bg-white px-3 py-3 text-left shadow-sm"
          @click="openKmModal()"
        >
          <div class="flex min-w-0 items-center gap-2.5">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-700">
              <ChartBarIcon class="h-5 w-5" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-900">{{ t('driver_trip_detail.km_row_title') }}</p>
              <p v-if="!hasEndOdometer" class="text-xs text-amber-600">
                {{ t('driver_trip_detail.km_end_missing') }}
              </p>
              <p v-else class="text-xs text-slate-500">
                {{ t('driver_trip_detail.km_end_value', { km: formatKm(trip.record.end_odometer_km) }) }}
              </p>
            </div>
          </div>
          <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400" />
        </button>

        <div class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm">
          <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">{{ t('driver_trip_detail.costs_title') }}</h2>
            <button
              v-if="canAddCost"
              type="button"
              class="text-sm font-semibold text-sky-700"
              @click="openCostModal"
            >
              {{ t('driver_trip_detail.costs_add') }}
            </button>
          </div>
          <p v-if="!tripCosts.length" class="px-0.5 py-2 text-xs text-slate-500">
            {{ t('driver_trip_detail.costs_empty') }}
          </p>
          <ul v-else class="space-y-2">
            <li v-for="c in tripCosts" :key="c.id">
              <RouterLink
                :to="`/driver/costs/${c.id}`"
                class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-slate-50/60 px-2.5 py-2.5 transition active:bg-slate-100"
              >
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-slate-700 ring-1 ring-slate-200/80"
                >
                  <FireIcon v-if="c.type === 'fuel'" class="h-4 w-4" />
                  <BanknotesIcon v-else class="h-4 w-4" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-slate-900">
                    {{ costTypeLabel(c.type) }}
                    <span v-if="c.description" class="font-normal text-slate-600">· {{ c.description }}</span>
                  </p>
                  <p class="text-[11px] text-slate-500">{{ formatCostTime(c.created_at) }}</p>
                </div>
                <div class="flex items-center gap-0.5 text-sm font-bold tabular-nums text-slate-900">
                  {{ formatVnd(c.amount) }}
                  <ChevronRightIcon class="h-4 w-4 text-slate-300" />
                </div>
              </RouterLink>
            </li>
          </ul>
        </div>

        <div v-if="paxList.length" class="mt-1">
          <div class="mb-2 flex items-center justify-between gap-2">
            <h2 class="text-sm font-bold text-slate-900">
              {{ t('driver_trip_detail.students_title', { n: paxList.length }) }}
            </h2>
            <span v-if="showPickupBar" class="shrink-0 text-xs text-slate-500">
              {{ pickedCount }}/{{ paxList.length }} {{ t('driver_trip_detail.picked') }}
            </span>
          </div>
          <ul class="space-y-3">
            <li
              v-for="(p, i) in paxList"
              :key="i"
              class="overflow-hidden rounded-2xl border bg-white shadow-sm"
              :class="
                isNextIndex(i) && paxKind === 'student'
                  ? 'border-2 border-amber-400 border-l-[6px] border-l-amber-500'
                  : 'border-slate-200/90'
              "
            >
              <div class="flex items-start gap-3 p-3">
                <div class="relative shrink-0">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-700"
                  >
                    {{ studentInitials(p.name) }}
                  </div>
                  <div
                    v-if="paxKind === 'student' && rowState(i) === 'picked_up'"
                    class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white"
                  >
                    <CheckIcon class="h-3.5 w-3.5" />
                  </div>
                  <div
                    v-else-if="paxKind === 'student' && isNextIndex(i) && canMarkPickup"
                    class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-white ring-2 ring-white"
                  >
                    <MapPinIcon class="h-3 w-3" />
                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="font-semibold text-slate-900">{{ p.name }}</span>
                    <span
                      v-if="paxKind === 'student' && isNextIndex(i) && canMarkPickup"
                      class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-800"
                    >
                      {{ t('driver_trip_detail.tag_next') }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500">{{ p.subtitle }}</p>
                </div>
                <a
                  v-if="p.phone"
                  :href="`tel:${p.phone}`"
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-[#001a2e] hover:bg-slate-100"
                >
                  <PhoneIcon class="h-5 w-5" />
                </a>
              </div>
              <div
                v-if="paxKind === 'student' && isNextIndex(i) && canMarkPickup"
                class="flex gap-2 border-t border-slate-100 px-3 py-2.5"
              >
                <button
                  type="button"
                  :disabled="eventPosting"
                  class="min-w-0 flex-1 rounded-xl bg-[#27AE60] py-2.5 text-sm font-bold text-white disabled:opacity-50"
                  @click="setRowState(i, 'picked_up')"
                >
                  {{ t('driver_trip_detail.btn_picked') }}
                </button>
                <button
                  type="button"
                  :disabled="eventPosting"
                  class="w-[4.5rem] shrink-0 rounded-xl bg-slate-200 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                  @click="setRowState(i, 'absent')"
                >
                  {{ t('driver_trip_detail.btn_absent') }}
                </button>
              </div>
            </li>
          </ul>
        </div>
        <p
          v-else-if="paxKind === 'student'"
          class="rounded-2xl border border-dashed border-slate-200 bg-white/80 px-3 py-5 text-center text-sm text-slate-500"
        >
          {{ t('driver_trip_detail.no_pax') }}
        </p>
      </template>
    </div>

    <div
      class="safe-pb fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200/80 bg-white/95 px-3 py-3 backdrop-blur sm:static sm:mt-4 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:backdrop-blur-0"
    >
      <button
        v-if="canStart"
        type="button"
        :disabled="actionBusy"
        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#27AE60] py-3.5 text-sm font-bold text-white shadow-md disabled:opacity-50"
        @click="startTrip"
      >
        <PlayIcon class="h-5 w-5" />
        {{ t('driver_trip_detail.btn_start_trip') }}
      </button>
      <button
        v-else-if="canEndTrip"
        type="button"
        :disabled="actionBusy"
        class="flex w-full items-center justify-center gap-2 rounded-2xl py-3.5 text-sm font-bold text-white shadow-md disabled:opacity-50"
        style="background: #001a2e"
        @click="onEndTrip"
      >
        <FlagIcon class="h-5 w-5" />
        {{ t('driver_trip_detail.btn_end_trip') }}
      </button>
      <p
        v-else-if="trip?.status === 'completed'"
        class="rounded-2xl border border-emerald-200 bg-emerald-50 py-3 text-center text-sm font-medium text-emerald-900"
      >
        {{ t('driver_trip_detail.done') }}
      </p>
    </div>

    <Teleport to="body">
      <div
        v-if="kmModalOpen"
        class="fixed inset-0 z-40 flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
        @click.self="kmModalOpen = false"
      >
        <div
          class="w-full max-w-md rounded-t-2xl bg-white p-4 shadow-2xl sm:rounded-2xl"
          @click.stop
        >
          <div class="mb-3 flex items-start justify-between gap-2">
            <h3 class="pr-6 text-base font-bold text-[#001a2e]">{{ t('driver_trip_detail.km_modal_title') }}</h3>
            <button
              type="button"
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600"
              :aria-label="t('driver_trip_detail.close')"
              @click="kmModalOpen = false"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[11px] font-medium text-slate-500">{{ t('driver_trip_detail.km_start') }}</label>
              <div class="mt-0.5 flex items-center rounded-xl border border-slate-200 bg-slate-50 px-2 py-2 text-sm text-slate-800">
                <input
                  class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm"
                  :value="formatKmInput(startKmModel)"
                  readonly
                />
                <span class="ml-1 text-xs text-slate-500">km</span>
              </div>
            </div>
            <div>
              <label class="text-[11px] font-medium text-slate-500">{{ t('driver_trip_detail.km_end') }}</label>
              <div class="mt-0.5 flex items-center rounded-xl border border-slate-200 bg-white px-2 py-2 focus-within:ring-2 focus-within:ring-sky-300/50">
                <input
                  v-model="endKmModel"
                  type="text"
                  inputmode="numeric"
                  class="min-w-0 flex-1 border-0 p-0 text-sm"
                  :placeholder="t('driver_trip_detail.km_placeholder')"
                  @input="onEndKmInput"
                />
                <span class="ml-1 text-xs text-slate-500">km</span>
              </div>
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2.5 text-sm text-slate-800">
            <MapIcon class="h-4 w-4 text-slate-500" />
            <span class="text-slate-600">{{ t('driver_trip_detail.km_distance') }}</span>
            <span class="ml-auto font-bold tabular-nums">{{ distancePreview }}</span>
          </div>
          <div class="mt-3">
            <label class="text-[11px] font-medium text-slate-500">{{ t('driver_trip_detail.km_note') }}</label>
            <textarea
              v-model="kmNoteModel"
              rows="3"
              class="mt-0.5 w-full resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm"
              :placeholder="t('driver_trip_detail.km_note_ph')"
            />
          </div>
          <div class="mt-4 flex gap-2">
            <button
              type="button"
              class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-800"
              @click="kmModalOpen = false"
            >
              {{ t('driver_trip_detail.cancel') }}
            </button>
            <button
              type="button"
              :disabled="kmSaving || !canSubmitKm"
              class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl py-2.5 text-sm font-bold text-white disabled:opacity-50"
              style="background: #001a2e"
              @click="submitKmModal"
            >
              {{ t('driver_trip_detail.confirm') }}
              <ArrowRightIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="costModalOpen"
        class="fixed inset-0 z-40 flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
        @click.self="costModalOpen = false"
      >
        <div
          class="w-full max-w-md rounded-t-2xl bg-white p-4 shadow-2xl sm:rounded-2xl"
          @click.stop
        >
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-[#001a2e]">{{ t('driver_trip_detail.cost_modal_title') }}</h3>
            <button
              type="button"
              class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
              @click="costModalOpen = false"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <label class="text-[11px] font-medium text-slate-500">{{ t('driver_trip_detail.cost_type') }}</label>
          <select
            v-model="costForm.type"
            class="mt-0.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
          >
            <option value="fuel">{{ costTypeLabel('fuel') }}</option>
            <option value="toll">{{ costTypeLabel('toll') }}</option>
            <option value="parking">{{ costTypeLabel('parking') }}</option>
            <option value="other">{{ costTypeLabel('other') }}</option>
          </select>
          <label class="mt-2 block text-[11px] font-medium text-slate-500">{{ t('driver_trip_detail.cost_amount') }}</label>
          <input
            v-model="costForm.amount"
            type="text"
            inputmode="numeric"
            class="mt-0.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            :placeholder="t('driver_trip_detail.cost_amount_ph')"
          />
          <label class="mt-2 block text-[11px] font-medium text-slate-500">{{ t('driver_trip_detail.cost_desc') }}</label>
          <input
            v-model="costForm.description"
            type="text"
            class="mt-0.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            :placeholder="t('driver_trip_detail.cost_desc_ph')"
          />
          <p v-if="costError" class="mt-2 text-xs text-rose-600">{{ costError }}</p>
          <div class="mt-4 flex gap-2">
            <button
              type="button"
              class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold"
              @click="costModalOpen = false"
            >
              {{ t('driver_trip_detail.cancel') }}
            </button>
            <button
              type="button"
              :disabled="costSaving"
              class="flex-1 rounded-xl py-2.5 text-sm font-bold text-white disabled:opacity-50"
              style="background: #001a2e"
              @click="submitCost"
            >
              {{ t('driver_trip_detail.confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  BanknotesIcon,
  BriefcaseIcon,
  ChartBarIcon,
  CheckIcon,
  ChevronRightIcon,
  ClockIcon,
  EllipsisVerticalIcon,
  FireIcon,
  FlagIcon,
  InformationCircleIcon,
  MapIcon,
  MapPinIcon,
  PhoneIcon,
  PlayIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { addTripEvent, getTrip, updateTripStatus, upsertTripRecord } from '../../api/trips'
import { submitTripCost } from '../../api/costs'
import { isPassengerRowFilled, isBusinessRowFilled, isCargoRowFilled } from '../../composables/dispatchWizardConstants'
import { formatVnd } from '../../util/labels'

const { t, te } = useI18n()
const route = useRoute()

const PASSENGER_PICKUP_EVENT = 'passenger_pickup'

const trip = ref(null)
const loading = ref(true)
const loadError = ref('')
const moreOpen = ref(false)
const moreRoot = ref(null)
const eventPosting = ref(false)
const actionBusy = ref(false)

const kmModalOpen = ref(false)
const kmAfterSave = ref(null)
const kmSaving = ref(false)
const endKmModel = ref('')
const kmNoteModel = ref('')

const costModalOpen = ref(false)
const costSaving = ref(false)
const costError = ref('')
const costForm = ref({ type: 'fuel', amount: '', description: '' })

function onDocClick(e) {
  const el = moreRoot.value
  if (el && !el.contains(e.target)) moreOpen.value = false
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  void load()
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
})

const tripId = computed(() => {
  const id = route.params.id
  const n = Number(id)
  return Number.isFinite(n) && n > 0 ? n : null
})

watch(
  () => route.params.id,
  () => {
    void load()
  },
)

const dr = computed(() => trip.value?.dispatch_request ?? null)
const snap = computed(() => dr.value?.wizard_snapshot ?? null)

const tripHeadlineType = computed(() => {
  const tt = dr.value?.trip_type
  if (tt === 'door_to_door') return 'D2D'
  if (tt === 'point_to_point') return 'P2P'
  if (tt === 'business') return t('driver_trip_detail.type_biz')
  if (tt === 'cargo') return 'Cargo'
  return '—'
})
const requesterLine = computed(() => dr.value?.requester?.name?.trim() || '—')
const tripCodeDisplay = computed(() => {
  const id = trip.value?.id
  if (!id) return '—'
  const tt = dr.value?.trip_type
  const pre = tt === 'business' ? 'CT' : tt === 'cargo' ? 'CG' : 'TR'
  return `#${pre}-${id}`
})

function splitAddress(s) {
  const t0 = (s || '').trim()
  if (!t0) return { main: '—', sub: '' }
  const i = t0.indexOf(',')
  if (i === -1) return { main: t0, sub: '' }
  return { main: t0.slice(0, i).trim(), sub: t0.slice(i + 1).trim() }
}

const originLines = computed(() => splitAddress(dr.value?.origin))
const destLines = computed(() => splitAddress(dr.value?.destination))
const originMain = computed(() => originLines.value.main)
const originSub = computed(() => originLines.value.sub)
const destMain = computed(() => destLines.value.main)
const destSub = computed(() => destLines.value.sub)

const dispatchNotes = computed(() => (dr.value?.notes || '').trim() || null)

const embedMapSrc = computed(() => {
  const o = dr.value?.origin?.trim()
  const d = dr.value?.destination?.trim()
  if (o && d) {
    return `https://maps.google.com/maps?q=${encodeURIComponent(`${o} → ${d}`)}&output=embed`
  }
  if (d) return `https://maps.google.com/maps?q=${encodeURIComponent(d)}&output=embed`
  if (o) return `https://maps.google.com/maps?q=${encodeURIComponent(o)}&output=embed`
  return ''
})

const mapUrl = computed(() => {
  const a = (dr.value?.origin || '').trim()
  const b = (dr.value?.destination || '').trim()
  if (a && b) {
    return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(a)}&destination=${encodeURIComponent(b)}&travelmode=driving`
  }
  if (a) return `https://maps.google.com/maps?q=${encodeURIComponent(a)}`
  if (b) return `https://maps.google.com/maps?q=${encodeURIComponent(b)}`
  return ''
})

const routeTitle = computed(() => {
  const tt = dr.value?.trip_type
  const short = (() => {
    if (tt === 'door_to_door') return 'D2D'
    if (tt === 'point_to_point') return 'P2P'
    if (tt === 'business') return t('driver_trip_detail.type_biz')
    if (tt === 'cargo') return 'Cargo'
    return '—'
  })()
  if (!trip.value?.depart_at) return short
  const h = new Date(trip.value.depart_at).getHours()
  const part = h < 12 ? t('driver_trip_detail.period_morning') : t('driver_trip_detail.period_afternoon')
  return `${short} ${part}`
})

const tripCosts = computed(() => {
  const c = trip.value?.costs
  if (!Array.isArray(c)) return []
  return c.slice().sort((a, b) => (b.id || 0) - (a.id || 0))
})

const canAddCost = computed(
  () => trip.value && ['in_progress', 'assigned', 'driver_confirmed', 'pending', 'approved'].includes(trip.value.status),
)

function timeHm(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: true })
}

function formatCostTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  const today = new Date()
  const isToday = d.toDateString() === today.toDateString()
  const time = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: true })
  if (isToday) return t('driver_trip_detail.today_time', { t: time })
  return d.toLocaleString('vi-VN', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function costTypeLabel(type) {
  const k = `driver_trip_detail.cost_type_${String(type || 'other')}`
  if (te(k)) return t(k)
  return type
}

const dispatcherPhone = computed(() => null)

function statusLabelTr(st) {
  const k = `trips_page.trip_status.${st}`
  const tr = t(k)
  return tr === k ? st : tr
}

const headerStatusText = computed(() => {
  if (!trip.value) return '—'
  if (trip.value.status === 'in_progress') return t('driver_trip_detail.status_running')
  return statusLabelTr(trip.value.status)
})

const startKmModel = computed(() => {
  const r = trip.value?.record
  if (r?.start_odometer_km != null) return r.start_odometer_km
  const v = trip.value?.vehicle?.odometer_km
  if (v != null) return v
  const d0 = trip.value?.driver?.odometer_km
  if (d0 != null) return d0
  return null
})

const hasEndOdometer = computed(() => trip.value?.record?.end_odometer_km != null)

function formatKm(n) {
  if (n == null || n === '') return '—'
  return Number(n).toLocaleString('vi-VN')
}

function formatKmInput(n) {
  if (n == null || n === '') return ''
  return Number(n).toLocaleString('vi-VN')
}

const distancePreview = computed(() => {
  const s = startKmModel.value
  const e = parseDigits(endKmModel.value)
  if (s == null || e == null) return t('driver_trip_detail.km_dash')
  const d = e - Number(s)
  if (Number.isNaN(d) || d < 0) return t('driver_trip_detail.km_dash')
  return `${d.toLocaleString('vi-VN')} km`
})

const canSubmitKm = computed(() => {
  const e = parseDigits(endKmModel.value)
  if (e == null) return false
  const s = startKmModel.value
  if (s != null && e < Number(s)) return false
  return true
})

function parseDigits(v) {
  const d = String(v || '').replace(/[^\d]/g, '')
  if (d === '') return null
  return parseInt(d, 10)
}

function onEndKmInput() {
  const p = parseDigits(endKmModel.value)
  if (p != null) endKmModel.value = p.toLocaleString('vi-VN')
}

function openKmModal(completeAfter) {
  kmAfterSave.value = completeAfter === 'complete' ? 'complete' : null
  const e = trip.value?.record?.end_odometer_km
  if (e != null) {
    endKmModel.value = String(e)
    onEndKmInput()
  } else {
    endKmModel.value = ''
  }
  kmNoteModel.value = (trip.value?.record?.driver_notes || '').trim()
  kmModalOpen.value = true
}

async function submitKmModal() {
  const id = tripId.value
  if (id == null || !canSubmitKm.value || kmSaving.value) return
  const e = parseDigits(endKmModel.value)
  if (e == null) return
  const doComplete = kmAfterSave.value === 'complete'
  kmSaving.value = true
  try {
    const s = startKmModel.value
    const payload = {
      end_odometer_km: e,
    }
    const note = kmNoteModel.value?.trim()
    if (note) payload.driver_notes = note
    if (s != null && trip.value?.record?.start_odometer_km == null) {
      payload.start_odometer_km = Number(s)
    }
    await upsertTripRecord(id, payload)
    await refresh()
    kmModalOpen.value = false
    if (doComplete) await doCompleteTrip()
  } catch {
    loadError.value = t('driver_trip_detail.km_err')
  } finally {
    kmSaving.value = false
    kmAfterSave.value = null
  }
}

function openCostModal() {
  costError.value = ''
  costForm.value = { type: 'fuel', amount: '', description: '' }
  costModalOpen.value = true
}

async function submitCost() {
  const id = tripId.value
  if (id == null || costSaving.value) return
  const a = String(costForm.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num < 0) {
    costError.value = t('driver_trip_detail.cost_err_amount')
    return
  }
  costSaving.value = true
  costError.value = ''
  try {
    await submitTripCost(
      id,
      {
        type: costForm.value.type,
        amount: num,
        description: costForm.value.description?.trim() || null,
        currency: 'VND',
      },
      { idempotencyKey: `driver-cost-${id}-${Date.now()}` },
    )
    costModalOpen.value = false
    await refresh()
  } catch (e) {
    costError.value = t('driver_trip_detail.cost_err_submit')
  } finally {
    costSaving.value = false
  }
}

const warningBanner = computed(() => {
  const tr = trip.value
  if (!tr) return null
  if (!['in_progress', 'assigned', 'driver_confirmed', 'pending', 'approved'].includes(tr.status)) return null
  const endIso = tr.arrive_by || dr.value?.arrive_by
  if (!endIso) return null
  const end = new Date(endIso).getTime()
  if (!Number.isFinite(end)) return null
  const min = Math.round((end - Date.now()) / 60000)
  if (min < 0 || min > 10) return null
  const firstDrop = (dr.value?.destination || '').trim() || '—'
  return { minutes: min, body: t('driver_trip_detail.warn_body', { place: firstDrop }) }
})

const pickupStateByIndex = computed(() => {
  const evs = trip.value?.events
  if (!Array.isArray(evs) || !evs.length) return new Map()
  const sorted = [...evs].sort((a, b) => (b.id || 0) - (a.id || 0))
  const m = new Map()
  for (const e of sorted) {
    if (e.type !== PASSENGER_PICKUP_EVENT) continue
    const d = e.data
    if (!d || d.row_index == null) continue
    const idx = Number(d.row_index)
    if (Number.isNaN(idx) || m.has(idx)) continue
    const st = d.state
    if (st === 'picked_up' || st === 'absent') m.set(idx, st)
  }
  return m
})

function rowState(i) {
  return pickupStateByIndex.value.get(i) ?? null
}

const paxKind = computed(() => {
  const tt = dr.value?.trip_type
  if (tt === 'door_to_door' || tt === 'point_to_point') return 'student'
  return 'other'
})

const paxList = computed(() => {
  const ttr = dr.value
  if (!ttr) return []
  const s = snap.value
  const out = []
  const tt = ttr.trip_type

  if (tt === 'cargo' && s?.cargoRows?.length) {
    let i = 0
    for (const r of s.cargoRows) {
      if (!isCargoRowFilled(r)) continue
      i += 1
      out.push({
        name: r.name?.trim() || t('driver_trip_detail.cargo_item', { n: i }),
        subtitle: [r.pickup_at, r.pickup_place].filter(Boolean).join(' · ') || '—',
        phone: (r.pickup_contact || r.delivery_contact || '').replace(/\D/g, '') || null,
      })
    }
    return out
  }

  if (tt === 'business' && s?.businessRows?.length) {
    let i = 0
    for (const r of s.businessRows) {
      if (!isBusinessRowFilled(r)) continue
      i += 1
      out.push({
        name: t('driver_trip_detail.biz_party', { n: i }),
        subtitle: r.notes?.trim() || r.pickup || '—',
        phone: null,
      })
    }
    if (out.length) return out
  }

  let idx = 0
  for (const r of s?.passengerRows ?? []) {
    if (!isPassengerRowFilled(r)) continue
    idx += 1
    const name = r.person_in_charge?.trim() || t('driver_trip_detail.guest_n', { n: idx })
    const classGuess = classFromNotes(r.notes)
    const timePart = r.depart_at
      ? t('driver_trip_detail.expected', { t: timeHm(r.depart_at) })
      : timeHm(ttr.depart_at) !== '—'
        ? t('driver_trip_detail.expected', { t: timeHm(ttr.depart_at) })
        : ''
    const sub = [classGuess, timePart].filter(Boolean).join(' • ') || (r.notes || '').trim() || '—'
    out.push({ name, subtitle: sub, phone: null })
  }

  for (const r of s?.businessRows ?? []) {
    if (tt === 'business') break
    if (!isBusinessRowFilled(r)) continue
    idx += 1
    out.push({
      name: t('driver_trip_detail.biz_party', { n: idx }),
      subtitle: r.notes?.trim() || '—',
      phone: null,
    })
  }

  if (!out.length && (ttr.passenger_count ?? 0) > 0) {
    out.push({
      name: t('driver_trip_detail.unlisted', { n: ttr.passenger_count }),
      subtitle: '—',
      phone: ttr.requester?.phone || null,
    })
  }
  return out
})

function classFromNotes(notes) {
  const s = (notes || '').trim()
  if (!s) return ''
  const m = s.match(/lớp\s*([0-9A-Za-z]+)/i) || s.match(/Lớp\s*([0-9A-Za-z.]+)/)
  if (m) return `Lớp ${m[1]}`
  if (s.length < 40) return s
  return s.slice(0, 36) + '…'
}

function studentInitials(name) {
  const n = (name || '').trim() || '?'
  const p = n.split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
}

const showPickupBar = computed(() => paxKind.value === 'student' && paxList.value.length > 0)

const pickedCount = computed(() => {
  if (paxKind.value !== 'student') return 0
  let c = 0
  for (let i = 0; i < paxList.value.length; i += 1) {
    if (rowState(i) === 'picked_up') c += 1
  }
  return c
})

function isNextIndex(i) {
  if (paxKind.value !== 'student' || !canMarkPickup.value) return false
  for (let j = 0; j < paxList.value.length; j += 1) {
    const st = rowState(j)
    if (st === 'picked_up' || st === 'absent') continue
    return j === i
  }
  return false
}

const canMarkPickup = computed(
  () => trip.value?.status === 'in_progress' && paxKind.value === 'student' && paxList.value.length > 0,
)

const canStart = computed(() => {
  if (!trip.value) return false
  return ['assigned', 'driver_confirmed', 'pending', 'approved'].includes(trip.value.status)
})

const canEndTrip = computed(() => trip.value?.status === 'in_progress')

async function onEndTrip() {
  if (!canEndTrip.value) return
  if (!hasEndOdometer.value) {
    openKmModal('complete')
    return
  }
  await doCompleteTrip()
}

async function doCompleteTrip() {
  const id = tripId.value
  if (id == null || actionBusy.value) return
  actionBusy.value = true
  try {
    await updateTripStatus(id, { status: 'completed' })
    await refresh()
  } catch {
    loadError.value = t('driver_trip_detail.status_err')
  } finally {
    actionBusy.value = false
  }
}

async function load() {
  const id = tripId.value
  if (id == null) {
    loadError.value = t('driver_trip_detail.err_bad_id')
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    trip.value = await getTrip(id)
  } catch (e) {
    loadError.value = t('driver_home.load_error')
    trip.value = null
  } finally {
    loading.value = false
  }
}

async function refresh() {
  const id = tripId.value
  if (id == null) return
  try {
    trip.value = await getTrip(id)
  } catch {
    /* keep */
  }
}

async function setRowState(i, state) {
  const id = tripId.value
  if (id == null || eventPosting.value) return
  eventPosting.value = true
  try {
    await addTripEvent(id, {
      type: PASSENGER_PICKUP_EVENT,
      data: { row_index: i, state, at: new Date().toISOString() },
    })
    await refresh()
  } catch {
    loadError.value = t('driver_trip_detail.event_err')
  } finally {
    eventPosting.value = false
  }
}

async function startTrip() {
  const id = tripId.value
  if (id == null || actionBusy.value) return
  actionBusy.value = true
  try {
    await updateTripStatus(id, { status: 'in_progress' })
    await refresh()
  } catch {
    loadError.value = t('driver_trip_detail.status_err')
  } finally {
    actionBusy.value = false
  }
}
</script>

<style scoped>
.safe-pb {
  padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0px));
}
</style>
