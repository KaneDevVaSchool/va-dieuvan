<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div
      class="w-full rounded-lg bg-white shadow"
      :class="wide ? 'max-w-3xl max-h-[90vh] overflow-y-auto' : 'max-w-lg'"
    >
      <div class="flex items-center justify-between border-b px-4 py-3">
        <div class="text-sm font-semibold">{{ title }}</div>
        <button class="text-sm text-gray-600 hover:text-gray-900" type="button" @click="$emit('close')">
          Đóng
        </button>
      </div>
      <div class="px-4 py-3">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  /** Form phức tạp (ví dụ bảng giá nhiều cột). */
  wide: { type: Boolean, default: false },
})
defineEmits(['close'])
</script>

