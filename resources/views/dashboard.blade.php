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

        {{-- === Tambahan: Top Kategori & Top Subkategori (bawah chart) === --}}
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
        {{-- === END Tambahan === --}}
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
          <colgroup>
            <col style="width: 12rem;">
            <col style="width: 32rem;">
            <col style="width: 10rem;">
            <col style="width: 12rem;">
            <col style="width: 16rem;">
            <col style="width: 10rem;">
            <col style="width: 12rem;">
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
                      'In Progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-100',
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
</x-layouts.app>

{{-- ========= SCRIPT CHART: inline ========= --}}
<script>
(function () {
  const waitFor = (cond, cb, { tries=120, interval=50 }={}) => {
    const t = setInterval(() => { if (cond()) { clearInterval(t); cb(); } else if (--tries<=0) clearInterval(t); }, interval);
  };
  const toNums = (arr) => (Array.isArray(arr) ? arr.map(v => Number(v) || 0) : []);

  function drawTrend() {
    const el = document.getElementById('trendChart'); if (!el) return;
    const labels = @json($labels ?? []);
    const months = @json($monthMarks ?? []);
    const data   = toNums(@json($series ?? []));

    new Chart(el.getContext('2d'), {
      type: 'line',
      data: { labels, datasets: [{ label:'PTK/minggu', data, fill:false, tension:.3, borderWidth:2, pointRadius:3, pointHoverRadius:4, borderColor:'#3b82f6', backgroundColor:'#3b82f6' }] },
      options: {
        responsive:true, plugins:{ legend:{ display:true } },
        scales:{
          x:{ title:{ display:true, text:'Minggu (26 minggu terakhir)' },
              ticks:{ callback:(v,i)=>{ const w=labels[i]??v; const m=months[i]??''; return m?[w,m]:w; }, font:{ size:11 } } },
          y:{ beginAtZero:true, ticks:{ stepSize:1 } }
        }
      }
    });
  }

  function drawDonut() {
    const el = document.getElementById('deptDonut'); if (!el) return;
    const labels = @json($deptLabels ?? []);
    const series = toNums(@json($deptSeries ?? []));

    if (!labels.length || !series.length || series.reduce((a,b)=>a+b,0) === 0) {
      el.replaceWith(Object.assign(document.createElement('div'), { className: 'text-sm text-gray-400', innerText: 'Tidak ada data departemen pada periode ini.' }));
      return;
    }

    const palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#22c55e','#eab308','#ec4899','#94a3b8','#84cc16'];
    const colors  = labels.map((_, i) => palette[i % palette.length]);

    const cfg = {
      type:'doughnut',
      data:{ labels, datasets:[{ data:series, backgroundColor:colors, borderColor:colors, borderWidth:1, hoverOffset:8 }] },
      options:{
        maintainAspectRatio:false,
        layout:{ padding:{ top:24, right:24, bottom:32, left:24 } },
        responsive:true,
        cutout:'56%',
        plugins:{
          legend:{ display:false },
          tooltip:{ callbacks:{ label:(ctx)=>{ const lbl=ctx.label??''; const val=Number(ctx.parsed)||0; const tot=series.reduce((a,b)=>a+b,0)||1; const pct=((val/tot)*100).toFixed(1); return `${lbl}: ${val} (${pct}%)`; } } }
        }
      }
    };

    if (window.ChartDataLabels) {
      cfg.plugins = [ChartDataLabels];
      cfg.options.plugins.datalabels = {
        labels: {
          percent: { formatter:(v)=>{ const t=series.reduce((a,b)=>a+b,0)||1; return Math.round((Number(v)/t)*100)+'%'; }, anchor:'center', align:'center', clamp:true, color:'#fff', font:{ weight:'bold' } },
          name: { formatter:(_,ctx)=>labels[ctx.dataIndex]||'', anchor:'end', align:'end', offset:16, clamp:true, color:'#6b7280', font:{ size:11 } }
        }
      };
    }

    new Chart(el.getContext('2d'), cfg);
  }

  waitFor(() => window.Chart, () => { drawTrend(); drawDonut(); });
})();
</script>
