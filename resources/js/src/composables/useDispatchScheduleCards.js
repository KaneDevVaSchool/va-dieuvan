import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  isPassengerRowFilled,
  isBusinessRowFilled,
  isCargoRowFilled,
} from './dispatchWizardConstants'
import { parseMoneyVnd } from '../util/money'
import { wizardSnapshotGuestTotal } from '../util/dispatchRequestPassengers'

function rowLineTotal(row) {
  return parseMoneyVnd(row?.unit_price) + parseMoneyVnd(row?.extra_fee)
}

/**
 * Build schedule cards from wizard_snapshot (same keys as backend: passenger:0, business:1, cargo:0).
 *
 * @param {import('vue').MaybeRefOrGetter<object|null|undefined>} snapshot
 * @param {import('vue').MaybeRefOrGetter<string>} tripType
 */
export function useDispatchScheduleCards(snapshot, tripType) {
  const { t, locale } = useI18n()

  const localeTag = computed(() => (locale.value === 'en' ? 'en-US' : 'vi-VN'))

  function formatShortDt(val) {
    if (!val) return '—'
    try {
      const d = new Date(val)
      if (Number.isNaN(d.getTime())) return '—'
      return new Intl.DateTimeFormat(localeTag.value, {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      }).format(d)
    } catch {
      return '—'
    }
  }

  const scheduleCards = computed(() => {
    const s = unref(snapshot) ?? {}
    const tt = unref(tripType) ?? ''
    const cards = []
    let seq = 0

    if (tt === 'cargo') {
      for (const [idx, row] of (s.cargoRows ?? []).entries()) {
        if (!isCargoRowFilled(row)) continue
        seq += 1
        const key = `cargo:${idx}`
        cards.push({
          key,
          variant: 'cargo',
          rowIndex: idx,
          labelSeq: seq,
          heading: t('dispatch_wizard.confirm.cargo_trip_heading', { n: seq }),
          depart_at: row.pickup_at || null,
          arrive_by: row.delivery_at || null,
          pickup: (row.pickup_place ?? '').trim(),
          dropoff: (row.delivery_place ?? '').trim(),
          waypoint: '',
          lines: [
            {
              label: t('dispatch_wizard.s3.cargo_name'),
              value: row.name?.trim() || '—',
            },
            {
              label: t('dispatch_wizard.confirm.lbl_pickup'),
              value: `${formatShortDt(row.pickup_at)} — ${row.pickup_place?.trim() || '—'}`,
            },
            {
              label: t('dispatch_wizard.confirm.lbl_delivery'),
              value: `${formatShortDt(row.delivery_at)} — ${row.delivery_place?.trim() || '—'}`,
            },
          ],
        })
      }
      return cards
    }

    if (tt !== 'business') {
      for (const [idx, row] of (s.passengerRows ?? []).entries()) {
        if (!isPassengerRowFilled(row)) continue
        seq += 1
        const key = `passenger:${idx}`
        cards.push({
          key,
          variant: 'passenger',
          rowIndex: idx,
          labelSeq: seq,
          heading: t('dispatch_wizard.confirm.trip_heading', { n: seq }),
          depart_at: row.depart_at || null,
          arrive_by: row.return_at || null,
          pickup: (row.pickup ?? '').trim(),
          dropoff: (row.dropoff ?? '').trim(),
          waypoint: (row.waypoint ?? '').trim(),
          lines: [
            {
              label: t('dispatch_wizard.confirm.lbl_out'),
              value: `${formatShortDt(row.depart_at)} — ${row.pickup?.trim() || '—'}`,
            },
            {
              label: t('dispatch_wizard.confirm.lbl_back'),
              value: `${formatShortDt(row.return_at)} — ${row.dropoff?.trim() || '—'}`,
            },
            {
              lineKey: 'guests_per_leg',
              label: t('trip_detail.schedules.guests_per_leg'),
              value: String(row.guests ?? '').trim() || '—',
            },
          ],
        })
      }
    }

    if (tt !== 'point_to_point') {
      for (const [idx, row] of (s.businessRows ?? []).entries()) {
        if (!isBusinessRowFilled(row)) continue
        seq += 1
        const key = `business:${idx}`
        const lines = [
          {
            label: t('dispatch_wizard.confirm.lbl_out'),
            value: `${formatShortDt(row.depart_at)} — ${row.pickup?.trim() || '—'}`,
          },
          {
            label: t('dispatch_wizard.confirm.lbl_back'),
            value: `${formatShortDt(row.return_at)} — ${row.dropoff?.trim() || '—'}`,
          },
          {
            lineKey: 'guests_per_leg',
            label: t('trip_detail.schedules.guests_per_leg'),
            value: String(row.guests ?? '').trim() || '—',
          },
        ]
        if (row.waypoint?.trim()) {
          lines.splice(2, 0, {
            label: t('dispatch_wizard.s3.waypoint_col'),
            value: row.waypoint.trim(),
          })
        }
        cards.push({
          key,
          variant: 'business',
          rowIndex: idx,
          labelSeq: seq,
          heading: t('dispatch_wizard.confirm.business_trip_heading', { n: seq }),
          depart_at: row.depart_at || null,
          arrive_by: row.return_at || null,
          pickup: (row.pickup ?? '').trim(),
          dropoff: (row.dropoff ?? '').trim(),
          waypoint: (row.waypoint ?? '').trim(),
          lines,
        })
      }
    }

    return cards
  })

  const scheduleCount = computed(() => scheduleCards.value.length)

  const totalGuests = computed(() =>
    wizardSnapshotGuestTotal(unref(snapshot) ?? {}, unref(tripType) ?? ''),
  )

  return { scheduleCards, scheduleCount, totalGuests, formatShortDt }
}
