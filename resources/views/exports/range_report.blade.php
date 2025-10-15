<x-layouts.app>
  @php
    // ===== GUARD & FALLBACK =====
    $ptks          = $ptks          ?? collect();
    $items         = $items         ?? $ptks;   // kompatibel dengan view lama
    $categories    = $categories    ?? collect();
    $departments   = $departments   ?? collect();
    $subcategories = $subcategories ?? collect();

    // Top 3 guard
    $topCategories    = $topCategories    ?? collect();
    $topDepartments   = $topDepartments   ?? collect();
    $topSubcategories = $topSubcategories ?? collect();

    // Fallback data periode & filter (pakai struktur $data jika ada)
    $start = $data['start'] ?? ($start ?? '-');
    $end   = $data['end']   ?? ($end   ?? '-');

    $catId = $data['category_id']    ?? null;
    $depId = $data['department_id']  ?? null;
    $subId = $data['subcategory_id'] ?? null;
    $sts   = $data['status']         ?? ($status ?? 'Semua');

    $cat = optional($categories->firstWhere('id', $catId))->name ?? 'Semua';
    $dep = optional($departments->firstWhere('id', $depId))->name ?? 'Semua';
    $sub = optional($subcategories->firstWhere('id', $subId))->name ?? 'Semua';
  @endphp

  <div class="flex items-center justify-between mb-3">
    <div>
      <h2 class="text-xl font-semibold">Laporan {{ $start }} s.d. {{ $end }}</h2>
      <div class="text-sm text-gray-500">
        Kategori <strong>{{ $cat }}</strong> ·
        Subkategori <strong>{{ $sub }}</strong> ·
        Departemen <strong>{{ $dep }}</strong> ·
        Status <strong>{{ $sts ?? 'Semua' }}</strong>
      </div>
    </div>

    <div class="space-x-2">
      <form method="post" action="{{ route('exports.range.excel') }}" class="inline">
        @csrf
        <input type="hidden" name="start" value="{{ $start }}">
        <input type="hidden" name="end" value="{{ $end }}">
        <input type="hidden" name="subcategory_id" value="{{ $subId ?? '' }}">
        {{-- Opsional: ikutkan filter lain agar konsisten dengan tampilan --}}
        {{-- <input type="hidden" name="category_id" value="{{ $catId ?? '' }}"> --}}
        {{-- <input type="hidden" name="department_id" value="{{ $depId ?? '' }}"> --}}
        {{-- <input type="hidden" name="status" value="{{ $sts ?? '' }}"> --}}
        <button class="px-3 py-2 bg-green-600 text-white rounded">Export Excel</button>
      </form>

      <form method="post" action="{{ route('exports.range.pdf') }}" class="inline">
        @csrf
        <input type="hidden" name="start" value="{{ $start }}">
        <input type="hidden" name="end" value="{{ $end }}">
        <input type="hidden" name="subcategory_id" value="{{ $subId ?? '' }}">
        {{-- Filter lain opsional, sama seperti di atas --}}
        <button class="px-3 py-2 bg-rose-600 text-white rounded">Export PDF</button>
      </form>
    </div>
  </div>

  {{-- ===== Top 3 ===== --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-4">
    <div class="p-3 border rounded bg-white dark:bg-gray-800">
      <div class="font-semibold mb-2">Top 3 Kategori</div>
      <ol class="list-decimal ml-5 space-y-1">
        @forelse($topCategories as $row)
          <li>{{ $row['name'] }} — <span class="font-semibold">{{ $row['total'] }}</span></li>
        @empty
          <li class="text-gray-500">Tidak ada data.</li>
        @endforelse
      </ol>
    </div>

    <div class="p-3 border rounded bg-white dark:bg-gray-800">
      <div class="font-semibold mb-2">Top 3 Departemen</div>
      <ol class="list-decimal ml-5 space-y-1">
        @forelse($topDepartments as $row)
          <li>{{ $row['name'] }} — <span class="font-semibold">{{ $row['total'] }}</span></li>
        @empty
          <li class="text-gray-500">Tidak ada data.</li>
        @endforelse
      </ol>
    </div>

    <div class="p-3 border rounded bg-white dark:bg-gray-800">
      <div class="font-semibold mb-2">Top 3 Subkategori</div>
      <ol class="list-decimal ml-5 space-y-1">
        @forelse($topSubcategories as $row)
          <li>{{ $row['name'] }} — <span class="font-semibold">{{ $row['total'] }}</span></li>
        @empty
          <li class="text-gray-500">Tidak ada data.</li>
        @endforelse
      </ol>
    </div>
  </div>

  {{-- ===== Tabel hasil ===== --}}
  <table class="display w-full text-sm">
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Judul</th>
        <th>Kategori</th>
        <th>Subkategori</th>
        <th>Departemen</th>
        <th>PIC</th>
        <th>Status</th>
        <th>Due</th>
        <th>Dibuat</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $p)
        <tr>
          <td>{{ $p->number ?? '—' }}</td>
          <td class="truncate max-w-[320px]">{{ $p->title }}</td>
          <td>{{ $p->category->name ?? '-' }}</td>
          <td>{{ $p->subcategory->name ?? '-' }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->pic->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td class="whitespace-nowrap">{{ optional($p->due_date)->format('Y-m-d') }}</td>
          <td class="whitespace-nowrap">{{ optional($p->created_at)->format('Y-m-d') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="text-center text-gray-500 py-4">Tidak ada data untuk rentang ini.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <script>
    $(function () {
      $('table.display').DataTable({
        pageLength: 25,
        order: [[8, 'desc']], // urutkan berdasarkan kolom "Dibuat"
        language: { search: "_INPUT_", searchPlaceholder: "Cari di laporan..." },
      });
    });
  </script>
</x-layouts.app>
