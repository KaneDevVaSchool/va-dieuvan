<template>
  <div class="text-sm text-slate-500 dark:text-slate-400">
    {{ t('p2p_policy_page.loading') }}
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const router = useRouter()

onMounted(() => {
  router.replace({ name: 'p2pPolicyTrips' })
})
</script>
