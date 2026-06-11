#!/usr/bin/env node
/**
 * Stage → lint-staged → gợi ý message → git commit (hook pre-commit vẫn chạy).
 * Không gọi `npm`/`npx` lồng nhau — tránh lỗi Windows "The system cannot find the path specified".
 *
 * Usage:
 *   git add .
 *   npm run commit
 *   npm run commit:push
 */
import { execFileSync, spawnSync } from 'node:child_process'
import { existsSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import lintStaged from 'lint-staged'

const pushAfter = process.argv.includes('--push')
const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const suggestScript = path.join(repoRoot, 'scripts', 'suggest-commit-message.mjs')

function gitOutput(args) {
  return execFileSync('git', args, { encoding: 'utf8', cwd: repoRoot }).trim()
}

function runGit(args) {
  const r = spawnSync('git', args, { cwd: repoRoot, stdio: 'inherit', windowsHide: true })
  if (r.status !== 0) {
    process.exit(r.status ?? 1)
  }
  if (r.error) {
    console.error(r.error.message || r.error)
    process.exit(1)
  }
}

/** Giống .husky/pre-commit — ServBay khi `php` không có trong PATH của process con. */
function ensurePhpOnPath() {
  if (process.env.PHP_BIN) {
    return
  }
  try {
    execFileSync('php', ['-v'], { stdio: 'ignore', windowsHide: true })
    return
  } catch {
    /* thử ServBay */
  }
  const servBayBin = 'C:\\ServBay\\bin'
  const phpCmd = path.join(servBayBin, 'php.cmd')
  if (existsSync(phpCmd)) {
    process.env.PATH = `${servBayBin};${process.env.PATH || ''}`
  }
}

async function main() {
  let staged
  try {
    staged = gitOutput(['diff', '--cached', '--name-only', '--diff-filter=ACM'])
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

  const hasPhpStaged = staged.some((f) => f.endsWith('.php'))
  if (hasPhpStaged) {
    ensurePhpOnPath()
    console.log('→ lint-staged…')
    const ok = await lintStaged({
      cwd: repoRoot,
      shell: process.platform === 'win32',
    })
    if (!ok) {
      process.exit(1)
    }
  }

  let message
  try {
    message = execFileSync(process.execPath, [suggestScript, '--copy'], {
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
  runGit(['commit', '-m', message])

  if (pushAfter) {
    console.log('→ git push…')
    runGit(['push'])
  }
}

main().catch((err) => {
  console.error(err?.message || err)
  process.exit(1)
})
