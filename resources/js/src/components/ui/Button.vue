<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition',
      variant === 'primary'
        ? 'bg-va-800 text-white hover:bg-va-900 disabled:bg-slate-300'
        : variant === 'danger'
          ? 'bg-rose-600 text-white hover:bg-rose-500 disabled:bg-rose-300'
          : 'border bg-white text-slate-800 hover:bg-slate-50 disabled:text-slate-400',
    ]"
  >
    <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/60 border-t-white"></span>
    <slot />
  </button>
</template>

<script setup>
defineProps({
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  variant: { type: String, default: 'primary' }, // primary | secondary | danger
})
</script>

