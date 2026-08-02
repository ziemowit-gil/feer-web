<?php

namespace Database\Seeders;

use App\Models\BipDocument;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BipDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@demo.feer.org.pl')->value('id');

        $documents = [
            [
                'category' => 'subject_status',
                'title'    => 'Status prawny i forma prawna',
                'summary'  => 'Fundacja FEER jest organizacją pozarządową, działającą na podstawie ustawy z dnia 6 kwietnia 1984 r. o fundacjach.',
                'content'  => '<p>Fundacja FEER (dalej: Fundacja) jest organizacją pozarządową nieposiadającą statusu pożytku publicznego, działającą na podstawie:</p>
<ul>
  <li>ustawy z dnia 6 kwietnia 1984 r. o fundacjach (Dz.U. 1984 nr 21 poz. 97 z późn. zm.),</li>
  <li>ustawy z dnia 24 kwietnia 2003 r. o działalności pożytku publicznego i o wolontariacie,</li>
  <li>postanowienia Sądu Rejonowego dla Krakowa-Śródmieścia w Krakowie o wpisie do Krajowego Rejestru Sądowego.</li>
</ul>
<p><strong>KRS:</strong> 0001234567<br>
<strong>NIP:</strong> 734-000-00-01<br>
<strong>REGON:</strong> 380000001</p>
<p>Fundacja posiada osobowość prawną i prowadzi działalność na terenie całej Rzeczypospolitej Polskiej oraz poza jej granicami.</p>',
                'order'    => 1,
            ],
            [
                'category' => 'subject_scope',
                'title'    => 'Przedmiot działania i kompetencje',
                'summary'  => 'Fundacja działa na rzecz dostępności cyfrowej, edukacji technologicznej i równego dostępu do informacji.',
                'content'  => '<p>Zgodnie ze statutem celami Fundacji są:</p>
<ol>
  <li>propagowanie i wdrażanie standardów dostępności cyfrowej (WCAG) w instytucjach publicznych i organizacjach pozarządowych,</li>
  <li>prowadzenie działalności edukacyjnej i szkoleniowej w zakresie technologii informatycznych i komunikacyjnych,</li>
  <li>rozwijanie i utrzymywanie otwartych narzędzi wspomagających edukację i administrację publiczną,</li>
  <li>wspieranie integracji cyfrowej osób z niepełnosprawnościami i osób starszych,</li>
  <li>działalność badawczo-rozwojowa w obszarze technologii pomocniczych (AT).</li>
</ol>
<p>Fundacja realizuje swoje cele poprzez audyty WCAG, szkolenia, projekty badawcze, publikacje oraz platformę wirtualnych laboratoriów szkoleniowych vLAB.</p>',
                'order'    => 2,
            ],
            [
                'category' => 'subject_persons',
                'title'    => 'Organy i osoby sprawujące funkcje w Fundacji',
                'summary'  => 'Organami Fundacji są: Zarząd i Rada Fundacji.',
                'content'  => '<h2>Zarząd Fundacji</h2>
<p>Zarząd kieruje działalnością Fundacji i reprezentuje ją na zewnątrz. Kadencja Zarządu trwa 3 lata.</p>
<ul>
  <li><strong>Prezes Zarządu</strong> – [imię i nazwisko]</li>
  <li><strong>Wiceprezes Zarządu</strong> – [imię i nazwisko]</li>
</ul>
<h2>Rada Fundacji</h2>
<p>Rada Fundacji sprawuje kontrolę nad działalnością Zarządu.</p>
<ul>
  <li><strong>Przewodniczący Rady</strong> – [imię i nazwisko]</li>
  <li><strong>Członek Rady</strong> – [imię i nazwisko]</li>
  <li><strong>Członek Rady</strong> – [imię i nazwisko]</li>
</ul>
<p><em>Dane osobowe osób pełniących funkcje w organach Fundacji zostaną uzupełnione po zatwierdzeniu przez Zarząd.</em></p>',
                'order'    => 3,
            ],
            [
                'category' => 'subject_structure',
                'title'    => 'Struktura organizacyjna',
                'summary'  => 'Fundacja działa w formie jednoosobowej struktury z Zarządem i Radą Fundacji.',
                'content'  => '<p>Struktura organizacyjna Fundacji FEER obejmuje następujące jednostki:</p>
<ul>
  <li><strong>Zarząd</strong> – organ wykonawczy odpowiedzialny za bieżące kierowanie Fundacją,</li>
  <li><strong>Rada Fundacji</strong> – organ kontrolny,</li>
  <li><strong>Biuro Fundacji</strong> – jednostka administracyjna obsługująca działalność operacyjną,</li>
  <li><strong>Dział projektów i szkoleń</strong> – realizacja audytów WCAG, szkoleń i projektów badawczych,</li>
  <li><strong>Dział techniczny</strong> – rozwijanie i utrzymywanie platformy vLAB i innych narzędzi.</li>
</ul>',
                'order'    => 4,
            ],
            [
                'category' => 'property',
                'title'    => 'Majątek i nieruchomości',
                'summary'  => 'Fundacja nie posiada nieruchomości. Majątek stanowią głównie środki pieniężne i sprzęt komputerowy.',
                'content'  => '<p>Fundacja FEER nie posiada nieruchomości własnych ani użytkowanych na podstawie prawa rzeczowego.</p>
<p>Majątek Fundacji obejmuje:</p>
<ul>
  <li>środki pieniężne na rachunkach bankowych,</li>
  <li>sprzęt komputerowy i biurowy,</li>
  <li>licencje na oprogramowanie,</li>
  <li>prawa majątkowe do opracowanych narzędzi i materiałów edukacyjnych.</li>
</ul>
<p>Szczegółowe dane majątkowe publikowane są w corocznym sprawozdaniu finansowym.</p>',
                'order'    => 5,
            ],
            [
                'category' => 'operations',
                'title'    => 'Tryb działania – obsługa spraw i przyjęcia interesantów',
                'summary'  => 'Biuro Fundacji czynne jest od poniedziałku do piątku w godz. 9:00–17:00.',
                'content'  => '<h2>Kontakt z Fundacją</h2>
<p>Biuro Fundacji FEER:<br>
ul. Barbackiego 28<br>
33-300 Nowy Sącz</p>
<p><strong>Godziny przyjęć:</strong> poniedziałek–piątek, 9:00–17:00</p>
<p><strong>E-mail:</strong> <a href="mailto:kontakt@feer.org.pl">kontakt@feer.org.pl</a><br>
<strong>Telefon:</strong> +48 18 123 45 67</p>
<h2>Tryb rozpatrywania wniosków o dostęp do informacji publicznej</h2>
<p>Wnioski o udostępnienie informacji publicznej należy kierować na adres e-mail: <a href="mailto:bip@feer.org.pl">bip@feer.org.pl</a> lub pocztą tradycyjną na adres Biura.</p>
<p>Informacja publiczna udostępniana jest bez zbędnej zwłoki, nie później niż w terminie 14 dni od dnia złożenia wniosku. Jeżeli informacja nie może być udostępniona w powyższym terminie, wnioskodawca zostaje poinformowany o powodach opóźnienia i wskazaniu terminu nie dłuższego niż 2 miesiące.</p>',
                'order'    => 6,
            ],
            [
                'category' => 'official_docs',
                'title'    => 'Dokumenty urzędowe',
                'summary'  => 'Statut fundacji, odpis z KRS i inne dokumenty urzędowe.',
                'content'  => '<p>Poniżej zamieszczono dokumenty urzędowe Fundacji FEER:</p>
<ul>
  <li><strong>Statut Fundacji FEER</strong> – do pobrania w zakładce Strony → Statut</li>
  <li><strong>Odpis z KRS</strong> – dostępny w Centralnej Informacji Krajowego Rejestru Sądowego pod numerem KRS 0001234567</li>
  <li><strong>Zaświadczenie o nadaniu numeru NIP</strong> – NIP 734-000-00-01</li>
  <li><strong>Zaświadczenie o nadaniu numeru REGON</strong> – REGON 380000001</li>
</ul>
<p>Aktualne dane Fundacji są dostępne w systemie S24 Ministerstwa Sprawiedliwości.</p>',
                'order'    => 7,
            ],
            [
                'category' => 'finance',
                'title'    => 'Sprawozdania finansowe i budżet',
                'summary'  => 'Roczne sprawozdania finansowe i merytoryczne Fundacji FEER.',
                'content'  => '<p>Fundacja FEER sporządza i publikuje corocznie:</p>
<ul>
  <li>sprawozdanie finansowe (bilans, rachunek wyników, informacja dodatkowa),</li>
  <li>sprawozdanie merytoryczne z działalności.</li>
</ul>
<p>Sprawozdania dostępne są w dziale <a href="/sprawozdania">Sprawozdania roczne</a> niniejszego BIP.</p>
<p>Sprawozdania finansowe za lata ubiegłe zostały złożone do właściwego rejestru i są dostępne w systemie e-KRS.</p>',
                'order'    => 8,
            ],
            [
                'category' => 'public_projects',
                'title'    => 'Projekty realizowane ze środków publicznych',
                'summary'  => 'Zestawienie projektów dofinansowanych ze środków publicznych.',
                'content'  => '<p>Fundacja FEER realizuje i realizowała projekty dofinansowane ze środków publicznych w ramach:</p>
<ul>
  <li>programów Funduszy Europejskich,</li>
  <li>dotacji ze środków samorządowych w trybie otwartych konkursów ofert,</li>
  <li>grantów ministerstw i agencji rządowych.</li>
</ul>
<p>Szczegółowe informacje o każdym projekcie, w tym zakres, budżet i dokumentacja, zamieszczane są w zakładce <a href="/projekty">Projekty</a>.</p>',
                'order'    => 9,
            ],
            [
                'category' => 'registers',
                'title'    => 'Rejestry i ewidencje prowadzone przez Fundację',
                'summary'  => 'Wykaz rejestrów i ewidencji prowadzonych przez Fundację FEER.',
                'content'  => '<p>Fundacja FEER prowadzi następujące rejestry i ewidencje:</p>
<ul>
  <li><strong>Rejestr umów</strong> – umowy zawierane przez Fundację (jawna treść, bez danych osobowych),</li>
  <li><strong>Ewidencja wolontariuszy</strong> – prowadzona zgodnie z ustawą o działalności pożytku publicznego,</li>
  <li><strong>Rejestr beneficjentów działań szkoleniowych</strong> – w zakresie wymaganym przez umowy projektowe.</li>
</ul>
<p>Dostęp do danych z ww. rejestrów jest możliwy na pisemny wniosek, z wyłączeniem danych objętych ochroną prawną.</p>',
                'order'    => 10,
            ],
        ];

        foreach ($documents as $data) {
            BipDocument::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                array_merge($data, [
                    'slug'         => Str::slug($data['title']),
                    'is_published' => true,
                    'published_at' => now()->subDays(30),
                    'created_by'   => $adminId,
                    'updated_by'   => $adminId,
                ]),
            );
        }
    }
}
