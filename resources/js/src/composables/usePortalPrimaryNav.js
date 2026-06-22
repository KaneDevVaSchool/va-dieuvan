import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  HomeIcon,
  ListBulletIcon,
  PlusCircleIcon,
} from '@heroicons/vue/24/outline'
import { usePortalExtracurricularModule } from './usePortalExtracurricularModule'

/**
 * Primary portal navigation — list/create targets follow active module (general vs ngoại khóa).
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
      {
        key: 'create',
        label: t('portal.nav_create'),
        shortLabel: t('portal.shell.bottom_create'),
        icon: PlusCircleIcon,
        to: { name: r.create },
        isActive: () => route.name === r.create,
        emphasize: true,
      },
    ]
  })

  const createTo = computed(() => ({ name: routes.value.create }))

  return { items, createTo }
}
