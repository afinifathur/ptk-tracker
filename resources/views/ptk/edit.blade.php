{{-- resources/views/ptk/edit.blade.php --}}
<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">Edit PTK {{ $ptk->number }}</h2>

  <form method="post" enctype="multipart/form-data" action="{{ route('ptk.update', $ptk) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf
    @method('PUT')

    <label for="title">Judul
      <input id="title" type="text" name="title" class="border p-2 rounded w-full" required
             value="{{ old('title', $ptk->title) }}">
      @error('title') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="status">Status
      <select id="status" name="status" class="border p-2 rounded w-full" required>
        @foreach(['Not Started','In Progress','Completed'] as $s)
          <option value="{{ $s }}" @selected(old('status', $ptk->status) === $s)>{{ $s }}</option>
        @endforeach
      </select>
      @error('status') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Kategori --}}
    <label for="cat">Kategori
      <select name="category_id" id="cat" class="border p-2 rounded w-full" required>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id', $ptk->category_id) == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('category_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Subkategori (dependent on Kategori) --}}
    <label for="subcat">Subkategori
      <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full">
        <option value="">-- pilih subkategori --</option>
      </select>
      @error('subcategory_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="department_id">Departemen
      <select id="department_id" name="department_id" class="border p-2 rounded w-full" required>
        @foreach($departments as $d)
          <option value="{{ $d->id }}" @selected(old('department_id', $ptk->department_id) == $d->id)>{{ $d->name }}</option>
        @endforeach
      </select>
      @error('department_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="pic_user_id">PIC
      <select id="pic_user_id" name="pic_user_id" class="border p-2 rounded w-full" required>
        @foreach($users as $u)
          <option value="{{ $u->id }}" @selected(old('pic_user_id', $ptk->pic_user_id) == $u->id)>{{ $u->name }}</option>
        @endforeach
      </select>
      @error('pic_user_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="due_date">Due date
      <input id="due_date" type="date" name="due_date" class="border p-2 rounded w-full" required
             value="{{ old('due_date', optional($ptk->due_date)->format('Y-m-d')) }}">
      @error('due_date') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Deskripsi umum --}}
    <label for="description" class="md:col-span-2">Deskripsi
      <textarea id="description" name="description" rows="6" class="border p-2 rounded w-full" required>{{ old('description', $ptk->description) }}</textarea>
      @error('description') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- 🔽 4 bagian tambahan --}}
    <label class="md:col-span-2">Deskripsi Ketidaksesuaian
      <textarea name="description_nc" rows="5" class="border p-2 rounded w-full">{{ old('description_nc', $ptk->description_nc ?? '') }}</textarea>
      @error('description_nc') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">Evaluasi Masalah
      <textarea name="evaluation" rows="5" class="border p-2 rounded w-full">{{ old('evaluation', $ptk->evaluation ?? '') }}</textarea>
      @error('evaluation') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">3a. Koreksi (perbaikan masalah)
      <textarea name="correction_action" rows="5" class="border p-2 rounded w-full">{{ old('correction_action', $ptk->correction_action ?? '') }}</textarea>
      @error('correction_action') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">3b. Tindakan Korektif (akar masalah)
      <textarea name="corrective_action" rows="5" class="border p-2 rounded w-full">{{ old('corrective_action', $ptk->corrective_action ?? '') }}</textarea>
      @error('corrective_action') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>
    {{-- 🔼 end 4 bagian --}}

    <label for="attachments" class="md:col-span-2">Lampiran (tambah)
      <input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="border p-2 rounded w-full">
      @error('attachments.*') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <div class="md:col-span-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('ptk.show', $ptk) }}" class="ml-2 underline">Batal</a>
    </div>
  </form>

  @push('scripts')
  <script>
    async function loadSubcats(catId, selectedId = null){
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
          if (String(selectedId) === String(row.id)) {
            opt.selected = true;
          }
          sel.appendChild(opt);
        });
      }catch(e){
        console.error(e);
      }finally{
        sel.disabled = false;
      }
    }

    const catSel = document.getElementById('cat');
    catSel.addEventListener('change', () => loadSubcats(catSel.value));

    // preload saat halaman dibuka (untuk nilai saat ini / old input)
    loadSubcats(
      catSel.value,
      '{{ old('subcategory_id', $ptk->subcategory_id) }}'
    );
  </script>
  @endpush
</x-layouts.app>
