<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan PTK - {{ $ptk->number ?? 'DRAFT' }}</title>
<style>
  /* Tipografi */
  *{ font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size:11px; line-height:1.35; }
  body{ margin:10mm 10mm; } /* lebih sempit dari Word Narrow (12.7mm) */

  /* Header */
  .title{ font-size:16px; font-weight:700; margin:0; }
  .subtitle{ font-size:11px; margin:2px 0 0; }
  .meta{ text-align:right; }
  .muted{ color:#666; }

  /* Separator */
  .hr{ border-top:1px solid #bbb; margin:6px 0 10px; }

  /* Tabel */
  table{ width:100%; border-collapse:collapse; }
  th{ text-align:left; background:#f5f6f8; border:1px solid #cfd4db; padding:5px 6px; font-weight:700; }
  td{ border:1px solid #d7dbe1; padding:5px 6px; vertical-align:top; }
  .grid2 td{ width:50%; }

  /* Status biasa (tanpa badge) */
  .status{ font-weight:600; }

  /* Tanda tangan */
  .sign-box{ height:70px; border:1px dashed #aaa; text-align:center; }
  .sign-img{ max-height:60px; }
  .small{ font-size:10px; }

  /* Attachment + caption */
  .attachments img{ width:100%; height:auto; display:block; }
  .caption{ font-size:10px; color:#555; margin-top:4px; }

  /* Spacing helpers */
  .mb6{ margin-bottom:6px; } .mb10{ margin-bottom:10px; }
</style>
</head>
<body>

  {{-- HEADER --}}
  <table style="width:100%;" class="mb6">
    <tr>
      <td>
        @if(!empty($companyLogoBase64))
          <img src="{{ $companyLogoBase64 }}" alt="Logo" style="height:42px;">
        @else
          <strong>PT. Peroni Karya Sentra</strong>
        @endif
        <div class="subtitle muted">Laporan PTK (Permintaan Tindakan Korektif)</div>
      </td>
      <td class="meta">
        <div class="title">{{ $ptk->number ?? 'DRAFT' }}</div>
        <div class="small muted">
          Dibuat: {{ $ptk->created_at->format('d M Y') }}<br>
          Departemen: {{ $ptk->department->name ?? '-' }}<br>
          Status: <span class="status">{{ $ptk->status }}</span>
        </div>
      </td>
    </tr>
  </table>
  <div class="hr"></div>

  {{-- RINGKASAN --}}
  <table class="grid2 mb10">
    <tr>
      <td>
        <strong>Judul</strong><br>
        {{ $ptk->title }}
      </td>
      <td>
        <strong>Kategori / Subkategori</strong><br>
        {{ $ptk->category->name ?? '-' }} @if($ptk->subcategory) / {{ $ptk->subcategory->name }} @endif
      </td>
    </tr>
    <tr>
      <td>
        <strong>PIC</strong><br>
        {{ $ptk->pic->name ?? '-' }}
      </td>
      <td>
        <strong>Due Date</strong><br>
        {{ optional($ptk->due_date)->format('d M Y') ?? '-' }}
      </td>
    </tr>
  </table>

  {{-- DESKRIPSI (4 bagian) --}}
  <table class="mb10">
    <tr><th>1) Deskripsi Ketidaksesuaian</th></tr>
    <tr><td>{!! nl2br(e($ptk->description_nc ?? '-')) !!}</td></tr>
  </table>
  <table class="mb10">
    <tr><th>2) Evaluasi Masalah</th></tr>
    <tr><td>{!! nl2br(e($ptk->evaluation ?? '-')) !!}</td></tr>
  </table>
  <table class="mb10">
    <tr><th>3a) Koreksi (Perbaikan Masalah)</th></tr>
    <tr><td>{!! nl2br(e($ptk->correction_action ?? '-')) !!}</td></tr>
  </table>
  <table class="mb10">
    <tr><th>3b) Tindakan Korektif (Akar Masalah)</th></tr>
    <tr><td>{!! nl2br(e($ptk->corrective_action ?? '-')) !!}</td></tr>
  </table>

  {{-- LAMPIRAN FOTO (3 kolom + caption, maks 6) --}}
  @php $displayed = $ptk->attachments->take(6); @endphp
  @if($displayed->count() > 0)
    <table class="attachments mb10">
      <tr>
        @foreach($displayed as $i => $att)
          @php
            $img = null;
            if (str_starts_with(strtolower($att->mime ?? ''), 'image/')) {
              $full = \Illuminate\Support\Facades\Storage::disk('public')->path($att->path);
              $img = is_file($full) ? 'data:'.$att->mime.';base64,'.base64_encode(file_get_contents($full)) : null;
            }
          @endphp
          <td style="width:33%; padding:5px;">
            @if($img)<img src="{{ $img }}" alt="lampiran-{{ $i+1 }}">@endif
            @if($att->caption)<div class="caption">{{ $att->caption }}</div>@endif
          </td>
          @if(($i + 1) % 3 === 0)</tr><tr>@endif
        @endforeach

        {{-- filler cell jika jumlah tidak kelipatan 3 --}}
        @php $sisa = $displayed->count() % 3; @endphp
        @if($sisa !== 0)
          @for($k = 0; $k < 3 - $sisa; $k++)
            <td style="width:33%; padding:5px;"></td>
          @endfor
        @endif
      </tr>
    </table>
  @endif

  {{-- TANDA TANGAN --}}
  <table class="mb10">
    <tr>
      <th>Pembuat (Admin)</th>
      <th>Disetujui (Kabag/Manager)</th>
      <th>Direktur</th>
    </tr>
    <tr>
      <td class="sign-box">
        @if(!empty($signAdmin)) <img class="sign-img" src="{{ $signAdmin }}"> @endif
        <div class="small">{{ $ptk->creator->name ?? '-' }}</div>
      </td>
      <td class="sign-box">
        @if($ptk->approved_at && !empty($signApprover)) <img class="sign-img" src="{{ $signApprover }}"> @endif
        <div class="small">
          {{ optional($ptk->approver)->name ?? '-' }}<br>
          {{ optional($ptk->approved_at)->format('d M Y H:i') }}
        </div>
      </td>
      <td class="sign-box">
        @if(!empty($signDirector)) <img class="sign-img" src="{{ $signDirector }}"> @endif
        <div class="small">{{ optional($ptk->director)->name ?? '-' }}</div>
      </td>
    </tr>
  </table>

  {{-- FOOTER AUDIT + QR --}}
  <div class="hr"></div>
  <table>
    <tr>
      <td class="small muted">
        Dokumen hash: {{ $docHash }}<br>
        Dicetak: {{ now()->format('d M Y H:i') }} · IP: {{ request()->ip() }}
      </td>
      <td style="text-align:right;">
        @if(!empty($qrBase64))
          <img src="{{ $qrBase64 }}" style="width:90px; height:90px;">
          <div class="small muted">{{ $verifyUrl }}</div>
        @endif
      </td>
    </tr>
  </table>

</body>
</html>
