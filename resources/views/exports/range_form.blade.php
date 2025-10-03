<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">Laporan Periode</h2>

  <form method="post" action="{{ route('exports.range.report') }}" class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
  @csrf

  <label class="block lg:col-span-1">Start
    <input type="date" name="start" class="border p-2 rounded w-full" required value="{{ old('start') }}">
  </label>

  <label class="block lg:col-span-1">End
    <input type="date" name="end" class="border p-2 rounded w-full" required value="{{ old('end') }}">
  </label>

  <label class="block lg:col-span-1">Kategori
    <select name="category_id" id="cat" class="border p-2 rounded w-full">
      <option value="">Semua</option>
      @foreach($categories as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
    </select>
  </label>

  <label class="block lg:col-span-1">Subkategori
    <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full">
      <option value="">Semua</option>
    </select>
  </label>

  <label class="block lg:col-span-1">Departemen
    <select name="department_id" class="border p-2 rounded w-full">
      <option value="">Semua</option>
      @foreach($departments as $d)
        <option value="{{ $d->id }}">{{ $d->name }}</option>
      @endforeach
    </select>
  </label>

  <label class="block lg:col-span-1">Status
    <select name="status" class="border p-2 rounded w-full">
      <option value="">Semua</option>
      @foreach(['Not Started','In Progress','Completed'] as $s)
        <option value="{{ $s }}">{{ $s }}</option>
      @endforeach
    </select>
  </label>

  <div class="lg:col-span-6">
    <button class="px-4 py-2 bg-blue-600 text-white rounded">Generate</button>
  </div>
</form>


  @push('scripts')
  <script>
    async function loadSubcats(catId){
      const sel = document.getElementById('subcat');
      sel.innerHTML = '<option value="">Semua</option>';
      if(!catId){ return; }
      try{
        const res = await fetch(`{{ route('api.subcategories') }}?category_id=${catId}`);
        const data = await res.json();
        data.forEach(row=>{
          const opt = document.createElement('option');
          opt.value = row.id; opt.textContent = row.name;
          sel.appendChild(opt);
        });
      }catch(e){}
    }
    document.getElementById('cat').addEventListener('change', function(){ loadSubcats(this.value); });
  </script>
  @endpush
</x-layouts.app>
