import { test, expect } from '@playwright/test';
import { login } from './support.mjs';
import { fieldInput, pickSelectOption } from './support.mjs';

const PDF_FIXTURE = 'tests/e2e/fixtures/peta-blok-fixture.pdf';

const uploadBlok = async (page, nama, skala = '1:1000') => {
    await fieldInput(page, 'Nama Blok').fill(nama);
    await fieldInput(page, 'Skala Peta').fill(skala);
    await page.locator('#file-upload').setInputFiles(PDF_FIXTURE);
    await page.getByRole('button', { name: 'Simpan' }).click();
};

test.describe('Unggah Peta Blok', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/peta');
    });

    test('daftar peta menampilkan blok yang sudah diunggah', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Unggah Peta Blok' })).toBeVisible();
        await expect(page.getByText('Blok Cendana 01')).toBeVisible();
        await expect(page.getByText('Blok Melati 02')).toBeVisible();
        await expect(page.getByText('1:1000')).toBeVisible();
    });

    test('upload blok baru berhasil', async ({ page }) => {
        const nama = `E2E Blok ${Date.now()}`;
        await uploadBlok(page, nama, '1:500');
        await expect(page.getByText('Peta blok berhasil diunggah!')).toBeVisible();
        await expect(page.locator('tr', { hasText: nama })).toContainText('1:500');
    });

    test('skala selain format 1:xxxx ditolak', async ({ page }) => {
        await fieldInput(page, 'Nama Blok').fill('E2E Blok Skala Salah');
        const skala = fieldInput(page, 'Skala Peta');
        await skala.fill('500');
        await expect(page.getByText('Skala harus dalam format 1:xxxx (contoh: 1:1000)')).toBeVisible();
    });

    test('hapus blok via konfirmasi', async ({ page }) => {
        const nama = `E2E Hapus Blok ${Date.now()}`;
        await uploadBlok(page, nama, '1:1000');
        await expect(page.getByText('Peta blok berhasil diunggah!')).toBeVisible();

        const row = page.locator('tr', { hasText: nama });
        await row.getByTitle('Hapus').click();
        await page.getByRole('heading', { name: 'Konfirmasi Hapus Peta Blok' }).waitFor();
        await page.getByRole('button', { name: 'Ya, Hapus' }).click();
        await expect(page.getByText('Peta blok berhasil dihapus!')).toBeVisible();
        await expect(page.locator('tr', { hasText: nama })).toHaveCount(0);
    });
});