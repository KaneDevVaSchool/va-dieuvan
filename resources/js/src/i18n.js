import { createI18n } from 'vue-i18n'
import vi from './locales/vi.json'
import en from './locales/en.json'
import dwVi from './locales/dispatch-wizard.vi.json'
import dwEn from './locales/dispatch-wizard.en.json'

const saved = typeof localStorage !== 'undefined' ? localStorage.getItem('locale') : null

export const i18n = createI18n({
  legacy: false,
  locale: saved === 'en' ? 'en' : 'vi',
  fallbackLocale: 'vi',
  messages: {
    vi: { ...vi, ...dwVi },
    en: { ...en, ...dwEn },
  },
})

export function setLocale(lang) {
  const next = lang === 'en' ? 'en' : 'vi'
  i18n.global.locale.value = next
  localStorage.setItem('locale', next)
}
