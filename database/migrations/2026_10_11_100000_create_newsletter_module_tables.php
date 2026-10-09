<?php

use App\Models\Subscriber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Moduł Newsletter: rozszerzenie `subscribers` oraz tabele newsletter_*.
 * Istniejąca tabela `campaigns` (zbiórki) pozostaje bez zmian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->after('id')->index();
            $table->uuid('uuid')->nullable()->after('site_id')->unique();
            $table->char('email_hash', 64)->nullable()->after('email')->unique();
            $table->text('phone')->nullable()->after('name');
            $table->string('status', 20)->default('pending')->after('phone')->index();
            $table->json('channels')->nullable()->after('topics');
            $table->json('tags')->nullable()->after('channels');
            $table->string('locale', 5)->default('pl')->after('tags');
            $table->string('timezone', 64)->default('Europe/Warsaw')->after('locale');
            $table->string('source', 64)->nullable()->after('timezone');
            $table->timestamp('confirmation_sent_at')->nullable()->after('confirmed_at');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_reason', 255)->nullable();
            $table->unsignedBigInteger('unsubscribe_campaign_id')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_open_at')->nullable()->index();
            $table->timestamp('last_click_at')->nullable();
            $table->smallInteger('engagement_score')->default(0);
            $table->timestamp('anonymized_at')->nullable();
            $table->string('szo_contact_id', 64)->nullable();
            $table->timestamp('szo_synced_at')->nullable();
            $table->string('szo_error', 500)->nullable();
            $table->timestamp('crm_synced_at')->nullable();
            $table->string('crm_error', 500)->nullable();
        });

        // Backfill: uuid, ślepy indeks e-maila, status z confirmed_at, kanał e-mail.
        DB::table('subscribers')->orderBy('id')->lazy()->each(function ($row) {
            DB::table('subscribers')->where('id', $row->id)->update([
                'uuid'       => (string) Str::uuid(),
                'email_hash' => Subscriber::hashEmail($row->email),
                'status'     => $row->confirmed_at ? Subscriber::STATUS_CONFIRMED : Subscriber::STATUS_PENDING,
                'channels'   => json_encode(['email']),
                'source'     => 'legacy',
            ]);
        });

        Schema::create('newsletter_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('newsletter_list_subscriber', function (Blueprint $table) {
            $table->foreignId('list_id')->constrained('newsletter_lists')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->primary(['list_id', 'subscriber_id']);
        });

        Schema::create('newsletter_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name', 120);
            $table->json('rules');
            $table->unsignedInteger('cached_count')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->unsignedBigInteger('clause_id')->nullable();
            $table->string('clause_version', 20)->default('1');
            $table->text('clause_text');
            $table->string('source', 64);
            $table->string('method', 32);
            $table->char('ip_hash', 16)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_via', 32)->nullable();
            $table->index(['subscriber_id', 'channel']);
        });

        Schema::create('newsletter_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->string('eyebrow', 120)->nullable();
            $table->string('heading', 200);
            $table->text('lead')->nullable();
            $table->boolean('ask_name')->default(true);
            $table->boolean('ask_phone')->default(false);
            $table->boolean('show_topics')->default(true);
            $table->json('topics')->nullable();
            $table->json('default_topics')->nullable();
            $table->boolean('offer_webpush')->default(false);
            $table->boolean('offer_sms')->default(false);
            $table->text('consent_text');
            $table->unsignedBigInteger('clause_id')->nullable();
            $table->string('privacy_url', 500)->nullable();
            $table->string('button_label', 60)->default('Zapisz się');
            $table->text('success_message');
            $table->string('style', 20)->default('band');
            $table->string('accent_color', 7)->nullable();
            $table->json('list_ids')->nullable();
            $table->string('szo_form_slug', 120)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('submissions_count')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('newsletter_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('kind', 16)->default('mosaico');
            $table->string('mosaico_template', 64)->nullable();
            $table->json('editor_metadata')->nullable();
            $table->json('editor_content')->nullable();
            $table->mediumText('html_body')->nullable();
            $table->string('thumbnail_path', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->string('title', 160);
            $table->string('subject', 255)->nullable();
            $table->string('subject_b', 255)->nullable();
            $table->unsignedTinyInteger('ab_split_percent')->nullable();
            $table->string('preheader', 255)->nullable();
            $table->string('from_name', 120)->nullable();
            $table->string('from_address', 255)->nullable();
            $table->string('reply_to', 255)->nullable();
            $table->foreignId('template_id')->nullable()->constrained('newsletter_templates')->nullOnDelete();
            $table->string('mosaico_template', 64)->nullable();
            $table->json('editor_metadata')->nullable();
            $table->json('editor_content')->nullable();
            $table->mediumText('html_body')->nullable();
            $table->mediumText('text_body')->nullable();
            $table->string('short_title', 60)->nullable();
            $table->string('short_text', 160)->nullable();
            $table->string('short_url', 500)->nullable();
            $table->json('channels');
            $table->json('audience');
            $table->json('utm')->nullable();
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('send_in_recipient_tz')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('batch_id', 36)->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('opened_unique')->default(0);
            $table->unsignedInteger('clicked_unique')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('complained_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);
            $table->string('recurrence', 16)->nullable();
            $table->json('recurrence_rule')->nullable();
            $table->unsignedBigInteger('parent_campaign_id')->nullable()->index();
            $table->json('content_snapshot')->nullable();
            $table->timestamp('last_feed_item_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('newsletter_campaign_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->char('hash', 16);
            $table->text('url');
            $table->string('label', 255)->nullable();
            $table->unsignedSmallInteger('position')->nullable();
            $table->unsignedInteger('clicks_total')->default(0);
            $table->unsignedInteger('clicks_unique')->default(0);
            $table->unique(['campaign_id', 'hash']);
        });

        Schema::create('newsletter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->char('variant', 1)->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('provider', 32)->nullable();
            $table->string('provider_message_id', 255)->nullable()->index();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedSmallInteger('opens_count')->default(0);
            $table->unsignedSmallInteger('clicks_count')->default(0);
            $table->unique(['campaign_id', 'subscriber_id', 'channel']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('newsletter_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('newsletter_deliveries')->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->char('ip_hash', 16)->nullable();
            $table->string('client_family', 32)->nullable();
            $table->boolean('is_proxy')->default(false);
            $table->index(['delivery_id', 'opened_at']);
        });

        Schema::create('newsletter_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('newsletter_deliveries')->cascadeOnDelete();
            $table->foreignId('link_id')->constrained('newsletter_campaign_links')->cascadeOnDelete();
            $table->timestamp('clicked_at');
            $table->char('ip_hash', 16)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->index(['link_id', 'clicked_at']);
        });

        Schema::create('newsletter_bounces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->nullable()->constrained('newsletter_deliveries')->nullOnDelete();
            $table->foreignId('subscriber_id')->nullable()->constrained('subscribers')->nullOnDelete();
            $table->string('type', 16);
            $table->string('provider', 32);
            $table->string('provider_event_id', 255)->nullable();
            $table->string('smtp_code', 10)->nullable();
            $table->string('reason', 500)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable();
            $table->unique(['provider', 'provider_event_id']);
        });

        Schema::create('newsletter_subscriber_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            $table->string('type', 40);
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at');
            $table->index(['subscriber_id', 'created_at']);
        });

        Schema::create('newsletter_suppressions', function (Blueprint $table) {
            $table->id();
            $table->char('email_hash', 64)->unique();
            $table->string('reason', 64);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('push_subscriptions') && ! Schema::hasColumn('push_subscriptions', 'subscriber_id')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('subscriber_id')->nullable()->after('user_id')->index();
            });
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('newsletter_mailer', 20)->nullable();
            $table->string('newsletter_from_address')->nullable();
            $table->string('newsletter_from_name', 120)->nullable();
            $table->string('newsletter_reply_to')->nullable();
            $table->text('newsletter_ses_key')->nullable();
            $table->text('newsletter_ses_secret')->nullable();
            $table->string('newsletter_ses_region', 32)->nullable();
            $table->string('newsletter_mailgun_domain')->nullable();
            $table->text('newsletter_mailgun_secret')->nullable();
            $table->string('newsletter_mailgun_endpoint')->nullable();
            $table->text('newsletter_postmark_token')->nullable();
            $table->string('newsletter_smtp_host')->nullable();
            $table->unsignedSmallInteger('newsletter_smtp_port')->nullable();
            $table->string('newsletter_smtp_username')->nullable();
            $table->text('newsletter_smtp_password')->nullable();
            $table->string('newsletter_smtp_encryption', 8)->nullable();
            $table->unsignedSmallInteger('newsletter_rate_per_minute')->default(60);
            $table->unsignedInteger('newsletter_rate_per_day')->nullable();
            $table->text('newsletter_webhook_secret')->nullable();
            $table->string('newsletter_sms_provider', 20)->nullable();
            $table->text('newsletter_sms_token')->nullable();
            $table->string('newsletter_sms_sender', 20)->nullable();
            $table->string('newsletter_turnstile_site_key')->nullable();
            $table->text('newsletter_turnstile_secret')->nullable();
            $table->unsignedSmallInteger('newsletter_doi_ttl_days')->default(7);
            $table->unsignedSmallInteger('newsletter_retention_days')->default(730);
            $table->boolean('newsletter_track_opens')->default(true);
            $table->boolean('newsletter_track_clicks')->default(true);
            $table->boolean('newsletter_webpush_enabled')->default(false);
            $table->boolean('newsletter_szo_sync')->default(false);
            $table->string('newsletter_szo_form', 120)->nullable();
            $table->boolean('newsletter_crm_sync')->default(false);
            $table->string('newsletter_crm_webhook_url', 500)->nullable();
            $table->text('newsletter_crm_webhook_secret')->nullable();
            $table->boolean('newsletter_require_approval')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'newsletter_mailer', 'newsletter_from_address', 'newsletter_from_name', 'newsletter_reply_to',
                'newsletter_ses_key', 'newsletter_ses_secret', 'newsletter_ses_region',
                'newsletter_mailgun_domain', 'newsletter_mailgun_secret', 'newsletter_mailgun_endpoint',
                'newsletter_postmark_token', 'newsletter_smtp_host', 'newsletter_smtp_port', 'newsletter_smtp_username',
                'newsletter_smtp_password', 'newsletter_smtp_encryption', 'newsletter_rate_per_minute', 'newsletter_rate_per_day',
                'newsletter_webhook_secret', 'newsletter_sms_provider', 'newsletter_sms_token', 'newsletter_sms_sender',
                'newsletter_turnstile_site_key', 'newsletter_turnstile_secret', 'newsletter_doi_ttl_days', 'newsletter_retention_days',
                'newsletter_track_opens', 'newsletter_track_clicks', 'newsletter_webpush_enabled', 'newsletter_szo_sync', 'newsletter_szo_form',
                'newsletter_crm_sync', 'newsletter_crm_webhook_url', 'newsletter_crm_webhook_secret', 'newsletter_require_approval',
            ]);
        });

        if (Schema::hasColumn('push_subscriptions', 'subscriber_id')) {
            Schema::table('push_subscriptions', fn (Blueprint $t) => $t->dropColumn('subscriber_id'));
        }

        foreach ([
            'newsletter_suppressions', 'newsletter_subscriber_events', 'newsletter_bounces', 'newsletter_clicks', 'newsletter_opens',
            'newsletter_deliveries', 'newsletter_campaign_links', 'newsletter_campaigns', 'newsletter_templates', 'newsletter_forms',
            'newsletter_consents', 'newsletter_segments', 'newsletter_list_subscriber', 'newsletter_lists',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'site_id', 'uuid', 'email_hash', 'phone', 'status', 'channels', 'tags', 'locale', 'timezone', 'source',
                'confirmation_sent_at', 'unsubscribed_at', 'unsubscribe_reason', 'unsubscribe_campaign_id', 'bounced_at', 'complained_at',
                'last_sent_at', 'last_open_at', 'last_click_at', 'engagement_score', 'anonymized_at',
                'szo_contact_id', 'szo_synced_at', 'szo_error', 'crm_synced_at', 'crm_error',
            ]);
        });
    }
};
