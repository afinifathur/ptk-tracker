{{-- resources/views/ptk/show.blade.php --}}
<x-layouts.app>
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
    <h2 class="text-xl font-semibold">
      PTK {{ $ptk->number }}
    </h2>

    <div class="flex flex-wrap items-center gap-2">
      @can('update', $ptk)
        <a href="{{ route('ptk.edit', $ptk) }}"
           class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Edit
        </a>
      @endcan

      {{-- Tombol Download PDF --}}
      <a href="{{ route('exports.pdf', $ptk) }}"
         class="px-3 py-2 bg-gray-800 text-white rounded hover:bg-gray-900">
        Download PDF
      </a>

      @can('delete', $ptk)
        <form method="post" action="{{ route('ptk.destroy', $ptk) }}" class="inline"
              onsubmit="return confirm('Yakin menghapus PTK ini?');">
          @csrf
          @method('DELETE')
          <button type="submit"
                  class="px-3 py-2 bg-rose-600 text-white rounded hover:bg-rose-700">
            Delete
          </button>
        </form>
      @endcan
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Kolom kiri: ringkasan PTK --}}
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
      <div class="text-sm text-gray-500 dark:text-gray-400">Judul</div>
      <div class="font-medium">{{ $ptk->title }}</div>

      <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">Status</div>
      <x-ui.stat-badge :status="$ptk->status" />

      <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">Kategori / Departemen</div>
      <div>
        {{ $ptk->category->name ?? '-' }}
        @if($ptk->subcategory) / {{ $ptk->subcategory->name }} @endif
        / {{ $ptk->department->name ?? '-' }}
      </div>

      <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">PIC</div>
      <div>{{ $ptk->pic->name ?? '-' }}</div>

      <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">Due / Approved</div>
      <div>
        {{ optional($ptk->due_date)->format('Y-m-d') ?? '-' }}
        /
        {{ optional($ptk->approved_at)->format('Y-m-d') ?? '-' }}
      </div>
    </div>

    {{-- Kolom kanan: deskripsi & lampiran --}}
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
      <div class="text-sm text-gray-500 dark:text-gray-400">Deskripsi</div>
      <div class="whitespace-pre-wrap">{{ $ptk->description }}</div>

      <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">Lampiran</div>

      <ul class="list-disc ml-5 space-y-4">
        @forelse($ptk->attachments as $a)
          @php
            $url  = asset('storage/' . $a->path);
            $isImg = isset($a->mime) && str_starts_with(strtolower($a->mime), 'image/');
          @endphp
          <li class="space-y-1">
            <div class="flex items-start gap-3">
              <a class="underline hover:no-underline break-all" href="{{ $url }}" target="_blank" rel="noopener">
                {{ $a->original_name ?? basename($a->path) }}
              </a>
              @if($isImg)
                <a href="{{ $url }}" target="_blank" rel="noopener" class="shrink-0">
                  <img src="{{ $url }}" alt="preview"
                       class="ml-1 mt-0.5 h-10 w-10 object-cover rounded border border-gray-200 dark:border-gray-700" />
                </a>
              @endif
            </div>

            {{-- 🔽 Form caption kecil --}}
            <div>
              <form method="post" action="{{ route('attachments.caption', $a) }}" class="flex gap-2 items-center mt-1">
                @csrf
                @method('PATCH')
                <input name="caption"
                       value="{{ old('caption', $a->caption) }}"
                       class="border p-1 rounded w-64 text-sm"
                       placeholder="Keterangan foto (opsional)">
                <button class="px-2 py-1 text-sm bg-gray-800 text-white rounded hover:bg-gray-900">Simpan</button>
              </form>
              @if($a->caption)
                <div class="text-xs text-gray-500 mt-1">Keterangan: {{ $a->caption }}</div>
              @endif
            </div>
          </li>
        @empty
          <li>Tidak ada lampiran</li>
        @endforelse
      </ul>
    </div>
  </div>
</x-layouts.app>
