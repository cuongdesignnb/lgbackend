<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove legacy PC-builder tables & columns for LG Tech e-commerce.
     */
    public function up(): void
    {
        // Drop PC-builder specific tables
        Schema::dropIfExists('saved_builds');
        Schema::dropIfExists('power_requirements');
        Schema::dropIfExists('compatibility_rules');
        Schema::dropIfExists('component_supported_values');
        Schema::dropIfExists('component_types');

        // Remove component_type_id from products
        if (Schema::hasColumn('products', 'component_type_id')) {
            // Drop FK and index via raw SQL (safe if they don't exist)
            try { DB::statement('ALTER TABLE `products` DROP FOREIGN KEY `products_component_type_id_foreign`'); } catch (\Exception $e) {}
            try { DB::statement('ALTER TABLE `products` DROP INDEX `products_component_type_id_foreign`'); } catch (\Exception $e) {}
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('component_type_id');
            });
        }

        // Remove component_type_id from specification_keys
        if (Schema::hasColumn('specification_keys', 'component_type_id')) {
            try { DB::statement('ALTER TABLE `specification_keys` DROP FOREIGN KEY `specification_keys_component_type_id_foreign`'); } catch (\Exception $e) {}
            try { DB::statement('ALTER TABLE `specification_keys` DROP INDEX `specification_keys_component_type_id_key_unique`'); } catch (\Exception $e) {}
            Schema::table('specification_keys', function (Blueprint $table) {
                $table->dropColumn('component_type_id');
            });
        }

        // Remove component_type_id from categories
        if (Schema::hasColumn('categories', 'component_type_id')) {
            try { DB::statement('ALTER TABLE `categories` DROP FOREIGN KEY `categories_component_type_id_foreign`'); } catch (\Exception $e) {}
            try { DB::statement('ALTER TABLE `categories` DROP INDEX `categories_component_type_id_foreign`'); } catch (\Exception $e) {}
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('component_type_id');
            });
        }

        // Create filter tables if not exist (from 2026_04_22)
        if (!Schema::hasTable('filters')) {
            Schema::create('filters', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->enum('type', ['checkbox', 'radio', 'range'])->default('checkbox');
                $table->string('match_field')->default('specifications_text');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('filter_values')) {
            Schema::create('filter_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('filter_id')->constrained()->cascadeOnDelete();
                $table->string('label');
                $table->string('slug');
                $table->string('match_value')->nullable();
                $table->decimal('price_min', 15, 0)->nullable();
                $table->decimal('price_max', 15, 0)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['filter_id', 'slug']);
            });
        }

        if (!Schema::hasTable('category_filter')) {
            Schema::create('category_filter', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->foreignId('filter_id')->constrained()->cascadeOnDelete();
                $table->integer('sort_order')->default(0);
                $table->unique(['category_id', 'filter_id']);
            });
        }

        // Create AI article tables if not exist
        if (!Schema::hasTable('ai_topics')) {
            Schema::create('ai_topics', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('prompt')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('post_category_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_articles')) {
            Schema::create('ai_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_topic_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('body');
                $table->string('featured_image')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('status')->default('draft');
                $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Not reversible — PC builder tables should not come back
    }
};
