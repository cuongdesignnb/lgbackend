<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ─── General ────────────────────────────────────────
            [
                'key' => 'site_name',
                'value' => 'Lgtech',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Tên website',
                'is_public' => true,
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Bán PC, Laptop & Linh kiện máy tính',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Khẩu hiệu (Slogan)',
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => 'Chuyên cung cấp PC Gaming, Laptop, linh kiện máy tính chính hãng. Xây dựng cấu hình PC thông minh với công cụ kiểm tra tương thích.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Mô tả website',
                'is_public' => true,
            ],
            [
                'key' => 'site_logo',
                'value' => '',
                'group' => 'general',
                'type' => 'image',
                'label' => 'Logo website',
                'is_public' => true,
            ],
            [
                'key' => 'site_favicon',
                'value' => '',
                'group' => 'general',
                'type' => 'image',
                'label' => 'Favicon',
                'is_public' => true,
            ],
            [
                'key' => 'currency',
                'value' => 'VND',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Đơn vị tiền tệ',
                'is_public' => true,
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Ho_Chi_Minh',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Múi giờ',
                'is_public' => false,
            ],

            // ─── Contact ────────────────────────────────────────
            [
                'key' => 'contact_phone',
                'value' => '1900 1234',
                'group' => 'contact',
                'type' => 'text',
                'label' => 'Số điện thoại',
                'is_public' => true,
            ],
            [
                'key' => 'contact_hotline',
                'value' => '0909 123 456',
                'group' => 'contact',
                'type' => 'text',
                'label' => 'Hotline',
                'is_public' => true,
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@pcshop.vn',
                'group' => 'contact',
                'type' => 'text',
                'label' => 'Email',
                'is_public' => true,
            ],
            [
                'key' => 'contact_address',
                'value' => '123 Nguyễn Văn Linh, Quận 7, TP.HCM',
                'group' => 'contact',
                'type' => 'textarea',
                'label' => 'Địa chỉ',
                'is_public' => true,
            ],
            [
                'key' => 'business_hours',
                'value' => 'T2 - CN: 8:00 - 21:00',
                'group' => 'contact',
                'type' => 'text',
                'label' => 'Giờ làm việc',
                'is_public' => true,
            ],
            [
                'key' => 'contact_map',
                'value' => '',
                'group' => 'contact',
                'type' => 'textarea',
                'label' => 'Google Maps embed URL',
                'is_public' => true,
            ],

            // ─── Social ─────────────────────────────────────────
            [
                'key' => 'social_facebook',
                'value' => 'https://facebook.com/pcshop',
                'group' => 'social',
                'type' => 'text',
                'label' => 'Facebook',
                'is_public' => true,
            ],
            [
                'key' => 'social_youtube',
                'value' => 'https://youtube.com/@pcshop',
                'group' => 'social',
                'type' => 'text',
                'label' => 'YouTube',
                'is_public' => true,
            ],
            [
                'key' => 'social_tiktok',
                'value' => '',
                'group' => 'social',
                'type' => 'text',
                'label' => 'TikTok',
                'is_public' => true,
            ],
            [
                'key' => 'social_zalo',
                'value' => '',
                'group' => 'social',
                'type' => 'text',
                'label' => 'Zalo OA',
                'is_public' => true,
            ],
            [
                'key' => 'social_instagram',
                'value' => '',
                'group' => 'social',
                'type' => 'text',
                'label' => 'Instagram',
                'is_public' => true,
            ],

            // ─── SEO ────────────────────────────────────────────
            [
                'key' => 'seo_title',
                'value' => 'Lgtech - Bán PC, Laptop & Linh kiện máy tính chính hãng',
                'group' => 'seo',
                'type' => 'text',
                'label' => 'Tiêu đề SEO mặc định',
                'is_public' => true,
            ],
            [
                'key' => 'seo_description',
                'value' => 'Lgtech - Chuyên cung cấp PC Gaming, Laptop, linh kiện máy tính chính hãng giá tốt. Build PC online, kiểm tra tương thích tự động.',
                'group' => 'seo',
                'type' => 'textarea',
                'label' => 'Mô tả SEO mặc định',
                'is_public' => true,
            ],
            [
                'key' => 'seo_keywords',
                'value' => 'pc gaming, laptop, linh kiện máy tính, build pc, mua pc, Lgtech',
                'group' => 'seo',
                'type' => 'text',
                'label' => 'Từ khóa SEO mặc định',
                'is_public' => true,
            ],
            [
                'key' => 'seo_og_image',
                'value' => '',
                'group' => 'seo',
                'type' => 'image',
                'label' => 'Ảnh chia sẻ mặc định (OG Image)',
                'is_public' => true,
            ],
            [
                'key' => 'google_analytics_id',
                'value' => '',
                'group' => 'seo',
                'type' => 'text',
                'label' => 'Google Analytics ID (UA/G-)',
                'is_public' => false,
            ],
            [
                'key' => 'google_tag_manager_id',
                'value' => '',
                'group' => 'seo',
                'type' => 'text',
                'label' => 'Google Tag Manager ID',
                'is_public' => false,
            ],
            [
                'key' => 'facebook_pixel_id',
                'value' => '',
                'group' => 'seo',
                'type' => 'text',
                'label' => 'Facebook Pixel ID',
                'is_public' => false,
            ],

            // ─── Homepage ───────────────────────────────────────
            [
                'key' => 'homepage_hero_autoplay',
                'value' => '1',
                'group' => 'homepage',
                'type' => 'boolean',
                'label' => 'Tự động chuyển banner trang chủ',
                'is_public' => true,
            ],
            [
                'key' => 'homepage_hero_interval',
                'value' => '5000',
                'group' => 'homepage',
                'type' => 'number',
                'label' => 'Thời gian chuyển slide (mili giây)',
                'is_public' => true,
            ],
            [
                'key' => 'homepage_products_per_section',
                'value' => '8',
                'group' => 'homepage',
                'type' => 'number',
                'label' => 'Số sản phẩm mỗi mục trên trang chủ',
                'is_public' => true,
            ],
            [
                'key' => 'homepage_show_brands',
                'value' => '1',
                'group' => 'homepage',
                'type' => 'boolean',
                'label' => 'Hiện thanh thương hiệu trên trang chủ',
                'is_public' => true,
            ],
            [
                'key' => 'homepage_show_posts',
                'value' => '1',
                'group' => 'homepage',
                'type' => 'boolean',
                'label' => 'Hiện bài viết nổi bật',
                'is_public' => true,
            ],

            // ─── Payment ────────────────────────────────────────
            [
                'key' => 'payment_bank_name',
                'value' => 'MB Bank',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Tên ngân hàng',
                'is_public' => true,
            ],
            [
                'key' => 'payment_bank_account',
                'value' => '0123456789',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Số tài khoản',
                'is_public' => true,
            ],
            [
                'key' => 'payment_bank_holder',
                'value' => 'CONG TY Lgtech',
                'group' => 'payment',
                'type' => 'text',
                'label' => 'Tên chủ tài khoản',
                'is_public' => true,
            ],
            [
                'key' => 'payment_cod_enabled',
                'value' => '1',
                'group' => 'payment',
                'type' => 'boolean',
                'label' => 'Cho phép thanh toán khi nhận hàng (COD)',
                'is_public' => true,
            ],

            // ─── Shipping ───────────────────────────────────────
            [
                'key' => 'shipping_free_threshold',
                'value' => '2000000',
                'group' => 'shipping',
                'type' => 'number',
                'label' => 'Miễn phí ship từ (VNĐ)',
                'is_public' => true,
            ],
            [
                'key' => 'shipping_default_fee',
                'value' => '30000',
                'group' => 'shipping',
                'type' => 'number',
                'label' => 'Phí ship mặc định (VNĐ)',
                'is_public' => true,
            ],
            [
                'key' => 'shipping_express_fee',
                'value' => '50000',
                'group' => 'shipping',
                'type' => 'number',
                'label' => 'Phí ship nhanh (VNĐ)',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Seeded ' . count($settings) . ' settings');
    }
}
