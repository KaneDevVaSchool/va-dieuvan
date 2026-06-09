import { passengerCheckIn, passengerUncheckIn } from '../api/trips'
import { isTripLockConflict } from '../util/tripLock'
import { showAppError } from './appMessage'

export async function togglePassengerCheckInApi(options: {
  tripId: number
  passengerKey: string
  nextChecked: boolean
  lockVersion: number
  errorMessage: string
}): Promise<{ ok: boolean; lockConflict?: boolean; data?: Record<string, unknown> }> {
  const { tripId, passengerKey, nextChecked, lockVersion, errorMessage } = options
  try {
    if (nextChecked) {
      const data = await passengerCheckIn(tripId, passengerKey, {
        checked_in_at: new Date().toISOString(),
        lock_version: lockVersion,
      })
      return { ok: true, data: data as Record<string, unknown> }
    }
    const data = await passengerUncheckIn(tripId, passengerKey, { lock_version: lockVersion })
    return { ok: true, data: data as Record<string, unknown> }
  } catch (e) {
    if (isTripLockConflict(e)) {
      return { ok: false, lockConflict: true }
    }
    showAppError(errorMessage)
    return { ok: false }
  }
}
