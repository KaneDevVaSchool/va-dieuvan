<template>
  <div class="p2p-routes-page w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="{ name: 'p2pPolicyHub' }"
          class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-base font-medium text-teal-800 transition hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
        >
          <ArrowLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('p2p_policy_page.back_to_hub') }}
        </RouterLink>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
          {{ t('p2p_policy_page.routes_title') }}
        </h1>
        <p class="max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.routes_subtitle') }}
        </p>
      </div>
    </header>

    <form @submit.prevent="create">
      <Card :hint="t('p2p_policy_page.tip_section_routes_create')">
        <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_routes_create') }}</h2>
        <div class="grid gap-5 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_p2p_term')" :hint="t('p2p_policy_page.tip_p2p_term')" required />
            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-stretch">
              <select v-model="createForm.p2p_policy_term_id" required class="p2p-term-input min-w-0 flex-1">
                <option disabled value="">{{ t('p2p_policy_page.placeholder_select_term') }}</option>
                <option v-for="term in terms" :key="term.id" :value="term.id">{{ p2pTermLabel(term) }}</option>
              </select>
              <button
                type="button"
                class="shrink-0 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-base font-medium text-teal-900 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
                @click="openTermModal"
              >
                {{ t('p2p_policy_page.add_p2p_term_btn') }}
              </button>
            </div>
          </div>
          <div class="sm:col-span-2">
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_route_name')" :hint="t('p2p_policy_page.tip_route_name')" required />
            <input
              v-model="createForm.name"
              required
              class="p2p-term-input mt-2 w-full"
              :placeholder="t('p2p_policy_page.placeholder_route_name')"
            />
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_origin_campus')" :hint="t('p2p_policy_page.tip_campus')" required />
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
              <select v-model="createForm.origin_campus_id" required class="p2p-term-input min-w-0 flex-1">
                <option disabled value="">{{ t('p2p_policy_page.placeholder_campus') }}</option>
                <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <button
                type="button"
                class="shrink-0 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-base font-medium text-teal-900 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
                @click="openCampusModal"
              >
                {{ t('p2p_policy_page.add_campus_btn') }}
              </button>
            </div>
          </div>
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_dest_campus')" :hint="t('p2p_policy_page.tip_campus')" required />
            <select v-model="createForm.dest_campus_id" required class="p2p-term-input mt-2 w-full">
              <option disabled value="">{{ t('p2p_policy_page.placeholder_campus') }}</option>
              <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
        </div>
        <button type="submit" class="mt-5 rounded-xl bg-va-800 px-6 py-3 text-base font-semibold text-white hover:bg-va-900">
          {{ t('p2p_policy_page.add_route') }}
        </button>
      </Card>
    </form>

    <section class="space-y-3" aria-labelledby="p2p-routes-list-heading">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 id="p2p-routes-list-heading" class="text-lg font-bold text-slate-900 dark:text-white">
          {{ t('p2p_policy_page.section_routes_list') }}
        </h2>
        <button
          type="button"
          class="shrink-0 rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-900 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
          @click="openTermModal"
        >
          {{ t('p2p_policy_page.add_p2p_term_btn') }}
        </button>
      </div>

      <div class="relative z-40">
        <AppFilterBar>
          <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
            <details class="group relative">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 [&::-webkit-details-marker]:hidden"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
                  <span
                    v-if="activeFilterCount > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                  >
                    {{ activeFilterCount }}
                  </span>
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl dark:border-violet-800/40 dark:bg-slate-900"
              >
                <p
                  class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
                >
                  {{ t('filter_bar.active_title') }}
                </p>
                <div class="p-3 pt-2">
                  <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <li v-if="filters.p2p_policy_term_id" class="flex justify-between gap-2">
                      <span class="text-slate-500">{{ t('p2p_policy_page.filter_p2p_term') }}</span>
                      <span class="max-w-[12rem] truncate font-medium">{{ termFilterLabel }}</span>
                    </li>
                    <li v-if="filters.is_active !== ''" class="flex justify-between gap-2">
                      <span class="text-slate-500">{{ t('p2p_policy_page.filter_active') }}</span>
                      <span class="font-medium">{{ activeFilterLabel }}</span>
                    </li>
                    <li v-if="filters.per_page !== P2P_ROUTES_DEFAULT_PER_PAGE" class="flex justify-between gap-2">
                      <span class="text-slate-500">{{ t('filter_bar.per_page') }}</span>
                      <span class="font-medium">{{ filters.per_page }}</span>
                    </li>
                    <li v-if="activeFilterCount === 0" class="text-slate-400">{{ t('filter_bar.empty') }}</li>
                  </ul>
                  <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                      {{ t('trips_page.filter_show_controls_title') }}
                    </p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
                      {{ t('trips_page.filter_show_controls_hint') }}
                    </p>
                    <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                      <li v-for="fd in filterControlDefs" :key="'routes-vis-' + fd.id" class="flex items-start gap-2">
                        <input
                          :id="'p2p-routes-filter-vis-' + fd.id"
                          v-model="visibility[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600"
                        />
                        <label :for="'p2p-routes-filter-vis-' + fd.id" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">
                          {{ t(fd.labelKey) }}
                        </label>
                      </li>
                    </ul>
                  </div>
                  <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
                    @click="onClearFilters"
                  >
                    {{ t('filter_bar.clear_all') }}
                  </button>
                </div>
              </div>
            </details>

            <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
              <AppFilterDropdown
                v-if="visibility.p2p_policy_term_id"
                :label="t('p2p_policy_page.filter_p2p_term')"
                :summary-text="termFilterLabel"
                summary-text-class="max-w-[11rem]"
                panel-class="min-w-[240px] max-h-[min(50vh,280px)] overflow-y-auto py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li>
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="!filters.p2p_policy_term_id ? activeOptClass : inactiveOptClass"
                      @click="setTermFilter('')"
                    >
                      {{ t('p2p_policy_page.filter_any') }}
                    </button>
                  </li>
                  <li v-for="term in terms" :key="term.id">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="String(filters.p2p_policy_term_id) === String(term.id) ? activeOptClass : inactiveOptClass"
                      @click="setTermFilter(term.id)"
                    >
                      {{ p2pTermLabel(term) }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <AppFilterDropdown
                v-if="visibility.is_active"
                :label="t('p2p_policy_page.filter_active')"
                :summary-text="activeFilterLabel"
                panel-class="min-w-[200px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in activeOptions" :key="opt.value === '' ? '_all' : opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="filters.is_active === opt.value ? activeOptClass : inactiveOptClass"
                      @click="setActiveFilter(opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <label v-if="visibility.per_page" class="inline-flex shrink-0 items-center gap-1.5">
                <span class="sr-only">{{ t('filter_bar.per_page') }}</span>
                <select
                  v-model.number="filters.per_page"
                  class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                  :aria-label="t('filter_bar.per_page')"
                  @change="onPerPageChange"
                >
                  <option v-for="n in P2P_ROUTES_PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
                </select>
                <span class="hidden text-xs text-slate-500 sm:inline dark:text-slate-400">{{ t('p2p_policy_page.rows') }}</span>
              </label>
            </div>

            <div
              class="ml-auto flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 sm:pl-3 dark:border-violet-900/40"
            >
              <button
                type="button"
                class="inline-flex rounded-lg p-2 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:hover:bg-white/10"
                :title="t('filter_bar.clear_icon')"
                :aria-label="t('filter_bar.clear_icon')"
                @click="onClearFilters"
              >
                <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
          </div>
        </AppFilterBar>
      </div>

      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700 dark:bg-slate-900/80">
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
              <tr>
                <th class="px-3 py-2.5">{{ t('p2p_policy_page.routes_col_route') }}</th>
                <th class="hidden px-3 py-2.5 sm:table-cell">{{ t('p2p_policy_page.routes_col_students') }}</th>
                <th class="px-3 py-2.5">{{ t('p2p_policy_page.routes_col_assign') }}</th>
                <th class="w-[4.5rem] px-3 py-2.5 text-right" />
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="r in routes"
                :key="r.id"
                class="border-t border-slate-100 transition-colors hover:bg-violet-50/40 dark:border-slate-800 dark:hover:bg-violet-950/20"
              >
                <td class="px-3 py-2.5">
                  <p class="font-medium text-slate-900 dark:text-white">{{ r.name }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">
                    {{ r.origin_campus?.name }} → {{ r.dest_campus?.name }}
                    <span class="sm:hidden"> · {{ r.policy_students_count ?? 0 }} HS</span>
                  </p>
                </td>
                <td class="hidden px-3 py-2.5 tabular-nums text-slate-600 sm:table-cell">
                  {{ r.policy_students_count ?? 0 }}
                </td>
                <td class="px-3 py-2.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <select
                      v-if="assignDraft[r.id]"
                      v-model="assignDraft[r.id].vehicle_id"
                      class="h-8 min-w-[6.5rem] max-w-[9rem] rounded-md border-0 bg-slate-50 px-2 text-xs ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-600"
                      :title="t('p2p_policy_page.tip_assign_vehicle')"
                      :aria-label="t('p2p_policy_page.pick_vehicle')"
                    >
                      <option value="">{{ t('p2p_policy_page.pick_vehicle') }}</option>
                      <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }}</option>
                    </select>
                    <select
                      v-if="assignDraft[r.id]"
                      v-model="assignDraft[r.id].driver_id"
                      class="h-8 min-w-[7rem] max-w-[10rem] rounded-md border-0 bg-slate-50 px-2 text-xs ring-1 ring-slate-200/80 dark:bg-slate-800 dark:ring-slate-600"
                      :title="t('p2p_policy_page.tip_assign_driver')"
                      :aria-label="t('p2p_policy_page.pick_driver')"
                    >
                      <option value="">{{ t('p2p_policy_page.pick_driver') }}</option>
                      <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.full_name }}</option>
                    </select>
                  </div>
                </td>
                <td class="px-3 py-2.5 text-right">
                  <button
                    type="button"
                    class="rounded-lg bg-va-800 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-va-900"
                    @click="saveAssign(r.id)"
                  >
                    {{ t('p2p_policy_page.save') }}
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && !routes.length">
                <td colspan="4" class="px-3 py-10 text-center text-slate-500">{{ t('p2p_policy_page.empty') }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="loading" class="flex items-center justify-center gap-2 border-t border-slate-100 py-8 text-sm text-slate-500 dark:border-slate-800">
            <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
            {{ t('p2p_policy_page.routes_loading') }}
          </div>
        </div>

        <div
          v-if="meta.total > 0"
          class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50"
        >
          <p class="text-sm text-slate-600 dark:text-slate-400">
            {{
              t('p2p_policy_page.routes_pagination_summary', {
                from: pageFrom,
                to: pageTo,
                total: meta.total,
              })
            }}
          </p>
          <div v-if="(meta.last_page ?? 1) > 1" class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
              :disabled="loading || (meta.current_page ?? 1) <= 1"
              @click="goPage((meta.current_page ?? 1) - 1)"
            >
              {{ t('p2p_policy_page.routes_page_prev') }}
            </button>
            <button
              v-for="p in pageNumbers"
              :key="p"
              type="button"
              class="min-w-[2rem] rounded-lg px-2 py-1.5 text-sm"
              :class="
                p === meta.current_page
                  ? 'bg-teal-600 font-semibold text-white'
                  : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
              "
              :disabled="loading"
              @click="goPage(p)"
            >
              {{ p }}
            </button>
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
              :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
              @click="goPage((meta.current_page ?? 1) + 1)"
            >
              {{ t('p2p_policy_page.routes_page_next') }}
            </button>
          </div>
        </div>
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.tip_section_routes_list') }}</p>
    </section>

    <dialog ref="campusDialog" class="w-[min(100%,28rem)] rounded-2xl border p-0 shadow-xl dark:border-slate-700 dark:bg-slate-900">
      <form class="p-5 space-y-4" @submit.prevent="submitCampus">
        <h3 class="text-lg font-bold">{{ t('p2p_policy_page.add_campus_modal_title') }}</h3>
        <p class="text-sm text-slate-500">{{ t('p2p_policy_page.add_campus_modal_hint') }}</p>
        <div>
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.campus_field_code')" required />
          <input v-model="campusForm.code" required class="p2p-term-input mt-2 w-full" :placeholder="t('p2p_policy_page.campus_placeholder_code')" />
        </div>
        <div>
          <P2pPolicyFieldLabel :label="t('p2p_policy_page.campus_field_name')" required />
          <input v-model="campusForm.name" required class="p2p-term-input mt-2 w-full" :placeholder="t('p2p_policy_page.campus_placeholder_name')" />
        </div>
        <p v-if="campusError" class="text-sm text-rose-600">{{ campusError }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="closeCampusModal">{{ t('p2p_policy_page.cancel') }}</button>
          <button type="submit" class="rounded-lg bg-va-800 px-4 py-2 text-sm text-white" :disabled="campusSaving">
            {{ t('p2p_policy_page.add_campus_save') }}
          </button>
        </div>
      </form>
    </dialog>

    <dialog ref="termDialog" class="w-[min(100vw-2rem,32rem)] max-w-lg rounded-2xl border p-0 shadow-2xl backdrop:bg-slate-900/40 dark:border-slate-700 dark:bg-slate-900">
      <form class="max-h-[min(90vh,640px)] overflow-y-auto p-6" @submit.prevent="submitTerm">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.add_p2p_term_modal_title') }}</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.add_p2p_term_modal_hint') }}</p>
        <div class="mt-5 space-y-4">
          <div>
            <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_academic_term')" :hint="t('p2p_policy_page.tip_academic_term')" required />
            <select v-model="termForm.academic_term_id" required class="p2p-term-input mt-2 w-full">
              <option disabled value="">{{ t('p2p_policy_page.placeholder_select_academic_term') }}</option>
              <option v-for="at in academicTerms" :key="at.id" :value="at.id">
                {{ at.academic_year }} — {{ at.name }} ({{ at.term_code }})
              </option>
            </select>
            <p v-if="!academicTerms.length" class="mt-2 text-sm text-amber-800 dark:text-amber-200">
              {{ t('p2p_policy_page.add_p2p_term_no_academic') }}
            </p>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_operating_from')" required />
              <input v-model="termForm.operating_from" type="date" required class="p2p-term-input mt-2 w-full" />
            </div>
            <div>
              <P2pPolicyFieldLabel :label="t('p2p_policy_page.field_operating_to')" required />
              <input v-model="termForm.operating_to" type="date" required class="p2p-term-input mt-2 w-full" />
            </div>
          </div>
          <label class="flex cursor-pointer items-start gap-3 text-sm">
            <input v-model="termIncludeWeekend" type="checkbox" class="mt-0.5 rounded border-slate-300" />
            <span>{{ t('p2p_policy_page.include_weekend') }}</span>
          </label>
        </div>
        <p v-if="termError" class="mt-3 text-sm text-rose-600">{{ termError }}</p>
        <div class="mt-6 flex flex-wrap justify-end gap-2">
          <button type="button" class="rounded-lg border px-4 py-2.5 text-sm dark:border-slate-600" @click="closeTermModal">
            {{ t('p2p_policy_page.cancel') }}
          </button>
          <button
            type="submit"
            class="rounded-lg bg-va-800 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
            :disabled="termSaving || !academicTerms.length"
          >
            {{ t('p2p_policy_page.create_term') }}
          </button>
        </div>
      </form>
    </dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ArrowLeftIcon, ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { http } from '../../api/http'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import P2pPolicyFieldLabel from '../../components/p2pPolicy/P2pPolicyFieldLabel.vue'
