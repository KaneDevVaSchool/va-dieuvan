/**
 * Unit checks cho util chặng lịch tài xế (chạy: npm run test:driver-schedule-leg).
 */
import assert from 'node:assert/strict'
import {
  buildDriverTripStatusFields,
  driverTripListRowKey,
  mergeTripRowForStatusAction,
  resolveDispatchTripApiId,
  resolveDriverScheduleKey,
  tripHasMultipleScheduleLegs,
} from '../resources/js/src/util/driverScheduleLeg.js'
import {
  expandTripsForDriverScheduleList,
  expandTripsForPendingConfirmation,
} from '../resources/js/src/composables/driverScheduleExpand.js'

function ok(cond, msg) {
  assert.ok(cond, msg)
}

// --- resolveDispatchTripApiId ---
ok(resolveDispatchTripApiId({ id: '32:morning', trip_id: 32 }) === 32, 'composite + trip_id')
ok(resolveDispatchTripApiId({ id: '32:afternoon' }) === 32, 'composite id head')
ok(resolveDispatchTripApiId({ id: 32 }) === 32, 'numeric id')
ok(resolveDispatchTripApiId({ id: 'tp-1-morning' }) === null, 'tp synthetic id')

// --- schedule_key ---
const multi = {
  id: 32,
  schedule_legs: [
    { key: 'morning', status: 'driver_confirmed', assignment: { driver_id: 9 } },
    { key: 'afternoon', status: 'assigned', assignment: { driver_id: 9 } },
  ],
}
ok(tripHasMultipleScheduleLegs(multi), 'multi legs detected')
const startFields = buildDriverTripStatusFields('in_progress', multi, 9)
ok(startFields.schedule_key === 'morning', 'operational leg = morning confirmed')
ok(
  resolveDriverScheduleKey({ ...multi, schedule_leg_key: 'afternoon' }, 9) === 'afternoon',
  'explicit schedule_leg_key wins',
)

const single = { schedule_legs: [{ key: 'only', status: 'driver_confirmed' }] }
ok(!tripHasMultipleScheduleLegs(single), 'single leg not multi')
ok(buildDriverTripStatusFields('in_progress', single, 1).schedule_key === undefined, 'no key single leg')

// --- merge row ---
const listRow = {
  id: 32,
  lock_version: 3,
  schedule_legs: multi.schedule_legs,
  status: 'assigned',
}
const hint = expandTripsForDriverScheduleList([listRow]).find((r) => r.schedule_leg_key === 'morning')
ok(hint?.id === '32:morning', 'schedule list expands id')
const merged = mergeTripRowForStatusAction(hint, (id) => (id === 32 ? listRow : null))
ok(merged.lock_version === 3, 'merge keeps lock_version from list')
ok(merged.schedule_leg_key === 'morning', 'merge keeps leg key')

// --- pending confirmation ---
const pendingTrip = {
  id: 40,
  status: 'assigned',
  schedule_legs: [
    { key: 'a', status: 'assigned' },
    { key: 'b', status: 'driver_confirmed' },
  ],
}
const pendingRows = expandTripsForPendingConfirmation([pendingTrip])
ok(pendingRows.length === 1, 'only assigned leg in banner')
ok(pendingRows[0].schedule_leg_key === 'a', 'pending leg key')

// --- row key ---
ok(driverTripListRowKey({ id: 1, calendar_key: '1:morning' }) === '1:morning', 'calendar_key priority')

// --- dispatch can start (mirror useTpDriverSlotActions) ---
function dispatchCanStart(trip) {
  const st = String(trip?.status ?? '').trim().toLowerCase()
  return ['driver_confirmed', 'assigned'].includes(st)
}
ok(dispatchCanStart({ status: 'driver_confirmed' }), 'can start driver_confirmed')
ok(!dispatchCanStart({ status: 'approved' }), 'cannot start approved alone')
ok(dispatchCanStart({ status: 'assigned' }), 'can start assigned')

console.log('test:driver-schedule-leg — all passed')
