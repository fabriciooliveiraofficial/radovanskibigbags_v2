<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CreditApplicationController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\QuotePublicController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CepController;
use Illuminate\Support\Facades\Route;

// Busca de CEP com fallback
Route::get('/api/cep/{cep}', [CepController::class, 'lookup'])->name('api.cep.lookup');

// Loja
Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/produtos', [StoreController::class, 'products'])->name('products.index');
Route::get('/produto/{product:slug}', [StoreController::class, 'product'])->name('products.show');
Route::get('/assistente-de-medidas', [StoreController::class, 'wizard'])->name('wizard');
Route::get('/retirada', [StoreController::class, 'pickup'])->name('pickup');
Route::get('/perguntas-frequentes', [StoreController::class, 'faq'])->name('faq');

// Landing pages de SEO local
Route::get('/big-bags-para-{useCase:slug}', [StoreController::class, 'useCase'])
    ->where('useCase', '[a-z0-9\-]+')
    ->name('use-case');
Route::get('/{category:slug}-curitiba', [StoreController::class, 'category'])
    ->where('category', '[a-z0-9\-]+')
    ->name('category');

// Carrinho-cotação
Route::get('/cotacao', [CartController::class, 'index'])->name('cart.index');
Route::post('/cotacao/adicionar', [CartController::class, 'add'])->name('cart.add');
Route::post('/cotacao/atualizar', [CartController::class, 'update'])->name('cart.update');
Route::post('/cotacao/remover', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cotacao/whatsapp', [CartController::class, 'whatsapp'])->name('cart.whatsapp');
Route::post('/cotacao/frete', [CartController::class, 'freight'])->name('cart.freight');
Route::post('/cotacao/boleto/verificar', [CartController::class, 'checkBoleto'])->name('cart.boleto.check');

// Ficha cadastral B2B
Route::get('/ficha-cadastral', [CreditApplicationController::class, 'create'])->name('credit-application.create');
Route::post('/ficha-cadastral', [CreditApplicationController::class, 'store'])->name('credit-application.store');

// Orçamento público (link compartilhado)
Route::get('/orcamento/{token}', [QuotePublicController::class, 'show'])->name('quote.public');
Route::get('/orcamento/{token}/pdf', [QuotePublicController::class, 'pdf'])->name('quote.pdf');
Route::post('/orcamento/{token}/aprovar', [QuotePublicController::class, 'approve'])->name('quote.approve');
Route::get('/orcamento/{token}/repetir', [QuotePublicController::class, 'repeat'])->name('quote.repeat');

// Pixel de rastreio de e-mail
Route::get('/e/{token}', [EmailTrackingController::class, 'pixel'])->name('email.pixel');

// SEO
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