import Card from '../../components/ui/Card.vue'
import {
  P2P_ROUTES_DEFAULT_PER_PAGE,
  P2P_ROUTES_PER_PAGE_OPTIONS,
  useP2pPolicyRoutesFilters,
} from '../../composables/useP2pPolicyRoutesFilters'
import {
  assignPolicyRoute,
  createCampus,
  createP2pPolicyTerm,
  createPolicyRoute,
  listAcademicTerms,
  listCampuses,
  listP2pPolicyTerms,
  listPolicyRoutes,
} from '../../api/p2pPolicy'

const { t } = useI18n()
const { filters, visibility, filterControlDefs, activeFilterCount, apiParams, clearFilters, resetPage } =
  useP2pPolicyRoutesFilters()

const routes = ref([])
const meta = ref({ total: 0, current_page: 1, per_page: P2P_ROUTES_DEFAULT_PER_PAGE, last_page: 1 })
const loading = ref(false)
const terms = ref([])
const campuses = ref([])
const vehicles = ref([])
const drivers = ref([])
const assignDraft = reactive({})

const activeOptClass = 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
const inactiveOptClass = 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'

const activeOptions = computed(() => [
  { value: '', label: t('p2p_policy_page.filter_any') },
  { value: 'true', label: t('p2p_policy_page.active_yes') },
  { value: 'false', label: t('p2p_policy_page.active_no') },
])

