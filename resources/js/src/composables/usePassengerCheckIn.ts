import { passengerCheckIn, passengerUncheckIn } from '../api/trips'
import { isOptimisticLockConflict, isTripLockConflict, withTripLockVersion } from '../util/tripLock'
import { showAppError } from './appMessage'

export async function togglePassengerCheckInApi(options: {
  tripId: number
  passengerKey: string
  nextChecked: boolean
  lockVersion: number
  tripSnapshot?: Record<string, unknown> | null
  errorMessage: string
}): Promise<{ ok: boolean; lockConflict?: boolean; data?: Record<string, unknown> }> {
  const { tripId, passengerKey, nextChecked, lockVersion, tripSnapshot, errorMessage } = options
  const tripForLock = tripSnapshot ?? { lock_version: lockVersion }
  try {
    if (nextChecked) {
      const data = await passengerCheckIn(
        tripId,
        passengerKey,
        withTripLockVersion({ checked_in_at: new Date().toISOString() }, tripForLock),
      )
      return { ok: true, data: data as Record<string, unknown> }
    }
    const data = await passengerUncheckIn(
      tripId,
      passengerKey,
      withTripLockVersion({}, tripForLock),
    )
    return { ok: true, data: data as Record<string, unknown> }
  } catch (e) {
    if (isOptimisticLockConflict(e)) {
      return { ok: false, lockConflict: true }
    }
    if (isTripLockConflict(e)) {
      const msg =
        typeof (e as { response?: { data?: { message?: string } } })?.response?.data?.message ===
        'string'
          ? String((e as { response?: { data?: { message?: string } } }).response?.data?.message)
          : errorMessage
      showAppError(msg)
      return { ok: false }
    }
    showAppError(errorMessage)
    return { ok: false }
  }
}
