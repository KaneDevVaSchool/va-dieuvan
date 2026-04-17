/**
 * ECharts — chỉ dữ liệu từ GET /api/reports/summary (production).
 */

import * as echarts from 'echarts'

const AXIS = {
  label: '#64748b',
  line: '#e2e8f0',
  split: '#f1f5f9',
}

const PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899']

/** Centered empty state so chart areas never look blank. */
export function emptyDashboardChartOption(message) {
  return {
    animation: false,
    graphic: [
      {
        type: 'group',
        left: 'center',
        top: 'middle',
        children: [
          {
            type: 'circle',
            shape: { cx: 0, cy: -8, r: 22 },
            style: { fill: '#f1f5f9', stroke: '#e2e8f0', lineWidth: 1 },
          },
          {
            type: 'text',
            y: 28,
            style: {
              text: message,
              fill: '#64748b',
              fontSize: 13,
              fontWeight: 500,
              textAlign: 'center',
            },
          },
        ],
      },
    ],
  }
}

export function sumTrips(tripsByStatus) {
  const o = tripsByStatus && typeof tripsByStatus === 'object' ? tripsByStatus : {}
  return Object.values(o).reduce((a, b) => a + Number(b ?? 0), 0)
}

/** @param {Record<string, number>|number[]|undefined|null} tripsByHour */
export function normalizeTripsByHour(tripsByHour) {
  const out = []
  for (let h = 0; h < 24; h++) {
    let n = 0
    if (Array.isArray(tripsByHour)) {
      n = Number(tripsByHour[h] ?? 0)
    } else if (tripsByHour && typeof tripsByHour === 'object') {
      n = Number(tripsByHour[h] ?? tripsByHour[String(h)] ?? 0)
    }
    out.push(n)
  }
  return out
}

export function tripsStatusDonutOption({ tripsByStatus, labelMap, emptyText }) {
  const entries = Object.entries(tripsByStatus ?? {}).filter(([, v]) => Number(v) > 0)
  const data = entries.map(([k, v], i) => ({
    value: Number(v),
    name: labelMap[k] ?? k,
    itemStyle: { color: PALETTE[i % PALETTE.length] },
  }))
  if (!data.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  return {
    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 11 } },
    series: [
      {
        type: 'pie',
        radius: ['46%', '72%'],
        center: ['50%', '46%'],
        avoidLabelOverlap: true,
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
        label: { color: '#334155', fontSize: 11 },
        data,
      },
    ],
  }
}

export function costsByTypeBarOption({ costsByType, formatMoney, emptyText }) {
  const entries = Object.entries(costsByType ?? {})
    .map(([k, v]) => [k, Number(v ?? 0)])
    .sort((a, b) => b[1] - a[1])
  const cats = entries.map(([k]) => k)
  const vals = entries.map(([, v]) => v)
  if (!cats.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter(params) {
        const p = params[0]
        return `${p.name}<br/>${formatMoney(p.value)}`
      },
    },
    grid: { left: 12, right: 8, top: 28, bottom: 28, containLabel: true },
    xAxis: {
      type: 'category',
      data: cats,
      axisLabel: { color: AXIS.label, rotate: cats.some((c) => c.length > 10) ? 28 : 0, fontSize: 10 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        color: AXIS.label,
        fontSize: 10,
        formatter(v) {
          if (v >= 1e9) return `${(v / 1e9).toFixed(1)}B`
          if (v >= 1e6) return `${(v / 1e6).toFixed(1)}M`
          if (v >= 1e3) return `${(v / 1e3).toFixed(0)}k`
          return String(v)
        },
      },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    series: [
      {
        type: 'bar',
        data: vals.map((v, i) => ({
          value: v,
          itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [4, 4, 0, 0] },
        })),
        barMaxWidth: 36,
      },
    ],
  }
}

