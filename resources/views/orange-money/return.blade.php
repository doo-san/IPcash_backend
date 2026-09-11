<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>IPCash</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; text-align: center; }
        p { color: #334155; padding: 0 24px; }
    </style>
</head>
<body>
    <p>
        @if ($status === 'success')
            Paiement effectué. Vous pouvez fermer cette fenêtre.
        @else
            Paiement annulé. Vous pouvez fermer cette fenêtre.
        @endif
    </p>
</body>
</html>
