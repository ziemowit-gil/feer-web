<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        .page { padding: 24px 28px; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 14px; margin-bottom: 18px; }
        .logo-name { font-size: 13px; font-weight: 700; color: #6b7280; }
        .badge { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 4px; }
        h1 { font-size: 20px; font-weight: 700; color: #111827; line-height: 1.3; }
        .lead { margin-top: 6px; font-size: 12px; color: #374151; }
        .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .chip { background: #f3f4f6; padding: 3px 10px; border-radius: 20px; font-size: 10px; color: #374151; }
        h2 { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 6px; border-left: 3px solid #e5e7eb; padding-left: 8px; }
        .section { margin-bottom: 16px; }
        ul { padding-left: 0; list-style: none; }
        ul li { padding: 2px 0 2px 14px; position: relative; font-size: 11px; color: #374151; }
        ul li::before { content: "•"; position: absolute; left: 0; color: #9ca3af; }
        .benefits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
        .benefit-item { background: #f9fafb; padding: 5px 8px; border-radius: 4px; font-size: 10px; }
        .apply-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-top: 16px; }
        .apply-box h2 { border-left-color: #d1d5db; }
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }
        .detail-row { display: flex; gap: 16px; margin-top: 6px; }
        .detail-item { font-size: 10px; }
        .detail-item strong { display: block; font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="logo-name">{{ $siteSettings->site_name }}</div>
                <div class="badge" style="margin-top:8px">Oferta pracy</div>
                <h1>{{ $offer->title }}</h1>
                <p class="lead">{{ $offer->lead }}</p>
                <div class="chips" style="margin-top:8px">
                    <span class="chip">{{ $offer->jobTypeLabel() }}</span>
                    <span class="chip">{{ $offer->modeLabel() }}@if($offer->location) · {{ $offer->location }}@endif</span>
                    @if($offer->salary_range)<span class="chip">{{ $offer->salary_range }}</span>@endif
                    @if($offer->hourly_rate)<span class="chip">{{ $offer->hourly_rate }}</span>@endif
                    @if($offer->closes_at)<span class="chip">Aplikuj do {{ $offer->closes_at->format('d.m.Y') }}</span>@endif
                </div>
            </div>
            <div style="text-align:right; font-size:9px; color:#9ca3af; white-space:nowrap;">
                Wygenerowano: {{ $printedAt }}
            </div>
        </div>

        @if($offer->isUop() || $offer->isZlecenie() || $offer->start_date)
            <div class="detail-row" style="margin-top:10px">
                @if($offer->isUop() && $offer->contract_duration_type)
                    <div class="detail-item">
                        <strong>Rodzaj umowy</strong>
                        {{ \App\Models\JobOffer::CONTRACT_DURATION_TYPES[$offer->contract_duration_type] ?? '' }}
                        @if($offer->contract_duration) ({{ $offer->contract_duration }}) @endif
                    </div>
                @endif
                @if($offer->isZlecenie() && $offer->hourly_rate)
                    <div class="detail-item">
                        <strong>Stawka godzinowa</strong>
                        {{ $offer->hourly_rate }}
                    </div>
                @endif
                @if($offer->start_date)
                    <div class="detail-item">
                        <strong>Termin rozpoczęcia</strong>
                        {{ $offer->start_date->format('d.m.Y') }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="section">
        <h2>Zakres obowiązków</h2>
        <ul>
            @foreach($offer->duties as $duty)
                <li>{{ $duty }}</li>
            @endforeach
        </ul>
    </div>

    <div class="section">
        <h2>Wymagania</h2>
        <ul>
            @foreach($offer->requirements as $req)
                <li>{{ $req }}</li>
            @endforeach
        </ul>
    </div>

    @if(!empty($offer->benefits))
        <div class="section">
            <h2>Co oferujemy</h2>
            <div class="benefits-grid">
                @foreach($offer->benefits as $benefit)
                    <div class="benefit-item">{{ $benefit }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="apply-box">
        <h2>Aplikowanie</h2>
        @if($offer->contact_name || $offer->contact_email)
            <p style="margin-top:4px; font-size:11px">
                Osoba kontaktowa:
                @if($offer->contact_name)<strong>{{ $offer->contact_name }}</strong>@endif
                @if($offer->contact_email) — {{ $offer->contact_email }}@endif
            </p>
        @endif
        @if($offer->application_url)
            <p style="margin-top:4px; font-size:11px">
                Formularz zgłoszeniowy: <strong>{{ $offer->application_url }}</strong>
            </p>
        @endif
    </div>

    <div class="footer">
        <span>{{ $siteSettings->site_name }} · {{ $siteSettings->contact_email }}</span>
        <span>{{ url('/praca/' . $offer->slug) }}</span>
    </div>
</div>
</body>
</html>
