/**
 * ECharts options cho driver dashboard (dữ liệu client-side từ danh sách chuyến).
 */

import * as echarts from 'echarts'
import { emptyDashboardChartOption } from './transportDashboardCharts'

/** Empty donut — nền tối (DriverAnalyticsSection). */
function driverDonutEmptyOption(message) {
  return {
    animation: false,
    backgroundColor: 'transparent',
    graphic: [
      {
        type: 'text',
        left: 'center',
        top: 'middle',
        style: {
          text: message,
          fill: 'rgba(127,220,200,0.65)',
          fontSize: 13,
          fontWeight: 500,
          textAlign: 'center',
        },
      },
    ],
  }
}

export const DRIVER_PENDING_STATUS_SET = new Set([
  'assigned',
  'driver_confirmed',
  'pending',
  'approved',
])

export const DRIVER_DONUT_ORDER = ['completed', 'in_progress', 'overdue', 'pending', 'cancelled']

export const DRIVER_DONUT_COLORS = {
  completed: '#10b981',
  in_progress: '#f59e0b',
  overdue: '#f97316',
  pending: '#5eead4',
  cancelled: '#f43f5e',
}

function tripStatusNorm(trip) {
  return String(trip?.status ?? '')
    .trim()
    .toLowerCase()
}

function parseDepartMs(trip) {
  const iso = trip?.depart_at
  if (!iso) return null
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? null : d.getTime()
}

/** Đồng bộ với DriverAccountView: khởi hành trước 0h hôm nay (local). */
function startOfLocalTodayMs() {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  return d.getTime()
}

function driverTripDepartAtMs(trip) {
  const iso = trip?.depart_at ?? trip?.dispatch_request?.depart_at
  if (!iso) return NaN
  const t = new Date(iso).getTime()
  return Number.isFinite(t) ? t : NaN
}

function driverTripDepartYmdKey(trip) {
  const dd = trip?.depart_date
  if (dd && typeof dd === 'string' && /^\d{4}-\d{2}-\d{2}/.test(dd)) {
    return dd.slice(0, 10)
  }
  const iso = trip?.depart_at ?? trip?.dispatch_request?.depart_at
  if (!iso) return null
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? null : localYmd(d)
}

/** Giống DriverAccountView `isTripOverdueForStats`: chưa xong/hủy và đã qua ngày (local) khởi hành. */
function tripIsOverdueForDriverDonut(trip) {
  const st = tripStatusNorm(trip)
  if (st === 'completed' || st === 'cancelled') return false
  const ms = driverTripDepartAtMs(trip)
  if (Number.isFinite(ms)) return ms < startOfLocalTodayMs()
  const key = driverTripDepartYmdKey(trip)
  if (!key) return false
  return key < localYmd(new Date())
}

function localYmd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function parseYmdToLocalDate(s) {
  if (!s || typeof s !== 'string') return null
  const [y, m, d] = s.split('-').map(Number)
  if (!Number.isFinite(y) || !Number.isFinite(m) || !Number.isFinite(d)) return null
  const dt = new Date(y, m - 1, d)
  if (dt.getFullYear() !== y || dt.getMonth() !== m - 1 || dt.getDate() !== d) return null
  return dt
}

/**
 * Đếm nhóm hiển thị trên donut (trạng thái khác các nhóm trên không tính).
 * Mọi chuyến trễ (chưa hoàn thành/hủy, quá ngày khởi hành local) → `overdue`, khớp DriverAccountView & `overdue_count` API.
 */
export function computeDriverTripCounts(rawTrips) {
  let completed = 0
  let inProgress = 0
  let overdue = 0
  let pending = 0
  let cancelled = 0
  for (const trip of rawTrips ?? []) {
    const st = tripStatusNorm(trip)
    if (st === 'completed') completed++
    else if (st === 'cancelled') cancelled++
    else if (tripIsOverdueForDriverDonut(trip)) overdue++
    else if (st === 'in_progress') inProgress++
    else if (DRIVER_PENDING_STATUS_SET.has(st)) pending++
  }
  return {
    completed,
    in_progress: inProgress,
    overdue,
    pending,
    cancelled,
  }
}

const DRIVER_DONUT_PAGE_BG = '#0f1816'

/**
 * Donut trạng thái chuyến — slice theo DRIVER_DONUT_ORDER.
 * @param {{ countsByStatus?: object, labelMap?: object, emptyText?: string, centerSuffix?: string }} opts — centerSuffix ví dụ "chuyến"
 */
export function driverStatusDonutOption({
  countsByStatus,
  labelMap,
  emptyText,
  centerSuffix = '',
}) {
  const data = DRIVER_DONUT_ORDER.map((k) => ({
    value: Number(countsByStatus?.[k] ?? 0),
    name: labelMap?.[k] ?? k,
    itemStyle: { color: DRIVER_DONUT_COLORS[k] },
  })).filter((d) => d.value > 0)

  if (!data.length) {
    return driverDonutEmptyOption(emptyText ?? '—')
  }

  const total = data.reduce((acc, d) => acc + d.value, 0)
  const suffixLine = String(centerSuffix ?? '').trim()
  const centerText = suffixLine ? `${total}\n${suffixLine}` : String(total)

  return {
    animationDuration: 400,
    backgroundColor: 'transparent',
    tooltip: {
      trigger: 'item',
      confine: true,
      backgroundColor: 'rgba(15,24,22,0.96)',
      borderColor: 'rgba(127,220,200,0.25)',
      textStyle: { color: '#eaf8f5', fontSize: 12 },
      formatter: (p) => `${p.name}: ${p.value} (${p.percent}%)`,
    },
    legend: { show: false },
    graphic: [
      {
        type: 'text',
        left: 'center',
        top: '42%',
        style: {
          text: centerText,
          fill: '#f8fafc',
          font: '600 20px system-ui, ui-sans-serif, sans-serif',
          textAlign: 'center',
          lineHeight: 26,
        },
      },
    ],
    series: [
      {
        type: 'pie',
        radius: ['44%', '72%'],
        center: ['50%', '47%'],
        avoidLabelOverlap: true,
        itemStyle: {
          borderRadius: 8,
          borderColor: DRIVER_DONUT_PAGE_BG,
          borderWidth: 3,
        },
        label: {
          color: 'rgba(232,249,245,0.92)',
          fontSize: 11,
          formatter: '{b}\n{d}%',
        },
        emphasis: {
          scale: true,
          scaleSize: 8,
          itemStyle: { shadowBlur: 18, shadowColor: 'rgba(127,220,200,0.25)' },
        },
        data,
      },
    ],
  }
}

