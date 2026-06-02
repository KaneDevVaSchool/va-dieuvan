import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'

/**
 * Việc cần làm + đếm badge tab trên chi tiết phiếu.
 * @param {import('vue').Ref|import('vue').ComputedRef} reqRef
 * @param {import('vue').Ref|import('vue').ComputedRef} ctxRef — flags từ view
 */
export function useRequestWorkflowSteps(reqRef, ctxRef) {
  const { t } = useI18n()

  const formTabActionCount = computed(() => {
    const c = unref(ctxRef)
    if (!c) return 0
    let n = 0
    if (c.showFillPriceSection) n += 1
    if (c.showDeptDecisionSection) n += 1
    return n
  })

  const studentsTabActionCount = computed(() => {
    const c = unref(ctxRef)
    if (!c) return 0
    return c.showPassengerAdjustSection ? 1 : 0
  })

  const docsTabActionCount = computed(() => {
    const c = unref(ctxRef)
    if (!c) return 0
    let n = 0
    if (c.docsTabNeedsFocus) n += 1
    if (c.docsNeedsPaperScan) n += 1
    return n
  })

  const todoItems = computed(() => {
    const r = unref(reqRef)
    const c = unref(ctxRef)
    if (!r || !c) return []

    const items = []

    if (c.showFillPriceSection) {
      items.push({
        key: 'fill-price',
        label: t('request_detail.todo_fill_price'),
        done: false,
        tab: 'form',
        focus: 'fill-price',
        priority: 1,
      })
    }

    if (c.showDeptDecisionSection) {
      items.push({
        key: 'dept-decision',
        label: t('request_detail.todo_dept_decision'),
        done: false,
        tab: 'form',
        focus: 'dept-decision',
        priority: 2,
      })
    }

    if (c.docsTabNeedsFocus) {
      items.push({
        key: 'signed-paper',
        label: t('request_detail.todo_signed_paper'),
        done: false,
        tab: 'docs',
        focus: 'docs',
        priority: 3,
      })
    }

    if (c.docsNeedsPaperScan) {
      items.push({
        key: 'paper-scan',
        label: t('request_detail.todo_paper_scan'),
        done: false,
        tab: 'docs',
        focus: 'docs-upload',
        priority: 4,
      })
    }

    if (c.showPassengerAdjustSection) {
      items.push({
        key: 'passenger-adjust',
        label: t('request_detail.todo_passenger_adjust'),
        done: false,
        tab: 'students',
        focus: 'passenger-adjust',
        priority: 5,
      })
    }

    return items.filter((x) => !x.done).sort((a, b) => a.priority - b.priority)
  })

  return {
    formTabActionCount,
    studentsTabActionCount,
    docsTabActionCount,
    todoItems,
  }
}
