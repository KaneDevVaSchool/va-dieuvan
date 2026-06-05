<template>
  <!-- Teleport ra <body>: tránh ancestor (vùng <main> cuộn) làm fixed bám sai, để backdrop phủ trọn viewport. -->
  <Teleport to="body">
    <!-- py-* trên lớp flex: khoảng cách với mép viewport; không scroll dọc trong modal. -->
    <div v-if="open" class="fixed inset-0 z-50 overflow-hidden bg-black/45 backdrop-blur-[1px]">
      <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 sm:py-12">
      <div
        class="w-full rounded-xl border border-slate-200/80 bg-white shadow-xl ring-1 ring-slate-900/5"
        :class="panelClass"
      >
        <div class="shrink-0 border-b border-slate-100 bg-gradient-to-b from-slate-50 to-white px-4 py-3.5 md:px-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-base font-semibold tracking-tight text-slate-900">{{ title }}</div>
              <p v-if="description" class="mt-1 text-xs leading-relaxed text-slate-500">{{ description }}</p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg px-2 py-1 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
              @click="$emit('close')"
            >
              Đóng
            </button>
          </div>
        </div>
        <div class="min-h-0 flex-1 overflow-visible px-4 py-4 md:px-5 md:py-5">
          <slot />
        </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  /** Dòng phụ dưới tiêu đề (gợi ý bắt buộc / chú thích). */
  description: { type: String, default: '' },
  /** Form phức tạp (ví dụ bảng giá nhiều cột). */
  wide: { type: Boolean, default: false },
})

const panelClass = computed(() =>
  props.wide ? 'max-w-3xl flex flex-col' : 'max-w-lg flex flex-col',
)

defineEmits(['close'])
</script>
