<?php

namespace App\Exports;

use App\Models\PTK;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping};

class RangeExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected string $start,
        protected string $end,
        protected ?int $categoryId = null,
        protected ?int $departmentId = null,
        protected ?string $status = null,
    ) {}

    public function collection()
    {
        $q = PTK::with(['pic','department','category'])
            ->whereBetween('created_at', [$this->start, $this->end]);

        if ($this->categoryId)   $q->where('category_id',   $this->categoryId);
        if ($this->departmentId) $q->where('department_id', $this->departmentId);
        if ($this->status)       $q->where('status',        $this->status);

        return $q->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Number','Title','Category','Department','PIC',
            'Status','Due Date','Approved At','Created At'
        ];
    }

    public function map($p): array
    {
        return [
            $p->number,
            $p->title,
            $p->category->name   ?? '-',
            $p->department->name ?? '-',
            $p->pic->name        ?? '-',
            $p->status,
            optional($p->due_date)->format('Y-m-d'),
            optional($p->approved_at)->format('Y-m-d H:i'),
            $p->created_at->format('Y-m-d H:i'),
        ];
    }
}
