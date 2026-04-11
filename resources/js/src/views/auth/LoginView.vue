<template>
  <div class="flex min-h-dvh items-center justify-center bg-slate-100 px-4">
    <div class="w-full max-w-md rounded-2xl border bg-white p-6 shadow-sm">
      <div class="text-center">
        <AppLogo size="xl" align="center" class="mx-auto" />
        <div class="mt-4 text-lg font-semibold text-va-900">Phần mềm điều vận</div>
        <div class="mt-1 text-sm text-slate-500">Đăng nhập để tiếp tục</div>
      </div>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <Input v-model="email" label="Email" type="email" autocomplete="username" placeholder="you@example.com" />
        <Input v-model="password" label="Mật khẩu" type="password" autocomplete="current-password" />

        <div v-if="error" class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>

        <Button class="w-full" :loading="loading" type="submit">Đăng nhập</Button>
      </form>

      <p class="mt-4 text-center text-xs text-slate-500">
        Token Sanctum được lưu localStorage (Bearer). Production nên dùng cookie HttpOnly + CSRF nếu cùng domain.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../store'
import AppLogo from '../../components/branding/AppLogo.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import { formatApiError } from '../../api/http'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    const redirect = route.query.redirect ?? '/'
    await router.replace(String(redirect))
  } catch (e) {
    error.value = formatApiError(e, 'Đăng nhập thất bại.')
  } finally {
    loading.value = false
  }
}
</script>
