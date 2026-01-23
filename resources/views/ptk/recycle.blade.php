<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Recycle Bin</h2>
    <a href="{{ route('ptk.index') }}"
      class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-100 underline decoration-gray-300 hover:decoration-gray-400">
      ← Kembali ke daftar
    </a>
  </div>

  <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded shadow-sm">
    <table id="recycleLogTable" class="display stripe hover order-column w-full text-sm">
      <thead>
        <tr>
          <th>Nomor</th>
          <th>Judul</th>
          <th>Dept</th>
          <th>Dihapus</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $p)
          <tr>
            <td>{{ $p->number }}</td>
            <td class="truncate max-w-[340px] text-gray-800 dark:text-gray-100">
              {{ $p->title }}
            </td>
            <td>{{ optional($p->department)->name ?? '-' }}</td>
            <td>{{ optional($p->deleted_at)->format('Y-m-d H:i') }}</td>
            <td class="space-x-2 whitespace-nowrap">
              <form class="inline" method="post" action="{{ route('ptk.restore', $p->id) }}">
                @csrf
                <button class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Restore</button>
              </form>
              <form class="inline" method="post" action="{{ route('ptk.force', $p->id) }}">
                @csrf
                @method('DELETE')
                <button class="px-2 py-1 bg-rose-700 text-white rounded hover:bg-rose-800"
                  onclick="return confirm('Hapus permanen?')">
                  Hapus Permanen
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $items->links() }}</div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Check/Destroy existing to prevent errors
      if ($.fn.dataTable.isDataTable('#recycleLogTable')) {
        $('#recycleLogTable').DataTable().destroy();
      }

      // Initialize
      $('#recycleLogTable').DataTable({
        pageLength: 20,
        paging: false,   // Layout uses Laravel pagination
        info: false,     // Layout uses Laravel pagination
        searching: true, // Let user search visible rows if they want, or set to false. User said "zebra style" mainly. Keep default or simple.
        // Actually approval-log had searching: false.
        // But recycle bin has no server-side search form visible in the snippet?
        // The screenshot does not show a search bar.
        // I will set searching: true for convenience on the client side, or false if it looks cluttered.
        // Let's stick to matching approval-log simpler style: searching: false
        searching: false,
        ordering: true,
        order: [[3, 'desc']], // Default sort by Deleted At
        language: {
          zeroRecords: "Tidak ada data",
          emptyTable: "Recycle bin kosong"
        },
        columnDefs: [{ targets: -1, orderable: false }]
      });
    });
  </script>
</x-layouts.app>