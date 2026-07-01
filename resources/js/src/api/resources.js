import { http } from './http'

export async function downloadVehicleImportSample() {
  const { data } = await http.get('/vehicles/import-sample', {
    responseType: 'blob',
    headers: { Accept: '*/*' },
  })
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', 'mau-import-phuong-tien.xlsx')
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export async function importVehiclesFile(file) {
  const form = new FormData()
  form.append('file', file, file.name || 'import.xlsx')
  const { data } = await http.post('/vehicles/import', form)
  return data.data
}

export async function exportVehiclesListXlsx(params = {}) {
  const { data } = await http.get('/vehicles/export', {
    params,
    responseType: 'blob',
    headers: { Accept: '*/*' },
  })
  const stamp = new Date().toISOString().slice(0, 10).replace(/-/g, '')
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `danh-sach-phuong-tien_${stamp}.xlsx`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export async function downloadDriverImportSample() {
  const { data } = await http.get('/drivers/import-sample', {
    responseType: 'blob',
    headers: { Accept: '*/*' },
  })
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', 'mau-import-tai-xe.xlsx')
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export async function importDriversFile(file) {
  const form = new FormData()
  form.append('file', file, file.name || 'import.xlsx')
  const { data } = await http.post('/drivers/import', form)
  return data.data
}

export async function exportDriversListXlsx(params = {}) {
  const { data } = await http.get('/drivers/export', {
    params,
    responseType: 'blob',
    headers: { Accept: '*/*' },
  })
  const stamp = new Date().toISOString().slice(0, 10).replace(/-/g, '')
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `danh-sach-tai-xe_${stamp}.xlsx`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}
