<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PTK;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifyController extends Controller
{
    public function show(PTK $ptk, string $hash): View
    {
        $expected = hash('sha256', json_encode([
            'id' => $ptk->id,
            'number' => $ptk->number,
            'status' => $ptk->status,
            'due' => $ptk->due_date?->format('Y-m-d'),
            'approved_at' => $ptk->approved_at?->format('c'),
            'updated_at' => $ptk->updated_at?->format('c'),
        ]));

        $valid = hash_equals($expected, $hash);

        return view('verify.result', compact('ptk', 'hash', 'expected', 'valid'));
    }
}
