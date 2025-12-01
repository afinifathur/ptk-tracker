<x-layouts.app>
  <div class="space-y-6" x-data="{ preview:false, imgSrc:'', imgCaption:'', rotate: 0, scale: 1 }">
    {{-- Header & aksi --}}
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">PTK {{ $ptk->number ?? '-' }}</h1>

      <div class="flex flex-wrap gap-2">
        {{-- ✅ Tombol Submit PTK --}}
        @hasanyrole('admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3')
          @if($ptk->status !== 'Completed')
            <form method="POST" action="{{ route('ptk.submit', $ptk) }}" class="inline">
              @csrf
              <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                Submit PTK
              </button>
            </form>
          @endif
        @endhasanyrole

        {{-- Edit PTK --}}
        @can('update', $ptk)
          <a href="{{ route('ptk.edit', $ptk) }}"
             class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Edit
          </a>
        @endcan

        {{-- Preview + Download PDF --}}
        <div class="flex gap-2">
          <!-- Tombol Preview buka di tab baru -->
          <a href="{{ route('exports.pdf.preview', $ptk->id) }}"
             target="_blank"
             rel="noopener"
             class="px-3 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
            Preview PDF
          </a>

          <!-- Tombol Download (tetap ada) -->
          <a href="{{ route('exports.pdf', $ptk->id) }}"
             class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-900">
            Download PDF
          </a>
        </div>

        {{-- Delete PTK --}}
        @can('delete', $ptk)
          <form method="POST" action="{{ route('ptk.destroy', $ptk) }}"
                onsubmit="return confirm('Yakin hapus PTK ini?')" class="inline">
            @csrf
            @method('DELETE')
            <button class="px-3 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
              Delete
            </button>
          </form>
        @endcan
      </div>
    </div>

    {{-- Meta ringkas --}}
    @php
      $badge = match($ptk->status) {
        'Completed'   => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100',
        'In Progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-100',
        default       => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
      };
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow">
        <div class="text-xs text-gray-500">Judul</div>
        <div class="text-lg font-semibold mb-3">{{ $ptk->title }}</div>

        <div class="text-xs text-gray-500">Status</div>
        <div class="mb-3">
          <span class="px-2 py-1 rounded text-xs font-medium {{ $badge }}">
            {{ $ptk->status }}
          </span>
        </div>

        <div class="text-xs text-gray-500">Kategori / Departemen</div>
        <div class="mb-3">
          {{ $ptk->category->name ?? '-' }}
          @if($ptk->subcategory)
            <span class="text-gray-400">/</span> {{ $ptk->subcategory->name }}
          @endif
          <span class="text-gray-400"> • </span> {{ $ptk->department->name ?? '-' }}
        </div>

        <div class="text-xs text-gray-500">PIC</div>
        <div class="mb-3">{{ $ptk->pic->name ?? '-' }}</div>

        <div class="text-xs text-gray-500">Due / Approved</div>
        <div class="mb-3">
          {{ optional($ptk->due_date)->format('Y-m-d') ?? '-' }} /
          {{ optional($ptk->approved_at)->format('Y-m-d') ?? '-' }}
        </div>

        {{-- Dua tanggal --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Tanggal Form:</span>
            <span class="font-medium">
              {{ optional($ptk->form_date)->format('d M Y') }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">Tanggal Input:</span>
            <span class="font-medium">
              {{ $ptk->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
            </span>
          </div>
        </div>
      </div>

      {{-- Deskripsi singkat --}}
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow">
        <div class="text-xs text-gray-500">Deskripsi singkat</div>
        <div class="prose dark:prose-invert max-w-none">
          {!! nl2br(e($ptk->description ?? '—')) !!}
        </div>
      </div>
    </div>

    {{-- Section utama --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow space-y-6">
      @foreach ([
        '1. Deskripsi Ketidaksesuaian' => $ptk->desc_nc,
        '2. Evaluasi Masalah (Analisis)' => $ptk->evaluation,
        '3a. Tindakan Koreksi dan Tindakan Korektif' => $ptk->action_correction,
        '4. Hasil Uji Coba' => $ptk->action_corrective,
      ] as $title => $content)
        <div>
          <h2 class="font-semibold mb-2">{{ $title }}</h2>
          <div class="prose dark:prose-invert max-w-none">
            {!! nl2br(e($content ?? '—')) !!}
          </div>
        </div>
      @endforeach
    </div>

    {{-- Lampiran (sudah dipatch) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
      <h2 class="font-semibold mb-3">Lampiran</h2>

      @if($ptk->attachments->count())
        <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          @foreach($ptk->attachments as $att)
            @php
              // URL file (pakai disk public + asset)
              $url   = asset(Storage::url($att->path));
              $mime  = strtolower($att->mime ?? '');
              $isImg = str_starts_with($mime, 'image/');

              // OPTIONAL: kalau kamu yakin pakai disk `public`, boleh dipakai.
              // Kalau tidak yakin, bisa set $exists = true saja supaya tidak menghalangi klik.
              try {
                  $exists = Storage::disk('public')->exists($att->path);
              } catch (\Throwable $e) {
                  $exists = true; // fallback: anggap ada
              }
            @endphp

            <li class="group">
              @if($isImg && $exists)
                {{-- Tombol untuk preview gambar --}}
                <button
                  type="button"
                  class="block w-full aspect-[4/3] overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-gray-700"
                  x-on:click="
                    preview = true;
                    imgSrc = `{{ $url }}`;
                    imgCaption = `{{ $att->original_name }}`;
                  "
                >
                  <img
                    src="{{ $url }}"
                    alt="{{ $att->original_name }}"
                    loading="lazy"
                    onerror="this.onerror=null;this.src='{{ asset('/storage/placeholders/image-missing.png') }}';"
                    class="w-full h-full object-contain bg-gray-50 group-hover:scale-105 transition"
                  />
                </button>

              @elseif($isImg && ! $exists)
                {{-- File gambar tidak ditemukan --}}
                <div class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 text-gray-500">
                  <span class="text-xs">File tidak ditemukan</span>
                </div>
              @else
                {{-- Non-image file (PDF, DOC, dll) --}}
                <a
                  href="{{ $url }}"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                >
                  <span class="text-xs">
                    {{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}
                  </span>
                </a>
              @endif

              <div
                class="mt-1 text-xs truncate text-gray-700 dark:text-gray-200"
                title="{{ $att->original_name }}"
              >
                {{ $att->original_name }}
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <div class="text-sm text-gray-500">Tidak ada lampiran</div>
      @endif
    </div>

  {{-- Modal preview (overlay gelap tapi tombol tetap terang) --}}
<div
  x-show="preview"
  x-transition.opacity
  x-cloak
  x-data
  x-on:keydown.escape.window="preview=false; rotate = 0; scale = 1"
  x-on:click.self="preview=false; rotate = 0; scale = 1"
  class="fixed inset-0 z-[9990] flex items-center justify-center"
  role="dialog"
  aria-modal="true"
>
    {{-- Overlay gelap di belakang --}}
    <div class="absolute inset-0 bg-black/70 z-[9990]"></div>

    {{-- Konten modal di atas overlay --}}
    <div class="relative z-[10000] w-full max-w-[98vw] max-h-[98vh] mx-4 flex items-center justify-center">
        
        {{-- AREA GAMBAR --}}
        <div class="flex items-center justify-center overflow-hidden rounded-md bg-transparent"
             style="width: 100%; height: 100%;">
          <img
            :src="imgSrc"
            :alt="imgCaption"
            class="block object-contain select-none"
            :style="`
                transform: rotate(${rotate}deg) scale(${scale});
                max-width: calc(100vw - 100px);
                max-height: calc(100vh - 100px);
                transition: transform .18s ease;
            `"
          />
        </div>

        {{-- PANEL TOMBOL --}}
        <div class="ml-4 flex flex-col gap-2 items-stretch z-[10001]">
            <button class="px-3 py-2 bg-white text-gray-800 rounded shadow"
                    x-on:click="rotate = (rotate - 45) % 360">
                ⟲ Rotate Left
            </button>

            <button class="px-3 py-2 bg-white text-gray-800 rounded shadow"
                    x-on:click="rotate = (rotate + 45) % 360">
                ⟳ Rotate Right
            </button>

            <button class="px-3 py-2 bg-white text-gray-800 rounded shadow"
                    x-on:click="rotate = 0; scale = 1">
                ↺ Reset
            </button>

            {{-- Zoom --}}
            <div class="bg-white p-3 rounded shadow mt-2">
                <div class="text-xs text-gray-700 mb-2">
                    Zoom: <span x-text="Math.round(scale*100) + '%'"></span>
                </div>
                <input type="range" min="0.2" max="2" step="0.05" x-model.number="scale" class="w-36">
            </div>

            <button class="px-3 py-2 mt-3 bg-white text-gray-800 rounded shadow"
                    x-on:click="preview=false; rotate=0; scale=1">
                ✕ Close
            </button>
        </div>
    </div>
</div>

  </div>
</x-layouts.app>
