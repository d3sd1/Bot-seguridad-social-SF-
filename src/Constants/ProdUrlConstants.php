<?php

namespace App\Constants;


class ProdUrlConstants
{
    /*
     * Alta
     */
    const ALTA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR01&E=I&AP=AFIR";
    /*
     * Baja
     */
    const BAJA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR01&E=I&AP=AFIR";
    /*
     * Anulación alta previa
     */
    const ANULACIONALTAPREVIA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR42&E=I&AP=AFIR";
    /*
     * Anulación alta consolidada
     */
    const ANULACIONALTACONSOLIDADA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR41&E=I&AP=AFIR";
    /*
     * Anulación baja previa
     */
    const ANULACIONBAJAPREVIA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR42&E=I&AP=AFIR";
    /*
     * Anulación baja consolidada
     */
    const ANULACIONBAJACONSOLIDADA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR02&E=I&AP=AFIR";
    /*
     * Cambio de contrato
     */
    const CAMBIOCONTRATOCONSOLIDADO = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR45&E=I&AP=AFIR";
    const CAMBIOCONTRATOPREVIO = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR42&E=I&AP=AFIR";
    /*
     * Consultar IPF por NAF
     */
    const CONSULTAIPF = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR66&E=I&AP=AFIR";
    /*
     * Consultar NAF por IPF
     */
    const CONSULTANAF = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR67&E=I&AP=AFIR";
    /*
     * Consultar afiliados actualmente
     */
    const CONSULTAALTASCCC = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ACR69&E=I&AP=AFIR";
    /*
     * Duplicados de TA
     */
    const CONSULTATA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR65&E=I&AP=AFIR";
    /*
     * Consulta de alta contra la seguridad social
     */
    const CONSULTAALTA = "https://w2.seg-social.es/Xhtml?JacadaApplicationName=SGIRED&TRANSACCION=ATR65&E=I&AP=AFIR";
}