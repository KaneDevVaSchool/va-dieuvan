<template>
  <PendingConfirmationBanner :trips="trips" :loading="loading" />
</template>

<script setup>
import PendingConfirmationBanner from '../PendingConfirmationBanner.vue'

defineProps({
  trips: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})
</script>
