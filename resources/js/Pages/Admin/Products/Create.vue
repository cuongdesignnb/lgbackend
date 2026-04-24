<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichEditor from '@/Components/RichEditor.vue';
import MediaPicker from '@/Components/MediaPicker.vue';

const props = defineProps({
    categories: Array,
    brands: Array,
});

const form = useForm({
    name: '', slug: '', sku: '', category_id: '', brand_id: '',
    description: '', short_description: '', price: '', sale_price: '', stock_quantity: 0,
    is_active: true, is_featured: false, warranty_months: 12,
    meta_title: '', meta_description: '',
    thumbnail: '',
    gallery: [],
    specifications_text: '',
});

function genSlug() {
    form.slug = form.name.toLowerCase().normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'd')
        .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

function submit() { form.post('/admin/products'); }
</script>
<template>
<AdminLayout title="Thêm sản phẩm">
    <div class="max-w-5xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-slate-200">Thêm sản phẩm mới</h3>
            <Link href="/admin/products" class="text-sm text-slate-400 hover:text-slate-300">← Quay lại</Link>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Thông tin cơ bản -->
            <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-6 space-y-4">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Thông tin cơ bản</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Tên sản phẩm *</label>
                        <input v-model="form.name" @blur="!form.slug && genSlug()" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50">
                        <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Slug *</label>
                        <input v-model="form.slug" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50">
                        <div v-if="form.errors.slug" class="text-red-400 text-xs mt-1">{{ form.errors.slug }}</div>
                    </div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">SKU *</label>
                        <input v-model="form.sku" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50">
                        <div v-if="form.errors.sku" class="text-red-400 text-xs mt-1">{{ form.errors.sku }}</div>
                    </div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Trạng thái</label>
                        <div class="flex items-center gap-4 mt-2">
                            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" class="rounded border-slate-700/50 text-cyan-500"> Đang bán</label>
                            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_featured" type="checkbox" class="rounded border-slate-700/50 text-cyan-500"> Nổi bật</label>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Danh mục *</label>
                        <select v-model="form.category_id" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"><option value="">Chọn...</option><option v-for="c in categories" :value="c.id">{{ c.name }}</option></select>
                        <div v-if="form.errors.category_id" class="text-red-400 text-xs mt-1">{{ form.errors.category_id }}</div>
                    </div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Thương hiệu</label>
                        <select v-model="form.brand_id" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"><option value="">Chọn...</option><option v-for="b in brands" :value="b.id">{{ b.name }}</option></select>
                    </div>
                </div>
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Mô tả ngắn</label><textarea v-model="form.short_description" rows="2" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"></textarea></div>
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Mô tả chi tiết</label><RichEditor v-model="form.description" placeholder="Nhập mô tả chi tiết sản phẩm..." /></div>
            </div>

            <!-- Hình ảnh -->
            <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-6 space-y-5">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Hình ảnh sản phẩm
                </h4>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Ảnh đại diện (Thumbnail) *</label>
                    <MediaPicker v-model="form.thumbnail" label="Chọn ảnh đại diện" />
                    <div v-if="form.errors.thumbnail" class="text-red-400 text-xs mt-1">{{ form.errors.thumbnail }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Thư viện ảnh sản phẩm</label>
                    <MediaPicker v-model="form.gallery" :multiple="true" label="Thêm ảnh vào thư viện" />
                    <div v-if="form.errors.gallery" class="text-red-400 text-xs mt-1">{{ form.errors.gallery }}</div>
                </div>
            </div>

            <!-- Giá & Kho -->
            <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-6 space-y-4">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Giá & Kho</h4>
                <div class="grid grid-cols-4 gap-4">
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Giá gốc *</label><input v-model="form.price" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"><div v-if="form.errors.price" class="text-red-400 text-xs mt-1">{{ form.errors.price }}</div></div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Giá sale</label><input v-model="form.sale_price" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Tồn kho *</label><input v-model="form.stock_quantity" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-300 mb-1">Bảo hành (tháng)</label><input v-model="form.warranty_months" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"></div>
                </div>
            </div>

            <!-- Thông số kỹ thuật -->
            <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-6 space-y-4">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Thông số kỹ thuật
                </h4>
                <p class="text-xs text-slate-400">Mỗi dòng là một thông số, theo định dạng: <strong>Tên: Giá trị</strong></p>
                <textarea v-model="form.specifications_text" rows="10"
                    placeholder="CPU: Intel Core i7-12700K&#10;RAM: 16GB DDR5&#10;SSD: 512GB NVMe&#10;Card đồ họa: RTX 4060 8GB&#10;Màn hình: 15.6 inch FHD IPS"
                    class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-cyan-500/50"></textarea>
            </div>



            <!-- SEO -->
            <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-6 space-y-4">
                <h4 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">SEO</h4>
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Meta title</label><input v-model="form.meta_title" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Meta description</label><textarea v-model="form.meta_description" rows="2" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm"></textarea></div>
            </div>

            <div class="flex justify-end gap-3">
                <Link href="/admin/products" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</Link>
                <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium disabled:opacity-50">
                    <span v-if="form.processing">Đang tạo...</span>
                    <span v-else>Tạo sản phẩm</span>
                </button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
