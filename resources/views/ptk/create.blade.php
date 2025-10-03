<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">New PTK</h2>

  <form method="post" enctype="multipart/form-data" action="{{ route('ptk.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf

    <label for="number">Nomor
      <input id="number" type="text" name="number" class="border p-2 rounded w-full" required value="{{ old('number') }}">
      @error('number') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="title">Judul
      <input id="title" type="text" name="title" class="border p-2 rounded w-full" required value="{{ old('title') }}">
      @error('title') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="category_id">Kategori
      <select id="category_id" name="category_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('category_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Subkategori (dependent on Kategori) --}}
    <label for="subcat">Subkategori
      <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full" data-old="{{ old('subcategory_id') }}">
        <option value="">-- pilih subkategori --</option>
      </select>
      @error('subcategory_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="department_id">Departemen
      <select id="department_id" name="department_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($departments as $d)
          <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
        @endforeach
      </select>
      @error('department_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="pic_user_id">PIC
      <select id="pic_user_id" name="pic_user_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($users as $u)
          <option value="{{ $u->id }}" @selected(old('pic_user_id') == $u->id)>{{ $u->name }}</option>
        @endforeach
      </select>
      @error('pic_user_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="due_date">Due date
      <input id="due_date" type="date" name="due_date" class="border p-2 rounded w-full" required value="{{ old('due_date') }}">
      @error('due_date') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="description" class="md:col-span-2">Deskripsi
      <textarea id="description" name="description" rows="6" class="border p-2 rounded w-full" required>{{ old('description') }}</textarea>
      @error('description') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="attachments" class="md:col-span-2">Lampiran (multiple)
      <input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="border p-2 rounded w-full">
      @error('attachments.*') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <div class="md:col-span-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('ptk.index') }}" class="ml-2 underline">Batal</a>
    </div>
  </form>

  @push('scripts')
  <script>
    async function loadSubcats(catId, preselectedId = null){
      const sel = document.getElementById('subcat');
      sel.innerHTML = '<option value="">-- pilih subkategori --</option>';
      sel.disabled = true;

      if(!catId){
        sel.disabled = false;
        return;
      }

      try{
        const res = await fetch(`{{ route('api.subcategories') }}?category_id=${encodeURIComponent(catId)}`);
        if(!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();

        data.forEach(row => {
          const opt = document.createElement('option');
          opt.value = row.id;
          opt.textContent = row.name;
          if (preselectedId && String(preselectedId) === String(row.id)) {
            opt.selected = true;
          }
          sel.appendChild(opt);
        });
      }catch(e){
        // bisa tambahkan notifikasi bila perlu
        console.error(e);
      }finally{
        sel.disabled = false;
      }
    }

    // On change kategori
    document.querySelector('select[name="category_id"]').addEventListener('change', function(){
      loadSubcats(this.value);
    });

    // Saat halaman dimuat ulang (validasi gagal), preload subkategori lama
    (function preload(){
      const oldCategory = "{{ old('category_id') }}";
      const oldSubcat = document.getElementById('subcat').dataset.old || "";
      if(oldCategory){
        loadSubcats(oldCategory, oldSubcat);
      }
    })();
  </script>
  @endpush
</x-layouts.app>
