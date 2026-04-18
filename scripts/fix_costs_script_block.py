from pathlib import Path

p = Path(__file__).resolve().parents[1] / "resources/js/src/views/costs/CostsListView.vue"
text = p.read_text(encoding="utf-8")
start = text.index("const TYPE_LABELS = {")
end = text.index("const loading = ref(false)", start)
new = """const BUILTIN_COST_TYPES = ['fuel', 'toll', 'parking', 'other']
const EXTRA_TYPES_STORAGE_KEY = 'va.costs.extra_types_v1'
const COSTS_FILTER_CONTROL_VISIBILITY_KEY = 'va.costs.filter_control_visibility_v1'
const FILTER_CONTROL_IDS = ['status', 'type', 'date', 'trip', 'search', 'per_page']

function defaultFilterControlVisibility() {
  return Object.fromEntries(FILTER_CONTROL_IDS.map((id) => [id, true]))
}

const extraCostTypes = ref([])

function typeLabel(slug) {
  if (!slug) return '—'
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  const hit = extraCostTypes.value.find((x) => x.slug === slug)
  return hit?.label ?? slug
}

const modalCostTypeOptions = computed(() => {
  const rows = BUILTIN_COST_TYPES.map((value) => ({ value, label: typeLabel(value) }))
  for (const x of extraCostTypes.value) {
    if (!rows.some((r) => r.value === x.slug)) {
      rows.push({ value: x.slug, label: x.label })
    }
  }
  return rows
})

"""
text = text[:start] + new + text[end:]
p.write_text(text, encoding="utf-8")
print("ok")
