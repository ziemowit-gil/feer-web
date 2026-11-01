<?php

namespace Tests\Feature;

use Modules\Blog\Models\BlogAuthor;
use Tests\Concerns\AssertsJsonLd;

/** Dane strukturalne JSON-LD bloga „Wiem FEER" (wpis i profil autora) — patrz StructuredDataJsonLdTest. */
class BlogStructuredDataTest extends BlogTestCase
{
    use AssertsJsonLd;

    public function test_wpis_ma_blogposting_z_poprawnym_jsonld(): void
    {
        $anna = BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak', 'role' => 'Trenerka dostępności']);
        $this->article('Wpis z autorką', ['author_id' => $anna->id, 'excerpt' => 'Zajawka wpisu.']);

        $blocks = $this->assertJsonLdTypes($this->get('/wiem/wpis-z-autorka')->assertOk()->getContent(), ['Organization', 'BlogPosting']);

        $post = $blocks['BlogPosting'];
        $this->assertSame('Wpis z autorką', $post['headline']);
        $this->assertSame('Person', $post['author'][0]['@type']);
        $this->assertSame('Anna Nowak', $post['author'][0]['name']);
    }

    public function test_profil_autora_ma_profilepage_z_poprawnym_jsonld(): void
    {
        BlogAuthor::create(['name' => 'Anna Nowak', 'slug' => 'anna-nowak', 'role' => 'Trenerka dostępności', 'bio' => 'Uczy WCAG.']);

        $blocks = $this->assertJsonLdTypes($this->get('/wiem/autor/anna-nowak')->assertOk()->getContent(), ['Organization', 'ProfilePage']);

        $person = $blocks['ProfilePage']['mainEntity'];
        $this->assertSame('Person', $person['@type']);
        $this->assertSame('Anna Nowak', $person['name']);
        $this->assertSame('Trenerka dostępności', $person['jobTitle']);
    }
}
