import { useI18n } from 'vue-i18n'

/**
 * Nhãn ngắn cho thanh điều hướng ngang (desktop/tablet); dropdown vẫn dùng `t(labelKey)` đầy đủ.
 */
export function useNavBarLabel() {
  const { t, te } = useI18n()
  return function navBarLabel(labelKey) {
    if (typeof labelKey !== 'string' || !labelKey.startsWith('nav.')) {
      return t(labelKey)
    }
    const suffix = labelKey.slice(4)
    const barKey = `nav.bar.${suffix}`
    return te(barKey) ? t(barKey) : t(labelKey)
  }
}
