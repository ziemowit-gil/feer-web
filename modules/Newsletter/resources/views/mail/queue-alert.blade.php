<!DOCTYPE html>
<html lang="pl">
<body style="margin:0;padding:24px;background:#F3F4F6;font-family:Arial,Helvetica,sans-serif;color:#1D1D1A">
    <div style="max-width:560px;margin:0 auto;padding:24px;background:#FFFFFF;border-radius:8px">
        <h1 style="margin:0 0 12px;font-size:20px">Kolejka zadań wymaga uwagi</h1>
        <p style="margin:0 0 12px;font-size:15px;line-height:1.5">W serwisie {{ $siteName }} wykryto problem z kolejką. Dopóki trwa, kampanie newslettera, maile potwierdzające zapis i synchronizacja z CRM mogą nie wychodzić.</p>
        <ul style="margin:0 0 16px;padding-left:20px;font-size:15px;line-height:1.5">
            @foreach ($state['problems'] as $problem)
                <li>{{ $problem }}</li>
            @endforeach
        </ul>
        <p style="margin:0 0 4px;font-size:14px">Czeka w kolejce: <strong>{{ $state['pending'] }}</strong> · nieudane łącznie: <strong>{{ $state['failed'] }}</strong></p>
        <p style="margin:0 0 16px;font-size:14px">Ostatnia praca workera: <strong>{{ $state['worker']?->format('d.m.Y H:i') ?? 'brak danych' }}</strong></p>
        <p style="margin:0 0 16px"><a href="{{ $url }}" style="display:inline-block;padding:10px 16px;border-radius:6px;background:#1752BF;color:#FFFFFF;font-weight:bold;text-decoration:none">Otwórz pulpit newslettera</a></p>
        <p style="margin:0;font-size:13px;color:#4A4A47">Co sprawdzić: czy cron uruchamia co minutę <code>php84 artisan schedule:run</code> i czy w logach (<code>storage/logs</code>) nie ma błędów. Kolejny alert o tym samym problemie przyjdzie najwcześniej za godzinę.</p>
    </div>
</body>
</html>
