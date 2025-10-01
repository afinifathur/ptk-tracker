<?php

namespace App\Exports;

use App\Models\PTK;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\{FromCollection,WithHeadings,WithMapping};

class PTKExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $q = PTK::with(['pic','department','category'])->latest();
        if ($s = $this->request->get('status')) $q->where('status',$s);
        return $q->get();
    }

    public function headings(): array
    {
        return ['Number','Title','Category','Department','PIC','Status','Due Date','Approved At','Created At'];
    }

    public function map($p): array
    {
        return [
            $p->number,
            $p->title,
            $p->category->name ?? '-',
            $p->department->name ?? '-',
            $p->pic->name ?? '-',
            $p->status,
            optional($p->due_date)->format('Y-m-d'),
            optional($p->approved_at)->format('Y-m-d H:i'),
            $p->created_at->format('Y-m-d H:i'),
        ];
    }
}
