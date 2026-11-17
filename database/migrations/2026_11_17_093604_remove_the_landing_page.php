<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The public landing page is gone: the address of the application opens
     * the sign-in page. Its menu (Master Data > Landing Page) goes too, with
     * its grants (menu_role and menu_role_permission cascade, a home page on
     * it becomes empty), and the table of its texts.
     */
    public function up(): void
    {
        if (DB::table('menu')->where('route', 'master-data.landing-content.index')->delete() > 0) {
            Cache::forget('rbac_menu_route_key_map');
        }

        Schema::dropIfExists('landing_contents');
    }

    /**
     * Reverse the migrations. The table comes back empty, as it was until
     * someone saved the page (the page showed its default texts); the menu
     * comes back with the MenuSeeder of an earlier version only.
     */
    public function down(): void
    {
        Schema::create('landing_contents', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title');
            $table->text('hero_intro')->comment('May contain {survey} for the title of the open survey');
            $table->string('hero_image_path')->nullable()->comment('Hero image on the public disk');
            $table->string('hero_image_alt')->nullable();
            $table->string('feedback_title');
            $table->text('feedback_body');
            $table->string('feedback_note')->nullable();
            $table->string('closing_title');
            $table->string('closing_note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
