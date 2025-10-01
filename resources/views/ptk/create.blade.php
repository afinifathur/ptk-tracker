<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">New PTK</h2>
  <form method="post" enctype="multipart/form-data" action="{{ route('ptk.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">@csrf
    <label>Nomor
      <input type="text" name="number" class="border p-2 rounded w-full" required value="{{ old('number') }}">
    </label>
    <label>Judul
      <input type="text" name="title" class="border p-2 rounded w-full" required value="{{ old('title') }}">
    </label>
    <label>Kategori
      <select name="category_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($categories as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
      </select>
    </label>
    <label>Departemen
      <select name="department_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($departments as $d) <option value="{{ $d->id }}">{{ $d->name }}</option> @endforeach
      </select>
    </label>
    <label>PIC
      <select name="pic_user_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($users as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
      </select>
    </label>
    <label>Due date
      <input type="date" name="due_date" class="border p-2 rounded w-full" required value="{{ old('due_date') }}">
    </label>
    <label class="md:col-span-2">Deskripsi
      <textarea name="description" rows="6" class="border p-2 rounded w-full" required>{{ old('description') }}</textarea>
    </label>
    <label class="md:col-span-2">Lampiran (multiple)
      <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="border p-2 rounded w-full">
    </label>

    <div class="md:col-span-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('ptk.index') }}" class="ml-2 underline">Batal</a>
    </div>
  </form>
</x-layouts.app>
