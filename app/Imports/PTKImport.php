<?php

namespace App\Imports;

use App\Models\{PTK, Category, Department};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection,WithHeadingRow,WithValidation};
use Illuminate\Validation\Rule;

class PTKImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function rules(): array
    {
        return [
            '*.number'      => ['required','string','distinct',Rule::unique('ptks','number')],
            '*.title'       => ['required','string','max:255'],
            '*.description' => ['required','string'],
            '*.category'    => ['required','string'],
            '*.department'  => ['required','string'],
            '*.pic_user_id' => ['required','integer','exists:users,id'],
            '*.due_date'    => ['required','date'],
            '*.status'      => ['required', Rule::in(['Not Started','In Progress','Completed'])],
        ];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $category   = Category::firstOrCreate(['name'=>trim($row['category'])]);
            $department = Department::firstOrCreate(['name'=>trim($row['department'])]);

            PTK::create([
                'number'        => trim($row['number']),
                'title'         => trim($row['title']),
                'description'   => trim($row['description']),
                'category_id'   => $category->id,
                'department_id' => $department->id,
                'pic_user_id'   => (int)$row['pic_user_id'],
                'status'        => trim($row['status']),
                'due_date'      => date('Y-m-d', strtotime($row['due_date'])),
            ]);
        }
    }
}
