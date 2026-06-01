<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — MaketuShop</title>

    <!-- Open Graph / WhatsApp -->
    <meta property="og:site_name" content="MaketuShop">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:url" content="{{ $url }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">

    <!-- Redirection vers la vraie fiche pour les humains -->
    <meta http-equiv="refresh" content="0; url={{ $url }}">
    <link rel="canonical" href="{{ $url }}">
    <script>window.location.replace(@json($url));</script>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 40px;">
    <p>Redirection vers <a href="{{ $url }}">{{ $title }}</a>…</p>
</body>
</html>
