<x-layouts.app>
    <h2 class="text-xl font-semibold mb-4">Laporan Periode</h2>

    @if ($errors->any())
        <div class="mb-3 p-3 rounded bg-red-50 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post"
          action="{{ route('exports.range.report') }}"
          class="flex flex-wrap items-end gap-3">
        @csrf

        <label class="block">
            <span class="block text-sm mb-1">Start</span>
            <input type="date" name="start" class="border p-2 rounded" required>
        </label>

        <label class="block">
            <span class="block text-sm mb-1">End</span>
            <input type="date" name="end" class="border p-2 rounded" required>
        </label>

        <button class="px-3 py-2 bg-blue-600 text-white rounded">Generate</button>
    </form>
</x-layouts.app>
