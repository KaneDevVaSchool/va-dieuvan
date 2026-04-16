<template>
  <div v-if="loading" class="text-sm text-slate-500">{{ t('trip_detail.loading') }}</div>
  <div v-else-if="trip" class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div class="min-w-0">
        <div class="text-lg font-semibold text-slate-900">{{ t('trip_detail.title') }}</div>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-600">
          <span class="font-medium text-slate-900">{{ tripCode }}</span>
          <span class="text-slate-300">•</span>
          <span>{{ t('trip_detail.created_at', { time: fmt(trip.created_at) }) }}</span>
          <span v-if="trip.dispatch_request?.is_urgent" class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">
            {{ t('trip_detail.high_priority') }}
          </span>
          <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', pillClassForStatus(trip.status)]">
            {{ labelTripStatus(trip.status) }}
          </span>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button variant="secondary" @click="copyLink">{{ t('trip_detail.actions.copy_link') }}</Button>
        <Button variant="secondary" @click="exportJson">{{ t('trip_detail.actions.export') }}</Button>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="space-y-4 lg:col-span-2">
        <Card :title="t('trip_detail.trip_status.title')">
          <div class="grid gap-4 md:grid-cols-3">
            <div class="flex items-start gap-3">
              <div :class="dotClass(stepPickup.state)"></div>
              <div class="min-w-0">
                <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.trip_status.pickup') }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ originLabel }}</div>
                <div class="mt-1 text-xs text-slate-500">
                  <span class="font-medium">{{ stepPickup.label }}</span>
                  <span v-if="trip.depart_at"> • {{ fmt(trip.depart_at) }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div :class="dotClass(stepCurrent.state)"></div>
              <div class="min-w-0">
                <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.trip_status.current') }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ currentLabel }}</div>
                <div class="mt-1 text-xs text-slate-500">
                  <span class="font-medium">{{ stepCurrent.label }}</span>
                  <span v-if="trip.started_at"> • {{ fmt(trip.started_at) }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div :class="dotClass(stepDropoff.state)"></div>
              <div class="min-w-0">
                <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.trip_status.dropoff') }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ destinationLabel }}</div>
                <div class="mt-1 text-xs text-slate-500">
                  <span class="font-medium">{{ stepDropoff.label }}</span>
                  <span v-if="trip.arrive_by"> • {{ fmt(trip.arrive_by) }}</span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <Card :title="t('trip_detail.timeline.title')">
          <div class="space-y-3">
            <div v-for="e in timeline" :key="e.key" class="flex gap-3">
              <div class="mt-0.5 h-6 w-6 flex-none rounded-full border bg-white text-center text-xs leading-6 text-slate-600">
                {{ e.icon }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                  <div class="text-sm font-medium text-slate-900">
                    {{ e.title }}
                    <span v-if="e.actor" class="text-xs font-normal text-slate-500">• {{ e.actor }}</span>
                  </div>
                  <div class="text-xs text-slate-500">{{ fmt(e.at) }}</div>
                </div>
                <div v-if="e.subtitle" class="mt-1 text-sm text-slate-700">{{ e.subtitle }}</div>
              </div>
            </div>
            <div v-if="!timeline.length" class="text-sm text-slate-500">{{ t('trip_detail.timeline.empty') }}</div>
          </div>
        </Card>

        <Card :title="t('trip_detail.route_tracking.title')">
          <div class="rounded-xl border bg-slate-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="text-sm text-slate-700">
                <div class="font-medium text-slate-900">{{ t('trip_detail.route_tracking.route') }}</div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ originLabel }} → {{ destinationLabel }}
                </div>
              </div>
              <a
                v-if="mapsHref"
                :href="mapsHref"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center justify-center rounded-md border bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
              >
                {{ t('trip_detail.route_tracking.open_map') }}
              </a>
            </div>
            <div class="mt-4 h-44 w-full rounded-lg bg-gradient-to-br from-emerald-100 via-sky-100 to-indigo-100"></div>
            <div class="mt-2 text-xs text-slate-500">
              {{ t('trip_detail.route_tracking.hint_no_gps') }}
            </div>
          </div>
        </Card>

        <Card ref="opsEl" :title="t('trip_detail.ops.title')">
          <div class="rounded-xl border bg-emerald-50/50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white">
                  <span class="text-sm font-semibold">↻</span>
                </div>
                <div>
                  <div class="text-sm font-semibold text-slate-900">{{ t('trip_detail.ops.subtitle') }}</div>
                  <div class="text-xs text-slate-600">{{ t('trip_detail.ops.hint') }}</div>
                </div>
              </div>
              <span
                class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200"
              >
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ t('trip_detail.ops.active') }}
              </span>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
              <div class="rounded-xl border bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-2">
                    <div class="text-sm font-semibold text-slate-900">{{ t('trip_detail.ops.adjust_vehicle.title') }}</div>
                  </div>
                  <button type="button" class="text-xs font-semibold text-emerald-700 hover:underline" @click="openOpsPanel('vehicle')">
                    {{ t('trip_detail.ops.change') }}
                  </button>
                </div>
                <div class="mt-3 grid gap-2 text-sm">
                  <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ t('trip_detail.ops.adjust_vehicle.current') }}</span>
                    <span class="font-medium text-slate-900">{{ trip.vehicle?.license_plate ?? '-' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ t('trip_detail.ops.adjust_vehicle.type') }}</span>
                    <span class="font-medium text-slate-900">{{ trip.vehicle?.type ?? '-' }}</span>
                  </div>
                </div>
                <div class="mt-3">
                  <button
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
                    @click="openOpsPanel('vehicle')"
                  >
                    {{ t('trip_detail.ops.adjust_vehicle.assign_other') }}
                  </button>
                </div>
              </div>

              <div class="rounded-xl border bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="text-sm font-semibold text-slate-900">{{ t('trip_detail.ops.adjust_driver.title') }}</div>
                  <button type="button" class="text-xs font-semibold text-emerald-700 hover:underline" @click="openOpsPanel('driver')">
                    {{ t('trip_detail.ops.change') }}
                  </button>
                </div>
                <div class="mt-3 grid gap-2 text-sm">
                  <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ t('trip_detail.ops.adjust_driver.driver') }}</span>
                    <span class="font-medium text-slate-900">{{ trip.driver?.full_name ?? '-' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-slate-500">{{ t('trip_detail.ops.adjust_driver.rating') }}</span>
                    <span class="font-medium text-slate-900">—</span>
                  </div>
                </div>
                <div class="mt-3">
                  <button
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100"
                    @click="openOpsPanel('driver')"
                  >
                    {{ t('trip_detail.ops.adjust_driver.assign_other') }}
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-3">
              <button type="button" class="rounded-xl border bg-white p-4 text-left hover:bg-slate-50" @click="openOpsPanel('time')">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-700">⏱</div>
                <div class="mt-3 text-sm font-semibold text-slate-900 text-center">{{ t('trip_detail.ops.tiles.time.title') }}</div>
                <div class="mt-1 text-xs text-slate-500 text-center">{{ t('trip_detail.ops.tiles.time.subtitle') }}</div>
              </button>
              <button type="button" class="rounded-xl border bg-white p-4 text-left hover:bg-slate-50" @click="openOpsPanel('route')">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-700">🧭</div>
                <div class="mt-3 text-sm font-semibold text-slate-900 text-center">{{ t('trip_detail.ops.tiles.route.title') }}</div>
                <div class="mt-1 text-xs text-slate-500 text-center">{{ t('trip_detail.ops.tiles.route.subtitle') }}</div>
              </button>
              <button type="button" class="rounded-xl border bg-white p-4 text-left hover:bg-slate-50" @click="openOpsPanel('passengers')">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sky-700">👥</div>
                <div class="mt-3 text-sm font-semibold text-slate-900 text-center">{{ t('trip_detail.ops.tiles.passengers.title') }}</div>
                <div class="mt-1 text-xs text-slate-500 text-center">{{ t('trip_detail.ops.tiles.passengers.subtitle') }}</div>
              </button>
            </div>

            <div v-if="resourceHint" class="mt-3 text-xs text-amber-800">{{ resourceHint }}</div>

            <div v-if="opsPanel !== 'none'" class="mt-4 rounded-xl border bg-white p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="text-sm font-semibold text-slate-900">{{ opsPanelTitle }}</div>
                <button type="button" class="text-xs font-semibold text-slate-600 hover:underline" @click="openOpsPanel('none')">
                  {{ t('trip_detail.ops.close') }}
                </button>
              </div>

              <div v-if="opsPanel === 'vehicle' || opsPanel === 'driver'" class="mt-3 grid gap-3 lg:grid-cols-2">
                <Select v-model="driverChoice" :label="t('trip_detail.ops.form.driver')" :placeholder="t('trip_detail.ops.form.pick_driver')">
                  <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                  <option v-for="d in drivers" :key="d.id" :value="String(d.id)">
                    {{ d.full_name }} {{ d.phone ? `· ${d.phone}` : '' }}
                  </option>
                </Select>
                <Select
                  v-model="vehicleChoice"
                  :label="t('trip_detail.ops.form.vehicle')"
                  :placeholder="t('trip_detail.ops.form.pick_vehicle')"
                  :disabled="!driverChoice"
                  :hint="!driverChoice ? t('trip_detail.ops.form.pick_driver_first') : ''"
                >
                  <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                  <option v-for="v in vehicles" :key="v.id" :value="String(v.id)">
                    {{ v.license_plate }} · {{ v.type ?? 'xe' }} {{ v.seat_count ? `(${v.seat_count} chỗ)` : '' }}
                  </option>
                </Select>
                <div class="lg:col-span-2">
                  <Input v-model.number="assign.transport_provider_id" :label="t('trip_detail.ops.form.provider_id_optional')" type="number" />
                </div>
                <div class="lg:col-span-2 flex items-center gap-3">
                  <Button :loading="assigning" type="button" @click="doAssign">{{ t('trip_detail.ops.form.assign') }}</Button>
                  <span v-if="assignMsg" class="text-sm text-slate-600">{{ assignMsg }}</span>
                </div>
              </div>

              <div v-else-if="opsPanel === 'time'" class="mt-3 text-sm text-slate-600">
                {{ t('trip_detail.ops.panels.coming_soon') }}
              </div>
              <div v-else-if="opsPanel === 'route'" class="mt-3 text-sm text-slate-600">
                {{ t('trip_detail.ops.panels.coming_soon') }}
              </div>
              <div v-else-if="opsPanel === 'passengers'" class="mt-3 text-sm text-slate-600">
                {{ t('trip_detail.ops.panels.coming_soon') }}
              </div>
            </div>
          </div>
        </Card>

        <Card :title="t('trip_detail.status_update.title')">
          <form class="grid gap-3 sm:grid-cols-3" @submit.prevent="doStatus">
            <Select v-model="statusForm.status" :label="t('trip_detail.status_update.status')" :placeholder="t('trip_detail.status_update.pick')">
              <option value="driver_confirmed">{{ labelTripStatus('driver_confirmed') }}</option>
              <option value="in_progress">{{ labelTripStatus('in_progress') }}</option>
              <option value="completed">{{ labelTripStatus('completed') }}</option>
              <option value="cancelled">{{ labelTripStatus('cancelled') }}</option>
            </Select>
            <div class="sm:col-span-2">
              <Input v-model="statusForm.message" :label="t('trip_detail.status_update.note')" />
            </div>
            <div class="sm:col-span-3 flex items-center gap-3">
              <Button :loading="statusing" type="submit">{{ t('trip_detail.status_update.update') }}</Button>
            </div>
          </form>
        </Card>

        <Card :title="t('trip_detail.costs.title')">
          <div class="space-y-2 text-sm">
            <div v-for="c in trip.costs ?? []" :key="c.id" class="flex justify-between border-b py-2">
              <span class="min-w-0 truncate">{{ c.type }} • {{ c.status }}</span>
              <span class="font-medium">{{ c.amount }} {{ c.currency }}</span>
            </div>
            <div v-if="!(trip.costs ?? []).length" class="text-slate-500">{{ t('trip_detail.costs.empty') }}</div>
          </div>
        </Card>
      </div>

      <div class="space-y-4">
        <Card :title="t('trip_detail.info.title')">
          <div class="space-y-3 text-sm">
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.info.trip_id') }}</div>
              <div class="font-medium text-slate-900">{{ tripCode }}</div>
            </div>
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.info.passengers') }}</div>
              <div class="font-medium text-slate-900">{{ passengerLabel }}</div>
            </div>
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.info.trip_type') }}</div>
              <div class="font-medium text-slate-900">{{ tripTypeLabel }}</div>
            </div>
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.info.requested_by') }}</div>
              <div class="min-w-0 text-right">
                <div class="truncate font-medium text-slate-900">{{ requesterName }}</div>
                <a v-if="requesterPhone" class="text-xs text-slate-500 underline" :href="`tel:${requesterPhone}`">
                  {{ requesterPhone }}
                </a>
              </div>
            </div>

            <div v-if="sla" class="pt-2">
              <div class="flex items-center justify-between text-xs text-slate-500">
                <span>{{ t('trip_detail.info.sla') }}</span>
                <span :class="['font-medium', sla.isCritical ? 'text-rose-700' : 'text-emerald-700']">
                  {{ sla.label }}
                </span>
              </div>
              <div class="mt-2 h-2 w-full rounded-full bg-slate-100">
                <div class="h-2 rounded-full" :class="sla.isCritical ? 'bg-rose-500' : 'bg-emerald-500'" :style="{ width: `${sla.progress}%` }"></div>
              </div>
            </div>
          </div>
        </Card>

        <Card :title="t('trip_detail.assigned.title')">
          <div class="space-y-3 text-sm">
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.assigned.vehicle') }}</div>
              <div class="font-medium text-slate-900">{{ trip.vehicle?.license_plate ?? '-' }}</div>
            </div>
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.assigned.driver') }}</div>
              <div class="min-w-0 text-right">
                <div class="truncate font-medium text-slate-900">{{ trip.driver?.full_name ?? '-' }}</div>
                <a v-if="trip.driver?.phone" class="text-xs text-slate-500 underline" :href="`tel:${trip.driver.phone}`">
                  {{ trip.driver.phone }}
                </a>
              </div>
            </div>
            <div class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ t('trip_detail.assigned.provider') }}</div>
              <div class="font-medium text-slate-900">{{ trip.transport_provider?.name ?? '-' }}</div>
            </div>
          </div>
        </Card>

        <Card :title="t('trip_detail.notes.title')">
          <div class="space-y-3">
            <div v-if="trip.dispatch_request?.notes" class="rounded-lg border bg-amber-50 p-3 text-sm text-amber-900">
              <div class="text-xs font-medium text-amber-800">{{ t('trip_detail.notes.from_request') }}</div>
              <div class="mt-1 whitespace-pre-wrap">{{ trip.dispatch_request.notes }}</div>
            </div>

            <div class="space-y-2">
              <div v-for="n in noteEvents" :key="n.id" class="rounded-lg border bg-white p-3">
                <div class="flex items-baseline justify-between gap-2">
                  <div class="text-xs font-medium text-slate-700">{{ n.creator?.name ?? t('trip_detail.timeline.system') }}</div>
                  <div class="text-xs text-slate-400">{{ fmt(n.created_at) }}</div>
                </div>
                <div class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ n.message }}</div>
              </div>
              <div v-if="!noteEvents.length" class="text-sm text-slate-500">{{ t('trip_detail.notes.empty') }}</div>
            </div>

            <div class="pt-2">
              <div class="text-sm font-semibold text-slate-900">{{ t('trip_detail.notes.add_title') }}</div>
              <textarea
                v-model="newNote"
                rows="3"
                class="mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring"
                :placeholder="t('trip_detail.notes.placeholder')"
              ></textarea>
              <div class="mt-2 flex items-center gap-2">
                <Button :loading="noting" :disabled="!newNote.trim()" @click="addNote">{{ t('trip_detail.notes.add_action') }}</Button>
                <span v-if="noteMsg" class="text-sm text-slate-600">{{ noteMsg }}</span>
              </div>
            </div>
          </div>
        </Card>

        <Card :title="t('trip_detail.quick.title')">
          <div class="grid gap-2">
            <a
              v-if="requesterPhone"
              class="inline-flex items-center justify-center rounded-md bg-va-800 px-3 py-2 text-sm font-medium text-white hover:bg-va-900"
              :href="`tel:${requesterPhone}`"
            >
              {{ t('trip_detail.quick.call_requester') }}
            </a>
            <a
              v-if="trip.driver?.phone"
              class="inline-flex items-center justify-center rounded-md border bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
              :href="`tel:${trip.driver.phone}`"
            >
              {{ t('trip_detail.quick.call_driver') }}
            </a>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-md border bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
              @click="scrollToOps"
            >
              {{ t('trip_detail.quick.reassign') }}
            </button>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { addTripEvent, assignTrip, getTrip, updateTripStatus } from '../../api/trips'
