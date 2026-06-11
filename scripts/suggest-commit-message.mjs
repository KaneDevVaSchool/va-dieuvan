#!/usr/bin/env node
/**
 * Gợi ý commit message (Conventional Commits) từ file đang stage.
 * Usage: npm run suggest:commit
 *        npm run suggest:commit -- --copy   (chỉ in dòng message, dễ pipe)
 */
import { execSync } from 'node:child_process'

const copyOnly = process.argv.includes('--copy')

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
  [/^resources\/js\//, 'ui'],
  [/^app\/Http\/Controllers\/Api\/Trips/i, 'trips'],
  [/^app\/Http\/Controllers\/Api\/Requests/i, 'requests'],
  [/^\.husky\//, 'husky'],
  [/^package(-lock)?\.json$/, 'dev'],
  [/^database\/migrations\//, 'db'],
]

for (const file of files) {
  for (const [re, scope] of scopeRules) {
    if (re.test(file)) {
      scopes.add(scope)
    }
  }
}

let diff = ''
try {
  diff = git('git diff --cached --stat')
} catch {
  diff = ''
}

const diffLower = diff.toLowerCase()
if (/test\//.test(files.join(' ')) && files.every((f) => f.startsWith('tests/'))) {
  type = 'test'
} else if (files.some((f) => f.includes('Test.php') || f.startsWith('tests/'))) {
  if (/fix|sửa|guard|underflow|bug/i.test(diff)) {
    type = 'fix'
  }
} else if (/\.md$|docs\//.test(files.join(' '))) {
  type = 'docs'
} else if (/^\.github\/|\.husky\/|package(-lock)?\.json/.test(files.join(' '))) {
  type = 'chore'
} else if (/feat|thêm|add new|introduce/i.test(diff)) {
  type = 'feat'
} else if (/fix|sửa|guard|underflow|bug|lỗi/i.test(diff) || files.some((f) => f.includes('fix'))) {
  type = 'fix'
} else if (/refactor/i.test(diff)) {
  type = 'refactor'
}

const scope =
  scopes.size === 1
    ? [...scopes][0]
    : scopes.size > 1
      ? [...scopes].slice(0, 2).join('+')
      : null

const scopePart = scope ? `(${scope})` : ''

let subject = 'cập nhật thay đổi đang stage'
if (files.length === 1) {
  const base = files[0].split('/').pop()
  subject = `cập nhật ${base}`
} else if (files.every((f) => /^package(-lock)?\.json$/.test(f))) {
  subject = 'đồng bộ husky, lint-staged và Playwright'
} else if (files.some((f) => f.includes('StudentLogService'))) {
  subject = 'sửa đếm total_absent khi điểm danh CPĐD'
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
