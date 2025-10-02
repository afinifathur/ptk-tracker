<x-layouts.app>
  <div class="flex items-center justify-between mb-3">
    <div>
      <h2 class="text-xl font-semibold">Laporan {{ $data['start'] }} s.d. {{ $data['end'] }}</h2>
      <div class="text-sm text-gray-500">
        Filter:
        @php
          $cat = $categories->firstWhere('id', $data['category_id'] ?? null)?->name ?? 'Semua';
          $dep = $departments->firstWhere('id', $data['department_id'] ?? null)?->name ?? 'Semua';
          $sts = $data['status'] ?? 'Semua';
        @endphp
        Kategori <strong>{{ $cat }}</strong> · Departemen <strong>{{ $dep }}</strong> · Status <strong>{{ $sts }}</strong>
      </div>
    </div>
    <div class="space-x-2">
      <form method="post" action="{{ route('exports.range.excel') }}" class="inline">@csrf
        <input type="hidden" name="start" value="{{ $data['start'] }}">
        <input type="hidden" name="end" value="{{ $data['end'] }}">
        <input type="hidden" name="category_id" value="{{ $data['category_id'] ?? '' }}">
        <input type="hidden" name="department_id" value="{{ $data['department_id'] ?? '' }}">
        <input type="hidden" name="status" value="{{ $data['status'] ?? '' }}">
        <button class="px-3 py-2 bg-green-600 text-white rounded">Export Excel</button>
      </form>
      <form method="post" action="{{ route('exports.range.pdf') }}" class="inline">@csrf
        <input type="hidden" name="start" value="{{ $data['start'] }}">
        <input type="hidden" name="end" value="{{ $data['end'] }}">
        <input type="hidden" name="category_id" value="{{ $data['category_id'] ?? '' }}">
        <input type="hidden" name="department_id" value="{{ $data['department_id'] ?? '' }}">
        <input type="hidden" name="status" value="{{ $data['status'] ?? '' }}">
        <button class="px-3 py-2 bg-rose-600 text-white rounded">Export PDF</button>
      </form>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <div class="text-sm text-gray-500">Total PTK</div>
      <div class="text-3xl font-bold">{{ $items->count() }}</div>
    </div>
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <div class="text-sm text-gray-500">SLA Compliance</div>
      <div class="text-3xl font-bold">{{ $sla }}%</div>
    </div>
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <div class="text-sm text-gray-500">Overdue (Not Started/In Progress)</div>
      <div class="text-3xl font-bold">{{ $overdue }}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <h3 class="font-semibold mb-2">Top 3 Kategori</h3>
      <ol class="list-decimal ml-5 space-y-1">
        @forelse($topCategories as $row)
          <li class="flex justify-between"><span>{{ $row['name'] }}</span> <span class="font-semibold">{{ $row['total'] }}</span></li>
        @empty
          <li class="text-gray-500">Tidak ada data</li>
        @endforelse
      </ol>
    </div>
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <h3 class="font-semibold mb-2">Top 3 Departemen</h3>
      <ol class="list-decimal ml-5 space-y-1">
        @forelse($topDepartments as $row)
          <li class="flex justify-between"><span>{{ $row['name'] }}</span> <span class="font-semibold">{{ $row['total'] }}</span></li>
        @empty
          <li class="text-gray-500">Tidak ada data</li>
        @endforelse
      </ol>
    </div>
  </div>

  <table class="display w-full text-sm">
    <thead>
      <tr>
        <th>Nomor</th><th>Judul</th><th>Kategori</th><th>Departemen</th><th>PIC</th><th>Status</th><th>Due</th><th>Dibuat</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $p)
        <tr>
          <td>{{ $p->number }}</td>
          <td class="truncate max-w-[320px]">{{ $p->title }}</td>
          <td>{{ $p->category->name ?? '-' }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->pic->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
          <td>{{ $p->created_at->format('Y-m-d') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <script> $(function(){ $('table.display').DataTable({ pageLength: 25 }); }); </script>
</x-layouts.app>