export function providersHorizontalBarOption({ rows, formatMoney, maxItems = 12, emptyText }) {
  const list = (rows ?? []).slice(0, maxItems)
  if (!list.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  const names = list.map((r) => r.provider ?? '—').reverse()
  const vals = list.map((r) => Number(r.total_amount ?? 0)).reverse()
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter(params) {
        const p = params[0]
        return `${p.name}<br/>${formatMoney(p.value)}`
      },
    },
    grid: { left: 8, right: 16, top: 8, bottom: 8, containLabel: true },
    xAxis: {
      type: 'value',
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    yAxis: {
      type: 'category',
      data: names,
      axisLabel: { color: AXIS.label, fontSize: 10, width: 120, overflow: 'truncate' },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    series: [
      {
        type: 'bar',
        data: vals.map((v, i) => ({
          value: v,
          itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [0, 4, 4, 0] },
        })),
        barMaxWidth: 20,
      },
    ],
  }
}

export function tripsByHourLineOption({ counts24, t, emptyText }) {
  const total = (counts24 ?? []).reduce((a, b) => a + Number(b ?? 0), 0)
  if (total <= 0 && emptyText) {
    return emptyDashboardChartOption(emptyText)
  }
  const labels = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`)
  const maxV = Math.max(...counts24, 1)
  const showLabels = maxV <= 30
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 8, right: 12, top: 32, bottom: 24, containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: labels,
      axisLabel: { color: AXIS.label, fontSize: 9, interval: 2 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      name: t('hour_y'),
      nameTextStyle: { color: AXIS.label, fontSize: 10 },
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    series: [
      {
        type: 'line',
        smooth: 0.25,
        symbol: 'circle',
        symbolSize: showLabels ? 5 : 0,
        showSymbol: showLabels,
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(59,130,246,0.35)' },
            { offset: 1, color: 'rgba(59,130,246,0.05)' },
          ]),
        },
        lineStyle: { width: 2, color: '#2563eb' },
        itemStyle: { color: '#2563eb' },
        label: {
          show: showLabels,
          position: 'top',
          fontSize: 9,
          color: AXIS.label,
        },
        data: counts24,
      },
    ],
  }
}

export function fleetModeDonutOption({ tripsByFleetMode, labelMap, emptyText }) {
  const order = ['internal', 'vendor_hire', 'taxi', 'unspecified']
  const data = order
    .map((k, i) => ({
      value: Number(tripsByFleetMode?.[k] ?? 0),
      name: labelMap[k] ?? k,
      itemStyle: { color: PALETTE[i % PALETTE.length] },
    }))
    .filter((d) => d.value > 0)
  if (!data.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  return {
    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 11 } },
    series: [
      {
        type: 'pie',
        radius: ['42%', '68%'],
        center: ['50%', '44%'],
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
        label: { color: '#334155', fontSize: 11 },
        data,
      },
    ],
  }
}

export function statusDonutOption({ countsByStatus, labelMap, emptyText }) {
  const entries = Object.entries(countsByStatus ?? {}).filter(([, v]) => Number(v) > 0)
  const data = entries.map(([k, v], i) => ({
    value: Number(v),
    name: labelMap[k] ?? k,
    itemStyle: { color: PALETTE[i % PALETTE.length] },
  }))
  if (!data.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  return {
    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 11 } },
    series: [
      {
        type: 'pie',
        radius: ['42%', '68%'],
        center: ['50%', '44%'],
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
        label: { color: '#334155', fontSize: 10 },
        data,
      },
    ],
  }
}

export function costsPipelineBarOption({ costsByPipelineStatus, labelMap, formatMoney, emptyText }) {
  const order = ['draft', 'submitted', 'confirmed', 'rejected']
  const entries = order
    .map((k) => [k, Number(costsByPipelineStatus?.[k] ?? 0)])
    .filter(([, v]) => v > 0)
  if (!entries.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  const cats = entries.map(([k]) => labelMap[k] ?? k)
  const vals = entries.map(([, v]) => v)
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter(params) {
        const p = params[0]
        return `${p.name}<br/>${formatMoney(p.value)}`
      },
    },
    grid: { left: 8, right: 8, top: 20, bottom: entries.length > 3 ? 40 : 28, containLabel: true },
    xAxis: {
      type: 'value',
      axisLabel: {
        color: AXIS.label,
        fontSize: 10,
        formatter(v) {
          if (v >= 1e9) return `${(v / 1e9).toFixed(1)}B`
          if (v >= 1e6) return `${(v / 1e6).toFixed(1)}M`
          if (v >= 1e3) return `${(v / 1e3).toFixed(0)}k`
          return String(v)
        },
      },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    yAxis: {
      type: 'category',
      data: cats,
      axisLabel: { color: AXIS.label, fontSize: 10, width: 90, overflow: 'truncate' },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    series: [
      {
        type: 'bar',
        data: vals.map((v, i) => ({
          value: v,
          itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [0, 4, 4, 0] },
        })),
        barMaxWidth: 22,
      },
    ],
  }
}

export function topRequestersBarOption({ rows, tripsSuffix, emptyText }) {
  const list = (rows ?? []).slice(0, 15)
  if (!list.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  const names = list.map((r) => r.requester_label ?? '—').reverse()
  const vals = list.map((r) => Number(r.trip_count ?? 0)).reverse()
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter(params) {
        const p = params[0]
        return `${p.name}<br/>${p.value} ${tripsSuffix}`
      },
    },
    grid: { left: 8, right: 12, top: 8, bottom: 8, containLabel: true },
    xAxis: {
      type: 'value',
      minInterval: 1,
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    yAxis: {
      type: 'category',
      data: names,
      axisLabel: { color: AXIS.label, fontSize: 10, width: 100, overflow: 'truncate' },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    series: [
      {
        type: 'bar',
        data: vals.map((v, i) => ({
          value: v,
          itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [0, 4, 4, 0] },
        })),
        barMaxWidth: 18,
      },
    ],
  }
}

export function licensePlateTripsBarOption({ tripsByPlate, plateSuffix, emptyText }) {
  const entries = Object.entries(tripsByPlate ?? {})
    .map(([plate, c]) => [plate, Number(c ?? 0)])
    .filter(([, v]) => v > 0)
    .sort((a, b) => b[1] - a[1])
  if (!entries.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  const names = entries.map(([p]) => p).reverse()
  const vals = entries.map(([, v]) => v).reverse()
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter(params) {
        const p = params[0]
        return `${p.name}<br/>${p.value} ${plateSuffix}`
      },
    },
    grid: { left: 8, right: 12, top: 8, bottom: 8, containLabel: true },
    xAxis: {
      type: 'value',
      minInterval: 1,
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    yAxis: {
      type: 'category',
      data: names,
      axisLabel: { color: AXIS.label, fontSize: 10, width: 88, overflow: 'truncate' },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    series: [
      {
        type: 'bar',
        data: vals.map((v, i) => ({
          value: v,
          itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [0, 4, 4, 0] },
        })),
        barMaxWidth: 18,
      },
    ],
  }
}

export function tripsByTripTypeBarOption({ tripsByTripType, labelMap, emptyText }) {
  const entries = Object.entries(tripsByTripType ?? {})
    .map(([k, v]) => [k, Number(v ?? 0)])
    .filter(([, v]) => v > 0)
    .sort((a, b) => b[1] - a[1])
  if (!entries.length) {
    return emptyDashboardChartOption(emptyText ?? '—')
  }
  const cats = entries.map(([k]) => labelMap[k] ?? k)
  const vals = entries.map(([, v]) => v)
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: 8, right: 8, top: 16, bottom: 48, containLabel: true },
    xAxis: {
      type: 'category',
      data: cats,
      axisLabel: { color: AXIS.label, fontSize: 10, interval: 0, rotate: 22 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    series: [
      {
        type: 'bar',
        data: vals.map((v, i) => ({
          value: v,
          itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [4, 4, 0, 0] },
        })),
        barMaxWidth: 40,
      },
    ],
  }
}
