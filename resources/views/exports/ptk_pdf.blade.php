<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
    .watermark { position: fixed; top: 35%; left: 20%; opacity: 0.1; font-size: 80px; transform: rotate(-30deg); }
    footer { position: fixed; bottom: 0; width: 100%; font-size:10px; }
    .sign { margin-top: 24px; display:flex; gap:40px; }
    .sign div { text-align:center; }
    .sign img { max-height: 80px; }
  </style>
</head>
<body>
  <div class="watermark">PTK FINAL</div>
  <h2>PTK {{ $ptk->number }}</h2>
  <p><strong>Judul:</strong> {{ $ptk->title }}</p>
  <p><strong>Kategori:</strong> {{ $ptk->category->name ?? '-' }} | <strong>Departemen:</strong> {{ $ptk->department->name ?? '-' }}</p>
  <p><strong>Status:</strong> {{ $ptk->status }} | <strong>Due:</strong> {{ optional($ptk->due_date)->format('Y-m-d') }} | <strong>Approved:</strong> {{ optional($ptk->approved_at)->format('Y-m-d') }}</p>
  <hr>
  <p>{!! nl2br(e($ptk->description)) !!}</p>

  @if($ptk->approved_at)
  <div class="sign">
    @if($ptk->approver_id)
      <div>
        <img src="{{ public_path('brand/signatures/approver.png') }}">
        <div>Approver</div>
      </div>
    @endif
    @if($ptk->director_id)
      <div>
        <img src="{{ public_path('brand/signatures/director.png') }}">
        <div>Director</div>
      </div>
    @endif
  </div>
  @endif

  <footer>
    Audit: printed at {{ now()->format('Y-m-d H:i:s') }}, by {{ auth()->user()->email ?? 'system' }}, IP {{ request()->ip() }}.
  </footer>
</body>
</html>
