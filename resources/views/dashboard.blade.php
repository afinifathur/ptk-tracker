<x-layouts.app>
  <div class="space-y-6">

    {{-- RINGKASAN KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="text-sm text-gray-500">Total PTK</div>
        <div class="text-3xl font-bold">{{ $total ?? 0 }}</div>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="text-sm text-gray-500">In Progress</div>
        <div class="text-3xl font-bold text-yellow-500">{{ $inProgress ?? 0 }}</div>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="text-sm text-gray-500">Completed</div>
        <div class="text-3xl font-bold text-green-600">{{ $completed ?? 0 }}</div>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="text-sm text-gray-500">Overdue</div>
        <div class="text-3xl font-bold text-red-600">{{ $overdue ?? 0 }}</div>
      </div>
    </div>

    {{-- GRAFIK + PANEL KANAN --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {{-- Grafik (2 kolom) --}}
      <div class="md:col-span-2 p-5 bg-white dark:bg-gray-800 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-2">Tren PTK per Minggu (26 minggu)</h2>
        <p class="text-sm text-gray-500 mb-4">
          Pakai data terbaru ({{ $from?->format('M Y') }} – {{ $to?->format('M Y') }})
        </p>
        <canvas id="trendChart" height="120"></canvas>
      </div>

      {{-- Panel kanan: SLA & Top 3 --}}
      <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow space-y-5">
        <div>
          <div class="text-sm text-gray-500">SLA Compliance (6 bulan)</div>
          <div class="text-5xl md:text-6xl font-extrabold mt-1">{{ $slaPct ?? 0 }}%</div>
          <div class="text-xs text-gray-500">PTK selesai ≤ due date</div>
        </div>

        <div>
          <div class="font-semibold mb-2">Top Departemen (6 bulan)</div>
          <ol class="list-decimal ml-5 space-y-1">
            @forelse($topDepartments ?? [] as $t)
              <li class="flex justify-between">
                <span>{{ $t['name'] }}</span>
                <span class="font-semibold">{{ $t['total'] }}</span>
              </li>
            @empty
              <li class="text-gray-400">Tidak ada data</li>
            @endforelse
          </ol>
        </div>

        <div>
          <div class="font-semibold mb-2">Top Kategori (6 bulan)</div>
          <ol class="list-decimal ml-5 space-y-1">
            @forelse($topCategories ?? [] as $t)
              <li class="flex justify-between">
                <span>{{ $t['name'] }}</span>
                <span class="font-semibold">{{ $t['total'] }}</span>
              </li>
            @empty
              <li class="text-gray-400">Tidak ada data</li>
            @endforelse
          </ol>
        </div>

        <div>
          <div class="font-semibold mb-2">Top Subkategori (6 bulan)</div>
          <ol class="list-decimal ml-5 space-y-1">
            @forelse($topSubcategories ?? [] as $t)
              <li class="flex justify-between">
                <span>{{ $t['name'] }}</span>
                <span class="font-semibold">{{ $t['total'] }}</span>
              </li>
            @empty
              <li class="text-gray-400">Tidak ada data</li>
            @endforelse
          </ol>
        </div>
      </div>
    </div>

    {{-- TABEL OVERDUE (FULL WIDTH, DENGAN WARNA STATUS & KATEGORI) --}}
    <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-lg">PTK yang Terlambat (Top 10)</h3>
        <a href="{{ route('ptk.index', ['overdue' => 1]) }}" class="text-sm text-blue-600 hover:underline">
          Lihat semua
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm table-auto">
          <colgroup>
            <col style="width: 12rem;">   {{-- Nomor --}}
            <col style="width: 32rem;">   {{-- Judul --}}
            <col style="width: 10rem;">   {{-- PIC --}}
            <col style="width: 12rem;">   {{-- Departemen --}}
            <col style="width: 16rem;">   {{-- Kategori/Subkategori --}}
            <col style="width: 10rem;">   {{-- Due --}}
            <col style="width: 12rem;">   {{-- Status --}}
          </colgroup>

          <thead class="text-gray-500">
            <tr>
              <th class="py-2 px-3 text-left">Nomor</th>
              <th class="py-2 px-3 text-left">Judul</th>
              <th class="py-2 px-3 text-left">PIC</th>
              <th class="py-2 px-3 text-left">Departemen</th>
              <th class="py-2 px-3 text-left">Kategori</th>
              <th class="py-2 px-3 text-left whitespace-nowrap">Due</th>
              <th class="py-2 px-3 text-left">Status</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse(($overdueTop ?? []) as $row)
              <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-900/40">
                <td class="py-2 px-3 whitespace-nowrap">{{ $row->number ?? '—' }}</td>
                <td class="py-2 px-3">
                  <a href="{{ route('ptk.show', $row) }}" class="underline text-blue-600 hover:text-blue-800">
                    {{ $row->title }}
                  </a>
                </td>
                <td class="py-2 px-3 whitespace-nowrap">{{ $row->pic->name ?? '-' }}</td>
                <td class="py-2 px-3 whitespace-nowrap">{{ $row->department->name ?? '-' }}</td>
                <td class="py-2 px-3 whitespace-nowrap">
                  {{ $row->category->name ?? '-' }}
                  @if($row->subcategory)
                    <span class="text-gray-400">/</span> {{ $row->subcategory->name }}
                  @endif
                </td>
                <td class="py-2 px-3 whitespace-nowrap">{{ $row->due_date?->format('Y-m-d') }}</td>
                <td class="py-2 px-3 whitespace-nowrap">
                  @php
                    $color = match($row->status) {
                      'Completed'   => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100',
                      'In Progress' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100',
                      default       => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                    };
                  @endphp
                  <span class="px-2 py-1 rounded text-xs font-medium {{ $color }}">
                    {{ $row->status ?? 'Not Started' }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada PTK overdue.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  {{-- JAVASCRIPT CHART --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const canvas = document.getElementById('trendChart');
      if (!canvas || typeof Chart === 'undefined') return;

      const labels = @json($labels ?? []);
      const months = @json($monthMarks ?? []);
      const data   = @json($series ?? []);

      const ctx = canvas.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'PTK/minggu',
            data,
            fill: false,
            tension: .3,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 4,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6'
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: {
            x: {
              title: { display: true, text: 'Minggu (26 minggu terakhir)' },
              ticks: {
                callback: function (value, index) {
                  const week = labels[index] ?? value;
                  const mon  = months[index] ?? '';
                  return mon ? [week, mon] : week; // multi-line tick
                },
                font: { size: 11 }
              }
            },
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1 }
            }
          }
        }
      });
    });
  </script>
</x-layouts.app>
