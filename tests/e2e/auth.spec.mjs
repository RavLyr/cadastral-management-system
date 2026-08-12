import { test, expect } from '@playwright/test';
import { login, ADMIN_DESA } from './support.mjs';

export const expectDashboard = async (page) => {
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
};

// User tidak perlu register: akun dibuat/disposal via seeding oleh backend.
// pengguna nyata aplikasi = adminDesa, admin adalah pemilik/maintener sistem.
test('halaman utama menuju ke /login untuk pengguna belum login', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/login$/);
});

test('login sukses sebagai adminDesa lalu muncul dashboard dgn nama user', async ({ page }) => {
    await login(page);
    await expectDashboard(page);
    await expect(page.getByText('adminDesa1', { exact: true })).toBeVisible();
});

test('login gagal bila password salah', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[type="text"]').first().fill(ADMIN_DESA.username);
    await page.locator('input[type="password"]').first().fill('salah123');
    await page.locator('button[type="submit"]').first().click();
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByText(/credentials do not match| tidak cocok/i)).toBeVisible();
});

test('akses halaman protected tanpa login diarahkan ke /login', async ({ page }) => {
    await page.goto('/tanah');
    await expect(page).toHaveURL(/\/login$/);
});

test('logout kembali ke halaman login', async ({ page }) => {
    await login(page);
    await expectDashboard(page);
    await page.getByRole('button', { name: 'Logout' }).click();
    await expect(page).toHaveURL(/\/login$/);
});