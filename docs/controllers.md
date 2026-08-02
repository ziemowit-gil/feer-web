# Kontrolery FEER CMS

**Autor:** Ziemowit Gil <ziemowit.gil@feer.org.pl>

---

## Kontrolery publiczne (`App\Http\Controllers`)

| Kontroler | Opis | Metody |
|-----------|------|--------|
| `AccessibilityController` | Publiczna strona „Deklaracja dostępności" z formularzem zgłaszania barier. | `show()` |
| `AccessibilityReportController` | Przyjmuje zgłoszenia barier dostępności z formularza; wysyła powiadomienie mailowe. | `store()` |
| `BannerTrackingController` | Rejestruje statystyki banerów: wyświetlenia (próbkowanie 1:5) i kliknięcia. | `impression()`, `click()` |
| `BlogCommentController` | Przyjmuje komentarze do artykułów bloga; trafiają do moderacji. | `store()` |
| `BlogController` | Publiczna lista i widok artykułów bloga Wiem FEER. | `index()`, `show()` |
| `ContactController` | Strona kontaktowa z listą koordynatorów i formularzem e-mail. | `index()`, `store()` |
| `Controller` *(bazowy)* | Bazowy kontroler z metodą sprawdzającą podpisany podgląd szkicu. | `isPreviewRequest()` |
| `EducationalMaterialController` | Publiczna lista opublikowanych materiałów edukacyjnych. | `index()` |
| `EtrController` | Publiczna strona informacyjna o standardzie ETR (Easy-To-Read). | `about()` |
| `EventController` | Publiczna lista nadchodzących szkoleń/wydarzeń i widok szczegółów. | `index()`, `show()` |
| `FaqController` | Publiczna strona FAQ — pytania pogrupowane wg kategorii. | `index()` |
| `HomeController` | Strona główna — agreguje dane ze wszystkich włączonych modułów. | `index()` |
| `LandingPageController` | Landing page webinaru/szkolenia: widok, zapis (AJAX) i plik .ics. | `show()`, `register()`, `calendar()` |
| `MaterialSubscriberController` | Zapis e-maila na powiadomienia o nowych materiałach edukacyjnych. | `store()` |
| `MeetingSignupController` | Formularz „Daj znać, że przyjdziesz" (strona kontaktowa i /booking). | `publicShow()`, `publicStore()`, `store()` |
| `NewsController` | Publiczna lista i szczegóły aktualności oraz widok do wydruku (PDF). | `index()`, `show()`, `pdf()` |
| `NewsletterController` | Publiczna strona newslettera z osadzonym formularzem zapisu. | `index()` |
| `PageController` | Podstrony z kontrolą dostępu (hasło/MS365) i obsługą podglądu szkicu. | `show()`, `unlock()` |
| `PollVoteController` | Oddawanie głosu w ankiecie z dedupeiem po sesji przeglądarki. | `store()` |
| `ProfileController` | Profil zalogowanego użytkownika: dane, powiadomienia, usunięcie konta. | `edit()`, `update()`, `updateNotifications()`, `destroy()` |
| `ProjectController` | Publiczne listy projektów (bieżące, archiwalne, wg kategorii) i szczegóły. | `index()`, `archive()`, `category()`, `show()` |
| `ReportController` | Publiczna strona sprawozdań rocznych. | `index()` |
| `ReserwacjeController` | Panel strefy współpracownika (MS365): terminy spotkań i zgłoszenia. | `index()`, `storeTermin()`, `destroyTermin()`, `notify()`, `confirmSignup()`, `destroySignup()`, `export()` |
| `SearchController` | Wyszukiwarka serwisu (strony, aktualności, projekty, materiały, blog). | `index()` |
| `ShortcutController` | Przekierowania skrótowe: /bip, /instagram, /facebook. | `bip()`, `instagram()`, `facebook()` |
| `SitemapController` | Plik XML sitemap (dla robotów) i strona HTML „Mapa strony". | `index()`, `page()` |
| `SupportController` | Publiczna strona „Wesprzyj nas". | `index()` |
| `VolunteerController` | Publiczna lista i szczegóły aktywnych ogłoszeń wolontariackich. | `index()`, `show()` |

---

## Trait

| Trait | Opis | Metody |
|-------|------|--------|
| `Concerns\HandlesContentApproval` | Obieg akceptacji treści: bez uprawnienia `canApproveContent()` żądanie publikacji trafia do kolejki. | `applyApprovalWorkflow()` |

---

## Kontrolery panelu administracyjnego (`App\Http\Controllers\Admin`)

