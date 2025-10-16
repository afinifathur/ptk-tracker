<x-guest-layout>
  <div x-data="{ showErr: {{ $errors->any() ? 'true' : 'false' }} }">

    {{-- Alert error --}}
    @if($errors->any())
      <div x-show="showErr" x-transition
           class="mb-4 rounded-md border border-red-300 bg-red-50 text-red-800 px-4 py-3 relative">
        <div class="font-semibold mb-1">Login gagal</div>
        <ul class="list-disc ml-5 text-sm">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" x-on:click="showErr=false"
                class="absolute top-2 right-2 text-red-700 hover:text-red-900">✕</button>
      </div>
    @endif

    {{-- Header perusahaan --}}
    <div class="flex flex-col items-center mb-6">
      @if(file_exists(public_path('brand/logo.png')))
        <img src="{{ asset('brand/logo.png') }}" alt="Logo" class="h-16 w-16 object-contain mb-3">
      @endif
      <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">PT. Peroni Karya Sentra</div>
      <div class="text-sm text-gray-500 dark:text-gray-400">PTK Tracker System</div>
    </div>

    {{-- Form login --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
      @csrf

      {{-- Email --}}
      <div>
        <label for="email" class="block text-sm font-medium mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
          class="block w-full rounded-lg border-2 border-gray-300 focus:border-blue-500 
                 focus:ring-1 focus:ring-blue-400 bg-white dark:bg-gray-900 
                 text-gray-900 dark:text-gray-100 px-4 py-3 text-base placeholder-gray-400" 
          placeholder="Masukkan email Anda" />
      </div>

      {{-- Password --}}
      <div x-data="{show:false}">
        <label for="password" class="block text-sm font-medium mb-1">Password</label>
        <div class="relative">
          <input :type="show ? 'text' : 'password'" id="password" name="password" required
            class="block w-full rounded-lg border-2 border-gray-300 focus:border-blue-500 
                   focus:ring-1 focus:ring-blue-400 bg-white dark:bg-gray-900 
                   text-gray-900 dark:text-gray-100 px-4 py-3 text-base pr-12 placeholder-gray-400"
            placeholder="Masukkan password" />
          <button type="button" x-on:click="show=!show"
            class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <span x-show="!show">👁️</span><span x-show="show">🙈</span>
          </button>
        </div>
      </div>

      {{-- Remember & Lupa password --}}
      <div class="flex items-center justify-between text-sm">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="remember"
                 class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
          <span>Remember me</span>
        </label>
        @if (Route::has('password.request'))
          <a class="text-blue-600 hover:underline" href="{{ route('password.request') }}">
            Lupa password?
          </a>
        @endif
      </div>

      {{-- Tombol login --}}
      <div>
        <button type="submit"
          class="w-full inline-flex justify-center items-center gap-2 rounded-lg 
                 bg-blue-600 hover:bg-blue-700 text-white font-semibold 
                 px-4 py-4 transition focus:outline-none focus:ring-2 focus:ring-blue-500">
          Login
        </button>
      </div>
    </form>
  </div>
</x-guest-layout>
