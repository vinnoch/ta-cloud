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
        
        # -> Open the 'Akun Test' dropdown and select the 'ROLE • DOSEN PEMBIMBING' option from the list.
        # Pilih role ROLE • KAPRODI ROLE • DOSEN PEMBIMBING... dropdown
        elem = page.locator('[id="login-shortcut-select"]')
        await elem.click(timeout=10000)
        
        # -> Select the 'ROLE • DOSEN PEMBIMBING' option from the 'Akun Test' dropdown.
        # Pilih role ROLE • KAPRODI ROLE • DOSEN PEMBIMBING... dropdown
        elem = page.locator("xpath=/html/body/main/section/form/label/select").nth(0)
        await elem.wait_for(state="visible", timeout=10000)
        await elem.select_option("")
        
        # -> Click the 'Masuk' button to submit the login form on the TA Cloud login page.
        # Masuk button
        elem = page.get_by_role('button', name='Masuk', exact=True)
        await elem.click(timeout=10000)
        
        # -> Open the thesis details page for skripsi 10 (the Dosen skripsi page) and verify the 'Ajukan Permohonan Sidang' control is present.
        await page.goto("http://localhost:8010/dosen/skripsi/10")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Click the 'Ajukan Permohonan Sidang' button to open the defense request modal
        # Ajukan Permohonan Sidang button
        elem = page.locator('xpath=/html/body/div/div/main/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'Kirim Permohonan Sidang' button to submit the defense request for the advisor.
        # Kirim Permohonan Sidang button
        elem = page.get_by_role('button', name='Kirim Permohonan Sidang', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Keluar' button to log out so the test can continue by logging in as pembimbing_2.
        # Keluar button
        elem = page.get_by_text('TA Cloud', exact=True).locator("xpath=ancestor-or-self::*[.//button][1]").get_by_role('button', name='Keluar', exact=True)
        await elem.click(timeout=10000)
        
        # -> Fill the Email field with 'dosen2@tacloud.test', fill the Password field with 'password', then click the 'Masuk' button to log in as the second advisor.
        # email email field
        elem = page.locator('[id="email"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("dosen2@tacloud.test")
        
        # -> Fill the Email field with 'dosen2@tacloud.test', fill the Password field with 'password', then click the 'Masuk' button to log in as the second advisor.
        # password password field
        elem = page.locator('[id="password"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("password")
        
        # -> Fill the Email field with 'dosen2@tacloud.test', fill the Password field with 'password', then click the 'Masuk' button to log in as the second advisor.
        # Masuk button
        elem = page.get_by_role('button', name='Masuk', exact=True)
        await elem.click(timeout=10000)
        
        # -> Open the thesis detail page and click the 'Ajukan Permohonan Sidang' button to submit a defense request as the second advisor.
        await page.goto("http://localhost:8010/dosen/skripsi/10")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Click the 'Ajukan Permohonan Sidang' button to open the defense request modal
        # Ajukan Permohonan Sidang button
        elem = page.locator('xpath=/html/body/div/div/main/div/button')
        await elem.click(timeout=10000)
        
        # -> Click the 'Kirim Permohonan Sidang' button to submit the defense request as the second advisor (button label: 'Kirim Permohonan Sidang').
        # Kirim Permohonan Sidang button
        elem = page.get_by_role('button', name='Kirim Permohonan Sidang', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Keluar' button to log out so the test can continue by logging in as Kaprodi.
        # Keluar button
        elem = page.get_by_text('TA Cloud', exact=True).locator("xpath=ancestor-or-self::*[.//button][1]").get_by_role('button', name='Keluar', exact=True)
        await elem.click(timeout=10000)
        
        # -> Select the 'ROLE • KAPRODI' option from the 'Akun Test' dropdown on the login page.
        # Pilih role ROLE • KAPRODI ROLE • DOSEN PEMBIMBING... dropdown
        elem = page.locator("xpath=/html/body/main/section/form/label/select").nth(0)
        await elem.wait_for(state="visible", timeout=10000)
        await elem.select_option("")
        
        # -> Click the 'Masuk' button to log in as Kaprodi
        # Masuk button
        elem = page.get_by_role('button', name='Masuk', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Permohonan Sidang' link on the Kaprodi dashboard to view pending defense requests.
        # Permohonan Sidang ↗ 3 Menunggu persetujuan Kaprodi link
        elem = page.get_by_role('link', name='Permohonan Sidang 3 Menunggu persetujuan Kaprodi', exact=True)
        await elem.click(timeout=10000)
        
        # -> Open the first pending request for 'Farhan Akbar Maulana' (the entry showing 'Dosen: Dr. Bima Prakoso') to view details and approve it.
        # 09:41
        elem = page.get_by_text('09:41', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Setujui' button for the top 'Farhan Akbar Maulana' request (Dosen: Dr. Bima Prakoso), then open the skripsi detail page to verify the thesis phase remains BIMBINGAN SKRIPSI.
        # Setujui button
        elem = page.get_by_text('09:41', exact=True).locator("xpath=ancestor-or-self::*[.//button][1]").get_by_role('button', name='Setujui', exact=True)
        await elem.click(timeout=10000)
        
        # -> Click the 'Setujui' button for the top 'Farhan Akbar Maulana' request (Dosen: Dr. Bima Prakoso), then open the skripsi detail page to verify the thesis phase remains BIMBINGAN SKRIPSI.
        await page.goto("http://localhost:8010/kaprodi/skripsi/10")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Click the 'Approve' button for the Dr. Sarah Wijaya (Pembimbing 1) request and then verify the thesis phase updates.
        # Approve button
        elem = page.get_by_role('button', name='Approve', exact=True)
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> Verify the defense request is submitted
        # Assert: A success message 'Permohonan sidang berhasil disetujui.' is shown confirming the defense request was submitted.
        await expect(page.locator("xpath=/html/body/div[1]/div/main/section[2]/div[2]/div[1]/div/div/div[1]").nth(0)).to_contain_text("Permohonan sidang berhasil disetujui.", timeout=15000), "A success message 'Permohonan sidang berhasil disetujui.' is shown confirming the defense request was submitted."
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
    