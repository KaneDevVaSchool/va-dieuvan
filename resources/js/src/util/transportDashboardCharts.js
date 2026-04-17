/**
 * Tùy chọn ECharts cho trang tổng quan vận tải (dữ liệu API + phần minh họa).
 */

import * as echarts from 'echarts'

const AXIS = {
  label: '#64748b',
  line: '#e2e8f0',
  split: '#f1f5f9',
}

const PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899']

export function sumTrips(tripsByStatus) {
  const o = tripsByStatus && typeof tripsByStatus === 'object' ? tripsByStatus : {}
  return Object.values(o).reduce((a, b) => a + Number(b ?? 0), 0)
}

export function effectiveTripTotal(tripsByStatus, tripStatusFilter) {
  const t = sumTrips(tripsByStatus)
  if (!tripStatusFilter) return t
  return Number(tripsByStatus?.[tripStatusFilter] ?? 0)
}

function genHourlyLabels() {
  const out = []
  for (let h = 6; h <= 22; h++) {
    out.push(`${String(h).padStart(2, '0')}:00`)
  }
  return out
}

/** Chuỗi giờ → mảng [label, count] mô phỏng mật độ chuyến. */
export function hourlyDensityForTotal(total, peakHour = 14) {
  const labels = genHourlyLabels()
  const sigma = 3.2
  const raw = labels.map((_, i) => {
    const h = 6 + i
    return Math.exp(-0.5 * ((h - peakHour) / sigma) ** 2)
  })
  const mx = Math.max(...raw, 1e-6)
  const n = Math.max(Number(total) || 0, 0)
  const peak = Math.max(Math.round(n * 0.38), n > 0 ? 1 : 0)
  return labels.map((lab, i) => [lab, Math.round((raw[i] / mx) * peak)])
}

export function tripsStatusDonutOption({ tripsByStatus, labelMap, selectedStatus }) {
  const entries = Object.entries(tripsByStatus ?? {}).filter(([, v]) => Number(v) > 0)
  const data = entries.map(([k, v], i) => ({
    value: Number(v),
    name: labelMap[k] ?? k,
    rawStatus: k,
    itemStyle: { color: PALETTE[i % PALETTE.length] },
  }))
  if (!data.length) {
    return {
      title: { text: '—', left: 'center', top: 'middle', textStyle: { color: AXIS.label, fontSize: 14 } },
    }
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
        emphasis: { scale: true, scaleSize: 6 },
        data: data.map((d) => ({
          ...d,
          selected: selectedStatus === d.rawStatus,
        })),
      },
    ],
  }
}

export function fleetGaugeOption({ availabilityPct, t }) {
  const pct = Math.min(100, Math.max(0, Number(availabilityPct) || 0))
  return {
    tooltip: { formatter: `${t('gauge_label')}: {c}%` },
    series: [
      {
        type: 'gauge',
        startAngle: 200,
        endAngle: -20,
        min: 0,
        max: 100,
        splitNumber: 10,
        radius: '88%',
        center: ['50%', '58%'],
        axisLine: {
          lineStyle: {
            width: 14,
            color: [
              [0.35, '#ef4444'],
              [0.65, '#f59e0b'],
              [1, '#10b981'],
            ],
          },
        },
        pointer: { length: '62%', width: 6 },
        axisTick: { show: false },
        splitLine: { show: false },
        axisLabel: { show: false },
        detail: {
          valueAnimation: true,
          formatter: '{value}%',
          fontSize: 22,
          fontWeight: 700,
          color: '#1e293b',
          offsetCenter: [0, '70%'],
        },
        title: {
          show: true,
          offsetCenter: [0, '38%'],
          fontSize: 11,
          color: AXIS.label,
          text: t('gauge_caption'),
        },
        data: [{ value: pct, name: t('gauge_label') }],
      },
    ],
  }
}

export function costsByTypeBarOption({ costsByType, selectedCostType, formatMoney }) {
  const entries = Object.entries(costsByType ?? {})
    .map(([k, v]) => [k, Number(v ?? 0)])
    .sort((a, b) => b[1] - a[1])
  const cats = entries.map(([k]) => k)
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
          itemStyle: {
            color: cats[i] === selectedCostType ? '#1d4ed8' : PALETTE[i % PALETTE.length],
            opacity: selectedCostType && cats[i] !== selectedCostType ? 0.35 : 1,
            borderRadius: [4, 4, 0, 0],
          },
        })),
        barMaxWidth: 36,
      },
    ],
  }
}

