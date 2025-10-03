<!DOCTYPE html>
<html lang="en" class="h-full"
      x-data="{dark: localStorage.getItem('theme')==='dark'}"
      x-init="document.documentElement.classList.toggle('dark', dark)">
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

      <nav class="space-x-4" role="navigation" aria-label="Main">
        <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
        <a href="{{ route('ptk.index') }}" class="hover:underline">Daftar PTK</a>
        <a href="{{ route('ptk.kanban') }}" class="hover:underline">Kanban</a>

        <a href="{{ route('ptk.queue') }}" class="relative hover:underline">
          Antrian Persetujuan
          @if(($queueCount ?? 0) > 0)
            <span
              class="absolute -top-2 -right-3 inline-flex items-center justify-center h-5 min-w-[20px] px-1
                     rounded-full bg-red-600 text-white text-xs leading-none"
              aria-label="Jumlah antrian">
              {{ $queueCount }}
            </span>
          @endif
        </a>

        <a href="{{ route('ptk.recycle') }}" class="hover:underline">Recycle Bin</a>
        <a href="{{ route('exports.range.form') }}" class="hover:underline">Laporan Periode</a>
        <a href="{{ route('exports.audits.index') }}" class="hover:underline">Audit Log</a>
        <a href="{{ route('settings.categories') }}" class="hover:underline">Settings</a>
      </nav>

      <button
        type="button"
        class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-800"
        x-on:click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', dark)"
        x-bind:aria-pressed="dark.toString()"
        aria-label="Toggle theme">
        <span x-show="!dark">🌙 Dark</span>
        <span x-show="dark">☀️ Light</span>
      </button>
    </header>

    @if(session('ok'))
      <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 rounded mb-4" role="status" aria-live="polite">
        {{ session('ok') }}
      </div>
    @endif

    {{ $slot }}
  </div>

  {{-- scripts yang di-push dari halaman (create/edit, dll) --}}
  @stack('scripts')
</body>
</html>
