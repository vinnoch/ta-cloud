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

        # -> Open the public 'Library' page by navigating to /library and check the page for a thesis search or completed-thesis list.
        await page.goto("http://127.0.0.1:8010/library")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass

        # -> Type 'E-Wallet' into the 'Cari judul TA...' search field and wait for results to appear.
        # Cari judul TA... text field
        elem = page.locator('[id="ta-search"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("E-Wallet")

        # --> Assertions to verify final state

        # --> Verify completed thesis results are displayed
        # Assert: The search input contains the value 'E-Wallet'.
        await expect(page.locator("xpath=/html/body/div[1]/div/div/div[2]/label/input").nth(0)).to_have_value("E-Wallet", timeout=15000), "The search input contains the value 'E-Wallet'."
        await page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[3]/div[4]/a").nth(0).scroll_into_view_if_needed()
        # Assert: The completed thesis result 'Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT' is visible (Detail link present).
        await expect(page.locator("xpath=/html/body/div[1]/div/main/section[3]/article/div[2]/div[3]/div[4]/a").nth(0)).to_be_visible(timeout=15000), "The completed thesis result 'Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT' is visible (Detail link present)."
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