const termFilterLabel = computed(() => {
  if (!filters.p2p_policy_term_id) return t('p2p_policy_page.filter_any')
  const term = terms.value.find((x) => String(x.id) === String(filters.p2p_policy_term_id))
  return term ? p2pTermLabel(term) : '—'
})

const activeFilterLabel = computed(() => {
  if (filters.is_active === '') return t('p2p_policy_page.filter_any')
  if (filters.is_active === 'true' || filters.is_active === true) return t('p2p_policy_page.active_yes')
  return t('p2p_policy_page.active_no')
})

const pageFrom = computed(() => {
  if (!meta.value.total) return 0
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const span = 5
  let start = Math.max(1, cur - Math.floor(span / 2))
  let end = Math.min(last, start + span - 1)
  start = Math.max(1, end - span + 1)
  const nums = []
  for (let p = start; p <= end; p++) nums.push(p)
  return nums
})

const createForm = reactive({
  p2p_policy_term_id: '',
  name: '',
  origin_campus_id: '',
  dest_campus_id: '',
})

const campusDialog = ref(null)
const campusSaving = ref(false)
const campusError = ref('')
const campusForm = reactive({ code: '', name: '' })

const termDialog = ref(null)
const termSaving = ref(false)
const termError = ref('')
const termIncludeWeekend = ref(false)
const academicTerms = ref([])
const termForm = reactive({
  academic_term_id: '',
  operating_from: '',
  operating_to: '',
  default_morning_start: '06:00',
  default_morning_end: '07:00',
  default_afternoon_start: '15:30',
  default_afternoon_end: '16:30',
  weekdays_mask: 31,
  exclude_fixed_holidays: true,
})

