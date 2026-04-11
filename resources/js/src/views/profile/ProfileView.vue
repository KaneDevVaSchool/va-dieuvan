<template>
  <div class="mx-auto max-w-lg space-y-4">
    <Card title="Hồ sơ tài khoản">
      <p class="mb-4 text-sm text-slate-600">
        Thông tin từ máy chủ sau đăng nhập. Cập nhật hồ sơ (đổi mật khẩu, SĐT) sẽ bổ sung khi có API quản trị người dùng.
      </p>
      <div v-if="!auth.user" class="text-sm text-slate-500">Chưa có dữ liệu người dùng.</div>
      <dl v-else class="space-y-3 text-sm">
        <div class="flex flex-col gap-0.5 border-b border-slate-100 pb-2 sm:flex-row sm:justify-between">
          <dt class="text-slate-500">Họ tên</dt>
          <dd class="font-medium text-slate-900">{{ auth.user.name }}</dd>
        </div>
        <div class="flex flex-col gap-0.5 border-b border-slate-100 pb-2 sm:flex-row sm:justify-between">
          <dt class="text-slate-500">Email</dt>
          <dd class="break-all font-medium text-slate-900">{{ auth.user.email }}</dd>
        </div>
        <div class="flex flex-col gap-0.5 border-b border-slate-100 pb-2 sm:flex-row sm:justify-between">
          <dt class="text-slate-500">Số điện thoại</dt>
          <dd class="font-medium text-slate-900">{{ auth.user.phone ?? '—' }}</dd>
        </div>
        <div class="flex flex-col gap-0.5 border-b border-slate-100 pb-2 sm:flex-row sm:justify-between">
          <dt class="text-slate-500">Mã nhân viên</dt>
          <dd class="font-medium text-slate-900">{{ auth.user.employee_code ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-500">Vai trò</dt>
          <dd class="mt-1 flex flex-wrap gap-1">
            <span
              v-for="role in auth.user.roles ?? []"
              :key="role.id"
              class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800"
            >
              {{ role.display_name ?? role.name }}
            </span>
            <span v-if="!(auth.user.roles ?? []).length" class="text-slate-400">—</span>
          </dd>
        </div>
      </dl>
      <div class="mt-4 flex flex-wrap gap-2">
        <Button :loading="refreshing" type="button" @click="refresh">Làm mới từ máy chủ</Button>
        <RouterLink class="rounded-md border px-3 py-2 text-sm hover:bg-slate-50" to="/hub">Về trung tâm vận hành</RouterLink>
      </div>
      <p v-if="msg" class="mt-2 text-sm text-emerald-700">{{ msg }}</p>
      <p v-if="err" class="mt-2 text-sm text-rose-600">{{ err }}</p>
    </Card>

    <Card title="Bảo mật phiên đăng nhập">
      <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
        <li>Ứng dụng lưu token Sanctum trong trình duyệt (localStorage).</li>
        <li>Đăng xuất trên thiết bị dùng chung sau khi xong việc.</li>
        <li>Production: ưu tiên cookie HttpOnly + CSRF nếu cùng domain với API.</li>
      </ul>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'

const auth = useAuthStore()
const refreshing = ref(false)
const msg = ref('')
const err = ref('')

async function refresh() {
  msg.value = ''
  err.value = ''
  refreshing.value = true
  try {
    await auth.fetchMe()
    msg.value = 'Đã cập nhật.'
  } catch (e) {
    err.value = formatApiError(e, 'Không tải được hồ sơ.')
  } finally {
    refreshing.value = false
  }
}
</script>
