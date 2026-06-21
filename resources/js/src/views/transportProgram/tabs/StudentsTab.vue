<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Học sinh đã đăng ký</h2>
        <p class="text-sm text-slate-500">{{ items.length }} học sinh<span v-if="capacity"> · còn trống {{ Math.max(0, capacity - items.length) }} chỗ</span></p>
      </div>
      <Button @click="goEnroll"><PlusIcon class="h-4 w-4" /> Đăng ký học sinh</Button>
    </div>

    <!-- Capacity bar -->
    <div v-if="capacity" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-1.5 flex items-center justify-between text-sm">
        <span class="text-slate-500">Tỉ lệ lấp đầy</span>
        <span class="font-semibold text-slate-700">{{ items.length }}/{{ capacity }} chỗ · {{ fillPct }}%</span>
      </div>
      <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full transition-all" :class="fillPct >= 100 ? 'bg-rose-500' : 'bg-emerald-500'" :style="{ width: Math.min(100, fillPct) + '%' }"></div>
      </div>
    </div>

    <!-- Search -->
    <div class="relative">
      <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
      <input
        v-model="search"
        type="search"
        placeholder="Tìm theo tên, mã hoặc lớp…"
        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-3 text-base outline-none ring-va-800/20 transition focus:ring"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!filtered.length" class="rounded-2xl border border-slate-200 bg-white py-16 text-center">
      <UsersIcon class="mx-auto mb-3 h-12 w-12 text-slate-300" />
      <p class="text-base font-medium text-slate-600">{{ search ? 'Không tìm thấy học sinh phù hợp' : 'Chưa có học sinh nào đăng ký' }}</p>
      <Button v-if="!search" class="mt-3" @click="goEnroll">Đăng ký học sinh đầu tiên</Button>
    </div>
    <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full min-w-[40rem] text-left text-base">
        <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Học sinh</th>
            <th class="px-4 py-3 font-medium">Mã</th>
            <th class="px-4 py-3 font-medium">Lớp</th>
            <th class="px-4 py-3 text-right font-medium">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in filtered" :key="s.student_id" class="hover:bg-slate-50/60">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-va-800/10 text-xs font-semibold text-va-800">
                  {{ initials(s.full_name) }}
                </span>
                <span class="font-medium text-slate-900">{{ s.full_name }}</span>
              </div>
            </td>
            <td class="px-4 py-3 font-mono text-sm text-slate-500">{{ s.code }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ s.class_name || t('tp_program_detail.empty_class') }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <button class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50" @click="unenroll(s)">
                <TrashIcon class="h-4 w-4" /> Hủy
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PlusIcon, TrashIcon, MagnifyingGlassIcon, ArrowPathIcon, UsersIcon } from '@heroicons/vue/24/outline'
import Button from '../../../components/ui/Button.vue'
import { listEnrollments, unenrollStudent } from '../../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../../composables/appMessage'
import { confirmAction } from '../../../composables/useConfirm'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()
const { t } = useI18n()
const loading = ref(false)
const items = ref([])
const search = ref('')

const capacity = computed(() => Number(props.program?.settings?.vehicle?.max_capacity || 0))
const fillPct = computed(() => (capacity.value ? Math.round((items.value.length / capacity.value) * 100) : 0))

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((s) =>
    [s.full_name, s.code, s.class_name].filter(Boolean).some((v) => String(v).toLowerCase().includes(q)),
  )
})

function initials(name) {
  if (!name) return '?'
  return name.trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
}

async function load() {
  loading.value = true
  try {
    items.value = (await listEnrollments(props.program.id))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function goEnroll() {
  router.push({ name: 'tpEnrollStudents', params: { id: props.program.id } })
}

async function unenroll(s) {
  const ok = await confirmAction({ title: 'Hủy đăng ký', message: `Hủy đăng ký ${s.full_name}?`, danger: true, confirmLabel: 'Hủy đăng ký' })
  if (!ok) return
  try {
    await unenrollStudent(props.program.id, s.student_id, 'Hủy bởi quản trị')
    showAppSuccess('Đã hủy đăng ký.')
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

onMounted(load)
</script>
