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
                --ink: #f6efdf;
                --muted: #c5b89b;
                --line: rgba(246, 239, 223, 0.1);
                --panel: rgba(15, 20, 29, 0.86);
                --panel-strong: rgba(19, 26, 37, 0.95);
                --accent: #f0aa3c;
                --accent-soft: rgba(240, 170, 60, 0.14);
                --accent-cool: rgba(91, 188, 255, 0.16);
                --danger: #ff8570;
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
                    radial-gradient(circle at right, rgba(91, 188, 255, 0.14), transparent 30%),
                    linear-gradient(180deg, var(--bg-soft), var(--bg));
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .shell {
                width: min(1200px, calc(100% - 32px));
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
                position: relative;
                overflow: hidden;
                padding: 28px;
                background:
                    linear-gradient(135deg, rgba(240, 170, 60, 0.16), transparent 42%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01)),
                    var(--panel-strong);
            }

            .eyebrow,
            .mono,
            .meta,
            .stage,
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
                width: 52px;
                height: 1px;
                background: linear-gradient(90deg, var(--accent), transparent);
            }

            h1,
            h2,
            h3,
            p,
            ul,
            li {
                margin: 0;
            }

            .hero h1 {
                max-width: 12ch;
                font-size: clamp(2.9rem, 7vw, 5.3rem);
                line-height: 0.94;
                letter-spacing: -0.05em;
            }

            .lede {
                max-width: 760px;
                margin-top: 18px;
                color: var(--muted);
                font-size: 1.06rem;
                line-height: 1.75;
            }

            .meta {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 22px;
                color: var(--muted);
                font-size: 0.82rem;
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
                line-height: 1.7;
            }

            .grid {
                display: grid;
                gap: 18px;
                margin-top: 22px;
            }

            .grid-primary {
                grid-template-columns: 0.95fr 1.05fr;
            }

            .grid-secondary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .panel {
                padding: 22px;
            }

            .panel h2 {
                font-size: 1.14rem;
                letter-spacing: 0.02em;
            }

            .section-copy {
                margin-top: 8px;
                color: var(--muted);
                line-height: 1.65;
            }

            .context-grid,
            .stats-grid {
                display: grid;
                gap: 14px;
                margin-top: 18px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .context-card,
            .focus-card,
            .history-card,
            .want-card {
                padding: 18px;
                border-radius: 22px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.035);
            }

            .context-card strong,
            .history-card strong,
            .want-card strong,
            .focus-card strong {
                display: block;
                font-size: 1rem;
                line-height: 1.45;
            }

            .card-label,
            .focus-label,
            .link-label {
                display: block;
                margin-bottom: 8px;
                color: var(--muted);
                font-size: 0.76rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .stack-list,
            .capability-list,
            .actions-list,
            .wants-list,
            .detail-list {
                display: grid;
                gap: 10px;
                margin-top: 18px;
                padding: 0;
                list-style: none;
            }

            .stack-list,
            .capability-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .chip,
            .capability-list li {
                padding: 12px 14px;
                border-radius: 16px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.04);
                font-size: 0.84rem;
            }

            .want-card {
                display: grid;
                gap: 12px;
            }

            .want-head {
                display: flex;
                gap: 12px;
                justify-content: space-between;
                align-items: flex-start;
            }

            .stage {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 96px;
                padding: 8px 10px;
                border-radius: 999px;
                border: 1px solid rgba(240, 170, 60, 0.24);
                background: var(--accent-soft);
                color: var(--ink);
                font-size: 0.76rem;
                letter-spacing: 0.08em;
                text-transform: lowercase;
            }

            .stage.blocked {
                border-color: rgba(255, 133, 112, 0.28);
                background: rgba(255, 133, 112, 0.12);
                color: var(--danger);
            }

            .stage.acting {
                border-color: rgba(91, 188, 255, 0.28);
                background: var(--accent-cool);
            }

            .stage.completed {
                border-color: rgba(137, 219, 145, 0.28);
                background: rgba(137, 219, 145, 0.14);
            }

            .want-meta,
            .history-meta,
            .detail-list li,
            .focus-copy {
                color: var(--muted);
                line-height: 1.7;
            }

            .want-meta {
                font-size: 0.94rem;
            }

            .detail-list {
                margin-top: 14px;
            }

            .detail-list strong {
                color: var(--ink);
            }

            .focus-card {
                margin-top: 18px;
                background:
                    linear-gradient(180deg, rgba(240, 170, 60, 0.08), transparent 62%),
                    rgba(255, 255, 255, 0.035);
            }

            .focus-copy {
                font-size: 1rem;
            }

            .focus-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 16px;
            }

            .read-link {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 14px;
                border-radius: 14px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.05);
            }

            .read-link:hover {
                border-color: rgba(240, 170, 60, 0.32);
                background: rgba(240, 170, 60, 0.08);
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
                opacity: 0.9;
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
                .stats-grid,
                .stack-list,
                .capability-list {
                    grid-template-columns: 1fr;
                }

                .want-head {
                    flex-direction: column;
                }

                .shell {
                    width: min(100% - 24px, 1200px);
                    padding-top: 18px;
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
                    <h2>Project lens</h2>
                    <p class="section-copy">The current project story, conventions, and stack surfaced without leaking local repo paths.</p>

                    <div class="context-grid">
                        <div class="context-card">
                            <span class="card-label">Current phase</span>
                            <strong>{{ $projectContext->current_phase }}</strong>
                        </div>

                        <div class="context-card">
                            <span class="card-label">Primary branch</span>
                            <strong>{{ $projectContext->primary_branch }}</strong>
                        </div>

                        <div class="context-card">
                            <span class="card-label">Read discipline</span>
                            <strong>{{ $projectContext->conventions[0] ?? 'No convention recorded.' }}</strong>
                        </div>

                        <div class="context-card">
                            <span class="card-label">Builder posture</span>
                            <strong>{{ $projectContext->conventions[1] ?? 'No additional convention recorded.' }}</strong>
                        </div>
                    </div>

                    <ul class="stack-list">
                        @foreach ($projectContext->stack as $item)
                            <li class="chip">{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>

                <article class="panel">
                    <h2>Want focus</h2>
                    <p class="section-copy">The latest readable plan or grounded summary for the most useful active want to review next.</p>

                    @if ($highlightedWant)
                        <div class="focus-card">
                            <span class="focus-label">{{ $highlightedWant['focus_label'] }}</span>
                            <div class="want-head">
                                <div>
                                    <strong>{{ $highlightedWant['title'] }}</strong>
                                    <p class="want-meta">Status: {{ $highlightedWant['status'] }} · Stage: {{ $highlightedWant['stage'] }}</p>
                                </div>

                                <span class="stage {{ $highlightedWant['stage'] }}">{{ $highlightedWant['stage'] }}</span>
                            </div>

                            <p class="focus-copy">{{ $highlightedWant['focus_text'] }}</p>

                            @if ($highlightedWant['grounded_summary'])
                                <ul class="detail-list">
                                    <li><strong>Grounded summary:</strong> {{ $highlightedWant['grounded_summary'] }}</li>
                                </ul>
                            @endif

                            <div class="focus-actions">
                                <a class="read-link" href="{{ route('wants.show', ['want' => $highlightedWant['id']]) }}">
                                    <span class="link-label">Read-only detail</span>
                                    <strong>Open want #{{ $highlightedWant['id'] }}</strong>
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="empty-state">No active wants are currently stored for {{ $project->name }}.</p>
                    @endif
                </article>
            </section>

            <section class="panel">
                <h2>Active wants</h2>
                <p class="section-copy">Real stages derived from stored history records, newest active wants first.</p>

                @if ($activeWants !== [])
                    <ul class="wants-list">
                        @foreach ($activeWants as $want)
                            <li class="want-card">
                                <div class="want-head">
                                    <div>
                                        <strong>{{ $want['title'] }}</strong>
                                        <p class="want-meta">Status: {{ $want['status'] }} · Stage: {{ $want['stage'] }}</p>
                                    </div>

                                    <span class="stage {{ $want['stage'] }}">{{ $want['stage'] }}</span>
                                </div>

                                <p class="want-meta">{{ $want['summary'] }}</p>

                                <div class="focus-actions">
                                    <a class="read-link" href="{{ route('wants.show', ['want' => $want['id']]) }}">
                                        <span class="link-label">Read-only detail</span>
                                        <strong>Review want #{{ $want['id'] }}</strong>
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="empty-state">No active wants have been recorded yet.</p>
                @endif
            </section>

            <section class="grid grid-secondary">
                <article class="panel">
                    <h2>Open cycle</h2>
                    <p class="section-copy">The newest unfinished want in the local history spine.</p>

                    @if ($openCycle)
                        <div class="history-card">
                            <span class="card-label">Want #{{ $openCycle->want->id }}</span>
                            <strong>{{ $openCycle->want->title }}</strong>

                            <ul class="detail-list">
                                <li><strong>Status:</strong> {{ $openCycle->want->status }}</li>
                                <li><strong>Action status:</strong> {{ $openCycle->actionRun?->status ?? 'none' }}</li>
                                <li><strong>Reason:</strong> <span class="{{ str_contains(strtolower($openCycle->openReason ?? ''), 'defect') ? 'danger-note' : '' }}">{{ $openCycle->openReason }}</span></li>
                            </ul>
                        </div>
                    @else
                        <p class="empty-state">No open cycle is currently stored for {{ $project->name }}.</p>
                    @endif
                </article>

                <article class="panel">
                    <h2>Latest completed outcome</h2>
                    <p class="section-copy">The newest fully closed cycle with a stored outcome.</p>

                    @if ($latestCompletedOutcome)
                        <div class="history-card">
                            <span class="card-label">Want #{{ $latestCompletedOutcome->want->id }}</span>
                            <strong>{{ $latestCompletedOutcome->want->title }}</strong>
                            <p class="history-meta">{{ $latestCompletedOutcome->outcomeLog?->outcome }}</p>
                        </div>
                    @else
                        <p class="empty-state">No completed outcome has been recorded yet.</p>
                    @endif
                </article>

                <article class="panel">
                    <h2>Capabilities</h2>
                    <p class="section-copy">Current read and write-safe artisan surfaces already available in the app.</p>

                    <ul class="capability-list">
                        @foreach ($capabilities as $capability)
                            <li>{{ $capability }}</li>
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
                                    <span class="action-command">CLI-first flow only in dashboard phase 2</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </article>
            </section>
        </div>
    </body>
</html>
