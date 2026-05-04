<template>
  <Transition name="splash" @after-leave="$emit('done')">
    <div v-if="visible" class="splash-screen">
      <img :src="logoUrl" class="splash-logo" alt="VAS" />
      <div class="splash-name">Vietnam America Schools</div>
      <div class="splash-dots">
        <span
          v-for="i in 3"
          :key="i"
          :style="{ animationDelay: `${i * 0.15}s` }"
        />
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'

/** Public path — bind qua :src để tránh Vite/Rollup resolve như import. */
const logoUrl = '/icons/pwa-192.png'

const props = defineProps({
  /** Bật khi phiên đã restore xong (fetch /user hoặc không còn token). */
  appReady: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['done'])

const visible = ref(true)
const startedAt = ref(typeof performance !== 'undefined' ? performance.now() : Date.now())

function scheduleHide() {
  if (!props.appReady) return
  const elapsed =
    (typeof performance !== 'undefined' ? performance.now() : Date.now()) - startedAt.value
  const minMs = 1200
  const wait = Math.max(0, minMs - elapsed)
  window.setTimeout(() => {
    visible.value = false
  }, wait)
}

watch(
  () => props.appReady,
  (v) => {
    if (v) scheduleHide()
  },
  { immediate: true },
)
</script>

<style scoped>
.splash-screen {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #8b1a1a;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}
.splash-logo {
  width: 96px;
  height: 96px;
  border-radius: 20px;
}
.splash-name {
  color: #fff;
  font-size: 15px;
  font-weight: 500;
  letter-spacing: 0.3px;
}
.splash-dots {
  display: flex;
  gap: 6px;
}
.splash-dots span {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.6);
  animation: dot-bounce 0.9s ease-in-out infinite alternate;
}
@keyframes dot-bounce {
  from {
    opacity: 0.3;
    transform: translateY(0);
  }
  to {
    opacity: 1;
    transform: translateY(-6px);
  }
}
.splash-enter-active,
.splash-leave-active {
  transition: opacity 0.4s ease;
}
.splash-enter-from,
.splash-leave-to {
  opacity: 0;
}
</style>
