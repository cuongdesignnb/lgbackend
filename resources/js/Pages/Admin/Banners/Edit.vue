<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ banner: Object });

const imagePreview = ref(null);

const form = useForm({
    _method: 'put',
    title: props.banner.title || '',
    description: props.banner.description || '',
    badge: props.banner.badge || '',
    image: props.banner.image || '',
    image_file: null,
    link: props.banner.link || '',
    position: props.banner.position || 'hero',
    sort_order: props.banner.sort_order ?? 0,
    is_active: props.banner.is_active ?? true,
    starts_at: props.banner.starts_at ? props.banner.starts_at.slice(0, 16) : '',
    ends_at: props.banner.ends_at ? props.banner.ends_at.slice(0, 16) : '',
    metadata: props.banner.metadata || { cta_label: '', cta_link: '' },
});

const previewUrl = computed(() => {
    if (imagePreview.value) return imagePreview.value;
    if (form.image) return form.image;
    return null;
});

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.image_file = file;
    imagePreview.value = URL.createObjectURL(file);
    form.image = '';
}

function removeImage() {
    form.image_file = null;
    form.image = '';
    imagePreview.value = null;
}

function submit() {
    form.post(`/admin/banners/${props.banner.id}`, {
        forceFormData: true,
    });
}

const positions = [
    { value: 'hero', label: 'Hero (Banner chính)' },
    { value: 'sidebar', label: 'Sidebar' },
    { value: 'footer', label: 'Footer' },
    { value: 'popup', label: 'Popup' },
    { value: 'category', label: 'Danh mục' },
];
</script>
<template>
<AdminLayout title="Sửa banner">
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-slate-200">Sửa banner</h3>
            <Link href="/admin/banners" class="text-sm text-slate-400 hover:text-slate-300">← Quay lại</Link>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Image Upload Section -->
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6">
                <h4 class="text-sm font-semibold text-slate-300 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Ảnh banner *
                </h4>
                <!-- Preview -->
                <div v-if="previewUrl" class="mb-4 relative group">
                    <img :src="previewUrl" class="w-full max-h-64 object-cover rounded-lg border border-slate-700/50" />
                    <button type="button" @click="removeImage"
                        class="absolute top-2 right-2 w-8 h-8 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div class="absolute bottom-2 left-2 bg-black/60 text-white text-xs px-2 py-1 rounded">
                        Kích thước khuyến nghị: 1200 × 420 px
                    </div>
                </div>
                <!-- Upload area (when no image) -->
                <div v-if="!previewUrl" class="border-2 border-dashed border-slate-700/50 rounded-lg p-8 text-center hover:border-cyan-500/50 transition-colors">
                    <svg class="w-12 h-12 mx-auto text-slate-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <label class="cursor-pointer">
                        <span class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium inline-block">Chọn ảnh từ máy</span>
                        <input type="file" accept="image/*" @change="onFileChange" class="hidden" />
                    </label>
                    <p class="text-slate-500 text-xs mt-3">JPG, PNG, WebP — tối đa 5MB</p>
                    <p class="text-slate-400 text-xs mt-1 font-medium">Kích thước khuyến nghị: 1200 × 420 px</p>
                    <div class="mt-3 flex items-center gap-2 justify-center">
                        <span class="text-slate-600 text-xs">hoặc</span>
                    </div>
                    <input v-model="form.image" placeholder="Nhập URL ảnh..." class="mt-2 w-full max-w-md mx-auto border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-300 text-center" />
                </div>
                <!-- Change image button when already has image -->
                <div v-if="previewUrl" class="mt-3 flex gap-3">
                    <label class="cursor-pointer px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm border border-slate-700/50">
                        Đổi ảnh
                        <input type="file" accept="image/*" @change="onFileChange" class="hidden" />
                    </label>
                </div>
                <div v-if="form.errors.image || form.errors.image_file" class="text-red-400 text-xs mt-2">{{ form.errors.image || form.errors.image_file }}</div>
            </div>

            <!-- Basic Info -->
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6 space-y-4">
                <h4 class="text-sm font-semibold text-slate-300 mb-2">Thông tin cơ bản</h4>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Tiêu đề *</label>
                    <input v-model="form.title" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200">
                    <div v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Mô tả ngắn</label>
                    <textarea v-model="form.description" rows="2" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" placeholder="Mô tả hiển thị trên banner"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Vị trí *</label>
                        <select v-model="form.position" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200">
                            <option v-for="p in positions" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Link khi click</label>
                        <input v-model="form.link" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" placeholder="/san-pham">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Thứ tự</label>
                        <input v-model="form.sort_order" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Bắt đầu hiển thị</label>
                        <input v-model="form.starts_at" type="datetime-local" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Kết thúc</label>
                        <input v-model="form.ends_at" type="datetime-local" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-slate-700/50 text-cyan-500 bg-slate-800/50"> Hiện banner
                </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <Link href="/admin/banners" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</Link>
                <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium disabled:opacity-50">
                    {{ form.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
                </button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
