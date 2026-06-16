@extends('layouts.store')

@section('title', 'Meu pedido | Radovanski Big Bags Curitiba')
@section('meta_description', 'Revise sua lista de big bags e sacos de ráfia e finalize seu pedido pelo WhatsApp.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold mb-1">Meu pedido</h1>
    <p class="text-gray-600 mb-6">Revise os itens e finalize pelo WhatsApp — confirmamos seu pedido na hora.</p>

    @if($items->isEmpty())
        <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
            <p class="font-bold text-lg mb-2">Sua lista está vazia</p>
            <a href="{{ route('products.index') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg px-6 py-3">
                Ver produtos
            </a>
        </div>
    @else
        @php($total = 0)
        @php($hasConsulta = false)
        <div class="space-y-3">
            @foreach($items as $item)
                @php($cover = $item['product']->coverImage())
                @php($lineTotal = $item['unit_price'] !== null ? $item['unit_price'] * $item['qty'] : null)
                @php($total += $lineTotal ?? 0)
                @php($hasConsulta = $hasConsulta || $lineTotal === null)
                <div class="flex gap-3 border border-gray-200 rounded-xl p-3 bg-white items-center">
                    @if($cover)
                        <img src="{{ asset('storage/' . $cover->path) }}" alt="{{ $item['product']->name }}" class="w-16 h-16 object-cover rounded-lg bg-gray-100">
                    @endif
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('products.show', $item['product']) }}" class="font-bold leading-snug hover:text-brand-700">{{ $item['product']->name }}</a>
                        @if($item['variant'])
                            <p class="text-sm text-gray-500">{{ $item['variant']->name }}</p>
                        @endif
                        <p class="text-sm font-semibold text-brand-700 mt-0.5">
                            {{ $item['unit_price'] !== null ? format_brl($item['unit_price']) . '/un' : 'Sob consulta' }}
                        </p>
                    </div>
                    <form action="{{ route('cart.update') }}" method="post" class="flex items-center gap-1">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                        <input type="hidden" name="variant_id" value="{{ $item['variant']?->id }}">
                        <input type="number" name="qty" value="{{ $item['qty'] }}" min="0"
                               class="w-16 border border-gray-300 rounded-lg px-2 py-1.5 text-center font-bold text-sm">
                        <button class="text-xs font-bold text-brand-700 hover:underline px-1">OK</button>
                    </form>
                    <form action="{{ route('cart.remove') }}" method="post">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                        <input type="hidden" name="variant_id" value="{{ $item['variant']?->id }}">
                        <button class="text-gray-400 hover:text-red-600 p-1" aria-label="Remover">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t-2 border-gray-100 pt-4 flex justify-between items-baseline">
            <span class="font-bold">Estimativa{{ $hasConsulta ? ' (itens sob consulta não inclusos)' : '' }}:</span>
            <span class="text-2xl font-extrabold text-brand-700">{{ format_brl($total) }}</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">O valor final (com frete/retirada e condições) é confirmado no orçamento enviado pela nossa equipe.</p>

        {{-- Frete / retirada + dados de contato --}}
        <div x-data="{
                cep: @js($freightCep ?? ''),
                city: @js(old('city', '')),
                address: null,
                addressError: false,
                loadingAddress: false,
                async lookupCep(autoSubmit) {
                    const digits = this.cep.replace(/\D/g, '');
                    if (digits.length !== 8) {
                        this.address = null;
                        this.addressError = false;
                        return;
                    }
                    this.loadingAddress = true;
                    this.addressError = false;

                    const applyAddress = (street, neighborhood, city, state) => {
                        this.address = {
                            logradouro: street,
                            bairro: neighborhood,
                            localidade: city,
                            uf: state
                        };
                        if (!this.city) {
                            this.city = city + ' - ' + state;
                        }
                        if (autoSubmit) {
                            this.$refs.freightForm.requestSubmit();
                        }
                    };

                    try {
                        const res = await fetch('https://viacep.com.br/ws/' + digits + '/json/');
                        if (res.ok) {
                            const data = await res.json();
                            if (!data.erro) {
                                applyAddress(data.logradouro, data.bairro, data.localidade, data.uf);
                                this.loadingAddress = false;
                                return;
                            }
                        }
                    } catch (e) {
                        console.warn('ViaCEP client-side failed, attempting fallback API...');
                    }

                    try {
                        const res = await fetch('/api/cep/' + digits);
                        if (res.ok) {
                            const data = await res.json();
                            applyAddress(data.street, data.neighborhood, data.city, data.state);
                            this.loadingAddress = false;
                            return;
                        }
                    } catch (e) {
                        console.error('All CEP services failed.', e);
                    }

                    this.address = null;
                    this.addressError = true;
                    this.loadingAddress = false;
                },
             }"
             x-init="lookupCep(false)">

            <div class="mt-6 border border-gray-200 rounded-2xl p-5">
                <p class="font-bold text-lg mb-1">🚚 Entrega ou retirada</p>
                <p class="text-sm text-gray-600 mb-3">Informe seu CEP para preenchermos seu endereço e estimar a entrega — ou retire grátis no nosso depósito em Curitiba.</p>
                <form x-ref="freightForm" action="{{ route('cart.freight') }}" method="post" class="flex gap-2 max-w-xs">
                    @csrf
                    <input type="text" name="cep" x-model="cep" 
                           @input="cep = cep.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2').substring(0, 9); lookupCep(true)"
                           placeholder="Seu CEP" inputmode="numeric" maxlength="9"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5">
                    <button class="bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg px-4">Calcular</button>
                </form>
                @error('cep')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror

                <p x-show="loadingAddress" class="text-xs text-gray-500 mt-2">Buscando endereço...</p>
                <p x-show="address" class="text-sm text-gray-600 mt-2">
                    <span x-text="address ? [address.logradouro, address.bairro].filter(Boolean).join(', ') : ''"></span>
                    <span x-show="address && address.localidade" x-text="address ? '— ' + address.localidade + '/' + address.uf : ''"></span>
                </p>
                <p x-show="addressError" class="text-sm text-red-600 mt-2">CEP não encontrado. Verifique e tente novamente.</p>

                @if(!empty($freight['options']))
                    <div class="mt-4 space-y-2">
                        @foreach($freight['options'] as $option)
                            <div class="flex justify-between items-center border border-gray-100 rounded-lg px-4 py-2.5 text-sm {{ $option['method'] === 'retirada' ? 'bg-brand-50 border-brand-200' : '' }}">
                                <div>
                                    <p class="font-semibold">{{ $option['label'] }}</p>
                                    @if($option['deadline'])
                                        <p class="text-xs text-gray-500">{{ $option['deadline'] }}</p>
                                    @endif
                                </div>
                                <span class="font-bold {{ ($option['cost'] ?? null) === 0.0 ? 'text-brand-700' : '' }}">
                                    {{ $option['cost'] === null ? 'Sob consulta' : ($option['cost'] == 0 ? 'Grátis' : format_brl($option['cost'])) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Estimativa. O valor final é confirmado no orçamento.</p>
                @endif
            </div>

            <form action="{{ route('cart.whatsapp') }}" method="post" class="mt-6 bg-brand-50 border border-brand-200 rounded-2xl p-5 space-y-3" x-data="{ paymentMethod: 'whatsapp' }">
                @csrf
                <p class="font-bold text-lg">Finalizar pedido</p>
                <div class="grid sm:grid-cols-3 gap-3">
                    <input type="text" name="name" placeholder="Seu nome (opcional)" value="{{ old('name') }}"
                           class="border border-gray-300 rounded-lg px-3 py-2.5">
                    <input type="tel" name="phone" placeholder="Seu WhatsApp (opcional)" value="{{ old('phone') }}"
                           x-data="{ 
                               val: '{{ old('phone') }}',
                               mask(v) {
                                   let r = v.replace(/\D/g, '').substring(0, 11);
                                   if (r.length > 10) {
                                       return r.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                                   } else if (r.length > 5) {
                                       return r.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
                                   } else if (r.length > 2) {
                                       return r.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
                                   } else if (r.length > 0) {
                                       return r.replace(/^(\d*)$/, '($1');
                                   }
                                   return r;
                               }
                           }" 
                           x-model="val" 
                           @input="val = mask(val)"
                           class="border border-gray-300 rounded-lg px-3 py-2.5">
                    <input type="text" name="city" placeholder="Sua cidade (opcional)" x-model="city"
                           class="border border-gray-300 rounded-lg px-3 py-2.5">
                </div>

                @if($creditApplication)
                    <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                        <p class="font-semibold text-sm">Forma de pagamento</p>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="payment_method" value="whatsapp" x-model="paymentMethod" checked>
                            Combinar pelo WhatsApp
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="payment_method" value="boleto" x-model="paymentMethod">
                            Pagar com boleto ({{ $creditApplication->company_name }})
                        </label>
                        <p x-show="paymentMethod === 'boleto'" class="text-xs text-gray-600">
                            O prazo (30/45/60 dias) será confirmado pela nossa equipe após análise do pedido.
                        </p>
                    </div>
                @endif

                <button class="w-full inline-flex items-center justify-center gap-2 bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-xl py-3.5 text-lg">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.2-1c-.3-.1-.5-.1-.7.1l-1 1.2c-.2.2-.4.2-.6.1a8.1 8.1 0 01-3.8-3.7c-.1-.3-.1-.5.1-.6l1.1-1c.2-.3.3-.5.1-.8l-1-2.1c-.1-.4-.4-.5-.7-.5h-.8c-.3 0-.8.3-1 .5-1.7 1.7-1.3 3.8.2 6 1.7 2.6 4 4.5 6.9 5.2 1.6.4 3 .1 3.9-1.2.2-.2.3-.6.3-.9v-.7c0-.3-.3-.5-.8-.6z"/><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.5-4.4-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                    Fazer Pedido pelo WhatsApp
                </button>
                <p class="text-xs text-gray-500 text-center">Abre o WhatsApp com sua lista pronta. Sem cadastro, sem pagamento online.</p>
            </form>

            {{-- Pagamento para empresas (B2B) --}}
            <div class="mt-6 border border-gray-200 rounded-2xl p-5">
                <p class="font-bold text-lg mb-1">🏢 Pagamento para empresas (B2B)</p>

                @if(session('boleto_not_found'))
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-3 text-sm">
                        CNPJ não encontrado ou ainda não aprovado para boleto.
                        <a href="{{ route('credit-application.create') }}" class="font-bold underline">Preencha a ficha cadastral</a> para liberar essa opção.
                    </div>
                @endif

                @if($creditApplication)
                    <div class="bg-brand-50 border border-brand-200 text-brand-800 rounded-lg px-4 py-3 text-sm font-semibold">
                        ✅ {{ $creditApplication->company_name }} aprovada para pagamento com boleto. Escolha essa opção em "Finalizar pedido".
                    </div>
                @else
                    <p class="text-sm text-gray-600 mb-3">Sua empresa tem ficha cadastral aprovada? Informe o CNPJ para liberar o pagamento com boleto.</p>
                    <form action="{{ route('cart.boleto.check') }}" method="post" class="flex gap-2 max-w-sm"
                          x-data="{
                              val: '',
                              mask(v) {
                                  let r = v.replace(/\D/g, '').substring(0, 14);
                                  if (r.length > 12) {
                                      return r.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                                  } else if (r.length > 8) {
                                      return r.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})$/, '$1.$2.$3/$4');
                                  } else if (r.length > 5) {
                                      return r.replace(/^(\d{2})(\d{3})(\d{0,3})$/, '$1.$2.$3');
                                  } else if (r.length > 2) {
                                      return r.replace(/^(\d{2})(\d{0,3})$/, '$1.$2');
                                  }
                                  return r;
                              }
                          }">
                        @csrf
                        <input type="text" name="cnpj" placeholder="CNPJ da empresa" inputmode="numeric"
                               x-model="val" @input="val = mask(val)"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5">
                        <button class="bg-ink hover:bg-black text-white font-bold rounded-lg px-4">Verificar</button>
                    </form>
                    @error('cnpj')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-2">Ainda não tem cadastro? <a href="{{ route('credit-application.create') }}" class="text-brand-700 font-semibold hover:underline">Preencha a ficha cadastral</a>.</p>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
