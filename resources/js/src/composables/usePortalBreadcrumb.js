import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { usePortalExtracurricularModule } from './usePortalExtracurricularModule'

/**
 * Breadcrumb segments for portal shell header (label + optional route).
 * Root «Điều vận» omitted — brand link in header already goes to home.
 * @returns {{ segments: import('vue').ComputedRef<Array<{ label: string, to?: object }>>, pageTitle: import('vue').ComputedRef<string> }}
 */
export function usePortalBreadcrumb() {
  const { t } = useI18n()
  const route = useRoute()
  const { isExtracurricularModule } = usePortalExtracurricularModule()

  const segments = computed(() => {
    const name = route.name

    if (name === 'portalHome') {
      return []
    }

    if (name === 'portalRequestList' || name === 'portalCreate') {
      return []
    }

    if (name === 'portalNotifications') {
      return [{ label: t('portal.shell.breadcrumb_notifications') }]
    }

    if (name === 'portalRequestDetail') {
      return [{ label: t('portal.shell.breadcrumb_detail') }]
    }

    if (isExtracurricularModule.value) {
      if (name === 'portalExtracurricularHome') {
        return []
      }
      if (name === 'portalExtracurricularList') {
        return [{ label: t('portal.shell.breadcrumb_list') }]
      }
      if (name === 'portalExtracurricularCreate') {
        return [{ label: t('portal.extracurricular_module.create_heading') }]
      }
      if (name === 'portalExtracurricularDetail') {
        return [
          { label: t('portal.shell.breadcrumb_list'), to: { name: 'portalExtracurricularList' } },
          { label: t('portal.shell.breadcrumb_detail') },
        ]
      }
    }

    return []
  })

  const pageTitle = computed(() => {
    const name = route.name
    if (name === 'portalHome') {
      return t('portal.dashboard_heading')
    }
    if (name === 'portalRequestList') {
      return t('portal.nav_list')
    }
    if (name === 'portalCreate') {
      return t('portal.shell.breadcrumb_create')
    }
    if (name === 'portalExtracurricularHome') {
      return t('portal.nav_extracurricular')
    }
    const segs = segments.value
    return segs[segs.length - 1]?.label ?? ''
  })

  return { segments, pageTitle }
}
