<!-- resources/views/components/ui/stat-card.blade.php -->
@props(['title' => '-', 'value' => '-', 'sub' => null])
<div class="p-5 rounded-2xl shadow-sm bg-white dark:bg-gray-800 border border-gray-100/50 dark:border-gray-700/50">
  <div class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</div>
  <div class="mt-1 text-3xl font-semibold tracking-tight">{{ $value }}</div>
  @if($sub)
    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</div>
  @endif
</div>
