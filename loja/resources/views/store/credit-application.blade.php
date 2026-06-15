@extends('layouts.store')

@section('title', 'Ficha cadastral B2B | Radovanski Big Bags Curitiba')
@section('meta_description', 'Cadastre sua empresa para liberar o pagamento via boleto nos pedidos da Radovanski Big Bags.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold mb-1">Ficha cadastral B2B</h1>
    <p class="text-gray-600 mb-6">Preencha os dados da sua empresa para liberar o pagamento via boleto. Nossa equipe avalia o cadastro e retorna pelo WhatsApp.</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4">
            <p class="font-bold mb-1">Corrija os campos abaixo:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('credit-application.store') }}" method="post" class="bg-white border border-gray-200 rounded-2xl p-5 space-y-5">
        @csrf

        <div>
            <p class="font-bold text-lg mb-3">Dados da empresa</p>
            <div class="grid sm:grid-cols-2 gap-3">
                <input type="text" name="company_name" placeholder="Razão social *" value="{{ old('company_name') }}" required
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
                <input type="text" name="trade_name" placeholder="Nome fantasia" value="{{ old('trade_name') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="document" placeholder="CNPJ *" value="{{ old('document') }}" required
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="state_registration" placeholder="Inscrição estadual" value="{{ old('state_registration') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
            </div>
        </div>

        <div>
            <p class="font-bold text-lg mb-3">Contato</p>
            <div class="grid sm:grid-cols-2 gap-3">
                <input type="text" name="contact_name" placeholder="Nome do responsável *" value="{{ old('contact_name') }}" required
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="tel" name="phone" placeholder="WhatsApp *" value="{{ old('phone') }}" required
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="email" name="email" placeholder="E-mail" value="{{ old('email') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
            </div>
        </div>

        <div>
            <p class="font-bold text-lg mb-3">Endereço</p>
            <div class="grid sm:grid-cols-4 gap-3">
                <input type="text" name="cep" placeholder="CEP" value="{{ old('cep') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="address" placeholder="Endereço" value="{{ old('address') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
                <input type="text" name="city" placeholder="Cidade" value="{{ old('city') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="state" placeholder="UF" maxlength="2" value="{{ old('state') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
            </div>
        </div>

        <div>
            <p class="font-bold text-lg mb-3">Observações</p>
            <textarea name="notes" rows="4" placeholder="Volume esperado de compras, referências comerciais, etc. (opcional)"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5">{{ old('notes') }}</textarea>
        </div>

        <button class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl py-3.5 text-lg">
            Enviar ficha cadastral
        </button>
        <p class="text-xs text-gray-500 text-center">Após a análise, sua empresa poderá escolher "Pagar com boleto" ao finalizar pedidos pelo site.</p>
    </form>
</div>
@endsection
