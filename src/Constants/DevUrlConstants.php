<?php

namespace App\Constants;


class DevUrlConstants
{
    /*
     * Alta
     */
    const ALTA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR01&E=Y&AP=AFIRP";
    /*
     * Baja
     */
    const BAJA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR01&E=Y&AP=AFIRP";
    /*
     * Anulación alta previa
     */
    const ANULACIONALTAPREVIA = "pplicationName=SGIRED&TRANSACCION=ATR42&E=Y&AP=AFIRP";
    /*
     * Anulación alta consolidada
     */
    const ANULACIONALTACONSOLIDADA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR41&E=Y&AP=AFIRP";
    /*
     * Anulación baja previa
     */
    const ANULACIONBAJAPREVIA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR42&E=Y&AP=AFIRP";
    /*
     * Anulación baja consolidada
     */
    const ANULACIONBAJACONSOLIDADA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR02&E=Y&AP=AFIRP";
    /*
     * Cambio de contrato
     */
    const CAMBIOCONTRATOCONSOLIDADO = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR45&E=Y&AP=AFIRP";
    const CAMBIOCONTRATOPREVIO = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR42&E=Y&AP=AFIRP";
    /*
     * Consultar IPF por NAF
     */
    const CONSULTAIPF = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR66&E=Y&AP=AFIRP";
    /*
     * Consultar NAF por IPF
     */
    const CONSULTANAF = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR67&E=Y&AP=AFIRP";
    /*
     * Consultar afiliados actualmente
     */
    const CONSULTAALTASCCC = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR62&E=Y&AP=AFIRP";
    /*
     * Duplicados de TA
     */
    const CONSULTATA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR65&E=Y&AP=AFIRP";
}