<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Want #{{ $wantView['id'] }} · {{ $project->name }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|ibm-plex-mono:400,500" rel="stylesheet" />

        <style>
            :root {
                --ink: #f6efdf;
                --muted: #c5b89b;
                --line: rgba(246, 239, 223, 0.1);
                --panel: rgba(15, 20, 29, 0.88);
                --panel-strong: rgba(19, 26, 37, 0.95);
                --accent: #f0aa3c;
                --accent-soft: rgba(240, 170, 60, 0.14);
                --bg: #071018;
                --bg-soft: #11263b;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                color: var(--ink);
                font-family: "Space Grotesk", sans-serif;
                background:
                    radial-gradient(circle at top, rgba(240, 170, 60, 0.18), transparent 28%),
                    linear-gradient(180deg, var(--bg-soft), var(--bg));
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            h1,
            h2,
            p,
            ul,
            li {
                margin: 0;
            }

            .shell {
                width: min(940px, calc(100% - 32px));
                margin: 0 auto;
                padding: 28px 0 48px;
            }

            .hero,
            .panel {
                border: 1px solid var(--line);
                border-radius: 28px;
                background: var(--panel);
                backdrop-filter: blur(14px);
                box-shadow: 0 28px 60px rgba(0, 0, 0, 0.24);
            }

            .hero {
                padding: 28px;
                background:
                    linear-gradient(135deg, rgba(240, 170, 60, 0.16), transparent 42%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01)),
                    var(--panel-strong);
            }

            .eyebrow,
            .stage {
                font-family: "IBM Plex Mono", monospace;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 18px;
                color: var(--muted);
                font-size: 0.78rem;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .eyebrow::before {
                content: "";
                width: 52px;
                height: 1px;
                background: linear-gradient(90deg, var(--accent), transparent);
            }

            .hero h1 {
                font-size: clamp(2.4rem, 6vw, 4.4rem);
                line-height: 0.96;
                letter-spacing: -0.05em;
            }

            .hero p {
                margin-top: 16px;
                color: var(--muted);
                line-height: 1.72;
            }

            .stage {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-top: 18px;
                padding: 8px 12px;
                border-radius: 999px;
                border: 1px solid rgba(240, 170, 60, 0.24);
                background: var(--accent-soft);
                font-size: 0.76rem;
                letter-spacing: 0.08em;
                text-transform: lowercase;
            }

            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 18px;
                padding: 10px 14px;
                border-radius: 14px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.05);
            }

            .grid {
                display: grid;
                gap: 18px;
                margin-top: 22px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .panel {
                padding: 22px;
            }

            .panel h2 {
                font-size: 1.08rem;
                letter-spacing: 0.02em;
            }

            .copy,
            .detail-list li {
                margin-top: 10px;
                color: var(--muted);
                line-height: 1.72;
            }

            .detail-list {
                margin: 16px 0 0;
                padding: 0;
                list-style: none;
                display: grid;
                gap: 10px;
            }

            .detail-list strong {
                color: var(--ink);
            }

            .empty {
                color: var(--muted);
                line-height: 1.7;
            }

            @media (max-width: 900px) {
                .grid {
                    grid-template-columns: 1fr;
                }

                .shell {
                    width: min(100% - 24px, 940px);
                    padding-top: 18px;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <section class="hero">
                <p class="eyebrow">Read-only want detail</p>
                <h1>{{ $wantView['title'] }}</h1>
                <p>{{ $projectContext->summary }}</p>
                <span class="stage">{{ $wantView['stage'] }}</span>

                <div>
                    <a class="back-link" href="{{ url('/') }}">Back to dashboard</a>
                </div>
            </section>

            <section class="grid">
                <article class="panel">
                    <h2>Latest written plan</h2>

                    @if ($wantView['plan_text'])
                        <p class="copy">{{ $wantView['plan_text'] }}</p>
                    @else
                        <p class="empty">No written plan text has been recorded for this want yet.</p>
                    @endif
                </article>

                <article class="panel">
                    <h2>Grounded summary</h2>

                    @if ($wantView['grounded_summary'])
                        <p class="copy">{{ $wantView['grounded_summary'] }}</p>
                    @elseif ($wantView['validation_summary'])
                        <p class="copy">{{ $wantView['validation_summary'] }}</p>
                    @else
                        <p class="empty">No grounded summary has been recorded for this want yet.</p>
                    @endif
                </article>

                <article class="panel">
                    <h2>Current detail</h2>

                    <ul class="detail-list">
                        <li><strong>Want id:</strong> {{ $wantView['id'] }}</li>
                        <li><strong>Status:</strong> {{ $wantView['status'] }}</li>
                        <li><strong>Stage:</strong> {{ $wantView['stage'] }}</li>
                        <li><strong>Action status:</strong> {{ $wantView['action_status'] ?? 'none' }}</li>
                        <li><strong>Open reason:</strong> {{ $wantView['open_reason'] ?? 'none' }}</li>
                    </ul>
                </article>

                <article class="panel">
                    <h2>Latest outcome</h2>

                    @if ($wantView['outcome'])
                        <p class="copy">{{ $wantView['outcome'] }}</p>
                    @else
                        <p class="empty">No outcome has been logged for this want yet.</p>
                    @endif
                </article>
            </section>
        </div>
    </body>
</html>
