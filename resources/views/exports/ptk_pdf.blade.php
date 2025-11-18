{{-- resources/views/exports/ptk_pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan PTK - {{ $ptk->number ?? 'DRAFT' }}</title>
<style>
  /* Tipografi */
  *{ font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size:11px; line-height:1.35; }
  body{ margin:10mm 10mm; }

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

  /* Status */
  .status{ font-weight:600; }

  /* Tanda tangan */
  .small{ font-size:10px; }
  .sig-title{ font-weight:600; margin-bottom:6px; text-align:center; }
  .sig-box{ position:relative; height:100px; text-align:center; border:0; }
  .sig-img{ height:100px; position:relative; top:-10px; } /* naik sedikit agar overlap tulisan */

  /* Lampiran */
  .attachments img{ width:100%; height:auto; display:block; }
  .caption{ font-size:10px; color:#555; margin-top:4px; }

  /* Spacing */
  .mb6{ margin-bottom:6px; } .mb10{ margin-bottom:10px; }
</style>
</head>
<body>

  {{-- HEADER --}}
  <table class="mb6">
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
          Departemen: {{ $ptk->department->name ?? '-' }}<br>
          Status: <span class="status">{{ $ptk->status }}</span>
        </div>
      </td>
    </tr>
  </table>
  <div class="hr"></div>

  {{-- TANGGAL FORM & TANGGAL INPUT --}}
  <table class="w-full text-sm mb10" style="border:1px solid #d7dbe1;">
    <tr>
      <td style="width:30%; border-right:1px solid #d7dbe1;"><strong>Tanggal Form</strong></td>
      <td>: {{ optional($ptk->form_date)->format('d M Y') }}</td>
    </tr>
    <tr>
      <td style="border-right:1px solid #d7dbe1;"><strong>Tanggal Input</strong></td>
      <td>: {{ $ptk->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
    </tr>
  </table>

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
    <tr><td>{!! nl2br(e($ptk->desc_nc ?? '-')) !!}</td></tr>
  </table>
  <table class="mb10">
    <tr><th>2) Evaluasi Masalah</th></tr>
    <tr><td>{!! nl2br(e($ptk->evaluation ?? '-')) !!}</td></tr>
  </table>
  <table class="mb10">
    <tr><th>3a) Koreksi (Perbaikan Masalah) dan Tindakan Korektif (Akar Masalah)</th></tr>
    <tr><td>{!! nl2br(e($ptk->action_correction ?? '-')) !!}</td></tr>
  </table>
  <table class="mb10">
    <tr><th>4) Hasil Uji Coba</th></tr>
    <tr><td>{!! nl2br(e($ptk->action_corrective ?? '-')) !!}</td></tr>
  </table>

  {{-- LAMPIRAN FOTO (maks 6) --}}
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

        {{-- filler cell jika tidak kelipatan 3 --}}
        @php $sisa = $displayed->count() % 3; @endphp
        @if($sisa !== 0)
          @for($k = 0; $k < 3 - $sisa; $k++)
            <td style="width:33%; padding:5px;"></td>
          @endfor
        @endif
      </tr>
    </table>
  @endif

 {{-- ====== TANDA TANGAN (Stage 1 & Stage 2) ====== --}}
<div class="hr"></div>

@php
  $signDir = public_path('signatures');

  $u1 = $ptk->approverStage1;
  $u2 = $ptk->approverStage2;

  // per-user override
  $u1File = $u1 ? $signDir . '/users/' . $u1->id . '.png' : null;
  $u2File = $u2 ? $signDir . '/users/' . $u2->id . '.png' : null;

  // fallback per-role stage 1
  $roleStage1 = $u1 && method_exists($u1, 'roles') ? ($u1->roles->pluck('name')->first() ?? null) : null;
  $stage1RoleFile = match($roleStage1) {
      'kabag_qc'   => $signDir . '/kabag_qc.png',
      'manager_hr' => $signDir . '/manager_hr.png',
      default      => $signDir . '/kabag_qc.png',
  };

  // fallback stage 2
  $stage2RoleFile = $signDir . '/director.png';

  // pilih final file
  $stage1File = ($u1File && file_exists($u1File)) ? $u1File : (file_exists($stage1RoleFile) ? $stage1RoleFile : null);
  $stage2File = ($u2File && file_exists($u2File)) ? $u2File : (file_exists($stage2RoleFile) ? $stage2RoleFile : null);
@endphp

<table style="width:100%; border:0; margin-top:12px; margin-bottom:0;">
  <tr>
    {{-- Stage 1 --}}
    <td style="width:50%; vertical-align:top; padding-right:16px; border:0;">
      <div class="sig-title">Disetujui</div>
      <div class="sig-box" style="height:130px;"> {{-- tinggi diperbesar --}}
        @if($ptk->approved_stage1_at)
          @if($stage1File)
            <img src="{{ $stage1File }}" alt="ttd stage1" class="sig-img" style="height:115px; top:-12px;">
          @endif
          <div class="small" style="margin-top:-4px;">
            <div>{{ $u1->name ?? '' }}</div>
            <div>{{ $ptk->approved_stage1_at->format('d M Y H:i') }}</div>
          </div>
        @else
          <div class="small muted">Belum disetujui</div>
        @endif
      </div>
    </td>

    {{-- Stage 2 --}}
    <td style="width:50%; vertical-align:top; padding-left:16px; border:0;">
      <div class="sig-title">Disetujui</div>
      <div class="sig-box" style="height:130px;"> {{-- tinggi diperbesar --}}
        @if($ptk->approved_stage2_at)
          @if($stage2File)
            <img src="{{ $stage2File }}" alt="ttd director" class="sig-img" style="height:115px; top:-12px;">
          @endif
          <div class="small" style="margin-top:-4px;">
            <div>{{ $u2->name ?? '' }}</div>
            <div>{{ $ptk->approved_stage2_at->format('d M Y H:i') }}</div>
          </div>
        @else
          <div class="small muted">Belum disetujui</div>
        @endif
      </div>
    </td>
  </tr>
</table>

{{-- spacer fisik agar footer tidak menimpa tanggal, aman di semua PDF engine --}}
<div style="height:18px;"></div>

{{-- ====== FOOTER AUDIT + QR ====== --}}
<div class="hr" style="margin:0 0 8px 0;"></div>
<table style="width:100%; border-collapse:separate; border-spacing:0;">
  <tr>
    <td class="small muted" style="border:1px solid #d7dbe1; padding:8px 10px; vertical-align:top;">
      Dokumen hash: {{ $docHash }}<br>
      Dicetak: {{ now()->format('d M Y H:i') }} · IP: {{ request()->ip() }}
    </td>
    <td style="text-align:right; border:0; vertical-align:top; padding-left:10px;">
      @if(!empty($qrBase64))
        <img src="{{ $qrBase64 }}" style="width:90px; height:90px;">
        <div class="small muted" style="margin-top:2px;">{{ $verifyUrl }}</div>
      @endif
    </td>
  </tr>
</table>
