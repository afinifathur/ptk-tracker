<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">Edit PTK {{ $ptk->number }}</h2>
  <form method="post" enctype="multipart/form-data" action="{{ route('ptk.update',$ptk) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf @method('PUT')
    <label>Judul
      <input type="text" name="title" class="border p-2 rounded w-full" required value="{{ old('title',$ptk->title) }}">
    </label>
    <label>Status
      <select name="status" class="border p-2 rounded w-full" required>
        @foreach(['Not Started','In Progress','Completed'] as $s)
          <option value="{{ $s }}" @selected($ptk->status==$s)>{{ $s }}</option>
        @endforeach
      </select>
    </label>
    <label>Kategori
      <select name="category_id" class="border p-2 rounded w-full" required>
        @foreach($categories as $c) <option value="{{ $c->id }}" @selected($ptk->category_id==$c->id)>{{ $c->name }}</option> @endforeach
      </select>
    </label>
    <label>Departemen
      <select name="department_id" class="border p-2 rounded w-full" required>
        @foreach($departments as $d) <option value="{{ $d->id }}" @selected($ptk->department_id==$d->id)>{{ $d->name }}</option> @endforeach
      </select>
    </label>
    <label>PIC
      <select name="pic_user_id" class="border p-2 rounded w-full" required>
        @foreach($users as $u) <option value="{{ $u->id }}" @selected($ptk->pic_user_id==$u->id)>{{ $u->name }}</option> @endforeach
      </select>
    </label>
    <label>Due date
      <input type="date" name="due_date" class="border p-2 rounded w-full" required value="{{ old('due_date', optional($ptk->due_date)->format('Y-m-d')) }}">
    </label>
    <label class="md:col-span-2">Deskripsi
      <textarea name="description" rows="6" class="border p-2 rounded w-full" required>{{ old('description',$ptk->description) }}</textarea>
    </label>
    <label class="md:col-span-2">Lampiran (tambah)
      <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="border p-2 rounded w-full">
    </label>

    <div class="md:col-span-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('ptk.show',$ptk) }}" class="ml-2 underline">Batal</a>
    </div>
  </form>
</x-layouts.app>
