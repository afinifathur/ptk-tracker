<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">Laporan Periode</h2>

  <form method="post" action="{{ route('exports.range.report') }}" class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
    @csrf

    <label class="block lg:col-span-1">Start
      <input type="date" name="start" class="border p-2 rounded w-full" required value="{{ old('start') }}">
      @error('start')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </label>

    <label class="block lg:col-span-1">End
      <input type="date" name="end" class="border p-2 rounded w-full" required value="{{ old('end') }}">
      @error('end')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </label>

    <label class="block lg:col-span-1">Kategori
      <select name="category_id" id="cat" class="border p-2 rounded w-full">
        <option value="">Semua</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('category_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </label>

    <label class="block lg:col-span-1">Subkategori
      <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full" data-old="{{ old('subcategory_id') }}">
        <option value="">Semua</option>
      </select>
      @error('subcategory_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </label>

    <label class="block lg:col-span-1">Departemen
      <select name="department_id" class="border p-2 rounded w-full">
        <option value="">Semua</option>
        @foreach($departments as $d)
          <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
        @endforeach
      </select>
      @error('department_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </label>

    <label class="block lg:col-span-1">Status
      <select name="status" class="border p-2 rounded w-full">
        <option value="">Semua</option>
        @foreach(['Not Started','In Progress','Completed'] as $s)
          <option value="{{ $s }}" @selected(old('status') == $s)>{{ $s }}</option>
        @endforeach
      </select>
      @error('status')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </label>

    <div class="lg:col-span-6">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Generate</button>
    </div>
  </form>

  @push('scripts')
  <script>
    async function loadSubcats(catId){
      const sel = document.getElementById('subcat');
      const oldVal = sel.getAttribute('data-old') || '';
      sel.innerHTML = '<option value="">Semua</option>';
      sel.disabled = true;

      if(!catId){ sel.disabled = false; return; }

      try{
        const url = new URL(@json(route('api.subcategories')));
        url.searchParams.set('category_id', catId);
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        if(!res.ok) throw new Error('Failed to fetch');
        const data = await res.json();

        data.forEach(row=>{
          const opt = document.createElement('option');
          opt.value = row.id; opt.textContent = row.name;
          if(String(oldVal) === String(row.id)) opt.selected = true;
          sel.appendChild(opt);
        });
      }catch(e){
        // optional: tampilkan notif error
        console.warn('Gagal memuat subkategori', e);
      }finally{
        sel.disabled = false;
      }
    }

    // Event change kategori
    document.getElementById('cat').addEventListener('change', function(){
      // reset old subcategory selection
      document.getElementById('subcat').setAttribute('data-old', '');
      loadSubcats(this.value);
    });

    // Preload saat halaman dibuka jika ada old('category_id')
    document.addEventListener('DOMContentLoaded', function(){
      const cat = document.getElementById('cat').value;
      if(cat){ loadSubcats(cat); }
    });
  </script>
  @endpush
</x-layouts.app>