export function providersHorizontalBarOption({ rows, formatMoney, maxItems = 10 }) {
  const list = (rows ?? []).slice(0, maxItems)
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
      axisLabel: { color: AXIS.label, fontSize: 10, width: 110, overflow: 'truncate' },
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

export function hourlyAreaOption({ hourlyPairs, t }) {
  const labels = hourlyPairs.map(([h]) => h)
  const vals = hourlyPairs.map(([, v]) => v)
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 8, right: 12, top: 32, bottom: 24, containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: labels,
      axisLabel: { color: AXIS.label, fontSize: 10 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: {
      type: 'value',
      name: t('area_y'),
      nameTextStyle: { color: AXIS.label, fontSize: 10 },
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    series: [
      {
        type: 'line',
        smooth: true,
        symbol: 'none',
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(59,130,246,0.45)' },
            { offset: 1, color: 'rgba(59,130,246,0.04)' },
          ]),
        },
        lineStyle: { width: 2, color: '#2563eb' },
        data: vals,
      },
    ],
  }
}

export function costsTreemapOption({ costsByType, selectedCostType, formatMoney }) {
  const entries = Object.entries(costsByType ?? {})
    .map(([name, v]) => ({ name, value: Math.max(Number(v) || 0, 0) }))
    .filter((d) => d.value > 0)
  if (!entries.length) {
    return {
      title: { text: '—', left: 'center', top: 'middle', textStyle: { color: AXIS.label, fontSize: 14 } },
    }
  }
  return {
    tooltip: {
      formatter(info) {
        return `${info.name}<br/>${formatMoney(info.value)}`
      },
    },
    series: [
      {
        type: 'treemap',
        roam: false,
        nodeClick: false,
        breadcrumb: { show: false },
        label: { show: true, fontSize: 11, color: '#fff' },
        upperLabel: { show: false },
        itemStyle: { borderColor: '#fff', borderWidth: 2, gapWidth: 2 },
        data: entries.map((d, i) => ({
          ...d,
          itemStyle: {
            color:
              selectedCostType && d.name !== selectedCostType
                ? '#94a3b8'
                : PALETTE[i % PALETTE.length],
            opacity: selectedCostType && d.name !== selectedCostType ? 0.45 : 1,
          },
        })),
      },
    ],
  }
}

/** Minh họa: luồng từ khu vực đến loại tuyến. */
export function sankeyFlowOption({ t }) {
  return {
    tooltip: { trigger: 'item' },
    series: [
      {
        type: 'sankey',
        emphasis: { focus: 'adjacency' },
        layoutIterations: 32,
        lineStyle: { color: 'gradient', curveness: 0.5, opacity: 0.35 },
        label: { color: '#334155', fontSize: 10 },
        data: [
          { name: t('sankey_hub_north') },
          { name: t('sankey_hub_south') },
          { name: t('sankey_hub_east') },
          { name: t('sankey_route_urban') },
          { name: t('sankey_route_intercity') },
          { name: t('sankey_route_cargo') },
        ],
        links: [
          { source: t('sankey_hub_north'), target: t('sankey_route_urban'), value: 32 },
          { source: t('sankey_hub_north'), target: t('sankey_route_intercity'), value: 18 },
          { source: t('sankey_hub_south'), target: t('sankey_route_urban'), value: 28 },
          { source: t('sankey_hub_south'), target: t('sankey_route_cargo'), value: 14 },
          { source: t('sankey_hub_east'), target: t('sankey_route_intercity'), value: 22 },
          { source: t('sankey_hub_east'), target: t('sankey_route_cargo'), value: 16 },
        ],
      },
    ],
  }
}

/** Minh họa: mật độ theo giờ × ngày (heatmap). */
export function dispatchHeatmapOption({ t }) {
  const hours = []
  for (let h = 7; h <= 20; h++) hours.push(`${h}h`)
  const days = [t('heat_d1'), t('heat_d2'), t('heat_d3'), t('heat_d4'), t('heat_d5')]
  const data = []
  for (let j = 0; j < days.length; j++) {
    for (let i = 0; i < hours.length; i++) {
      const v = Math.round(8 + Math.sin(i / 2) * 6 + Math.cos(j) * 4 + ((i + j) % 5) * 2)
      data.push([i, j, v])
    }
  }
  return {
    tooltip: { position: 'top' },
    grid: { left: 8, right: 12, top: 28, bottom: 48, containLabel: true },
    xAxis: {
      type: 'category',
      data: hours,
      splitArea: { show: true },
      axisLabel: { color: AXIS.label, fontSize: 9 },
    },
    yAxis: {
      type: 'category',
      data: days,
      splitArea: { show: true },
      axisLabel: { color: AXIS.label, fontSize: 10 },
    },
    visualMap: {
      min: 0,
      max: 28,
      calculable: true,
      orient: 'horizontal',
      left: 'center',
      bottom: 0,
      inRange: { color: ['#e0f2fe', '#0369a1'] },
      textStyle: { color: AXIS.label, fontSize: 10 },
    },
    series: [
      {
        type: 'heatmap',
        data,
        label: { show: false },
        emphasis: { itemStyle: { shadowBlur: 8, shadowColor: 'rgba(0,0,0,0.25)' } },
      },
    ],
  }
}

