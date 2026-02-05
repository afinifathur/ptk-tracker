{{-- resources/views/ptk/index.blade.php --}}

<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Daftar PTK</h2>

    <div class="space-x-2">
      <a href="{{ route('ptk.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">New PTK</a>
      <a href="{{ route('exports.excel') }}" class="px-3 py-2 bg-green-600 text-white rounded">Export Excel</a>
      <button type="button" onclick="document.getElementById('imp').classList.toggle('hidden')"
        class="px-3 py-2 bg-amber-600 text-white rounded">Import</button>
    </div>
  </div>

  {{-- IMPORT AREA (toggle) --}}
  <div id="imp" class="hidden mb-4 p-4 bg-white dark:bg-gray-800 rounded">
    <form method="post" action="{{ route('ptk.import') }}" enctype="multipart/form-data"
      class="flex flex-col sm:flex-row sm:items-center sm:gap-3">
      @csrf
      <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="border p-2 rounded mb-2 sm:mb-0">
      <button type="submit" class="px-3 py-2 bg-amber-700 text-white rounded">Upload</button>

      <a class="mt-2 sm:mt-0 ml-0 sm:ml-4 underline text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
        href="{{ asset('storage/templates/ptk_import_template.csv') }}" target="_blank" rel="noopener">
        Download Template CSV
      </a>
    </form>
  </div>

  {{-- FILTER FORM (GET) --}}
  <form method="GET" action="{{ route('ptk.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4 items-center">
    {{-- Search free-text (judul / nomor / catatan) --}}
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul/nomor..."
      class="border p-2 rounded w-full">

    {{-- Role / Divisi filter (opsional) --}}
    <select name="role_filter" class="border p-2 rounded w-full">
      <option value="">-- Semua Divisi / Role Admin --</option>
      <option value="admin_qc_flange" @selected(request('role_filter') == 'admin_qc_flange')>Admin QC Flange</option>
      <option value="admin_qc_fitting" @selected(request('role_filter') == 'admin_qc_fitting')>Admin QC Fitting</option>
      <option value="admin_hr" @selected(request('role_filter') == 'admin_hr')>Admin HR</option>
      <option value="admin_k3" @selected(request('role_filter') == 'admin_k3')>Admin K3</option>
      <option value="admin_mtc" @selected(request('role_filter') == 'admin_mtc')>Admin MTC</option>
    </select>

    {{-- Status filter --}}
    <div class="flex items-center gap-2">
      <select name="status" class="border p-2 rounded flex-1">
        <option value="">-- Status (optional) --</option>
        <option value="Not Started" @selected(request('status') == 'Not Started')>Not Started</option>
        <option value="In Progress" @selected(request('status') == 'In Progress')>In Progress</option>
        <option value="Submitted" @selected(request('status') == 'Submitted')>Submitted</option>
        <option value="Waiting Director" @selected(request('status') == 'Waiting Director')>Waiting Director</option>
        <option value="Completed" @selected(request('status') == 'Completed')>Completed</option>
      </select>

      <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded">Filter</button>
    </div>
  </form>

  {{-- TABLE --}}
  <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded">
    <table id="ptkTable" class="display stripe hover order-column w-full text-sm js-dt">
      <thead>
        <tr>
          <th>Nomor</th>
          <th>Tanggal</th>
          <th>Judul</th>
          <th>PIC</th>
          <th>Departemen</th>
          <th>Kategori</th>
          <th>Subkategori</th>
          <th>Status</th>
          <th>Due</th>
        </tr>
      </thead>
      <tbody>
        @forelse($ptks as $p)
          <tr>
            {{-- Nomor --}}
            <td class="whitespace-nowrap px-2 py-3">{{ $p->number ?? '—' }}</td>

            {{-- Tanggal --}}
            <td class="whitespace-nowrap px-2 py-3">{{ optional($p->created_at)->format('Y-m-d') ?? '-' }}</td>

            {{-- Judul --}}
            <td class="truncate max-w-[280px] px-2 py-3">
              <a href="{{ route('ptk.show', $p) }}"
                class="font-medium block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-gray-50 underline decoration-gray-300 hover:decoration-gray-400">
                {{ $p->title }}
              </a>
            </td>

            <td class="px-2 py-3">{{ $p->pic->name ?? '-' }}</td>
            <td class="px-2 py-3">{{ $p->department->name ?? '-' }}</td>

            {{-- Kategori --}}
            <td class="px-2 py-3">
              @php
                $catName = $p->category->name ?? '-';
                $badgeClass = match ($catName) {
                  'Efisiensi' => 'bg-emerald-200 text-emerald-900 dark:bg-emerald-700 dark:text-white',
                  'Uji Coba' => 'bg-blue-200 text-blue-900 dark:bg-blue-700 dark:text-white',
                  default => null,
                };
              @endphp

              @if($badgeClass)
                <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $badgeClass }}">
                  {{ $catName }}
                </span>
              @else
                {{ $catName }}
              @endif
            </td>

            {{-- Subkategori --}}
            <td class="px-2 py-3">
              {{ $p->subcategory->name ?? '-' }}
            </td>

            {{-- Status badge --}}
            <td class="px-2 py-3">
              @php $s = $p->status; @endphp
              <x-ui.stat-badge :status="$s" />
            </td>

            {{-- Due --}}
            <td class="whitespace-nowrap px-2 py-3">{{ optional($p->due_date)->format('Y-m-d') ?? '-' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center text-gray-500 py-4">Belum ada data PTK.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div class="mt-4">{{ $ptks->links() }}</div>

  {{-- DataTables init (pastikan jQuery & DataTables sudah dimuat di layout) --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // jika DataTable sudah di-init sebelumnya, destroy dulu (hindari double-init)
      if ($.fn.dataTable.isDataTable('#ptkTable')) {
        $('#ptkTable').DataTable().destroy();
      }

      $('#ptkTable').DataTable({
        pageLength: 10,
        order: [[1, 'desc']],
        responsive: true,
        language: { search: "_INPUT_", searchPlaceholder: "Cari PTK..." },

      });
    });
  </script>
</x-layouts.app>