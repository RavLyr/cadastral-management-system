import { test, expect } from '@playwright/test';
import { execSync } from 'node:child_process';
import { login } from './support.mjs';

const TANAH_XLSX = 'tests/e2e/fixtures/tanah-import-run.xlsx';

test.describe('Import Data Tanah (Excel)', () => {
    test.beforeAll(() => {
        // Self-contained: kembalikan DB ke seed agar import selalu berhasil saat suite diulang.
        execSync('bash tests/e2e/reset-db.sh', { stdio: 'inherit' });
    });
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/tanah/import');
    });

    test('preview lalu import data tanah', async ({ page }) => {
        await page.getByRole('heading', { name: 'Import Data Tanah' }).waitFor();
        await page.locator('input[type="file"]').setInputFiles(TANAH_XLSX);
        await page.getByRole('button', { name: 'Preview Import' }).click();

        await expect(page.getByText('Siap diimport')).toBeVisible();
        await expect(page.getByText(/Data Valid.?/)).toBeVisible();

        const importBtn = page.getByRole('button', { name: 'Import Sekarang' });
        await expect(importBtn).toBeEnabled();
        await importBtn.click();
        await expect(page).toHaveURL(/\/tanah$/);
        await expect(page.locator('tr', { hasText: 'Ahmad E2E Satu' })).toHaveCount(1);
    });

    test('tombol import tetap nonaktif tanpa preview valid', async ({ page }) => {
        const importBtn = page.getByRole('button', { name: 'Import Sekarang' });
        await expect(importBtn).toBeDisabled();
    });
});