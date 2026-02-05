<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <style>
    /* ===== Basic PDF styles ===== */
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #111;
    }

    h2 {
      margin: 0 0 6px;
    }

    p.small {
      margin: 0 0 10px;
      font-size: 11px;
      color: #555;
    }

    p.meta {
      margin: 0 0 12px;
      font-size: 11px;
      color: #555;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #666;
      padding: 6px 8px;
      vertical-align: top;
    }

    th {
      background: #eee;
    }

    .w-title {
      width: 38%;
    }

    .text-right {
      text-align: right;
    }
  </style>
</head>

<body>
  @php
    // Fallback untuk start/end jika meta belum dikirim
    $metaStart = $start ?? ($data['start'] ?? null);
    $metaEnd = $end ?? ($data['end'] ?? null);

    // Label dari rangeMeta(); fallback ke 'Semua' bila kosong
    $catLabel = $category_name ?? 'Semua';
    $subLabel = $subcategory_name ?? 'Semua';
    $depLabel = $department_name ?? 'Semua';
    $stsLabel = $status_label ?? 'Semua';
  @endphp

  <h2>Laporan PTK</h2>

  {{-- Ringkasan filter yang dipakai --}}
  <p class="meta">
    Periode: {{ $metaStart ?: 'Semua' }} s/d {{ $metaEnd ?: 'Semua' }} •
    Kategori: {{ $catLabel }} •
    Subkategori: {{ $subLabel }} •
    Departemen / Role: {{ $depLabel }} •
    Status: {{ $stsLabel }}
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
        <th>Departemen</th>
        <th>Kategori</th>
        <th>Subkategori</th>
        <th>Status</th>
        <th>Due</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $p)
        <tr>
          <td>{{ $p->number ?? '—' }}</td>
          <td>{{ $p->title }}</td>
          <td>{{ $p->department->name ?? '-' }}</td>
          <td>{{ $p->category->name ?? '-' }}</td>
          <td>{{ $p->subcategory->name ?? '-' }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ optional($p->due_date)->format('Y-m-d') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-right">Tidak ada data untuk kriteria ini.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>

</html>