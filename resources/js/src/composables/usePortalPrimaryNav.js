import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  HomeIcon,
  ListBulletIcon,
} from '@heroicons/vue/24/outline'
import { usePortalExtracurricularModule } from './usePortalExtracurricularModule'

/**
 * Primary portal navigation — list targets follow active module (general vs ngoại khóa).
 */
export function usePortalPrimaryNav() {
  const { t } = useI18n()
  const route = useRoute()
  const { isExtracurricularModule, routes } = usePortalExtracurricularModule()

  const items = computed(() => {
    const r = routes.value

    return [
      {
        key: 'home',
        label: t('portal.nav_home'),
        icon: HomeIcon,
        to: { name: 'portalHome' },
        isActive: () =>
          route.name === 'portalHome' ||
          (!isExtracurricularModule.value && route.name === 'portalRequestDetail'),
      },
      {
        key: 'list',
        label: t('portal.nav_list'),
        icon: ListBulletIcon,
        to: { name: r.list },
        isActive: () =>
          route.name === r.list ||
          (isExtracurricularModule.value && route.name === r.detail),
      },
      {
        key: 'extracurricular',
        label: t('portal.nav_extracurricular'),
        shortLabel: t('portal.shell.bottom_recurring'),
        icon: AcademicCapIcon,
        to: { name: 'portalExtracurricularHome' },
        isActive: () => isExtracurricularModule.value,
      },
    ]
  })

  const createTo = computed(() => ({ name: 'portalCreate' }))

  return { items, createTo }
}
