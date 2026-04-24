<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $colors = [
            ['key' => 'primary_color', 'value' => '#c8102e', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu chủ đạo', 'is_public' => true],
            ['key' => 'primary_color_hover', 'value' => '#a50d25', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu chủ đạo (hover)', 'is_public' => true],
            ['key' => 'secondary_color', 'value' => '#b8976a', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu phụ (accent)', 'is_public' => true],
            ['key' => 'background_color', 'value' => '#f5f0e8', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu nền trang', 'is_public' => true],
            ['key' => 'text_color', 'value' => '#1a1a1a', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu chữ chính', 'is_public' => true],
            ['key' => 'header_bg_color', 'value' => '#ffffff', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu nền header', 'is_public' => true],
            ['key' => 'footer_bg_color', 'value' => '#f5f0e8', 'group' => 'appearance', 'type' => 'color', 'label' => 'Màu nền footer', 'is_public' => true],
        ];

        foreach ($colors as $c) {
            Setting::updateOrCreate(['key' => $c['key']], $c);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'primary_color', 'primary_color_hover', 'secondary_color',
            'background_color', 'text_color', 'header_bg_color', 'footer_bg_color',
        ])->delete();
    }
};
