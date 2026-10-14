<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Zajawka i najważniejsze informacje działania pokazują się tylko w zakładce „Opis”, nie na każdej podstronie. */
class ProjectSubpageHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_zajawka_i_fakty_tylko_w_zakladce_opis(): void
    {
        $category = Category::create(['name' => 'Współpraca', 'slug' => 'wspolpraca-hdr']);
        $project = Project::create(['title' => 'Współpraca z instytucjami', 'slug' => 'wspolpraca-hdr', 'is_published' => true,
            'category_id' => $category->id, 'excerpt' => 'Współpracujemy z instytucjami na różnych zasadach.']);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<div x-show="tab === \'opis\'"\s*>\s*<p class="mt-4 max-w-2xl text-lg[^>]*>Współpracujemy z instytucjami#', $html);
        // Nagłówek jest w zasięgu stanu zakładek (x-data z „tab”).
        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'tabs: '));
    }
}
