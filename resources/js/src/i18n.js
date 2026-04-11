import { createI18n } from 'vue-i18n'
import vi from './locales/vi.json'
import en from './locales/en.json'

const saved = typeof localStorage !== 'undefined' ? localStorage.getItem('locale') : null

export const i18n = createI18n({
  legacy: false,
  locale: saved === 'en' ? 'en' : 'vi',
  fallbackLocale: 'vi',
  messages: { vi, en },
})

export function setLocale(lang) {
  const next = lang === 'en' ? 'en' : 'vi'
  i18n.global.locale.value = next
  localStorage.setItem('locale', next)
}
