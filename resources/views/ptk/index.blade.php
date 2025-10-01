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
      <a class="underline" href="{{ asset('storage/templates/ptk_import_template.csv') }}" target="_blank">Download Template CSV</a>
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

  <table id="ptkTable" class="display w-full text-sm">
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Tanggal</th>
        <th>Judul</th>
        <th>PIC</th>
        <th>Departemen</th>
        <th>Status</th>
        <th>Due</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ptk as $p)
        <tr>
          <td><a class="underline" href="{{ route('ptk.show',$p) }}">{{ $p->number }}</a></td>
          <td>{{ $p->created_at->format('Y-m-d') }}</td>
          <td class="truncate max-w-[280px]">{{ $p->title }}</td>
          <td>{{ $p->pic->name ?? '-' }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>
            @php $s=$p->status; @endphp
            <x-ui.stat-badge :status="$s" />
          </td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
          <td class="space-x-2">
            <a href="{{ route('ptk.edit',$p) }}" class="text-blue-600 underline">Edit</a>
            <a href="{{ route('exports.pdf',$p) }}" class="text-gray-700 underline">PDF</a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt-4">{{ $ptk->links() }}</div>

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
