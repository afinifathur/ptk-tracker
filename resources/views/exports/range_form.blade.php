<x-layouts.app>
  <h1 class="text-xl font-semibold mb-4">Laporan Periode</h1>

  <form method="POST" action="{{ route('exports.range.report') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
      {{-- Tanggal mulai & akhir --}}
      <div>
        <label class="text-sm">Start</label>
        <input type="date" name="start" class="w-full rounded border-gray-300" value="{{ old('start') }}">
      </div>
      <div>
        <label class="text-sm">End</label>
        <input type="date" name="end" class="w-full rounded border-gray-300" value="{{ old('end') }}">
      </div>

      {{-- Kategori --}}
      <div>
        <label class="text-sm">Kategori</label>
        <select name="category_id" id="categorySelect" class="w-full rounded border-gray-300">
          <option value="">Semua</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('category_id')==$cat->id)>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Subkategori --}}
      <div>
        <label class="text-sm">Subkategori</label>
        <select name="subcategory_id" id="subcategorySelect" class="w-full rounded border-gray-300">
          <option value="">Semua</option>
          @foreach($subcategories as $sub)
            <option value="{{ $sub->id }}" data-category="{{ $sub->category_id }}">
              {{ $sub->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Departemen --}}
      <div>
        <label class="text-sm">Departemen</label>
        <select name="department_id" class="w-full rounded border-gray-300">
          <option value="">Semua</option>
          @foreach($departments as $dep)
            <option value="{{ $dep->id }}" @selected(old('department_id')==$dep->id)>
              {{ $dep->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Status --}}
      <div>
        <label class="text-sm">Status</label>
        <select name="status" class="w-full rounded border-gray-300">
          <option value="">Semua</option>
          <option value="Not Started" @selected(old('status')=='Not Started')>Not Started</option>
          <option value="In Progress" @selected(old('status')=='In Progress')>In Progress</option>
          <option value="Completed" @selected(old('status')=='Completed')>Completed</option>
          <option value="Overdue" @selected(old('status')=='Overdue')>Overdue</option>
        </select>
      </div>
    </div>

    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white">Generate</button>
  </form>

  {{-- JS: filter subkategori otomatis --}}
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const cat = document.getElementById('categorySelect');
    const sub = document.getElementById('subcategorySelect');

    function filterSubs() {
      const selectedCat = cat.value;
      Array.from(sub.options).forEach(opt => {
        const match = !selectedCat || opt.dataset.category === selectedCat || opt.value === '';
        opt.hidden = !match;
        if (opt.hidden && opt.selected) opt.selected = false;
      });
    }

    cat.addEventListener('change', filterSubs);
    filterSubs(); // initial
  });
  </script>
</x-layouts.app>