/** Radar chỉ số vận hành — một phần gắn với SLA thực tế. */
export function opsRadarOption({ slaBreaches, totalTrips, t }) {
  const breach = Math.min(Number(slaBreaches) || 0, 50)
  const trips = Math.max(Number(totalTrips) || 0, 1)
  const slaScore = Math.max(35, 100 - breach * 4)
  const loadScore = Math.min(100, 40 + Math.min(trips, 120) / 1.2)
  return {
    tooltip: {},
    radar: {
      radius: '66%',
      center: ['50%', '52%'],
      axisName: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.line } },
      splitArea: { show: true, areaStyle: { color: ['rgba(241,245,249,0.6)', 'rgba(255,255,255,0.4)'] } },
      indicator: [
        { name: t('radar_sla'), max: 100 },
        { name: t('radar_load'), max: 100 },
        { name: t('radar_otd'), max: 100 },
        { name: t('radar_safety'), max: 100 },
        { name: t('radar_fuel'), max: 100 },
      ],
    },
    series: [
      {
        type: 'radar',
        symbol: 'circle',
        symbolSize: 6,
        areaStyle: { opacity: 0.22 },
        lineStyle: { width: 2, color: '#4f46e5' },
        itemStyle: { color: '#4f46e5' },
        data: [
          {
            value: [slaScore, loadScore, 82, 91, 76],
            name: t('radar_series'),
          },
        ],
      },
    ],
  }
}

/** Xe tải / xe khách / hàng — tỉ lệ minh họa theo tổng chuyến hiệu dụng. */
export function vehicleMixStackedOption({ effectiveTotal, t }) {
  const n = Math.max(Number(effectiveTotal) || 0, 0)
  const a = Math.round(n * 0.42)
  const b = Math.round(n * 0.35)
  const c = Math.max(n - a - b, 0)
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 10 } },
    grid: { left: 8, right: 8, top: 16, bottom: 36, containLabel: true },
    xAxis: {
      type: 'value',
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    yAxis: {
      type: 'category',
      data: [t('stack_period')],
      axisLabel: { color: AXIS.label, fontSize: 11 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    series: [
      {
        name: t('mix_bus'),
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#3b82f6' },
        data: [a],
      },
      {
        name: t('mix_van'),
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#10b981' },
        data: [b],
      },
      {
        name: t('mix_cargo'),
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#f59e0b' },
        data: [c],
      },
    ],
  }
}

/** Kết hợp cột + đường (minh họa nhiên liệu / chi phí). */
export function fuelComboOption({ t }) {
  const days = [t('combo_d1'), t('combo_d2'), t('combo_d3'), t('combo_d4'), t('combo_d5'), t('combo_d6'), t('combo_d7')]
  const vol = [120, 132, 101, 134, 90, 140, 118]
  const priceIdx = [100, 102, 101, 105, 104, 108, 107]
  return {
    tooltip: { trigger: 'axis' },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 10 } },
    grid: { left: 8, right: 48, top: 24, bottom: 36, containLabel: true },
    xAxis: {
      type: 'category',
      data: days,
      axisLabel: { color: AXIS.label, fontSize: 9 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: [
      {
        type: 'value',
        name: t('combo_vol'),
        nameTextStyle: { color: AXIS.label, fontSize: 10 },
        axisLabel: { color: AXIS.label, fontSize: 10 },
        splitLine: { lineStyle: { color: AXIS.split } },
      },
      {
        type: 'value',
        name: t('combo_idx'),
        nameTextStyle: { color: AXIS.label, fontSize: 10 },
        axisLabel: { color: AXIS.label, fontSize: 10 },
        splitLine: { show: false },
      },
    ],
    series: [
      {
        name: t('combo_vol'),
        type: 'bar',
        data: vol,
        itemStyle: { color: '#cbd5e1', borderRadius: [3, 3, 0, 0] },
      },
      {
        name: t('combo_idx'),
        type: 'line',
        yAxisIndex: 1,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        lineStyle: { width: 2, color: '#ca8a04' },
        itemStyle: { color: '#ca8a04' },
        data: priceIdx,
      },
    ],
  }
}
