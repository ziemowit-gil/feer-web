<?php

namespace App\Console\Commands;

use App\Mail\PageDeadlineReminderMail;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Przypomnienia e-mail dla redakcji o terminach stron typu „Rekrutacja do
 * projektu" i „Zapytanie ofertowe" (wszystkie witryny federacji):
 *
 *   rec_end_soon          — nabór „trwa" i kończy się w ciągu 3 dni,
 *   rec_results           — nabór zakończony, a pole „Informacja po zakończeniu
 *                           naboru" (rec_results) jest puste,
 *   tender_deadline_soon  — termin składania ofert mija w ciągu 2 dni,
 *   tender_result         — od terminu ofert minęło ponad 7 dni, status nadal
 *                           „Ocena ofert" i brak wyniku postępowania.
 *
 * Każde przypomnienie wysyłamy raz: klucz „rodzaj:data-terminu" trafia do
 * type_data.reminders_sent (zmiana terminu = nowe przypomnienie). Przypomnienia
 * „po terminie" dotyczą tylko terminów z ostatnich AFTER_WINDOW_DAYS dni, żeby
 * po wdrożeniu nie zasypać redakcji wiadomościami o dawnych naborach.
 *
 * Adresat: autor strony (created_by), a gdy go brak — e-mail kontaktowy z danych
 * typu (contact_email). Bez adresata strona jest pomijana.
 */
#[Signature('strony:przypomnienia {--dry-run : Tylko wypisz, co zostałoby wysłane}')]
#[Description('Wysyła redakcji przypomnienia o kończących się naborach i terminach zapytań ofertowych.')]
class PageDeadlineReminders extends Command
{
    /** Ile dni przed końcem naboru przypominamy. */
    public const REC_END_DAYS = 3;

    /** Ile dni przed terminem składania ofert przypominamy. */
    public const TENDER_DEADLINE_DAYS = 2;

    /** Po ilu dniach od terminu ofert prosimy o wpisanie wyniku. */
    public const TENDER_RESULT_AFTER_DAYS = 7;

    /** Przypomnienia „po terminie" tylko dla terminów z tego okresu wstecz. */
    public const AFTER_WINDOW_DAYS = 60;

    public function handle(): int
    {
        $sent = 0;
        $skipped = 0;

        $pages = Page::query()->live()->whereIn('type', ['recruitment', 'tender'])->orderBy('id')->get();

        foreach ($pages as $page) {
            foreach ($this->dueReminders($page) as [$key, $kind, $headline, $body]) {
                $sentKeys = $this->sentKeys($page);
                if (in_array($key, $sentKeys, true)) {
                    continue;
                }

                $recipient = $this->recipient($page);
                if (! $recipient) {
                    $skipped++;
                    $this->line("Pominięto (brak adresata): #{$page->id} {$page->title} — {$kind}");

                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("[dry-run] {$recipient}: {$headline} — #{$page->id} {$page->title}");

                    continue;
                }

                try {
                    Mail::to($recipient)->send(new PageDeadlineReminderMail(
                        $page, $kind, $headline, $body,
                        $this->editUrl($page), $this->publicUrl($page), $this->siteName($page),
                    ));
                } catch (\Throwable $e) {
                    report($e);

                    continue;
                }

                $this->markSent($page, $key);
                $sent++;
            }
        }

        $this->info("Wysłano przypomnień: {$sent}".($skipped ? ", pominięto bez adresata: {$skipped}" : '').'.');

        return self::SUCCESS;
    }

