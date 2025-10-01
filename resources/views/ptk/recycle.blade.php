<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Recycle Bin</h2>
    <a href="{{ route('ptk.index') }}" class="underline">← Kembali ke daftar</a>
  </div>

  <table class="display w-full text-sm">
    <thead>
      <tr>
        <th>Nomor</th><th>Judul</th><th>Dept</th><th>Dihapus</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $p)
        <tr>
          <td>{{ $p->number }}</td>
          <td class="truncate max-w-[340px]">{{ $p->title }}</td>
          <td>{{ optional($p->department)->name ?? '-' }}</td>
          <td>{{ optional($p->deleted_at)->format('Y-m-d H:i') }}</td>
          <td class="space-x-2">
            <form class="inline" method="post" action="{{ route('ptk.restore',$p->id) }}">@csrf
              <button class="px-2 py-1 bg-blue-600 text-white rounded">Restore</button>
            </form>
            <form class="inline" method="post" action="{{ route('ptk.force',$p->id) }}">@csrf @method('DELETE')
              <button class="px-2 py-1 bg-rose-700 text-white rounded" onclick="return confirm('Hapus permanen?')">Hapus Permanen</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt-4">{{ $items->links() }}</div>

  <script> $(function(){ $('table.display').DataTable({ pageLength: 20 }); }); </script>
</x-layouts.app>
