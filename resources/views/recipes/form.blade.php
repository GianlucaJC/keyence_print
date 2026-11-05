<x-layouts.app>
    <div class="page-title"><div><h1>{{ $recipe->exists ? 'Modifica ricetta' : 'Nuova ricetta' }}</h1><p class="muted">Il testo finale viene composto concatenando i campi nell'ordine indicato.</p></div><a class="button secondary" href="{{ route('recipes.index') }}">Indietro</a></div>
    <form method="post" action="{{ $recipe->exists ? route('recipes.update', $recipe) : route('recipes.store') }}">@csrf @if($recipe->exists) @method('PUT') @endif
        <section class="card"><div class="grid grid-2"><div class="field"><label for="name">Nome ricetta</label><input id="name" name="name" value="{{ old('name', $recipe->name) }}" required></div><div class="field"><label for="printer_id">Marcatore</label><select id="printer_id" name="printer_id" required><option value="">Seleziona un marcatore</option>@foreach($printers as $printer)<option value="{{ $printer->id }}" @selected((string) old('printer_id', $recipe->printer_id) === (string) $printer->id)>{{ $printer->name }} ({{ $printer->host }})</option>@endforeach</select></div><div class="field"><label for="program_number">Numero programma (FW)</label><input id="program_number" type="number" min="1" max="500" name="program_number" value="{{ old('program_number', $recipe->program_number ?: 1) }}" required></div><div class="field"><label for="message_number">Numero messaggio</label><input id="message_number" type="number" min="1" max="64" name="message_number" value="{{ old('message_number', $recipe->message_number ?: 1) }}" required></div></div><div class="field"><label for="description">Descrizione</label><input id="description" name="description" value="{{ old('description', $recipe->description) }}"></div><label class="checkbox"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recipe->exists ? $recipe->is_active : true))> Ricetta attiva</label></section>
        @if ($recipe->exists)
            <section class="card" style="margin-top:20px"><div class="page-title"><div><h2>Campi della schermata operatore</h2><p class="muted">Trascina un tipo di campo nell'area sottostante oppure riordina quelli esistenti. L'ordine definisce il testo trasmesso.</p></div><button class="button secondary" type="button" id="add-field">+ Campo testo</button></div>
            <div class="field-palette" aria-label="Tipi di campo disponibili"><button class="field-tool" type="button" draggable="true" data-field-type="text">Testo</button><button class="field-tool" type="button" draggable="true" data-field-type="number">Numero</button><button class="field-tool" type="button" draggable="true" data-field-type="date">Data</button></div>
            <div id="field-list" class="drop-zone">
                @foreach(old('fields', $recipe->fields->map(fn ($field) => $field->only(['id','key','label','input_type','default_value','validation_rules','is_required','layout_x','layout_y','layout_width','layout_height','font_size']))->all()) as $index => $field)
                    @include('recipes.field-row', ['index' => $index, 'field' => $field])
                @endforeach
                <p id="drop-hint" class="drop-hint" @style(['display: none' => count(old('fields', $recipe->fields)) > 0])>Trascina qui un tipo di campo per aggiungerlo alla ricetta.</p>
            </div><template id="field-template">@include('recipes.field-row', ['index' => '__INDEX__', 'field' => ['input_type' => '__TYPE__']])</template></section>
            <section class="card layout-editor" aria-labelledby="layout-title"><h2 id="layout-title">Anteprima layout di stampa</h2><p class="layout-note">Trascina i campi nel canvas. Le coordinate e le dimensioni vengono salvate con la ricetta e riprodotte nella schermata operatore.</p><div class="layout-canvas-wrap"><div id="layout-canvas" class="layout-canvas" aria-label="Area di anteprima stampa"></div></div></section>
        @else <section class="card" style="margin-top:20px"><p class="muted">Salva prima la ricetta; nella schermata successiva potrai definire i campi da compilare.</p></section> @endif
        <div class="actions" style="margin-top:20px"><button class="button">Salva ricetta</button>@if($recipe->exists)<a class="button secondary" href="{{ route('operator.show', $recipe) }}">Apri schermata operatore</a>@endif</div>
    </form>
    @if ($recipe->exists)<script>
        const list = document.getElementById('field-list');
        const hint = document.getElementById('drop-hint');
        const canvas = document.getElementById('layout-canvas');
        let draggedRow;
        let paletteType;

        const rows = () => Array.from(list.querySelectorAll('.field-row'));
        const syncRows = () => {
            rows().forEach((row, index) => {
                row.querySelector('.field-position').textContent = `Campo ${index + 1}`;
                row.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(/fields\[\d+\]/, `fields[${index}]`));
            });
            hint.style.display = rows().length ? 'none' : 'block';
            renderLayout();
        };
        const addField = type => {
            const index = rows().length;
            const markup = document.getElementById('field-template').innerHTML
                .replaceAll('__INDEX__', index)
                .replaceAll('__TYPE__', type);
            hint.insertAdjacentHTML('beforebegin', markup);
            const row = hint.previousElementSibling;
            row.querySelector('select').value = type;
            row.querySelector('[name$="[layout_x]"]').value = Math.min(20 + index * 30, 580);
            row.querySelector('[name$="[layout_y]"]').value = Math.min(20 + index * 25, 230);
            syncRows();
        };
        const numericValue = (row, suffix, fallback) => Number(row.querySelector(`[name$="[${suffix}]"]`)?.value || fallback);
        const updateItem = (item, row) => {
            const width = Math.min(800, Math.max(20, numericValue(row, 'layout_width', 220)));
            const height = Math.min(300, Math.max(20, numericValue(row, 'layout_height', 48)));
            const x = Math.min(800 - width, Math.max(0, numericValue(row, 'layout_x', 20)));
            const y = Math.min(300 - height, Math.max(0, numericValue(row, 'layout_y', 20)));
            item.style.left = `${x}px`; item.style.top = `${y}px`; item.style.width = `${width}px`; item.style.height = `${height}px`;
            item.style.fontSize = `${Math.min(72, Math.max(8, numericValue(row, 'font_size', 24)))}px`;
        };
        const renderLayout = () => {
            canvas.innerHTML = '';
            rows().forEach((row, index) => {
                const item = document.createElement('button');
                item.type = 'button'; item.className = 'layout-item';
                item.textContent = row.querySelector('[name$="[default_value]"]').value || row.querySelector('[name$="[label]"]').value || `Campo ${index + 1}`;
                updateItem(item, row);
                item.addEventListener('pointerdown', event => {
                    event.preventDefault();
                    const startX = event.clientX; const startY = event.clientY;
                    const initialX = numericValue(row, 'layout_x', 20); const initialY = numericValue(row, 'layout_y', 20);
                    item.classList.add('selected'); item.setPointerCapture(event.pointerId);
                    item.addEventListener('pointermove', moveEvent => {
                        const width = numericValue(row, 'layout_width', 220); const height = numericValue(row, 'layout_height', 48);
                        row.querySelector('[name$="[layout_x]"]').value = Math.min(800 - width, Math.max(0, Math.round(initialX + moveEvent.clientX - startX)));
                        row.querySelector('[name$="[layout_y]"]').value = Math.min(300 - height, Math.max(0, Math.round(initialY + moveEvent.clientY - startY)));
                        updateItem(item, row);
                    });
                    item.addEventListener('pointerup', () => item.classList.remove('selected'), { once: true });
                });
                canvas.appendChild(item);
            });
        };
        const insertAtPointer = event => {
            const target = rows().find(row => {
                const bounds = row.getBoundingClientRect();
                return event.clientY < bounds.top + bounds.height / 2;
            });
            if (target) list.insertBefore(draggedRow, target); else list.insertBefore(draggedRow, hint);
        };

        document.getElementById('add-field').addEventListener('click', () => addField('text'));
        document.querySelectorAll('.field-tool').forEach(tool => {
            tool.addEventListener('dragstart', () => { paletteType = tool.dataset.fieldType; draggedRow = null; });
            tool.addEventListener('dragend', () => paletteType = null);
        });
        list.addEventListener('dragstart', event => {
            const row = event.target.closest('.field-row');
            if (!row || event.target.matches('input, select, textarea')) return;
            draggedRow = row;
            paletteType = null;
            row.classList.add('is-dragging');
        });
        list.addEventListener('dragover', event => {
            event.preventDefault();
            list.classList.add('is-dropping');
            if (draggedRow) insertAtPointer(event);
        });
        list.addEventListener('drop', event => {
            event.preventDefault();
            if (paletteType) addField(paletteType);
            if (draggedRow) syncRows();
            list.classList.remove('is-dropping');
        });
        list.addEventListener('dragend', () => { if (draggedRow) draggedRow.classList.remove('is-dragging'); draggedRow = null; list.classList.remove('is-dropping'); syncRows(); });
        list.addEventListener('click', event => {
            const row = event.target.closest('.field-row');
            if (event.target.matches('.remove-field')) row.remove();
            if (event.target.matches('.move-up') && row.previousElementSibling?.classList.contains('field-row')) list.insertBefore(row, row.previousElementSibling);
            if (event.target.matches('.move-down') && row.nextElementSibling?.classList.contains('field-row')) list.insertBefore(row.nextElementSibling, row);
            syncRows();
        });
        list.addEventListener('input', event => {
            if (event.target.matches('[name$="[label]"], [name$="[default_value]"], .layout-input')) renderLayout();
        });
        syncRows();
    </script>@endif
</x-layouts.app>
