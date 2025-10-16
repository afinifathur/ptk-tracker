<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-xl font-semibold">Antrian Persetujuan</h2>
      <p class="text-sm text-gray-500">Stage: <strong>{{ $stage ?? 'semua' }}</strong></p>
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

          @php
            $canApproveNow = session("previewed_ptk.{$row->id}");
          @endphp
          <td class="py-2 px-3">
            <div class="flex items-center gap-2">
              {{-- PREVIEW (kuning, gunakan shade yang sudah ada: amber-700) --}}
              <a href="{{ route('exports.preview', $row->id) }}"
                 target="_blank" rel="noopener"
                 class="px-3 py-1 rounded text-white bg-amber-700 hover:bg-amber-800">
                Preview
              </a>

              {{-- APPROVE: emerald (aktif) atau abu-abu (belum preview) --}}
              <form method="POST" action="{{ route('ptk.approve', $row) }}">
                @csrf
                <button type="submit"
                  @class([
                    'px-3 py-1 rounded text-white',
                    'bg-emerald-700 hover:bg-emerald-800' => $canApproveNow,
                    'bg-gray-300 cursor-not-allowed'      => ! $canApproveNow,
                  ])
                  @disabled(! $canApproveNow)
                  title="{{ $canApproveNow ? 'Approve dokumen' : 'Buka Preview dulu untuk mengaktifkan Approve' }}">
                  Approve
                </button>
              </form>

              {{-- REJECT (merah) --}}
              <form method="POST" action="{{ route('ptk.reject', $row) }}">
                @csrf
                <button class="px-3 py-1 rounded text-white bg-rose-600 hover:bg-rose-700">Reject</button>
              </form>
            </div>

            {{-- (opsional) seed kelas agar aman dari purge bila pakai build produksi --}}
            <div class="hidden">
              bg-emerald-700 hover:bg-emerald-800
              bg-amber-700 hover:bg-amber-800
              bg-gray-300 cursor-not-allowed
              bg-rose-600 hover:bg-rose-700
              px-3 py-1 rounded text-white
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada data PTK.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <script>
    $(function () {
      $('table.display').DataTable({ pageLength: 20 });
    });
  </script>
</x-layouts.app>
