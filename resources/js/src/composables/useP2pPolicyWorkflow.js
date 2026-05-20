/** Luồng P2P Policy: kỳ → tuyến → roster → kích hoạt (hub). */

export const P2P_WORKFLOW_STEPS = [
  { key: 'term', routeName: 'p2pPolicyTerm', titleKey: 'p2p_policy_page.hub_workflow_step1_title' },
  { key: 'routes', routeName: 'p2pPolicyRoutes', titleKey: 'p2p_policy_page.hub_workflow_step2_title' },
  { key: 'students', routeName: 'p2pPolicyStudents', titleKey: 'p2p_policy_page.hub_workflow_step3_title' },
  { key: 'activate', routeName: 'p2pPolicyHub', titleKey: 'p2p_policy_page.hub_workflow_step4_title' },
]

export function resolveP2pTermIdFromRoute(route) {
  const q = route?.query ?? {}
  if (q.term_id != null && q.term_id !== '') {
    const n = Number(q.term_id)
    return Number.isFinite(n) ? n : null
  }
  if (q.id != null && q.id !== '') {
    const n = Number(q.id)
    return Number.isFinite(n) ? n : null
  }
  return null
}

export function p2pWorkflowQuery(termId, extra = {}) {
  const q = { ...extra }
  if (termId != null && termId !== '') {
    q.term_id = String(termId)
  }
  return q
}

/** @param {string} routeName @param {number|string|null|undefined} termId @param {Record<string, string>} [extra] */
export function p2pStepTo(routeName, termId, extra = {}) {
  if (routeName === 'p2pPolicyTerm' && termId != null && termId !== '') {
    return {
      name: routeName,
      query: p2pWorkflowQuery(termId, { id: String(termId), ...extra }),
    }
  }
  return { name: routeName, query: p2pWorkflowQuery(termId, extra) }
}

export function p2pStudentsForRoute(termId, policyRouteId) {
  return p2pStepTo('p2pPolicyStudents', termId, {
    policy_route_id: String(policyRouteId),
  })
}

export function p2pWorkflowStepIndex(stepKey) {
  return P2P_WORKFLOW_STEPS.findIndex((s) => s.key === stepKey)
}
