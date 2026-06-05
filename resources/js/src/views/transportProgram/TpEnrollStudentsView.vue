<template>
  <div class="mx-auto max-w-3xl space-y-4 pb-6">
    <div>
      <button class="text-xs text-slate-400 hover:text-slate-600" @click="goBack">← Quay lại chương trình</button>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Đăng ký học sinh</h1>
      <p class="text-sm text-slate-500">Tìm và chọn học sinh để thêm vào chương trình.</p>
    </div>

    <Input v-model="search" label="Tìm học sinh" placeholder="Tên, mã, lớp…" />

    <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <label
        v-for="s in students"
        :key="s.id"
        class="flex cursor-pointer items-center gap-3 border-b border-slate-100 px-3 py-2 last:border-0 hover:bg-slate-50"
      >
        <input type="checkbox" :value="s.id" v-model="selected" class="h-4 w-4 accent-va-800" />
        <div class="flex-1">
          <div class="text-sm font-medium text-slate-900">{{ s.full_name }}</div>
          <div class="text-xs text-slate-500">{{ s.code }} · {{ s.class_name || '—' }}</div>
        </div>
      </label>
      <div v-if="!students.length" class="px-3 py-8 text-center text-sm text-slate-400">Không có học sinh.</div>
    </div>

    <div class="sticky bottom-0 z-20 border-t border-slate-200 bg-white/95 py-3 backdrop-blur">
      <div class="flex items-center justify-between">
        <span class="text-sm text-slate-600">Đã chọn {{ selected.length }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" @click="goBack">Hủy</Button>
          <Button :loading="saving" :disabled="!selected.length" @click="submit">Đăng ký {{ selected.length }} học sinh</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Input from '../../components/ui/Input.vue'
import Button from '../../components/ui/Button.vue'
import { listStudents, enrollStudents } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const students = ref([])
const selected = ref([])
const search = ref('')
let timer = null

async function load() {
  loading.value = true
  try {
    students.value = (await listStudents({ search: search.value || undefined, status: 'active', per_page: 50 }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})

function goBack() {
  router.push({ name: 'tpProgramWorkspace', params: { id: route.params.id } })
}

async function submit() {
  saving.value = true
  try {
    const res = await enrollStudents(route.params.id, selected.value)
    showAppSuccess(`Đã đăng ký ${res?.enrolled ?? selected.value.length} học sinh.`)
    goBack()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
