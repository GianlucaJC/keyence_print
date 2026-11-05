<x-layouts.app>
    <div class="page-title"><div><h1>{{ $recipe->name }}</h1><p class="muted">{{ $recipe->printer->name }} · {{ $recipe->printer->host }}:{{ $recipe->printer->port }} · Programma {{ $recipe->program_number }}, messaggio {{ $recipe->message_number }}</p></div><a class="button secondary" href="{{ route('dashboard') }}">Torna alla console</a></div>
    <div class="grid grid-2"><form class="card" method="post" action="{{ route('operator.send', $recipe) }}">@csrf
        <h2>Dati di stampa</h2>
        @forelse($recipe->fields as $field)<div class="field"><label for="field-{{ $field->key }}">{{ $field->label }} @if($field->is_required)* @endif</label><input class="print-value" id="field-{{ $field->key }}" type="{{ $field->input_type }}" name="values[{{ $field->key }}]" value="{{ old("values.{$field->key}", $field->default_value) }}" @required($field->is_required)></div>
        @empty <p class="muted">Questa ricetta non ha campi configurati. Torna nella configurazione ricetta per aggiungerli.</p> @endforelse
        <button class="button" @disabled($recipe->fields->isEmpty())>Trasmetti alla stampante</button>
    </form><section class="card"><h2>Anteprima layout di stampa</h2><div class="layout-canvas-wrap"><div class="layout-canvas" id="print-layout">
        @foreach($recipe->fields as $field)<span class="print-layout-item" data-field-key="{{ $field->key }}" data-fallback="{{ $field->default_value ?: $field->label }}" style="left:{{ $field->layout_x }}px;top:{{ $field->layout_y }}px;width:{{ $field->layout_width }}px;height:{{ $field->layout_height }}px;font-size:{{ $field->font_size }}px"></span>@endforeach
    </div></div><p class="protocol" id="command-preview">H2,{{ $recipe->program_number }},{{ $recipe->message_number }},...</p><p class="muted">L'anteprima riproduce il layout salvato nella ricetta. Per una stampa fisicamente identica, font, posizioni, loghi e messaggi del programma MK-G devono coincidere con questa configurazione.</p></section></div>
    <script>
        const values = Array.from(document.querySelectorAll('.print-value'));
        const command = document.getElementById('command-preview');
        const layoutItems = Array.from(document.querySelectorAll('.print-layout-item'));
        const updatePreview = () => {
            const text = values.map(field => field.value).join('');
            layoutItems.forEach(item => {
                const field = document.getElementById(`field-${item.dataset.fieldKey}`);
                item.textContent = field?.value || item.dataset.fallback || '—';
            });
            command.textContent = 'H2,{{ $recipe->program_number }},{{ $recipe->message_number }},' + (text || '...');
        };
        values.forEach(field => field.addEventListener('input', updatePreview)); updatePreview();
    </script>
</x-layouts.app>