function p2pTermLabel(term) {
  const year = term.academic_term?.academic_year ?? ''
  return year ? `${year} — ${term.status}` : `#${term.id} — ${term.status}`
}

async function loadCampuses() {
  const c = await listCampuses({ per_page: 100 })
  campuses.value = c.items ?? []
}

function syncAssignDraft(items) {
  for (const r of items) {
    if (!assignDraft[r.id]) {
      assignDraft[r.id] = { vehicle_id: r.vehicle_id ?? '', driver_id: r.driver_id ?? '' }
    } else {
      assignDraft[r.id].vehicle_id = r.vehicle_id ?? assignDraft[r.id].vehicle_id
      assignDraft[r.id].driver_id = r.driver_id ?? assignDraft[r.id].driver_id
    }
  }
}

async function loadRoutes() {
  loading.value = true
  try {
    const rt = await listPolicyRoutes(apiParams.value)
    routes.value = rt.items ?? []
    meta.value = rt.meta ?? { total: 0, current_page: 1, per_page: filters.per_page, last_page: 1 }
    syncAssignDraft(routes.value)
  } finally {
    loading.value = false
  }
}

function setTermFilter(id) {
  filters.p2p_policy_term_id = id === '' ? '' : id
  resetPage()
}

function setActiveFilter(value) {
  filters.is_active = value
  resetPage()
}

