<!DOCTYPE html>
<html lang="en" class="h-full"
      x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
      x-init="document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  {{-- CSRF token --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>PTK Tracker</title>

  {{-- Vite assets --}}
  @vite(['resources/css/app.css','resources/js/app.js'])

  {{-- Vendor CSS --}}
  <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap5/bootstrap.min.css') }}">

  {{-- Alpine cloak --}}
  <style>[x-cloak]{display:none !important;}</style>

  {{-- Styling ringan --}}
  <style>
    table.dataTable thead th { font-weight: 600; }
    table.dataTable thead th,
    table.dataTable tbody td { border-color: #e5e7eb; }
    table.dataTable tbody tr:hover { background-color: #f9fafb; }

    .topnav a { color: #374151; }
    .topnav a:hover { color: #111827; }
    .dark .topnav a { color: #e5e7eb; }
    .dark .topnav a:hover { color: #f9fafb; }
  </style>
</head>

<body class="h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
<div class="max-w-7xl mx-auto p-6">

  {{-- =======================
       HEADER
     ======================= --}}
  <header class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">PTK Tracker</h1>

    {{-- NAVBAR --}}
    <nav class="topnav hidden md:flex space-x-4">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <a href="{{ route('ptk.index') }}">Daftar PTK</a>
      <a href="{{ route('ptk.kanban') }}">Kanban</a>

      @can('ptk.create')
        @unlessrole('director')
          <a href="{{ route('ptk.create') }}">New PTK</a>
        @endunlessrole
      @endcan

      @can('menu.queue')
        <a href="{{ route('ptk.queue') }}">Antrian Approval</a>
      @endcan

      @can('menu.recycle')
        <a href="{{ route('ptk.recycle') }}">Recycle Bin</a>
      @endcan

      <a href="{{ route('exports.range.form') }}">Laporan Periode</a>

      @can('menu.audit')
        <a href="{{ route('exports.audits.index') }}">Audit Log</a>
      @endcan

      {{-- ===== Approval Log (BARU) ===== --}}
      @hasanyrole('admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3|auditor')
        <a href="{{ route('approval.log') }}"
           class="{{ request()->routeIs('approval.log')
                    ? 'font-semibold border-b-2 border-indigo-600'
                    : '' }}">
          Approval Log
        </a>
      @endhasanyrole
      {{-- ============================== --}}

      @hasanyrole('director|kabag_qc|manager_hr|admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3')
        <a href="{{ route('settings.categories') }}">Settings</a>
      @endhasanyrole
    </nav>

    {{-- Theme toggle & User --}}
    <div class="flex items-center space-x-3 relative" x-data="{ open:false }">
      <button
        class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-800"
        x-on:click="dark=!dark;localStorage.setItem('theme',dark?'dark':'light');document.documentElement.classList.toggle('dark',dark)">
        <span x-show="!dark">🌙</span>
        <span x-show="dark">☀️</span>
      </button>

      @auth
        <div class="relative">
          <button
            x-on:click="open=!open"
            class="flex items-center space-x-2 px-3 py-2 bg-gray-200 dark:bg-gray-800 rounded hover:bg-gray-300 dark:hover:bg-gray-700">
            <span class="font-semibold">{{ auth()->user()->name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <div x-show="open" x-transition x-cloak
               class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 border rounded-lg shadow-lg py-2 z-50">
            <div class="px-4 py-2 text-sm border-b">
              <div class="font-semibold">{{ auth()->user()->name }}</div>
              <div class="text-xs text-gray-500">
                {{ auth()->user()->roles->pluck('name')->join(', ') }}
              </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit"
                      class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                🚪 Logout
              </button>
            </form>
          </div>
        </div>
      @endauth
    </div>
  </header>

  @if(session('ok'))
    <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 rounded mb-4">
      {{ session('ok') }}
    </div>
  @endif

  {{ $slot }}
</div>

{{-- JS --}}
<script src="{{ asset('vendor/polyfill/polyfill.min.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap5/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>

<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script src="{{ asset('vendor/sortable/Sortable.min.js') }}"></script>
<script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>

<script>
  document.addEventListener('alpine:init', () => console.log('Alpine OK'));
</script>

@stack('scripts')
</body>
</html>
