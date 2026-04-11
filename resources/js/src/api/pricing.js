import { http } from './http'

export async function getReferencePricing() {
  const { data } = await http.get('/reference-pricing')
  return data.data
}
