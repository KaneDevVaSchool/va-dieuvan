<template>
  <!-- Nhận diện tài liệu PDF (đỏ, góc gập) -->
  <svg
    :class="cls"
    viewBox="0 0 40 48"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >
    <path
      d="M4 2.5A2.5 2.5 0 0 1 6.5 0H26l10 10v35.5A2.5 2.5 0 0 1 33.5 48H6.5A2.5 2.5 0 0 1 4 45.5V2.5Z"
      fill="#E5252A"
    />
    <path d="M26 0v10h10L26 0Z" fill="#B81C1C" />
    <path
      d="M10 32h20v3H10v-3Zm0-8h20v3H10v-3Zm0-8h12v3H10v-3Z"
      fill="#fff"
      opacity=".92"
    />
    <path
      d="M8 12h24v1H8v-1Zm0 3h16v1H8v-1Z"
      fill="#fff"
      opacity=".55"
    />
  </svg>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  sizeClass: {
    type: String,
    default: 'h-9 w-7',
  },
})

const cls = computed(() => props.sizeClass)
</script>
