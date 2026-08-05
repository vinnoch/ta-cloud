import asyncio
import re
from playwright import async_api
from playwright.async_api import expect

async def run_test():
    pw = None
    browser = None
    context = None

    try:
        # Start a Playwright session in asynchronous mode
        pw = await async_api.async_playwright().start()

        # Launch a Chromium browser in headless mode with custom arguments
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--window-size=1280,720",
                "--disable-dev-shm-usage",
                "--ipc=host",
                "--single-process"
            ],
        )

        # Create a new browser context (like an incognito window)
        context = await browser.new_context()
        # Wider default timeout to match the agent's DOM-stability budget;
        # auto-waiting Playwright APIs (expect, locator.wait_for) inherit this.
        context.set_default_timeout(15000)

        # Open a new page in the browser context
        page = await context.new_page()

        # Interact with the page elements to simulate user flow
        # -> navigate
        await page.goto("http://localhost:8010")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass

        # -> Open the 'Library' page by navigating to /library and verify the completed theses list is accessible without signing in.
        await page.goto("http://127.0.0.1:8010/library")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass

        # -> Click the 'Detail' link for the first thesis titled 'Analisis Retensi Mahasiswa Menggunakan Data Mining' to open its detail page.
        # Detail link
        elem = page.locator('a[href="/library/13-analisis-retensi-mahasiswa-menggunakan-data-mining"]')
        await elem.click(timeout=10000)

        # -> Click the 'Library' link in the left navigation to return to the thesis list and verify the list is displayed again.
        # Library link
        elem = page.get_by_role('link', name='Library', exact=True)
        await elem.click(timeout=10000)

        # --> Assertions to verify final state

        # --> Verify completed theses are displayed
        await page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[2]/div[4]/a").nth(0).scroll_into_view_if_needed()
        # Assert: The thesis 'Analisis Retensi Mahasiswa Menggunakan Data Mining' is listed (Detail link is visible).
        await expect(page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[2]/div[4]/a").nth(0)).to_be_visible(timeout=15000), "The thesis 'Analisis Retensi Mahasiswa Menggunakan Data Mining' is listed (Detail link is visible)."
        await page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[3]/div[4]/a").nth(0).scroll_into_view_if_needed()
        # Assert: The thesis 'Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT' is listed (Detail link is visible).
        await expect(page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[3]/div[4]/a").nth(0)).to_be_visible(timeout=15000), "The thesis 'Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT' is listed (Detail link is visible)."

        # --> Verify the thesis list is displayed again
        await page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[2]/div[4]/a").nth(0).scroll_into_view_if_needed()
        # Assert: The thesis list shows a 'Detail' link for Analisis Retensi Mahasiswa Menggunakan Data Mining.
        await expect(page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[2]/div[4]/a").nth(0)).to_be_visible(timeout=15000), "The thesis list shows a 'Detail' link for Analisis Retensi Mahasiswa Menggunakan Data Mining."
        await page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[3]/div[4]/a").nth(0).scroll_into_view_if_needed()
        # Assert: The thesis list shows a 'Detail' link for Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT.
        await expect(page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[3]/div[4]/a").nth(0)).to_be_visible(timeout=15000), "The thesis list shows a 'Detail' link for Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT."
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
