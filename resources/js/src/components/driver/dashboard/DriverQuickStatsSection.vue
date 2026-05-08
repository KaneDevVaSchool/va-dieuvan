<template>
  <TripStatsCard :stats="stats" :loading="loading" />
</template>

<script setup>
import TripStatsCard from '../../trips/TripStatsCard.vue'

defineProps({
  stats: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})
</script>
