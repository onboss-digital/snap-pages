<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'numeric' => 'The :attribute field must be a number.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'regex' => 'The :attribute field format is invalid.',
    'email' => 'The :attribute field must be a valid email address.',

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
        'cardName' => 'card name',
        'cardNumber' => 'card number',
        'cardExpiry' => 'expiry date',
        'cardCvv' => 'security code (CVV)',
        'email' => 'email',
        'phone' => 'phone',
    ],
];
