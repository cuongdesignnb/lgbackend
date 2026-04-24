<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const form = useForm({
    title: '',
    description: '',
    thumbnail: '',
    video_url: '',
    embed_code: '',
    source: 'embed',
    sort_order: 0,
    is_active: true,
    is_featured: false,
});

function submit() {
    form.post('/admin/videos');
}
</script>
<template>
<AdminLayout title="Thêm video">
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-slate-200">Thêm video</h3>
            <Link href="/admin/videos" class="text-sm text-slate-400 hover:text-slate-300">← Quay lại</Link>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Source toggle -->
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6">
                <h4 class="text-sm font-semibold text-slate-300 mb-4">Nguồn video</h4>
                <div class="flex gap-3">
                    <button type="button" @click="form.source = 'embed'"
                        :class="form.source === 'embed' ? 'bg-cyan-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Mã nhúng (YouTube/Vimeo)
                    </button>
                    <button type="button" @click="form.source = 'upload'"
                        :class="form.source === 'upload' ? 'bg-cyan-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        URL / Thư viện Media
                    </button>
                </div>
            </div>

            <!-- Embed code -->
            <div v-if="form.source === 'embed'" class="bg-slate-900 rounded-lg border border-slate-800/60 p-6">
                <h4 class="text-sm font-semibold text-slate-300 mb-3">Mã nhúng *</h4>
                <textarea v-model="form.embed_code" rows="5" placeholder='<iframe width="560" height="315" src="https://www.youtube.com/embed/..." ...></iframe>' class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200 font-mono"></textarea>
                <p class="text-xs text-slate-500 mt-2">Dán mã nhúng từ YouTube, Vimeo hoặc bất kỳ nền tảng nào</p>
                <div v-if="form.errors.embed_code" class="text-red-400 text-xs mt-1">{{ form.errors.embed_code }}</div>
            </div>

            <!-- Upload URL -->
            <div v-if="form.source === 'upload'" class="bg-slate-900 rounded-lg border border-slate-800/60 p-6">
                <h4 class="text-sm font-semibold text-slate-300 mb-3">URL video *</h4>
                <input v-model="form.video_url" placeholder="https://... hoặc /storage/media/video.mp4" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                <p class="text-xs text-slate-500 mt-2">Tải video lên thư viện Media, sau đó dán URL vào đây</p>
                <div v-if="form.errors.video_url" class="text-red-400 text-xs mt-1">{{ form.errors.video_url }}</div>
            </div>

            <!-- Info -->
            <div class="bg-slate-900 rounded-lg border border-slate-800/60 p-6 space-y-4">
                <h4 class="text-sm font-semibold text-slate-300 mb-2">Thông tin</h4>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Tiêu đề *</label>
                    <input v-model="form.title" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                    <div v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Mô tả</label>
                    <textarea v-model="form.description" rows="3" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Ảnh thumbnail</label>
                    <input v-model="form.thumbnail" placeholder="URL ảnh đại diện" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                    <div v-if="form.thumbnail" class="mt-2"><img :src="form.thumbnail" class="h-24 rounded border border-slate-700/50 object-cover" /></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Thứ tự</label>
                        <input v-model="form.sort_order" type="number" class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm bg-slate-800/50 text-slate-200" />
                    </div>
                </div>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-700/50 text-cyan-500 bg-slate-800/50" /> Hiện video
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input v-model="form.is_featured" type="checkbox" class="rounded border-slate-700/50 text-amber-500 bg-slate-800/50" /> Nổi bật
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <Link href="/admin/videos" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</Link>
                <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium disabled:opacity-50">
                    {{ form.processing ? 'Đang tạo...' : 'Tạo video' }}
                </button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
