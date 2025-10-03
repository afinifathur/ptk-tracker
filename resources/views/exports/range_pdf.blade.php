<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    h2   { margin: 0 0 6px; }
    p.small { margin: 0 0 10px; font-size: 11px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #666; padding: 6px 8px; vertical-align: top; }
    th { background: #eee; }
    .w-title { width: 38%; }
  </style>
</head>
<body>
  <h2>Laporan Periode {{ $data['start'] }} s.d. {{ $data['end'] }}</h2>

  {{-- Filter ringkas (menggunakan ID/label yang diterima controller) --}}
  <p class="small">
    Filter:
    Kategori: {{ $data['category_id'] ?? 'Semua' }},
    Subkategori: {{ $data['subcategory_id'] ?? 'Semua' }},
    Departemen: {{ $data['department_id'] ?? 'Semua' }},
    Status: {{ $data['status'] ?? 'Semua' }}
  </p>

  {{-- Optional: fingerprint dokumen --}}
  @isset($docHash)
    <p class="small">Fingerprint: {{ $docHash }}</p>
  @endisset

  <table>
    <thead>
      <tr>
        <th>Nomor</th>
        <th class="w-title">Judul</th>
        <th>Dept</th>
        <th>Kategori</th>
        <th>Subkategori</th>
        <th>Status</th>
        <th>Due</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $p)
        <tr>
          <td>{{ $p->number }}</td>
          <td>{{ $p->title }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->category->name ?? '-' }}</td>
          <td>{{ $p->subcategory->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