import { listVehicles, listDrivers } from '../../api/operational'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripStatus, labelTripType } from '../../util/labels'
const route = useRoute()
const { t, locale } = useI18n()
const trip = ref(null)
const loading = ref(true)
const assigning = ref(false)
const assignMsg = ref('')
const statusing = ref(false)
const vehicles = ref([])
const drivers = ref([])
const resourceHint = ref('')
const vehicleChoice = ref('')
const driverChoice = ref('')
const opsEl = ref(null)
const linkMsg = ref('')

const newNote = ref('')
const noting = ref(false)
const noteMsg = ref('')

const assign = ref({ lock_version: 0, vehicle_id: null, driver_id: null, transport_provider_id: null })
const opsPanel = ref('none') // none | vehicle | driver | time | route | passengers

function fmt(v) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleString(l) : '-'
}

const tripCode = computed(() => {
  const id = trip.value?.id
  if (!id) return 'TRP-—'
  return `TRP-${String(id).padStart(4, '0')}`
})

const originLabel = computed(() => trip.value?.dispatch_request?.origin ?? '—')
const destinationLabel = computed(() => trip.value?.dispatch_request?.destination ?? '—')
const currentLabel = computed(() => {
  if (trip.value?.status === 'in_progress') return t('trip_detail.current_location.en_route')
  return '—'
})

