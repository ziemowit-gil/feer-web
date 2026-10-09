<?php

declare(strict_types=1);

namespace Modules\Newsletter\View\Components;

use App\Models\SiteSetting;
use App\Support\SpamGuard;
use Illuminate\View\Component;
use Illuminate\View\View;
use Modules\Newsletter\Models\NewsletterForm;

/**
 * <x-newsletter-widget :form="$form" style="band|card|inline" source="widget_home" />
 * Bez wskazanego formularza bierze domyślny; gdy żaden nie istnieje — nie renderuje nic
 * (szablony mogą wtedy pokazać stary kod osadzenia z ustawień).
 */
class SignupWidget extends Component
{
    public ?NewsletterForm $form;
    public array $challenge;

    public function __construct(?NewsletterForm $form = null, public ?string $style = null, public string $source = 'widget', public bool $heading = true)
    {
        $this->form = $form ?? NewsletterForm::defaultFor(SiteSetting::current()->id);
        $this->style ??= $this->form?->style ?? 'band';
        $this->challenge = SpamGuard::challenge();
    }

    public function shouldRender(): bool
    {
        return $this->form !== null && $this->form->is_active;
    }

    public function render(): View
    {
        return view('newsletter::components.signup-widget');
    }
}
