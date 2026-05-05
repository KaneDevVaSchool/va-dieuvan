import { type MaybeRefOrGetter, ref, toValue } from 'vue'
import { fetchDriverWorkloadDetail, fetchDriversWorkload } from '../api/operational'

export interface DriverWorkload {
  id: number
  trips_this_week: number
  trips_today: number
  total_hours: number
  load_score: number
  load_level: 'low' | 'medium' | 'high'
  last_trip_end: string | null
  is_resting: boolean
}

export interface WorkloadChartDay {
  date: string
  day_label: string
  trip_count: number
  hours: number
}

export interface DriverWorkloadDetailPayload {
  driver_id: number
  chart: WorkloadChartDay[]
  upcoming: Array<{
    id: number
    scheduled_at: string
    scheduled_end_at: string
    trip_code: string
  }>
}

function isoWeekBounds(tripDateYmd: string): { date_from: string; date_to: string } {
  const d = new Date(`${tripDateYmd}T12:00:00`)
  if (Number.isNaN(d.getTime())) {
    const t = new Date()
    const y = t.getFullYear()
    const m = String(t.getMonth() + 1).padStart(2, '0')
    const day = String(t.getDate()).padStart(2, '0')
    return isoWeekBounds(`${y}-${m}-${day}`)
  }
  const dow = d.getDay() || 7
  const monday = new Date(d)
  monday.setDate(d.getDate() - (dow - 1))
  const sunday = new Date(monday)
  sunday.setDate(monday.getDate() + 6)
  const fmt = (x: Date) =>
    `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`
  return { date_from: fmt(monday), date_to: fmt(sunday) }
}

export function useDriverWorkload(tripDate: MaybeRefOrGetter<string>) {
  const workloadMap = ref<Record<string, DriverWorkload>>({})
  const detailCache = ref<Record<number, DriverWorkloadDetailPayload>>({})
  const isLoading = ref(false)
  const inflightDetail = new Map<number, Promise<DriverWorkloadDetailPayload>>()

  async function fetchWorkload() {
    const td = toValue(tripDate)
    if (!td) return
    isLoading.value = true
    try {
      const { date_from, date_to } = isoWeekBounds(td)
      const payload = await fetchDriversWorkload({
        date_from,
        date_to,
        trip_date: td,
      })
      workloadMap.value = (payload && typeof payload === 'object' ? payload : {}) as Record<
        string,
        DriverWorkload
      >
    } finally {
      isLoading.value = false
    }
  }

  async function fetchDetail(driverId: number): Promise<DriverWorkloadDetailPayload> {
    const cached = detailCache.value[driverId]
    if (cached) return cached
    const existing = inflightDetail.get(driverId)
    if (existing) return existing
    const p = (async () => {
      const data = (await fetchDriverWorkloadDetail(driverId)) as DriverWorkloadDetailPayload
      detailCache.value = { ...detailCache.value, [driverId]: data }
      return data
    })()
    inflightDetail.set(driverId, p)
    try {
      return await p
    } finally {
      inflightDetail.delete(driverId)
    }
  }

  function getWorkload(driverId: number): DriverWorkload | null {
    const w = workloadMap.value[String(driverId)]
    return w ?? null
  }

  function loadColor(level: string): { bg: string; text: string; bar: string } {
    return (
      {
        low: { bg: '#EAF3DE', text: '#27500A', bar: '#639922' },
        medium: { bg: '#FAEEDA', text: '#633806', bar: '#BA7517' },
        high: { bg: '#FCEBEB', text: '#791F1F', bar: '#E24B4A' },
      }[level] ?? { bg: '#f5f5f5', text: '#555', bar: '#aaa' }
    )
  }

  return {
    workloadMap,
    detailCache,
    isLoading,
    fetchWorkload,
    fetchDetail,
    getWorkload,
    loadColor,
  }
}
