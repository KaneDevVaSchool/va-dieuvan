import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as admin from '../api/admin'
import { formatApiError } from '../api/http'
import { showAppError, showAppSuccess } from './appMessage'
import { confirmAction } from './useConfirm'
import { debounceTrailing } from './useDebounce'

/**
 * Quản lý gán vai trò nhân viên (1 vai trò / user).
 * Cung cấp state + actions cho SystemUserRolesView.vue.
 */
export function useUserRoleManager() {
  const route  = useRoute()
  const router = useRouter()

  // ── State ────────────────────────────────────────────────────────────────

  const loading      = ref(false)
  const savingIds    = ref(new Set())  // user IDs đang được lưu
  const savedIds     = ref(new Set())  // user IDs vừa lưu xong (hiện tick ✓)
  const bulkApplying = ref(false)

  const users  = ref([])
  const roles  = ref([])
  const meta   = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25, per_page_mode: 'paged', truncated: false, cap: null })

  // Lưu role ID hiện tại của mỗi user (null = chưa gán)
  const rowRoleId = reactive({})  // { [userId]: roleId | null }

  // Selections cho bulk
  const selectedIds = ref([])
  const bulkRoleId  = ref('')
  const bulkAction  = ref('assign')

  // Filters
  const filters = reactive({
    q:          '',
    assignment: 'all',
    per_page:   '25',
    roles:      [],  // filter by role name
  })

  // ── Computed ──────────────────────────────────────────────────────────────

  const roleMap    = computed(() => new Map(roles.value.map((r) => [r.id, r])))
  const allSelected = computed(() => users.value.length > 0 && users.value.every((u) => selectedIds.value.includes(u.id)))
  const someSelected = computed(() => selectedIds.value.length > 0 && !allSelected.value)
  const isLocked   = computed(() => loading.value || bulkApplying.value)

  const displayFrom = computed(() => {
    if (meta.value.per_page_mode === 'all') return 1
    return (meta.value.current_page - 1) * meta.value.per_page + 1
  })
  const displayTo = computed(() => {
    if (meta.value.per_page_mode === 'all') return users.value.length
    return Math.min(meta.value.current_page * meta.value.per_page, meta.value.total)
  })

  // ── URL sync ─────────────────────────────────────────────────────────────

  function readFiltersFromUrl() {
    const q = route.query
    if (q.q)          filters.q          = String(q.q)
    if (q.assignment)  filters.assignment = String(q.assignment)
    if (q.per_page)    filters.per_page   = String(q.per_page)
    if (q.roles)       filters.roles      = Array.isArray(q.roles) ? q.roles : [q.roles]
    if (q.page)        meta.value.current_page = Number(q.page) || 1
  }

  function pushFiltersToUrl(page = 1) {
    const q = {}
    if (filters.q.trim())              q.q = filters.q.trim()
    if (filters.assignment !== 'all')  q.assignment = filters.assignment
    if (filters.per_page !== '25')     q.per_page = filters.per_page
    if (filters.roles.length)          q.roles = filters.roles
    if (page > 1)                      q.page = page
    router.replace({ query: q }).catch(() => {})
  }

  // ── Load ─────────────────────────────────────────────────────────────────

  async function loadRoles() {
    try {
      roles.value = (await admin.listRoles()) ?? []
    } catch {
      roles.value = []
    }
  }

  async function loadUsers(page = 1) {
    loading.value = true
    try {
      const params = {
        q:          filters.q.trim() || undefined,
        assignment: filters.assignment !== 'all' ? filters.assignment : undefined,
        per_page:   filters.per_page,
        page,
      }
      if (filters.roles.length) params.roles = filters.roles

      const res = await admin.listUsers(params)
      users.value = res.items ?? []
      meta.value  = res.meta ?? meta.value

      // Khởi tạo rowRoleId từ dữ liệu user (lấy role đầu tiên nếu có)
      for (const u of users.value) {
        rowRoleId[u.id] = u.roles?.[0]?.id ?? null
      }

      // Reset selections khi load trang mới
      selectedIds.value = []
    } catch (e) {
      showAppError(formatApiError(e))
    } finally {
      loading.value = false
    }
  }

  async function bootstrap() {
    readFiltersFromUrl()
    await Promise.all([loadRoles(), loadUsers(meta.value.current_page)])
  }

  function applyFilters() {
    meta.value.current_page = 1
    pushFiltersToUrl(1)
    loadUsers(1)
  }

  function goPage(page) {
    meta.value.current_page = page
    pushFiltersToUrl(page)
    loadUsers(page)
  }

  // ── Auto-save per user ────────────────────────────────────────────────────

  async function saveUserRole(user) {
    const newRoleId = rowRoleId[user.id]

    savingIds.value.add(user.id)
    savedIds.value.delete(user.id)

    try {
      if (newRoleId) {
        // Gán vai trò mới
        await admin.syncUserRoles(user.id, [newRoleId])
        // Cập nhật local state
        const role = roleMap.value.get(newRoleId)
        if (role) user.roles = [role]
      } else {
        // Gỡ vai trò: dùng bulk endpoint với action remove
        const currentRoleName = user.roles?.[0]?.name
        if (currentRoleName) {
          await admin.bulkUpdateUserRoles({
            user_ids: [user.id],
            roles:    [currentRoleName],
            action:   'remove',
          })
          user.roles = []
        }
      }
      // Flash ✓
      savedIds.value.add(user.id)
      setTimeout(() => savedIds.value.delete(user.id), 3000)
    } catch (e) {
      showAppError(formatApiError(e))
      // Revert local dropdown
      rowRoleId[user.id] = user.roles?.[0]?.id ?? null
    } finally {
      savingIds.value.delete(user.id)
    }
  }

  // Map userId → debounced save function (tránh tạo mới mỗi render)
  const debouncedSavers = new Map()

  function onRoleChange(user) {
    if (!debouncedSavers.has(user.id)) {
      debouncedSavers.set(user.id, debounceTrailing(() => saveUserRole(user), 500))
    }
    debouncedSavers.get(user.id)()
  }

  // ── Selection ─────────────────────────────────────────────────────────────

  function toggleSelect(userId) {
    const i = selectedIds.value.indexOf(userId)
    if (i >= 0) selectedIds.value.splice(i, 1)
    else selectedIds.value.push(userId)
  }

  function toggleSelectAll() {
    if (allSelected.value) {
      selectedIds.value = []
    } else {
      selectedIds.value = users.value.map((u) => u.id)
    }
  }

  function clearSelection() {
    selectedIds.value = []
    bulkRoleId.value  = ''
    bulkAction.value  = 'assign'
  }

  // ── Bulk apply ────────────────────────────────────────────────────────────

  async function applyBulk() {
    if (!selectedIds.value.length) return

    if (bulkAction.value === 'assign' && !bulkRoleId.value) {
      showAppError('Vui lòng chọn vai trò muốn đặt.')
      return
    }

    const role       = bulkRoleId.value ? roleMap.value.get(Number(bulkRoleId.value)) : null
    const roleLabel  = role ? (role.display_name || role.name) : '(gỡ hết)'
    const actionLabel = bulkAction.value === 'assign' ? `đặt vai trò «${roleLabel}»` : `gỡ vai trò «${roleLabel}»`

    const ok = await confirmAction({
      title:        `Áp dụng cho ${selectedIds.value.length} nhân viên?`,
      message:      `Xác nhận ${actionLabel} cho ${selectedIds.value.length} nhân viên đang chọn?`,
      confirmLabel: 'Áp dụng',
    })
    if (!ok) return

    bulkApplying.value = true
    try {
      await admin.bulkUpdateUserRoles({
        user_ids: selectedIds.value,
        roles:    role ? [role.name] : [],
        action:   bulkAction.value,
      })
      showAppSuccess(`Đã ${actionLabel} cho ${selectedIds.value.length} nhân viên.`)
      clearSelection()
      await loadUsers(meta.value.current_page)
    } catch (e) {
      showAppError(formatApiError(e))
    } finally {
      bulkApplying.value = false
    }
  }

  // ── Search debounce ───────────────────────────────────────────────────────

  const bumpSearch = debounceTrailing(applyFilters, 400)

  return {
    // state
    loading, savingIds, savedIds, bulkApplying,
    users, roles, meta, rowRoleId,
    selectedIds, bulkRoleId, bulkAction,
    filters,
    // computed
    roleMap, allSelected, someSelected, isLocked, displayFrom, displayTo,
    // actions
    bootstrap, applyFilters, goPage, onRoleChange,
    toggleSelect, toggleSelectAll, clearSelection, applyBulk,
    bumpSearch,
  }
}
