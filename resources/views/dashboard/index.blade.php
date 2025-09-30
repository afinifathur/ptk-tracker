<!-- resources/views/dashboard/index.blade.php -->
<x-layouts.app>
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 class="text-2xl font-bold">Dashboard</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan status PTK & metrik kinerja</p>
    </div>
    <a href="{{ route('ptk.create') }}" class="px-4 py-2 rounded-xl bg-blue-600 text-white shadow hover:bg-blue-700">
      + New PTK
    </a>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-ui.stat-card title="Total PTK" :value="$kpi['total'] ?? 0" />
    <x-ui.stat-card title="In Progress" :value="$kpi['inProgress'] ?? 0" />
    <x-ui.stat-card title="Completed" :value="$kpi['completed'] ?? 0" />
    <x-ui.stat-card title="Overdue" :value="$kpi['overdue'] ?? 0" />
  </div>

  <!-- Charts + SLA -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="col-span-2 p-5 rounded-2xl shadow-sm bg-white dark:bg-gray-800 border border-gray-100/50 dark:border-gray-700/50">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Tren PTK (6 bulan)</h3>
      </div>
      <canvas id="trendChart" height="110"></canvas>
    </div>

    <div class="p-5 rounded-2xl shadow-sm bg-white dark:bg-gray-800 border border-gray-100/50 dark:border-gray-700/50">
      <h3 class="font-semibold mb-3">SLA Compliance</h3>
      <div class="text-5xl font-bold">{{ $kpi['sla'] ?? 0 }}%</div>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">PTK selesai ≤ due date</p>

      <div class="mt-6">
        <h4 class="font-medium mb-2">Top Departemen</h4>
        <ul class="space-y-1 text-sm">
          @forelse(($topDept ?? []) as $d)
            <li class="flex justify-between">
              <span>{{ $d->name }}</span>
              <span class="font-semibold">{{ $d->ptk_count }}</span>
            </li>
          @empty
            <li class="text-gray-500 dark:text-gray-400">Belum ada data</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

  <!-- Overdue Table -->
  <div class="p-5 rounded-2xl shadow-sm bg-white dark:bg-gray-800 border border-gray-100/50 dark:border-gray-700/50">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">Overdue PTK (Top 10)</h3>
      <a href="{{ route('ptk.index') }}" class="text-sm underline">Lihat semua</a>
    </div>

    <table id="overdueTable" class="display w-full text-sm">
      <thead>
        <tr>
          <th>Nomor</th>
          <th>Judul</th>
          <th>PIC</th>
          <th>Departemen</th>
          <th>Due</th>
        </tr>
      </thead>
      <tbody>
        @forelse(($overdueList ?? []) as $o)
          <tr>
            <td><a class="underline" href="{{ route('ptk.show',$o) }}">{{ $o->number }}</a></td>
            <td>{{ $o->title }}</td>
            <td>{{ $o->pic->name ?? '-' }}</td>
            <td>{{ $o->department->name ?? '-' }}</td>
            <td>{{ optional($o->due_date)->format('Y-m-d') }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-gray-500 dark:text-gray-400">Tidak ada overdue</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <script>
    // Data tren dari controller (fallback array kosong)
    const trendLabels = @json($trendLabels ?? []);
    const trendValues = @json($trendValues ?? []);

    // Chart.js (pakai default color)
    if (document.getElementById('trendChart')) {
      const ctx = document.getElementById('trendChart');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: trendLabels,
          datasets: [{ label: 'PTK/bulan', data: trendValues, tension: .3, fill: false }]
        }
      });
    }

    // DataTables
    $(function(){
      $('#overdueTable').DataTable({
        pageLength: 10,
        order: [[4,'asc']],
        language: { search: "_INPUT_", searchPlaceholder: "Cari PTK..." }
      });
    });
  </script>
</x-layouts.app>
