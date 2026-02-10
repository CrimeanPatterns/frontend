import time
from playwright.sync_api import sync_playwright
import asyncio

with sync_playwright() as p:
    browser = p.firefox.launch(headless=False)
    page = browser.new_page()
    page.goto('https://www.hyatt.com/de-DE/member/sign-in/traditional?returnUrl=https%3A%2F%2Fworld.hyatt.com%2Fcontent%2Fgp%2Fen%2Fmy-account.html', timeout=0)
    time.sleep(10)
    i=0

    while i < 60:
        page.mouse.wheel(0,500)
        time.sleep(2)
        print(i)
        i=i+1