const passengerLabel = computed(() => {
  const c = trip.value?.dispatch_request?.passenger_count
  if (c == null || c === '') return '—'
  return `${c}`
})

const tripTypeLabel = computed(() => {
  return labelTripType(trip.value?.dispatch_request?.trip_type)
})

const requesterName = computed(() => trip.value?.dispatch_request?.requester?.name ?? '—')
const requesterPhone = computed(() => trip.value?.dispatch_request?.requester?.phone ?? '')

function pillClassForStatus(s) {
  const map = {
    pending: 'bg-slate-100 text-slate-700',
    approved: 'bg-sky-50 text-sky-700',
    assigned: 'bg-indigo-50 text-indigo-700',
    driver_confirmed: 'bg-amber-50 text-amber-700',
    in_progress: 'bg-emerald-50 text-emerald-700',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-rose-50 text-rose-700',
    incident: 'bg-rose-50 text-rose-700',
  }
  return map[s] ?? 'bg-slate-100 text-slate-700'
}

function dotClass(state) {
  const base = 'mt-0.5 h-3 w-3 flex-none rounded-full'
  if (state === 'done') return `${base} bg-emerald-500`
  if (state === 'active') return `${base} bg-amber-400`
  if (state === 'blocked') return `${base} bg-rose-400`
  return `${base} bg-slate-300`
}

