<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser uma string.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',

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
        'cardName' => 'nome no cartão',
        'cardNumber' => 'número do cartão',
        'cardExpiry' => 'data de validade',
        'cardCvv' => 'código de segurança (CVV)',
        'email' => 'e-mail',
        'phone' => 'telefone',
        'cpf' => 'CPF',
    ],
];
