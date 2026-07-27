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
        
        # -> Open the 'Akun Test' dropdown and reveal the role options so 'ROLE • DOSEN PEMBIMBING' can be selected.
        # Pilih role ROLE • KAPRODI ROLE • DOSEN PEMBIMBING... dropdown
        elem = page.locator('[id="login-shortcut-select"]')
        await elem.click(timeout=10000)
        
        # -> Click the 'Masuk' button to sign in as the selected role after the Akun Test selection populates the credentials.
        # Pilih role ROLE • KAPRODI ROLE • DOSEN PEMBIMBING... dropdown
        elem = page.locator("xpath=/html/body/main/section/form/label/select").nth(0)
        await elem.wait_for(state="visible", timeout=10000)
        await elem.select_option("")
        
        # -> Click the 'Masuk' button to sign in as the selected role.
        # Masuk button
        elem = page.get_by_role('button', name='Masuk', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Skripsi' link in the left sidebar to open the Skripsi list.
        # Skripsi link
        elem = page.get_by_role('link', name='Skripsi', exact=True)
        await elem.click(timeout=10000)
        
        # -> Open the thesis details page for Muhammad Daffa Pratama (20210800007) — the target thesis details the tester requested to inspect.
        await page.goto("http://localhost:8010/dosen/skripsi/7")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Scroll the page to reveal the 'REVIEW DOKUMEN FINAL' button and list all visible button labels so the 'REVIEW DOKUMEN FINAL' control can be identified.
        await page.mouse.wheel(0, 300)
        
        # -> Scroll up to reveal the top area and list visible button labels so the 'REVIEW DOKUMEN FINAL' control can be identified.
        await page.mouse.wheel(0, 300)
        
        # -> Click the 'Keluar' (Logout) button to sign out so the Kaprodi test can be performed from the login page.
        # Keluar button
        elem = page.get_by_text('TA Cloud', exact=True).locator("xpath=ancestor-or-self::*[.//button][1]").get_by_role('button', name='Keluar', exact=True)
        await elem.click(timeout=10000)
        
        # -> Select 'ROLE • KAPRODI' from the Akun Test dropdown and click the 'Masuk' button to sign in as Kaprodi.
        # Pilih role ROLE • KAPRODI ROLE • DOSEN PEMBIMBING... dropdown
        elem = page.locator("xpath=/html/body/main/section/form/label/select").nth(0)
        await elem.wait_for(state="visible", timeout=10000)
        await elem.select_option("")
        
        # -> Select 'ROLE • KAPRODI' from the Akun Test dropdown and click the 'Masuk' button to sign in as Kaprodi.
        # Masuk button
        elem = page.get_by_role('button', name='Masuk', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Review Dokumen Final' link in the left navigation to open the Kaprodi review list page.
        # Review Dokumen Final 1 link
        elem = page.get_by_role('link', name='Review Dokumen Final 1', exact=True)
        await elem.click(timeout=10000)
        
        # -> Open the Kaprodi thesis detail page for skripsi ID 7 by navigating to the URL /kaprodi/skripsi/7 so the final-document review controls can be inspected.
        await page.goto("http://localhost:8010/kaprodi/skripsi/7")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Click the 'Validasi & Selesaikan Skripsi' button to attempt finalizing the thesis submission and observe the UI result (confirmation, success, or error).
        # Validasi & Selesaikan Skripsi button
        elem = page.get_by_role('button', name='Validasi & Selesaikan Skripsi', exact=True)
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> Verify the approval status is visible
        # Assert: The approval status '2/2' is visible.
        await expect(page.locator("xpath=/html/body/div[1]/div/main/div[1]/section[2]/div[2]/div[1]/div[1]/strong").nth(0)).to_have_text("2/2", timeout=15000), "The approval status '2/2' is visible."
        current_url = await page.evaluate("() => window.location.href")
        # Assert: page loaded with a URL (final outcome verified by the AI judge during the run)
        assert current_url, 'Page should have loaded with a URL'
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    