import { http } from './http'

export async function listP2pPolicyTerms(params = {}) {
  const { data } = await http.get('/p2p-policy/terms', { params })
  return data.data
}

export async function getP2pPolicyTerm(id) {
  const { data } = await http.get(`/p2p-policy/terms/${id}`)
  return data.data
}

export async function getP2pPolicyTermReadiness(id) {
  const { data } = await http.get(`/p2p-policy/terms/${id}/readiness`)
  return data.data
}

export async function createP2pPolicyTerm(payload) {
  const { data } = await http.post('/p2p-policy/terms', payload)
  return data.data
}

export async function updateP2pPolicyTerm(id, payload) {
  const { data } = await http.patch(`/p2p-policy/terms/${id}`, payload)
  return data.data
}

export async function syncP2pPolicyTermCalendar(id, payload) {
  const { data } = await http.put(`/p2p-policy/terms/${id}/calendar`, payload)
  return data.data
}

export async function activateP2pPolicyTerm(id, idempotencyKey) {
  const { data } = await http.post(
    `/p2p-policy/terms/${id}/activate`,
    { confirm: true },
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return data.data
}

export async function getPolicyGenerationRun(id) {
  const { data } = await http.get(`/p2p-policy/generation-runs/${id}`)
  return data.data
}

export async function listPolicyRoutes(params = {}) {
  const { data } = await http.get('/p2p-policy/routes', { params })
  return data.data
}

export async function createPolicyRoute(payload) {
  const { data } = await http.post('/p2p-policy/routes', payload)
  return data.data
}

export async function assignPolicyRoute(id, payload) {
  const { data } = await http.patch(`/p2p-policy/routes/${id}/assignment`, payload)
  return data.data
}

export async function listPolicyStudents(params = {}) {
  const { data } = await http.get('/p2p-policy/students', { params })
  return data.data
}

export async function listCampuses(params = {}) {
  const { data } = await http.get('/campuses', { params })
  return data.data
}

export async function listAcademicTerms(params = {}) {
  const { data } = await http.get('/academic-terms', { params })
  return data.data
}

export async function createAcademicTerm(payload) {
  const { data } = await http.post('/academic-terms', payload)
  return data.data
}

export async function importPolicyStudents(formData) {
  const { data } = await http.post('/p2p-policy/students/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data
}

export function policyStudentsExportUrl(params = {}) {
  const qs = new URLSearchParams()
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== '') qs.set(k, String(v))
  }
  const q = qs.toString()
  return `/api/p2p-policy/students/export${q ? `?${q}` : ''}`
}
