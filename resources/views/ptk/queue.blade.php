<x-layouts.app>
  @php
    /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $items */
    $user = auth()->user();
    $asCollection = $items instanceof \Illuminate\Support\Collection ? $items : collect($items);

    $isStage1 = $user?->hasAnyRole(['kabag_qc','manager_hr']);
    $isStage2 = $user?->hasRole('director');

    $stageLabel = $isStage1 ? 'Approver (Stage 1)' : ($isStage2 ? 'Director (Stage 2)' : 'semua');

    // Filter sesuai role (bila diperlukan)
    $rows = $asCollection->when($isStage1, fn($c) =>
        $c->where('status', 'Submitted')->whereNull('approved_stage1_at')
    )->when($isStage2, fn($c) =>
        $c->where('status', 'Waiting Director')->whereNull('approved_stage2_at')
    );
  @endphp

  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-xl font-semibold">Antrian Persetujuan</h2>
      <p class="text-sm text-gray-500">
        Stage: <strong>{{ $stageLabel }}</strong>
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
      @forelse($rows as $row)
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

          {{-- === AKSI (rapi inline buttons + localStorage sync + fokus tab) === --}}
          <td class="px-3 py-2 text-center">
            <div class="flex items-center justify-center gap-2" x-data="{ ready: false }"
                 x-init="
                   // baca state awal dari localStorage
                   ready = (localStorage.getItem('ptk-previewed-{{ $row->id }}') === '1');
                   window.addEventListener('focus', () => {
                     ready = (localStorage.getItem('ptk-previewed-{{ $row->id }}') === '1');
                   });
                 ">

              {{-- PREVIEW: buka tab baru + set flag sebelum tab terbuka --}}
              <a href="{{ route('exports.preview', $row) }}"
                 target="_blank" rel="noopener noreferrer"
                 class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium rounded shadow-sm
                        bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                 x-on:click="localStorage.setItem('ptk-previewed-{{ $row->id }}','1'); ready = true;">
                Preview
              </a>

              {{-- APPROVE: form submit --}}
              <form method="POST" action="{{ route('ptk.approve', $row) }}" class="inline-block"
                    x-on:submit="localStorage.removeItem('ptk-previewed-{{ $row->id }}')">
                @csrf
                <button type="submit"
                  :disabled="!ready"
                  :class="ready
                    ? 'inline-flex items-center justify-center px-3 py-1 text-sm font-medium rounded shadow-sm bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300'
                    : 'inline-flex items-center justify-center px-3 py-1 text-sm font-medium rounded bg-gray-200 text-gray-500 cursor-not-allowed opacity-60'">
                  Approve
                </button>
              </form>

              {{-- REJECT: modal trigger --}}
              <button type="button"
                :disabled="!ready"
                :class="ready
                  ? 'inline-flex items-center justify-center px-3 py-1 text-sm font-medium rounded shadow-sm bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300'
                  : 'inline-flex items-center justify-center px-3 py-1 text-sm font-medium rounded bg-gray-200 text-gray-500 cursor-not-allowed opacity-60'"
                @click.prevent="if (ready) $dispatch('open-reject-modal', { id: {{ $row->id }} })">
                Reject
              </button>

            </div>
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

  {{-- === Modal Reject (global satu kali) === --}}
  <div
    x-data="{ open:false, id:null }"
    x-on:open-reject-modal.window="
      open = true;
      id = $event.detail.id;
      // fokus textarea saat modal terbuka (sedikit delay untuk render)
      setTimeout(() => { $refs.reason?.focus(); }, 50);
    "
    x-cloak
  >
    <form
      method="POST"
      :action="id ? '{{ url('ptk') }}/' + id + '/reject' : '#'"
      class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4"
      x-show="open"
      @keydown.escape.window="open=false"
      x-on:submit="localStorage.removeItem('ptk-previewed-' + id)"
    >
      @csrf
      <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded shadow p-4 mt-24">
        <h3 class="font-semibold mb-2">Alasan Reject</h3>
        <textarea x-ref="reason" name="reason" class="w-full border dark:border-gray-700 rounded p-2"
                  required rows="4" placeholder="Tuliskan alasan…"></textarea>

        <div class="mt-3 flex justify-end gap-2">
          <button type="button" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700"
                  @click="open=false">Batal</button>
          <button type="submit" class="px-3 py-1 rounded bg-red-600 text-white">Kirim</button>
        </div>
      </div>
    </form>
  </div>

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
