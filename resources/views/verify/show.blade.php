<x-layouts.app>
  <h2 class="text-xl font-semibold mb-2">Verifikasi Dokumen PTK</h2>
  <p class="mb-3">Nomor: <strong>{{ $ptk->number }}</strong> — Status: <strong>{{ $ptk->status }}</strong></p>
  @if($valid)
    <div class="p-3 rounded bg-emerald-100 text-emerald-800">✅ Hash valid. Dokumen otentik.</div>
  @else
    <div class="p-3 rounded bg-rose-100 text-rose-800">❌ Hash tidak cocok. Dokumen berubah atau link salah.</div>
  @endif
  <div class="mt-4">
    <a class="underline" href="{{ route('ptk.show',$ptk) }}">Lihat PTK</a>
  </div>
</x-layouts.app>
