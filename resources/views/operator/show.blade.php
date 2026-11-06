<x-layouts.app>
    <div class="page-title"><div><h1>{{ $recipe->name }}</h1><p class="muted">{{ $recipe->printer->name }} · {{ $recipe->printer->host }}:{{ $recipe->printer->port }} · Programma {{ $recipe->program_number }}, messaggio {{ $recipe->message_number }}</p></div><a class="button secondary" href="{{ route('dashboard') }}">Torna alla console</a></div>
    <div class="grid operator-layout"><form class="card" method="post" action="{{ route('operator.send', $recipe) }}">@csrf
        <h2>Dati di stampa</h2>
        @forelse($recipe->fields as $field)<div class="field"><label for="field-{{ $field->key }}">{{ $field->label }} @if($field->is_required)* @endif</label><input class="print-value" id="field-{{ $field->key }}" type="{{ $field->input_type }}" name="values[{{ $field->key }}]" value="{{ old("values.{$field->key}", $field->default_value) }}" @required($field->is_required)></div>
        @empty <p class="muted">Questa ricetta non ha campi configurati. Torna nella configurazione ricetta per aggiungerli.</p> @endforelse
        <button class="button" @disabled($recipe->fields->isEmpty())>Trasmetti alla stampante</button>
    </form><section class="card"><h2>Anteprima layout di stampa</h2><div class="layout-canvas-wrap"><div class="layout-canvas" id="print-layout">
        @foreach($recipe->fields as $field)<span class="print-layout-item" data-field-key="{{ $field->key }}" data-message-number="{{ $field->printer_message_number }}" data-printer-font="{{ $field->printer_font }}" data-layout-x="{{ $field->layout_x }}" data-layout-y="{{ $field->layout_y }}" data-fallback="{{ $field->default_value ?: $field->label }}"></span>@endforeach
    </div></div><p class="protocol" id="command-preview">H2,{{ $recipe->program_number }},...</p><p class="muted">L'anteprima rispecchia il layout sincronizzato solo dopo aver inviato la configurazione dalla ricetta. Per una stampa fisicamente identica, verificare il font MK-G e l'orientamento configurati per ciascun messaggio.</p></section></div>
    <script>
        const values = Array.from(document.querySelectorAll('.print-value'));
        const command = document.getElementById('command-preview');
        const printLayout = document.getElementById('print-layout');
        const layoutItems = Array.from(document.querySelectorAll('.print-layout-item'));
        const printerFontPreview = font => ({
            0: { family: 'Arial, sans-serif', size: 5 }, 1: { family: 'Arial, sans-serif', size: 7 },
            3: { family: 'Arial, sans-serif', size: 10 }, 5: { family: 'Arial, sans-serif', size: 16 },
            6: { family: 'Arial, sans-serif', size: 24 }, 7: { family: 'Arial, sans-serif', size: 24 },
            9: { family: 'Arial, sans-serif', size: 32 }, 10: { family: 'Arial, sans-serif', size: 32 },
            11: { family: '"Times New Roman", serif', size: 32 }, 12: { family: 'Arial, sans-serif', size: 16 },
            13: { family: 'Arial, sans-serif', size: 32, vertical: true }, 16: { family: 'Arial, sans-serif', size: 24 },
            17: { family: 'Arial, sans-serif', size: 9 }, 18: { family: 'Arial, sans-serif', size: 12 },
            20: { family: 'Arial, sans-serif', size: 14 }, 22: { family: 'Arial, sans-serif', size: 32, vertical: true },
        }[font] || { family: 'Arial, sans-serif', size: 24 });
        const updatePreview = () => {
            layoutItems.forEach(item => {
                const field = document.getElementById(`field-${item.dataset.fieldKey}`);
                const font = printerFontPreview(Number(item.dataset.printerFont));
                item.textContent = field ? field.value : item.dataset.fallback || '—';
                item.style.left = `${Number(item.dataset.layoutX) * printLayout.clientWidth / 4095}px`;
                item.style.top = `${Number(item.dataset.layoutY) * printLayout.clientHeight / 31}px`;
                item.style.fontFamily = font.family; item.style.fontSize = `${font.size}px`; item.style.fontWeight = '700';
                item.style.writingMode = font.vertical ? 'vertical-rl' : 'horizontal-tb';
            });
            command.textContent = layoutItems.map(item => {
                const field = document.getElementById(`field-${item.dataset.fieldKey}`);
                return `H2,{{ $recipe->program_number }},${item.dataset.messageNumber},${field ? field.value || ' ' : '...'}`;
            }).join('\n');
        };
        values.forEach(field => field.addEventListener('input', updatePreview));
        window.addEventListener('resize', updatePreview);
        updatePreview();
    </script>
</x-layouts.app>