| Kontroler | Opis | Metody |
|-----------|------|--------|
| `AccessibilityReportController` | Lista zgłoszeń barier dostępności — usuwanie i eksport CSV. | `index()`, `destroy()`, `export()` |
| `ActivityController` | Przeglądarka dziennika zdarzeń (ActivityLog) z filtrami. | `index()` |
| `AnnualReportController` | CRUD sprawozdań rocznych (merytorycznych i finansowych) z plikami PDF. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `ApprovalController` | Kolejka treści oczekujących na zatwierdzenie z akcjami i licznikiem badge'a. | `index()`, `approve()`, `reject()`, `pendingCount()` |
| `AttachmentController` | Zarządzanie załącznikami (plikami) przypiętymi do podstron i aktualności. | `storeForPage()`, `storeForNews()`, `lista()`, `destroy()` |
| `BannerController` | CRUD banerów graficznych/HTML z przypisywaniem do stref i toggle aktywności. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `toggle()` |
| `BannerZoneController` | Zarządzanie strefami banerów (miejscami wyświetlania). | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `BlogArticleController` | CRUD artykułów bloga Wiem FEER z klonowaniem i togglem dostępności. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `clone()`, `toggleDisabled()` |
| `BlogCommentController` | Moderacja komentarzy bloga — zatwierdzanie i usuwanie. | `index()`, `approve()`, `destroy()` |
| `CalendarController` | Kalendarz redakcyjny — aktualności i wydarzenia w układzie miesięcznym. | `index()` |
| `CategoryController` | CRUD kategorii projektów. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `ContentPortabilityController` | Eksport całego serwisu do ZIP i import z ZIP. | `index()`, `export()`, `import()` |
| `ContentTemplateController` | Szablony treści wielokrotnego użycia (JSON API dla edytora). | `index()`, `manage()`, `load()`, `store()`, `destroy()` |
| `DashboardController` | Główny pulpit z licznikami treści i ostatnimi wpisami. | `index()` |
| `DocxImportController` | Konwersja pliku .docx na HTML do wklejenia w edytor treści. | `import()` |
| `EditLockController` | Heartbeat blokady jednoczesnej edycji (cache-based, odświeżany co 30 s). | `__invoke()` |
| `EducationalMaterialController` | CRUD materiałów edukacyjnych z obsługą MediaLibrary. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `EtrController` | Zapis i usuwanie wersji ETR aktualności i podstron. | `update()`, `destroy()` |
| `EventController` | CRUD szkoleń/wydarzeń z archiwizacją, klonowaniem, operacjami zbiorczymi i konwersją na aktualność/LP. | `index()`, `archive()`, `restore()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `clone()`, `bulk()`, `toNews()`, `toLanding()` |
| `FaqController` | CRUD pytań i odpowiedzi (FAQ). | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `GalleryImageController` | CRUD zdjęć w galerii publicznej. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `HeroSlideController` | Zarządzanie slajdami sekcji hero i edytor slajdu z misją organizacji. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `updateMissionSlide()` |
| `HomepageLayoutController` | Zapis kolejności sekcji strony głównej (drag & drop, JSON API). | `updateSectionOrder()` |
| `LandingPageController` | CRUD landing page'ów webinarów z listą uczestników i eksportem CSV. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `registrations()`, `exportRegistrations()` |
| `LinkCheckController` | Skaner martwych linków w treści aktualności, podstron i projektów. | `index()`, `scan()` |
| `MaterialSubscriberController` | Lista subskrybentów powiadomień o materiałach z eksportem CSV. | `index()`, `destroy()`, `export()` |
| `MediaLibraryController` | Biblioteka multimediów: upload, foldery, tagi, alt-tekst, Unsplash, OneDrive, ZIP. | `index()`, `store()`, `uploadAjax()`, `bulk()`, `rename()`, `updateTags()`, `updateAuthor()`, `updateAlt()`, `altAudit()`, `imagesJson()`, `unsplashSearch()`, `unsplashImport()`, `oneDriveImport()`, `move()`, `archive()`, `restore()`, `destroy()`, `export()`, `exportSelected()`, `import()`, `emptyArchive()`, `storeFolder()`, `updateFolder()`, `destroyFolder()` |
| `MeetingSignupController` | Lista zgłoszeń spotkań z usuwaniem i eksportem CSV. | `index()`, `destroy()`, `export()` |
| `NavItemController` | Zarządzanie pozycjami menu z reorderingiem drag & drop. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `move()`, `reorder()` |
| `NewsCategoryController` | CRUD kategorii aktualności. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `NewsController` | Zarządzanie aktualnościami z wyszukiwaniem, klonowaniem i operacjami zbiorczymi. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `clone()`, `bulk()` |
| `NewsletterController` | Edycja kodu osadzającego zewnętrzny formularz newslettera. | `edit()`, `update()` |
| `NotificationController` | Oznaczanie powiadomień jako przeczytanych (JSON API). | `seen()` |
| `PageController` | Zarządzanie podstronami — wiele typów, harmonogram, dostęp, operacje zbiorcze. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `toggleVisibility()`, `toggleFeatured()`, `toggleDisabled()`, `updateOrder()`, `bulk()`, `clone()` |
| `PageImageController` | Galeria zdjęć przypiętych do konkretnej podstrony. | `store()`, `update()`, `destroy()` |
| `PartnerController` | CRUD partnerów organizacji (logotypy, linki, kolejność). | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `PollController` | CRUD ankiet z zarządzaniem opcjami głosowania. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `ProjectController` | Zarządzanie projektami z cennikiem i sekcjami własnymi. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `QuickActionController` | Zarządzanie szybkimi akcjami (kafelki na stronie głównej). | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `RedirectController` | Zarządzanie przekierowaniami HTTP z eksportem/importem CSV. | `index()`, `store()`, `update()`, `destroy()`, `export()`, `import()` |
| `RevisionController` | Historia wersji treści z podglądem JSON i przywracaniem. | `index()`, `json()`, `restore()` |
| `SearchController` | Wyszukiwarka poleceń (Ctrl+K) filtrowana wg uprawnień. | `__invoke()` |
| `SiteSettingController` | Ustawienia serwisu: wygląd, moduły, kontakt, poczta, dostępność, SEO. | `edit()`, `update()`, `dev()`, `overwriteStrefa()`, `updateAdminPrefix()`, `mailTest()`, `regenerateEmergencyToken()` |
| `TaskController` | Zarządzanie zadaniami wewnętrznymi (kanban) z powiadomieniami. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `done()`, `myPendingCount()` |
| `TimelineController` | Dedykowany edytor osi czasu (historii) strony „O organizacji". | `edit()`, `update()` |
| `TrashController` | Kosz usuniętych treści — przywracanie i trwałe kasowanie. | `index()`, `restore()`, `forceDelete()`, `count()` |
| `UserController` | CRUD użytkowników panelu z rolami, grupami i odłączaniem MS365. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `unlinkMicrosoft()` |
| `UserGroupController` | Zarządzanie grupami użytkowników i ich uprawnieniami do modułów. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` |
| `VolunteerAdController` | Zarządzanie ogłoszeniami wolontariackimi z archiwizacją, klonowaniem i operacjami zbiorczymi. | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `clone()`, `bulk()`, `archive()`, `restore()` |