function buildTrendBuckets(rawTrips, period, customFrom, customTo) {
  const now = new Date()

  if (period === 'today') {
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const endMs = start.getTime() + 86400000
    const values = Array.from({ length: 24 }, () => 0)
    const categories = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`)
    for (const trip of rawTrips ?? []) {
      const ms = parseDepartMs(trip)
      if (ms == null || ms < start.getTime() || ms >= endMs) continue
      values[new Date(ms).getHours()] += 1
    }
    return { categories, values }
  }

  let startDay
  let endDay

  if (period === '7d') {
    endDay = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    startDay = new Date(endDay)
    startDay.setDate(startDay.getDate() - 6)
  } else if (period === '30d') {
    endDay = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    startDay = new Date(endDay)
    startDay.setDate(startDay.getDate() - 29)
  } else if (period === 'custom') {
    const a = parseYmdToLocalDate(customFrom)
    const b = parseYmdToLocalDate(customTo)
    if (!a || !b || a.getTime() > b.getTime()) {
      return { categories: [], values: [] }
    }
    startDay = new Date(a.getFullYear(), a.getMonth(), a.getDate())
    endDay = new Date(b.getFullYear(), b.getMonth(), b.getDate())
  } else {
    return { categories: [], values: [] }
  }

  const ymds = []
  const categories = []
  const values = []
  const cur = new Date(startDay.getFullYear(), startDay.getMonth(), startDay.getDate())
  const end = new Date(endDay.getFullYear(), endDay.getMonth(), endDay.getDate())

  while (cur.getTime() <= end.getTime()) {
    ymds.push(localYmd(cur))
    categories.push(
      cur.toLocaleDateString('vi-VN', { day: 'numeric', month: 'short' }),
    )
    values.push(0)
    cur.setDate(cur.getDate() + 1)
  }

  const idxByYmd = new Map(ymds.map((k, i) => [k, i]))
  const startMs = new Date(startDay.getFullYear(), startDay.getMonth(), startDay.getDate()).getTime()
  const endExclusive = end.getTime() + 86400000

  for (const trip of rawTrips ?? []) {
    const ms = parseDepartMs(trip)
    if (ms == null || ms < startMs || ms >= endExclusive) continue
    const idx = idxByYmd.get(localYmd(new Date(ms)))
    if (idx != null) values[idx] += 1
  }

  return { categories, values }
}

/**
 * Xu hướng số chuyến theo giờ (today) hoặc theo ngày (7d/30d/custom).
 */
export function driverTrendOption({
  rawTrips,
  period,
  customFrom,
  customTo,
  emptyText,
  yAxisName,
  tripsSuffix = '',
  lineColor = '#2563eb',
}) {
  const { categories, values } = buildTrendBuckets(rawTrips, period, customFrom, customTo)
  const total = values.reduce((a, b) => a + Number(b ?? 0), 0)
  if (!categories.length || total <= 0) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }

  const showSymbols = values.length <= 14
  const suffix = (tripsSuffix && String(tripsSuffix).trim()) || ''

  return {
    animationDuration: 450,
    tooltip: {
      trigger: 'axis',
      confine: true,
      axisPointer: { type: 'line', lineStyle: { color: '#818cf8', width: 1 } },
      formatter(params) {
        const p = Array.isArray(params) ? params[0] : params
        if (!p) return ''
        const n = Number(p.value ?? 0)
        const unit = suffix ? ` ${suffix}` : ''
        return `${p.name}<br/>${n}${unit}`
      },
    },
    grid: { left: 8, right: 10, top: 32, bottom: 28, containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: categories,
      axisLabel: {
        color: '#64748b',
        fontSize: period === 'today' ? 9 : 10,
        interval: period === 'today' ? 3 : undefined,
      },
      axisLine: { lineStyle: { color: '#e2e8f0' } },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      name: yAxisName ?? '',
      nameTextStyle: { color: '#64748b', fontSize: 10 },
      axisLabel: { color: '#64748b', fontSize: 10 },
      splitLine: { lineStyle: { color: '#f1f5f9' } },
    },
    series: [
      {
        type: 'line',
        smooth: 0.3,
        symbol: 'circle',
        symbolSize: showSymbols ? 5 : 0,
        showSymbol: showSymbols,
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(37,99,235,0.32)' },
            { offset: 1, color: 'rgba(37,99,235,0.04)' },
          ]),
        },
        lineStyle: { width: 2, color: lineColor },
        itemStyle: { color: lineColor },
        data: values,
      },
    ],
  }
}
