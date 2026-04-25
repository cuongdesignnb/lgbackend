<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaPicker from '@/Components/MediaPicker.vue';

const props = defineProps({ catalogue: Object });
const form = useForm({
    title: props.catalogue.title || '',
    description: props.catalogue.description || '',
    cover_image: props.catalogue.cover_image || '',
    file_url: props.catalogue.file_url || '',
    catalogue_file: null,
    sort_order: props.catalogue.sort_order ?? 0,
    is_active: props.catalogue.is_active ?? true,
});

const filePreview = ref(props.catalogue.file_name || (props.catalogue.file_url ? props.catalogue.file_url : null));

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.catalogue_file = file;
    filePreview.value = file.name;
    form.file_url = '';
}

function submit() {
    form.post(`/admin/catalogues/${props.catalogue.id}`, { forceFormData: true, _method: 'put' });
}
</script>
<template>
<AdminLayout title="Sửa tài liệu">
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-slate-200">Sửa tài liệu kỹ thuật</h3>
            <Link href="/admin/catalogues" class="text-sm text-slate-400 hover:text-slate-300">← Quay lại</Link>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6">
                <h4 class="text-sm font-semibold text-slate-300 mb-4">File tài liệu</h4>
                <div v-if="filePreview || form.file_url" class="flex items-center gap-3 p-4 bg-slate-800/40 rounded-lg mb-3">
                    <div class="w-10 h-10 bg-emerald-500/10 rounded-lg flex items-center justify-center"><svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <div class="flex-1"><p class="text-sm text-slate-200 font-medium">{{ filePreview || form.file_url }}</p></div>
                </div>
                <label class="cursor-pointer px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm border border-slate-700/50 inline-block">
                    Đổi file <input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" @change="onFileChange" class="hidden" />
                </label>
            </div>
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Tiêu đề <span class="text-red-400">*</span></label>
                    <input v-model="form.title" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Mô tả</label>
                    <textarea v-model="form.description" rows="3" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Ảnh bìa</label>
                    <div class="flex items-start gap-4">
                        <div v-if="form.cover_image" class="w-24 h-16 rounded-lg border border-slate-700/50 bg-slate-800/40 p-1.5 flex items-center justify-center flex-shrink-0">
                            <img :src="form.cover_image" class="max-w-full max-h-full object-contain" />
                        </div>
                        <div class="flex-1 space-y-2">
                            <input v-model="form.cover_image" placeholder="URL ảnh bìa hoặc chọn từ thư viện..." class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                            <div class="flex items-center gap-2">
                                <MediaPicker :modelValue="form.cover_image" @update:modelValue="form.cover_image = $event" label="" />
                                <button v-if="form.cover_image" type="button" @click="form.cover_image = ''" class="text-xs text-red-400 hover:text-red-300">Xóa</button>
                            </div>
                            <p class="text-xs text-amber-400/70 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Khuyến nghị: 400×300px hoặc tỉ lệ 4:3 (JPG/PNG/WebP)
                            </p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Thứ tự hiển thị</label>
                        <input v-model="form.sort_order" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300"><input v-model="form.is_active" type="checkbox" class="rounded border-slate-700/50 text-cyan-500 bg-slate-800/50" /> Hiện tài liệu trên trang web</label>
            </div>
            <div class="flex justify-end gap-3">
                <Link href="/admin/catalogues" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</Link>
                <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium disabled:opacity-50">{{ form.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}</button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
