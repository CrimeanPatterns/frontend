import time
from playwright.sync_api import sync_playwright
import asyncio
import undetected_chromedriver as uc

driver = uc.Chrome()
driver.get("https://www.hyatt.com/de-DE/member/sign-in/traditional?returnUrl=https%3A%2F%2Fworld.hyatt.com%2Fcontent%2Fgp%2Fen%2Fmy-account.html")
driver.set_window_size(1000, 900)
time.sleep(60)
