import { http } from './http'
import { saveAs } from 'file-saver'
import { normalizeAxiosBlobError } from '../util/downloadPdfAttachment'

export async function getSummary(params = {}) {
  const { data } = await http.get('/reports/summary', { params })
  return data.data
}

/**
 * Returns { stats: {...}, rows: [...] }
 */
export async function getTripCostReport(params = {}) {
  const { data } = await http.get('/reports/trip-costs/statistics', { params })
  return data.data   // { stats, rows }
}

export async function downloadTripCostXlsx(params = {}) {
  let res
  try {
    res = await http.get('/reports/trip-costs/export-xlsx', {
      params,
      responseType: 'blob',
      headers: { Accept: '*/*' },
      timeout: 90_000,
    })
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
  const blob = res.data
  if (!(blob instanceof Blob) || blob.size === 0) throw new Error('empty_response')
  const filename = `chi-phi-chuyen_${new Date().toISOString().slice(0, 10)}.xlsx`
  saveAs(blob, filename)
}

export async function downloadTripCostPdf(params = {}) {
  let res
  try {
    res = await http.get('/reports/trip-costs/export-pdf', {
      params,
      responseType: 'blob',
      headers: { Accept: '*/*' },
      timeout: 90_000,
    })
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
  const blob = res.data
  if (!(blob instanceof Blob) || blob.size === 0) throw new Error('empty_response')
  const filename = `chi-phi-chuyen_${new Date().toISOString().slice(0, 10)}.pdf`
  saveAs(blob, filename)
}

