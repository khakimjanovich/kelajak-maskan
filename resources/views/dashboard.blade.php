<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $project->name }} Dashboard</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|ibm-plex-mono:400,500" rel="stylesheet" />

        <style>
            :root {
                --ink: #f6f1df;
                --muted: #c8bda0;
                --line: rgba(246, 241, 223, 0.12);
                --panel: rgba(13, 17, 24, 0.8);
                --panel-strong: rgba(19, 25, 36, 0.94);
                --accent: #f0aa3c;
                --accent-soft: rgba(240, 170, 60, 0.18);
                --danger: #ff7f66;
                --bg: #081018;
                --bg-soft: #102033;
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
                    radial-gradient(circle at top, rgba(240, 170, 60, 0.16), transparent 28%),
                    radial-gradient(circle at right, rgba(74, 179, 244, 0.14), transparent 32%),
                    linear-gradient(180deg, var(--bg-soft), var(--bg));
            }

            .shell {
                width: min(1180px, calc(100% - 32px));
                margin: 0 auto;
                padding: 32px 0 48px;
            }

            .hero {
                position: relative;
                overflow: hidden;
                padding: 28px;
                border: 1px solid var(--line);
                border-radius: 28px;
                background:
                    linear-gradient(135deg, rgba(240, 170, 60, 0.16), transparent 45%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01)),
                    var(--panel-strong);
                box-shadow: 0 28px 60px rgba(0, 0, 0, 0.26);
            }

            .eyebrow,
            .meta,
            .chip,
            .list-label,
            .action-command {
                font-family: "IBM Plex Mono", monospace;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin: 0 0 18px;
                color: var(--muted);
                font-size: 0.78rem;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .eyebrow::before {
                content: "";
                width: 48px;
                height: 1px;
                background: linear-gradient(90deg, var(--accent), transparent);
            }

            h1,
            h2,
            h3,
            p,
            ul,
            li,
            dl,
            dd,
            dt {
                margin: 0;
            }

            .hero h1 {
                max-width: 12ch;
                font-size: clamp(2.7rem, 7vw, 5.2rem);
                line-height: 0.94;
                letter-spacing: -0.05em;
            }

            .lede {
                max-width: 680px;
                margin-top: 18px;
                color: var(--muted);
                font-size: 1.05rem;
                line-height: 1.7;
            }

            .meta {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 22px;
                color: var(--muted);
                font-size: 0.83rem;
            }

            .meta span {
                padding: 10px 12px;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.04);
            }

            .notice {
                margin-top: 20px;
                padding: 16px 18px;
                border: 1px solid rgba(240, 170, 60, 0.24);
                border-radius: 18px;
                background: var(--accent-soft);
                color: var(--ink);
                line-height: 1.7;
            }

            .grid {
                display: grid;
                gap: 18px;
                margin-top: 22px;
            }

            .grid-primary {
                grid-template-columns: 1.1fr 0.9fr;
            }

            .grid-secondary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .panel {
                padding: 22px;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: var(--panel);
                backdrop-filter: blur(14px);
            }

            .panel h2 {
                font-size: 1.1rem;
                letter-spacing: 0.02em;
            }

            .section-copy {
                margin-top: 8px;
                color: var(--muted);
                line-height: 1.6;
            }

            .context-grid {
                display: grid;
                gap: 14px;
                margin-top: 18px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .context-card {
                padding: 16px;
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.035);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }

            .list-label {
                display: block;
                margin-bottom: 8px;
                color: var(--muted);
                font-size: 0.76rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .context-card strong,
            .history-title {
                display: block;
                font-size: 1rem;
                line-height: 1.4;
            }

            .stack-list,
            .wants-list,
            .details-list,
            .capability-list,
            .actions-list {
                display: grid;
                gap: 10px;
                margin-top: 18px;
                padding: 0;
                list-style: none;
            }

            .stack-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .chip {
                padding: 12px 14px;
                border-radius: 16px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.04);
                color: var(--ink);
                font-size: 0.83rem;
            }

            .history-card {
                margin-top: 18px;
                padding: 18px;
                border-radius: 20px;
                border: 1px solid var(--line);
                background:
                    linear-gradient(180deg, rgba(240, 170, 60, 0.08), transparent 60%),
                    rgba(255, 255, 255, 0.03);
            }

            .history-title {
                font-size: 1.02rem;
            }

            .history-meta,
            .details-list li,
            .wants-list li {
                color: var(--muted);
            }

            .history-meta {
                margin-top: 10px;
                line-height: 1.7;
            }

            .capability-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .capability-list li,
            .actions-list li,
            .wants-list li {
                padding: 14px 16px;
                border-radius: 18px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.035);
            }

            .action-button {
                width: 100%;
                padding: 14px 16px;
                border: 1px solid rgba(240, 170, 60, 0.28);
                border-radius: 16px;
                background: linear-gradient(180deg, rgba(240, 170, 60, 0.16), rgba(240, 170, 60, 0.06));
                color: var(--ink);
                text-align: left;
                cursor: not-allowed;
                opacity: 0.88;
            }

            .action-title {
                display: block;
                font-size: 0.98rem;
            }

            .action-command {
                display: block;
                margin-top: 6px;
                color: var(--muted);
                font-size: 0.8rem;
            }

            .wants-list li strong {
                display: block;
                color: var(--ink);
                font-size: 0.95rem;
            }

            .wants-list li span {
                display: block;
                margin-top: 6px;
                font-size: 0.9rem;
            }

            .details-list {
                margin-top: 14px;
            }

            .details-list li strong {
                color: var(--ink);
            }

            .empty-state {
                color: var(--muted);
                line-height: 1.7;
            }

            .danger-note {
                color: var(--danger);
            }

            @media (max-width: 980px) {
                .grid-primary,
                .grid-secondary,
                .context-grid,
                .capability-list,
                .stack-list {
                    grid-template-columns: 1fr;
                }

                .shell {
                    width: min(100% - 24px, 1180px);
                    padding-top: 18px;
                }

                .hero,
                .panel {
                    border-radius: 22px;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <section class="hero">
                <p class="eyebrow">Read-first project surface</p>
                <h1>{{ $project->name }}</h1>
                <p class="lede">{{ $projectContext->summary }}</p>

                <div class="meta">
                    <span>Slug: {{ $project->slug }}</span>
                    <span>Phase: {{ $projectContext->current_phase }}</span>
                    <span>Branch: {{ $projectContext->primary_branch }}</span>
                </div>

                @unless ($isReady)
                    <p class="notice">Stored project context has not been written yet. This dashboard is showing the app-defined baseline until history is recorded.</p>
                @endunless
            </section>

            <section class="grid grid-primary">
                <article class="panel">
                    <h2>Current context</h2>
                    <p class="section-copy">Stored project context, the app stack, and the current cycle state surfaced without mutating history.</p>

                    <div class="context-grid">
                        <div class="context-card">
                            <span class="list-label">Summary</span>
                            <strong>{{ $projectContext->summary }}</strong>
                        </div>

                        <div class="context-card">
                            <span class="list-label">Current phase</span>
                            <strong>{{ $projectContext->current_phase }}</strong>
                        </div>

                        <div class="context-card">
                            <span class="list-label">Repo path</span>
                            <strong>{{ $projectContext->repo_path }}</strong>
                        </div>

                        <div class="context-card">
                            <span class="list-label">Conventions</span>
                            <strong>{{ $projectContext->conventions[0] ?? 'No convention recorded.' }}</strong>
                        </div>
                    </div>

                    <ul class="stack-list">
                        @foreach ($projectContext->stack as $item)
                            <li class="chip">{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>

                <article class="panel">
                    <h2>Open cycle</h2>
                    <p class="section-copy">The newest unfinished want in the local history spine.</p>

                    @if ($openCycle)
                        <div class="history-card">
                            <span class="list-label">Want #{{ $openCycle->want->id }}</span>
                            <strong class="history-title">{{ $openCycle->want->title }}</strong>

                            <ul class="details-list">
                                <li><strong>Status:</strong> {{ $openCycle->want->status }}</li>
                                <li><strong>Action status:</strong> {{ $openCycle->actionRun?->status ?? 'none' }}</li>
                                <li><strong>Reason:</strong> <span class="{{ str_contains(strtolower($openCycle->openReason ?? ''), 'defect') ? 'danger-note' : '' }}">{{ $openCycle->openReason }}</span></li>
                            </ul>
                        </div>
                    @else
                        <p class="empty-state">No open cycle is currently stored for {{ $project->name }}.</p>
                    @endif
                </article>
            </section>

            <section class="grid grid-secondary">
                <article class="panel">
                    <h2>Capabilities</h2>
                    <p class="section-copy">Current read and write-safe artisan surfaces already available in the app.</p>

                    <ul class="capability-list">
                        @foreach ($capabilities as $capability)
                            <li class="chip">{{ $capability }}</li>
                        @endforeach
                    </ul>
                </article>

                <article class="panel">
                    <h2>Available actions</h2>
                    <p class="section-copy">Next kelajak-maskan actions. These remain informational in this phase.</p>

                    <ul class="actions-list">
                        @foreach ($availableActions as $action)
                            <li>
                                <button class="action-button" type="button" disabled aria-disabled="true">
                                    <span class="action-title">{{ $action }}</span>
                                    <span class="action-command">CLI-first flow only in phase 1</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </article>

                <article class="panel">
                    <h2>Latest completed outcome</h2>
                    <p class="section-copy">The newest fully closed cycle with a stored outcome.</p>

                    @if ($latestCompletedOutcome)
                        <div class="history-card">
                            <span class="list-label">Want #{{ $latestCompletedOutcome->want->id }}</span>
                            <strong class="history-title">{{ $latestCompletedOutcome->want->title }}</strong>
                            <p class="history-meta">{{ $latestCompletedOutcome->outcomeLog?->outcome }}</p>
                        </div>
                    @else
                        <p class="empty-state">No completed outcome has been recorded yet.</p>
                    @endif
                </article>
            </section>

            <section class="panel">
                <h2>Latest three wants</h2>
                <p class="section-copy">Recent intent, newest first, pulled from the existing history summary read model.</p>

                <ul class="wants-list">
                    @foreach ($recentWants as $want)
                        <li>
                            <strong>{{ $want->title }}</strong>
                            <span>Status: {{ $want->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </body>
</html>
