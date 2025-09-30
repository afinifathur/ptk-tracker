<?php

namespace App\Exports;

use App\Models\PTK;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RangeExport implements FromView
{
    public function __construct(public string $start, public string $end){}

    public function view(): View
    {
        $items = PTK::with(['pic','department','category'])
            ->whereBetween('created_at', [$this->start, $this->end])->get();

        return view('exports.ptk_excel', compact('items'));
    }
}