function onPerPageChange() {
  resetPage()
}

function onClearFilters() {
  clearFilters()
}

function goPage(p) {
  const last = meta.value.last_page ?? 1
  filters.page = Math.min(Math.max(1, p), last)
}

async function loadTerms() {
  const tr = await listP2pPolicyTerms({ per_page: 30 })
  terms.value = tr.items ?? []
}

async function load() {
  await Promise.all([loadTerms(), loadCampuses()])
  if (terms.value[0] && !createForm.p2p_policy_term_id) {
    createForm.p2p_policy_term_id = terms.value[0].id
  }
  if (terms.value[0] && !filters.p2p_policy_term_id) {
    filters.p2p_policy_term_id = terms.value[0].id
  }
  if (campuses.value[0] && !createForm.origin_campus_id) {
    createForm.origin_campus_id = campuses.value[0].id
    createForm.dest_campus_id = campuses.value[1]?.id ?? campuses.value[0].id
  }
  const [{ data: v }, { data: d }] = await Promise.all([
    http.get('/vehicles', { params: { per_page: 100 } }),
    http.get('/drivers', { params: { per_page: 100 } }),
  ])
  vehicles.value = v.data?.items ?? v.data ?? []
  drivers.value = d.data?.items ?? d.data ?? []
  await loadRoutes()
}

