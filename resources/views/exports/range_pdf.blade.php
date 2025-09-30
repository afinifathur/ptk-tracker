<!DOCTYPE html>
<html>
<head>
  <style> body { font-family: DejaVu Sans, sans-serif; font-size: 12px; } h3 { margin-bottom:4px; } </style>
</head>
<body>
  <h2>Laporan Periode {{ $data['start'] }} s.d. {{ $data['end'] }}</h2>
  <h3>Top 3 Kategori</h3>
  <ol>
  @foreach($topCategories as $catId=>$count)
    <li>{{ \App\Models\Category::find($catId)->name ?? 'N/A' }} — {{ $count }}</li>
  @endforeach
  </ol>

  <h3>Top 3 Departemen</h3>
  <ol>
  @foreach($topDepartments as $deptId=>$count)
    <li>{{ \App\Models\Department::find($deptId)->name ?? 'N/A' }} — {{ $count }}</li>
  @endforeach
  </ol>

  <h3>Overdue</h3>
  <ul>
    @forelse($overdue as $p)
      <li>{{ $p->number }} — {{ $p->title }} (PIC: {{ $p->pic->name ?? '-' }})</li>
    @empty
      <li>Tidak ada.</li>
    @endforelse
  </ul>

  <h3>Daftar PTK</h3>
  <table width="100%" border="1" cellspacing="0" cellpadding="4">
    <thead><tr><th>Nomor</th><th>Judul</th><th>PIC</th><th>Dept</th><th>Status</th></tr></thead>
    <tbody>
      @foreach($items as $p)
        <tr><td>{{ $p->number }}</td><td>{{ $p->title }}</td><td>{{ $p->pic->name ?? '-' }}</td><td>{{ $p->department->name ?? '-' }}</td><td>{{ $p->status }}</td></tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
