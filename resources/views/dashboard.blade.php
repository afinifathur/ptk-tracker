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
                <td class="py-2 px-3">
                  <x-ui.stat-badge :status="$row->status" />
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

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // DEBUG: Verify Script Run
      // alert('Chart Script Running!'); 

      var statusEl = document.getElementById('js-status');
      if (statusEl) statusEl.innerText = 'RUNNING...';

      try {
        // ... Logic ...
        console.log('Initializing Charts...');

        // Debug check
        if (typeof Chart === 'undefined') {
          throw new Error('Chart is NOT defined. Library failed to load.');
        }

        // Register plugin if not automatically registered
        if (typeof ChartDataLabels !== 'undefined') {
          Chart.register(ChartDataLabels);
        }

        // Helper safe convert to numbers
        function toNums(arr) {
          if (Array.isArray(arr)) {
            return arr.map(function (v) { return Number(v) || 0; });
          }
          return [];
        }

        /* =========================================================
         * LINE CHART — TREN PTK
         * ========================================================= */
        (function drawTrend() {
          var el = document.getElementById('trendChart');
          if (!el) {
            console.error('#trendChart canvas not found');
            return;
          }

          var labels = @json($labels ?? []);
          var months = @json($monthMarks ?? []);
          var seriesQc = toNums(@json($seriesQc ?? []));
          var seriesHr = toNums(@json($seriesHr ?? []));
          var seriesMtc = toNums(@json($seriesMtc ?? []));

          new Chart(el, {
            type: 'line',
            data: {
              labels: labels,
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
                },
                {
                  label: 'PTK MTC (Kabag MTC)',
                  data: seriesMtc,
                  borderColor: '#10b981', // Green
                  backgroundColor: '#10b981',
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
                legend: { display: true } // Ensure legend is on
              },
              scales: {
                x: {
                  title: {
                    display: true,
                    text: 'Minggu (26 minggu terakhir)'
                  },
                  ticks: {
                    callback: function (v, i) {
                      var w = labels[i] || v;
                      var m = months[i] || '';
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
          var el = document.getElementById('deptDonut');
          if (!el) return;

          var labels = @json($deptLabels ?? []);
          var series = toNums(@json($deptSeries ?? []));

          // Sum total safely
          var total = 0;
          for (var k = 0; k < series.length; k++) {
            total += series[k];
          }

          if (!labels.length || !series.length || total === 0) {
            // Optional: Show empty state
            // el.style.display = 'none';
            // return;
            var div = document.createElement('div');
            div.className = 'text-sm text-gray-400';
            div.innerText = 'Tidak ada data departemen pada periode ini.';
            el.parentNode.replaceChild(div, el);
            return;
          }

          var colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
            '#8b5cf6', '#06b6d4', '#f97316', '#22c55e',
            '#eab308', '#ec4899', '#94a3b8'
          ];

          new Chart(el, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                data: series,
                backgroundColor: labels.map(function (_, i) {
                  return colors[i % colors.length];
                }),
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
                legend: { display: false },
                // Enable datalabels locally
                datalabels: {
                  color: '#ffffff',
                  font: { weight: '700', size: 13 },
                  formatter: function (value) {
                    return value > 0 ? value : '';
                  }
                }
              }
            },
            plugins: [ChartDataLabels] // Global reg might handle this, but safe to add
          });
        })();

        if (statusEl) statusEl.innerText = 'FINISHED (OK)';

      } catch (err) {
        console.error('Chart Error:', err);
        // Show error in the chart container so it's visible
        var el = document.getElementById('trendChart');
        if (el) {
          var errDiv = document.createElement('div');
          errDiv.style.color = 'red';
          errDiv.style.padding = '20px';
          errDiv.style.textAlign = 'center';
          errDiv.innerText = 'Gagal memuat grafik: ' + err.message;
          el.parentNode.replaceChild(errDiv, el);
        }
      }
    });
  </script>
</x-layouts.app>