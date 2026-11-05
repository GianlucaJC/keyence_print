<x-layouts.app>
    <div class="page-title"><div><h1>Console di produzione</h1><p class="muted">Seleziona una ricetta e trasmetti i dati al marcatore configurato.</p></div><a class="button" href="{{ route('recipes.create') }}">+ Nuova ricetta</a></div>
    <div class="grid grid-3">
        <section class="card"><div class="muted">Marcatori attivi</div><div class="metric">{{ $printers->count() }}</div></section>
        <section class="card"><div class="muted">Ricette disponibili</div><div class="metric">{{ $recipes->count() }}</div></section>
        <section class="card"><div class="muted">Ultimi invii riusciti</div><div class="metric">{{ $recentLogs->where('was_successful', true)->count() }}</div></section>
    </div>
    <div class="grid grid-2" style="margin-top:20px">
        <section class="card"><h2>Ricette di produzione</h2>
            @forelse ($recipes as $recipe)
                <div style="padding:12px 0;border-bottom:1px solid #e6eaed"><strong>{{ $recipe->name }}</strong><br><span class="muted">{{ $recipe->printer->name }} · Programma {{ $recipe->program_number }}, messaggio {{ $recipe->message_number }}</span><div style="margin-top:8px"><a class="button small" href="{{ route('operator.show', $recipe) }}">Apri schermata operatore</a></div></div>
            @empty <p class="muted">Crea un marcatore e una ricetta per iniziare.</p> @endforelse
        </section>
        <section class="card"><h2>Ultime comunicazioni</h2>
            @forelse ($recentLogs as $log)
                <div style="padding:9px 0;border-bottom:1px solid #e6eaed"><span @class(['status', 'ok' => $log->was_successful, 'error' => ! $log->was_successful])>{{ $log->was_successful ? 'OK' : 'ERRORE' }}</span> <strong>{{ $log->printer->name }}</strong> · {{ $log->sent_at->format('d/m/Y H:i:s') }}<br><span class="muted">{{ $log->error_message ?: $log->response }}</span></div>
            @empty <p class="muted">Nessuna comunicazione registrata.</p> @endforelse
        </section>
    </div>
</x-layouts.app>
