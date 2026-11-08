<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | Immagine mostrata accanto al nome dell'applicazione nella barra di
    | navigazione e nella pagina di accesso (URL assoluto o percorso dentro
    | public/, ad es. "/images/logo.svg"). Se vuoto si usa il logo di
    | LaraCashbook; "none" mostra solo il nome.
    |
    */

    'logo_url' => match ($logo = env('CASHBOOK_LOGO_URL')) {
        null, '' => '/images/laracashbook-logo.png',
        'none' => null,
        default => $logo,
    },

    'logo_alt' => env('CASHBOOK_LOGO_ALT', ''),

];
