@extends('layouts.app')

@section('content')
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
              <span>{{ $t['name'] }}</span><span class="font-semibold">{{ $t['total'] }}</span>
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
              <span>{{ $t['name'] }}</span><span class="font-semibold">{{ $t['total'] }}</span>
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
              <span>{{ $t['name'] }}</span><span class="font-semibold">{{ $t['total'] }}</span>
            </li>
          @empty
            <li class="text-gray-400">Tidak ada data</li>
          @endforelse
        </ol>
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
        labels: labels,
        datasets: [{
          label: 'PTK/minggu',
          data: data,
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
              // Baris 1: nomor minggu, Baris 2: bulan (hanya saat awal bulan)
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

  {{-- TABEL OVERDUE --}}
  <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">Overdue PTK (Top 10)</h3>
      <a href="{{ route('ptk.index', ['overdue' => 1]) }}" class="text-sm text-blue-600">Lihat semua</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-gray-500">
          <tr>
            <th class="py-2 px-3 text-left">Nomor</th>
            <th class="py-2 px-3 text-left">Judul</th>
            <th class="py-2 px-3 text-left">PIC</th>
            <th class="py-2 px-3 text-left">Departemen</th>
            <th class="py-2 px-3 text-left">Due</th>
            <th class="py-2 px-3 text-left">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($overdueTop ?? []) as $row)
            <tr class="border-t border-gray-200 dark:border-gray-700">
              <td class="py-2 px-3">{{ $row->number ?? '—' }}</td>
              <td class="py-2 px-3">
                <a href="{{ route('ptk.show', $row) }}" class="underline">{{ $row->title }}</a>
              </td>
              <td class="py-2 px-3">{{ $row->pic->name ?? '-' }}</td>
              <td class="py-2 px-3">{{ $row->department->name ?? '-' }}</td>
              <td class="py-2 px-3">{{ $row->due_date?->format('Y-m-d') }}</td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100">
                  {{ $row->status ?? 'Not Started' }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-4 text-center text-gray-500">Tidak ada PTK overdue.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection