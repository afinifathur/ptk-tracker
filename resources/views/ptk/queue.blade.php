<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-xl font-semibold">Antrian Persetujuan</h2>
      <p class="text-sm text-gray-500">
        Stage: <strong>{{ $stage ?? 'semua' }}</strong>
      </p>
    </div>

    <div class="space-x-2">
      <a href="{{ route('ptk.queue') }}" class="px-3 py-2 bg-gray-700 text-white rounded">Semua</a>
      <a href="{{ route('ptk.queue','approver') }}" class="px-3 py-2 bg-amber-700 text-white rounded">Approver</a>
      <a href="{{ route('ptk.queue','director') }}" class="px-3 py-2 bg-emerald-700 text-white rounded">Director</a>
    </div>
  </div>

  <table class="display w-full text-sm">
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Judul</th>
        <th>PIC</th>
        <th>Dept</th>
        <th>Status</th>
        <th>Due</th>
        <th>Aksi</th>
      </tr>
    </thead>

    <tbody>
      @forelse($items as $row)
        <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
          <td class="py-2 px-3">{{ $row->number ?? '—' }}</td>

          <td class="py-2 px-3">
            <a href="{{ route('ptk.show', $row) }}" class="text-blue-600 underline hover:text-blue-800">
              {{ $row->title }}
            </a>
          </td>

          <td class="py-2 px-3">{{ $row->pic->name ?? '-' }}</td>
          <td class="py-2 px-3">{{ $row->department->name ?? '-' }}</td>
          <td class="py-2 px-3">{{ $row->status ?? '-' }}</td>
          <td class="py-2 px-3 whitespace-nowrap">{{ $row->due_date?->format('Y-m-d') ?? '-' }}</td>

          {{-- === AKSI (sinkron localStorage + fokus tab) === --}}
          <td class="px-3 py-2 text-center space-x-2"
              x-data="{ ready: false }"
              x-init="
                // baca state awal dari localStorage
                ready = (localStorage.getItem('ptk-previewed-{{ $row->id }}') === '1');

                // saat tab kembali fokus, sinkron ulang dari localStorage
                window.addEventListener('focus', () => {
                  ready = (localStorage.getItem('ptk-previewed-{{ $row->id }}') === '1');
                });
              ">

            {{-- PREVIEW: buka tab baru + set flag SEBELUM tab terbuka --}}
            <a href="{{ route('exports.preview', $row) }}"
               target="_blank" rel="noopener noreferrer"
               class="inline-block px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
               x-on:click="
                 localStorage.setItem('ptk-previewed-{{ $row->id }}','1');
                 ready = true;
               ">
               Preview
            </a>

            {{-- APPROVE --}}
            <form method="POST" action="{{ route('ptk.approve', $row) }}" class="inline"
                  x-on:submit="localStorage.removeItem('ptk-previewed-{{ $row->id }}')">
              @csrf
              <button type="submit"
                class="px-3 py-1 rounded transition"
                :class="ready ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-300 text-gray-600 cursor-not-allowed'"
                :disabled="!ready">
                Approve
              </button>
            </form>

            {{-- REJECT --}}
            <form method="POST" action="{{ route('ptk.reject', $row) }}" class="inline"
                  x-on:submit="localStorage.removeItem('ptk-previewed-{{ $row->id }}')">
              @csrf
              <button type="submit"
                class="px-3 py-1 rounded transition"
                :class="ready ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-300 text-gray-600 cursor-not-allowed'"
                :disabled="!ready">
                Reject
              </button>
            </form>
          </td>
          {{-- === /AKSI === --}}
        </tr>
      @empty
        <tr>
          <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada data PTK.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Satu blok script saja: init DataTables + re-init Alpine --}}
  <script>
    document.addEventListener('alpine:init', () => {
      $(function () {
        const $table = $('table.display');

        // Init DataTable (sekali)
        const dt = $.fn.dataTable.isDataTable($table)
          ? $table.DataTable()
          : $table.DataTable({ pageLength: 20 });

        // Pastikan binding Alpine aktif setelah render pertama
        Alpine.initTree($table.get(0));

        // Saat DataTables redraw (paging/sort/filter), aktifkan lagi directive Alpine
        $table.on('draw.dt', function () {
          Alpine.initTree(this);
        });
      });
    });
  </script>
</x-layouts.app>
