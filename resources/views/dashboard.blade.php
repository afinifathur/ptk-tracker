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

        {{-- Top Kategori & Subkategori --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-white dark:bg-gray-800 rounded shadow">
            <div class="font-semibold mb-3">Top Kategori (6 bulan)</div>
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

          <div class="p-4 bg-white dark:bg-gray-800 rounded shadow">
            <div class="font-semibold mb-3">Top Subkategori (6 bulan)</div>
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

      {{-- Panel kanan: SLA + Donut --}}
      <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow space-y-5">
        <div>
          <div class="text-sm text-gray-500">SLA Compliance (6 bulan)</div>
          <div class="text-5xl md:text-6xl font-extrabold mt-1">{{ $slaPct ?? 0 }}%</div>
          <div class="text-xs text-gray-500">PTK selesai ≤ due date</div>
        </div>

        <div>
          <div class="font-semibold mb-2">Distribusi PTK per Departemen (6 bulan)</div>
          <canvas id="deptDonut" style="height:300px;max-height:340px;"></canvas>
          @if(isset($donutFrom, $donutTo))
            <div class="mt-2 text-xs text-gray-500">
              Rentang: {{ $donutFrom->format('d M Y') }} – {{ $donutTo->format('d M Y') }}
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- TABEL OVERDUE --}}
    <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-lg">PTK yang Terlambat (Top 10)</h3>
        <a href="{{ route('ptk.index', ['overdue' => 1]) }}" class="text-sm text-blue-600 hover:underline">
          Lihat semua
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm table-auto">
          <thead class="text-gray-500">
            <tr>
              <th class="py-2 px-3 text-left">Nomor</th>
              <th class="py-2 px-3 text-left">Judul</th>
              <th class="py-2 px-3 text-left">PIC</th>
              <th class="py-2 px-3 text-left">Departemen</th>
              <th class="py-2 px-3 text-left">Kategori</th>
              <th class="py-2 px-3 text-left">Due</th>
              <th class="py-2 px-3 text-left">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse(($overdueTop ?? []) as $row)
              <tr>
                <td class="py-2 px-3">{{ $row->number }}</td>
                <td class="py-2 px-3">
                  <a href="{{ route('ptk.show', $row) }}" class="underline text-blue-600">
                    {{ $row->title }}
                  </a>
                </td>
                <td class="py-2 px-3">{{ $row->pic->name ?? '-' }}</td>
                <td class="py-2 px-3">{{ $row->department->name ?? '-' }}</td>
                <td class="py-2 px-3">
                  {{ $row->category->name ?? '-' }}
                  @if($row->subcategory)
                    <span class="text-gray-400">/</span> {{ $row->subcategory->name }}
                  @endif
                </td>
                <td class="py-2 px-3">{{ $row->due_date?->format('Y-m-d') }}</td>
                <td class="py-2 px-3">{{ $row->status }}</td>
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
</x-layouts.app>

{{-- ================= CHART JS ================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const toNums = (arr) =>
    Array.isArray(arr) ? arr.map(v => Number(v) || 0) : [];

  /* =========================================================
   * LINE CHART — TREN PTK
   * ========================================================= */
  (function drawTrend() {
    const el = document.getElementById('trendChart');
    if (!el) return;

    const labels   = @json($labels ?? []);
    const months   = @json($monthMarks ?? []);
    const seriesQc = toNums(@json($seriesQc ?? []));
    const seriesHr = toNums(@json($seriesHr ?? []));

    new Chart(el, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'PTK QC (Kabag QC)',
            data: seriesQc,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 3,
            fill: false
          },
          {
            label: 'PTK HR (Manager HR)',
            data: seriesHr,
            borderColor: '#f97316',
            backgroundColor: '#f97316',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 3,
            fill: false
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true }
        },
        scales: {
          x: {
            title: {
              display: true,
              text: 'Minggu (26 minggu terakhir)'
            },
            ticks: {
              callback: (v, i) => {
                const w = labels[i] ?? v;
                const m = months[i] ?? '';
                return m ? [w, m] : w;
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
  })();

  /* =========================================================
   * DONUT CHART — DISTRIBUSI DEPARTEMEN
   * ========================================================= */
  (function drawDonut() {
    const el = document.getElementById('deptDonut');
    if (!el) return;

    const labels = @json($deptLabels ?? []);
    const series = toNums(@json($deptSeries ?? []));
    const total  = series.reduce((a, b) => a + b, 0);

    if (!labels.length || !series.length || total === 0) {
      el.replaceWith(Object.assign(document.createElement('div'), {
        className: 'text-sm text-gray-400',
        innerText: 'Tidak ada data departemen pada periode ini.'
      }));
      return;
    }

    const colors = [
      '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
      '#8b5cf6', '#06b6d4', '#f97316', '#22c55e',
      '#eab308', '#ec4899', '#94a3b8'
    ];

    new Chart(el, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: series,
          backgroundColor: labels.map((_, i) => colors[i % colors.length]),
          borderColor: '#ffffff',
          borderWidth: 2,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const val = ctx.parsed || 0;
                const pct = ((val / total) * 100).toFixed(1);
                return `${ctx.label}: ${val} (${pct}%)`;
              }
            }
          },
          datalabels: {
            color: '#ffffff',
            font: {
              weight: '700',
              size: 13
            },
            formatter: (value) => {
              const pct = Math.round((value / total) * 100);
              return pct >= 3 ? pct + '%' : '';
            }
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  })();

});
</script>
