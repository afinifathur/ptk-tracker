<?php
namespace App\Services;

use App\Models\{PTKSequence, Department};
use Illuminate\Support\Facades\DB;

class PTKNumberingService
{
    public function nextNumber(int $departmentId, \DateTimeInterface $when): string
    {
        $y = (int)$when->format('y');       // 25
        $Y = (int)$when->format('Y');       // 2025
        $m = (int)$when->format('m');       // 10

        return DB::transaction(function () use ($departmentId, $Y, $y, $m) {
            // kunci baris sequence (upsert)
            $seq = PTKSequence::where(compact('departmentId','Y','m'))->lockForUpdate()->first();
            if (!$seq) {
                $seq = PTKSequence::create([
                    'department_id' => $departmentId,
                    'year'          => $Y,
                    'month'         => $m,
                    'last_run'      => 0,
                ]);
                // lock ulang supaya aman di MySQL <8 (opsional)
                $seq->refresh();
            }
            $seq->last_run += 1;
            $seq->save();

            $run = str_pad((string)$seq->last_run, 3, '0', STR_PAD_LEFT);

            // kode dept: pakai kolom code jika ada; fallback dari nama
            $dept = Department::find($departmentId);
            $code = $dept?->code ?: $this->deriveCode($dept?->name);

            return "PTK/{$code}/{$y}/".str_pad((string)$m,2,'0',STR_PAD_LEFT)."/{$run}";
        }, 5);
    }

    protected function deriveCode(?string $name): string
    {
        if (!$name) return 'GEN';
        $n = strtoupper($name);
        if (str_contains($n,'QC')) return 'QC';
        if (str_contains($n,'HR')) return 'HR';
        if (str_contains($n,'K3')) return 'K3';
        // ambil huruf besar awal kata, fallback 3 huruf pertama
        preg_match_all('/\b([A-Z])/',$n,$m);
        return $m[1] ? implode('', array_slice($m[1],0,3)) : substr($n,0,3);
    }
}
