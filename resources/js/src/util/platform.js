/**
 * Heuristic cho Safari / WebKit trên iPhone, iPad (và iPadOS desktop UA).
 * @returns {boolean}
 */
export function isLikelyIos() {
  if (typeof navigator === 'undefined') {
    return false
  }
  if (/iPad|iPhone|iPod/i.test(navigator.userAgent)) {
    return true
  }
  return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1
}

/** PWA: đã Thêm vào Màn hình chính / standalone. */
export function isStandaloneDisplayMode() {
  if (typeof window === 'undefined') {
    return false
  }
  return (
    window.matchMedia?.('(display-mode: standalone)')?.matches === true
    || window.navigator.standalone === true
  )
}
