import { test, expect } from '@playwright/test';
import { login } from './support.mjs';

const field = (page, label) =>
    page.locator('div.space-y-2:visible', { has: page.locator('label', { hasText: label }) }).last();

const formInput = (page, label) => field(page, label).locator('input').last();

const formSelect = (page, label) => field(page, label).locator('.v-select');

const pickSelectOption = async (page, label, optionText) => {
    await formSelect(page, label).locator('.vs__search').first().click();
    await page.locator('.vs__dropdown-option', { hasText: optionText }).first().click();
};

test.describe('Manajemen Data Tanah', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/tanah');
    });

    test('halaman index menampilkan data & tombol aktif karena blok ada', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Manajemen Data Tanah' })).toBeVisible();
        await expect(page.getByText('E2E BUAH MERAH')).toBeVisible();
        await expect(page.getByText('P001')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Import Excel' })).toBeEnabled();
        await expect(page.getByRole('button', { name: 'Tambah Data Tanah' })).toBeEnabled();
    });

    test('pencarian berdasarkan nama', async ({ page }) => {
        const search = page.getByPlaceholder('Cari berdasarkan Jenis Tanah...');
        await search.fill('E2E BUAH MERAH');
        await search.press('Enter');
        await expect(page.getByText('E2E BUAH MERAH')).toBeVisible();
        await expect(page.getByText('P001')).toBeVisible();
    });

    test('tambah data tanah via modal', async ({ page }) => {
        await page.getByRole('button', { name: 'Tambah Data Tanah' }).click();
        await expect(page.getByRole('heading', { name: 'Tambah Data Tanah' })).toBeVisible();

        const nama = `E2E TAMBAH ${Date.now()}`;
await formInput(page, 'No Urut').fill('99');
        await formInput(page, 'Nama Wajib IPEDA').fill(nama);
        await formInput(page, 'Tempat Tinggal').fill('Dusun E2E');
        await formInput(page, 'Nomor Persil').fill(`999${Date.now() % 1000}`);
        await pickSelectOption(page, 'Blok', 'Blok Cendana 01');
        await pickSelectOption(page, 'Jenis Tanah', 'Tanah Basah');
        await formInput(page, 'Luas HA').fill('1.5');
        await formInput(page, 'Luas DA').fill('0.5');

        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByText(/Data berhasil (disimpan|ditambahkan)/)).toBeVisible();
        await expect(page.locator('tr', { hasText: nama })).toHaveCount(1);
    });

    test('validasi luas <= 0 menolak simpan', async ({ page }) => {
        await page.getByRole('button', { name: 'Tambah Data Tanah' }).click();
        await formInput(page, 'Nama Wajib IPEDA').fill('E2E NEGATIF');
        await formInput(page, 'Nomor Persil').fill('NEG001');
        await pickSelectOption(page, 'Blok', 'Blok Cendana 01');
        await pickSelectOption(page, 'Jenis Tanah', 'Tanah Kering');
        await formInput(page, 'Luas HA').fill('-5');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByText('Luas HA harus lebih besar dari 0.')).toBeVisible();
    });

    test('edit data tanah via modal', async ({ page }) => {
        await page.getByTitle('Edit').first().click();
        await expect(page.getByRole('heading', { name: 'Edit Data Tanah' })).toBeVisible();
        const nama = `E2E EDIT ${Date.now()}`;
        await formInput(page, 'Nama Wajib IPEDA').fill(nama);
        await page.getByRole('button', { name: 'Simpan' }).last().click();
        await expect(page.getByText(/Data berhasil (diupdate|diubah)/)).toBeVisible();
        await expect(page.locator('tr', { hasText: nama })).toHaveCount(1);
    });

    test('hapus data via modal konfirmasi', async ({ page }) => {
        await page.getByRole('button', { name: 'Tambah Data Tanah' }).click();
        const nama = `E2E HAPUS ${Date.now()}`;
        await formInput(page, 'Nama Wajib IPEDA').fill(nama);
        await formInput(page, 'Nomor Persil').fill(`HAP${Date.now() % 1000}`);
        await pickSelectOption(page, 'Blok', 'Blok Melati 02');
        await pickSelectOption(page, 'Jenis Tanah', 'Tanah Basah');
        await page.getByRole('button', { name: 'Simpan' }).click();
        await expect(page.getByText(/Data berhasil (disimpan|ditambahkan)/)).toBeVisible();

        await page.getByTitle('Hapus').first().click();
        await page.getByRole('heading', { name: 'Konfirmasi Hapus Data' }).waitFor();
        await page.getByRole('button', { name: 'Hapus' }).last().click();
        await expect(page.getByText(/berhasil dihapus/i)).toBeVisible();
    });
});