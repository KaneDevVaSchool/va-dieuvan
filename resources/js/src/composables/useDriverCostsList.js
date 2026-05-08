import { ref, watch } from 'vue'
import { listTripCosts } from '../api/costs'

/**
 * Danh sách chi phí tài xế + phân trang vô hạn.
 * @param {import('vue').Ref<string>} statusRef '' | submitted | confirmed | rejected
 */
export function useDriverCostsList(statusRef) {
  const items = ref([])
  const loading = ref(false)
  const loadingMore = ref(false)
  const errorMsg = ref('')
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  async function fetchPage(page, append) {
    const p = {
      per_page: meta.value.per_page || 20,
      page,
    }
    const st = statusRef.value
    if (st) p.status = st
    const res = await listTripCosts(p)
    const chunk = res?.items ?? []
    meta.value = {
      current_page: res?.meta?.current_page ?? page,
      last_page: res?.meta?.last_page ?? page,
      per_page: res?.meta?.per_page ?? p.per_page,
      total: res?.meta?.total ?? chunk.length,
    }
    if (append) items.value = items.value.concat(chunk)
    else items.value = chunk
  }

  /**
   * @param {{ append?: boolean }} [opts]
   */
  async function load(opts = {}) {
    const append = !!opts.append
    if (append) loadingMore.value = true
    else {
      loading.value = true
      errorMsg.value = ''
    }
    try {
      const page = append ? (meta.value.current_page || 1) + 1 : 1
      await fetchPage(page, append)
    } catch {
      errorMsg.value = 'load_error'
      if (!append) items.value = []
    } finally {
      loading.value = false
      loadingMore.value = false
    }
  }

  async function refresh() {
    items.value = []
    meta.value = { ...meta.value, current_page: 1 }
    await load({ append: false })
  }

  async function loadMore() {
    if (loading.value || loadingMore.value) return
    if ((meta.value.current_page || 1) >= (meta.value.last_page || 1)) return
    await load({ append: true })
  }

  watch(
    statusRef,
    () => {
      void refresh()
    },
    { flush: 'post' },
  )

  return {
    items,
    loading,
    loadingMore,
    errorMsg,
    meta,
    load,
    refresh,
    loadMore,
  }
}
