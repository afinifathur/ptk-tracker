<?php

namespace App\Services;

use App\Models\{PTK, PTKSequence, Department};
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PTKNumberingService
{
    /**
     * Wrapper agar bisa dipanggil dari controller:
     * $svc->generate($ptk)
     */
    public function generate(PTK $ptk, \DateTimeInterface $when = null): string
    {
        // Pastikan ada department_id
        if (!$ptk->department_id) {
            $ptk->loadMissing('department');
            $ptk->department_id = $ptk->department_id ?? $ptk->department?->id;
        }

        if (!$ptk->department_id) {
            throw new \RuntimeException('PTK tidak memiliki department_id.');
        }

        // Gunakan nextNumber() yang sudah ada
        return $this->nextNumber($ptk->department_id, $when ?? now());
    }

    /**
     * Generate nomor PTK.
     * Contoh hasil: PTK/QC/2025/10/001
     */
    public function nextNumber(int $departmentId, \DateTimeInterface $when): string
    {
        $Y = (int) $when->format('Y');
        $m = (int) $when->format('m');

        return DB::transaction(function () use ($departmentId, $Y, $m) {
            // 1) Lock baris sequence untuk DEPT+YEAR+MONTH
            $seq = PTKSequence::where('department_id', $departmentId)
                ->where('year',  $Y)
                ->where('month', $m)
                ->lockForUpdate()
                ->first();

            // 2) Jika belum ada, buat; handle race dengan unique constraint
            if (!$seq) {
                try {
                    $seq = PTKSequence::create([
                        'department_id' => $departmentId,
                        'year'          => $Y,
                        'month'         => $m,
                        'last_run'      => 0,
                    ]);
                } catch (QueryException) {
                    // Transaksi lain sudah membuat; ambil ulang & lock
                    $seq = PTKSequence::where('department_id', $departmentId)
                        ->where('year',  $Y)
                        ->where('month', $m)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            // 3) Increment counter
            $seq->last_run += 1;
            $seq->save();

            // 4) Format nomor
            $run  = str_pad((string) $seq->last_run, 3, '0', STR_PAD_LEFT);
            $dept = Department::query()->select('code', 'name')->find($departmentId);
            $code = $dept?->code ?: $this->deriveCode($dept?->name);
            $mm   = str_pad((string) $m, 2, '0', STR_PAD_LEFT);

            return "PTK/{$code}/{$Y}/{$mm}/{$run}";
        }, 5);
    }

    /**
     * Derive kode departemen dari nama jika kolom code kosong.
     * - Jika mengandung 'QC'/'HR'/'K3' → pakai itu
     * - Jika tidak, ambil inisial huruf besar kata (maks 3)
     * - Fallback: 3 huruf pertama nama
     */
    protected function deriveCode(?string $name): string
    {
        if (!$name) {
            return 'GEN';
        }

        $n = strtoupper($name);

        foreach (['QC', 'HR', 'K3'] as $kw) {
            if (str_contains($n, $kw)) {
                return $kw;
            }
        }

        if (preg_match_all('/\b([A-Z])/', $n, $m) && !empty($m[1])) {
            return implode('', array_slice($m[1], 0, 3));
        }

        return substr($n, 0, 3) ?: 'GEN';
    }
}
