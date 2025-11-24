{{-- resources/views/ptk/edit.blade.php --}}
<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">
    Edit PTK {{ $ptk->number ?? '—' }}
  </h2>

  <form
    method="POST"
    enctype="multipart/form-data"
    action="{{ route('ptk.update', $ptk) }}"
    class="grid grid-cols-1 md:grid-cols-2 gap-4"
  >
    @csrf
    @method('PUT')

    {{-- NOMOR PTK (required) --}}
    <div class="md:col-span-2 mb-1">
      <label for="number" class="block text-sm font-medium">
        Nomor PTK <span class="text-red-500">*</span>
      </label>
      <input
        id="number"
        type="text"
        name="number"
        value="{{ old('number', $ptk->number) }}"
        class="w-full border rounded px-3 py-2"
        required
        placeholder="contoh: PTK/QC/2025/10/001"
      >
      <p class="text-xs text-gray-500 mt-1">
        Nomor wajib unik. Jika perlu koreksi format, ubah di sini.
      </p>
      @error('number')
        <div class="text-red-600 text-sm">{{ $message }}</div>
      @enderror
    </div>

    {{-- Judul --}}
    <div>
      <label for="title" class="block text-sm font-medium mb-1">
        Judul
      </label>
      <input
        id="title"
        type="text"
        name="title"
        class="border p-2 rounded w-full"
        required
        maxlength="200"
        value="{{ old('title', $ptk->title) }}"
      >
      @error('title')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Status --}}
    <div>
      <label for="status" class="block text-sm font-medium mb-1">
        Status
      </label>
      <select
        id="status"
        name="status"
        class="border p-2 rounded w-full"
      >
        @foreach(['Not Started','In Progress','Completed'] as $s)
          <option value="{{ $s }}" @selected(old('status', $ptk->status) === $s)>
            {{ $s }}
          </option>
        @endforeach
      </select>
      @error('status')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Kategori --}}
    <div>
      <label for="cat" class="block text-sm font-medium mb-1">
        Kategori
      </label>
      <select
        name="category_id"
        id="cat"
        class="border p-2 rounded w-full"
        required
      >
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id', $ptk->category_id) == $c->id)>
            {{ $c->name }}
          </option>
        @endforeach
      </select>
      @error('category_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Subkategori (dynamic) --}}
    <div>
      <label for="subcat" class="block text-sm font-medium mb-1">
        Subkategori
      </label>
      <select
        name="subcategory_id"
        id="subcat"
        class="border p-2 rounded w-full"
      >
        <option value="">-- pilih subkategori --</option>
      </select>
      @error('subcategory_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Departemen --}}
    <div>
      <label for="department_id" class="block text-sm font-medium mb-1">
        Departemen
      </label>
      <select
        id="department_id"
        name="department_id"
        class="border p-2 rounded w-full"
        required
      >
        @foreach($departments as $id => $name)
          <option value="{{ $id }}" @selected(old('department_id', $ptk->department_id) == $id)>
            {{ $name }}
          </option>
        @endforeach
      </select>
      @error('department_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- PIC --}}
    <div>
      <label for="pic_user_id" class="block text-sm font-medium mb-1">
        PIC
      </label>
      <select
        id="pic_user_id"
        name="pic_user_id"
        class="border p-2 rounded w-full"
        required
      >
        @foreach($picCandidates as $u)
          <option value="{{ $u->id }}" @selected(old('pic_user_id', $ptk->pic_user_id) == $u->id)>
            {{ $u->name }}
          </option>
        @endforeach
      </select>
      @error('pic_user_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Due Date --}}
    <div>
      <label for="due_date" class="block text-sm font-medium mb-1">
        Due date
      </label>
      <input
        id="due_date"
        type="date"
        name="due_date"
        class="border p-2 rounded w-full"
        required
        value="{{ old('due_date', optional($ptk->due_date)->format('Y-m-d')) }}"
      >
      @error('due_date')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Tanggal Form --}}
    <div class="md:col-span-2">
      <label for="form_date" class="block text-sm font-medium mb-1">
        Tanggal Form (Tanggal PTK Asli)
      </label>
      <input
        id="form_date"
        type="date"
        name="form_date"
        value="{{ old('form_date', optional($ptk->form_date)->format('Y-m-d')) }}"
        class="border p-2 rounded w-full"
        required
      >
      @error('form_date')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Deskripsi --}}
    <div class="md:col-span-2">
      <label for="description" class="block text-sm font-medium mb-1">
        Deskripsi
      </label>
      <textarea
        id="description"
        name="description"
        rows="6"
        class="border p-2 rounded w-full"
      >{{ old('description', $ptk->description) }}</textarea>
      @error('description')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 1. Deskripsi Ketidaksesuaian --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        Deskripsi Ketidaksesuaian
      </label>
      <textarea
        name="desc_nc"
        rows="5"
        class="border p-2 rounded w-full"
      >{{ old('desc_nc', $ptk->desc_nc ?? '') }}</textarea>
      @error('desc_nc')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 2. Evaluasi Masalah --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        Evaluasi Masalah
      </label>
      <textarea
        name="evaluation"
        rows="5"
        class="border p-2 rounded w-full"
      >{{ old('evaluation', $ptk->evaluation ?? '') }}</textarea>
      @error('evaluation')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 3a. Koreksi & Tindakan Korektif --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        3a. Koreksi (perbaikan masalah) dan Tindakan Korektif (akar masalah)
      </label>
      <textarea
        name="action_correction"
        rows="5"
        class="border p-2 rounded w-full"
      >{{ old('action_correction', $ptk->action_correction ?? '') }}</textarea>
      @error('action_correction')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 4. Hasil Uji Coba --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        4. Hasil Uji Coba
      </label>
      <textarea
        name="action_corrective"
        rows="5"
        class="border p-2 rounded w-full"
      >{{ old('action_corrective', $ptk->action_corrective ?? '') }}</textarea>
      @error('action_corrective')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Lampiran (tambah baru) --}}
    <div class="md:col-span-2">
      <label for="attachments" class="block text-sm font-medium mb-1">
        Lampiran (tambah)
      </label>
      <input
        id="attachments"
        type="file"
        name="attachments[]"
        multiple
        accept=".jpg,.jpeg,.png,.pdf"
        class="border p-2 rounded w-full"
      >
      @error('attachments.*')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Lampiran lama --}}
    @if($ptk->attachments->count())
      <div class="md:col-span-2 mt-4">
        <h3 class="text-sm font-semibold mb-2">
          Lampiran Lama
        </h3>

        <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          @foreach($ptk->attachments as $att)
            @php
              $url   = asset(Storage::url($att->path));
              $isImg = str_starts_with(strtolower($att->mime ?? ''), 'image/');
            @endphp

            <li class="relative group">
              {{-- Tombol Hapus: pakai form global di bawah --}}
              <button
                type="button"
                class="delete-attachment absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow opacity-80 group-hover:opacity-100"
                data-att-delete-url="{{ route('ptk.attachment.delete', $att->id) }}"
                title="Hapus lampiran"
              >
                ×
              </button>

              {{-- Gambar / File --}}
              @if($isImg)
                <a
                  href="{{ $url }}"
                  target="_blank"
                  class="block w-full aspect-[4/3] overflow-hidden rounded-lg ring-1 ring-gray-200 bg-gray-50"
                >
                  <img
                    src="{{ $url }}"
                    alt="{{ $att->original_name }}"
                    loading="lazy"
                    class="w-full h-full object-contain"
                  >
                </a>
              @else
                <a
                  href="{{ $url }}"
                  target="_blank"
                  class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 bg-gray-50"
                >
                  <span class="text-xs">
                    {{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}
                  </span>
                </a>
              @endif

              <div class="mt-1 text-xs truncate" title="{{ $att->original_name }}">
                {{ $att->original_name }}
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Tombol aksi --}}
    <div class="md:col-span-2">
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
        Simpan
      </button>
      <a
        href="{{ route('ptk.show', $ptk) }}"
        class="ml-2 underline"
      >
        Batal
      </a>
    </div>
  </form>

  {{-- Form DELETE global (di luar form edit, agar tidak nested) --}}
  <form id="global-attachment-delete-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
  </form>

  @push('scripts')
    <script>
      // Dropdown subkategori dinamis
      async function loadSubcats(catId, selectedId = null) {
        const sel = document.getElementById('subcat');
        if (!sel) return;

        sel.innerHTML = '<option value="">-- pilih subkategori --</option>';
        sel.disabled = true;

        if (!catId) {
          sel.disabled = false;
          return;
        }

        try {
          const res = await fetch(`{{ route('api.subcategories') }}?category_id=${encodeURIComponent(catId)}`);
          if (!res.ok) throw new Error('Network response was not ok');

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
        } catch (e) {
          console.error(e);
        } finally {
          sel.disabled = false;
        }
      }

      document.addEventListener('DOMContentLoaded', function () {
        // Init subkategori
        const catSel = document.getElementById('cat');
        if (catSel) {
          catSel.addEventListener('change', () => loadSubcats(catSel.value));

          loadSubcats(
            catSel.value,
            @json(old('subcategory_id', $ptk->subcategory_id))
          );
        }

        // Hapus lampiran via form global (bukan AJAX)
        const globalDeleteForm = document.getElementById('global-attachment-delete-form');
        if (!globalDeleteForm) return;

        document.querySelectorAll('.delete-attachment').forEach(btn => {
          btn.addEventListener('click', function () {
            const url = btn.dataset.attDeleteUrl;
            if (!url) return;

            if (!confirm('Hapus lampiran ini?')) return;

            globalDeleteForm.action = url;
            globalDeleteForm.submit();
          });
        });
      });
    </script>
  @endpush
</x-layouts.app>
