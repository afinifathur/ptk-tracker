@props(['status'])
@php
  $map = [
    'Not Started' => 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    'In Progress' => 'bg-amber-200 text-amber-900 dark:bg-amber-700 dark:text-white',
    'Completed'   => 'bg-emerald-200 text-emerald-900 dark:bg-emerald-700 dark:text-white',
  ];
  $cls = $map[$status] ?? 'bg-gray-200 text-gray-800';
@endphp
<span class="px-2 py-0.5 text-xs font-semibold rounded {{ $cls }}">{{ $status }}</span>
