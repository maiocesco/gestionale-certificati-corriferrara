@props(['alt' => config('app.name')])

<img src="{{ asset('images/logo.png') }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'dark:hidden']) }}>
<img src="{{ asset('images/logo-dark.png') }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'hidden dark:block']) }}>
