/**
 * Điểm mở rộng WebSocket / SSE — dùng chung contract với app mobile.
 */
export function createRealtimeConnection(_url, _handlers) {
  return {
    connect() {},
    disconnect() {},
    send(_payload) {},
  }
}
