<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Modules\Blog\Models\BlogComment;
use Modules\Blog\Notifications\BlogCommentAwaitingModeration;

class BlogCommentsTest extends BlogTestCase
{
    /** @param array<string, mixed> $extra */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'author_name' => 'Ola',
            'body'        => 'Świetny tekst, dziękuję!',
            SpamGuard::TOKEN_FIELD  => Crypt::encryptString(json_encode(['t' => now()->subSeconds(20)->timestamp, 'a' => 7])),
            SpamGuard::ANSWER_FIELD => 'siedem',
        ], $extra);
    }

    public function test_formularz_ma_ochrone_antyspamowa(): void
    {
        $this->article('Wpis');

        $this->get('/wiem/wpis')->assertOk()->assertSee('Zadanie antyspamowe', false)->assertSee('name="form_check"', false);
    }

    public function test_komentarz_czeka_na_moderacje_i_powiadamia_moderatorow(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->article('Wpis');

        $this->post('/wiem/wpis/komentarz', $this->payload())->assertRedirect()->assertSessionHas('comment_status');

        $this->assertSame(1, BlogComment::pending()->count());
        Notification::assertSentTo($admin, BlogCommentAwaitingModeration::class);
        Notification::assertNotSentTo($editor, BlogCommentAwaitingModeration::class);
    }

    public function test_bot_bez_zetonu_dostaje_podziekowanie_ale_nic_nie_zapisujemy(): void
    {
        Notification::fake();
        $this->article('Wpis');

        $this->post('/wiem/wpis/komentarz', ['author_name' => 'Bot', 'body' => 'Spam'])->assertRedirect()->assertSessionHas('comment_status');
        $this->assertSame(0, BlogComment::count());
        Notification::assertNothingSent();
    }

    public function test_bledna_odpowiedz_na_zadanie_pokazuje_blad(): void
    {
        $this->article('Wpis');

        $this->post('/wiem/wpis/komentarz', $this->payload([SpamGuard::ANSWER_FIELD => '3']))->assertSessionHasErrors('spam');
        $this->assertSame(0, BlogComment::count());
    }

    public function test_odpowiedz_redakcji_zatwierdza_komentarz_i_jest_widoczna(): void
    {
        $article = $this->article('Wpis');
        $comment = $article->comments()->create(['author_name' => 'Ola', 'body' => 'Pytanie czytelniczki', 'is_approved' => false]);

        $this->actingAs($this->admin())->get(route('admin.komentarze-bloga.index'))->assertOk()->assertSee('Odpowiedz jako redakcja');

        $this->actingAs($this->admin())
            ->post(route('admin.komentarze-bloga.reply', $comment), ['reply_body' => 'Dziękujemy za pytanie!'])
            ->assertRedirect();

        $this->assertTrue($comment->fresh()->is_approved);
        $reply = BlogComment::where('parent_id', $comment->id)->first();
        $this->assertTrue($reply->is_staff_reply);
        $this->assertTrue($reply->is_approved);

        $this->get('/wiem/wpis')->assertOk()
            ->assertSeeInOrder(['Pytanie czytelniczki', 'Odpowiedź redakcji', 'Dziękujemy za pytanie!'])
            ->assertSee('Komentarze <span class="text-base font-normal text-muted">(1)</span>', false);

        $this->actingAs($this->admin())->post(route('admin.komentarze-bloga.reply', $comment), ['reply_body' => ''])
            ->assertSessionHasErrors('reply_body');
    }

    public function test_usuniecie_komentarza_usuwa_odpowiedzi(): void
    {
        $article = $this->article('Wpis');
        $comment = $article->comments()->create(['author_name' => 'Ola', 'body' => 'X', 'is_approved' => true]);
        BlogComment::create(['article_id' => $article->id, 'parent_id' => $comment->id, 'author_name' => 'Red', 'body' => 'Y', 'is_approved' => true, 'is_staff_reply' => true]);

        $this->actingAs($this->admin())->delete(route('admin.komentarze-bloga.destroy', $comment))->assertRedirect();
        $this->assertSame(0, BlogComment::count());
    }
}
