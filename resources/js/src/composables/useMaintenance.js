import { computed, ref } from 'vue'
import {
  createMaintenanceReminder,
  deleteMaintenanceReminder,
  getMaintenanceItem,
  getMaintenanceList,
  updateMaintenanceItem,
  uploadMaintenanceImage,
} from '../api/maintenance'

// Module-level shared state (singleton within the SPA session)
const items = ref([])
const reminders = ref([])
const vehicle = ref(null)
const counts = ref({ urgent: 0, upcoming: 0, safe: 0 })
const loading = ref(false)
const error = ref(null)

// Detail state
const currentItem = ref(null)
const detailLoading = ref(false)
const detailError = ref(null)

export function useMaintenance() {
  // ---------------------------------------------------------------
  // Derived
  // ---------------------------------------------------------------
  const urgentItems = computed(() => items.value.filter((i) => i.status === 'urgent'))
  const hasUrgent = computed(() => counts.value.urgent > 0)

  const legalItems = computed(() =>
    items.value.filter((i) =>
      ['registration', 'insurance_mandatory', 'insurance_hull'].includes(i.type),
    ),
  )

  const maintenanceItems = computed(() =>
    items.value.filter((i) =>
      ['oil', 'tire', 'air_filter', 'brake'].includes(i.type),
    ),
  )

  const customItems = computed(() =>
    items.value.filter((i) => i.type === 'custom'),
  )

  // ---------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------
  async function fetchList() {
    loading.value = true
    error.value = null
    try {
      const data = await getMaintenanceList()
      items.value = data.items ?? []
      reminders.value = data.reminders ?? []
      vehicle.value = data.vehicle ?? null
      counts.value = data.counts ?? { urgent: 0, upcoming: 0, safe: 0 }
    } catch (err) {
      error.value = err?.response?.data?.message ?? 'Không tải được dữ liệu bảo trì.'
    } finally {
      loading.value = false
    }
  }

  async function fetchDetail(id) {
    detailLoading.value = true
    detailError.value = null
    currentItem.value = null
    try {
      currentItem.value = await getMaintenanceItem(id)
    } catch (err) {
      detailError.value = err?.response?.data?.message ?? 'Không tải được chi tiết.'
    } finally {
      detailLoading.value = false
    }
  }

  async function updateItem(id, payload) {
    const updated = await updateMaintenanceItem(id, payload)
    // Patch in-memory list
    const idx = items.value.findIndex((i) => i.id === id)
    if (idx !== -1) items.value[idx] = { ...items.value[idx], ...updated }
    if (currentItem.value?.id === id) {
      currentItem.value = { ...currentItem.value, ...updated }
    }
    return updated
  }

  async function uploadImage(id, file, onProgress) {
    const attachment = await uploadMaintenanceImage(id, file, onProgress)
    if (currentItem.value?.id === id) {
      currentItem.value = {
        ...currentItem.value,
        images: [...(currentItem.value.images ?? []), attachment],
      }
    }
    return attachment
  }

  async function createReminder(payload) {
    const reminder = await createMaintenanceReminder(payload)
    reminders.value = [...reminders.value, reminder]
    return reminder
  }

  async function deleteReminder(id) {
    await deleteMaintenanceReminder(id)
    reminders.value = reminders.value.filter((r) => r.id !== id)
  }

  // ---------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------
  function formatDate(ymd) {
    if (!ymd) return '—'
    const [y, m, d] = String(ymd).split('-')
    if (!y || !m || !d) return ymd
    return `${d}/${m}/${y}`
  }

  function formatMoney(amount) {
    if (amount == null) return '—'
    return Number(amount).toLocaleString('vi-VN') + ' ₫'
  }

  return {
    // State
    items,
    reminders,
    vehicle,
    counts,
    loading,
    error,
    currentItem,
    detailLoading,
    detailError,
    // Derived
    urgentItems,
    hasUrgent,
    legalItems,
    maintenanceItems,
    customItems,
    // Actions
    fetchList,
    fetchDetail,
    updateItem,
    uploadImage,
    createReminder,
    deleteReminder,
    // Helpers
    formatDate,
    formatMoney,
  }
}
