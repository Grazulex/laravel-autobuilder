<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AutoBuilder - {{ $flow->name }}</title>

    @if(app()->environment('local') && file_exists(public_path('hot')))
        {{-- Development: Vite dev server --}}
        @vite(['resources/js/autobuilder.js'])
    @else
        {{-- Production: Built assets --}}
        <link rel="stylesheet" href="{{ asset('vendor/autobuilder/css/autobuilder.css') }}">
        <script type="module" src="{{ asset('vendor/autobuilder/js/autobuilder.js') }}" defer></script>
    @endif
</head>
<body class="bg-gray-100">
    <div id="autobuilder-app"></div>

    <script>
        window.flowData = @json($flow);
        window.apiBase = '{{ url(config('autobuilder.routes.prefix', 'autobuilder')) }}/api';
        window.indexUrl = '{{ route('autobuilder.index') }}';
    </script>
</body>
</html>
