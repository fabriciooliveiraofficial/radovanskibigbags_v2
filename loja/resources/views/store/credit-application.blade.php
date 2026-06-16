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

    <form action="{{ route('credit-application.store') }}" method="post" 
          class="bg-white border border-gray-200 rounded-2xl p-5 space-y-5"
          x-data="{
              document: '{{ old('document') }}',
              phone: '{{ old('phone') }}',
              cep: '{{ old('cep') }}',
              address: '{{ old('address') }}',
              city: '{{ old('city') }}',
              state: '{{ old('state') }}',
              loadingCep: false,
              cepError: false,

              maskCnpj(v) {
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
              },

              maskPhone(v) {
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
              },

              maskCep(v) {
                  return v.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2').substring(0, 9);
              },

              async lookupCep() {
                  const digits = this.cep.replace(/\D/g, '');
                  if (digits.length !== 8) {
                      this.cepError = false;
                      return;
                  }
                  this.loadingCep = true;
                  this.cepError = false;

                  const applyAddress = (street, neighborhood, city, state) => {
                      this.address = [street, neighborhood].filter(Boolean).join(', ');
                      this.city = city;
                      this.state = state;
                  };

                  try {
                      const res = await fetch('https://viacep.com.br/ws/' + digits + '/json/');
                      if (res.ok) {
                          const data = await res.json();
                          if (!data.erro) {
                              applyAddress(data.logradouro, data.bairro, data.localidade, data.uf);
                              this.loadingCep = false;
                              return;
                          }
                      }
                  } catch (e) {
                      console.warn('ViaCEP client-side failed, trying fallback...');
                  }

                  try {
                      const res = await fetch('/api/cep/' + digits);
                      if (res.ok) {
                          const data = await res.json();
                          applyAddress(data.street, data.neighborhood, data.city, data.state);
                          this.loadingCep = false;
                          return;
                      }
                  } catch (e) {
                      console.error('All CEP services failed.', e);
                  }

                  this.cepError = true;
                  this.loadingCep = false;
              }
          }">
        @csrf

        <div>
            <p class="font-bold text-lg mb-3">Dados da empresa</p>
            <div class="grid sm:grid-cols-2 gap-3">
                <input type="text" name="company_name" placeholder="Razão social *" value="{{ old('company_name') }}" required
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
                <input type="text" name="trade_name" placeholder="Nome fantasia" value="{{ old('trade_name') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="document" placeholder="CNPJ *" x-model="document" @input="document = maskCnpj(document)" required
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
                <input type="tel" name="phone" placeholder="WhatsApp *" x-model="phone" @input="phone = maskPhone(phone)" required
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="email" name="email" placeholder="E-mail" value="{{ old('email') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
            </div>
        </div>

        <div>
            <p class="font-bold text-lg mb-3">Endereço</p>
            <div class="grid sm:grid-cols-4 gap-3">
                <div class="flex flex-col">
                    <input type="text" name="cep" placeholder="CEP" x-model="cep" @input="cep = maskCep(cep); lookupCep()"
                           class="border border-gray-300 rounded-lg px-3 py-2.5">
                    <span x-show="loadingCep" class="text-xs text-gray-500 mt-1">Buscando CEP...</span>
                    <span x-show="cepError" class="text-xs text-red-600 mt-1">CEP não encontrado.</span>
                </div>
                <input type="text" name="address" placeholder="Endereço" x-model="address"
                       class="border border-gray-300 rounded-lg px-3 py-2.5 sm:col-span-2">
                <input type="text" name="city" placeholder="Cidade" x-model="city"
                       class="border border-gray-300 rounded-lg px-3 py-2.5">
                <input type="text" name="state" placeholder="UF" maxlength="2" x-model="state"
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
