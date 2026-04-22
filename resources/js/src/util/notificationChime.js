/**
 * Âm chuông ngắn bằng Web Audio (không cần file).
 * Một số trình duyệt yêu cầu tương tác người dùng trước khi phát âm.
 */
export function playNotificationChime() {
  if (typeof window === 'undefined' || !window.AudioContext && !window.webkitAudioContext) {
    return
  }
  try {
    const Ctx = window.AudioContext || window.webkitAudioContext
    const ctx = new Ctx()
    const o = ctx.createOscillator()
    const g = ctx.createGain()
    o.type = 'sine'
    o.frequency.setValueAtTime(880, ctx.currentTime)
    o.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12)
    g.gain.setValueAtTime(0.12, ctx.currentTime)
    g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25)
    o.connect(g)
    g.connect(ctx.destination)
    o.start(ctx.currentTime)
    o.stop(ctx.currentTime + 0.25)
  } catch {
    /* bỏ qua */
  }
}
