<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', store_setting('seo_home_title', 'Big Bags em Curitiba — Novos, Lavados e Usados | Radovanski Big Bags'))</title>
    <meta name="description" content="@yield('meta_description', store_setting('seo_home_description', 'Big bags novos, lavados e usados e sacos de ráfia em Curitiba. Atendimento direto pelo WhatsApp, retirada no local e entrega na região.'))">
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    {{-- Open Graph (link bonito no WhatsApp) --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', store_setting('seo_home_title', 'Big Bags em Curitiba | Radovanski Big Bags'))">
    <meta property="og:description" content="@yield('meta_description', store_setting('seo_home_description', 'Big bags novos, lavados e usados em Curitiba.'))">
    <meta property="og:image" content="@yield('og_image', asset('images/logo.svg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="pt_BR">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    {{-- schema.org LocalBusiness (SEO local Curitiba) --}}
    @php($storePhoneE164 = '+55' . preg_replace('/\D/', '', (string) store_setting('store_whatsapp', '')))
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": @json(store_setting('store_name', 'Radovanski Big Bags')),
        "image": @json(asset('images/logo.svg')),
        "url": @json(url('/')),
        "telephone": @json($storePhoneE164),
        "address": {
            "@type": "PostalAddress",
            "streetAddress": @json(store_setting('store_address', '')),
            "addressLocality": "Curitiba",
            "addressRegion": "PR",
            "postalCode": @json(store_setting('store_cep', '')),
            "addressCountry": "BR"
        },
        "areaServed": ["Curitiba", "Região Metropolitana de Curitiba"],
        "openingHours": @json(store_setting('store_hours', 'Seg a Sex 8h às 18h'))
    }
    </script>

    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-ink antialiased">

{{-- Topo: informação de retirada --}}
<div class="bg-brand-800 text-white text-center text-sm py-1.5 px-4">
    Retire em Curitiba e pague na hora · Atendimento direto pelo WhatsApp
</div>

<header class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center gap-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ store_setting('store_logo') ? asset('storage/' . store_setting('store_logo')) : asset('images/logo.svg') }}"
                     alt="{{ store_setting('store_name', 'Radovanski Big Bags') }}" class="h-11 w-11">
                <span class="font-extrabold text-lg leading-tight text-brand-700">
                    Radovanski<br class="sm:hidden"> <span class="text-ink">Big Bags</span>
                </span>
            </a>

            <form action="{{ route('products.index') }}" method="get" class="flex-1 hidden md:flex">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Buscar big bag, saco de ráfia, medida..."
                       class="w-full border border-gray-300 rounded-l-lg px-4 py-2.5 focus:outline-none focus:border-brand-600">
                <button class="bg-brand-600 hover:bg-brand-700 text-white px-5 rounded-r-lg font-semibold" aria-label="Buscar">
                    Buscar
                </button>
            </form>

            <div class="flex items-center gap-3 ml-auto">
                <a href="{{ route('cart.index') }}"
                   class="relative inline-flex items-center gap-2 border-2 border-brand-600 text-brand-700 font-semibold rounded-lg px-4 py-2 hover:bg-brand-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 100 4 2 2 0 000-4zm8 0a2 2 0 100 4 2 2 0 000-4zM3 3h2l2.6 10.4A2 2 0 009.5 15h7.9a2 2 0 001.9-1.4L21 7H6"/></svg>
                    <span class="hidden sm:inline">Meu pedido</span>
                    @php($cartCount = app(\App\Services\Cart::class)->count())
                    @if($cartCount > 0)
                        <span class="absolute -top-2 -right-2 bg-accent-500 text-white text-xs font-bold rounded-full h-5 min-w-5 px-1 flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                </a>
                <a href="{{ store_whatsapp_link('Olá! Vim pelo site e quero fazer um pedido.') }}" target="_blank" rel="noopener"
                   class="hidden sm:inline-flex items-center gap-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-semibold rounded-lg px-4 py-2.5">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>

        {{-- Busca mobile --}}
        <form action="{{ route('products.index') }}" method="get" class="md:hidden pb-3">
            <div class="flex">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar produto ou medida..."
                       class="w-full border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:border-brand-600">
                <button class="bg-brand-600 text-white px-4 rounded-r-lg font-semibold">OK</button>
            </div>
        </form>

        <nav class="flex gap-1 overflow-x-auto pb-2 -mx-1 text-sm font-semibold whitespace-nowrap">
            <a href="{{ route('products.index') }}" class="px-3 py-1.5 rounded-full {{ request()->routeIs('products.index') ? 'bg-brand-600 text-white' : 'text-ink hover:bg-brand-50' }}">Todos os produtos</a>
            <a href="{{ route('products.index', ['condicao' => ['novo']]) }}" class="px-3 py-1.5 rounded-full text-ink hover:bg-brand-50">Novos</a>
            <a href="{{ route('products.index', ['condicao' => ['lavado']]) }}" class="px-3 py-1.5 rounded-full text-ink hover:bg-brand-50">Lavados</a>
            <a href="{{ route('products.index', ['condicao' => ['sujo', 'usado']]) }}" class="px-3 py-1.5 rounded-full text-ink hover:bg-brand-50">Usados</a>
            <a href="{{ route('wizard') }}" class="px-3 py-1.5 rounded-full text-accent-600 hover:bg-amber-50">🎯 Não sei a medida</a>
            <a href="{{ route('pickup') }}" class="px-3 py-1.5 rounded-full text-ink hover:bg-brand-50">Retirada</a>
            <a href="{{ route('faq') }}" class="px-3 py-1.5 rounded-full text-ink hover:bg-brand-50">Dúvidas</a>
        </nav>
    </div>
