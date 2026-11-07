// Requires Playwright and Chromium. Uses existing local bootstrap credentials without logging them.
const { chromium } = require('playwright');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const assert = require('node:assert/strict');

async function main() {
  const settings = Object.fromEntries(readFileSync(join(__dirname, '../.env'), 'utf8')
    .split('\n').filter(line => line && !line.startsWith('#')).map(line => {
      const i = line.indexOf('='); return [line.slice(0, i), line.slice(i + 1)];
    }));
  const origin = settings.APP_ORIGIN || 'http://localhost:4200';
  const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium', headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(origin);
    await page.getByRole('heading', { name: 'Sign in', exact: true }).waitFor();
    await page.getByLabel('Email', { exact: true }).fill(settings.BOOTSTRAP_EMAIL);
    await page.getByLabel('Password', { exact: true }).fill('deliberately-incorrect-test-password');
    await page.getByRole('button', { name: 'Sign in →' }).click();
    await page.getByRole('alert').filter({ hasText: 'invalid credentials' }).waitFor();
    await page.getByLabel('Password', { exact: true }).fill(settings.BOOTSTRAP_PASSWORD);
    await page.getByRole('button', { name: 'Sign in →' }).click();
    await page.getByRole('heading', { name: 'Network inventory', exact: true }).waitFor();
    await page.getByRole('button', { name: 'Refresh', exact: true }).waitFor();
    assert.deepEqual(await page.evaluate(() => ({ local: localStorage.length, session: sessionStorage.length })), { local: 0, session: 0 });
    assert.equal(await page.evaluate(() => document.cookie.includes('rac_session')), false);
    await page.reload();
    await page.getByRole('heading', { name: 'Network inventory', exact: true }).waitFor();
    const nav = page.getByRole('navigation', { name: 'Main navigation' });
    for (const section of ['buildings', 'locations']) {
      await nav.getByRole('button', { name: section, exact: true }).click();
      await page.getByRole('heading', { name: section, exact: true }).waitFor();
    }
    await nav.getByRole('button', { name: 'Network inventory', exact: true }).click();
    await page.getByRole('button', { name: '+ Add device', exact: true }).click();
    const form = page.locator('form.record-form');
    assert.equal(await form.evaluate(f => f.checkValidity()), false);
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await form.waitFor({ state: 'detached' });
    await nav.getByRole('button', { name: 'Audit trail', exact: true }).click();
    await page.getByRole('heading', { name: 'Audit trail', exact: true }).waitFor();
    await page.getByRole('cell', { name: 'auth.login', exact: true }).first().waitFor();
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'mobile page overflows horizontally');
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await page.getByRole('heading', { name: 'Sign in', exact: true }).waitFor();
    assert.equal(await page.evaluate(() => fetch('/api/v1/auth/me').then(r => r.status)), 401);
    assert.deepEqual(errors, [], 'browser runtime errors');
    console.log('PASS: Chromium login errors, login, session reload, navigation, required fields, audit display, mobile layout, logout and HttpOnly session protection');
  } finally {
    await browser.close();
  }
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
