<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Daftar PTK</h2>
    <div class="space-x-2">
      <a href="{{ route('ptk.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">New PTK</a>
      <a href="{{ route('exports.excel') }}" class="px-3 py-2 bg-green-600 text-white rounded">Export Excel</a>
      <button type="button" onclick="document.getElementById('imp').classList.toggle('hidden')" class="px-3 py-2 bg-amber-600 text-white rounded">Import</button>
    </div>
  </div>

  <div id="imp" class="hidden mb-4 p-4 bg-white dark:bg-gray-800 rounded">
    <form method="post" action="{{ route('ptk.import') }}" enctype="multipart/form-data" class="flex items-center gap-3">
      @csrf
      <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="border p-2 rounded">
      <button class="px-3 py-2 bg-amber-700 text-white rounded">Upload</button>
      <a class="underline text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
         href="{{ asset('storage/templates/ptk_import_template.csv') }}" target="_blank">
        Download Template CSV
      </a>
    </form>
  </div>

  <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
    <input class="border p-2 rounded" type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul/nomor...">
    <select class="border p-2 rounded" name="status">
      <option value="">-- Status --</option>
      @foreach(['Not Started','In Progress','Completed'] as $s)
        <option value="{{ $s }}" @selected(request('status')==$s)>{{ $s }}</option>
      @endforeach
    </select>
    <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
  </form>

  <table id="ptkTable" class="display stripe hover order-column w-full text-sm js-dt">
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Tanggal</th>
        <th>Judul</th>
        <th>PIC</th>
        <th>Departemen</th>
        <th>Kategori</th>
        <th>Status</th>
        <th>Due</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ptks as $p)
        <tr>
          {{-- Nomor --}}
          <td>{{ $p->number ?? '—' }}</td>

          {{-- Tanggal --}}
          <td class="whitespace-nowrap">{{ optional($p->created_at)->format('Y-m-d') }}</td>

          {{-- Judul (link ke detail, abu-abu elegan) --}}
          <td class="truncate max-w-[280px]">
            <a href="{{ route('ptk.show', $p) }}"
               class="font-medium block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-gray-50 underline decoration-gray-300 hover:decoration-gray-400">
              {{ $p->title }}
            </a>
          </td>

          <td>{{ $p->pic->name ?? '-' }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>

          {{-- Kategori / Subkategori --}}
          <td>
            {{ $p->category->name ?? '-' }}
            @if($p->subcategory)
              / <span class="text-xs text-gray-500">{{ $p->subcategory->name }}</span>
            @endif
          </td>

          <td>
            @php $s = $p->status; @endphp
            <x-ui.stat-badge :status="$s" />
          </td>

          {{-- Due --}}
          <td class="whitespace-nowrap">{{ optional($p->due_date)->format('Y-m-d') }}</td>

          {{-- Aksi (link abu-abu netral) --}}
          <td class="space-x-2">
            <a href="{{ route('ptk.edit', $p) }}"
               class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-100 underline decoration-gray-300 hover:decoration-gray-400">
              Edit
            </a>
            <a href="{{ route('exports.pdf', $p) }}"
               class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-100 underline decoration-gray-300 hover:decoration-gray-400">
              PDF
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="text-center text-gray-500 py-4">Belum ada data PTK.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="mt-4">{{ $ptks->links() }}</div>

  <script>
    $(function(){
      $('#ptkTable').DataTable({
        pageLength: 10,
        order: [[1,'desc']],
        language: { search: "_INPUT_", searchPlaceholder: "Cari PTK..." },
        columnDefs: [{ targets: -1, orderable:false }]
      });
    });
  </script>
</x-layouts.app>
