import { cloneDispatchRequest } from '../api/requests'
import { formatDispatchRequestRefCode } from '../util/portalRequestFormat'
import { confirmAction } from './useConfirm'

/**
 * Xác nhận rồi gọi API clone — tránh tạo phiếu mới do bấm nhầm.
 * @param {{ id?: number|string, created_at?: string|null }} req Phiếu nguồn (approved/rejected)
 * @param {(key: string, params?: Record<string, unknown>) => string} t
 * @returns {Promise<object|null>} Phiếu mới hoặc null nếu hủy / lỗi id
 */
export async function confirmAndCloneDispatchRequest(req, t) {
  if (req?.id == null || req.id === '') return null

  const code = formatDispatchRequestRefCode(req) || `#${req.id}`
  const ok = await confirmAction({
    title: t('request_detail.clone_confirm_title'),
    message: t('request_detail.clone_confirm_message', { code }),
    confirmLabel: t('request_detail.clone_confirm_btn'),
  })
  if (!ok) return null

  return cloneDispatchRequest(req.id)
}