    /**
     * Przypomnienia należne dla strony dziś.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}> [klucz, rodzaj, nagłówek, treść]
     */
    private function dueReminders(Page $page): array
    {
        $now = Carbon::now();
        $td = $page->typeData();
        $due = [];

        if ($page->isRecruitment()) {
            $status = $page->recruitmentStatus();
            $end = $page->recruitmentDate('end');

            if ($status === 'open' && $end && $end->greaterThanOrEqualTo($now)) {
                $days = $this->daysUntil($end);
                if ($days <= self::REC_END_DAYS) {
                    $due[] = [
                        'rec_end_soon:'.$end->toDateString(),
                        'rec_end_soon',
                        $days <= 0 ? 'Nabór kończy się dziś' : 'Nabór kończy się za '.$this->daysLabel($days),
                        'Nabór kończy się '.$this->dateLabel($end).'. Sprawdź, czy informacje o naborze są aktualne, i — jeśli to potrzebne — przedłuż termin albo przygotuj informację po zakończeniu naboru.',
                    ];
                }
            }

            if ($status === 'closed' && blank($td['rec_results'] ?? null)
                && (! $end || $end->greaterThanOrEqualTo($now->copy()->subDays(self::AFTER_WINDOW_DAYS)))) {
                $due[] = [
                    'rec_results:'.($end?->toDateString() ?? 'closed'),
                    'rec_results',
                    'Uzupełnij informację po zakończeniu naboru',
                    'Nabór jest zakończony'.($end ? ' ('.$this->dateLabel($end).')' : '').', a na stronie brakuje informacji dla kandydatów, co dalej (np. termin ogłoszenia wyników, lista rezerwowa). Uzupełnij pole „Informacja po zakończeniu naboru".',
                ];
            }
        }

        if ($page->isTender()) {
            $status = $page->tenderStatus();
            $deadline = $page->tenderDeadline();

            if ($status === 'open' && $deadline && $deadline->greaterThanOrEqualTo($now)) {
                $days = $this->daysUntil($deadline);
                if ($days <= self::TENDER_DEADLINE_DAYS) {
                    $due[] = [
                        'tender_deadline_soon:'.$deadline->toDateString(),
                        'tender_deadline_soon',
                        $days <= 0 ? 'Termin składania ofert mija dziś' : 'Termin składania ofert mija za '.$this->daysLabel($days),
                        'Termin składania ofert mija '.$this->dateLabel($deadline).'. Sprawdź, czy wszystkie wyjaśnienia i zmiany zapytania są opublikowane.',
                    ];
                }
            }

            if ($status === 'evaluation' && blank($td['tender_result'] ?? null) && $deadline
                && $deadline->lessThan($now->copy()->subDays(self::TENDER_RESULT_AFTER_DAYS))
                && $deadline->greaterThanOrEqualTo($now->copy()->subDays(self::AFTER_WINDOW_DAYS))) {
                $due[] = [
                    'tender_result:'.$deadline->toDateString(),
                    'tender_result',
                    'Wpisz wynik postępowania',
                    'Termin składania ofert minął '.$this->dateLabel($deadline).', a strona nadal pokazuje status „Ocena ofert". Wpisz wynik postępowania (albo ustaw status „Unieważnione").',
                ];
            }
        }

        return $due;
    }

    /** @return list<string> */
    private function sentKeys(Page $page): array
    {
        $sent = is_array($page->type_data) ? ($page->type_data['reminders_sent'] ?? []) : [];

        return is_array($sent) ? array_values(array_filter($sent, 'is_string')) : [];
    }

    /**
     * Zapis klucza wysłanego przypomnienia bez zdarzeń modelu (bez rewizji,
     * dziennika aktywności, indeksu wyszukiwarki i zmiany updated_at).
     */
    private function markSent(Page $page, string $key): void
    {
        $data = is_array($page->type_data) ? $page->type_data : [];
        $data['reminders_sent'] = array_values(array_unique([...$this->sentKeys($page), $key]));
        $page->type_data = $data;
        $page->timestamps = false;
        $page->saveQuietly();
        $page->timestamps = true;
    }

    private function recipient(Page $page): ?string
    {
        if ($page->created_by && ($email = User::query()->whereKey($page->created_by)->value('email'))
            && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $contact = trim((string) ($page->typeData()['contact_email'] ?? ''));

        return filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;
    }

    /** Pełne dni kalendarzowe do terminu (0 = dziś). */
    private function daysUntil(Carbon $date): int
    {
        return (int) Carbon::now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);
    }

    private function daysLabel(int $days): string
    {
        return $days === 1 ? '1 dzień' : $days.' dni';
    }

    private function dateLabel(Carbon $date): string
    {
        return $date->copy()->locale('pl')->translatedFormat($date->format('H:i') === '23:59' ? 'j F Y' : 'j F Y, \g\o\d\z. H:i');
    }

    private function site(Page $page): ?SiteSetting
    {
        return $page->site_id ? SiteSetting::query()->find($page->site_id) : null;
    }

    /** Nazwa witryny — tylko dla sub-witryn federacji, żeby redakcja wiedziała, gdzie szukać strony. */
    private function siteName(Page $page): ?string
    {
        $site = $this->site($page);

        return $site && $site->isSubsite() ? ($site->site_name ?: null) : null;
    }

    /** Link do edycji w panelu — na domenie sub-witryny, jeśli ma własną. */
    private function editUrl(Page $page): string
    {
        $site = $this->site($page);
        $path = route('admin.podstrony.edit', $page, false);

        return $site && $site->domain ? 'https://'.$site->domain.$path : url($path);
    }

    private function publicUrl(Page $page): string
    {
        $site = $this->site($page);
        if ($site && $site->isSubsite()) {
            return rtrim(current_site_url($site), '/').'/'.$page->slug;
        }

        return $page->publicUrl();
    }
}
