import { passengerCheckIn, passengerUncheckIn } from '../api/trips'
import { showAppError } from './appMessage'

export async function togglePassengerCheckInApi(options: {
  tripId: number
  passengerKey: string
  nextChecked: boolean
  errorMessage: string
}): Promise<{ ok: boolean; data?: Record<string, unknown> }> {
  const { tripId, passengerKey, nextChecked, errorMessage } = options
  try {
    if (nextChecked) {
      const data = await passengerCheckIn(tripId, passengerKey, {
        checked_in_at: new Date().toISOString(),
      })
      return { ok: true, data: data as Record<string, unknown> }
    }
    const data = await passengerUncheckIn(tripId, passengerKey)
    return { ok: true, data: data as Record<string, unknown> }
  } catch {
    showAppError(errorMessage)
    return { ok: false }
  }
}
