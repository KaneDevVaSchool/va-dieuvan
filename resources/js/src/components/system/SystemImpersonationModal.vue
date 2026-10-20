<template>
  <Teleport to="body">
    <div v-if="user" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="impersonate-title" @click.self="close" @keydown.esc="close">
      <form class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900" @submit.prevent="start">
        <h2 id="impersonate-title" class="text-base font-semibold text-slate-900 dark:text-white">Giả lập người dùng</h2>
        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">Bạn sẽ trải nghiệm hệ thống với tư cách “{{ user.name }}”. Mọi thao tác ghi sẽ được ghi nhật ký dưới tài khoản này.</p>
        <label for="impersonate-role" class="mt-4 block text-sm font-medium text-slate-700 dark:text-slate-200">Chọn vai trò để trải nghiệm</label>
        <select id="impersonate-role" v-model="selectedRole" :disabled="busy" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
          <option value="" disabled>Chọn vai trò</option>
          <option v-for="role in availableRoles" :key="role.name" :value="role.name">{{ role.display_name || role.name }}</option>
        </select>
        <p v-if="error" role="alert" class="mt-3 text-sm text-red-600">{{ error }}</p>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" :disabled="busy" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="close">Hủy</button>
          <button type="submit" :disabled="busy || !selectedRole" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50">{{ busy ? 'Đang giả lập…' : 'Giả lập' }}</button>
        </div>
      </form>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'

const props = defineProps({ user: { type: Object, default: null } })
const emit = defineEmits(['close'])
const auth = useAuthStore()
const busy = ref(false)
const error = ref('')
const selectedRole = ref('')
const availableRoles = computed(() => (props.user?.roles ?? []).filter(role => role.name !== 'superadmin'))
watch(() => props.user, () => {
  error.value = ''
  selectedRole.value = availableRoles.value.length === 1 ? availableRoles.value[0].name : ''
})
function close() { if (!busy.value) emit('close') }
async function start() {
  if (busy.value || !selectedRole.value) return
  busy.value = true
  error.value = ''
  try {
    const home = await auth.startImpersonation(props.user.id, selectedRole.value)
    window.location.replace(home)
  } catch (e) {
    error.value = formatApiError(e, 'Không thể bắt đầu phiên giả lập.')
    busy.value = false
  }
}
</script>
