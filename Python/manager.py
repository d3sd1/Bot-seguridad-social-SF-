import sys
import time
# Import operations
import operations.Alta
import operations.ConsultaNaf
import os

try:
    operation=sys.argv[1]
    url=sys.argv[2]
    opId=sys.argv[3]
    op = ""
    if operation == "Alta":
        op = operations.Alta
    elif operation == "Baja":
        op = operations.Baja
    elif operation == "ConsultaNaf":
        op = operations.ConsultaNaf

    print(op.do(url, opId))


except Exception as e:
  print("An exception occurred " + str(e))