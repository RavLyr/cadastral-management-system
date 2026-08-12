import { test, expect } from '@playwright/test';
import { login } from './support.mjs';
import { fieldInput } from './support.mjs';

const XLSX_FIXTURE = 'tests/e2e/fixtures/sismiop-import-fixture.xlsx';

test.describe('Data SISMIOP', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/sismiop');
    });

    test('index menampilkan data yang sudah diimport', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Upload SISMIOP' })).toBeVisible();
        await expect(page.getByText('SRI RAHAYU E2E')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Download Template Excel' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Upload & Preview' })).toBeVisible();
    });

    test('upload -> preview -> simpan ke database', async ({ page }) => {
        await page.locator('#excel-upload').setInputFiles(XLSX_FIXTURE);
        await page.getByRole('button', { name: 'Upload & Preview' }).click();

        await expect(page.getByText('Preview Data Import')).toBeVisible();
        await expect(page.getByText(/Total: \d+ record/)).toBeVisible();
        await page.getByRole('button', { name: 'Simpan ke Database' }).click();
        await page.getByRole('heading', { name: 'Konfirmasi Simpan Data' }).waitFor();
        await page.getByRole('button', { name: 'Ya, Simpan' }).click();
        await expect(page.locator('tbody tr')).toHaveCount(4);

        const search = page.getByPlaceholder('Cari berdasarkan NOP atau Nama Wajib Pajak...');
        await search.fill('SUKINI E2E');
        await search.press('Enter');
        await expect(page.getByText('SUKINI E2E')).toBeVisible();
    });

    test('pencarian data berdasarkan nama wajib pajak', async ({ page }) => {
        const search = page.getByPlaceholder('Cari berdasarkan NOP atau Nama Wajib Pajak...');
        await search.fill('SRI RAHAYU E2E');
        await search.press('Enter');
        await expect(page.getByText('SRI RAHAYU E2E')).toBeVisible();
        await expect(page.locator('tbody tr')).toHaveCount(1);
    });

    test('edit data sismiop', async ({ page }) => {
        await page.getByTitle('Edit').first().click();
        await expect(page).toHaveURL(/\/sismiop\/\d+\/edit/);
        await fieldInput(page, 'Nama Wajib Pajak').fill('SRI RAHAYU E2E UPDATED');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByText(/berhasil diperbarui/i)).toBeVisible();
        await expect(page).toHaveURL(/\/sismiop$/);
        await expect(page.getByText('SRI RAHAYU E2E UPDATED')).toBeVisible();
    });

    test('hapus satu data via konfirmasi', async ({ page }) => {
        await page.getByTitle('Hapus').first().click();
        await page.getByRole('heading', { name: 'Konfirmasi Hapus Data' }).waitFor();
        await page.getByRole('button', { name: 'Hapus' }).last().click();
        await expect(page.getByText('Data berhasil dihapus')).toBeVisible();
    });

    test('hapus semua data membutuhkan konfirmasi ketik', async ({ page }) => {
        await page.getByRole('button', { name: 'Hapus Semua Data' }).click();
        await page.getByRole('heading', { name: 'Konfirmasi Hapus Semua Data' }).waitFor();
        const confirmBtn = page.getByRole('button', { name: 'Ya, Hapus Semua' });
        await expect(confirmBtn).toBeDisabled();
        await page.getByPlaceholder("Ketik 'hapus semua data' untuk konfirmasi").fill('hapus semua data');
        await expect(confirmBtn).toBeEnabled();
        await confirmBtn.click();
        await expect(page.getByText('Semua data berhasil dihapus')).toBeVisible();
        await expect(page.getByText('Belum ada data yang tersimpan')).toBeVisible();
    });
});