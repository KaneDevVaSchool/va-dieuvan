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

    if (name === 'portalRequestList') {
      return [{ label: t('portal.shell.breadcrumb_requests') }]
    }

    if (name === 'portalCreate') {
      return [{ label: t('portal.shell.breadcrumb_create') }]
    }

    if (name === 'portalNotifications') {
      return [{ label: t('portal.shell.breadcrumb_notifications') }]
    }

    if (name === 'portalRequestDetail') {
      return [
        { label: t('portal.shell.breadcrumb_requests'), to: { name: 'portalRequestList' } },
        { label: t('portal.shell.breadcrumb_detail') },
      ]
    }

    if (isExtracurricularModule.value) {
      const ecRoot = {
        label: t('portal.shell.breadcrumb_recurring'),
        to: { name: 'portalExtracurricularHome' },
      }
      if (name === 'portalExtracurricularHome') {
        return [{ label: t('portal.shell.breadcrumb_recurring') }]
      }
      if (name === 'portalExtracurricularList') {
        return [ecRoot, { label: t('portal.shell.breadcrumb_list') }]
      }
      if (name === 'portalExtracurricularCreate') {
        return [ecRoot, { label: t('portal.extracurricular_module.create_heading') }]
      }
      if (name === 'portalExtracurricularDetail') {
        return [
          ecRoot,
          { label: t('portal.shell.breadcrumb_list'), to: { name: 'portalExtracurricularList' } },
          { label: t('portal.shell.breadcrumb_detail') },
        ]
      }
    }

    return []
  })

  const pageTitle = computed(() => {
    if (route.name === 'portalHome') {
      return t('portal.dashboard_heading')
    }
    const segs = segments.value
    return segs[segs.length - 1]?.label ?? ''
  })

  return { segments, pageTitle }
}
