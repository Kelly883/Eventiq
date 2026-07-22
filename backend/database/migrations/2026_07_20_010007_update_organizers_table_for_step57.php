<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->uuid('userId')->nullable()->after('user_id');
            $table->string('displayName')->nullable()->after('userId');
            $table->string('avatarUrl')->nullable()->after('business_name');
            $table->string('email')->nullable()->after('avatarUrl');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->json('socialLinks')->nullable()->after('website');
            $table->json('brandingColors')->nullable()->after('socialLinks');
            $table->boolean('isPublic')->default(true)->after('brandingColors');
            $table->boolean('emailPublic')->default(false)->after('isPublic');
            $table->boolean('phonePublic')->default(false)->after('emailPublic');
            $table->json('notificationPreferences')->nullable()->after('phonePublic');
            $table->integer('totalEventsCreated')->default(0)->after('notificationPreferences');
            $table->integer('totalTicketsSold')->default(0)->after('totalEventsCreated');
            $table->timestamp('deletedAt')->nullable()->after('updated_at');

            $table->index('userId');
            $table->index(['userId', 'isPublic']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropIndex(['organizers_userid_index']);
            $table->dropIndex(['organizers_userid_ispublic_index']);
            $table->dropIndex(['organizers_created_at_index']);

            $table->dropColumn([
                'userId',
                'displayName',
                'avatarUrl',
                'email',
                'phone',
                'website',
                'socialLinks',
                'brandingColors',
                'isPublic',
                'emailPublic',
                'phonePublic',
                'notificationPreferences',
                'totalEventsCreated',
                'totalTicketsSold',
                'deletedAt',
            ]);
        });
    }
};