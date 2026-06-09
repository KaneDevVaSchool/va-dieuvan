# UI Rules — VA Điều Vận (Vue 3 + Tailwind)

## Component Design Principles

### 1. Composition API + `<script setup>`
Luôn dùng `<script setup>` (không dùng Options API).

### 2. Single Responsibility
Mỗi component chỉ làm 1 việc. Tách thành sub-components khi vượt ~200 lines template.

### 3. Props + Emits — không mutate props
```vue
<!-- ĐÚNG: emit để parent xử lý -->
const emit = defineEmits<{ updated: [Trip] }>()
function handleSave() {
  emit('updated', updatedTrip)
}

<!-- SAI: mutate prop trực tiếp -->
props.trip.status = 'completed'
```

### 4. Composables cho logic
Logic > 20 lines nên extract vào composable `use{Name}.ts`:
```ts
// composables/useTripDetail.ts
export function useTripDetail(tripId: Ref<number>) {
  const trip = ref<Trip | null>(null)
  const loading = ref(false)
  
  async function refresh() {
    loading.value = true
    try {
      const res = await http.get(`/api/trips/${tripId.value}`)
      trip.value = res.data.data
    } finally {
      loading.value = false
    }
  }
  
  return { trip, loading, refresh }
}
```

---

## Tailwind CSS Rules

### Không dùng inline styles
```vue
<!-- SAI -->
<div style="margin-top: 16px; color: red;">

<!-- ĐÚNG -->
<div class="mt-4 text-red-600">
```

### Không dùng arbitrary values trừ khi cần thiết
```vue
<!-- Tránh -->
<div class="mt-[17px]">

<!-- Ưu tiên -->
<div class="mt-4">  <!-- 16px -->
```

### Consistent spacing scale
- Container padding: `p-4` hoặc `p-6`
- Card internal: `p-4`
- Form fields gap: `space-y-4`
- Section gap: `space-y-6` hoặc `mt-6`

### Responsive
```vue
<!-- Mobile-first: sm: md: lg: xl: 2xl: -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
```

---

## Shared Components (app/components/common/)

Dùng existing shared components — không tạo mới khi đã có:

| Component | Dùng khi |
|-----------|---------|
| `<Button>` | Mọi button |
| `<Input>` | Text input |
| `<Select>` | Dropdown |
| `<Modal>` | Modal dialog |
| `<Card>` | Card container |
| `<Toggle>` | On/off switch |
| `<StatusBadge>` | Status pill |
| `<FileUpload>` | File upload |
| `<ConfirmModal>` | Confirm action |
| `<AppMessageModal>` | Alert/info dialog |
| `<BaseDateTime>` | Date/time display |

---

## State Management (Pinia)

### Khi nào dùng Pinia store
- State chia sẻ giữa nhiều views/components
- Global state: auth user, UI state, notification count

### Khi nào dùng local ref/reactive
- State chỉ trong 1 component hoặc composable
- Form state
- Loading/error state

### Store structure
```ts
// stores/dispatch.ts
export const useDispatchStore = defineStore('dispatch', () => {
  // state
  const currentRequest = ref<DispatchRequest | null>(null)
  
  // getters
  const isPending = computed(() => currentRequest.value?.status === 'pending')
  
  // actions
  async function loadRequest(id: number) { ... }
  
  return { currentRequest, isPending, loadRequest }
})
```

---

## i18n Rules

### Luôn dùng i18n cho text hiển thị người dùng
```vue
<!-- vi.json -->
{
  "trip": {
    "status": {
      "pending": "Chờ xử lý",
      "in_progress": "Đang thực hiện",
      "completed": "Hoàn thành"
    }
  }
}

<!-- Template -->
{{ $t(`trip.status.${trip.status}`) }}
```

### Không hardcode tiếng Việt trong component (chỉ trong locales/)

---

## Loading & Error States

### Luôn xử lý loading và error
```vue
<template>
  <div v-if="loading" class="text-center py-8">
    <Spinner />
  </div>
  <div v-else-if="error" class="text-red-600 text-center py-4">
    {{ error }}
  </div>
  <div v-else>
    <!-- content -->
  </div>
</template>
```

### Empty states
```vue
<div v-if="items.length === 0" class="text-center py-12 text-gray-500">
  <p>Không có dữ liệu.</p>
</div>
```

---

## Performance

### Lazy load views (Vue Router)
```js
// router.js
const TripDetailView = () => import('./views/TripDetailView.vue')
```

### v-memo cho heavy lists
```vue
<div v-for="trip in trips" :key="trip.id" v-memo="[trip.status, trip.updated_at]">
```

### debounce cho search inputs
```ts
import { useDebounce } from '../composables/useDebounce'
const debouncedSearch = useDebounce(searchQuery, 300)
```