async function create() {
  await createPolicyRoute({ ...createForm })
  createForm.name = ''
  await loadRoutes()
}

async function saveAssign(routeId) {
  const p = assignDraft[routeId]
  await assignPolicyRoute(routeId, {
    vehicle_id: Number(p.vehicle_id),
    driver_id: Number(p.driver_id),
  })
  await loadRoutes()
}

function openCampusModal() {
  campusError.value = ''
  campusForm.code = ''
  campusForm.name = ''
  campusDialog.value?.showModal()
}

function closeCampusModal() {
  campusDialog.value?.close()
}

async function submitCampus() {
  campusSaving.value = true
  campusError.value = ''
  try {
    const created = await createCampus({ ...campusForm, is_active: true })
    await loadCampuses()
    if (!createForm.origin_campus_id) createForm.origin_campus_id = created.id
    closeCampusModal()
  } catch (e) {
    campusError.value = e?.response?.data?.message ?? t('p2p_policy_page.add_campus_error')
  } finally {
    campusSaving.value = false
  }
}

async function loadAcademicTerms() {
  const res = await listAcademicTerms({ per_page: 100 })
  academicTerms.value = res.items ?? []
}

function resetTermForm() {
  termError.value = ''
  termIncludeWeekend.value = false
  termForm.academic_term_id = academicTerms.value[0]?.id ?? ''
  termForm.operating_from = ''
  termForm.operating_to = ''
  termForm.default_morning_start = '06:00'
  termForm.default_morning_end = '07:00'
  termForm.default_afternoon_start = '15:30'
  termForm.default_afternoon_end = '16:30'
  termForm.weekdays_mask = 31
  termForm.exclude_fixed_holidays = true
}

async function openTermModal() {
  await loadAcademicTerms()
  resetTermForm()
  termDialog.value?.showModal()
}

function closeTermModal() {
  termDialog.value?.close()
}

async function submitTerm() {
  termSaving.value = true
  termError.value = ''
  termForm.weekdays_mask = termIncludeWeekend.value ? 127 : 31
  try {
    const created = await createP2pPolicyTerm({ ...termForm })
    await loadTerms()
    createForm.p2p_policy_term_id = created.id
    filters.p2p_policy_term_id = created.id
    resetPage()
    await loadRoutes()
    closeTermModal()
  } catch (e) {
    termError.value = e?.response?.data?.message ?? t('p2p_policy_page.add_p2p_term_error')
  } finally {
    termSaving.value = false
  }
}

watch(apiParams, loadRoutes, { deep: true })

onMounted(load)
</script>

<style scoped>
.p2p-term-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base text-slate-900 shadow-sm ring-1 ring-slate-900/5 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100;
}
</style>
