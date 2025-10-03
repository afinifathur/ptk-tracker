<?php

namespace App\Exports;

use App\Models\PTK;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping};

class RangeExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected string $start,
        protected string $end,
        protected ?int $categoryId    = null,
        protected ?int $departmentId  = null,
        protected ?string $status     = null,
        protected ?int $subcategoryId = null,
    ) {}

    /**
     * Data utama untuk diexport (dengan filter opsional).
     */
    public function collection(): Collection
    {
        $q = PTK::with(['pic', 'department', 'category', 'subcategory'])
            ->whereBetween('created_at', [$this->start, $this->end]);

        if ($this->categoryId)    { $q->where('category_id',    $this->categoryId); }
        if ($this->subcategoryId) { $q->where('subcategory_id', $this->subcategoryId); }
        if ($this->departmentId)  { $q->where('department_id',  $this->departmentId); }
        if ($this->status)        { $q->where('status',         $this->status); }

        return $q->latest()->get();
    }

    /**
     * Header kolom Excel.
     */
    public function headings(): array
    {
        return [
            'Number',
            'Title',
            'Category',
            'Subcategory',
            'Department',
            'PIC',
            'Status',
            'Due Date',
            'Approved At',
            'Created At',
        ];
    }

    /**
     * Mapping setiap baris data ke kolom Excel.
     */
    public function map($p): array
    {
        /** @var \App\Models\PTK $p */
        return [
            $p->number,
            $p->title,
            $p->category->name     ?? '-',
            $p->subcategory->name  ?? '-',
            $p->department->name   ?? '-',
            $p->pic->name          ?? '-',
            $p->status,
            optional($p->due_date)->format('Y-m-d'),
            optional($p->approved_at)->format('Y-m-d H:i'),
            $p->created_at->format('Y-m-d H:i'),
        ];
        // Jika perlu timezone khusus, sesuaikan format() di atas.
    }
}
