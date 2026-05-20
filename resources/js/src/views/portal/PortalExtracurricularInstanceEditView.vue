<template>
  <PortalExtracurricularBm03EditForm :req="req" @saved="$emit('saved')" />
</template>

<script setup>
import PortalExtracurricularBm03EditForm from '../../components/portal/extracurricular/PortalExtracurricularBm03EditForm.vue'

defineProps({
  req: { type: Object, required: true },
})

defineEmits(['saved'])
</script>
