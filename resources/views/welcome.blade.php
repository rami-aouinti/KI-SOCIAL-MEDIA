<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KI Social Media — Editorial workspace</title>
    <style>
        :root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033;background:#f4f6fa}
        *{box-sizing:border-box}body{margin:0}.shell{max-width:1120px;margin:0 auto;padding:42px 24px 72px}
        .top{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:30px}
        .eyebrow{font-size:12px;letter-spacing:.13em;text-transform:uppercase;color:#64748b;font-weight:700}
        h1{font-size:32px;letter-spacing:-.04em;margin:7px 0}.muted{color:#64748b}.pill{display:inline-block;padding:6px 10px;border-radius:99px;background:#e8eefc;color:#3154a4;font-size:12px;font-weight:700}
        .button{display:inline-block;background:#172033;color:#fff;text-decoration:none;padding:11px 16px;border-radius:10px;font-size:13px;font-weight:700}
        .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:24px 0}
        .card{background:#fff;border:1px solid #e6eaf0;border-radius:16px;padding:20px;box-shadow:0 4px 18px #15213b08}
        .metric{font-size:27px;font-weight:750;letter-spacing:-.04em;margin-top:8px}.section{margin-top:28px}.section h2{font-size:18px;margin:0 0 13px}
        .ideas{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.idea{background:#fff;border:1px solid #e6eaf0;border-radius:14px;padding:17px}
        .idea h3{margin:9px 0 6px;font-size:16px}.meta{font-size:13px;color:#64748b;line-height:1.7}
        .status{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#246b50;background:#e8f6ef;border-radius:99px;padding:5px 8px}
        .warning{color:#95620b;background:#fff6df;padding:12px 14px;border-radius:10px;font-size:13px}
        .empty-state{border:1px dashed #cbd5e1;border-radius:12px;padding:18px;color:#64748b;font-size:14px;background:#ffffff80}
        .inspirations{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.inspiration{font-size:13px;line-height:1.6}
        .card,.idea{min-width:0;overflow-wrap:anywhere}.meta{overflow-wrap:anywhere}
        footer{margin-top:30px;color:#718096;font-size:12px;line-height:1.6}
        @media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.inspirations{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:640px){.grid,.ideas,.inspirations{grid-template-columns:minmax(0,1fr)}.top{align-items:flex-start;flex-direction:column}.shell{padding:28px 16px 48px}h1{font-size:28px}.button{max-width:100%;text-align:center}}
    </style>
</head>
<body>
<main class="shell">
    <header class="top">
        <div>
            <div class="eyebrow">Synthetic demo workspace · Optician</div>
            <h1>KI Social Media</h1>
            <div class="muted">Weekly editorial planning, human approval and simulated handoff.</div>
        </div>
        @if($dashboard->plan)
            <a class="button" href="{{ route('weekly-plans.report', $dashboard->plan['id']) }}">Download weekly TXT report</a>
        @endif
    </header>

    @if(!$dashboard->plan)
        <section class="card">
            <h2>No demo plan found</h2>
            <p class="muted">Run <code>php artisan db:seed --class=DemoContentSeeder</code> to load the synthetic demonstration data.</p>
        </section>
    @else
        @php($plan = $dashboard->plan)
        <div class="eyebrow">Week starting {{ $plan['week_start'] }} · {{ $plan['seasonal_occasion'] }}</div>
        @if($plan['generation_simulated'])
            <p><span class="pill">AI generation: simulated mock · {{ $plan['generation_model'] }}</span></p>
        @endif
        <div class="grid">
            <section class="card"><div class="eyebrow">Editorial ideas</div><div class="metric">{{ count($plan['ideas']) }}</div><div class="muted">{{ $plan['priority_count'] }} priorities · {{ $plan['reserve_count'] }} reserves</div></section>
            <section class="card"><div class="eyebrow">Inspiration references</div><div class="metric">{{ count($plan['inspirations']) }}</div><div class="muted">Synthetic references, no live trend claims</div></section>
            <section class="card"><div class="eyebrow">Approval workflow</div><div class="metric">{{ $plan['has_handoff'] ? 'Handed off' : 'In progress' }}</div><div class="muted">Human approval required before handoff</div></section>
        </div>

        <section class="section">
            <h2>Team capacity</h2>
            @if($dashboard->capacity['people'] === [])
                <p class="empty-state">No team capacity or work allocations have been recorded for this plan.</p>
            @else
                <div class="grid">
                    @foreach($dashboard->capacity['people'] as $person => $details)
                        <div class="card">
                            <div class="eyebrow">{{ $person }}</div>
                            <div class="metric">{{ $details['allocated_minutes'] }}<span class="muted" style="font-size:15px"> / {{ $details['capacity_minutes'] ?? '—' }} min</span></div>
                            <div class="muted">{{ $details['is_over_capacity'] ? 'Over capacity by '.$details['exceeded_by_minutes'].' min' : ($details['remaining_minutes'] ?? 0).' min remaining' }}</div>
                        </div>
                    @endforeach
                </div>
                @foreach($dashboard->capacity['warnings'] as $warning)
                    <p class="warning">{{ $warning['message'] }}</p>
                @endforeach
            @endif
        </section>

        <section class="section">
            <h2>Editorial plan</h2>
            @if($plan['ideas'] === [])
                <p class="empty-state">No editorial ideas have been saved for this week yet.</p>
            @else
                <div class="ideas">
                    @foreach($plan['ideas'] as $idea)
                        <article class="idea">
                            <span class="pill">{{ $idea['priority'] }}</span>
                            <span class="status">{{ $idea['status'] }}</span>
                            <h3>{{ $idea['title'] }}</h3>
                            <div class="meta">{{ $idea['planned_at'] }} · {{ implode(', ', $idea['channels']) }}</div>
                            <div class="meta">{{ $idea['responsible_person'] }} · {{ $idea['estimated_minutes'] }} min</div>
                            <p class="meta">{{ $idea['selection_reason'] }}</p>
                            @if($idea['briefing_status'])
                                <span class="status">Briefing: {{ str_replace('_', ' ', $idea['briefing_status']) }}</span>
                            @else
                                <span class="meta">No briefing created</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @if($plan['featured'])
            <section class="section">
                <h2>Featured briefing · {{ $plan['featured']['title'] }}</h2>
                <article class="card">
                    <div class="eyebrow">AI-generated demo briefing · human review required</div>
                    <div class="eyebrow">Hook</div>
                    <p>{{ $plan['featured']['briefing']['hook'] }}</p>
                    <div class="eyebrow">Production plan</div>
                    <p class="muted">{{ $plan['featured']['briefing']['filming_plan'] }}</p>
                    <div class="eyebrow">Channel variants</div>
                    @foreach($plan['featured']['channel_variants'] as $channel => $variant)
                        <p><strong>{{ ucfirst($channel) }}</strong><br>{{ $variant['text'] }}</p>
                    @endforeach
                    <div class="meta">Approval: {{ $plan['featured']['approval'] }} · Handoff: {{ $plan['featured']['handoff'] }}</div>
                </article>
            </section>
        @endif

        <section class="section">
            <h2>Inspiration references</h2>
            @if($plan['inspirations'] === [])
                <p class="empty-state">No inspiration references have been saved for this week yet.</p>
            @else
                <div class="inspirations">
                    @foreach($plan['inspirations'] as $reference)
                        <article class="card inspiration">
                            <strong>{{ $reference['channel'] }}</strong>
                            <div class="muted">{{ $reference['source_id'] }}</div>
                            <p>{{ $reference['description'] }}</p>
                            <span class="muted">Metrics: {{ $reference['has_metrics'] ? 'provided' : 'unknown / not provided' }}</span>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <footer>Demo data is synthetic. Handoff is simulated only. No social network is connected and no real post is published.</footer>
</main>
</body>
</html>
