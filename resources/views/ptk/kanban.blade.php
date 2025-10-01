<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Kanban PTK</h2>
    <a href="{{ route('ptk.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">New PTK</a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach($cols as $col)
      <div class="p-3 rounded-xl bg-white dark:bg-gray-800 border">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">{{ $col }}</h3>
          <span class="text-xs text-gray-500">{{ ($items[$col] ?? collect())->count() }}</span>
        </div>
        <div class="min-h-[200px] space-y-2 kan-col" data-status="{{ $col }}">
          @foreach(($items[$col] ?? collect()) as $p)
            <div class="p-3 rounded-lg border bg-gray-50 dark:bg-gray-900 draggable"
                 data-id="{{ $p->id }}">
              <div class="text-sm font-semibold truncate">{{ $p->number }} — {{ $p->title }}</div>
              <div class="text-xs text-gray-500">
                {{ $p->department->name ?? '-' }} — {{ $p->pic->name ?? '-' }}
              </div>
              <div class="text-xs">{{ optional($p->due_date)->format('Y-m-d') }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>

  <script>
    // Drag & drop via SortableJS (sudah kamu load di layout)
    document.querySelectorAll('.kan-col').forEach(col => {
      new Sortable(col, {
        group: 'ptk',
        animation: 150,
        onAdd: async (evt) => {
          const card   = evt.item;
          const id     = card.dataset.id;
          const status = evt.to.dataset.status;

          try {
            const r = await fetch(`{{ url('ptk') }}/${id}/status`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({ status })
            });
            if (!r.ok) throw new Error('Gagal update status');
          } catch (e) {
            alert(e.message || 'Update gagal');
            // rollback: pindahkan balik
            evt.from.appendChild(card);
          }
        }
      });
    });
  </script>
</x-layouts.app>
