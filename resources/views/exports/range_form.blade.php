<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">Laporan Periode</h2>

  <form method="post" action="{{ route('exports.range.report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
    @csrf
    <label class="block">Start
      <input type="date" name="start" class="border p-2 rounded w-full" required value="{{ old('start') }}">
    </label>
    <label class="block">End
      <input type="date" name="end" class="border p-2 rounded w-full" required value="{{ old('end') }}">
    </label>
    <label class="block">Kategori
      <select name="category_id" class="border p-2 rounded w-full">
        <option value="">Semua</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </label>
    <label class="block">Departemen
      <select name="department_id" class="border p-2 rounded w-full">
        <option value="">Semua</option>
        @foreach($departments as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>
    </label>
    <label class="block">Status
      <select name="status" class="border p-2 rounded w-full">
        <option value="">Semua</option>
        @foreach(['Not Started','In Progress','Completed'] as $s)
          <option value="{{ $s }}">{{ $s }}</option>
        @endforeach
      </select>
    </label>

    <div class="md:col-span-5">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Generate</button>
    </div>
  </form>
</x-layouts.app>
