/**
 * Trạng thái báo cáo /api/reports/summary dùng chung: Tổng quan (KPI) + trang Báo cáo (biểu đồ).
 * Singleton để giữ bộ lọc khi chuyển giữa / và /reports.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useVisibleFilterControls } from './useVisibleFilterControls.js'
import { getSummary } from '../api/reports'
import { i18n } from '../i18n'
import { labelTripStatus, labelTripType, labelRequestStatus } from '../util/labels'
import {
  sumTrips,
  normalizeTripsByHour,
  tripsStatusDonutOption,
  fleetModeDonutOption,
  statusDonutOption,
  costsByTypeBarOption,
  costsPipelineBarOption,
  providersHorizontalBarOption,
  topRequestersBarOption,
  licensePlateTripsBarOption,
  tripsByHourLineOption,
} from '../util/transportDashboardCharts'

const DIMENSION_FILTER_IDS = ['trip_type', 'channel', 'paper', 'urgent', 'trip_run', 'fleet']
const REPORT_FILTER_BAR_VIS_IDS = ['period', 'dates', ...DIMENSION_FILTER_IDS]
const REPORT_FILTER_CONTROLS = REPORT_FILTER_BAR_VIS_IDS.map((key) => ({
  key,
  label: '',
  default: false,
}))

const TRIP_RUN_STATUS_KEYS = [
  'pending',
  'approved',
  'assigned',
  'driver_confirmed',
  'in_progress',
  'completed',
  'cancelled',
  'incident',
]

let sharedApi = null

function ymd(d) {
  const x = new Date(d)
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function subDays(base, n) {
  const x = new Date(base)
  x.setDate(x.getDate() - n)
  return x
}

function startOfQuarter(d) {
  const m = d.getMonth()
  const q0 = Math.floor(m / 3) * 3
  return new Date(d.getFullYear(), q0, 1)
}

function createSharedApi() {
  /** Global composer — avoid capturing component-scoped `useI18n()` in a singleton (stale / invalid after unmount). */
  function t(...args) {
    return i18n.global.t(...args)
  }

  function formatDisplayDate(ymdStr) {
    if (!ymdStr || typeof ymdStr !== 'string') return '—'
    const p = ymdStr.split('-').map((x) => Number(x))
    if (p.length !== 3 || Number.isNaN(p[0])) return ymdStr
    const tag = i18n.global.locale.value === 'vi' ? 'vi-VN' : 'en-GB'
    try {
      return new Intl.DateTimeFormat(tag, { day: '2-digit', month: 'short', year: 'numeric' }).format(
        new Date(p[0], p[1] - 1, p[2]),
      )
    } catch {
      return ymdStr
    }
  }

  const rangeFrom = ref('')
  const rangeTo = ref('')
  const preset = ref('all')

  const filterTripType = ref('')
  const filterSourceChannel = ref('')
  const filterPaperStatus = ref('')
  const filterIsUrgent = ref(false)
  const filterTripRunStatus = ref('')
  const filterFleetMode = ref('')

  const {
    visibleFilters: filterBarVisible,
    hasFilterRow: hasVisibleBarFilters,
    showFilterPanelDd,
    openFilterPanel,
    closeFilterPanel,
  } = useVisibleFilterControls(REPORT_FILTER_CONTROLS, 'va-dieuvan.reports.summary.visible-filters.v1')

  function resetFilterBarVisibility() {
    for (const c of REPORT_FILTER_CONTROLS) {
      filterBarVisible[c.key] = false
    }
  }

  const loading = ref(false)
  const loadError = ref('')
  const summary = ref(null)

  const chartHeight = ref('260px')
  const chartHeightWide = ref('280px')
  const chartHeightTall = ref('300px')

  let resizeAttached = false

  function updateChartHeights() {
    const narrow = typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches
    chartHeight.value = narrow ? '220px' : '260px'
    chartHeightWide.value = narrow ? '240px' : '300px'
    chartHeightTall.value = narrow ? '260px' : '320px'
  }

  function onWindowResize() {
    updateChartHeights()
  }

  function safeLabel(v, fb = '—') {
    const s = v != null && typeof v !== 'object' ? String(v) : ''
    return s.trim() !== '' ? s : fb
  }

  /** Nhãn field là dòng đầu trong panel lọc, sau đó mới tới «Tất cả» và các giá trị. */
  function optionsWithFieldLabel(fieldLabel, options) {
    const label = safeLabel(fieldLabel)
    const opts = Array.isArray(options) ? options : []
    return [{ header: true, label, value: null }, ...opts]
  }

  const presetDefs = computed(() =>
    [
      { id: 'all', label: t('dashboard_analytics.preset_all_time') },
      { id: 'month', label: t('dashboard_analytics.preset_month') },
      { id: 'last30', label: t('dashboard_analytics.preset_last30') },
      { id: 'last7', label: t('dashboard_analytics.preset_last7') },
      { id: 'quarter', label: t('dashboard_analytics.preset_quarter') },
      { id: 'custom', label: t('dashboard_analytics.preset_custom') },
    ].map((p) => (p && p.id != null ? { ...p, label: safeLabel(p.label) } : null)).filter(Boolean),
  )

  const currentPresetLabel = computed(() => presetDefs.value.find((p) => p.id === preset.value)?.label ?? '')

  const emptyChartLabel = computed(() => t('dashboard_analytics.chart_empty'))

  const summaryFilters = computed(() => {
    const o = {}
    if (rangeFrom.value && rangeTo.value) {
      o.from = rangeFrom.value
      o.to = rangeTo.value
    }
    if (filterTripType.value) o.trip_type = filterTripType.value
    if (filterSourceChannel.value) o.source_channel = filterSourceChannel.value
    if (filterPaperStatus.value) o.paper_status = filterPaperStatus.value
    if (filterIsUrgent.value) o.is_urgent = 1
    if (filterTripRunStatus.value) o.trip_status = filterTripRunStatus.value
    if (filterFleetMode.value) o.fleet_mode = filterFleetMode.value
    return o
  })

  const rangeValid = computed(() => {
    if (preset.value === 'all') return true
    if (!rangeFrom.value || !rangeTo.value) return false
    return rangeFrom.value <= rangeTo.value
  })

  async function reloadSummary() {
    if (!rangeValid.value) return
    loading.value = true
    loadError.value = ''
    try {
      summary.value = await getSummary(summaryFilters.value)
    } catch {
      loadError.value = t('dashboard_analytics.load_error')
    } finally {
      loading.value = false
    }
  }

  const activeFilterCount = computed(() => {
    let n = 0
    if (filterTripType.value) n++
    if (filterSourceChannel.value) n++
    if (filterPaperStatus.value) n++
    if (filterIsUrgent.value) n++
    if (filterTripRunStatus.value) n++
    if (filterFleetMode.value) n++
    return n
  })

  const activeFilterLines = computed(() => {
    const rows = []
    if (filterTripType.value) {
      rows.push({ label: t('dashboard_analytics.filter_trip_type'), value: labelTripType(filterTripType.value) })
    }
    if (filterSourceChannel.value) {
      rows.push({
        label: t('dashboard_analytics.filter_channel'),
        value: t(`labels.source_channel.${filterSourceChannel.value}`),
      })
    }
    if (filterPaperStatus.value) {
      rows.push({
        label: t('dashboard_analytics.filter_paper'),
        value: t(`labels.paper_status.${filterPaperStatus.value}`),
      })
    }
    if (filterIsUrgent.value) {
      rows.push({ label: t('dashboard_analytics.filter_urgent'), value: t('dashboard_analytics.filter_urgent_only') })
    }
    if (filterTripRunStatus.value) {
      rows.push({
        label: t('dashboard_analytics.filter_trip_run_status'),
        value: labelTripStatus(filterTripRunStatus.value),
      })
    }
    if (filterFleetMode.value) {
      const map = {
        internal: t('dashboard_analytics.fleet_internal'),
        vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
        taxi: t('dashboard_analytics.fleet_taxi'),
        unspecified: t('dashboard_analytics.fleet_unspecified'),
      }
      rows.push({ label: t('dashboard_analytics.filter_fleet'), value: map[filterFleetMode.value] ?? filterFleetMode.value })
    }
    return rows.filter((row) => row && row.label != null && row.value != null)
  })

  const dimensionFilters = computed(() => {
    const fa = safeLabel(t('dashboard_analytics.filter_all'))
    const tripTypeOpts = [
      { value: '', label: fa },
      { value: 'door_to_door', label: labelTripType('door_to_door') },
      { value: 'point_to_point', label: labelTripType('point_to_point') },
      { value: 'business', label: labelTripType('business') },
      { value: 'cargo', label: labelTripType('cargo') },
    ]
    const channelOpts = [
      { value: '', label: fa },
      { value: 'portal', label: t('labels.source_channel.portal') },
      { value: 'zalo', label: t('labels.source_channel.zalo') },
      { value: 'paper', label: t('labels.source_channel.paper') },
    ]
    const paperOpts = [
      { value: '', label: fa },
      { value: 'pending', label: t('labels.paper_status.pending') },
      { value: 'received', label: t('labels.paper_status.received') },
      { value: 'digitally_signed', label: t('labels.paper_status.digitally_signed') },
    ]
    const urgentOpts = [
      { value: '', label: fa },
      { value: '1', label: t('dashboard_analytics.filter_urgent_only') },
    ]
    const runStatusOpts = [{ value: '', label: fa }].concat(
      TRIP_RUN_STATUS_KEYS.map((k) => ({ value: k, label: labelTripStatus(k) })),
    )
    const fleetOpts = [
      { value: '', label: fa },
      { value: 'internal', label: t('dashboard_analytics.fleet_internal') },
      { value: 'vendor_hire', label: t('dashboard_analytics.fleet_vendor_hire') },
      { value: 'taxi', label: t('dashboard_analytics.fleet_taxi') },
      { value: 'unspecified', label: t('dashboard_analytics.fleet_unspecified') },
    ]

    const defs = [
      {
        id: 'trip_type',
        label: safeLabel(t('dashboard_analytics.filter_trip_type')),
        summary: filterTripType.value ? labelTripType(filterTripType.value) : safeLabel(t('dashboard_analytics.filter_trip_type')),
        options: optionsWithFieldLabel(t('dashboard_analytics.filter_trip_type'), tripTypeOpts),
        isSelected: (v) => (v === '' ? !filterTripType.value : filterTripType.value === v),
        pick: (v) => {
          filterTripType.value = v || ''
          reloadSummary()
        },
      },
      {
        id: 'channel',
        label: safeLabel(t('dashboard_analytics.filter_channel')),
        summary: filterSourceChannel.value ? t(`labels.source_channel.${filterSourceChannel.value}`) : safeLabel(t('dashboard_analytics.filter_channel')),
        options: optionsWithFieldLabel(t('dashboard_analytics.filter_channel'), channelOpts),
        isSelected: (v) => (v === '' ? !filterSourceChannel.value : filterSourceChannel.value === v),
        pick: (v) => {
          filterSourceChannel.value = v || ''
          reloadSummary()
        },
      },
      {
        id: 'paper',
        label: safeLabel(t('dashboard_analytics.filter_paper')),
        summary: filterPaperStatus.value ? t(`labels.paper_status.${filterPaperStatus.value}`) : safeLabel(t('dashboard_analytics.filter_paper')),
        options: optionsWithFieldLabel(t('dashboard_analytics.filter_paper'), paperOpts),
        isSelected: (v) => (v === '' ? !filterPaperStatus.value : filterPaperStatus.value === v),
        pick: (v) => {
          filterPaperStatus.value = v || ''
          reloadSummary()
        },
      },
      {
        id: 'urgent',
        label: safeLabel(t('dashboard_analytics.filter_urgent')),
        summary: filterIsUrgent.value ? t('dashboard_analytics.filter_urgent_only') : safeLabel(t('dashboard_analytics.filter_urgent')),
        options: optionsWithFieldLabel(t('dashboard_analytics.filter_urgent'), urgentOpts),
        isSelected: (v) => (v === '' ? !filterIsUrgent.value : filterIsUrgent.value),
        pick: (v) => {
          filterIsUrgent.value = v === '1'
          reloadSummary()
        },
      },
      {
        id: 'trip_run',
        label: safeLabel(t('dashboard_analytics.filter_trip_run_status')),
        summary: filterTripRunStatus.value ? labelTripStatus(filterTripRunStatus.value) : safeLabel(t('dashboard_analytics.filter_trip_run_status')),
        options: optionsWithFieldLabel(t('dashboard_analytics.filter_trip_run_status'), runStatusOpts),
        isSelected: (v) => (v === '' ? !filterTripRunStatus.value : filterTripRunStatus.value === v),
        pick: (v) => {
          filterTripRunStatus.value = v || ''
          reloadSummary()
        },
      },
      {
        id: 'fleet',
        label: safeLabel(t('dashboard_analytics.filter_fleet')),
        summary: filterFleetMode.value
          ? {
              internal: t('dashboard_analytics.fleet_internal'),
              vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
              taxi: t('dashboard_analytics.fleet_taxi'),
              unspecified: t('dashboard_analytics.fleet_unspecified'),
            }[filterFleetMode.value] ?? filterFleetMode.value
          : safeLabel(t('dashboard_analytics.filter_fleet')),
        options: optionsWithFieldLabel(t('dashboard_analytics.filter_fleet'), fleetOpts),
        isSelected: (v) => (v === '' ? !filterFleetMode.value : filterFleetMode.value === v),
        pick: (v) => {
          filterFleetMode.value = v || ''
          reloadSummary()
        },
      },
    ]

    return defs
      .filter((fd) => fd && fd.id && typeof fd.label === 'string' && fd.label.length > 0)
      .map((fd) => ({
        ...fd,
        options: Array.isArray(fd.options)
          ? fd.options
              .filter((o) => o != null && typeof o === 'object')
              .map((o) => ({
                ...o,
                label: o.header ? safeLabel(o.label) : safeLabel(o.label, fa),
              }))
          : [],
      }))
      .filter((fd) => fd.options.length > 0)
  })

  const visibleDimensionFilters = computed(() =>
    dimensionFilters.value.filter(
      (fd) =>
        fd &&
        fd.id &&
        typeof fd.label === 'string' &&
        Array.isArray(fd.options) &&
        fd.options.length > 0 &&
        filterBarVisible[fd.id] === true,
    ),
  )

  const rangeDisplayFormatted = computed(() => {
    if (preset.value === 'all' || (!rangeFrom.value && !rangeTo.value)) {
      return t('dashboard_analytics.preset_all_time')
    }
    return `${formatDisplayDate(rangeFrom.value)} — ${formatDisplayDate(rangeTo.value)}`
  })

  const rangeDaySpan = computed(() => {
    if (!rangeFrom.value || !rangeTo.value || rangeFrom.value > rangeTo.value) return 0
    const a = new Date(`${rangeFrom.value}T12:00:00`)
    const b = new Date(`${rangeTo.value}T12:00:00`)
    return Math.floor((b.getTime() - a.getTime()) / 86400000) + 1
  })

  const dateQuickChips = computed(() =>
    [
      { kind: 'today', label: t('dashboard_analytics.date_range_quick_today') },
      { kind: 'yesterday', label: t('dashboard_analytics.date_range_quick_yesterday') },
      { kind: 'last7', label: t('dashboard_analytics.date_range_quick_last7') },
      { kind: 'month', label: t('dashboard_analytics.date_range_quick_month') },
    ]
      .map((c) => (c && c.kind ? { ...c, label: safeLabel(c.label) } : null))
      .filter(Boolean),
  )

  function syncRangeForPreset(id) {
    const now = new Date()
    const end = ymd(now)
    if (id === 'month') {
      rangeFrom.value = ymd(new Date(now.getFullYear(), now.getMonth(), 1))
      rangeTo.value = end
    } else if (id === 'last30') {
      rangeFrom.value = ymd(subDays(now, 29))
      rangeTo.value = end
    } else if (id === 'last7') {
      rangeFrom.value = ymd(subDays(now, 6))
      rangeTo.value = end
    } else if (id === 'quarter') {
      rangeFrom.value = ymd(startOfQuarter(now))
      rangeTo.value = end
    }
  }

  function applyPreset(id) {
    preset.value = id
    if (id !== 'custom') {
      syncRangeForPreset(id)
      reloadSummary()
    }
  }

  function resetFilters() {
    filterTripType.value = ''
    filterSourceChannel.value = ''
    filterPaperStatus.value = ''
    filterIsUrgent.value = false
    filterTripRunStatus.value = ''
    filterFleetMode.value = ''
    applyPreset('all')
  }

  function onManualDateChange() {
    preset.value = 'custom'
  }

  function onRangeFromChange() {
    if (rangeFrom.value && rangeTo.value && rangeFrom.value > rangeTo.value) {
      rangeTo.value = rangeFrom.value
    }
    onManualDateChange()
  }

  function onRangeToChange() {
    if (rangeFrom.value && rangeTo.value && rangeTo.value < rangeFrom.value) {
      rangeFrom.value = rangeTo.value
    }
    onManualDateChange()
  }

  function applyQuickDateRange(kind) {
    if (kind === 'month') {
      applyPreset('month')
      return
    }
    const now = new Date()
    if (kind === 'today') {
      const d = ymd(now)
      rangeFrom.value = d
      rangeTo.value = d
    } else if (kind === 'yesterday') {
      const d = ymd(subDays(now, 1))
      rangeFrom.value = d
      rangeTo.value = d
    } else if (kind === 'last7') {
      rangeFrom.value = ymd(subDays(now, 6))
      rangeTo.value = ymd(now)
    }
    preset.value = 'custom'
    reloadSummary()
  }

  function formatMoney(v) {
    const n = Number(v ?? 0)
    const loc = i18n.global.locale.value === 'vi' ? 'vi-VN' : 'en-GB'
    return `${new Intl.NumberFormat(loc).format(n)} VND`
  }

  const tripLabelMap = computed(() => {
    const o = summary.value?.trips_by_status ?? {}
    const map = {}
    for (const k of Object.keys(o)) {
      map[k] = labelTripStatus(k)
    }
    return map
  })

  const fleetLabelMap = computed(() => ({
    internal: t('dashboard_analytics.fleet_internal'),
    vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
    taxi: t('dashboard_analytics.fleet_taxi'),
    unspecified: t('dashboard_analytics.fleet_unspecified'),
  }))

  const dispatchStatusLabelMap = computed(() => {
    const o = summary.value?.dispatch_requests_by_status ?? {}
    const map = {}
    for (const k of Object.keys(o)) {
      map[k] = labelRequestStatus(k)
    }
    return map
  })

  const costPipelineLabelMap = computed(() => ({
    draft: t('dashboard_analytics.cost_status_draft'),
    submitted: t('dashboard_analytics.cost_status_submitted'),
    confirmed: t('dashboard_analytics.cost_status_confirmed'),
    rejected: t('dashboard_analytics.cost_status_rejected'),
  }))

  const totalTrips = computed(() => sumTrips(summary.value?.trips_by_status))

  const chartBadgeTripsTotal = computed(() => (totalTrips.value > 0 ? String(totalTrips.value) : ''))

  const totalTripsInRange = computed(() => Number(summary.value?.trip_completion?.total ?? totalTrips.value))

  const completedTrips = computed(() => Number(summary.value?.trip_completion?.completed ?? 0))

  const completionRate = computed(() => {
    const r = summary.value?.trip_completion?.rate_pct
    return r != null ? Number(r) : null
  })

  const totalConfirmedCost = computed(() => {
    const o = summary.value?.confirmed_costs_by_type ?? {}
    return Object.values(o).reduce((a, b) => a + Number(b ?? 0), 0)
  })

  const topProvider = computed(() => {
    const list = summary.value?.confirmed_costs_by_provider
    if (!Array.isArray(list) || !list.length) return null
    return list[0]
  })

  const topProviderName = computed(() => topProvider.value?.provider ?? '—')
  const topProviderAmount = computed(() => topProvider.value?.total_amount ?? 0)

  const chartT = (key) => t(`dashboard_analytics.${key}`)

  const optDonut = computed(() =>
    tripsStatusDonutOption({
      tripsByStatus: summary.value?.trips_by_status,
      labelMap: tripLabelMap.value,
      emptyText: emptyChartLabel.value,
    }),
  )

  const optFleet = computed(() =>
    fleetModeDonutOption({
      tripsByFleetMode: summary.value?.trips_by_fleet_mode,
      labelMap: fleetLabelMap.value,
      emptyText: emptyChartLabel.value,
    }),
  )

  const optDispatchDonut = computed(() =>
    statusDonutOption({
      countsByStatus: summary.value?.dispatch_requests_by_status,
      labelMap: dispatchStatusLabelMap.value,
      emptyText: emptyChartLabel.value,
    }),
  )

  const optHourLine = computed(() => {
    const counts = normalizeTripsByHour(summary.value?.trips_by_hour)
    const base = tripsByHourLineOption({
      counts24: counts,
      t: chartT,
      emptyText: emptyChartLabel.value,
    })
    if (base.graphic) return base
    const seriesList = Array.isArray(base.series) ? base.series.filter((s) => s && typeof s === 'object') : []
    return {
      ...base,
      backgroundColor: 'transparent',
      xAxis: {
        ...base.xAxis,
        axisLabel: { ...base.xAxis.axisLabel, color: '#94a3b8' },
        axisLine: { lineStyle: { color: 'rgba(148,163,184,0.35)' } },
      },
      yAxis: {
        ...base.yAxis,
        nameTextStyle: { color: '#94a3b8', fontSize: 10 },
        axisLabel: { color: '#94a3b8', fontSize: 10 },
        splitLine: { lineStyle: { color: 'rgba(148,163,184,0.2)' } },
      },
      series: seriesList.map((s) => ({
        ...s,
        lineStyle: { ...s.lineStyle, color: '#60a5fa' },
        itemStyle: { color: '#60a5fa' },
        label: s.label ? { ...s.label, color: '#94a3b8' } : s.label,
      })),
    }
  })

  const optCostBar = computed(() =>
    costsByTypeBarOption({
      costsByType: summary.value?.confirmed_costs_by_type,
      formatMoney,
      emptyText: emptyChartLabel.value,
    }),
  )

  const optCostPipeline = computed(() =>
    costsPipelineBarOption({
      costsByPipelineStatus: summary.value?.costs_by_pipeline_status,
      labelMap: costPipelineLabelMap.value,
      formatMoney,
      emptyText: emptyChartLabel.value,
    }),
  )

  const optProviders = computed(() =>
    providersHorizontalBarOption({
      rows: summary.value?.confirmed_costs_by_provider,
      formatMoney,
      emptyText: emptyChartLabel.value,
    }),
  )

  const optRequesters = computed(() =>
    topRequestersBarOption({
      rows: summary.value?.top_requesters,
      tripsSuffix: t('dashboard_analytics.tooltip_trips_unit'),
      emptyText: emptyChartLabel.value,
    }),
  )

  const optPlates = computed(() =>
    licensePlateTripsBarOption({
      tripsByPlate: summary.value?.trips_by_license_plate,
      plateSuffix: t('dashboard_analytics.tooltip_trips_unit'),
      emptyText: emptyChartLabel.value,
    }),
  )

  onMounted(() => {
    updateChartHeights()
    if (!resizeAttached && typeof window !== 'undefined') {
      resizeAttached = true
      window.addEventListener('resize', onWindowResize)
    }
  })

  onUnmounted(() => {
    /* singleton: không gỡ listener để các trang khác vẫn resize biểu đồ */
  })

  return {
    formatDisplayDate,
    rangeFrom,
    rangeTo,
    preset,
    filterTripType,
    filterSourceChannel,
    filterPaperStatus,
    filterIsUrgent,
    filterTripRunStatus,
    filterFleetMode,
    filterBarVisible,
    resetFilterBarVisibility,
    hasVisibleBarFilters,
    showFilterPanelDd,
    openFilterPanel,
    closeFilterPanel,
    reportFilterBarVisIds: REPORT_FILTER_BAR_VIS_IDS,
    loading,
    loadError,
    summary,
    chartHeight,
    chartHeightWide,
    chartHeightTall,
    presetDefs,
    currentPresetLabel,
    emptyChartLabel,
    summaryFilters,
    activeFilterCount,
    activeFilterLines,
    dimensionFilters,
    visibleDimensionFilters,
    rangeValid,
    rangeDisplayFormatted,
    rangeDaySpan,
    dateQuickChips,
    applyPreset,
    resetFilters,
    onRangeFromChange,
    onRangeToChange,
    applyQuickDateRange,
    formatMoney,
    totalTrips,
    chartBadgeTripsTotal,
    totalTripsInRange,
    completedTrips,
    completionRate,
    totalConfirmedCost,
    topProviderName,
    topProviderAmount,
    optDonut,
    optFleet,
    optDispatchDonut,
    optHourLine,
    optCostBar,
    optCostPipeline,
    optProviders,
    optRequesters,
    optPlates,
    reloadSummary,
    updateChartHeights,
  }
}

export function useTransportReportSummary() {
  if (!sharedApi) {
    sharedApi = createSharedApi()
  }
  return sharedApi
}
