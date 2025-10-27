<x-layouts.app>
  <h2 class="text-xl font-semibold mb-3">Audit Log</h2>

  {{-- Filter Form --}}
  <form class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
    {{-- Filter User --}}
    <input class="border p-2 rounded" type="number" name="user_id"
           value="{{ request('user_id') }}" placeholder="User ID">

    {{-- Filter Event --}}
    <select class="border p-2 rounded" name="event">
      <option value="">Event</option>
      @foreach(['created','updated','deleted','restored'] as $e)
        <option value="{{ $e }}" @selected(request('event')==$e)>
          {{ ucfirst($e) }}
        </option>
      @endforeach
    </select>

    {{-- Filter Model --}}
    <input class="border p-2 rounded" type="text" name="model"
           value="{{ request('model') }}" placeholder="Model (mis. PTK)">

    <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
  </form>

  {{-- Audit Table --}}
  <table class="min-w-full text-sm border border-gray-200">
    <thead class="bg-gray-100 text-left">
      <tr>
        <th class="py-2 px-3">Waktu</th>
        <th class="py-2 px-3">User</th>
        <th class="py-2 px-3">Event</th>
        <th class="py-2 px-3">PTK Nomor</th>
        <th class="py-2 px-3">IP Address</th>
        <th class="py-2 px-3">Notes</th>
      </tr>
    </thead>
    <tbody>
      @forelse($audits as $audit)
        <tr class="border-t">
          {{-- Waktu --}}
          <td class="py-2 px-3">
            {{ optional($audit->created_at)->format('Y-m-d H:i:s') ?? '-' }}
          </td>

          {{-- User name (fallback ke ID) --}}
          <td class="py-2 px-3">
            {{ optional($audit->user)->name ?? 'User #'.$audit->user_id }}
          </td>

          {{-- Event --}}
          <td class="py-2 px-3 capitalize">{{ $audit->event }}</td>

          {{-- PTK Nomor (jika auditable adalah PTK atau punya "number") --}}
          <td class="py-2 px-3">
            @php
              $auditable = $audit->auditable;
              $ptkNumber = $auditable->number ?? null;
            @endphp
            @if($ptkNumber)
              <a href="{{ route('ptk.show', $audit->auditable_id) }}"
                 class="underline text-blue-600">{{ $ptkNumber }}</a>
            @else
              <span class="text-gray-500">#{{ $audit->auditable_id }}</span>
            @endif
          </td>

          {{-- IP Address --}}
          <td class="py-2 px-3">{{ $audit->ip_address ?? '-' }}</td>

          {{-- Notes: ringkasan perubahan --}}
          <td class="py-2 px-3 text-xs text-gray-600">
            @if(!empty($audit->old_values) || !empty($audit->new_values))
              <div>Old: {{ \Illuminate\Support\Str::limit(json_encode($audit->old_values), 120) }}</div>
              <div>New: {{ \Illuminate\Support\Str::limit(json_encode($audit->new_values), 120) }}</div>
            @else
              &mdash;
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="py-4 text-center text-gray-500">
            Tidak ada data audit untuk filter saat ini.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Pagination --}}
  <div class="mt-4">{{ $audits->links() }}</div>
</x-layouts.app>
