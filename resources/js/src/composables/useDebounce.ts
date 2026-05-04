type AnyFn = (...args: unknown[]) => unknown

/** Trailing debounce — gọi `fn` sau `ms` ms kể từ lần gọi cuối. */
export function debounceTrailing<Fn extends AnyFn>(fn: Fn, ms: number) {
  let t: ReturnType<typeof setTimeout> | null = null
  return (...args: Parameters<Fn>) => {
    if (t) clearTimeout(t)
    t = setTimeout(() => {
      t = null
      fn(...args)
    }, ms)
  }
}
