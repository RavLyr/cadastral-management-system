export const ADMIN_DESA = { username: 'adminDesa1', password: 'rahasia123' };

export const login = async (page, { username = ADMIN_DESA.username, password = ADMIN_DESA.password } = {}) => {
    await page.goto('/login');
    await page.locator('input[type="text"]').first().fill(username);
    await page.locator('input[type="password"]').first().fill(password);
    await page.locator('button[type="submit"]').first().click();
    await page.waitForURL(/\/dashboard/);
};

export const field = (page, label) =>
    page.locator('div.space-y-2:visible', { has: page.locator('label', { hasText: label }) }).last();

export const fieldInput = (page, label) => field(page, label).locator('input').first();

export const pickSelectOption = async (page, label, optionText) => {
    await field(page, label).locator('.vs__search').first().click();
    await page.locator('.vs__dropdown-option', { hasText: optionText }).first().click();
};