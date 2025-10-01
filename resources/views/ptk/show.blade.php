<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">PTK {{ $ptk->number }}</h2>
    <div class="space-x-2">
      <a href="{{ route('ptk.edit', $ptk) }}" class="px-3 py-2 bg-blue-600 text-white rounded">Edit</a>

      <a href="{{ route('exports.pdf', $ptk) }}" class="px-3 py-2 bg-gray-700 text-white rounded">Unduh PDF</a>

      <form method="post" action="{{ route('ptk.destroy', $ptk) }}" class="inline">
        @csrf
        @method('DELETE')
        <button class="px-3 py-2 bg-rose-600 text-white rounded" onclick="return confirm('Hapus?')">Delete</button>
      </form>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <div class="text-sm text-gray-500">Judul</div>
      <div class="font-medium">{{ $ptk->title }}</div>

      <div class="mt-3 text-sm text-gray-500">Status</div>
      <x-ui.stat-badge :status="$ptk->status" />

      <div class="mt-3 text-sm text-gray-500">Kategori / Departemen</div>
      <div>{{ $ptk->category->name ?? '-' }} / {{ $ptk->department->name ?? '-' }}</div>

      <div class="mt-3 text-sm text-gray-500">PIC</div>
      <div>{{ $ptk->pic->name ?? '-' }}</div>

      <div class="mt-3 text-sm text-gray-500">Due / Approved</div>
      <div>{{ optional($ptk->due_date)->format('Y-m-d') }} / {{ optional($ptk->approved_at)->format('Y-m-d') }}</div>
    </div>

    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <div class="text-sm text-gray-500">Deskripsi</div>
      <div class="whitespace-pre-wrap">{{ $ptk->description }}</div>

      <div class="mt-4 text-sm text-gray-500">Lampiran</div>
      <ul class="list-disc ml-5">
        @forelse($ptk->attachments as $a)
          <li>
            <a class="underline" href="{{ asset('storage/'.$a->path) }}" target="_blank">
              {{ $a->original_name }}
            </a>
          </li>
        @empty
          <li>Tidak ada lampiran</li>
        @endforelse
      </ul>
    </div>
  </div>
</x-layouts.app>
