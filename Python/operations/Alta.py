from selenium import webdriver
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from pyvirtualdisplay import Display
import datetime
import time


def getFileContent(pathAndFileName):
    with open(pathAndFileName, 'r') as theFile:
        # Return a list of lines (strings)
        # data = theFile.read().split('\n')

        # Return as string without line breaks
        # data = theFile.read().replace('\n', '')

        # Return as string
        data = theFile.read()
        return data

def do(url):
    display = Display(visible=0, size=(800, 600))
    display.start()

    browser = webdriver.Firefox(webdriver.FirefoxProfile("/home/andrei/.mozilla/firefox/v6vu77oe.default"))
    browser.get(url)
    print (browser.title)
    browser.get_screenshot_as_file("capture.png")
    browser.quit()

    display.stop()