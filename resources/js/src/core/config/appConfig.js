/**
 * Cấu hình tập trung — PWA & API.
 */
export const APP_CONFIG = {
  apiBase: '/api',
  /** Gắn Sentry / OpenTelemetry SDK tại đây khi triển khai */
  telemetryEndpoint: '/api/telemetry/frontend',
  idbName: 'va-dispatch',
  idbVersion: 1,
  outboxStore: 'outbox',
}
