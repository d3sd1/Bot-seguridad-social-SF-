import re

def checkErrors(browser):
    errBoxes = browser.find_element_by_id("DIL")
    notErrorBoxCodes = {
        "3408": "Operación realizada correctamente (alta)",
        "3083": "INTRODUZCA LOS DATOS Y PULSE CONTINUAR",
        "9125": "ALTA REALIZADA. ASIGNADO CONVENIO DE LA CUENTA",
        "3251": "HAY MAS AFILIADOS A CONSULTAR",
        "3083": "INTRODUZCA LOS DATOS Y PULSE CONTINUAR",
        "3543": "NO EXISTEN DATOS PARA ESTA CONSULTA",
        "4359": "MOVIMIENTO PREVIO ERRONEO - AFILIADO EN ALTA PREVIA"
    }
    isFalseErr = False;
    for code, msg in notErrorBoxCodes:
        if re.search(errBoxes.text, code + "*", re.IGNORECASE):
            isFalseErr = True

    if errBoxes.is_displayed() and False != isFalseErr:
        return  errBoxes.text;
    return "ok"

def waitFormSubmit(browser, el):
    try:
        element = WebDriverWait(browser, 30).until(
            EC.visibility_of_element_located((By.ID, el))
        )
        print(element)
        return True
    except:
        return False