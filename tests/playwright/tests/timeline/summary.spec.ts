import { test, expect } from '@playwright/test';
import * as fs from 'fs';

test('timeline summary', async ({ page }) => {
    const data = JSON.parse(fs.readFileSync(__filename.replace('.spec.ts', '.data.json'), 'utf8'))
    await page.goto('/timeline/summary?_switch_user=' + data['login']);

  // Expect a title "to contain" a substring.
  await expect(page).toHaveTitle(/AwardWallet/);
  await expect(page.getByText('Historical Travel Summary')).toBeVisible();
});
