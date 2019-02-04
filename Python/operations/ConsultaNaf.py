from selenium import webdriver
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from pyvirtualdisplay import Display
import datetime
import time
import os, sys
import operations.common.funcs as Funcs

def do(url, opId):
    display = Display(visible=0, size=(800, 600))
    display.start()

    opPath = "../debug/ConsultaNaf/"
    logPath = opPath + opId + "/"

    if not os.path.exists(opPath):
        os.mkdir(opPath)

    if not os.path.exists(logPath):
        os.mkdir(logPath)

    browser = webdriver.Firefox(webdriver.FirefoxProfile("/home/andrei/.mozilla/firefox/v6vu77oe.default"), log_path= logPath + "sel.log")
    browser.get(url)

    rtn = "";

    browser.get_screenshot_as_file(logPath + "/screenshot1.png")

    browser.find_element_by_name('txt_SDFTIPO_ayuda').send_keys('IPT')
    browser.find_element_by_name('txt_SDFNUMERO').send_keys('IPF')
    browser.find_element_by_name('txt_SDFAPELL1').send_keys('AP1')
    browser.find_element_by_name('txt_SDFAPELL2').send_keys('AP2')
    browser.find_element_by_name('btn_Sub2207601004').click()
    #wait form submit here
    Funcs.waitFormSubmit(browser, "SDFPROVNAF")

    # Check errors
    if Funcs.checkErrors(browser):
        #return data
        rtn = "ok." + browser.find_element_by_name('SDFPROVNAF').text + browser.find_element_by_name('SDFNUMNAF').text
    else:
        rtn = "err."


    browser.quit()

    display.stop()
    return rtn