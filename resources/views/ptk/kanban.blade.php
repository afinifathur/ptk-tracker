<x-layouts.app>
  <h1 class="text-xl font-semibold mb-4">Kanban PTK</h1>
  <p class="text-xs text-gray-500 mb-3">
    Menampilkan maksimal <strong>30</strong> PTK per kolom (Not Started / In Progress / Completed).
  </p>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- NOT STARTED (abu) --}}
    <section>
      <header class="px-4 py-2 rounded-t-lg bg-gray-100 dark:bg-gray-800 border border-b-0">
        <div class="flex items-center justify-between">
          <span class="font-semibold">Not Started</span>
          <span class="text-sm text-gray-500">{{ $notStarted->count() }}</span>
        </div>
      </header>

      <div class="rounded-b-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 space-y-3">
        @forelse($notStarted as $k)
          <article class="p-3 rounded-lg bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700">
            <a href="{{ route('ptk.show', $k) }}"
               class="font-medium block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-gray-50 underline decoration-gray-300 hover:decoration-gray-400">
              {{ Str::limit($k->title, 100) }}
            </a>
            <div class="mt-1 text-xs text-gray-500">
              {{ $k->department->name ?? '-' }} — {{ $k->pic->name ?? '-' }}
              <div class="mt-0.5">{{ optional($k->due_date)->format('Y-m-d') }}</div>
            </div>
          </article>
        @empty
          <div class="p-3 text-sm text-gray-500">Tidak ada data.</div>
        @endforelse
      </div>
    </section>

    {{-- IN PROGRESS (kuning) --}}
    <section>
      <header class="px-4 py-2 rounded-t-lg bg-yellow-100 text-yellow-900 dark:bg-yellow-900/30 dark:text-yellow-200 border border-b-0 border-yellow-200 dark:border-yellow-800">
        <div class="flex items-center justify-between">
          <span class="font-semibold">In Progress</span>
          <span class="text-sm">{{ $inProgress->count() }}</span>
        </div>
      </header>

      <div class="rounded-b-lg border border-yellow-200 dark:border-yellow-800 bg-white dark:bg-gray-900 p-2 space-y-3">
        @forelse($inProgress as $k)
          <article class="p-3 rounded-lg bg-white dark:bg-gray-900 shadow-sm border border-yellow-200 dark:border-yellow-800">
            <a href="{{ route('ptk.show', $k) }}"
               class="font-medium block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-gray-50 underline decoration-gray-300 hover:decoration-gray-400">
              {{ Str::limit($k->title, 100) }}
            </a>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
              {{ $k->department->name ?? '-' }} — {{ $k->pic->name ?? '-' }}
              <div class="mt-0.5">{{ optional($k->due_date)->format('Y-m-d') }}</div>
            </div>
          </article>
        @empty
          <div class="p-3 text-sm text-gray-500">Tidak ada data.</div>
        @endforelse
      </div>
    </section>

    {{-- COMPLETED (hijau) --}}
    <section>
      <header class="px-4 py-2 rounded-t-lg bg-green-100 text-green-900 dark:bg-green-900/30 dark:text-green-200 border border-b-0 border-green-200 dark:border-green-800">
        <div class="flex items-center justify-between">
          <span class="font-semibold">Completed</span>
          <span class="text-sm">{{ $completed->count() }}</span>
        </div>
      </header>

      <div class="rounded-b-lg border border-green-200 dark:border-green-800 bg-white dark:bg-gray-900 p-2 space-y-3">
        @forelse($completed as $k)
          <article class="p-3 rounded-lg bg-white dark:bg-gray-900 shadow-sm border border-green-200 dark:border-green-800">
            <a href="{{ route('ptk.show', $k) }}"
               class="font-medium block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-gray-50 underline decoration-gray-300 hover:decoration-gray-400">
              {{ Str::limit($k->title, 100) }}
            </a>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
              {{ $k->department->name ?? '-' }} — {{ $k->pic->name ?? '-' }}
              <div class="mt-0.5">
                {{ optional($k->approved_at ?? $k->updated_at ?? $k->created_at)->format('Y-m-d') }}
              </div>
            </div>
          </article>
        @empty
          <div class="p-3 text-sm text-gray-500">Tidak ada data.</div>
        @endforelse
      </div>
    </section>

  </div>

  {{-- Pastikan TIDAK meng-inisialisasi Sortable di halaman ini --}}
  <script>
    // Jika app.js pernah auto-init Sortable, jalankan hanya untuk elemen yang diberi data-draggable="1".
  </script>
</x-layouts.app>
