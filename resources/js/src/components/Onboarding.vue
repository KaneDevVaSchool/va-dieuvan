<template>
  <div
    class="fixed inset-0 z-[9998] flex flex-col bg-[#8B1A1A] text-white"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
  >
    <div class="flex items-center justify-between px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-2">
      <div class="flex gap-2">
        <span
          v-for="s in steps.length"
          :key="s"
          class="h-2 w-2 rounded-full transition-colors"
          :class="s - 1 === step ? 'bg-white' : 'bg-white/35'"
        />
      </div>
      <button
        type="button"
        class="text-sm font-medium text-white/90 hover:text-white px-2 py-1"
        @click="skip"
      >
        Bỏ qua
      </button>
    </div>

    <div class="flex-1 overflow-hidden relative touch-pan-y">
      <Transition :name="slideDir">
        <div
          :key="step"
          class="absolute inset-0 flex flex-col items-center justify-center px-8 text-center gap-4"
        >
          <h1 class="text-xl font-semibold leading-snug max-w-sm">
            {{ steps[step].title }}
          </h1>
          <p class="text-sm text-white/85 max-w-sm leading-relaxed">
            {{ steps[step].body }}
          </p>
        </div>
      </Transition>
    </div>

    <div class="p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
      <button
        type="button"
        class="w-full rounded-xl bg-white py-3 text-sm font-semibold text-[#8B1A1A] hover:bg-white/95"
        @click="next"
      >
        {{ step === steps.length - 1 ? 'Bắt đầu' : 'Tiếp' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const emit = defineEmits(['done'])

const steps = [
  {
    title: 'Hệ thống điều vận VAS',
    body: 'Theo dõi yêu cầu, chuyến xe và vận hành nội bộ ngay trên điện thoại hoặc máy tính.',
  },
  {
    title: 'Tạo yêu cầu nhanh',
    body: 'Dùng biểu mẫu 4 bước để gửi yêu cầu điều xe — thông tin rõ ràng, ít sai sót hơn.',
  },
  {
    title: 'Theo dõi trạng thái',
    body: 'Xem nhanh pending, đã duyệt hoặc hoàn tất để chủ động phối hợp với điều vận.',
  },
  {
    title: 'Cài đặt ứng dụng',
    body: 'Thêm VA Dispatch vào màn hình chính (Chrome → menu ⋮ → Install app) để mở nhanh như app.',
  },
]

const step = ref(0)
const slideDir = ref('slide-left')

const lastIndex = computed(() => steps.length - 1)

let touchX0 = 0

function onTouchStart(e) {
  touchX0 = e.changedTouches[0]?.clientX ?? 0
}

function onTouchEnd(e) {
  const x1 = e.changedTouches[0]?.clientX ?? touchX0
  const dx = x1 - touchX0
  if (Math.abs(dx) < 48) return
  if (dx < 0 && step.value < lastIndex.value) {
    slideDir.value = 'slide-left'
    step.value += 1
  } else if (dx > 0 && step.value > 0) {
    slideDir.value = 'slide-right'
    step.value -= 1
  }
}

function next() {
  if (step.value < lastIndex.value) {
    slideDir.value = 'slide-left'
    step.value += 1
    return
  }
  emit('done')
}

function skip() {
  emit('done')
}
</script>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition:
    transform 0.28s ease,
    opacity 0.28s ease;
}
.slide-left-enter-from {
  opacity: 0;
  transform: translateX(28px);
}
.slide-left-leave-to {
  opacity: 0;
  transform: translateX(-28px);
}
.slide-right-enter-from {
  opacity: 0;
  transform: translateX(-28px);
}
.slide-right-leave-to {
  opacity: 0;
  transform: translateX(28px);
}
</style>
