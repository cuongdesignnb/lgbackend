<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const form = useForm({
    title: '',
    description: '',
    cover_image: '',
    file_url: '',
    catalogue_file: null,
    sort_order: 0,
    is_active: true,
});

const filePreview = ref(null);

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.catalogue_file = file;
    filePreview.value = file.name;
    form.file_url = '';
}

function submit() {
    form.post('/admin/catalogues', { forceFormData: true });
}
</script>
<template>
<AdminLayout title="Thêm catalogue">
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-slate-200">Thêm catalogue</h3>
            <Link href="/admin/catalogues" class="text-sm text-slate-400 hover:text-slate-300">← Quay lại</Link>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
            <!-- File Upload -->
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6">
                <h4 class="text-sm font-semibold text-slate-300 mb-4">File tài liệu *</h4>
                <div v-if="!filePreview && !form.file_url" class="border-2 border-dashed border-slate-700/50 rounded-lg p-8 text-center hover:border-cyan-500/50 transition-colors">
                    <svg class="w-12 h-12 mx-auto text-slate-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <label class="cursor-pointer"><span class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium inline-block">Chọn file</span><input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" @change="onFileChange" class="hidden" /></label>
                    <p class="text-slate-500 text-xs mt-3">PDF, DOC, XLS, ZIP — tối đa 50MB</p>
                    <div class="mt-3"><span class="text-slate-600 text-xs">hoặc nhập URL:</span></div>
                    <input v-model="form.file_url" placeholder="https://..." class="mt-2 w-full max-w-md mx-auto border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-300 text-center" />
                </div>
                <div v-else class="flex items-center gap-3 p-4 bg-slate-800/40 rounded-lg">
                    <div class="w-10 h-10 bg-emerald-500/10 rounded-lg flex items-center justify-center"><svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <div class="flex-1"><p class="text-sm text-slate-200 font-medium">{{ filePreview || form.file_url }}</p></div>
                    <button type="button" @click="form.catalogue_file = null; filePreview = null; form.file_url = ''" class="text-red-400 hover:text-red-300 text-sm">Xóa</button>
                </div>
                <div v-if="form.errors.file_url || form.errors.catalogue_file" class="text-red-400 text-xs mt-2">{{ form.errors.file_url || form.errors.catalogue_file }}</div>
            </div>

            <!-- Info -->
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6 space-y-4">
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Tiêu đề *</label><input v-model="form.title" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" /><div v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</div></div>
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Mô tả</label><textarea v-model="form.description" rows="3" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200"></textarea></div>
                <div><label class="block text-sm font-medium text-slate-300 mb-1">Ảnh bìa</label><input v-model="form.cover_image" placeholder="URL ảnh bìa" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" /><div v-if="form.cover_image" class="mt-2"><img :src="form.cover_image" class="h-24 rounded border border-slate-700/50 object-cover" /></div></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-slate-300 mb-1">Thứ tự</label><input v-model="form.sort_order" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" /></div></div>
                <label class="flex items-center gap-2 text-sm text-slate-300"><input v-model="form.is_active" type="checkbox" class="rounded border-slate-700/50 text-cyan-500 bg-slate-800/50" /> Hiện catalogue</label>
            </div>

            <div class="flex justify-end gap-3">
                <Link href="/admin/catalogues" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</Link>
                <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium disabled:opacity-50">{{ form.processing ? 'Đang tạo...' : 'Tạo catalogue' }}</button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
