import { computed } from 'vue'
import { useRoute } from 'vue-router'

const MODULE_ROUTE_NAMES = new Set([
  'portalExtracurricularHome',
  'portalExtracurricularList',
  'portalExtracurricularCreate',
  'portalExtracurricularDetail',
])

export function isExtracurricularDispatchRequest(req) {
  if (!req) return false
  const tid = req.dispatch_request_template_id ?? req.dispatch_request_template?.id
  return tid != null && tid !== ''
}

/** Route chi tiết portal khớp loại yêu cầu (navbar / module context). */
export function portalDetailRouteForRequest(req) {
  const id = req?.id
  const name = isExtracurricularDispatchRequest(req)
    ? 'portalExtracurricularDetail'
    : 'portalRequestDetail'
  return { name, params: { id: String(id) } }
}

/**
 * Portal sub-module: point-to-point extracurricular (CLB / ngoại khóa).
 */
export function usePortalExtracurricularModule() {
  const route = useRoute()

  const isExtracurricularModule = computed(
    () =>
      route.meta.portalExtracurricular === true ||
      MODULE_ROUTE_NAMES.has(route.name),
  )

  const routes = computed(() =>
    isExtracurricularModule.value
      ? {
          home: 'portalExtracurricularHome',
          list: 'portalExtracurricularList',
          create: 'portalExtracurricularCreate',
          detail: 'portalExtracurricularDetail',
        }
      : {
          home: 'portalHome',
          list: 'portalRequestList',
          create: 'portalCreate',
          detail: 'portalRequestDetail',
        },
  )

  return { isExtracurricularModule, routes }
}
