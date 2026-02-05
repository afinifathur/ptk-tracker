<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Verifikasi Dokumen PTK</title>
  @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-50 text-gray-800 min-h-screen flex items-center justify-center">
  <div class="bg-white shadow p-8 rounded-lg w-[600px] text-center">
    <h1 class="text-2xl font-bold mb-4">Verifikasi Dokumen PTK</h1>

    @if($valid)
      <div class="text-green-600 font-semibold mb-2">✅ Dokumen valid</div>
      <p>PTK Nomor: <strong>{{ $ptk->number }}</strong></p>
      <p>Status: {{ $ptk->status }}</p>
      <p>Dibuat: {{ $ptk->created_at->format('d M Y H:i') }}</p>
    @else
      <div class="text-red-600 font-semibold mb-2">❌ Dokumen tidak valid</div>
      <p>Hash tidak sesuai dengan data saat ini.</p>
    @endif

    <hr class="my-4">
    <div class="text-sm text-gray-500">
      Hash input: {{ $hash }}<br>
      Hash sistem: {{ $expected }}
    </div>
  </div>
</body>

</html>