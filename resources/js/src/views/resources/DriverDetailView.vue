<template>
  <div
    class="mx-auto max-w-5xl space-y-6 rounded-xl border border-slate-200 bg-white p-4 text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:p-6"
  >
    <div class="flex flex-wrap items-center gap-3">
      <RouterLink
        to="/resources/list?tab=drivers"
        class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400"
      >
        ← {{ t('driver_detail.back') }}
      </RouterLink>
    </div>

    <div v-if="loading" class="py-12 text-center text-sm text-slate-500">{{ t('resources.loading') }}</div>
    <div v-else-if="loadError" class="py-12 text-center text-sm text-rose-600">{{ loadError }}</div>

    <template v-else>
      <!-- Hồ sơ -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start gap-4">
          <div class="shrink-0">
            <img
              v-if="driver.user?.avatar_url"
              :src="driver.user.avatar_url"
              :alt="driver.full_name || ''"
              class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-200/90 dark:ring-slate-600"
            />
            <div
              v-else
              class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-teal-100 to-teal-200 text-lg font-semibold text-teal-900 dark:from-teal-950/80 dark:to-teal-900/60 dark:text-teal-200"
              aria-hidden="true"
            >
              {{ profileInitials }}
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('driver_detail.profile') }}</h2>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('driver_detail.profile_hint') }}</p>
              </div>
              <button
                v-if="canManage"
                type="button"
                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="toggleEdit"
              >
                {{ profileEdit ? t('driver_detail.cancel_edit') : t('driver_detail.edit') }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="!profileEdit" class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_driver_name') }}</div>
            <div class="mt-0.5 font-medium">{{ driver.full_name }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_phone') }}</div>
            <div class="mt-0.5">{{ driver.phone || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_user_email') }}</div>
            <div class="mt-0.5 break-all">{{ driverEmail || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_employee_code') }}</div>
            <div class="mt-0.5 font-mono text-xs">{{ driver.user?.employee_code || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('driver_detail.national_id') }}</div>
            <div class="mt-0.5">{{ driver.national_id || '—' }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('resources.col_license') }}</div>
            <div class="mt-0.5">{{ licenseLine }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('driver_detail.employment') }}</div>
            <div class="mt-0.5">{{ labelEmployment(driver.employment_status) }}</div>
          </div>
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ t('driver_detail.availability') }}</div>
            <div class="mt-0.5">{{ labelAvailability(driver.availability_status) }}</div>
          </div>
        </div>

        <form v-else class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="saveProfile">
          <label class="sm:col-span-2 block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('resources.col_driver_name') }}
            <input
              v-model="profileForm.full_name"
              type="text"
              required
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_full_name')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('resources.col_phone') }}
            <input
              v-model="profileForm.phone"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_phone')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('resources.col_user_email') }}
            <input
              v-model="profileForm.email"
              type="email"
              autocomplete="email"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_email')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.national_id') }}
            <input
              v-model="profileForm.national_id"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_national_id')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.license_class') }}
            <input
              v-model="profileForm.license_class"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('driver_detail.ph_license_class')"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.license_expires') }}
            <input
              v-model="profileForm.license_expires_at"
              type="date"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            />
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.employment') }}
            <select
              v-model="profileForm.employment_status"
              class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            >
              <option value="active">{{ t('driver_detail.emp_active') }}</option>
              <option value="on_leave">{{ t('driver_detail.emp_on_leave') }}</option>
              <option value="terminated">{{ t('driver_detail.emp_terminated') }}</option>
            </select>
          </label>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ t('driver_detail.availability') }}
            <select
              v-model="profileForm.availability_status"
              class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            >
              <option value="available">{{ t('driver_detail.avail_available') }}</option>
              <option value="busy">{{ t('driver_detail.avail_busy') }}</option>
              <option value="offline">{{ t('driver_detail.avail_offline') }}</option>
            </select>
          </label>
          <div class="sm:col-span-2 flex flex-wrap gap-2 pt-2">
            <button
              type="submit"
              class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
              :disabled="savingProfile"
            >
              {{ savingProfile ? t('resources.loading') : t('driver_detail.save_profile') }}
            </button>
            <p v-if="profileError" class="text-xs text-rose-600">{{ profileError }}</p>
          </div>
        </form>
      </section>

      <!-- Giấy tờ & tuân thủ -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('driver_detail.compliance_title') }}</h2>
            <p class="mt-1 max-w-3xl text-xs text-slate-600 dark:text-slate-400">{{ t('driver_detail.compliance_intro') }}</p>
          </div>
          <button
            v-if="canManage"
            type="button"
            class="rounded-lg bg-teal-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-teal-500"
            @click="openDocModal(null)"
          >
            {{ t('driver_detail.compliance_quick_add') }}
          </button>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full min-w-[720px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_doc_type') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_title') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_expires') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('resources.col_status') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('driver_detail.col_file') }}</th>
                <th v-if="canManage" class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('resources.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="doc in documents"
                :key="doc.id"
                class="cursor-pointer transition hover:bg-slate-50 dark:hover:bg-slate-800/40"
                @click="openDocModal(doc)"
              >
                <td class="px-3 py-2 align-top">{{ docTypeLabel(doc.doc_type) }}</td>
                <td class="max-w-[200px] px-3 py-2 align-top text-xs text-slate-600 dark:text-slate-400">
                  <span class="line-clamp-2">{{ doc.title || '—' }}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-2 align-top text-xs">{{ doc.expires_at || '—' }}</td>
                <td class="px-3 py-2 align-top">
                  <span :class="expiryPillClass(doc.expiry)">{{ expiryLabel(doc.expiry) }}</span>
                </td>
                <td class="max-w-[min(100%,280px)] px-3 py-2 align-top text-xs" @click.stop>
                  <span v-if="!doc.attachments?.length" class="text-slate-400">—</span>
                  <div v-else class="flex flex-col gap-2">
                    <div
                      v-for="(a, aIdx) in doc.attachments"
                      :key="a.id ?? `att-${aIdx}`"
                      class="rounded-lg border border-slate-200/90 bg-slate-50/80 px-2 py-1.5 dark:border-slate-600/80 dark:bg-slate-800/40"
                    >
                      <template v-if="isPdfAttachment(a)">
                        <div class="flex flex-wrap items-center gap-1.5">
                          <PdfFileIcon class="shrink-0" size-class="h-8 w-6" />
                          <span
                            class="shrink-0 rounded px-1 py-0.5 text-[9px] font-bold uppercase leading-none text-red-700 ring-1 ring-red-600/25 dark:text-red-300 dark:ring-red-500/30"
                            >{{ t('resources.attachment_pdf_badge') }}</span
                          >
                          <span class="min-w-0 flex-1 truncate text-[11px] text-slate-800 dark:text-slate-200">{{
                            a.original_name || 'file'
                          }}</span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap gap-1">
                          <button
                            type="button"
                            class="inline-flex items-center gap-0.5 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            :title="t('resources.attachment_preview_pdf')"
                            :disabled="
                              (pdfPreviewLoading && pdfPreviewBusyKey === driverPdfAttachmentBusyKey(doc, a, aIdx)) ||
                              pdfAttachmentDownloadBusyKey === driverPdfAttachmentBusyKey(doc, a, aIdx)
                            "
                            @click.stop="openDriverPdfPreview(doc, a, aIdx)"
                          >
                            <EyeIcon class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ t('resources.attachment_preview_short') }}
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center gap-0.5 rounded bg-teal-600 px-1.5 py-0.5 text-[10px] font-medium text-white hover:bg-teal-500 disabled:opacity-60"
                            :title="t('resources.attachment_download_pdf_title')"
                            :disabled="
                              pdfAttachmentDownloadBusyKey === driverPdfAttachmentBusyKey(doc, a, aIdx) ||
                              (pdfPreviewLoading && pdfPreviewBusyKey === driverPdfAttachmentBusyKey(doc, a, aIdx))
                            "
                            @click.stop="downloadDriverPdfAttachment(doc, a, aIdx)"
                          >
                            <ArrowDownTrayIcon class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ t('resources.attachment_download_short') }}
                          </button>
                        </div>
                      </template>
                      <a
                        v-else
                        :href="a.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-teal-700 underline dark:text-teal-400"
                      >
                        {{ a.original_name || a.id }}
                      </a>
                    </div>
                  </div>
                </td>
                <td v-if="canManage" class="whitespace-nowrap px-3 py-2 align-top text-right text-xs" @click.stop>
                  <button type="button" class="text-teal-700 hover:underline dark:text-teal-400" @click="openDocModal(doc)">
                    {{ t('resources.action_edit') }}
                  </button>
                  <button type="button" class="ml-2 text-rose-600 hover:underline" @click="confirmDelete(doc)">
                    {{ t('driver_detail.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!documents.length" class="mt-3 text-sm text-slate-500">{{ t('resources.empty') }}</p>
      </section>

      <!-- Audit -->
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('driver_detail.audit_title') }}</h2>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('driver_detail.audit_hint') }}</p>
        <ul class="mt-3 space-y-2 text-xs">
          <li v-for="log in auditLogs" :key="log.id" class="rounded-lg border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-800/40">
            <div class="flex flex-wrap justify-between gap-2">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ log.event }}</span>
              <span class="text-slate-500">{{ formatDt(log.created_at) }}</span>
            </div>
            <div class="mt-1 text-slate-600 dark:text-slate-400">{{ log.actor?.name || log.actor_id || '—' }}</div>
          </li>
        </ul>
        <p v-if="!auditLogs.length" class="mt-2 text-sm text-slate-500">{{ t('resources.empty') }}</p>
      </section>
    </template>

    <!-- Modal giấy tờ -->
    <Teleport to="body">
      <div
        v-if="docModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        @click.self="docModalOpen = false"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900" @click.stop>
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">
              {{
                !canManage
                  ? t('driver_detail.doc_modal_view')
                  : editingDocId
                    ? t('driver_detail.doc_modal_edit')
                    : t('driver_detail.doc_modal_add')
              }}
            </h2>
          </div>
          <form class="space-y-3 p-4" @submit.prevent="submitDocForm">
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_doc_type') }}
              <select
                v-model="docForm.doc_type"
                required
                :disabled="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm disabled:cursor-not-allowed disabled:opacity-70 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              >
                <option v-for="opt in docTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.col_title') }}
              <input
                v-model="docForm.title"
                type="text"
                :readonly="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                :placeholder="t('driver_detail.ph_doc_title')"
              />
            </label>
            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.notes') }}
              <textarea
                v-model="docForm.notes"
                rows="3"
                :readonly="!canManage"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                :placeholder="t('driver_detail.ph_doc_notes')"
              />
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.issued_at') }}
                <input
                  v-model="docForm.issued_at"
                  type="date"
                  :readonly="!canManage"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                />
              </label>
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('driver_detail.expires_at') }}
                <input
                  v-model="docForm.expires_at"
                  type="date"
                  :readonly="!canManage"
                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                  :class="!canManage ? 'bg-slate-50 dark:bg-slate-800/80' : ''"
                />
              </label>
            </div>
            <label v-if="canManage" class="block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('driver_detail.upload_file') }}
              <input type="file" class="mt-1 w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-800 dark:file:bg-teal-950 dark:file:text-teal-300" @change="onDocFile" />
            </label>
            <label v-if="canManage && editingDocId" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
              <input v-model="docForm.replace_file" type="checkbox" class="rounded border-slate-300 text-teal-600" />
              {{ t('driver_detail.replace_file') }}
            </label>
            <p v-if="docFormError" class="text-xs text-rose-600">{{ docFormError }}</p>
            <div class="flex gap-2 pt-2">
              <button
                type="button"
                class="flex-1 rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="docModalOpen = false"
              >
                {{ canManage ? t('app.cancel') : t('resources.close_panel') }}
              </button>
              <button
                v-if="canManage"
                type="submit"
                class="flex-1 rounded-lg bg-teal-600 py-2 text-sm font-medium text-white hover:bg-teal-500 disabled:opacity-50"
                :disabled="docSaving"
              >
                {{ docSaving ? t('resources.loading') : t('driver_detail.save_doc') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="pdfPreviewOpen"
        class="fixed inset-0 z-[110] flex flex-col bg-black/60 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-label="t('resources.attachment_preview_pdf')"
        @click.self="closePdfPreview"
      >
        <div
          class="mx-auto flex h-full w-full max-w-5xl flex-col border-x border-slate-700/50 bg-slate-900 shadow-2xl sm:my-4 sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl"
          @click.stop
        >
          <div
            class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-700/80 bg-slate-900 px-3 py-2.5 text-white sm:px-4"
          >
            <div class="flex min-w-0 items-center gap-2">
              <PdfFileIcon class="shrink-0" size-class="h-8 w-6" />
              <div class="min-w-0">
                <div class="truncate text-sm font-medium">{{ pdfPreviewFilename || 'PDF' }}</div>
                <div class="truncate text-[11px] text-slate-400">{{ t('resources.attachment_preview_pdf_hint') }}</div>
              </div>
            </div>
            <button
              type="button"
              class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-medium text-slate-100 hover:bg-slate-800"
              @click="closePdfPreview"
            >
              {{ t('app.close') }}
            </button>
          </div>
          <div class="relative min-h-0 flex-1 bg-slate-950">
            <div
              v-if="pdfPreviewLoading"
              class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-slate-950/90 text-sm text-slate-300"
            >
              <span class="inline-block h-8 w-8 animate-spin rounded-full border-2 border-teal-400 border-t-transparent"></span>
              {{ t('resources.loading') }}
            </div>
            <iframe
              v-if="pdfPreviewBlobUrl"
              :src="pdfPreviewBlobUrl"
              class="h-full min-h-[50vh] w-full border-0 sm:min-h-0"
              title="PDF"
            />
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowDownTrayIcon, EyeIcon } from '@heroicons/vue/24/outline'
import PdfFileIcon from '../../components/icons/PdfFileIcon.vue'
import {
  createDriverComplianceDocument,
  deleteDriverComplianceDocument,
  getDriver,
  getDriverComplianceAudit,
  listDriverComplianceDocuments,
  updateDriver,
  updateDriverComplianceDocument,
} from '../../api/operational'
import { formatApiError, TOKEN_KEY } from '../../api/http'
import { showAppError, showAppErrorFromApi, showAppInfo } from '../../composables/appMessage'
import {
  downloadPdfAttachmentFromApi,
  downloadPdfAttachmentFromUrl,
  fetchPdfBlobForPreview,
  normalizeAxiosBlobError,
} from '../../util/downloadPdfAttachment'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const canManage = computed(() => auth.hasPermission('resource.driver.manage'))

const profileInitials = computed(() => {
  const n = String(driver.value?.full_name || '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/).filter(Boolean)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return n.slice(0, 2).toUpperCase()
})

const loading = ref(true)
const loadError = ref('')
const driver = ref({})
const documents = ref([])
const auditLogs = ref([])

const profileEdit = ref(false)
const savingProfile = ref(false)
const profileError = ref('')
const profileForm = ref({
  full_name: '',
  phone: '',
  email: '',
  national_id: '',
  license_class: '',
  license_expires_at: '',
  employment_status: 'active',
  availability_status: 'available',
})

const docModalOpen = ref(false)
const editingDocId = ref(null)
const docSaving = ref(false)
const docFormError = ref('')
const docFile = ref(null)
const docForm = ref({
  doc_type: 'license',
  title: '',
  notes: '',
  issued_at: '',
  expires_at: '',
  replace_file: false,
})

const pdfAttachmentDownloadBusyKey = ref(null)
const pdfPreviewOpen = ref(false)
const pdfPreviewBlobUrl = ref(null)
const pdfPreviewFilename = ref('')
const pdfPreviewLoading = ref(false)
const pdfPreviewBusyKey = ref(null)

const docTypeOptions = computed(() =>
  DOC_TYPES.map((value) => ({
    value,
    label: t(`driver_compliance_doc_type.${value}`),
  })),
)

const DOC_TYPES = [
  'id_card',
  'license',
  'medical_certificate',
  'criminal_record',
  'training_certificate',
  'labor_contract',
  'social_insurance',
  'other',
]

const licenseLine = computed(() => {
  const d = driver.value
  if (!d) return '—'
  const parts = [d.license_class, d.license_expires_at].filter(Boolean)
  return parts.length ? parts.join(' — ') : '—'
})

const driverEmail = computed(() => {
  const d = driver.value
  if (!d || typeof d !== 'object') return ''
  return d.email || d.user?.email || ''
})

function syncProfileForm() {
  const d = driver.value
  profileForm.value = {
    full_name: d.full_name || '',
    phone: d.phone || '',
    email: driverEmail.value,
    national_id: d.national_id || '',
    license_class: d.license_class || '',
    license_expires_at: d.license_expires_at || '',
    employment_status: d.employment_status || 'active',
    availability_status: d.availability_status || 'available',
  }
}

function toggleEdit() {
  if (!profileEdit.value) {
    syncProfileForm()
    profileEdit.value = true
  } else {
    profileEdit.value = false
    profileError.value = ''
  }
}

async function load() {
  loading.value = true
  loadError.value = ''
  const id = Number(route.params.id)
  if (!id) {
    loadError.value = t('resources.load_error')
    loading.value = false
    return
  }
  try {
    const [d, docs, audit] = await Promise.all([
      getDriver(id),
      listDriverComplianceDocuments(id),
      getDriverComplianceAudit(id),
    ])
    driver.value = d
    documents.value = docs.items || []
    auditLogs.value = audit.items || []
  } catch (e) {
    loadError.value = formatApiError(e, t('resources.load_error'))
    showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(
  () => route.params.id,
  () => load(),
)

async function saveProfile() {
  savingProfile.value = true
  profileError.value = ''
  try {
    const id = Number(route.params.id)
    const f = profileForm.value
    driver.value = await updateDriver(id, {
      full_name: f.full_name.trim(),
      phone: f.phone?.trim() || null,
      email: f.email?.trim() || null,
      national_id: f.national_id?.trim() || null,
      license_class: f.license_class?.trim() || null,
      license_expires_at: f.license_expires_at || null,
      employment_status: f.employment_status,
      availability_status: f.availability_status,
    })
    profileEdit.value = false
  } catch (e) {
    profileError.value = formatApiError(e, t('resources.load_error'))
  } finally {
    savingProfile.value = false
  }
}

function docTypeLabel(type) {
  const k = `driver_compliance_doc_type.${type}`
  return t(k) !== k ? t(k) : type
}

function expiryPillClass(exp) {
  if (!exp || exp.state === 'none') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  if (exp.state === 'ok') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  }
  if (exp.state === 'soon') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  }
  return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
}

function expiryLabel(exp) {
  if (!exp || exp.state === 'none') return t('driver_detail.expiry_none')
  if (exp.state === 'ok') return t('resources.compliance_ok')
  if (exp.state === 'soon') return t('resources.exp_in_days', { n: exp.days })
  return t('resources.compliance_exp')
}

function labelEmployment(s) {
  const map = {
    active: t('driver_detail.emp_active'),
    on_leave: t('driver_detail.emp_on_leave'),
    terminated: t('driver_detail.emp_terminated'),
  }
  return map[s] ?? s
}

function labelAvailability(s) {
  const map = {
    available: t('driver_detail.avail_available'),
    busy: t('driver_detail.avail_busy'),
    offline: t('driver_detail.avail_offline'),
  }
  return map[s] ?? s
}

function formatDt(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString()
  } catch {
    return iso
  }
}

function emptyDocForm() {
  docForm.value = {
    doc_type: 'license',
    title: '',
    notes: '',
    issued_at: '',
    expires_at: '',
    replace_file: false,
  }
  docFile.value = null
}

function openDocModal(doc) {
  docFormError.value = ''
  if (doc) {
    editingDocId.value = doc.id
    docForm.value = {
      doc_type: doc.doc_type,
      title: doc.title || '',
      notes: doc.notes || '',
      issued_at: doc.issued_at || '',
      expires_at: doc.expires_at || '',
      replace_file: false,
    }
    docFile.value = null
  } else {
    editingDocId.value = null
    emptyDocForm()
  }
  docModalOpen.value = true
}

function onDocFile(e) {
  const f = e.target.files?.[0]
  docFile.value = f || null
}

async function submitDocForm() {
  if (!canManage.value) return
  docSaving.value = true
  docFormError.value = ''
  const id = Number(route.params.id)
  try {
    const fd = new FormData()
    fd.append('doc_type', docForm.value.doc_type)
    if (docForm.value.title) fd.append('title', docForm.value.title)
    if (docForm.value.notes) fd.append('notes', docForm.value.notes)
    if (docForm.value.issued_at) fd.append('issued_at', docForm.value.issued_at)
    if (docForm.value.expires_at) fd.append('expires_at', docForm.value.expires_at)
    if (docFile.value) fd.append('file', docFile.value)
    if (editingDocId.value) {
      fd.append('replace_file', docForm.value.replace_file ? '1' : '0')
      await updateDriverComplianceDocument(id, editingDocId.value, fd)
    } else {
      await createDriverComplianceDocument(id, fd)
    }
    docModalOpen.value = false
    await load()
  } catch (e) {
    const st = e?.response?.status
    if (st === 422) {
      const msg = e?.response?.data?.message
      const errs = e?.response?.data?.errors
      docFormError.value =
        (typeof msg === 'string' && msg) ||
        (errs && typeof errs === 'object' ? Object.values(errs).flat().join(' ') : '') ||
        t('resources.load_error')
    } else {
      showAppErrorFromApi(e, t('resources.load_error'))
    }
  } finally {
    docSaving.value = false
  }
}

async function confirmDelete(doc) {
  if (!canManage.value) return
  if (!window.confirm(t('driver_detail.confirm_delete_doc'))) return
  const id = Number(route.params.id)
  try {
    await deleteDriverComplianceDocument(id, doc.id)
    await load()
  } catch (e) {
    showAppErrorFromApi(e, t('resources.load_error'))
  }
}

function isPdfAttachment(a) {
  const mime = String(a?.mime_type || '').toLowerCase()
  if (mime.includes('pdf')) return true
  if (mime === 'application/octet-stream' || mime === 'binary/octet-stream') {
    const name = String(a?.original_name || '').toLowerCase()
    const path = String(a?.url || '').split('?')[0].toLowerCase()
    if (name.endsWith('.pdf') || path.endsWith('.pdf')) return true
  }
  const name = String(a?.original_name || '').toLowerCase()
  if (name.endsWith('.pdf')) return true
  try {
    const path = String(a?.url || '').split('?')[0].toLowerCase()
    return path.endsWith('.pdf')
  } catch {
    return false
  }
}

function resolveAttachmentAbsoluteUrl(a) {
  const u = a?.url
  if (!u) return ''
  if (typeof window === 'undefined') return u
  const viteBackend =
    import.meta.env.DEV && import.meta.env.VITE_APP_URL
      ? String(import.meta.env.VITE_APP_URL).trim().replace(/\/$/, '')
      : ''
  try {
    const parsed = new URL(u, window.location.origin)
    if (parsed.pathname.startsWith('/storage')) {
      const base = viteBackend || window.location.origin
      return `${base}${parsed.pathname}${parsed.search}`
    }
    return parsed.href
  } catch {
    const path = u.startsWith('/') ? u : `/${u}`
    if (path.startsWith('/storage')) {
      const base = viteBackend || window.location.origin
      return `${base}${path}`
    }
    return `${window.location.origin}${path}`
  }
}

function attachmentDownloadName(a) {
  const n = String(a?.original_name || '').trim()
  if (n) return n
  try {
    const path = String(a?.url || '').split('?')[0]
    const seg = path.split('/').pop() || ''
    return seg || 'document.pdf'
  } catch {
    return 'document.pdf'
  }
}

function driverPdfAttachmentBusyKey(doc, a, aIdx) {
  return `${doc?.id ?? 'doc'}-${a?.id ?? aIdx}`
}

function attachmentPdfSoftFail(result) {
  if (result.reason === 'html') {
    showAppInfo(t('resources.attachment_download_html_hint'), t('resources.attachment_download_html_title'))
  } else if (result.reason === 'bad_json') {
    showAppError(t('resources.attachment_download_failed'))
  } else {
    showAppInfo(t('resources.attachment_download_not_pdf_hint'), t('resources.attachment_download_not_pdf_title'))
  }
}

function revokePdfPreviewBlobUrl() {
  if (pdfPreviewBlobUrl.value) {
    URL.revokeObjectURL(pdfPreviewBlobUrl.value)
    pdfPreviewBlobUrl.value = null
  }
}

function closePdfPreview() {
  revokePdfPreviewBlobUrl()
  pdfPreviewOpen.value = false
  pdfPreviewFilename.value = ''
}

async function openDriverPdfPreview(doc, a, aIdx) {
  const busyKey = driverPdfAttachmentBusyKey(doc, a, aIdx)
  if (pdfPreviewLoading.value && pdfPreviewBusyKey.value === busyKey) return

  if (a?.id == null) {
    const url = resolveAttachmentAbsoluteUrl(a)
    if (url) {
      window.open(url, '_blank', 'noopener,noreferrer')
    } else {
      showAppError(t('resources.attachment_download_failed'))
    }
    return
  }

  pdfPreviewBusyKey.value = busyKey
  pdfPreviewLoading.value = true
  pdfPreviewFilename.value = attachmentDownloadName(a)
  revokePdfPreviewBlobUrl()
  pdfPreviewOpen.value = true

  try {
    const result = await fetchPdfBlobForPreview(a.id)
    if (!result.ok) {
      attachmentPdfSoftFail(result)
      pdfPreviewOpen.value = false
      return
    }
    pdfPreviewBlobUrl.value = URL.createObjectURL(new Blob([result.blob], { type: 'application/pdf' }))
  } catch (e) {
    await normalizeAxiosBlobError(e)
    pdfPreviewOpen.value = false
    showAppErrorFromApi(e, t('resources.attachment_download_failed'))
  } finally {
    pdfPreviewLoading.value = false
    pdfPreviewBusyKey.value = null
  }
}

async function downloadDriverPdfAttachment(doc, a, aIdx) {
  const busyKey = driverPdfAttachmentBusyKey(doc, a, aIdx)
  if (pdfAttachmentDownloadBusyKey.value === busyKey) return
  const url = resolveAttachmentAbsoluteUrl(a)
  if (a?.id == null && !url) return

  pdfAttachmentDownloadBusyKey.value = busyKey
  let bearerToken = null
  try {
    if (url) {
      const u = new URL(url, window.location.origin)
      if (u.origin === window.location.origin) {
        bearerToken = localStorage.getItem(TOKEN_KEY)
      }
    }
  } catch {
    /* ignore */
  }

  try {
    let result
    if (a?.id != null) {
      result = await downloadPdfAttachmentFromApi(a.id, attachmentDownloadName(a))
    } else {
      result = await downloadPdfAttachmentFromUrl(url, {
        filename: attachmentDownloadName(a),
        bearerToken,
      })
    }
    if (result.ok) return

    if (url) {
      const w = window.open(url, '_blank', 'noopener,noreferrer')
      if (!w) attachmentPdfSoftFail(result)
    } else {
      attachmentPdfSoftFail(result)
    }
  } catch (e) {
    await normalizeAxiosBlobError(e)
    if (url) {
      const w = window.open(url, '_blank', 'noopener,noreferrer')
      if (!w) showAppErrorFromApi(e, t('resources.attachment_download_failed'))
    } else {
      showAppErrorFromApi(e, t('resources.attachment_download_failed'))
    }
  } finally {
    pdfAttachmentDownloadBusyKey.value = null
  }
}

onUnmounted(() => {
  revokePdfPreviewBlobUrl()
})
</script>
