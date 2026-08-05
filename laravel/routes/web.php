<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Landing'))->name('home');
Route::get('terms', fn () => Inertia::render('Terms'))->name('terms');
Route::get('privacy', fn () => Inertia::render('Privacy'))->name('privacy');
