/**
 * Skip Husky on production servers and CI asset builds (HUSKY=0).
 */
if (process.env.HUSKY === '0') {
    process.exit(0);
}

const { execSync } = await import('node:child_process');

execSync('husky', { stdio: 'inherit' });
