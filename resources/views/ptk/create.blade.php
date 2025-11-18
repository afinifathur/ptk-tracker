{{-- resources/views/ptk/create.blade.php --}}
<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">New PTK</h2>

  <form method="post" enctype="multipart/form-data" action="{{ route('ptk.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf

    {{-- NOMOR PTK (required) --}}
    <div class="md:col-span-2 mb-3">
      <label class="block text-sm font-medium">Nomor PTK <span class="text-red-500">*</span></label>
      <input
        type="text"
        name="number"
        value="{{ old('number', $ptk->number ?? '') }}"
        class="w-full border rounded px-3 py-2"
        required
        placeholder="contoh: PTK/QC/2025/10/001">
      <p class="text-xs text-gray-500 mt-1">
        Nomor diisi manual oleh admin saat membuat PTK.
      </p>
      @error('number')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>

    <label for="title">Judul
      <input id="title" type="text" name="title" class="border p-2 rounded w-full" required value="{{ old('title') }}" maxlength="200">
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

    <label for="subcat">Subkategori
      <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full" data-old="{{ old('subcategory_id') }}">
        <option value="">-- pilih subkategori --</option>
      </select>
      @error('subcategory_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Departemen: selectable untuk semua role --}}
    <label for="department_id">Departemen
      <select id="department_id" name="department_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($departments as $id => $name)
          <option value="{{ $id }}" @selected(old('department_id') == $id)>{{ $name }}</option>
        @endforeach
      </select>
      @error('department_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- PIC --}}
    <label for="pic_user_id">PIC
      <select id="pic_user_id" name="pic_user_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($picCandidates as $uopt)
          <option value="{{ $uopt->id }}" @selected(old('pic_user_id') == $uopt->id)>{{ $uopt->name }}</option>
        @endforeach
      </select>
      @error('pic_user_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="due_date">Due date
      <input id="due_date" type="date" name="due_date" class="border p-2 rounded w-full" value="{{ old('due_date') }}" required>
      @error('due_date') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Tanggal Form (tanggal di kertas) --}}
    <label for="form_date">Tanggal Form (Tanggal PTK Asli)
      <input id="form_date" type="date" name="form_date"
             value="{{ old('form_date', now()->toDateString()) }}"
             class="border p-2 rounded w-full" required>
      @error('form_date') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Deskripsi umum (opsional) --}}
    <label for="description" class="md:col-span-2">Deskripsi
      <textarea id="description" name="description" rows="6" class="border p-2 rounded w-full">{{ old('description') }}</textarea>
      @error('description') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- 4 bagian tambahan --}}
    <label class="md:col-span-2">Deskripsi Ketidaksesuaian
      <textarea name="desc_nc" rows="5" class="border p-2 rounded w-full">{{ old('desc_nc') }}</textarea>
      @error('desc_nc') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">Evaluasi Masalah
      <textarea name="evaluation" rows="5" class="border p-2 rounded w-full">{{ old('evaluation') }}</textarea>
      @error('evaluation') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">3a. Koreksi (perbaikan masalah) dan Tindakan Korektif (akar masalah)
      <textarea name="action_correction" rows="5" class="border p-2 rounded w-full">{{ old('action_correction') }}</textarea>
      @error('action_correction') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">4. Hasil Uji Coba
      <textarea name="action_corrective" rows="5" class="border p-2 rounded w-full">{{ old('action_corrective') }}</textarea>
      @error('action_corrective') <small class="text-red-600">{{ $message }}</small> @enderror
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

      if(!catId){ sel.disabled = false; return; }

      try{
        const res = await fetch(`{{ route('api.subcategories') }}?category_id=${encodeURIComponent(catId)}`);
        if(!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();

        data.forEach(row => {
          const opt = document.createElement('option');
          opt.value = row.id;
          opt.textContent = row.name;
          if (preselectedId && String(preselectedId) === String(row.id)) opt.selected = true;
          sel.appendChild(opt);
        });
      }catch(e){ console.error(e); }
      finally{ sel.disabled = false; }
    }

    document.getElementById('category_id').addEventListener('change', function(){
      loadSubcats(this.value);
    });

    (function preload(){
      const oldCategory = "{{ old('category_id') }}";
      const oldSubcat   = document.getElementById('subcat').dataset.old || "";
      if(oldCategory){ loadSubcats(oldCategory, oldSubcat); }
    })();
  </script>
  @endpush
</x-layouts.app>
