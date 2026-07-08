<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns guest_blog_requests from a pitch-only record into an end-to-end guest
 * submission: the actual article (title/content/excerpt + submission locale),
 * a tokenized "submit your article" link (submit_token / submitted_at), and
 * audit/idempotency links to what approval produced (created_post_id, the draft
 * post; created_user_id, the guest author). The cover image is a MediaLibrary
 * collection on the model, so no column is needed for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_blog_requests', function (Blueprint $table) {
            $table->string('title')->nullable()->after('proposed_topic');
            $table->longText('content')->nullable()->after('title');
            $table->text('excerpt')->nullable()->after('content');
            $table->string('locale', 12)->nullable()->after('excerpt');

            $table->string('submit_token')->nullable()->unique()->after('sample_url');
            $table->timestamp('submitted_at')->nullable()->after('submit_token');

            $table->foreignId('created_post_id')->nullable()->after('submitted_at')
                ->constrained('blog_posts')->nullOnDelete();
            $table->foreignId('created_user_id')->nullable()->after('created_post_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guest_blog_requests', function (Blueprint $table) {
            $table->dropForeign(['created_post_id']);
            $table->dropForeign(['created_user_id']);
            $table->dropColumn([
                'title', 'content', 'excerpt', 'locale',
                'submit_token', 'submitted_at', 'created_post_id', 'created_user_id',
            ]);
        });
    }
};
