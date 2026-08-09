import { test, expect } from '@playwright/test';
import { login } from './support.mjs';
import { field, pickSelectOption } from './support.mjs';

test.describe('Pencarian Data Tanah & Peta Interaktif', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/pencarian');
    });

    test('cari berdasarkan nama lalu tampilkan hasil', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Pencarian Data Tanah' })).toBeVisible();
        await page.getByPlaceholder('Cari berdasarkan Nama, NOP, Nomor Persil, atau Blok...')
            .fill('E2E BUAH MERAH');
        await expect(page.locator('button', { hasText: 'Lihat Peta' })).toBeVisible();
    });

    test.fixme('klik Lihat Peta menampilkan informasi bidang di peta', async ({ page }) => {
        await page.getByPlaceholder('Cari berdasarkan Nama, NOP, Nomor Persil, atau Blok...')
            .fill('E2E BUAH MERAH');
        const hasilRow = page.locator('tbody tr').filter({ hasText: 'E2E BUAH MERAH' });
        await expect(hasilRow).toBeVisible({ timeout: 60000 });
        await page.getByRole('button', { name: 'Lihat Peta' }).click();

        await expect(page.getByText('Informasi Bidang Terpilih')).toBeVisible({ timeout: 30000 });
        await expect(page.getByText('Status Data', { exact: true })).toBeVisible({ timeout: 30000 });
        await expect(page.getByText('Data Tanah tersedia')).toBeVisible({ timeout: 30000 });
        await expect(page.getByText('NOP', { exact: true })).toBeVisible({ timeout: 30000 });

        // popup Leaflet berisi link Print
        const popup = page.locator('.leaflet-popup-content');
        await expect(popup).toContainText('Print', { timeout: 30000 });
        await expect(popup.locator('a[href^="/print/"]')).toBeVisible({ timeout: 30000 });
    });

    test('catat perubahan ganti pemilik', async ({ page }) => {
        await page.getByPlaceholder('Cari berdasarkan Nama, NOP, Nomor Persil, atau Blok...')
            .fill('E2E BUAH MERAH');
        await page.getByRole('button', { name: 'Lihat Peta' }).click();
        await page.getByRole('button', { name: 'Catat Perubahan' }).click();

        await expect(page.getByRole('heading', { name: 'Catat Perubahan' })).toBeVisible();
        await pickSelectOption(page, 'Jenis Perubahan', 'Ganti Pemilik');
        await field(page, 'Pemilik Baru').locator('input').fill('PEMILIK E2E BARU');
        await field(page, 'Tanggal Perubahan').locator('input').fill('2026-08-09');

        await page.getByRole('button', { name: 'Simpan' }).click();
        await page.getByRole('button', { name: 'Ya, Simpan Perubahan' }).click();
        await expect(page.getByText('Riwayat perubahan tanah berhasil dicatat.')).toBeVisible();
    });

    test('link Print membuka halaman PDF di tab baru', async ({ page, context }) => {
        await page.getByPlaceholder('Cari berdasarkan Nama, NOP, Nomor Persil, atau Blok...')
            .fill('E2E BUAH MERAH');
        await page.getByRole('button', { name: 'Lihat Peta' }).click();

        const popup = page.locator('.leaflet-popup-content').last();
        const printLink = popup.locator('a[href^="/print/"]');
        await expect(printLink).toBeVisible({ timeout: 30000 });

        const printResponse = context.waitForEvent('response', {
            predicate: (r) => r.url().includes('/print/'),
            timeout: 15000,
        });
        await printLink.click();
        const res = await printResponse;
        expect(res.status()).toBe(200);
        expect(res.headers()['content-type']).toContain('application/pdf');
    });
});