---

## Kontrolery autentykacji (`App\Http\Controllers\Auth`)

| Kontroler | Opis | Metody |
|-----------|------|--------|
| `AuthenticatedSessionController` | Logowanie hasłem (e-mail + hasło) z obsługą 2FA i dostępu awaryjnego. | `create()`, `createEmergency()`, `store()`, `destroy()` |
| `ConfirmablePasswordController` | Ponowne potwierdzenie hasła przed wrażliwymi operacjami. | `show()`, `store()` |
| `EmailVerificationNotificationController` | Ponowne wysłanie e-maila weryfikacyjnego (throttle 60 s). | `store()` |
| `EmailVerificationPromptController` | Ekran z prośbą o weryfikację adresu e-mail. | `__invoke()` |
| `MemberMicrosoftAuthController` | Logowanie współpracowników do strefy wewnętrznej przez Microsoft 365. | `create()`, `redirect()`, `callback()`, `destroy()` |
| `MicrosoftAuthController` | Logowanie administratorów/redaktorów do panelu przez Microsoft 365 (SSO). | `redirect()`, `callback()` |
| `NewPasswordController` | Ustawienie nowego hasła po kliknięciu linku resetującego. | `create()`, `store()` |
| `PasswordController` | Zmiana hasła przez zalogowanego użytkownika z profilu. | `update()` |
| `PasswordResetLinkController` | Wysyłka linku resetującego hasło na adres e-mail. | `create()`, `store()` |
| `TwoFactorChallengeController` | Drugi składnik logowania: TOTP, kody zapasowe lub YubiKey. | `create()`, `store()` |
| `TwoFactorSettingController` | Zarządzanie 2FA z profilu: TOTP, kody zapasowe, YubiKey. | `enable()`, `confirm()`, `disable()`, `regenerateRecovery()`, `addYubikey()`, `removeYubikey()` |
| `VerifyEmailController` | Potwierdzenie adresu e-mail przez link weryfikacyjny. | `__invoke()` |
