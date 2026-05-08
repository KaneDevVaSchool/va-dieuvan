<template>
  <div class="min-h-full w-full overflow-x-hidden bg-[#0a0f0d] pb-[calc(9rem+env(safe-area-inset-bottom))] text-white">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="sticky top-0 z-20 flex items-center gap-3 bg-[#0a0f0d]/95 px-4 py-3 pt-[calc(0.75rem+env(safe-area-inset-top))] shadow-[0_1px_0_rgba(127,220,200,0.08)] backdrop-blur-sm">
      <RouterLink
        :to="{ name: 'driverHome' }"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/8 text-white ring-1 ring-white/10 active:scale-95"
        :aria-label="t('driver_maintenance.back')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L4.862 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </RouterLink>

      <h1 class="flex-1 text-center text-[20px] font-bold leading-tight text-white">
        {{ t('driver_maintenance.title') }}
      </h1>

      <!-- Bell -->
      <NotificationBell />
    </div>

    <div class="mx-auto w-full max-w-lg px-4 pt-4">

      <!-- Vehicle pill -->
      <div v-if="vehicle" class="inline-flex items-center gap-2 rounded-full bg-[#111d16] px-3.5 py-1.5 ring-1 ring-white/10">
        <span class="h-2.5 w-2.5 rounded-full bg-[#7fdcc8]" aria-hidden="true" />
        <span class="text-base font-semibold text-white/90">
          {{ vehiclePill }}
        </span>
      </div>

      <!-- ── Urgent Alert Banner ────────────────────────────────── -->
      <div class="mt-4">
        <MaintenanceAlertBanner :items="items" @cta="onAlertCta" />
      </div>

      <!-- ── Skeleton loading ──────────────────────────────────── -->
      <template v-if="loading">
        <div class="mt-4 space-y-2.5">
          <div v-for="i in 5" :key="i" class="h-[72px] animate-pulse rounded-2xl bg-[#111d16]" />
        </div>
      </template>

      <!-- ── Error state ───────────────────────────────────────── -->
      <div
        v-else-if="error"
        class="mt-6 rounded-2xl border border-[#ff6b6b]/30 bg-[#2a1010] px-4 py-4"
      >
        <p class="text-sm font-semibold text-[#ff9999]">{{ error }}</p>
        <button
          type="button"
          class="mt-3 rounded-full bg-[#ff6b6b]/20 px-4 py-1.5 text-sm font-semibold text-[#ff9999] ring-1 ring-[#ff6b6b]/40"
          @click="fetchList"
        >Thử lại</button>
      </div>

      <!-- ── Empty state ───────────────────────────────────────── -->
      <div
        v-else-if="!vehicle"
        class="mt-12 flex flex-col items-center gap-4 text-center"
      >
        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-[#111d16] ring-1 ring-white/10">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-10 w-10 text-white/20" aria-hidden="true">
            <path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 1 1 6 0h3a.75.75 0 0 0 .75-.75V15Z"/>
            <path d="M8.25 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM15.75 6.75a.75.75 0 0 0-.75.75v11.25c0 .087.015.17.042.248a3 3 0 0 1 5.958.464c.853-.175 1.522-.935 1.464-1.883a18.659 18.659 0 0 0-3.732-10.104 1.837 1.837 0 0 0-1.47-.725H15.75Z"/>
            <path d="M19.5 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z"/>
          </svg>
        </div>
        <p class="text-base font-semibold text-white/40">{{ t('driver_maintenance.no_vehicle') }}</p>
      </div>

      <template v-else>
        <!-- ── Filter Chips ────────────────────────────────────── -->
        <div class="mt-4">
          <MaintenanceFilterChips
            v-model="activeFilter"
            :counts="counts"
            :total="items.length"
          />
        </div>

        <!-- ── Section: Giấy tờ pháp lý ─────────────────────── -->
        <template v-if="filteredLegalItems.length">
          <div class="mt-6">
            <h2 class="mb-3 text-base font-semibold text-white/80">
              {{ t('driver_maintenance.section_legal') }}
            </h2>
            <div class="space-y-2.5">
              <MaintenanceItemCard
                v-for="item in filteredLegalItems"
                :key="item.id"
                :item="item"
              />
            </div>
          </div>
        </template>

        <!-- ── Section: Bảo dưỡng định kỳ ───────────────────── -->
        <template v-if="filteredMaintenanceItems.length">
          <div class="mt-6">
            <h2 class="mb-3 text-base font-semibold text-white/80">
              {{ t('driver_maintenance.section_maintenance') }}
            </h2>
            <div class="space-y-2.5">
              <MaintenanceItemCard
                v-for="item in filteredMaintenanceItems"
                :key="item.id"
                :item="item"
              />
            </div>
          </div>
        </template>

        <!-- ── No results for active filter ─────────────────── -->
        <div
          v-if="activeFilter !== 'all' && !filteredLegalItems.length && !filteredMaintenanceItems.length"
          class="mt-12 flex flex-col items-center gap-3 text-center"
        >
          <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#7fdcc8]/10">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8 text-[#7fdcc8]/50" aria-hidden="true">
              <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
            </svg>
          </div>
          <p class="text-base font-semibold text-white/40">{{ t('driver_maintenance.empty_title') }}</p>
          <p class="text-sm text-white/30">{{ t('driver_maintenance.empty_body') }}</p>
        </div>

        <!-- ── Section: Nhắc nhở tùy chỉnh ─────────────────── -->
        <div class="mt-6">
          <h2 class="mb-3 text-base font-semibold text-white/80">
            {{ t('driver_maintenance.section_reminders') }}
          </h2>

          <!-- Existing reminder chips -->
          <div v-if="reminders.length" class="mb-3 flex flex-wrap gap-2">
            <div
              v-for="r in reminders"
              :key="r.id"
              class="flex items-center gap-1.5 rounded-full bg-[#111d16] px-3 py-1.5 text-sm font-medium text-white/70 ring-1 ring-white/10"
            >
              <span>{{ r.title }}</span>
              <button
                type="button"
                class="ml-1 flex h-4 w-4 items-center justify-center rounded-full text-white/40 hover:text-white/80"
                :aria-label="t('driver_maintenance.reminder_delete')"
                @click="onDeleteReminder(r.id)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3">
                  <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Add reminder button -->
          <button
            type="button"
            class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-[#7fdcc8]/30 text-[#7fdcc8]/60 transition hover:border-[#7fdcc8]/60 hover:text-[#7fdcc8]"
            @click="showReminderModal = true"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
              <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
            </svg>
            <span class="text-sm font-semibold">{{ t('driver_maintenance.add_reminder') }}</span>
          </button>
        </div>
      </template>
    </div>

    <!-- ── Sticky Bottom CTA ─────────────────────────────────────── -->
    <div
      v-if="vehicle && !loading"
      class="fixed bottom-[calc(var(--driver-bottom-nav-height,3.5rem)+env(safe-area-inset-bottom))] left-0 right-0 z-30 px-4 pb-3 pt-4 backdrop-blur-sm"
      style="background: linear-gradient(to top, #0a0f0d 70%, transparent);"
    >
      <RouterLink
        :to="{ name: 'driverAccount' }"
        class="flex min-h-[52px] w-full items-center justify-center rounded-[14px] bg-[#1dbc5e] text-[18px] font-bold text-white shadow-lg shadow-[#1dbc5e]/20 transition active:scale-[0.98]"
      >
        {{ t('driver_maintenance.cta_update') }}
      </RouterLink>
    </div>

    <!-- ── Add Reminder Modal ──────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showReminderModal"
          class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center"
          @click.self="showReminderModal = false"
        >
          <div class="w-full max-w-lg rounded-t-3xl bg-[#111d16] px-5 pb-[calc(2rem+env(safe-area-inset-bottom))] pt-5 ring-1 ring-white/10 sm:rounded-3xl sm:pb-6">
            <h3 class="mb-4 text-lg font-bold text-white">{{ t('driver_maintenance.add_reminder') }}</h3>

            <div class="space-y-4">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-white/70">{{ t('driver_maintenance.reminder_title_label') }}</label>
                <input
                  v-model="newReminder.title"
                  type="text"
                  class="w-full rounded-xl bg-white/8 px-4 py-3 text-base text-white placeholder:text-white/30 ring-1 ring-white/15 focus:outline-none focus:ring-[#7fdcc8]/60"
                  :placeholder="t('driver_maintenance.reminder_title_label')"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-white/70">{{ t('driver_maintenance.reminder_repeat_label') }}</label>
                <div class="flex gap-2">
                  <button
                    v-for="rt in ['once', 'weekly', 'monthly']"
                    :key="rt"
                    type="button"
                    :class="[
                      'flex-1 rounded-xl py-2.5 text-sm font-semibold transition',
                      newReminder.repeat_type === rt
                        ? 'bg-[#7fdcc8] text-[#0a0f0d]'
                        : 'bg-white/8 text-white/60 ring-1 ring-white/15',
                    ]"
                    @click="newReminder.repeat_type = rt"
                  >
                    {{ t(`driver_maintenance.reminder_repeat_${rt}`) }}
                  </button>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-semibold text-white/70">{{ t('driver_maintenance.reminder_date_label') }}</label>
                <input
                  v-model="newReminder.remind_at"
                  type="datetime-local"
                  class="w-full rounded-xl bg-white/8 px-4 py-3 text-base text-white ring-1 ring-white/15 focus:outline-none focus:ring-[#7fdcc8]/60 [color-scheme:dark]"
                />
              </div>
            </div>

            <div class="mt-5 flex gap-3">
              <button
                type="button"
                class="flex-1 rounded-xl py-3 text-sm font-semibold text-white/50 ring-1 ring-white/15"
                @click="showReminderModal = false"
              >
                {{ t('driver_maintenance.reminder_cancel') }}
              </button>
              <button
                type="button"
                :disabled="!newReminder.title || !newReminder.remind_at || savingReminder"
                class="flex-1 rounded-xl bg-[#1dbc5e] py-3 text-sm font-bold text-white disabled:opacity-50"
                @click="onSaveReminder"
              >
                {{ savingReminder ? t('common.processing') : t('driver_maintenance.reminder_save') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import MaintenanceAlertBanner from '../../components/maintenance/MaintenanceAlertBanner.vue'
import MaintenanceFilterChips from '../../components/maintenance/MaintenanceFilterChips.vue'
import MaintenanceItemCard from '../../components/maintenance/MaintenanceItemCard.vue'
import NotificationBell from '../../components/notifications/NotificationBell.vue'
import { useMaintenance } from '../../composables/useMaintenance'

const { t } = useI18n()

const {
  items, reminders, vehicle, counts, loading, error,
  legalItems, maintenanceItems,
  fetchList, createReminder, deleteReminder,
} = useMaintenance()

const activeFilter = ref('all')
const showReminderModal = ref(false)
const savingReminder = ref(false)
const newReminder = reactive({ title: '', repeat_type: 'once', remind_at: '' })

const vehiclePill = computed(() => {
  const v = vehicle.value
  if (!v) return ''
  const parts = [v.type, v.seat_count ? `${v.seat_count} chỗ` : '', v.license_plate].filter(Boolean)
  return parts.join(' · ')
})

function applyFilter(list) {
  if (activeFilter.value === 'all') return list
  return list.filter((i) => {
    if (activeFilter.value === 'urgent') return i.status === 'urgent'
    if (activeFilter.value === 'warning') return i.status === 'warning'
    if (activeFilter.value === 'safe') return i.status === 'safe'
    return true
  })
}

const filteredLegalItems = computed(() => applyFilter(legalItems.value))
const filteredMaintenanceItems = computed(() => applyFilter(maintenanceItems.value))

async function onSaveReminder() {
  if (!newReminder.title || !newReminder.remind_at) return
  savingReminder.value = true
  try {
    await createReminder({ ...newReminder })
    showReminderModal.value = false
    newReminder.title = ''
    newReminder.remind_at = ''
    newReminder.repeat_type = 'once'
  } finally {
    savingReminder.value = false
  }
}

async function onDeleteReminder(id) {
  await deleteReminder(id)
}

function onAlertCta() {
  // Navigate to account/contact page or open tel link
}

onMounted(() => fetchList())
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
