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

  {{-- Optional: sembunyikan elemen ber-x-cloak sampai Alpine siap --}}
  <style>[x-cloak]{display:none !important;}</style>
</head>
<body class="h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <div class="max-w-7xl mx-auto p-6">

    {{-- =======================
         HEADER
       ======================= --}}
    <header class="flex items-center justify-between mb-6">
      {{-- Kiri: Judul --}}
      <h1 class="text-2xl font-bold">PTK Tracker</h1>

      {{-- Tengah: Navigasi --}}
      <nav class="hidden md:flex space-x-4">
  
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('ptk.index') }}">Daftar PTK</a>

        {{-- Kanban: tampil untuk semua user login --}}
        <a href="{{ route('ptk.kanban') }}">Kanban</a>

        {{-- New PTK: sembunyikan khusus Direktur (Auditor memang tak punya permission) --}}
        @can('ptk.create')
          @unlessrole('director')
            <a href="{{ route('ptk.create') }}">New PTK</a>
          @endunlessrole
        @endcan

        @can('menu.queue')
          <a href="{{ route('ptk.queue') }}">Antrian Persetujuan</a>
        @endcan

        @can('menu.recycle')
          <a href="{{ route('ptk.recycle') }}">Recycle Bin</a>
        @endcan

        <a href="{{ route('exports.range.form') }}">Laporan Periode</a>

        @can('menu.audit')
          <a href="{{ route('exports.audits.index') }}">Audit Log</a>
        @endcan

        {{-- Settings: tampil untuk Direktur + Kabag QC + Manager HR + Admin QC/HR/K3 --}}
        @role('director|kabag_qc|manager_hr|admin_qc|admin_hr|admin_k3')
          <a href="{{ route('settings.categories') }}">Settings</a>
        @endrole
      </nav>

      {{-- Kanan: Theme toggle + User Dropdown --}}
      <div class="flex items-center space-x-3 relative" x-data="{open:false}">
        <button class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-800"
          x-on:click="dark=!dark; localStorage.setItem('theme', dark?'dark':'light'); document.documentElement.classList.toggle('dark', dark)"
          aria-label="Toggle theme">
          <span x-show="!dark">🌙</span><span x-show="dark">☀️</span>
        </button>

        {{-- Dropdown User --}}
        <div class="relative">
          <button x-on:click="open=!open" class="flex items-center space-x-2 px-3 py-2 bg-gray-200 dark:bg-gray-800 rounded hover:bg-gray-300 dark:hover:bg-gray-700">
            <span class="font-semibold">{{ auth()->user()->name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          {{-- Popup --}}
          <div x-show="open" x-transition x-cloak
               class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 border rounded-lg shadow-lg py-2 z-50">
            <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 border-b">
              <div class="font-semibold">{{ auth()->user()->name }}</div>
              <div class="text-xs text-gray-500">{{ auth()->user()->roles->pluck('name')->join(', ') }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
              @csrf
              <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                🚪 Logout
              </button>
            </form>
          </div>
        </div>
      </div>
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
