<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <div class="mx-auto max-w-6xl px-4 py-6">
      <header class="flex items-center justify-between gap-4">
        <div>
          <div class="text-lg font-semibold">Phần mềm điều vận</div>
          <div class="text-sm text-gray-500">Vehicle Dispatching System</div>
        </div>
        <nav class="flex items-center gap-3 text-sm">
          <RouterLink class="hover:underline" to="/">Dashboard</RouterLink>
          <RouterLink class="hover:underline" to="/dispatcher">Dispatcher</RouterLink>
          <RouterLink class="hover:underline" to="/driver">Tài xế</RouterLink>
          <RouterLink class="hover:underline" to="/accountant">Kế toán</RouterLink>
          <RouterLink class="hover:underline" to="/admin">Admin</RouterLink>
        </nav>
      </header>

      <main class="mt-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

