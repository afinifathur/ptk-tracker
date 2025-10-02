<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
    .watermark { position: fixed; top: 30%; left: 10%; right:10%; opacity: 0.08; text-align:center; }
    .watermark img { max-width: 420px; }
    header { display:flex; align-items:center; gap:12px; margin-bottom:10px; }
    header img { height: 36px; }
    header .title { font-size: 16px; font-weight: bold; }
    footer { position: fixed; bottom: 0; width: 100%; font-size:10px; border-top:1px solid #ccc; padding-top:4px; }
    .sign { margin-top: 24px; display:flex; gap:40px; }
    .sign div { text-align:center; }
    .sign img { max-height: 80px; }
  </style>
</head>
<body>
  <div class="watermark">
    @if(file_exists(public_path('brand/logo.png')))
      <img src="{{ public_path('brand/logo.png') }}">
    @else
      <div>PT. Peroni Karya Sentra</div>
    @endif
  </div>

  <header>
    @if(file_exists(public_path('brand/logo.png')))
      <img src="{{ public_path('brand/logo.png') }}" alt="Logo">
    @endif
    <div class="title">PT. Peroni Karya Sentra — PTK FINAL</div>
  </header>

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
        <img src="{{ public_path('brand/signatures/approver.png') }}"><div>Approver</div>
      </div>
    @endif
    @if($ptk->director_id)
      <div>
        <img src="{{ public_path('brand/signatures/director.png') }}"><div>Director</div>
      </div>
    @endif
  </div>
  @endif

  @if(!empty($qrBase64))
  <div style="position:absolute; right:20px; top:20px; text-align:center;">
    <img src="{{ $qrBase64 }}" style="width:120px; height:120px;">
    <div style="font-size:9px; width:120px; word-wrap:break-word;">Verifikasi: {{ $verifyUrl }}</div>
  </div>
@endif

  <footer>
    Audit: printed {{ now()->format('Y-m-d H:i:s') }}, by {{ auth()->user()->email ?? 'system' }}, IP {{ request()->ip() }} — Hash: {{ $docHash }}
  </footer>
</body>
</html>
