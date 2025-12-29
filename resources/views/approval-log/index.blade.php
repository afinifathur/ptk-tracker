<x-layouts.app>

  <h2 class="text-xl font-semibold mb-4">Approval Log</h2>

  {{-- ================= FILTER BAR (FINAL & SEJAJAR) ================= --}}
  <form method="GET"
        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 items-end">

    {{-- Search --}}
    <div>
      <label class="text-xs text-gray-500">Cari (PTK / Alasan)</label>
      <input type="text"
             name="q"
             value="{{ request('q') }}"
             placeholder="Cari nomor / alasan reject…"
             class="w-full border rounded px-3 py-2
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100">
    </div>

    {{-- User --}}
    <div>
      <label class="text-xs text-gray-500">Oleh</label>
      <select name="user_id"
              class="w-full border rounded px-3 py-2
                     bg-white dark:bg-gray-800
                     text-gray-900 dark:text-gray-100">
        <option value="">— Semua User —</option>
        @foreach($users as $u)
          <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
            {{ $u->name }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- Action --}}
    <div>
      <label class="text-xs text-gray-500">Aksi</label>
      <select name="action"
              class="w-full border rounded px-3 py-2
                     bg-white dark:bg-gray-800
                     text-gray-900 dark:text-gray-100">
        <option value="">— Semua —</option>
        <option value="stage1" @selected(request('action')=='stage1')>
          Reject Stage 1
        </option>
        <option value="stage2" @selected(request('action')=='stage2')>
          Reject Stage 2
        </option>
      </select>
    </div>

    {{-- Submit (WARNA ASLI, POSISI BENAR) --}}
    <button type="submit"
            class="w-full px-4 py-2
                   bg-gray-800 hover:bg-gray-700
                   text-white rounded">
      Filter
    </button>
  </form>

  {{-- ================= TABLE ================= --}}
  <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200">
        <tr>
          <th class="px-3 py-2 text-left">Waktu</th>
          <th class="px-3 py-2 text-left">PTK</th>
          <th class="px-3 py-2 text-left">Aksi</th>
          <th class="px-3 py-2 text-left">Oleh</th>
          <th class="px-3 py-2 text-left">Keterangan</th>
          <th class="px-3 py-2 text-left">IP</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($logs as $log)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">

            {{-- Waktu --}}
            <td class="px-3 py-2 whitespace-nowrap">
              {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}
            </td>

            {{-- PTK (LINK KE DETAIL) --}}
            <td class="px-3 py-2 whitespace-nowrap font-medium">
              @if($log->auditable)
                <a href="{{ route('ptk.show', $log->auditable->id) }}"
                   class="text-gray-800 dark:text-gray-100 hover:underline">
                  {{ $log->auditable->number }}
                </a>
              @else
                —
              @endif
            </td>

            {{-- AKSI (FINAL & JELAS) --}}
            <td class="px-3 py-2 font-medium whitespace-nowrap">
              @if(data_get($log->new_values, 'last_reject_stage') === 'stage1')
                Reject Stage 1
              @elseif(data_get($log->new_values, 'last_reject_stage') === 'stage2')
                Reject Stage 2
              @elseif(data_get($log->properties, 'stage') === 'stage1')
                Approve Stage 1
              @elseif(data_get($log->properties, 'stage') === 'stage2')
                Approve Stage 2
              @else
                —
              @endif
            </td>

            {{-- OLEH --}}
            <td class="px-3 py-2 whitespace-nowrap">
              {{ optional($log->user)->name ?? 'System' }}
            </td>

            {{-- KETERANGAN (REJECT SAJA) --}}
            <td class="px-3 py-2 max-w-[520px] text-gray-700 dark:text-gray-300">
              @if(data_get($log->new_values, 'last_reject_stage'))
                <div class="whitespace-pre-wrap break-words">
                  {{ data_get($log->new_values, 'last_reject_reason') }}
                </div>
              @else
                —
              @endif
            </td>

            {{-- IP --}}
            <td class="px-3 py-2 whitespace-nowrap">
              {{ $log->ip_address ?? '-' }}
            </td>

          </tr>
        @empty
          <tr>
            <td colspan="6"
                class="px-3 py-6 text-center text-gray-500">
              Belum ada approval log.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ================= PAGINATION ================= --}}
  <div class="mt-4">
    {{ $logs->withQueryString()->links() }}
  </div>

</x-layouts.app>
