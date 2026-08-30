<?php

namespace App\Http\Controllers;

/**
 * Widoki specyficzne dla szablonu „federation" (federacja organizacji pozarządowych).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FederationController extends Controller
{
    /** Lista organizacji członkowskich federacji. */
    public function organizations()
    {
        $organizations = [
            'Uniwersytet Trzeciego Wieku w Andrychowie',
            'Klub Żeglarski HORN Kraków',
            'Ogólnopolski Związek Inwalidów Narządu Ruchu',
            'Stowarzyszenie Absolwentów Liceum Ogólnokształcącego im. Marcina Wadowity w Wadowicach',
            'Krakowskie Towarzystwo Pomocy Uzależnionym',
            'Stowarzyszenie Przyjaciół im. Św. Brata Alberta',
            'Polski Związek Niewidomych. Okręg małopolski',
            'Regionalne Stowarzyszenie Diabetyków z Siedzibą w Chrzanowie',
            'Fundacja na Rzecz Chorych na SM im. bł. Anieli Salawy',
            'Polski Związek Emerytów, Rencistów i Inwalidów. Zarząd oddziału rejonowego Kraków - Podgórze',
            'Stowarzyszenie Pomocy Szkole Małopolska',
            'Stowarzyszenia Przyjaciół Osób Niepełnosprawnych Wspólna Radość',
            'Bank Żywności w Krakowie',
            'Stowarzyszenie Dobroczynne "Betlejem"',
            'Chrześcijańskie Stowarzyszenie Dobroczynne',
            'Polskie Stowarzyszenie na Rzecz Osób z Upośledzeniem Umysłowym Koło w Jabłonce',
            'Fundacja Dla Dzieci Młodzieży I Dorosłych Niepełnosprawnych Intelektualnie',
            'Krajowe Towarzystwo Autyzmu Oddział w Krakowie',
            'Stowarzyszenie na rzecz Domu Pomocy Społecznej im. Św. Brata Alberta w Krakowie oraz osób niepełnosprawnych',
            'Krakowskie Stowarzyszenie Terapeutów Uzależnień',
            'Stowarzyszenie Lekarzy Nadziei',
            'Stowarzyszenie Rodzin Adopcyjnych i Zastępczych „Pro Familia"',
            'Krakowska Fundacja Pomocy Potrzebującym "Nasz Dom" im. Św. Brata Alberta',
            'Stowarzyszenie Przyjaciół Harcerstwa',
            'Stowarzyszenie Rozwoju i Integracji Młodzieży ST.R.I.M',
            'Małopolski Związek Osób Niepełnosprawnych w Bochni',
        ];

        return view('templates.federation.organizations', compact('organizations'));
    }
}
