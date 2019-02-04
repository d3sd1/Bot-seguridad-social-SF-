<?php
$handle = popen('python3.6 ./manager.py "ConsultaNaf" "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR67&E=I&AP=AFIR" {ID34}', 'r');
$read = fread($handle, 2096);
echo $read;
pclose($handle);