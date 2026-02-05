<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'PTK Tracker') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
  <div class="min-h-screen flex items-center justify-center p-4">
    <!-- Card -->
    <div class="w-full max-w-xl bg-white dark:bg-gray-800 shadow rounded-2xl p-8">
      <!-- Header: Company logo + name -->
      <div class="flex flex-col items-center mb-6">
        @if(file_exists(public_path('brand/logo.png')))
        @endif
      </div>

      {{ $slot }}
    </div>
  </div>
</body>

</html>