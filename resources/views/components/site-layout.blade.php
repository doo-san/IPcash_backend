<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'IPCash' }} — La super-app financière de l'UEMOA</title>
    <meta name="description" content="{{ $description ?? "Transférez, épargnez, payez et changez de devise depuis une seule application. IPCash, la néobanque pensée pour le Sénégal et l'UEMOA." }}">
    <link rel="icon" href="data:,">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    @include('partials.site-styles')
</head>
<body>

    @include('partials.site-header')

    {{ $slot }}

    @include('partials.site-footer')

    @include('partials.site-scripts')
</body>
</html>
