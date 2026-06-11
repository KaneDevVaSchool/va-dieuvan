/**
 * useHaptics — phản hồi rung (haptic) nhẹ cho thao tác trên mobile.
 *
 * Dùng Vibration API (navigator.vibrate). Bọc try/catch + feature-detect để
 * an toàn trên desktop / iOS Safari (không hỗ trợ → no-op, không lỗi).
 *
 * Quy ước cường độ:
 *   tap     — chạm nhẹ (mở rộng hàng, đổi tab)
 *   select  — chọn / chuyển trạng thái phụ
 *   impact  — hành động chính (bắt đầu / kết thúc chuyến)
 *   success — xác nhận thành công (đánh dấu đón)
 *   warn    — cảnh báo / từ chối
 */

function canVibrate() {
  return (
    typeof navigator !== 'undefined' &&
    typeof navigator.vibrate === 'function'
  )
}

function buzz(pattern) {
  if (!canVibrate()) return
  try {
    navigator.vibrate(pattern)
  } catch {
    /* ignore — một số trình duyệt chặn khi chưa có user gesture */
  }
}

export function useHaptics() {
  return {
    tap: () => buzz(10),
    select: () => buzz(8),
    impact: () => buzz(18),
    success: () => buzz([14, 36, 22]),
    warn: () => buzz([22, 48, 22]),
  }
}
