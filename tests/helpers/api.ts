import { APIRequestContext } from '@playwright/test'

export class ApiHelper {
  constructor(
    private readonly request: APIRequestContext,
    private readonly token: string,
  ) {}

  private headers() {
    return {
      Authorization: `Bearer ${this.token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    }
  }

  async createDispatchRequest(data: Record<string, unknown>) {
    const res = await this.request.post('/api/dispatch-requests', {
      headers: this.headers(),
      data,
    })
    return res.json()
  }

  async createTrip(dispatchRequestId: number) {
    const res = await this.request.post('/api/trips', {
      headers: this.headers(),
      data: { dispatch_request_id: dispatchRequestId },
    })
    return res.json()
  }

  async getTrip(tripId: number) {
    const res = await this.request.get(`/api/trips/${tripId}`, {
      headers: this.headers(),
    })
    return res.json()
  }

  async assignTrip(tripId: number, data: Record<string, unknown>) {
    const res = await this.request.post(`/api/trips/${tripId}/assign`, {
      headers: this.headers(),
      data,
    })
    return res.json()
  }
}
