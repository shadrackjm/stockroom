<?php

use App\Http\Controllers\ProductExportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Fixed URLs first, so "create" or "trash" is never mistaken for a product slug.
    Route::livewire('products', 'pages::products.index')->name('products.index');
    Route::livewire('products/create', 'pages::products.create')->name('products.create');
    Route::livewire('products/trash', 'pages::products.trash')->name('products.trash');

    Route::get('products/export', ProductExportController::class)->name('products.export'); 
    
    Route::livewire('products/{product}', 'pages::products.show')->name('products.show');
    Route::livewire('products/{product}/edit', 'pages::products.edit')->name('products.edit');

    
});

require __DIR__.'/settings.php';
