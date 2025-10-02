<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    .small { font-size: 11px; color: #666; }
  </style>
</head>
<body>
  <h3>Laporan PTK: {{ $data['start'] }} s.d. {{ $data['end'] }}</h3>

  <p class="small">
    Filter:
    Kategori: {{ $data['category_id'] ?? 'Semua' }},
    Departemen: {{ $data['department_id'] ?? 'Semua' }},
    Status: {{ $data['status'] ?? 'Semua' }}
  </p>

  <table>
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Judul</th>
        <th>Dept</th>
        <th>Kategori</th>
        <th>Status</th>
        <th>Due</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($items as $p)
        <tr>
          <td>{{ $p->number }}</td>
          <td>{{ $p->title }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->category->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @isset($docHash)
    <p style="margin-top:8px;font-size:10px;color:#666">
      Hash laporan: {{ $docHash }}
    </p>
  @endisset
</body>
</html>
