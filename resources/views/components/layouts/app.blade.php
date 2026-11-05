<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Keyence Print') }}</title>
    <style>
        :root { --blue: #094d70; --dark: #05334b; --accent: #09a6b8; --bg: #edf2f5; --danger: #b42318; --success: #067647; }
        * { box-sizing: border-box; } body { margin: 0; color: #172b3a; background: var(--bg); font: 15px/1.45 Arial, sans-serif; }
        .app { display: flex; min-height: 100vh; } .sidebar { width: 250px; padding: 28px 16px; color: #d7ecf4; background: linear-gradient(180deg, var(--blue), var(--dark)); }
        .brand { margin: 0 0 36px; font-size: 24px; font-weight: 700; letter-spacing: .5px; } .brand span { color: #fff; } .brand small { display: block; margin-top: 3px; font-size: 11px; font-weight: 400; letter-spacing: 1.3px; }
        .nav a { display: block; margin: 7px 0; padding: 12px; color: inherit; text-decoration: none; border-radius: 5px; } .nav a:hover, .nav a.active { color: #fff; background: rgba(255,255,255,.14); }
        .main { width: min(1280px, calc(100% - 250px)); padding: 36px; } .page-title { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 24px; } h1 { margin: 0; font-size: 28px; } h2 { margin: 0 0 14px; font-size: 18px; } .muted { color: #667085; }
        .grid { display: grid; gap: 20px; } .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .card { padding: 22px; background: #fff; border: 1px solid #dfe7ec; border-radius: 8px; box-shadow: 0 2px 6px rgba(19, 52, 73, .04); } .metric { font-size: 30px; color: var(--blue); font-weight: 700; }
        label { display: block; margin-bottom: 5px; color: #344054; font-weight: 700; font-size: 13px; } input, select, textarea { width: 100%; padding: 9px 10px; color: #172b3a; background: #fff; border: 1px solid #b8c5ce; border-radius: 4px; font: inherit; } textarea { min-height: 76px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: 2px solid #8ad5dd; border-color: var(--accent); } .field { margin-bottom: 16px; } .checkbox { display: flex; align-items: center; gap: 8px; } .checkbox input { width: auto; }
        .button { display: inline-block; padding: 9px 14px; color: #fff; background: var(--blue); border: 0; border-radius: 4px; cursor: pointer; font: inherit; font-weight: 700; text-decoration: none; } .button:hover { background: #063b58; } .button.secondary { color: #17455d; background: #d9e8ef; } .button.danger { background: var(--danger); } .button.small { padding: 6px 9px; font-size: 13px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; } .alert { margin-bottom: 20px; padding: 12px 14px; border-radius: 5px; } .alert.success { color: var(--success); background: #ecfdf3; border: 1px solid #abefc6; } .alert.error { color: #912018; background: #fff3f2; border: 1px solid #fecdca; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 11px 8px; text-align: left; vertical-align: top; border-bottom: 1px solid #e6eaed; } th { color: #475467; font-size: 12px; text-transform: uppercase; } .status { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 12px; font-weight: 700; } .status.ok { color: #067647; background: #d1fadf; } .status.error { color: #b42318; background: #fee4e2; }
        .preview { padding: 16px; min-height: 62px; color: #092f45; background: repeating-linear-gradient(90deg, #f8fafc, #f8fafc 11px, #e8eef2 12px); border: 1px dashed #7b98a9; border-radius: 4px; font: 700 20px/1.4 Consolas, monospace; word-break: break-all; } .protocol { margin-top: 12px; color: #475467; font: 13px Consolas, monospace; }
        .field-palette { display: flex; flex-wrap: wrap; gap: 10px; margin: 14px 0; } .field-tool { min-width: 130px; padding: 12px; color: #17455d; background: #f0f8fb; border: 1px solid #9dc5d5; border-radius: 5px; cursor: grab; font: inherit; font-weight: 700; text-align: left; } .field-tool:active { cursor: grabbing; }
        .drop-zone { min-height: 72px; padding: 8px; border: 2px dashed #9ab4c1; border-radius: 7px; background: #f8fbfc; } .drop-zone.is-dropping { border-color: var(--accent); background: #e6f8fa; } .drop-hint { margin: 13px; color: #667085; text-align: center; }
        .field-row { position: relative; } .field-row.is-dragging { opacity: .45; } .field-row-actions { display: flex; gap: 6px; align-items: center; margin-bottom: 12px; } .drag-handle { padding: 6px 9px; color: #17455d; border: 1px solid #9dc5d5; border-radius: 4px; background: #f0f8fb; cursor: grab; font-weight: 700; } .drag-handle:active { cursor: grabbing; }
        .layout-editor { margin: 20px 0; } .layout-canvas-wrap { overflow: auto; padding: 10px; background: #e4edf1; border: 1px solid #b8c5ce; border-radius: 7px; } .layout-canvas { position: relative; width: 800px; height: 300px; background-color: #fff; background-image: linear-gradient(#e9f0f3 1px, transparent 1px), linear-gradient(90deg, #e9f0f3 1px, transparent 1px); background-size: 20px 20px; box-shadow: inset 0 0 0 1px #8da7b5; overflow: hidden; }
        .layout-item { position: absolute; display: flex; align-items: center; padding: 4px 7px; color: #092f45; background: rgba(215, 242, 246, .92); border: 1px solid #088a99; border-radius: 3px; cursor: move; overflow: hidden; white-space: nowrap; user-select: none; } .layout-item.selected { outline: 3px solid #f5a623; z-index: 1; } .layout-note { margin: 7px 0; color: #667085; font-size: 13px; } .layout-settings summary { cursor: pointer; color: #17455d; font-weight: 700; }
        .print-layout-item { position: absolute; display: flex; align-items: center; overflow: hidden; color: #101828; font-family: Arial, sans-serif; font-weight: 700; line-height: 1; white-space: nowrap; }
        @media (max-width: 800px) { .app { display: block; } .sidebar { width: auto; padding: 16px; } .brand { margin-bottom: 10px; } .nav { display: flex; flex-wrap: wrap; } .main { width: auto; padding: 20px; } .grid-2, .grid-3 { grid-template-columns: 1fr; } .page-title { align-items: flex-start; flex-direction: column; } table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <p class="brand"><span>KEYENCE</span> PRINT<small>MK-G CONTROL</small></p>
        <nav class="nav">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
            <a href="{{ route('printers.index') }}" @class(['active' => request()->routeIs('printers.*')])>Marcatori</a>
            <a href="{{ route('recipes.index') }}" @class(['active' => request()->routeIs('recipes.*')])>Ricette</a>
        </nav>
    </aside>
    <main class="main">
        @if (session('success')) <div class="alert success">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert error"><strong>Operazione non completata.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        {{ $slot }}
    </main>
</div>
</body>
</html>
