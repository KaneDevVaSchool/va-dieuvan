<template>
  <div class="mx-auto max-w-6xl px-4 py-6 sm:px-5">
    <header class="mb-6">
      <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        Hồ sơ tài khoản
      </h1>
      <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
        Thông tin từ máy chủ sau đăng nhập. Cập nhật chi tiết sẽ bổ sung khi có API quản trị.
      </p>
    </header>

    <div v-if="!auth.user" class="rounded-xl border border-slate-200/90 bg-white p-8 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
      Chưa có dữ liệu người dùng.
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-12 lg:items-start">
      <!-- Cột trái: tóm tắt -->
      <aside class="lg:col-span-4">
        <div
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80"
        >
          <div
            class="h-24 bg-gradient-to-br from-va-800 via-va-700 to-va-900 dark:from-va-950 dark:via-va-900 dark:to-slate-950"
            aria-hidden="true"
          />
          <div class="relative -mt-12 flex flex-col items-center px-4 pb-5 pt-0">
            <UserAvatar
              class="!h-24 !w-24 !text-2xl ring-4 ring-white dark:ring-slate-900"
              :name="auth.user.name"
              :email="auth.user.email"
              :avatar-url="auth.user.avatar_url"
              :title="auth.user.name ?? ''"
              size="lg"
              ring-prominent
            />
            <p class="mt-3 text-center text-base font-semibold text-slate-900 dark:text-slate-100">
              {{ auth.user.name }}
            </p>
            <p class="mt-0.5 max-w-full truncate text-center text-xs text-slate-500 dark:text-slate-400">
              {{ auth.user.email }}
            </p>
            <div class="mt-3 flex max-w-full flex-wrap justify-center gap-1.5">
              <span
                v-for="role in auth.user.roles ?? []"
                :key="role.id"
                class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200"
              >
                {{ role.display_name ?? role.name }}
              </span>
              <span
                v-if="!(auth.user.roles ?? []).length"
                class="text-[11px] text-slate-400"
              >
                —
              </span>
            </div>
            <div class="mt-5 flex w-full flex-col gap-2 sm:flex-row sm:justify-center">
              <Button :loading="refreshing" type="button" class="w-full sm:w-auto" @click="refresh">
                Làm mới từ máy chủ
              </Button>
              <RouterLink
                class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto"
                to="/hub"
              >
                Trung tâm vận hành
              </RouterLink>
            </div>
          </div>
        </div>
      </aside>

      <!-- Cột phải: bảng chi tiết + bảo mật -->
      <div class="space-y-6 lg:col-span-8">
        <section
          class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80"
        >
          <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700/80">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Thông tin chi tiết
            </h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[280px] text-sm">
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <tr>
                  <th
                    scope="row"
                    class="w-[38%] whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Họ tên
                  </th>
                  <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                    {{ auth.user.name }}
                  </td>
                </tr>
                <tr>
                  <th
                    scope="row"
                    class="whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Email
                  </th>
                  <td class="break-all px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                    {{ auth.user.email }}
                  </td>
                </tr>
                <tr>
                  <th
                    scope="row"
                    class="whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Số điện thoại
                  </th>
                  <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                    {{ auth.user.phone ?? '—' }}
                  </td>
                </tr>
                <tr>
                  <th
                    scope="row"
                    class="whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Mã nhân viên
                  </th>
                  <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                    {{ auth.user.employee_code ?? '—' }}
                  </td>
                </tr>
                <tr>
                  <th
                    scope="row"
                    class="align-top whitespace-nowrap bg-slate-50/80 px-4 py-3 text-left font-medium text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                  >
                    Vai trò
                  </th>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="role in auth.user.roles ?? []"
                        :key="role.id"
                        class="rounded-full bg-va-50 px-2.5 py-0.5 text-xs font-medium text-va-900 dark:bg-va-950/50 dark:text-va-100"
                      >
                        {{ role.display_name ?? role.name }}
                      </span>
                      <span v-if="!(auth.user.roles ?? []).length" class="text-slate-400">—</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="msg" class="border-t border-slate-100 px-4 py-2 text-sm text-emerald-700 dark:border-slate-700 dark:text-emerald-400">
            {{ msg }}
          </p>
          <p v-if="err" class="border-t border-slate-100 px-4 py-2 text-sm text-rose-600 dark:border-slate-700">
            {{ err }}
          </p>
        </section>

        <Card title="Bảo mật phiên đăng nhập">
          <ul class="list-disc space-y-1.5 pl-5 text-sm text-slate-600 dark:text-slate-400">
            <li>Ứng dụng lưu token Sanctum trong trình duyệt (localStorage).</li>
            <li>Đăng xuất trên thiết bị dùng chung sau khi xong việc.</li>
            <li>Production: ưu tiên cookie HttpOnly + CSRF nếu cùng domain với API.</li>
          </ul>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import UserAvatar from '../../components/branding/UserAvatar.vue'
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
