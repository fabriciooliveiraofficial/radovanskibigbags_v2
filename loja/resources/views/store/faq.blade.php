@extends('layouts.store')

@section('title', 'Perguntas frequentes sobre big bags | Radovanski Big Bags Curitiba')
@section('meta_description', 'Tire suas dúvidas sobre big bags: medidas, capacidades, diferença entre novo, lavado e usado, retirada em Curitiba e formas de pagamento.')

@push('schema')
@if($faqs->isNotEmpty())
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        @foreach($faqs as $faq)
        {
            "@type": "Question",
            "name": @json($faq->question),
            "acceptedAnswer": { "@type": "Answer", "text": @json($faq->answer) }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endif
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold mb-6">Perguntas frequentes</h1>

    @if($faqs->isEmpty())
        <p class="text-gray-600">Em breve publicaremos as dúvidas mais comuns. Enquanto isso, fale com a gente no WhatsApp.</p>
    @else
        <div class="space-y-2">
            @foreach($faqs as $faq)
                <details class="border border-gray-200 rounded-xl px-5 py-4 bg-white">
                    <summary class="font-bold cursor-pointer">{{ $faq->question }}</summary>
                    <p class="text-gray-600 mt-2 whitespace-pre-line">{{ $faq->answer }}</p>
                </details>
            @endforeach
        </div>
    @endif

    <div class="mt-8 text-center border-t border-gray-100 pt-6">
        <p class="font-bold mb-2">Não achou sua resposta?</p>
        <a href="{{ store_whatsapp_link('Olá! Tenho uma dúvida: ') }}" target="_blank" rel="noopener"
           class="inline-block bg-whatsapp hover:bg-whatsapp-dark text-white font-bold rounded-lg px-6 py-3">
            Perguntar no WhatsApp
        </a>
    </div>
</div>
@endsection