const stepPickup = computed(() => {
  const s = trip.value?.status
  if (s === 'completed') return { state: 'done', label: t('trip_detail.step.completed') }
  if (s === 'in_progress') return { state: 'done', label: t('trip_detail.step.completed') }
  if (s === 'cancelled') return { state: 'blocked', label: t('trip_detail.step.cancelled') }
  return { state: 'active', label: t('trip_detail.step.pending') }
})
const stepCurrent = computed(() => {
  const s = trip.value?.status
  if (s === 'in_progress') return { state: 'active', label: t('trip_detail.step.en_route') }
  if (s === 'completed') return { state: 'done', label: t('trip_detail.step.arrived') }
  if (s === 'cancelled') return { state: 'blocked', label: t('trip_detail.step.cancelled') }
  return { state: 'pending', label: t('trip_detail.step.waiting') }
})
const stepDropoff = computed(() => {
  const s = trip.value?.status
  if (s === 'completed') return { state: 'done', label: t('trip_detail.step.completed') }
  if (s === 'cancelled') return { state: 'blocked', label: t('trip_detail.step.cancelled') }
  if (s === 'in_progress') return { state: 'active', label: t('trip_detail.step.pending') }
  return { state: 'pending', label: t('trip_detail.step.pending') }
})

const mapsHref = computed(() => {
  const o = originLabel.value
  const d = destinationLabel.value
  if (!o || !d || o === '—' || d === '—') return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
})

