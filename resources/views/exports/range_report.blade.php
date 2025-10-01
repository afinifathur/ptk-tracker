<x-layouts.app>
  <div class="flex items-center justify-between mb-3">
    <h2 class="text-xl font-semibold">Laporan {{ $data['start'] }} s.d. {{ $data['end'] }}</h2>
    <div class="space-x-2">
      <form method="post" action="{{ route('exports.range.excel') }}" class="inline">@csrf
        <input type="hidden" name="start" value="{{ $data['start'] }}">
        <input type="hidden" name="end" value="{{ $data['end'] }}">
        <button class="px-3 py-2 bg-green-600 text-white rounded">Export Excel</button>
      </form>
      <form method="post" action="{{ route('exports.range.pdf') }}" class="inline">@csrf
        <input type="hidden" name="start" value="{{ $data['start'] }}">
        <input type="hidden" name="end" value="{{ $data['end'] }}">
        <button class="px-3 py-2 bg-rose-600 text-white rounded">Export PDF</button>
      </form>
    </div>
  </div>

  <table class="display w-full">
    <thead><tr><th>Nomor</th><th>Judul</th><th>PIC</th><th>Dept</th><th>Status</th><th>Due</th></tr></thead>
    <tbody>
      @foreach($items as $p)
        <tr>
          <td>{{ $p->number }}</td>
          <td>{{ $p->title }}</td>
          <td>{{ $p->pic->name ?? '-' }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <script> $(function(){ $('table.display').DataTable(); }); </script>
</x-layouts.app>
