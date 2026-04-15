import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.join(__dirname, '..')
const vuePath = path.join(root, 'resources/js/src/views/requests/DispatchRequestCreateView.vue')
let s = fs.readFileSync(vuePath, 'utf8')
const start = s.indexOf('        <!-- Step 3 -->')
const end = s.indexOf('        <!-- Step 4 -->')
if (start < 0 || end < 0) throw new Error('markers not found')
let inner = s.slice(start, end)
inner = inner.replace(/^        <div v-show="step === 2" class="space-y-6 sm:space-y-8">\n/, '')
inner = inner.replace(/\n        <\/div>\s*$/, '')
let t = inner
t = t.replace(/\bform\./g, 'w.form.')
t = t.replace(/v-model="form\./g, 'v-model="w.form.')
t = t.replace(/\bisCargo\b/g, 'w.isCargo')
t = t.replace(/\bisPointToPointTrip\b/g, 'w.isPointToPointTrip')
t = t.replace(/\bpassengerRows\b/g, 'w.passengerRows')
t = t.replace(/\bbusinessRows\b/g, 'w.businessRows')
t = t.replace(/\bcargoRows\b/g, 'w.cargoRows')
t = t.replace(/\bformatCurrency\(/g, 'w.formatCurrency(')
t = t.replace(/\browLineTotal\(/g, 'w.rowLineTotal(')
t = t.replace(/\bpassengerE1Total\b/g, 'w.passengerE1Total')
t = t.replace(/\bpassengerE2Total\b/g, 'w.passengerE2Total')
t = t.replace(/\bcargoTotal\b/g, 'w.cargoTotal')
t = t.replace(/@click="addPassengerRow"/g, '@click="w.addPassengerRow"')
t = t.replace(/@click="removePassengerRow\(/g, '@click="w.removePassengerRow(')
t = t.replace(/@click="addBusinessRow"/g, '@click="w.addBusinessRow"')
t = t.replace(/@click="removeBusinessRow\(/g, '@click="w.removeBusinessRow(')
t = t.replace(/@click="addCargoRow"/g, '@click="w.addCargoRow"')
t = t.replace(/@click="removeCargoRow\(/g, '@click="w.removeCargoRow(')
t = t.replace(/@click="openDatePickerFromInput\(/g, '@click="w.openDatePickerFromInput(')
t = t.replace(/@click="toggleE1Weekday\(/g, '@click="w.toggleE1Weekday(')
t = t.replace(/\be1WeekdayOptions\b/g, 'w.e1WeekdayOptions')
// :class="{ 'dw-weekday-chip--on': form.e1_weekdays[w.k] }" — form. already w.form.
const out = `<template>
  <div class="space-y-6 sm:space-y-8">
${t.trim()}
  </div>
</template>
<script setup>
import { inject } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'

const w = inject(DISPATCH_WIZARD_KEY)
if (!w) throw new Error('DispatchWizardStep3: missing DISPATCH_WIZARD_KEY provider')
</script>
`
const outPath = path.join(
  root,
  'resources/js/src/views/requests/dispatch-wizard/DispatchWizardStep3.vue',
)
fs.writeFileSync(outPath, out)
console.log('Wrote', outPath, out.length)