</header>

@if(session('status'))
    <div class="max-w-6xl mx-auto px-4 mt-4">
        <div class="bg-brand-50 border border-brand-200 text-brand-800 rounded-lg px-4 py-3 font-medium">
            {{ session('status') }}
        </div>
    </div>
@endif

<main class="min-h-[60vh]">
    @yield('content')
</main>

<footer class="bg-ink text-gray-300 mt-16">
    <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="text-white font-extrabold text-lg mb-2">{{ store_setting('store_name', 'Radovanski Big Bags') }}</p>
            <p class="text-sm leading-relaxed">Big bags novos, lavados e usados e sacos de ráfia para indústria, agronegócio, reciclagem e logística em Curitiba e região.</p>
            @if(store_setting('store_cnpj'))
                <p class="text-xs mt-2 text-gray-400">CNPJ: {{ store_setting('store_cnpj') }}</p>
            @endif
        </div>
        <div>
            <p class="text-white font-bold mb-2">Atendimento</p>
            <ul class="space-y-1 text-sm">
                <li><a class="hover:text-white" href="{{ store_whatsapp_link() }}" target="_blank" rel="noopener">WhatsApp: {{ store_setting('store_whatsapp', '(41) 9 9999-9999') }}</a></li>
                @if(store_setting('store_email'))<li>{{ store_setting('store_email') }}</li>@endif
                <li>{{ store_setting('store_hours', 'Seg a Sex 8h às 18h') }}</li>
            </ul>
        </div>
        <div>
            <p class="text-white font-bold mb-2">Retirada em Curitiba</p>
            <p class="text-sm leading-relaxed">{{ store_setting('store_address', 'Endereço a configurar no painel') }}<br>{{ store_setting('store_city', 'Curitiba - PR') }}</p>
            <a href="{{ route('pickup') }}" class="text-brand-300 hover:text-white text-sm font-semibold">Como retirar →</a>
        </div>
        <div>
            <p class="text-white font-bold mb-2">Navegação</p>
            <ul class="space-y-1 text-sm">
                <li><a class="hover:text-white" href="{{ route('products.index') }}">Todos os produtos</a></li>
                <li><a class="hover:text-white" href="{{ route('wizard') }}">Assistente de medidas</a></li>
                <li><a class="hover:text-white" href="{{ route('faq') }}">Perguntas frequentes</a></li>
                <li><a class="hover:text-white" href="{{ route('pickup') }}">Retirada e pagamento</a></li>
                <li><a class="hover:text-white" href="{{ route('credit-application.create') }}">Ficha cadastral B2B (boleto)</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-gray-700 py-4 text-center text-xs text-gray-400">
        © {{ date('Y') }} {{ store_setting('store_name', 'Radovanski Big Bags') }} — Curitiba/PR
    </div>
</footer>

{{-- WhatsApp flutuante --}}
<a href="{{ store_whatsapp_link('Olá! Vim pelo site e preciso de ajuda.') }}" target="_blank" rel="noopener"
   class="fixed bottom-5 right-5 z-50 bg-whatsapp hover:bg-whatsapp-dark text-white rounded-full shadow-lg p-4 flex items-center justify-center"
   aria-label="Falar no WhatsApp">
    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
</a>

</body>
</html>
