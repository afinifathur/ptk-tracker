<x-layouts.app>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Approval Log</h2>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('approval_log') }}"
        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 items-end p-4 bg-white dark:bg-gray-800 rounded shadow-sm">

        {{-- Cari --}}
        <div class="col-span-1 md:col-span-1">
            <label class="block text-xs text-gray-500 mb-1">Cari (PTK / Alasan)</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor / alasan reject..."
                class="border p-2 rounded w-full text-sm">
        </div>

        {{-- User --}}
        <div>
            <label class="block text-xs text-gray-500 mb-1">Oleh</label>
            <select name="user_id" class="border p-2 rounded w-full text-sm">
                <option value="">-- Semua User --</option>
                @foreach($users as $uid => $uname)
                    <option value="{{ $uid }}" @selected(request('user_id') == $uid)>{{ $uname }}</option>
                @endforeach
            </select>
        </div>

        {{-- Aksi (Event) --}}
        <div>
            <label class="block text-xs text-gray-500 mb-1">Aksi</label>
            <select name="action" class="border p-2 rounded w-full text-sm">
                <option value="">-- Semua --</option>
                <option value="created" @selected(request('action') == 'created')>Created</option>
                <option value="updated" @selected(request('action') == 'updated')>Updated</option>
                <option value="deleted" @selected(request('action') == 'deleted')>Deleted</option>
                <option value="restored" @selected(request('action') == 'restored')>Restored</option>
            </select>
        </div>

        {{-- Tombol --}}
        <div>
            <button type="submit" class="bg-gray-800 text-white px-5 py-2 rounded text-sm w-full hover:bg-gray-900">
                Filter
            </button>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded shadow">
        <table class="w-full text-sm border-collapse text-left">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-b dark:border-gray-700">
                <tr>
                    <th class="p-3 font-semibold">Waktu</th>
                    <th class="p-3 font-semibold">PTK</th>
                    <th class="p-3 font-semibold">Aksi</th>
                    <th class="p-3 font-semibold">Oleh</th>
                    <th class="p-3 font-semibold">Keterangan / Perubahan</th>
                    <th class="p-3 font-semibold text-right">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($audits as $audit)
                    @php
                        $old = $audit->old_values;
                        $new = $audit->new_values;
                        $ptkNum = $new['number'] ?? ($old['number'] ?? optional($audit->auditable)->number);

                        // Deteksi jenis aksi lebih detail
                        $detailAction = '-';
                        $desc = '';

                        // 1. REJECT STAGE 1
                        if (isset($new['last_reject_stage']) && $new['last_reject_stage'] === 'stage1') {
                            $detailAction = '<span class="font-bold text-red-600">Reject Stage 1</span>';
                            $desc = $new['last_reject_reason'] ?? '';
                        }
                        // 2. REJECT STAGE 2
                        elseif (isset($new['last_reject_stage']) && $new['last_reject_stage'] === 'stage2') {
                            $detailAction = '<span class="font-bold text-red-600">Reject Stage 2</span>';
                            $desc = $new['last_reject_reason'] ?? '';
                        }
                        // 3. APPROVE STAGE 1
                        elseif (isset($new['approved_stage1_at']) && empty($old['approved_stage1_at'])) {
                            $detailAction = '<span class="font-bold text-green-600">Approve Stage 1</span>';
                        }
                        // 4. APPROVE STAGE 2
                        elseif (isset($new['approved_stage2_at']) && empty($old['approved_stage2_at'])) {
                            $detailAction = '<span class="font-bold text-green-600">Approve Stage 2</span>';
                        }
                        // 5. SUBMIT
                        elseif (isset($new['status']) && $new['status'] === 'Submitted' && ($old['status'] ?? '') !== 'Submitted') {
                            $detailAction = '<span class="font-semibold text-blue-600">Submitted</span>';
                        }
                        // 6. CREATE
                        elseif ($audit->event === 'created') {
                            $detailAction = '<span class="text-gray-500">Created</span>';
                        }
                        // 7. RESTORE
                        elseif ($audit->event === 'restored') {
                            $detailAction = '<span class="text-amber-600">Restored</span>';
                        }
                        // 8. DELETE
                        elseif ($audit->event === 'deleted') {
                            $detailAction = '<span class="text-red-800">Deleted</span>';
                        }
                        // 9. Update biasa
                        else {
                            // Tampilkan field apa yang berubah (limit 3 field)
                            $changes = array_keys(array_diff_key($new, array_flip(['updated_at', 'id'])));
                            // filter field internal
                            $changes = array_filter($changes, fn($c) => !in_array($c, ['last_reject_by', 'last_reject_at', 'approved_stage1_by', 'approved_stage2_by']));

                            if (count($changes) > 0) {
                                $detailAction = 'Update';
                                $desc = 'Ubah: ' . implode(', ', array_slice($changes, 0, 3));
                            }
                        }
                      @endphp

                    <tr
                        class="hover:bg-gray-50 dark:hover:bg-gray-800 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800/50">
                        <td class="p-3 whitespace-nowrap text-gray-500">
                            {{ $audit->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="p-3 font-medium">
                            @if($audit->auditable_id)
                                <a href="{{ route('ptk.show', $audit->auditable_id) }}" class="text-blue-600 hover:underline">
                                    {{ $ptkNum ?: 'PTK-' . $audit->auditable_id }}
                                </a>
                            @else
                                <span class="text-gray-400">Terhapus?</span>
                            @endif
                        </td>
                        <td class="p-3">
                            {!! $detailAction !!}
                        </td>
                        <td class="p-3">
                            {{ $audit->user->name ?? 'System' }}
                        </td>
                        <td class="p-3 text-gray-600 dark:text-gray-400 italic">
                            {{ Str::limit($desc, 100) }}
                        </td>
                        <td class="p-3 text-right text-gray-400 text-xs">
                            {{ $audit->ip_address }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">
                            Belum ada log approval/reject yang tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $audits->links() }}
    </div>

</x-layouts.app>