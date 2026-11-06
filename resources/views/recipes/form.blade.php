<x-layouts.app>
    <div class="page-title"><div><h1>{{ $recipe->exists ? 'Modifica ricetta' : 'Nuova ricetta' }}</h1><p class="muted">Il testo finale viene composto concatenando i campi nell'ordine indicato.</p></div><a class="button secondary" href="{{ route('recipes.index') }}">Indietro</a></div>
    <form method="post" action="{{ $recipe->exists ? route('recipes.update', $recipe) : route('recipes.store') }}">@csrf @if($recipe->exists) @method('PUT') @endif
        <section class="card"><div class="grid grid-2"><div class="field"><label for="name">Nome ricetta</label><input id="name" name="name" value="{{ old('name', $recipe->name) }}" required></div><div class="field"><label for="printer_id">Marcatore</label><select id="printer_id" name="printer_id" required><option value="">Seleziona un marcatore</option>@foreach($printers as $printer)<option value="{{ $printer->id }}" @selected((string) old('printer_id', $recipe->printer_id) === (string) $printer->id)>{{ $printer->name }} ({{ $printer->host }})</option>@endforeach</select></div><div class="field"><label for="program_number">Numero programma (FW)</label><input id="program_number" type="number" min="1" max="500" name="program_number" value="{{ old('program_number', $recipe->program_number ?: 1) }}" required></div><div class="field"><label for="message_number">Primo messaggio MK-G della ricetta</label><input id="message_number" type="number" min="1" max="64" name="message_number" value="{{ old('message_number', $recipe->message_number ?: 1) }}" required></div></div><div class="field"><label for="description">Descrizione</label><input id="description" name="description" value="{{ old('description', $recipe->description) }}"></div><label class="checkbox"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recipe->exists ? $recipe->is_active : true))> Ricetta attiva</label></section>
        @if ($recipe->exists)
            <section class="card" style="margin-top:20px"><div class="page-title"><div><h2>Campi della schermata operatore</h2><p class="muted">Clicca un tipo di campo per aggiungerlo oppure riordina quelli esistenti. L'ordine definisce il testo trasmesso e assegna automaticamente i messaggi MK-G.</p></div><button class="button secondary" type="button" id="add-field">+ Campo testo</button></div>
            <div class="field-palette" aria-label="Tipi di campo disponibili"><button class="field-tool" type="button" data-field-type="text">Testo</button><button class="field-tool" type="button" data-field-type="number">Numero</button><button class="field-tool" type="button" data-field-type="date">Data</button></div>
            <div id="field-list" class="field-list">
                @foreach(old('fields', $recipe->fields->map(fn ($field) => $field->only(['id','label','input_type','default_value','validation_rules','is_required','printer_font','layout_x','layout_y']))->all()) as $index => $field)
                    @include('recipes.field-row', ['index' => $index, 'field' => $field, 'messageStart' => old('message_number', $recipe->message_number)])
                @endforeach
                <p id="drop-hint" class="drop-hint" @style(['display: none' => count(old('fields', $recipe->fields)) > 0])>Clicca un tipo di campo qui sopra per aggiungerlo alla ricetta.</p>
            </div><template id="field-template">@include('recipes.field-row', ['index' => '__INDEX__', 'field' => ['input_type' => '__TYPE__'], 'messageStart' => old('message_number', $recipe->message_number)])</template></section>
            <section class="card layout-editor" aria-labelledby="layout-title"><h2 id="layout-title">Anteprima layout di stampa</h2><p class="layout-note">Il canvas usa le coordinate native MK-G: Base X 0–4095 e Base Y 0–31. La scala verticale è ingrandita solo per rendere modificabile il layout; le coordinate inviate alla stampante non vengono convertite.</p><div class="layout-canvas-wrap"><div id="layout-canvas" class="layout-canvas" aria-label="Area di anteprima stampa"></div></div></section>
        @else <section class="card" style="margin-top:20px"><p class="muted">Salva prima la ricetta; nella schermata successiva potrai definire i campi da compilare.</p></section> @endif
        <div class="actions" style="margin-top:20px"><button class="button">Salva ricetta</button>@if($recipe->exists)<a class="button secondary" href="{{ route('operator.show', $recipe) }}">Apri schermata operatore</a>@endif</div>
    </form>
    @if($recipe->exists)<form method="post" action="{{ route('recipes.sync-layout', $recipe) }}" class="card" style="margin-top:20px">@csrf <h2>Programma layout sul MK-G</h2><p class="muted">Invia i comandi F1 e FS per ogni campo della ricetta al programma {{ $recipe->program_number }}. Questa operazione sovrascrive i messaggi MK-G configurati.</p><button class="button" onclick="return confirm('Sincronizzare il layout sulla stampante? I messaggi MK-G indicati nei campi verranno sovrascritti.')">Sincronizza layout sulla stampante</button></form>@endif
    @if ($recipe->exists)<script>
        const list = document.getElementById('field-list');
        const hint = document.getElementById('drop-hint');
        const canvas = document.getElementById('layout-canvas');
        const messageStart = () => Number(document.getElementById('message_number').value) || {{ old('message_number', $recipe->message_number) }};
        let draggedRow;

        const rows = () => Array.from(list.querySelectorAll('.field-row'));
        const syncRows = () => {
            rows().forEach((row, index) => {
                row.querySelector('.field-position').textContent = `Campo ${index + 1}`;
                row.querySelector('[data-message-number]').innerHTML = `${messageStart() + index} <span class="muted">(automatico)</span>`;
                row.querySelectorAll('[name]').forEach(input => input.name = input.name.replace(/fields\[\d+\]/, `fields[${index}]`));
            });
            hint.style.display = rows().length ? 'none' : 'block';
            renderLayout();
        };
        const addField = type => {
            const index = rows().length;
            if (messageStart() + index > 64) {
                alert('Non sono disponibili altri messaggi MK-G per questa ricetta.');
                return;
            }
            const markup = document.getElementById('field-template').innerHTML
                .replaceAll('__INDEX__', index)
                .replaceAll('__TYPE__', type);
            hint.insertAdjacentHTML('beforebegin', markup);
            const row = hint.previousElementSibling;
            row.querySelector('select').value = type;
            row.querySelector('[name$="[layout_x]"]').value = Math.min(100 + index * 300, 4095);
            row.querySelector('[name$="[layout_y]"]').value = Math.min(2 + index * 4, 31);
            updateFontPreview(row.querySelector('.printer-font'));
            syncRows();
        };
        const numericValue = (row, suffix, fallback) => Number(row.querySelector(`[name$="[${suffix}]"]`)?.value || fallback);
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
        const updateItem = (item, row) => {
            const x = Math.min(4095, Math.max(0, numericValue(row, 'layout_x', 100)));
            const y = Math.min(31, Math.max(0, numericValue(row, 'layout_y', 2)));
            const font = printerFontPreview(numericValue(row, 'printer_font', 6));
            item.style.left = `${x * canvas.clientWidth / 4095}px`; item.style.top = `${y * canvas.clientHeight / 31}px`;
            item.style.width = 'auto'; item.style.height = 'auto';
            item.style.fontFamily = font.family; item.style.fontSize = `${font.size}px`; item.style.fontWeight = '700';
            item.style.writingMode = font.vertical ? 'vertical-rl' : 'horizontal-tb';
        };
        const renderLayout = () => {
            canvas.innerHTML = '';
            rows().forEach((row, index) => {
                const item = document.createElement('button');
                item.type = 'button'; item.className = 'layout-item';
                item.textContent = row.querySelector('[name$="[default_value]"]').value || row.querySelector('[name$="[label]"]').value || `Campo ${index + 1}`;
                updateItem(item, row);
                item.addEventListener('pointerdown', event => {
                    if (event.button !== 0) return;
                    event.preventDefault();
                    const startX = event.clientX; const startY = event.clientY;
                    const initialX = numericValue(row, 'layout_x', 100); const initialY = numericValue(row, 'layout_y', 2);
                    const moveItem = moveEvent => {
                        row.querySelector('[name$="[layout_x]"]').value = Math.min(4095, Math.max(0, Math.round(initialX + (moveEvent.clientX - startX) * 4095 / canvas.clientWidth)));
                        row.querySelector('[name$="[layout_y]"]').value = Math.min(31, Math.max(0, Math.round(initialY + (moveEvent.clientY - startY) * 31 / canvas.clientHeight)));
                        updateItem(item, row);
                    };
                    const finishDrag = () => {
                        item.classList.remove('selected');
                        item.removeEventListener('pointermove', moveItem);
                        item.removeEventListener('pointerup', finishDrag);
                        item.removeEventListener('pointercancel', finishDrag);
                        item.removeEventListener('lostpointercapture', finishDrag);
                    };

                    item.classList.add('selected');
                    item.setPointerCapture(event.pointerId);
                    item.addEventListener('pointermove', moveItem);
                    item.addEventListener('pointerup', finishDrag);
                    item.addEventListener('pointercancel', finishDrag);
                    item.addEventListener('lostpointercapture', finishDrag);
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
            tool.addEventListener('click', () => addField(tool.dataset.fieldType));
        });
        const updateFontPreview = select => {
            const preview = select.closest('.field').querySelector('[data-font-preview]');
            const option = select.options[select.selectedIndex];
            preview.querySelector('strong').style.fontFamily = option.dataset.previewFamily;
            preview.querySelector('span').textContent = `(${option.dataset.previewName})`;
        };
        list.querySelectorAll('.printer-font').forEach(updateFontPreview);
        list.addEventListener('dragstart', event => {
            const row = event.target.closest('.field-row');
            if (!row || event.target.matches('input, select, textarea')) return;
            draggedRow = row;
            row.classList.add('is-dragging');
        });
        list.addEventListener('dragover', event => {
            event.preventDefault();
            list.classList.add('is-dropping');
            if (draggedRow) insertAtPointer(event);
        });
        list.addEventListener('drop', event => {
            event.preventDefault();
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
        list.addEventListener('change', event => {
            if (event.target.matches('.printer-font')) {
                updateFontPreview(event.target);
                renderLayout();
            }
        });
        document.getElementById('message_number').addEventListener('input', syncRows);
        window.addEventListener('resize', renderLayout);
        syncRows();
    </script>@endif
</x-layouts.app>
