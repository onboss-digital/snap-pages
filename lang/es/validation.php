<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de caracteres.',
    'max' => [
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'regex' => 'El formato del campo :attribute es inválido.',
    'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'cardName' => 'nombre en la tarjeta',
        'cardNumber' => 'número de tarjeta',
        'cardExpiry' => 'fecha de caducidad',
        'cardCvv' => 'código de seguridad (CVV)',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',
    ],
];
