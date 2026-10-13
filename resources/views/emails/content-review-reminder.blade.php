<p>W serwisie {{ $siteName }} jest <strong>{{ $rows->count() }}</strong> opublikowanych treści, których nikt nie aktualizował ani nie sprawdził od ponad {{ $months }} miesięcy.
Przejrzyj je — zaktualizuj, oznacz jako sprawdzone, zdejmij z publikacji albo oznacz jako archiwalne.</p>

<table style="border-collapse:collapse;width:100%;font-family:sans-serif;font-size:14px;margin-top:16px">
    <thead>
        <tr style="background:#f3f4f6;text-align:left">
            <th style="padding:8px 12px;border-bottom:2px solid #e5e7eb">Treść</th>
            <th style="padding:8px 12px;border-bottom:2px solid #e5e7eb">Rodzaj</th>
            <th style="padding:8px 12px;border-bottom:2px solid #e5e7eb;white-space:nowrap">Ostatnio</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows->take(30) as $row)
            <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:8px 12px">@if ($row['edit'])<a href="{{ $row['edit'] }}" style="color:#1752bf">{{ $row['model']->title }}</a>@else{{ $row['model']->title }}@endif</td>
                <td style="padding:8px 12px;color:#374151">{{ $row['type'] }}</td>
                <td style="padding:8px 12px;white-space:nowrap;color:#374151">{{ $row['last']->format('d.m.Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@if ($rows->count() > 30)
    <p style="color:#374151">…i {{ $rows->count() - 30 }} więcej.</p>
@endif

@if (\Illuminate\Support\Facades\Route::has('admin.przeglad-tresci.index'))
<p style="margin-top:20px">
    <a href="{{ route('admin.przeglad-tresci.index') }}"
       style="display:inline-block;background:#1752bf;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px">
        Otwórz przegląd treści w panelu
    </a>
</p>
@endif