const noteEvents = computed(() => {
  const list = trip.value?.events ?? []
  return list
    .filter((e) => (e?.type ?? '') === 'note' && (e?.message ?? '').trim())
    .slice()
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

function eventIcon(type) {
  if (type === 'status_change') return '↻'
  if (type === 'note') return '✎'
  if (type === 'assign') return '⛟'
  return '•'
}

function eventTitle(e) {
  if (e.type === 'status_change') {
    const from = e?.data?.from
    const to = e?.data?.to
    if (from && to) return t('trip_detail.timeline.status_change', { from: labelTripStatus(from), to: labelTripStatus(to) })
    return t('trip_detail.timeline.status_change_short')
  }
  if (e.type === 'note') return t('trip_detail.timeline.dispatcher_note')
  return e.type || t('trip_detail.timeline.event')
}

const timeline = computed(() => {
  const items = []
  if (trip.value?.created_at) {
    items.push({
      key: `trip_created_${trip.value.id}`,
      icon: '+',
      title: t('trip_detail.timeline.trip_created'),
      subtitle: trip.value?.dispatch_request?.trip_type ? t('trip_detail.timeline.trip_created_subtitle', { type: tripTypeLabel.value }) : '',
      actor: t('trip_detail.timeline.system'),
      at: trip.value.created_at,
    })
  }
  const ev = trip.value?.events ?? []
  ev.forEach((e) => {
    items.push({
      key: `ev_${e.id}`,
      icon: eventIcon(e.type),
      title: eventTitle(e),
      subtitle: (e.message ?? '').trim(),
      actor: e.creator?.name ?? '',
      at: e.created_at,
    })
  })
  return items
    .filter((x) => x.at)
    .sort((a, b) => new Date(b.at) - new Date(a.at))
})

const sla = computed(() => {
  const arriveBy = trip.value?.arrive_by
  const departAt = trip.value?.depart_at
  const s = trip.value?.status
  if (!arriveBy || !departAt) return null
  if (['completed', 'cancelled'].includes(s)) return null
  const now = Date.now()
  const start = new Date(departAt).getTime()
  const end = new Date(arriveBy).getTime()
  if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) return null
  const remainingMs = end - now
  const remainingMin = Math.max(0, Math.floor(remainingMs / 60000))
  const progress = Math.max(0, Math.min(100, Math.round(((now - start) / (end - start)) * 100)))
  const isCritical = remainingMin <= 30
  const label = remainingMs <= 0 ? t('trip_detail.sla.overdue') : t('trip_detail.sla.remaining_minutes', { n: remainingMin })
  return { remainingMin, progress, isCritical, label }
})

function scrollToOps() {
  opsEl.value?.$el?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
}

function openOpsPanel(which) {
  opsPanel.value = which
  if (which === 'none') return
  // Ensure current values are reflected in selects
  driverChoice.value = trip.value?.driver_id ? String(trip.value.driver_id) : ''
  vehicleChoice.value = trip.value?.vehicle_id ? String(trip.value.vehicle_id) : ''
  scrollToOps()
}

const opsPanelTitle = computed(() => {
  const m = {
    none: '',
    vehicle: t('trip_detail.ops.panels.vehicle'),
    driver: t('trip_detail.ops.panels.driver'),
    time: t('trip_detail.ops.panels.time'),
    route: t('trip_detail.ops.panels.route'),
    passengers: t('trip_detail.ops.panels.passengers'),
  }
  return m[opsPanel.value] ?? ''
})

async function copyLink() {
  linkMsg.value = ''
  try {
    await navigator.clipboard.writeText(window.location.href)
    linkMsg.value = t('trip_detail.messages.copied')
  } catch {
    linkMsg.value = t('trip_detail.messages.copy_failed')
  }
}

function exportJson() {
  const blob = new Blob([JSON.stringify(trip.value, null, 2)], { type: 'application/json;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${tripCode.value}.json`
  a.click()
  URL.revokeObjectURL(url)
}

async function loadResources() {
  resourceHint.value = ''
  try {
    const [vr, dr] = await Promise.allSettled([
      listVehicles({ status: 'ready', per_page: 150 }),
      listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
    ])
    if (vr.status === 'fulfilled') {
      vehicles.value = vr.value.items ?? []
    } else {
      resourceHint.value = t('trip_detail.ops.messages.vehicles_load_failed')
    }
    if (dr.status === 'fulfilled') {
      drivers.value = dr.value.items ?? []
    } else {
      resourceHint.value =
        resourceHint.value ||
        t('trip_detail.ops.messages.drivers_load_failed')
    }
  } catch {
    resourceHint.value = t('trip_detail.ops.messages.resources_load_failed')
  }
}

async function load() {
  loading.value = true
  try {
    trip.value = await getTrip(route.params.id)
    assign.value.lock_version = trip.value.lock_version ?? 0
    vehicleChoice.value = trip.value.vehicle_id ? String(trip.value.vehicle_id) : ''
    driverChoice.value = trip.value.driver_id ? String(trip.value.driver_id) : ''
    await loadResources()
  } finally {
    loading.value = false
  }
}

watch(driverChoice, (v) => {
  if (!v) vehicleChoice.value = ''
})

async function doAssign() {
  assignMsg.value = ''
  assigning.value = true
  try {
    const payload = { lock_version: assign.value.lock_version }
    if (vehicleChoice.value) payload.vehicle_id = Number(vehicleChoice.value)
    if (driverChoice.value) payload.driver_id = Number(driverChoice.value)
    if (assign.value.transport_provider_id != null && assign.value.transport_provider_id !== '') {
      payload.transport_provider_id = Number(assign.value.transport_provider_id)
    }
    await assignTrip(route.params.id, payload, { idempotencyKey: newIdempotencyKey() })
    assignMsg.value = t('trip_detail.messages.ok')
    await load()
  } catch (e) {
    assignMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    assigning.value = false
  }
}

async function doStatus() {
  statusing.value = true
  try {
    await updateTripStatus(route.params.id, statusForm.value)
    await load()
  } finally {
    statusing.value = false
  }
}

async function addNote() {
  noteMsg.value = ''
  noting.value = true
  try {
    await addTripEvent(route.params.id, { type: 'note', message: newNote.value.trim() })
    newNote.value = ''
    noteMsg.value = t('trip_detail.messages.ok')
    await load()
  } catch (e) {
    noteMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    noting.value = false
  }
}

const statusForm = ref({ status: 'in_progress', message: '' })

onMounted(load)
watch(() => route.params.id, load)
</script>
