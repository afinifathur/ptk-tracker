<x-layouts.app>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">Settings: Kategori & Subkategori</h2>
    <a href="{{ route('exports.audits.index') }}" class="underline">Audit Log</a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Tambah Kategori --}}
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <h3 class="font-semibold mb-3">Tambah Kategori</h3>
      <form method="post" action="{{ route('settings.categories.store') }}" class="flex gap-2">@csrf
        <input name="name" class="border p-2 rounded w-full" placeholder="Nama kategori..." required>
        <button class="px-3 py-2 bg-blue-600 text-white rounded">Tambah</button>
      </form>
    </div>

    {{-- Tambah Subkategori --}}
    <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border">
      <h3 class="font-semibold mb-3">Tambah Subkategori</h3>
      <form method="post" action="{{ route('settings.subcategories.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-2">@csrf
        <select name="category_id" class="border p-2 rounded" required>
          <option value="">Pilih kategori</option>
          @foreach($cats as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
        </select>
        <input name="name" class="border p-2 rounded md:col-span-2" placeholder="Nama subkategori..." required>
        <div class="md:col-span-3">
          <button class="px-3 py-2 bg-amber-600 text-white rounded">Tambah</button>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-6 p-4 rounded-xl bg-white dark:bg-gray-800 border">
    <h3 class="font-semibold mb-3">Daftar Kategori & Subkategori</h3>
    <div class="grid md:grid-cols-2 gap-6">
      @forelse($cats as $c)
        <div class="border rounded-lg p-3">
          <div class="flex items-center justify-between">
            <strong>{{ $c->name }}</strong>
            <div class="space-x-2">
              <form method="post" action="{{ route('settings.categories.update',$c) }}" class="inline">@csrf @method('PATCH')
                <input name="name" value="{{ $c->name }}" class="border p-1 rounded">
                <button class="px-2 py-1 text-xs bg-gray-800 text-white rounded">Rename</button>
              </form>
              <form method="post" action="{{ route('settings.categories.delete',$c) }}" class="inline" onsubmit="return confirm('Hapus kategori & semua subkategori?')">@csrf @method('DELETE')
                <button class="px-2 py-1 text-xs bg-rose-700 text-white rounded">Delete</button>
              </form>
            </div>
          </div>
          <ul class="mt-2 list-disc ml-5 space-y-1">
            @forelse($c->subcategories as $s)
              <li class="flex items-center justify-between">
                <span>{{ $s->name }}</span>
                <span class="space-x-1">
                  <form method="post" action="{{ route('settings.subcategories.update',$s) }}" class="inline">@csrf @method('PATCH')
                    <input type="hidden" name="category_id" value="{{ $c->id }}">
                    <input name="name" value="{{ $s->name }}" class="border p-1 rounded">
                    <button class="px-2 py-1 text-xs bg-gray-700 text-white rounded">Rename</button>
                  </form>
                  <form method="post" action="{{ route('settings.subcategories.delete',$s) }}" class="inline" onsubmit="return confirm('Hapus subkategori?')">@csrf @method('DELETE')
                    <button class="px-2 py-1 text-xs bg-rose-700 text-white rounded">Delete</button>
                  </form>
                </span>
              </li>
            @empty
              <li class="text-gray-500">Belum ada subkategori</li>
            @endforelse
          </ul>
        </div>
      @empty
        <div class="text-gray-500">Belum ada kategori.</div>
      @endforelse
    </div>
  </div>
</x-layouts.app>
