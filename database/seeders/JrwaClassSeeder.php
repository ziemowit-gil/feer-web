<?php

namespace Database\Seeders;

use App\Models\JrwaClass;
use Illuminate\Database\Seeder;

class JrwaClassSeeder extends Seeder
{
    public function run(): void
    {
        // flag: 0 = folder/group, 1 = leaf (filing point), 2 = withdrawn
        // parent_symbol: null for top-level or non-numeric codes
        // sort_order: preserves original listing order

        $rows = [
            // ── Kody specjalne / poza hierarchią numeryczną ──────────────────
            ['symbol' => 'KSG',      'name' => 'Dokumenty księgowe — obieg od zapłaty',           'category' => 'B5',   'notes' => 'Dokumenty finansowo-księgowe zatwierdzone do wypłaty w module EOD Dokumentów Księgowych', 'flag' => 1, 'parent_symbol' => null,  'sort_order' => 10],
            ['symbol' => 'REZERWKR', 'name' => 'Rezerwacje przestrzeni — formularze',              'category' => 'B10',  'notes' => null, 'flag' => 1, 'parent_symbol' => null, 'sort_order' => 20],
            ['symbol' => '0-PAP',    'name' => 'Sprawy wszczęte w trybie papierowym (przed EZD)', 'category' => 'BE10', 'notes' => 'Klasa przejściowa. Akta spraw/projektów rozpoczętych w systemie tradycyjnym i kontynuowanych po wdrożeniu EZD — prowadzone dwutorowo. Podlega ekspertyzie archiwum państwowego.', 'flag' => 1, 'parent_symbol' => null, 'sort_order' => 30],
            ['symbol' => '1430',     'name' => 'Informacja publiczna',                             'category' => 'B10',  'notes' => null, 'flag' => 1, 'parent_symbol' => null, 'sort_order' => 40],
            ['symbol' => '171',      'name' => 'Kontrole wewnętrzne',                              'category' => 'B10',  'notes' => null, 'flag' => 1, 'parent_symbol' => null, 'sort_order' => 50],
            ['symbol' => '441',      'name' => 'Patronaty udzielone przez Fundację',               'category' => 'B10',  'notes' => null, 'flag' => 1, 'parent_symbol' => null, 'sort_order' => 60],
            ['symbol' => 'WOL',      'name' => 'Wolontariat',                                      'category' => 'B10',  'notes' => 'Klasa wycofana — używać nowego wykazu numerycznego', 'flag' => 2, 'parent_symbol' => null, 'sort_order' => 999],

            // ── 0 ZARZĄDZANIE ORGANIZACJĄ ────────────────────────────────────
            ['symbol' => '0',   'name' => 'ZARZĄDZANIE ORGANIZACJĄ',                          'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 100],
            ['symbol' => '00',  'name' => 'Organy fundacji i ich obsługa',                    'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '0',  'sort_order' => 110],
            ['symbol' => '000', 'name' => 'Akt fundacyjny, oświadczenia fundatora',           'category' => 'A',   'notes' => 'Tożsamość organizacji — przechowywane trwale', 'flag' => 0, 'parent_symbol' => '00', 'sort_order' => 111],
            ['symbol' => '001', 'name' => 'Zarząd — uchwały, protokoły posiedzeń',           'category' => 'A',   'notes' => null, 'flag' => 1, 'parent_symbol' => '00', 'sort_order' => 112],
            ['symbol' => '002', 'name' => 'Rada Fundacji / organ nadzoru — uchwały, protokoły', 'category' => 'A', 'notes' => null, 'flag' => 0, 'parent_symbol' => '00', 'sort_order' => 113],
            ['symbol' => '005', 'name' => 'Status OPP',                                       'category' => 'A',   'notes' => 'Organizacja pożytku publicznego', 'flag' => 0, 'parent_symbol' => '00', 'sort_order' => 114],

            ['symbol' => '01',  'name' => 'Podstawy prawne i organizacja',                    'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '0',  'sort_order' => 120],
            ['symbol' => '010', 'name' => 'Statut i jego zmiany',                             'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '01', 'sort_order' => 121],
            ['symbol' => '011', 'name' => 'Rejestracja w KRS, wpisy i zmiany',               'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '01', 'sort_order' => 122],
            ['symbol' => '012', 'name' => 'Regulaminy, instrukcje i polityki wewnętrzne',    'category' => 'A',   'notes' => null, 'flag' => 1, 'parent_symbol' => '01', 'sort_order' => 123],
            ['symbol' => '013', 'name' => 'Pełnomocnictwa i upoważnienia',                   'category' => 'B10', 'notes' => null, 'flag' => 1, 'parent_symbol' => '01', 'sort_order' => 124],
            ['symbol' => '014', 'name' => 'Uchwały Rady',                                    'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '01', 'sort_order' => 125],

            ['symbol' => '02',  'name' => 'Planowanie i sprawozdawczość statutowa',          'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '0',  'sort_order' => 130],
            ['symbol' => '020', 'name' => 'Strategia, plany wieloletnie i roczne',           'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '02', 'sort_order' => 131],
            ['symbol' => '021', 'name' => 'Sprawozdania merytoryczne roczne (ministerstwo/OPP)', 'category' => 'A', 'notes' => null, 'flag' => 0, 'parent_symbol' => '02', 'sort_order' => 132],
            ['symbol' => '022', 'name' => 'Zarządzenia',                                     'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '02', 'sort_order' => 133],
            ['symbol' => '025', 'name' => 'Księga procedur',                                 'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '02', 'sort_order' => 134],

            ['symbol' => '03',  'name' => 'Kontrole i audyty',                               'category' => 'A',   'notes' => null, 'flag' => 1, 'parent_symbol' => '0',  'sort_order' => 140],
            ['symbol' => '030', 'name' => 'Kontrole zewnętrzne — protokoły, zalecenia',     'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '03', 'sort_order' => 141],
            ['symbol' => '031', 'name' => 'Kontrole i audyty wewnętrzne',                   'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '03', 'sort_order' => 142],
            ['symbol' => '033', 'name' => 'Rejestr pełnomocnictw',                          'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '03', 'sort_order' => 143],

            ['symbol' => '04',  'name' => 'Ochrona danych osobowych (RODO)',                'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '0',  'sort_order' => 150],
            ['symbol' => '040', 'name' => 'Polityki, rejestr czynności przetwarzania, IOD', 'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '04', 'sort_order' => 151],
            ['symbol' => '041', 'name' => 'Umowy powierzenia, rejestr naruszeń, zgody',    'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '04', 'sort_order' => 152],
            ['symbol' => '043', 'name' => 'Audyty zewnętrzne',                              'category' => 'BE10','notes' => null, 'flag' => 0, 'parent_symbol' => '04', 'sort_order' => 153],

            ['symbol' => '05',  'name' => 'Obsługa prawna, skargi i wnioski',              'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '0',  'sort_order' => 160],
            ['symbol' => '050', 'name' => 'Skargi, wnioski, korespondencja sporna',        'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '05', 'sort_order' => 161],
            ['symbol' => '051', 'name' => 'Sprawy sądowe, opinie prawne',                  'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '05', 'sort_order' => 162],

            // ── 1 KADRY I WOLONTARIAT ────────────────────────────────────────
            ['symbol' => '1',   'name' => 'KADRY I WOLONTARIAT',                           'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 200],

            ['symbol' => '10',  'name' => 'Akta osobowe pracowników',                      'category' => 'B10', 'notes' => 'B50 dla zatrudnionych przed 1.01.2019 (ustawa o e-aktach)', 'flag' => 0, 'parent_symbol' => '1', 'sort_order' => 210],
            ['symbol' => '102', 'name' => 'Akta osobowe',                                  'category' => 'B50', 'notes' => null, 'flag' => 0, 'parent_symbol' => '10', 'sort_order' => 211],
            ['symbol' => '103', 'name' => 'Umowy o pracę',                                 'category' => 'B50', 'notes' => null, 'flag' => 0, 'parent_symbol' => '10', 'sort_order' => 212],
            ['symbol' => '123', 'name' => 'Listy płac',                                    'category' => 'B50', 'notes' => null, 'flag' => 0, 'parent_symbol' => '10', 'sort_order' => 213],
            ['symbol' => '124', 'name' => 'ZUS',                                           'category' => 'B50', 'notes' => null, 'flag' => 0, 'parent_symbol' => '10', 'sort_order' => 214],

            ['symbol' => '11',  'name' => 'Umowy cywilnoprawne (zlecenie, o dzieło)',      'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '1',  'sort_order' => 220],
            ['symbol' => '111', 'name' => 'Umowy zlecenia',                                'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '11', 'sort_order' => 221],
            ['symbol' => '112', 'name' => 'Umowy o dzieło',                                'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '11', 'sort_order' => 222],
            ['symbol' => '113', 'name' => 'Umowy B2B',                                     'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '11', 'sort_order' => 223],

            ['symbol' => '12',  'name' => 'Wolontariat — porozumienia, zakresy czynności', 'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '1',  'sort_order' => 230],
            ['symbol' => '121', 'name' => 'Ewidencja świadczeń i zaświadczenia wolontariuszy', 'category' => 'B5', 'notes' => null, 'flag' => 0, 'parent_symbol' => '12', 'sort_order' => 231],
            ['symbol' => '142', 'name' => 'Wolontariat akcyjny i krótkoterminowy (WOL)',   'category' => 'B5',  'notes' => 'Bez umów pisemnych: oświadczenia, listy obecności, zgody rodziców', 'flag' => 1, 'parent_symbol' => '12', 'sort_order' => 232],
            ['symbol' => '144', 'name' => 'Rejestr wolontariuszy i zaświadczenia',         'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '12', 'sort_order' => 233],

            ['symbol' => '13',  'name' => 'Ewidencja czasu pracy, urlopy, absencje',       'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '1',  'sort_order' => 240],

            ['symbol' => '14',  'name' => 'BHP — szkolenia, badania, ocena ryzyka',        'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '1',  'sort_order' => 250],
            ['symbol' => '131', 'name' => 'Szkolenia BHP',                                 'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '14', 'sort_order' => 251],
            ['symbol' => '133', 'name' => 'Wypadki',                                       'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '14', 'sort_order' => 252],
            ['symbol' => '141', 'name' => 'Wypadki przy pracy',                            'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '14', 'sort_order' => 253],
            ['symbol' => '143', 'name' => 'Umowy o staże i praktyki',                      'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '14', 'sort_order' => 254],

            ['symbol' => '15',  'name' => 'Świadczenia socjalne, ZFŚS',                    'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '1',  'sort_order' => 260],

            // ── 2 ADMINISTRACJA, MAJĄTEK I IT ───────────────────────────────
            ['symbol' => '2',   'name' => 'ADMINISTRACJA, MAJĄTEK I IT',                   'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 300],

            ['symbol' => '20',  'name' => 'Lokale i nieruchomości — najem, użyczenie',     'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '2',  'sort_order' => 310],
            ['symbol' => '504', 'name' => 'Najem lokalu',                                   'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '20', 'sort_order' => 311],

            ['symbol' => '21',  'name' => 'Środki trwałe i wyposażenie — ewidencja majątku', 'category' => 'B5', 'notes' => 'Okres liczony od likwidacji/zbycia składnika', 'flag' => 0, 'parent_symbol' => '2', 'sort_order' => 320],
            ['symbol' => '241', 'name' => 'Środki trwałe',                                  'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '21', 'sort_order' => 321],
            ['symbol' => '242', 'name' => 'Wyposażenie',                                    'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '21', 'sort_order' => 322],

            ['symbol' => '22',  'name' => 'Zaopatrzenie, zakupy, umowy z dostawcami',       'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '2',  'sort_order' => 330],

            ['symbol' => '23',  'name' => 'Obsługa kancelaryjna — dziennik korespondencji', 'category' => 'Bc',  'notes' => 'Dokumentacja manipulacyjna', 'flag' => 0, 'parent_symbol' => '2', 'sort_order' => 340],
            ['symbol' => '501', 'name' => 'Dziennik przychodzący',                          'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '23', 'sort_order' => 341],
            ['symbol' => '502', 'name' => 'Dziennik wychodzący',                            'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '23', 'sort_order' => 342],

            ['symbol' => '24',  'name' => 'Archiwum/składnica akt — spisy z-o, brakowanie', 'category' => 'A',  'notes' => 'Spisy zdawczo-odbiorcze i protokoły brakowania', 'flag' => 0, 'parent_symbol' => '2', 'sort_order' => 350],
            ['symbol' => '532', 'name' => 'Spisy zdawczo-odbiorcze',                        'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '24', 'sort_order' => 351],
            ['symbol' => '533', 'name' => 'Brakowanie akt',                                 'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '24', 'sort_order' => 352],

            ['symbol' => '25',  'name' => 'Informatyka — systemy, kopie, bezpieczeństwo IT', 'category' => 'B5', 'notes' => null, 'flag' => 0, 'parent_symbol' => '2',  'sort_order' => 360],
            ['symbol' => '511', 'name' => 'Sprzęt',                                          'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 361],
            ['symbol' => '512', 'name' => 'Licencje / SaaS',                                 'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 362],
            ['symbol' => '513', 'name' => 'Domeny i hosting',                                'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 363],
            ['symbol' => '541', 'name' => 'Polityka AI Governance',                          'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 364],
            ['symbol' => '542', 'name' => 'Rejestr systemów i ryzyk AI',                    'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 365],
            ['symbol' => '543', 'name' => 'Umowy i DPA z dostawcami LLM',                   'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 366],
            ['symbol' => '544', 'name' => 'Prompty systemowe i bazy wiedzy RAG',             'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 367],
            ['symbol' => '545', 'name' => 'Skrypty integracyjne kategoryzacji dokumentów',   'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '25', 'sort_order' => 368],

            ['symbol' => '26',  'name' => 'Ubezpieczenia majątkowe',                        'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '2',  'sort_order' => 370],

            // ── 3 FINANSE I KSIĘGOWOŚĆ ───────────────────────────────────────
            ['symbol' => '3',   'name' => 'FINANSE I KSIĘGOWOŚĆ',                           'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 400],

            ['symbol' => '30',  'name' => 'Polityka rachunkowości, plan kont',              'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '3',  'sort_order' => 410],
            ['symbol' => '201', 'name' => 'Polityka rachunkowości',                         'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '30', 'sort_order' => 411],

            ['symbol' => '31',  'name' => 'Księgi rachunkowe (dziennik, KG, pomocnicze)',   'category' => 'B5',  'notes' => 'Min. 5 lat — art. 74 ustawy o rachunkowości', 'flag' => 0, 'parent_symbol' => '3', 'sort_order' => 420],

            ['symbol' => '32',  'name' => 'Dowody księgowe — faktury, rachunki',            'category' => 'B5',  'notes' => null, 'flag' => 1, 'parent_symbol' => '3',  'sort_order' => 430],
            ['symbol' => '211', 'name' => 'Faktury kosztowe',                               'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '32', 'sort_order' => 431],
            ['symbol' => '212', 'name' => 'Faktury sprzedażowe',                            'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '32', 'sort_order' => 432],

            ['symbol' => '33',  'name' => 'Wyciągi bankowe, obrót pieniężny, kasa',        'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '3',  'sort_order' => 440],
            ['symbol' => '214', 'name' => 'Wyciągi bankowe',                               'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '33', 'sort_order' => 441],

            ['symbol' => '34',  'name' => 'Rozliczenia podatkowe (CIT, VAT, PIT)',          'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '3',  'sort_order' => 450],
            ['symbol' => '221', 'name' => 'CIT',                                            'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '34', 'sort_order' => 451],
            ['symbol' => '222', 'name' => 'PIT',                                            'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '34', 'sort_order' => 452],

            ['symbol' => '35',  'name' => 'Wynagrodzenia — listy płac, karty wynagrodzeń', 'category' => 'B10', 'notes' => 'B50 dla okresów sprzed 1.01.2019', 'flag' => 0, 'parent_symbol' => '3', 'sort_order' => 460],
            ['symbol' => '36',  'name' => 'Rozliczenia ZUS — deklaracje, składki',         'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '3',  'sort_order' => 470],

            ['symbol' => '37',  'name' => 'Roczne sprawozdania finansowe (bilans, RZiS)',   'category' => 'A',   'notes' => 'Trwale — art. 74 ust. 1 ustawy o rachunkowości', 'flag' => 0, 'parent_symbol' => '3', 'sort_order' => 480],
            ['symbol' => '231', 'name' => 'Roczne sprawozdania finansowe',                  'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '37', 'sort_order' => 481],
            ['symbol' => '232', 'name' => 'Sprawozdania merytoryczne do ministerstwa',      'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '37', 'sort_order' => 482],
            ['symbol' => '224', 'name' => 'Budżety',                                        'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '37', 'sort_order' => 483],

            ['symbol' => '38',  'name' => 'Inwentaryzacja',                                 'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '3',  'sort_order' => 490],

            // ── 4 PROJEKTY ───────────────────────────────────────────────────
            ['symbol' => '4',   'name' => 'PROJEKTY (DZIAŁALNOŚĆ PROGRAMOWA I DOTOWANA)',   'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 500],

            ['symbol' => '40',  'name' => 'Koncepcje i programy własne',                   'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '4',  'sort_order' => 510],
            ['symbol' => '41',  'name' => 'Dokumentacja konkursowa — wnioski o dofinansowanie', 'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '4', 'sort_order' => 520],
            ['symbol' => '42',  'name' => 'Umowy o dofinansowanie i aneksy',               'category' => 'B10', 'notes' => 'Okres wg umowy/okresu trwałości projektu', 'flag' => 0, 'parent_symbol' => '4', 'sort_order' => 530],
            ['symbol' => '43',  'name' => 'Realizacja — uczestnicy, dzienniki, produkty',  'category' => 'B10', 'notes' => 'Okres wg umowy/okresu trwałości projektu', 'flag' => 0, 'parent_symbol' => '4', 'sort_order' => 540],
            ['symbol' => '44',  'name' => 'Rozliczenia i raporty (cząstkowe i końcowe)',   'category' => 'B10', 'notes' => 'Okres wg umowy/okresu trwałości projektu', 'flag' => 0, 'parent_symbol' => '4', 'sort_order' => 550],
            ['symbol' => '45',  'name' => 'Ewaluacja i raporty oddziaływania',             'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '4',  'sort_order' => 560],
            ['symbol' => '46',  'name' => 'Współpraca z partnerami projektowymi',          'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '4',  'sort_order' => 570],

            // ── 5 DZIAŁALNOŚĆ SZKOLENIOWA ────────────────────────────────────
            ['symbol' => '5',   'name' => 'DZIAŁALNOŚĆ SZKOLENIOWA',                       'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 600],

            ['symbol' => '50',  'name' => 'Programy i oferta szkoleń (programy autorskie)', 'category' => 'A',  'notes' => null, 'flag' => 0, 'parent_symbol' => '5',  'sort_order' => 610],
            ['symbol' => '51',  'name' => 'Organizacja szkoleń — harmonogramy, rekrutacja', 'category' => 'B5', 'notes' => null, 'flag' => 0, 'parent_symbol' => '5',  'sort_order' => 620],
            ['symbol' => '52',  'name' => 'Dokumentacja uczestników — zgłoszenia, zgody',  'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '5',  'sort_order' => 630],
            ['symbol' => '53',  'name' => 'Rejestr wydanych zaświadczeń/certyfikatów',     'category' => 'B10', 'notes' => null, 'flag' => 1, 'parent_symbol' => '5',  'sort_order' => 640],
            ['symbol' => '54',  'name' => 'Materiały dydaktyczne',                         'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '5',  'sort_order' => 650],
            ['symbol' => '55',  'name' => 'Ewaluacja szkoleń, ankiety',                    'category' => 'B3',  'notes' => null, 'flag' => 0, 'parent_symbol' => '5',  'sort_order' => 660],

            // ── 6 FUNDRAISING, DARCZYŃCY I PROMOCJA ─────────────────────────
            ['symbol' => '6',   'name' => 'FUNDRAISING, DARCZYŃCY I PROMOCJA',             'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 700],

            ['symbol' => '60',  'name' => 'Darczyńcy — ewidencja, umowy darowizny',        'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '6',  'sort_order' => 710],
            ['symbol' => '61',  'name' => 'Zbiórki publiczne — zgłoszenia, sprawozdania',  'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '6',  'sort_order' => 720],
            ['symbol' => '62',  'name' => 'Status OPP i rozliczenia 1,5% podatku',         'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '6',  'sort_order' => 730],
            ['symbol' => '63',  'name' => 'Sponsoring, współpraca z biznesem',              'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '6',  'sort_order' => 740],
            ['symbol' => '64',  'name' => 'Promocja, PR, media, www, social media',        'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '6',  'sort_order' => 750],
            ['symbol' => '401', 'name' => 'Informacje prasowe',                             'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '64', 'sort_order' => 751],
            ['symbol' => '403', 'name' => 'Księga Znaku',                                   'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '64', 'sort_order' => 752],
            ['symbol' => '411', 'name' => 'Strona WWW',                                     'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '64', 'sort_order' => 753],
            ['symbol' => '412', 'name' => 'Social media',                                   'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '64', 'sort_order' => 754],
            ['symbol' => '413', 'name' => 'Archiwum foto/wideo',                            'category' => 'BE10','notes' => null, 'flag' => 0, 'parent_symbol' => '64', 'sort_order' => 755],
            ['symbol' => '65',  'name' => 'Wydawnictwa i publikacje własne',               'category' => 'A',   'notes' => null, 'flag' => 0, 'parent_symbol' => '6',  'sort_order' => 760],

            // ── 7 WSPÓŁPRACA ZEWNĘTRZNA ──────────────────────────────────────
            ['symbol' => '7',   'name' => 'WSPÓŁPRACA ZEWNĘTRZNA',                         'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => null, 'sort_order' => 800],

            ['symbol' => '70',  'name' => 'Współpraca z administracją publiczną',          'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '7',  'sort_order' => 810],
            ['symbol' => '71',  'name' => 'Członkostwo w federacjach, sieciach, koalicjach', 'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '7', 'sort_order' => 820],
            ['symbol' => '72',  'name' => 'Współpraca międzynarodowa',                     'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '7',  'sort_order' => 830],
            ['symbol' => '73',  'name' => 'Porozumienia o współpracy (ogólne)',            'category' => 'B10', 'notes' => null, 'flag' => 1, 'parent_symbol' => '7',  'sort_order' => 840],

            ['symbol' => '420', 'name' => 'Listy intencyjne',                              'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '73', 'sort_order' => 841],
            ['symbol' => '421', 'name' => 'Partnerstwa',                                   'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '73', 'sort_order' => 842],
            ['symbol' => '422', 'name' => 'Umowy sponsorskie',                             'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '73', 'sort_order' => 843],
            ['symbol' => '423', 'name' => 'Umowy z prelegentami i cateringiem',            'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '73', 'sort_order' => 844],

            ['symbol' => '430', 'name' => 'Plany i agendy wydarzeń',                      'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '7',  'sort_order' => 850],
            ['symbol' => '431', 'name' => 'Plany i agendy wydarzeń (szczegółowe)',         'category' => 'B5',  'notes' => null, 'flag' => 0, 'parent_symbol' => '430','sort_order' => 851],

            // ── OCHRONA DANYCH — klasy dodatkowe ───────────────────────────
            ['symbol' => '521', 'name' => 'Polityka ochrony danych',                       'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '040','sort_order' => 152],
            ['symbol' => '522', 'name' => 'Rejestr czynności przetwarzania (RCP)',         'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '040','sort_order' => 153],
            ['symbol' => '523', 'name' => 'Upoważnienia do przetwarzania',                 'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '040','sort_order' => 154],
            ['symbol' => '524', 'name' => 'Umowy powierzenia',                             'category' => 'B10', 'notes' => null, 'flag' => 0, 'parent_symbol' => '041','sort_order' => 155],

            // ════════════════════════════════════════════════════════════════
            // ── 8 DZIAŁALNOŚĆ STATUTOWA FEER ────────────────────────────────
            // Segregatory działań projektowych wg celów §6 i metod §7 Statutu
            // ════════════════════════════════════════════════════════════════
            ['symbol' => '8',   'name' => 'DZIAŁALNOŚĆ STATUTOWA FEER',
             'category' => 'B10', 'notes' => 'Segregatory działań projektowych wg celów §6 i metod realizacji §7 Statutu Fundacji',
             'flag' => 0, 'parent_symbol' => null, 'sort_order' => 900],

            // §6 ust. 1 — Przeciwdziałanie wykluczeniu społecznemu
            ['symbol' => '80',  'name' => 'Przeciwdziałanie wykluczeniu społecznemu',
             'category' => 'B10', 'notes' => '§6 ust. 1 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 910],
            ['symbol' => '800', 'name' => 'Działania ogólne nt. wykluczenia — dokumentacja',
             'category' => 'B10', 'notes' => null, 'flag' => 1, 'parent_symbol' => '80', 'sort_order' => 911],
            ['symbol' => '801', 'name' => 'Konferencje i spotkania — organizacja',
             'category' => 'B5',  'notes' => '§7 ust. 2', 'flag' => 1, 'parent_symbol' => '80', 'sort_order' => 912],
            ['symbol' => '802', 'name' => 'Konferencje i spotkania — uczestnictwo',
             'category' => 'B5',  'notes' => '§7 ust. 3', 'flag' => 1, 'parent_symbol' => '80', 'sort_order' => 913],
            ['symbol' => '803', 'name' => 'Wydawnictwa nt. wykluczenia społecznego',
             'category' => 'A',   'notes' => '§7 ust. 1', 'flag' => 1, 'parent_symbol' => '80', 'sort_order' => 914],

            // §6 ust. 2 — Działalność edukacyjna
            ['symbol' => '81',  'name' => 'Działalność edukacyjna',
             'category' => 'B5',  'notes' => '§6 ust. 2 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 920],
            ['symbol' => '810', 'name' => 'Szkolenia, kursy, warsztaty',
             'category' => 'B5',  'notes' => '§7 ust. 7', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 921],
            ['symbol' => '811', 'name' => 'Materiały dydaktyczne i programy szkoleń',
             'category' => 'B5',  'notes' => '§7 ust. 7', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 922],
            ['symbol' => '812', 'name' => 'Staże i praktyki zawodowe',
             'category' => 'B10', 'notes' => '§7 ust. 8', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 923],
            ['symbol' => '813', 'name' => 'Współpraca z podmiotami edukacyjnymi',
             'category' => 'B10', 'notes' => '§7 ust. 4', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 924],
            ['symbol' => '814', 'name' => 'Zaświadczenia i certyfikaty uczestników',
             'category' => 'B10', 'notes' => 'Rejestr wydanych zaświadczeń', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 925],
            ['symbol' => '815', 'name' => 'Szkolenia dla organizacji pozarządowych',
             'category' => 'B10', 'notes' => '§7 ust. 11', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 926],
            ['symbol' => '816', 'name' => 'Webinaria i szkolenia online',
             'category' => 'B5',  'notes' => '§7 ust. 12', 'flag' => 1, 'parent_symbol' => '81', 'sort_order' => 927],

            // §6 ust. 3 — Promocja i organizacja wolontariatu
            ['symbol' => '82',  'name' => 'Promocja i organizacja wolontariatu',
             'category' => 'B10', 'notes' => '§6 ust. 3 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 930],
            ['symbol' => '820', 'name' => 'Akcje i kampanie wolontariackie',
             'category' => 'B5',  'notes' => '§7 ust. 9', 'flag' => 1, 'parent_symbol' => '82', 'sort_order' => 931],
            ['symbol' => '821', 'name' => 'Dokumentacja kampanii promujących wolontariat',
             'category' => 'B5',  'notes' => '§7 ust. 9', 'flag' => 1, 'parent_symbol' => '82', 'sort_order' => 932],
            ['symbol' => '822', 'name' => 'Raporty i ewaluacja działań wolontariackich',
             'category' => 'B5',  'notes' => null, 'flag' => 1, 'parent_symbol' => '82', 'sort_order' => 933],

            // §6 ust. 4 — Podnoszenie kwalifikacji zawodowych osób niepełnosprawnych
            ['symbol' => '83',  'name' => 'Kwalifikacje zawodowe osób niepełnosprawnych',
             'category' => 'B10', 'notes' => '§6 ust. 4 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 940],
            ['symbol' => '830', 'name' => 'Szkolenia kwalifikacyjne dla ON',
             'category' => 'B10', 'notes' => '§7 ust. 7', 'flag' => 1, 'parent_symbol' => '83', 'sort_order' => 941],
            ['symbol' => '831', 'name' => 'Staże i praktyki zawodowe dla ON',
             'category' => 'B10', 'notes' => '§7 ust. 8', 'flag' => 1, 'parent_symbol' => '83', 'sort_order' => 942],
            ['symbol' => '832', 'name' => 'Doradztwo zawodowe dla ON',
             'category' => 'B5',  'notes' => '§7 ust. 5', 'flag' => 1, 'parent_symbol' => '83', 'sort_order' => 943],
            ['symbol' => '833', 'name' => 'Certyfikaty i zaświadczenia ON',
             'category' => 'B10', 'notes' => 'Rejestr zaświadczeń kwalifikacyjnych', 'flag' => 1, 'parent_symbol' => '83', 'sort_order' => 944],

            // §6 ust. 5 — Promowanie samorozwoju osób niepełnosprawnych
            ['symbol' => '84',  'name' => 'Samorozwój osób niepełnosprawnych',
             'category' => 'B5',  'notes' => '§6 ust. 5 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 950],
            ['symbol' => '840', 'name' => 'Warsztaty rozwojowe i grupy wsparcia',
             'category' => 'B5',  'notes' => '§7 ust. 7', 'flag' => 1, 'parent_symbol' => '84', 'sort_order' => 951],
            ['symbol' => '841', 'name' => 'Doradztwo indywidualne',
             'category' => 'B5',  'notes' => '§7 ust. 5', 'flag' => 1, 'parent_symbol' => '84', 'sort_order' => 952],
            ['symbol' => '842', 'name' => 'Wydawnictwa i materiały samorozwojowe dla ON',
             'category' => 'A',   'notes' => '§7 ust. 1', 'flag' => 1, 'parent_symbol' => '84', 'sort_order' => 953],

            // §6 ust. 6 — Działania na rzecz osób starszych
            ['symbol' => '85',  'name' => 'Działania na rzecz osób starszych',
             'category' => 'B10', 'notes' => '§6 ust. 6 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 960],
            ['symbol' => '850', 'name' => 'Spotkania, konferencje — osoby starsze',
             'category' => 'B5',  'notes' => '§7 ust. 2, 3', 'flag' => 1, 'parent_symbol' => '85', 'sort_order' => 961],
            ['symbol' => '851', 'name' => 'Projekty aktywizacyjne dla osób starszych',
             'category' => 'B10', 'notes' => '§7 ust. 1–9', 'flag' => 1, 'parent_symbol' => '85', 'sort_order' => 962],
            ['symbol' => '852', 'name' => 'Szkolenia i warsztaty dla osób starszych',
             'category' => 'B5',  'notes' => '§7 ust. 7', 'flag' => 1, 'parent_symbol' => '85', 'sort_order' => 963],

            // §6 ust. 7 — Integracja osób niepełnosprawnych
            ['symbol' => '86',  'name' => 'Integracja osób niepełnosprawnych',
             'category' => 'B10', 'notes' => '§6 ust. 7 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 970],
            ['symbol' => '860', 'name' => 'Projekty integracyjne',
             'category' => 'B10', 'notes' => '§7 ust. 1–9', 'flag' => 1, 'parent_symbol' => '86', 'sort_order' => 971],
            ['symbol' => '861', 'name' => 'Spotkania i wydarzenia integracyjne',
             'category' => 'B5',  'notes' => '§7 ust. 2, 12', 'flag' => 1, 'parent_symbol' => '86', 'sort_order' => 972],
            ['symbol' => '862', 'name' => 'Współpraca z podmiotami ds. integracji ON',
             'category' => 'B10', 'notes' => '§7 ust. 4', 'flag' => 1, 'parent_symbol' => '86', 'sort_order' => 973],

            // §6 ust. 8 — Promowanie tyfloinformatyki
            ['symbol' => '87',  'name' => 'Tyfloinformatyka i technologie wspomagające',
             'category' => 'B10', 'notes' => '§6 ust. 8 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 980],
            ['symbol' => '870', 'name' => 'Doradztwo w zakresie technologii wspomagających',
             'category' => 'B5',  'notes' => '§7 ust. 5', 'flag' => 1, 'parent_symbol' => '87', 'sort_order' => 981],
            ['symbol' => '871', 'name' => 'Prezentacje i demonstracje technologii wspomagających',
             'category' => 'B5',  'notes' => '§7 ust. 6', 'flag' => 1, 'parent_symbol' => '87', 'sort_order' => 982],
            ['symbol' => '872', 'name' => 'Wydawnictwa i poradniki tyfloinformatyczne',
             'category' => 'A',   'notes' => '§7 ust. 1', 'flag' => 1, 'parent_symbol' => '87', 'sort_order' => 983],
            ['symbol' => '873', 'name' => 'Szkolenia z oprogramowania wspomagającego',
             'category' => 'B5',  'notes' => '§7 ust. 7', 'flag' => 1, 'parent_symbol' => '87', 'sort_order' => 984],

            // §6 ust. 9 — Działalność na rzecz NGO i aktywizacja społeczeństwa
            ['symbol' => '88',  'name' => 'Aktywizacja społeczeństwa i wsparcie NGO',
             'category' => 'B10', 'notes' => '§6 ust. 9 Statutu', 'flag' => 0, 'parent_symbol' => '8', 'sort_order' => 990],
            ['symbol' => '880', 'name' => 'Szkolenia dla organizacji pozarządowych',
             'category' => 'B10', 'notes' => '§7 ust. 11', 'flag' => 1, 'parent_symbol' => '88', 'sort_order' => 991],
            ['symbol' => '881', 'name' => 'Portal internetowy i media własne',
             'category' => 'B5',  'notes' => '§7 ust. 10', 'flag' => 1, 'parent_symbol' => '88', 'sort_order' => 992],
            ['symbol' => '882', 'name' => 'Partnerstwa i sieci z organizacjami',
             'category' => 'B10', 'notes' => '§7 ust. 4', 'flag' => 1, 'parent_symbol' => '88', 'sort_order' => 993],
            ['symbol' => '883', 'name' => 'Inicjatywy lokalne i społeczne',
             'category' => 'B10', 'notes' => '§7 ust. 13', 'flag' => 1, 'parent_symbol' => '88', 'sort_order' => 994],
            ['symbol' => '884', 'name' => 'Wszelkie inne działania sprzyjające celom Fundacji',
             'category' => 'B10', 'notes' => '§7 ust. 13 — klauzula generalna', 'flag' => 1, 'parent_symbol' => '88', 'sort_order' => 995],

            // ── ZAŚWIADCZENIA — klasy dla 4 typów dokumentów w systemie ──────
            // zaswiadczenie_zatrudnienie → akta osobowe / stosunki pracy
            ['symbol' => '105', 'name' => 'Zaświadczenia o zatrudnieniu i zarobkach',
             'category' => 'B50', 'notes' => 'Oryginały zaświadczeń wystawianych pracownikom i zleceniobiorcom potwierdzających stosunek pracy / umowę oraz wysokość wynagrodzenia. Okres B50 stosować dla umów o pracę sprzed 1.01.2019, B10 dla pozostałych — w razie wątpliwości stosuj B50.',
             'flag' => 1, 'parent_symbol' => '10', 'sort_order' => 215],

            // zaswiadczenie_umowy → umowy cywilnoprawne (zlecenie, dzieło, B2B)
            ['symbol' => '115', 'name' => 'Zaświadczenia potwierdzające wykonanie umów cywilnoprawnych',
             'category' => 'B10', 'notes' => 'Zaświadczenia wystawiane zleceniobiorcom, wykonawcom dzieł i kontrahentom B2B potwierdzające fakt zawarcia i wykonania umowy. Grupować wg roku i rodzaju umowy.',
             'flag' => 1, 'parent_symbol' => '11', 'sort_order' => 225],

            // zaswiadczenie_wolontariat → wolontariat (uzupełnienie klasy 144)
            ['symbol' => '146', 'name' => 'Zaświadczenia wolontariackie — egzemplarze wystawione',
             'category' => 'B10', 'notes' => 'Kopie/egzemplarze zaświadczeń wydanych wolontariuszom o odbyciu i zakresie wolontariatu. Rejestr wystawionych zaświadczeń prowadzić równolegle w klasie 144.',
             'flag' => 1, 'parent_symbol' => '12', 'sort_order' => 234],

            // zaswiadczenie_wspolpraca → współpraca zewnętrzna / porozumienia
            ['symbol' => '426', 'name' => 'Zaświadczenia i opinie o współpracy',
             'category' => 'B10', 'notes' => 'Zaświadczenia wystawiane podmiotom zewnętrznym (NGO, uczelnie, firmy) potwierdzające fakt i zakres współpracy z Fundacją FEER. Powiązane z aktami umów w klasie 73.',
             'flag' => 1, 'parent_symbol' => '73', 'sort_order' => 845],
        ];

        foreach ($rows as $row) {
            JrwaClass::updateOrCreate(['symbol' => $row['symbol']], $row);
        }

        $this->command->info('JrwaClassSeeder: wstawiono/zaktualizowano ' . count($rows) . ' klas.');
    }
}
