<?php

/*
| Messaggi di validazione in italiano per le regole usate dall'applicazione.
| Le regole non elencate usano i messaggi inglesi (fallback_locale).
*/

return [
    'after_or_equal' => 'Il campo :attribute deve essere una data uguale o successiva a :date.',
    'array' => 'Il campo :attribute deve essere un elenco.',
    'before_or_equal' => 'Il campo :attribute deve essere una data uguale o precedente a :date.',
    'between' => [
        'array' => 'Il campo :attribute deve avere tra :min e :max elementi.',
        'numeric' => 'Il campo :attribute deve essere compreso tra :min e :max.',
        'string' => 'Il campo :attribute deve avere tra :min e :max caratteri.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'confirmed' => 'La conferma del campo :attribute non corrisponde.',
    'current_password' => 'La password non è corretta.',
    'date' => 'Il campo :attribute non è una data valida.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'exists' => 'Il valore selezionato per :attribute non è valido.',
    'in' => 'Il valore selezionato per :attribute non è valido.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'lowercase' => 'Il campo :attribute deve essere in minuscolo.',
    'max' => [
        'array' => 'Il campo :attribute non può avere più di :max elementi.',
        'numeric' => 'Il campo :attribute non può essere maggiore di :max.',
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],
    'min' => [
        'array' => 'Il campo :attribute deve avere almeno :min elementi.',
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'string' => 'Il campo :attribute deve avere almeno :min caratteri.',
    ],
    'not_in' => 'Il valore selezionato per :attribute non è valido.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'password' => [
        'letters' => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed' => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers' => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols' => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'La password indicata è comparsa in una fuga di dati. Scegline un\'altra.',
    ],
    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => 'Il campo :attribute deve essere un testo.',
    'unique' => 'Il valore del campo :attribute è già in uso.',

    'attributes' => [
        'name' => 'nome',
        'email' => 'email',
        'password' => 'password',
        'current_password' => 'password attuale',
        'categoria' => 'categoria',
        'descrizione' => 'descrizione',
        'descriz' => 'nome categoria',
        'descriz_cat' => 'nome categoria',
        'importo' => 'importo',
        'data' => 'data',
        'entratauscita' => 'tipo',
        'default_entity_id' => 'entità predefinita',
        'target' => 'categoria di destinazione',
        'role' => 'ruolo',
        'entities' => 'entità',
    ],
];
