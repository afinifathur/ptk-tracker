<!DOCTYPE html>
<html lang="en" class="h-full"
      x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
      x-init="document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PTK Tracker</title>

  {{-- Vite assets --}}
  @vite(['resources/css/app.css','resources/js/app.js'])

  {{-- Vendor CSS (lokal) --}}
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap5/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">

  {{-- Sembunyikan elemen ber-x-cloak sampai Alpine siap --}}
  <style>[x-cloak]{display:none!important}</style>

  {{-- Sentuhan kecil --}}
  <style>
    table.dataTable thead th { font-weight: 600; }
    table.dataTable thead th, table.dataTable tbody td { border-bottom: 1px solid #e5e7eb!important; }
    table.dataTable.stripe tbody tr:nth-child(odd){ background:#fafafa!important }
    table.dataTable.hover  tbody tr:hover{ background:#f3f4f6!important }

    .topnav a,.topnav .nav-link{ color:#374151; text-decoration:none }
    .topnav a:hover,.topnav .nav-link:hover{ color:#111827 }
    .dark .topnav a,.dark .topnav .nav-link{ color:#e5e7eb }
    .dark .topnav a:hover,.dark .topnav .nav-link:hover{ color:#f9fafb }
  </style>
</head>

<body class="h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <div class="max-w-7xl mx-auto p-6">
    {{-- ===== HEADER + NAVBAR ===== --}}
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">PTK Tracker</h1>

      {{-- NAV --}}
      <nav class="topnav hidden md:flex space-x-4">
         <a href="{{ route('dashboard') }}"
           class="px-2 py-1 {{ request()->routeIs('dashboard') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
          Dashboard
        </a>
       <a href="{{ route('ptk.index') }}"
           class="px-2 py-1 {{ request()->routeIs('ptk.index') || request()->routeIs('ptk.show') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
          Daftar PTK
        </a>
         <a href="{{ route('ptk.kanban') }}"
           class="px-2 py-1 {{ request()->routeIs('ptk.kanban') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
          Kanban
        </a>
        <a href="{{ route('exports.range.form') }}"
           class="px-2 py-1 {{ request()->routeIs('exports.range.form') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
          Laporan Periode
        </a>
       

        @can('ptk.create')
          @unlessrole('director')
            <a href="{{ route('ptk.create') }}"
               class="px-2 py-1 {{ request()->routeIs('ptk.create') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
              New PTK
            </a>
          @endunlessrole
        @endcan

        @unlessrole('admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3')
          @can('menu.queue')
            <a href="{{ route('ptk.queue') }}"
             class="px-2 py-1 inline-flex items-center gap-2 {{ request()->routeIs('ptk.queue*') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
            <span>Antrian Persetujuan</span>
            @if(isset($approvalQueueCount) && $approvalQueueCount > 0)
              <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold rounded-full text-white bg-red-600">
                {{ $approvalQueueCount }}
              </span>
            @endif
          </a>
        @endcan
       

          @can('menu.audit')
           <a href="{{ route('exports.audits.index') }}"
             class="px-2 py-1 {{ request()->routeIs('exports.audits.*') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
            Audit Log
          </a>
        @endcan

          @hasanyrole('director|kabag_qc|manager_hr|admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3')
            <a href="{{ route('settings.categories') }}"
             class="px-2 py-1 {{ request()->routeIs('settings.*') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
            Settings
          </a>
        @endhasanyrole

          @can('menu.recycle')
            <a href="{{ route('ptk.recycle') }}"
             class="px-2 py-1 {{ request()->routeIs('ptk.recycle') ? 'font-semibold border-b-2 border-indigo-600' : 'text-gray-700 dark:text-gray-300' }}">
            Recycle Bin
          </a>
        @endcan
        @endunlessrole
      </nav>

      {{-- Theme toggle + User --}}
      <div class="flex items-center space-x-3 relative" x-data="{ open:false }">
        <button class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-800"
                x-on:click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', dark)"
                aria-label="Toggle theme">
          <span x-show="!dark">🌙</span>
          <span x-show="dark">☀️</span>
        </button>

        @auth
          <div class="relative">
            <button x-on:click="open = !open"
                    class="flex items-center space-x-2 px-3 py-2 bg-gray-200 dark:bg-gray-800 rounded hover:bg-gray-300 dark:hover:bg-gray-700">
              <span class="font-semibold">{{ auth()->user()->name }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" x-transition x-cloak
                 class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 border rounded-lg shadow-lg py-2 z-50">
              <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 border-b">
                <div class="font-semibold">{{ auth()->user()->name }}</div>
                <div class="text-xs text-gray-500">{{ auth()->user()->roles->pluck('name')->join(', ') }}</div>
              </div>
              <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">🚪 Logout</button>
              </form>
            </div>
          </div>
        @endauth
      </div>
    </header>

    @if(session('ok'))
      <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 rounded mb-4" role="status" aria-live="polite">
        {{ session('ok') }}
      </div>
    @endif

    {{-- ===== PAGE CONTENT ===== --}}
    {{ $slot }}
  </div>

  {{-- ===== JS VENDOR (ORDER PENTING) ===== --}}
  <script src="{{ asset('vendor/polyfill/polyfill.min.js') }}"></script>
  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap5/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>

  {{-- Chart.js + plugin (Chart.js hanya sekali) --}}
  <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

  <script src="{{ asset('vendor/sortable/Sortable.min.js') }}"></script>
  <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // register plugin datalabels global bila ada
      if (window.Chart && window.ChartDataLabels) { Chart.register(ChartDataLabels); }
      // datatables auto init
      if (window.jQuery && $.fn.dataTable) {
        $('.js-dt').each(function () {
          const $t = $(this);
          if (!$t.hasClass('dataTable')) $t.DataTable({ pageLength: 10, lengthChange: true, autoWidth: false, order: [] });
        });
      }
    });
  </script>

  {{-- stack halaman: ditempatkan PALING BAWAH agar vendor sudah siap --}}
  @stack('scripts')
</body>
</html>
