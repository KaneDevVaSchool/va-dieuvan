<template>
  <div class="space-y-3">
    <p class="text-base font-semibold text-white">{{ t('driver_maintenance.images_title') }}</p>

    <!-- Image grid -->
    <div class="grid grid-cols-2 gap-2.5">
      <!-- Existing images -->
      <button
        v-for="img in images"
        :key="img.id"
        type="button"
        class="group relative aspect-[4/3] overflow-hidden rounded-xl ring-1 ring-white/12 active:scale-95 transition-transform"
        @click="openFullscreen(img)"
      >
        <img
          :src="img.url"
          :alt="img.original_name"
          class="h-full w-full object-cover"
          loading="lazy"
        />
        <div class="absolute inset-0 flex items-center justify-center bg-black/0 transition group-hover:bg-black/30">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
            class="h-6 w-6 text-white opacity-0 transition group-hover:opacity-100" aria-hidden="true">
            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
            <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd"/>
          </svg>
        </div>
      </button>

      <!-- Upload tile -->
      <label
        class="flex aspect-[4/3] cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-[#7fdcc8]/30 bg-[#111d16] text-[#7fdcc8]/70 transition hover:border-[#7fdcc8]/60 hover:text-[#7fdcc8] active:scale-95"
      >
        <span v-if="uploading" class="text-center text-xs text-white/50">{{ t('driver_maintenance.image_uploading') }}</span>
        <template v-else>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8" aria-hidden="true">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 9a.75.75 0 0 0-1.5 0v2.25H9a.75.75 0 0 0 0 1.5h2.25V15a.75.75 0 0 0 1.5 0v-2.25H15a.75.75 0 0 0 0-1.5h-2.25V9Z" clip-rule="evenodd"/>
          </svg>
          <span class="text-xs font-semibold">{{ t('driver_maintenance.image_add') }}</span>
        </template>
        <input
          ref="fileInput"
          type="file"
          accept="image/*,application/pdf"
          class="sr-only"
          :disabled="uploading"
          @change="onFileSelected"
        />
      </label>
    </div>

    <p v-if="uploadError" class="rounded-lg bg-[#2a1010] px-3 py-2 text-sm text-[#ff9999]">
      {{ uploadError }}
    </p>
  </div>

  <!-- Fullscreen overlay -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="fullscreenImg"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 p-4"
        @click.self="fullscreenImg = null"
      >
        <img
          :src="fullscreenImg.url"
          :alt="fullscreenImg.original_name"
          class="max-h-full max-w-full rounded-xl object-contain"
        />
        <button
          type="button"
          class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white"
          @click="fullscreenImg = null"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
          </svg>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  images: { type: Array, default: () => [] },
  uploading: { type: Boolean, default: false },
  uploadError: { type: String, default: null },
})

const emit = defineEmits(['upload'])

const fullscreenImg = ref(null)

function openFullscreen(img) {
  fullscreenImg.value = img
}

function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  emit('upload', file)
  e.target.value = ''
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
