<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>404 | Ticket</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6efe2;
            --panel: #ffffff;
            --text: #111827;
            --muted: #5b6472;
            --accent: #d89d2f;
            --border: rgba(17, 24, 39, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top, rgba(216, 157, 47, 0.18), transparent 30%),
                var(--bg);
            color: var(--text);
        }

        main {
            width: min(560px, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
        }

        .eyebrow {
            margin: 0 0 12px;
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(30px, 5vw, 44px);
            line-height: 1.1;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }
    </style>
</head>
<body>
<main>
    <p class="eyebrow">Erreur 404</p>
    <h1>La page demandée est introuvable.</h1>
    <p>Le tenant ou la ressource demandée n’existe pas, ou l’URL utilisée n’est plus valide.</p>
</main>
</body>
</html>
