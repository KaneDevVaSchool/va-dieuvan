/** Geocode free-text addresses via OSM Nominatim (respect rate limits between calls). */
const cache = new Map<string, { lat: number; lon: number }>()
const NOMINATIM = 'https://nominatim.openstreetmap.org/search'

export type MapPoint = { lat: number; lon: number }

export function delayMs(ms: number) {
  return new Promise((r) => setTimeout(r, ms))
}

export async function geocodeAddress(query: string): Promise<MapPoint | null> {
  const q = query?.trim()
  if (!q) return null
  const key = q.toLowerCase()
  if (cache.has(key)) return cache.get(key)!

  const url = `${NOMINATIM}?format=json&limit=1&q=${encodeURIComponent(q)}`
  const res = await fetch(url)
  if (!res.ok) return null
  const rows = await res.json()
  const row = Array.isArray(rows) ? rows[0] : null
  if (!row?.lat || !row?.lon) return null
  const lat = Number(row.lat)
  const lon = Number(row.lon)
  if (Number.isNaN(lat) || Number.isNaN(lon)) return null
  const pt = { lat, lon }
  cache.set(key, pt)
  return pt
}
