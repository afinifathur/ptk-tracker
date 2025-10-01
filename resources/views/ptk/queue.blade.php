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
        <th>Nomor</th><th>Judul</th><th>PIC</th><th>Dept</th><th>Status</th><th>Due</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $p)
        <tr>
          <td><a class="underline" href="{{ route('ptk.show',$p) }}">{{ $p->number }}</a></td>
          <td class="truncate max-w-[320px]">{{ $p->title }}</td>
          <td>{{ $p->pic->name ?? '-' }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
          <td class="space-x-2">
            <form class="inline" method="post" action="{{ route('ptk.approve',$p) }}">@csrf
              <button class="px-2 py-1 bg-emerald-600 text-white rounded">Approve</button>
            </form>
            <form class="inline" method="post" action="{{ route('ptk.reject',$p) }}">@csrf
              <input type="hidden" name="reason" value="Revisi">
              <button class="px-2 py-1 bg-rose-600 text-white rounded">Reject</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt-4">{{ $items->links() }}</div>

  <script> $(function(){ $('table.display').DataTable({ pageLength: 20 }); }); </script>
</x-layouts.app>
