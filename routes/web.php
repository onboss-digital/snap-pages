<?php

use App\Livewire\PagePay;
use Illuminate\Support\Facades\Route;

Route::get('/', PagePay::class)->name('home');
