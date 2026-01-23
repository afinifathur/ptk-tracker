<x-layouts.app>

  <h2 class="text-xl font-semibold mb-4">Approval Log</h2>

  {{-- ================= FILTER BAR (FINAL & SEJAJAR) ================= --}}
  <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 items-end">

    {{-- Search --}}
    <div>
      <label class="text-xs text-gray-500">Cari (PTK / Alasan)</label>
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor / alasan reject…" class="w-full border rounded px-3 py-2
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100">
    </div>

    {{-- User --}}
    <div>
      <label class="text-xs text-gray-500">Oleh</label>
      <select name="user_id" class="w-full border rounded px-3 py-2
                     bg-white dark:bg-gray-800
                     text-gray-900 dark:text-gray-100">
        <option value="">— Semua User —</option>
        @foreach($users as $u)
          <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
            {{ $u->name }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- Action --}}
    <div>
      <label class="text-xs text-gray-500">Aksi</label>
      <select name="action" class="w-full border rounded px-3 py-2
                     bg-white dark:bg-gray-800
                     text-gray-900 dark:text-gray-100">
        <option value="">— Semua —</option>
        <option value="stage1" @selected(request('action') == 'stage1')>
          Reject Stage 1
        </option>
        <option value="stage2" @selected(request('action') == 'stage2')>
          Reject Stage 2
        </option>
      </select>
    </div>

    {{-- Submit (WARNA ASLI, POSISI BENAR) --}}
    <button type="submit" class="w-full px-4 py-2
                   bg-gray-800 hover:bg-gray-700
                   text-white rounded">
      Filter
    </button>
  </form>

  {{-- ================= TABLE ================= --}}
  <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded">
    <table id="approvalLogTable" class="display stripe hover order-column w-full text-sm">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>PTK</th>
          <th>Aksi</th>
          <th>Oleh</th>
          <th>Keterangan</th>
          <th>IP</th>
        </tr>
      </thead>

      <tbody>
        @forelse($logs as $log)
          <tr>

            {{-- Waktu --}}
            <td>
              {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}
            </td>

            {{-- PTK (LINK KE DETAIL) --}}
            <td class="font-medium">
              @if($log->auditable)
                <a href="{{ route('ptk.show', $log->auditable->id) }}"
                  class="text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-gray-50 underline decoration-gray-300 hover:decoration-gray-400">
                  {{ $log->auditable->number }}
                </a>
              @else
                —
              @endif
            </td>

            {{-- AKSI --}}
            <td class="font-medium">
              @if(data_get($log->new_values, 'last_reject_stage') === 'stage1')
                Reject Stage 1
              @elseif(data_get($log->new_values, 'last_reject_stage') === 'stage2')
                Reject Stage 2
              @elseif(data_get($log->properties, 'stage') === 'stage1')
                Approve Stage 1
              @elseif(data_get($log->properties, 'stage') === 'stage2')
                Approve Stage 2
              @else
                —
              @endif
            </td>

            {{-- OLEH --}}
            <td>
              {{ optional($log->user)->name ?? 'System' }}
            </td>

            {{-- KETERANGAN --}}
            <td class="max-w-[400px] whitespace-normal break-words">
              @if(data_get($log->new_values, 'last_reject_stage'))
                {{ data_get($log->new_values, 'last_reject_reason') }}
              @else
                —
              @endif
            </td>

            {{-- IP --}}
            <td>
              {{ $log->ip_address ?? '-' }}
            </td>

          </tr>
        @empty
          {{-- DataTables handles empty state gracefully usually, but we keep this for server-side empty check --}}
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ================= PAGINATION ================= --}}
  <div class="mt-4">
    {{ $logs->withQueryString()->links() }}
  </div>

  {{-- DataTables Init --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if ($.fn.dataTable.isDataTable('#approvalLogTable')) {
        $('#approvalLogTable').DataTable().destroy();
      }

      $('#approvalLogTable').DataTable({
        pageLength: 20,
        paging: false, // We use server-side pagination links
        info: false,   // Hide "Showing 1 to 20 of X" since pagination does it
        searching: false, // Hide search box (we have custom filter)
        ordering: true, // Allow sorting visible page
        order: [], // Default no extra sort (server already sorted by time)
        language: {
          zeroRecords: "Tidak ada data yang cocok",
          emptyTable: "Tidak ada data log tersedia"
        }
      });
    });
  </script>

</x-layouts.app>