{{-- Blade snippet for PDF image rendering --}}
@foreach($images as $imgPath)
    <img src="{{ $imgPath }}" style="max-width:100%; height:auto; margin-bottom:10px;">
@endforeach
