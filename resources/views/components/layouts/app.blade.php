<!DOCTYPE html>
<html lang="en" class="h-full" x-data="{dark: localStorage.getItem('theme')==='dark'}" x-init="document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PTK Tracker</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <div class="max-w-7xl mx-auto p-6">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">PTK Tracker</h1>
      <nav class="space-x-4">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('ptk.index') }}">Daftar PTK</a>
        <a href="{{ route('ptk.kanban') }}">Kanban</a>
        <a href="{{ route('ptk.queue') }}">Antrian Persetujuan</a>
        <a href="{{ route('ptk.recycle') }}">Recycle Bin</a>
        <a href="{{ route('exports.range.form') }}">Laporan Periode</a>
      </nav>
      <button
        class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-800"
        x-on:click="dark=!dark; localStorage.setItem('theme', dark?'dark':'light'); document.documentElement.classList.toggle('dark', dark)">
        <span x-show="!dark">🌙 Dark</span><span x-show="dark">☀️ Light</span>
      </button>
    </header>
    @if(session('ok'))
      <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 rounded mb-4">{{ session('ok') }}</div>
    @endif
    {{ $slot }}
  </div>
</body>
</html>
