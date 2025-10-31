<x-layouts.app>
  <div class="space-y-6" x-data="{preview:false, imgSrc:'', imgCaption:''}">
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

        {{-- Download PDF --}}
        <a href="{{ route('exports.pdf', $ptk->id) }}"
           class="px-3 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
          Download PDF
        </a>

        {{-- Delete PTK --}}
        @can('delete', $ptk)
          <form method="POST" action="{{ route('ptk.destroy', $ptk) }}"
                onsubmit="return confirm('Yakin hapus PTK ini?')" class="inline">
            @csrf @method('DELETE')
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
        '3a. Tindakan Koreksi' => $ptk->action_correction,
        '3b. Tindakan Korektif' => $ptk->action_corrective,
      ] as $title => $content)
        <div>
          <h2 class="font-semibold mb-2">{{ $title }}</h2>
          <div class="prose dark:prose-invert max-w-none">
            {!! nl2br(e($content ?? '—')) !!}
          </div>
        </div>
      @endforeach
    </div>

    {{-- Lampiran --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
      <h2 class="font-semibold mb-3">Lampiran</h2>

      @if($ptk->attachments->count())
        <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          @foreach($ptk->attachments as $att)
            @php
              $url   = Storage::url($att->path);
              $mime  = strtolower($att->mime ?? '');
              $isImg = str_starts_with($mime, 'image/');
            @endphp

            <li class="group">
              @if($isImg)
                <button type="button"
                        class="block w-full aspect-[4/3] overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-gray-700"
                        x-on:click="imgSrc='{{ $url }}'; imgCaption='{{ $att->original_name }}'; preview=true">
                  <img src="{{ $url }}" alt="{{ $att->original_name }}"
                       class="w-full h-full object-cover group-hover:scale-105 transition"/>
                </button>
              @else
                <a href="{{ $url }}" target="_blank"
                   class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                  <span class="text-xs">
                    {{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}
                  </span>
                </a>
              @endif
              <div class="mt-1 text-xs truncate text-gray-700 dark:text-gray-200"
                   title="{{ $att->original_name }}">
                   {{ $att->original_name }}
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <div class="text-sm text-gray-500">Tidak ada lampiran</div>
      @endif
    </div>

    {{-- Modal preview gambar --}}
    <div x-show="preview" x-transition
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
         x-cloak
         x-on:keydown.escape.window="preview=false">
      <div class="relative max-w-5xl w-full">
        <button class="absolute -top-3 -right-3 bg-white text-gray-700 rounded-full w-8 h-8 shadow"
                x-on:click="preview=false">✕</button>
        <img :src="imgSrc" :alt="imgCaption" class="w-full rounded-lg shadow-lg">
        <div class="mt-2 text-center text-white text-sm" x-text="imgCaption"></div>
      </div>
    </div>
  </div>
</x-layouts.app>
