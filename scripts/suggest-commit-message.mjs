#!/usr/bin/env node
/**
 * Gợi ý commit message (Conventional Commits) từ file đang stage + nội dung diff.
 * Usage: npm run suggest:commit
 *        npm run suggest:commit -- --copy   (chỉ in dòng message, dễ pipe)
 */
import { execSync } from 'node:child_process'

const copyOnly = process.argv.includes('--copy')
const MAX_DIFF_CHARS = 120_000

function git(cmd) {
  return execSync(cmd, { encoding: 'utf8' }).trim()
}

let files
try {
  files = git('git diff --cached --name-only --diff-filter=ACM')
    .split('\n')
    .map((f) => f.trim())
    .filter(Boolean)
} catch {
  console.error('Không chạy được git. Hãy chạy trong thư mục repo.')
  process.exit(1)
}

if (files.length === 0) {
  console.error('Chưa có file stage. Chạy: git add <file> hoặc git add .')
  process.exit(1)
}

const scopes = new Set()
let type = 'chore'

const scopeRules = [
  [/^(app|tests)\/.*TransportProgram/i, 'tp'],
  [/^resources\/js\/.*driver/i, 'driver'],
  [/^resources\/js\/src\/views\/requests/i, 'requests'],
  [/^resources\/js\//, 'ui'],
  [/^app\/Http\/Controllers\/Api\/Trips/i, 'trips'],
  [/^app\/Http\/Controllers\/Api\/Requests/i, 'requests'],
  [/^routes\/api\/spa\/dispatch/i, 'requests'],
  [/^routes\/api\/.*request/i, 'requests'],
  [/^app\/Services\/LegacyImport\//, 'import'],
  [/^app\/Console\/Commands\/ImportLegacy/i, 'import'],
  [/^database\/migrations\//, 'db'],
  [/^\.husky\//, 'husky'],
  [/^package(-lock)?\.json$/, 'dev'],
]

for (const file of files) {
  for (const [re, scope] of scopeRules) {
    if (re.test(file)) {
      scopes.add(scope)
    }
  }
}

let diffStat = ''
let diffBody = ''
try {
  diffStat = git('git diff --cached --stat')
} catch {
  diffStat = ''
}
try {
  const raw = git('git diff --cached')
  diffBody = raw.length > MAX_DIFF_CHARS ? raw.slice(0, MAX_DIFF_CHARS) : raw
} catch {
  diffBody = ''
}

const diffAll = `${diffStat}\n${diffBody}`

/**
 * @returns {{ type?: string, scope?: string, subject: string } | null}
 */
function inferFromDiff(text) {
  const topics = [
    {
      test: (t) => /requests-purge|purge-all|purgeCooldown|purge_all_rate_limit/i.test(t),
      type: 'fix',
      scope: 'requests',
      subject: 'throttle purge-all theo user và countdown UI khi 429',
    },
    {
      test: (t) => /legacy-import-execute|throttle:legacy-import-execute/i.test(t),
      type: 'fix',
      scope: 'import',
      subject: 'giới hạn tần suất execute import phiếu legacy',
    },
    {
      test: (t) =>
        /LegacyDispatchImport|ImportLegacyDispatch|CargoRowMapper|PassengerRowMapper/i.test(t) &&
        /mapper|skip|import/i.test(t),
      type: 'feat',
      scope: 'import',
      subject: 'cập nhật mapper import phiếu điều vận legacy',
    },
    {
      test: (t) => /MapsWithSkipReason|function skip\(/i.test(t),
      type: 'fix',
      scope: 'import',
      subject: 'sửa kiểu trả về skip trong legacy import mappers',
    },
    {
      test: (t) => /optimistic.?lock|version conflict/i.test(t),
      type: 'fix',
      scope: 'trips',
      subject: 'sửa xung đột optimistic lock',
    },
    {
      test: (t) => /RateLimiter::for|->middleware\('throttle:/i.test(t),
      type: 'fix',
      scope: 'requests',
      subject: 'điều chỉnh rate limit API theo user',
    },
    {
      test: (t) => /data-testid|DatagridToolbar|kpi-strip|KpiSummary/i.test(t),
      type: 'feat',
      scope: 'ui',
      subject: 'cập nhật datagrid/KPI theo pattern VA',
    },
  ]

  for (const topic of topics) {
    if (topic.test(text)) {
      return { type: topic.type, scope: topic.scope, subject: topic.subject }
    }
  }
  return null
}

const inferred = inferFromDiff(diffAll)

if (/test\//.test(files.join(' ')) && files.every((f) => f.startsWith('tests/'))) {
  type = 'test'
} else if (files.some((f) => f.includes('Test.php') || f.startsWith('tests/'))) {
  if (/fix|sửa|guard|underflow|bug/i.test(diffAll)) {
    type = 'fix'
  }
} else if (/\.md$|docs\//.test(files.join(' ')) && files.every((f) => /\.md$|docs\//.test(f))) {
  type = 'docs'
} else if (
  files.every((f) => /^\.github\/|\.husky\/|package(-lock)?\.json/.test(f) || f.startsWith('.husky/'))
) {
  type = 'chore'
} else if (/feat|thêm|add new|introduce/i.test(diffAll)) {
  type = 'feat'
} else if (
  /fix|sửa|guard|underflow|bug|lỗi|429|retry-after|RateLimiter/i.test(diffAll) ||
  files.some((f) => f.includes('fix'))
) {
  type = 'fix'
} else if (/refactor/i.test(diffAll)) {
  type = 'refactor'
}

if (inferred?.type) {
  type = inferred.type
}

function pickScope() {
  if (inferred?.scope) {
    return inferred.scope
  }
  if (scopes.size === 0) {
    return null
  }
  if (scopes.size === 1) {
    return [...scopes][0]
  }
  const priority = ['requests', 'trips', 'import', 'tp', 'driver', 'db', 'ui', 'dev', 'husky']
  const sorted = [...scopes].sort((a, b) => priority.indexOf(a) - priority.indexOf(b))
  if (sorted.includes('requests') && sorted.includes('ui')) {
    return 'requests'
  }
  if (sorted.includes('import') && sorted.includes('ui')) {
    return 'import'
  }
  return sorted.slice(0, 2).join('+')
}

const scope = pickScope()
const scopePart = scope ? `(${scope})` : ''

function fallbackSubject() {
  if (files.every((f) => /^package(-lock)?\.json$/.test(f))) {
    return 'đồng bộ husky, lint-staged và Playwright'
  }
  if (files.some((f) => f.includes('StudentLogService'))) {
    return 'sửa đếm total_absent khi điểm danh CPĐD'
  }
  if (files.length === 1) {
    const base = files[0].split('/').pop()
    if (/\.(vue|tsx?|jsx?)$/.test(base)) {
      return `cập nhật màn ${base.replace(/\.(vue|tsx?|jsx?)$/, '')}`
    }
    if (base.endsWith('.php')) {
      return `cập nhật ${base.replace(/\.php$/, '')}`
    }
    return `cập nhật ${base}`
  }
  if (files.every((f) => /locales\/(en|vi)\.json$/.test(f))) {
    return 'bổ sung chuỗi i18n en/vi'
  }
  if (scopes.has('import')) {
    return 'cập nhật import phiếu điều vận legacy'
  }
  if (scopes.has('requests')) {
    return 'cập nhật luồng yêu cầu điều vận'
  }
  if (scopes.has('trips')) {
    return 'cập nhật luồng chuyến đi'
  }
  if (scopes.has('db')) {
    return 'cập nhật migration/schema'
  }
  if (scopes.has('ui')) {
    return 'cập nhật giao diện SPA'
  }
  return 'cập nhật thay đổi đang stage'
}

let subject = inferred?.subject ?? fallbackSubject()

if (!inferred && files.some((f) => f.includes('StudentLogService'))) {
  type = 'fix'
}

const message = `${type}${scopePart}: ${subject}`

if (copyOnly) {
  console.log(message)
} else {
  console.log('\nGợi ý commit message:\n')
  console.log(`  ${message}\n`)
  console.log('Commit:')
  console.log(`  git commit -m "${message.replace(/"/g, '\\"')}"\n`)
}
