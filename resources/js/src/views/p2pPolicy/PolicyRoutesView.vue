<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.routes_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('p2p_policy_page.routes_subtitle') }}</p>
      </div>
      <div class="flex shrink-0 flex-wrap gap-2">
        <router-link
          v-if="canManageRoutes"
          :to="{ name: 'routes' }"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
        >
          {{ t('p2p_policy_page.routes_open_d2d') }}
        </router-link>
        <Button v-if="canManageRoutes" @click="showCreate = true">
          <PlusIcon class="h-4 w-4" /> {{ t('p2p_policy_page.routes_create') }}
        </Button>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" :disabled="loading" @click="load">
          <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" /> {{ t('p2p_policy_page.refresh') }}
        </button>
      </div>
    </div>

    <p class="rounded-lg border border-slate-200/80 bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600">
      {{ t('p2p_policy_page.routes_help') }}
    </p>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>
    <div v-else-if="!items.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
      <MapPinIcon class="mb-3 h-12 w-12 text-slate-300" />
      <p class="text-sm font-medium text-slate-600">{{ t('p2p_policy_page.routes_empty') }}</p>
      <Button v-if="canManageRoutes" class="mt-4" @click="showCreate = true">
        <PlusIcon class="h-4 w-4" /> {{ t('p2p_policy_page.routes_create') }}
      </Button>
    </div>

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="r in items" :key="r.id" class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:shadow-md">
        <div class="flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100">
            <MapPinIcon class="h-5 w-5 text-teal-600" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="truncate font-semibold text-slate-900">{{ r.name }}</div>
            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="inline-flex items-center rounded-md bg-indigo-100 px-2 py-0.5 text-indigo-700">
                {{ t('p2p_policy_page.route_policy_count', { n: r.policy_students_count }) }}
              </span>
              <span v-if="r.stops_count != null" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5">
                {{ t('p2p_policy_page.route_stops_count', { n: r.stops_count }) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal
      :open="showCreate"
      :title="t('p2p_policy_page.routes_create_title')"
      :description="t('p2p_policy_page.routes_create_hint')"
      @close="showCreate = false"
    >
      <div class="space-y-4">
        <Input v-model="newRouteName" :label="t('p2p_policy_page.routes_create_name')" required />
        <div class="flex justify-end gap-2">
          <Button variant="secondary" :disabled="creating" @click="showCreate = false">{{ t('common.cancel') }}</Button>
          <Button :loading="creating" :disabled="!newRouteName.trim()" @click="submitCreate">{{ t('p2p_policy_page.routes_create') }}</Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, MapPinIcon, PlusIcon } from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Modal from '../../components/ui/Modal.vue'
import { listPolicyRoutes } from '../../api/p2p'
import { createRoute } from '../../api/d2d'
import { useAuthStore } from '../../store'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const { t } = useI18n()
const auth = useAuthStore()
const canManageRoutes = computed(() => auth.hasPermission('route.manage'))

const loading = ref(false)
const items = ref([])
const showCreate = ref(false)
const newRouteName = ref('')
const creating = ref(false)

async function load() {
  loading.value = true
  try {
    items.value = (await listPolicyRoutes())?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function submitCreate() {
  const name = newRouteName.value.trim()
  if (!name) return
  creating.value = true
  try {
    await createRoute({ name })
    showAppSuccess(t('p2p_policy_page.routes_create_ok'))
    showCreate.value = false
    newRouteName.value = ''
    await load()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    creating.value = false
  }
}

onMounted(load)
</script>
