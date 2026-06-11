#!/usr/bin/env node
/**
 * Stage → lint-staged → gợi ý message → git commit (hook pre-commit vẫn chạy).
 *
 * Usage:
 *   git add .
 *   npm run commit
 *   npm run commit:push
 */
import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const pushAfter = process.argv.includes('--push')
const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const suggestScript = path.join(repoRoot, 'scripts', 'suggest-commit-message.mjs')

function git(cmd, opts = {}) {
  return execSync(`git ${cmd}`, { encoding: 'utf8', cwd: repoRoot, ...opts }).trim()
}

function runShell(cmd) {
  try {
    execSync(cmd, { cwd: repoRoot, stdio: 'inherit', shell: true })
  } catch (err) {
    process.exit(typeof err.status === 'number' ? err.status : 1)
  }
}

let staged
try {
  staged = git('diff --cached --name-only --diff-filter=ACM')
    .split('\n')
    .map((f) => f.trim())
    .filter(Boolean)
} catch {
  console.error('Không chạy được git. Hãy chạy trong thư mục repo.')
  process.exit(1)
}

if (staged.length === 0) {
  console.error('Chưa có file stage. Chạy: git add . hoặc git add <file>')
  process.exit(1)
}

console.log('→ lint-staged…')
runShell('npm run commit:lint')

let message
try {
  message = execSync(`node ${JSON.stringify(suggestScript)} --copy`, {
    encoding: 'utf8',
    cwd: repoRoot,
  }).trim()
} catch {
  process.exit(1)
}

if (!message) {
  console.error('Không tạo được commit message.')
  process.exit(1)
}

console.log(`\n→ git commit\n  ${message}\n`)
runShell(`git commit -m ${JSON.stringify(message)}`)

if (pushAfter) {
  console.log('→ git push…')
  runShell('git push')
}
