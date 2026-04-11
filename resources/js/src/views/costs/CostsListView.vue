<template>
  <div class="space-y-4">
    <Card>
      <div class="grid gap-3 md:grid-cols-4">
        <Input v-model="filters.status" label="Trạng thái" placeholder="submitted" />
        <Input v-model.number="filters.trip_id" label="Trip ID" type="number" />
        <Input v-model="filters.from" label="Từ" type="date" />
        <Input v-model="filters.to" label="Đến" type="date" />
      </div>
      <Button class="mt-3" variant="secondary" :loading="loading" @click="reload">Lọc</Button>
    </Card>

    <Card title="Chi phí">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
          <thead class="border-b text-xs text-slate-500">
            <tr>
              <th class="py-2">ID</th>
              <th>Trip</th>
              <th>Loại</th>
              <th>Số tiền</th>
              <th>TT</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in items" :key="c.id" class="border-b">
              <td class="py-2">{{ c.id }}</td>
              <td>
                <RouterLink v-if="c.trip_id" class="underline" :to="`/trips/${c.trip_id}`">#{{ c.trip_id }}</RouterLink>
              </td>
              <td>{{ c.type }}</td>
              <td>{{ c.amount }} {{ c.currency }}</td>
              <td>{{ c.status }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!items.length" class="py-6 text-center text-sm text-slate-500">Không có dữ liệu.</div>
      </div>

      <div class="mt-4 flex justify-between text-sm">
        <span class="text-slate-500">Total {{ meta.total ?? 0 }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="page(-1)">Trước</Button>
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)" @click="page(1)">
            Sau
          </Button>
        </div>
      </div>
    </Card>

    <Card title="Nhập chi phí nhanh (cần trip ID)">
      <form class="grid gap-3 md:grid-cols-2" @submit.prevent="submitCost">
        <Input v-model.number="costForm.trip_id" label="Trip ID" type="number" />
        <Select v-model="costForm.type" label="Loại">
          <option value="fuel">fuel</option>
          <option value="toll">toll</option>
          <option value="parking">parking</option>
          <option value="other">other</option>
        </Select>
        <Input v-model.number="costForm.amount" label="Số tiền" type="number" />
        <Input v-model="costForm.description" label="Mô tả" />
        <div class="md:col-span-2 flex items-center gap-2">
          <Button :loading="submitting" type="submit">Gửi chi phí</Button>
          <span v-if="costMsg" class="text-sm text-slate-600">{{ costMsg }}</span>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { listTripCosts, submitTripCost } from '../../api/costs'
import { newIdempotencyKey } from '../../util/idempotency'

const loading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', trip_id: '', from: '', to: '', page: 1, per_page: 25 })

const costForm = ref({ trip_id: '', type: 'fuel', amount: '', description: '' })
const submitting = ref(false)
const costMsg = ref('')

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    Object.keys(p).forEach((k) => (p[k] === '' ? delete p[k] : null))
    const res = await listTripCosts(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

async function submitCost() {
  costMsg.value = ''
  submitting.value = true
  try {
    await submitTripCost(
      costForm.value.trip_id,
      {
        type: costForm.value.type,
        amount: costForm.value.amount,
        description: costForm.value.description || null,
      },
      { idempotencyKey: newIdempotencyKey() },
    )
    costMsg.value = 'Đã gửi'
    await reload()
  } catch (e) {
    costMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    submitting.value = false
  }
}

onMounted(reload)
</script>
