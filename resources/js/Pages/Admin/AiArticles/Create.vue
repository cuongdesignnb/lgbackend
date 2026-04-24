<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    categories: Array,
    hasKeys: Object,
});

const form = useForm({
    name: '',
    keywords: '',
    ai_provider: 'chatgpt',
    default_category_id: '',
    schedule_at: '',
    auto_publish: false,
});

const keywordCount = computed(() => {
    return form.keywords.split('\n').filter(k => k.trim()).length;
});

function submit() {
    form.post('/admin/ai-articles', {
        preserveScroll: true,
    });
}
</script>

<template>
<AdminLayout title="Tạo batch AI bài viết">
    <div class="max-w-3xl">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link href="/admin/ai-articles" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div>
                <h3 class="text-lg font-bold text-slate-200">Tạo batch AI bài viết</h3>
                <p class="text-xs text-slate-500 mt-0.5">Nhập danh sách từ khóa, AI sẽ tự động tạo bài viết cho từng từ khóa</p>
            </div>
        </div>

        <!-- API Key warnings -->
        <div v-if="!hasKeys?.chatgpt && !hasKeys?.gemini" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
            <p class="text-sm text-red-400 font-medium">Chưa cấu hình API Key</p>
            <p class="text-xs text-red-400/70 mt-1">Vui lòng vào <Link href="/admin/settings" class="text-cyan-400 underline">Cài đặt</Link> để nhập ChatGPT hoặc Gemini API Key trước khi sử dụng.</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
            <div class="bg-slate-900 rounded-xl border border-slate-800/60 p-6 space-y-5">
                <!-- Batch name -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Tên batch</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                        placeholder="VD: Bài viết laptop gaming tháng 4"
                    />
                    <p v-if="form.errors.name" class="text-xs text-red-400 mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- Keywords -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-sm font-medium text-slate-300">Danh sách từ khóa</label>
                        <span class="text-xs text-slate-500 tabular-nums">{{ keywordCount }} từ khóa</span>
                    </div>
                    <textarea
                        v-model="form.keywords"
                        rows="8"
                        class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50 focus:border-cyan-500/50 font-mono leading-relaxed"
                        placeholder="Mỗi dòng là một từ khóa, VD:
Top 10 laptop gaming tốt nhất 2025
Hướng dẫn build PC 20 triệu
So sánh RTX 4070 vs RTX 4060 Ti
Cách chọn màn hình cho đồ họa"
                    ></textarea>
                    <p v-if="form.errors.keywords" class="text-xs text-red-400 mt-1">{{ form.errors.keywords }}</p>
                </div>

                <!-- Provider & Category -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">AI Provider</label>
                        <select
                            v-model="form.ai_provider"
                            class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                        >
                            <option value="chatgpt" :disabled="!hasKeys?.chatgpt">ChatGPT {{ !hasKeys?.chatgpt ? '(chưa có key)' : '' }}</option>
                            <option value="gemini" :disabled="!hasKeys?.gemini">Gemini {{ !hasKeys?.gemini ? '(chưa có key)' : '' }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Danh mục mặc định</label>
                        <select
                            v-model="form.default_category_id"
                            class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                        >
                            <option value="">-- Không chọn --</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Schedule & Auto-publish -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Lịch hẹn (để trống = chạy thủ công)</label>
                        <input
                            v-model="form.schedule_at"
                            type="datetime-local"
                            class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                        />
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" v-model="form.auto_publish" class="sr-only peer" />
                                <div class="w-11 h-6 bg-slate-700 rounded-full peer-checked:bg-cyan-500 transition-colors"></div>
                                <div class="absolute left-1 top-1 w-4 h-4 bg-slate-900 rounded-full shadow peer-checked:translate-x-5 transition-transform"></div>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-300">Tự động đăng bài</span>
                                <p class="text-xs text-slate-500">Nếu tắt, bài sẽ lưu nháp để admin duyệt</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/ai-articles" class="px-5 py-2.5 text-sm font-medium text-slate-400 hover:text-slate-200 border border-slate-700 rounded-lg hover:bg-slate-800 transition-colors">
                    Hủy
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing || !form.name || !form.keywords"
                    class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-sm font-semibold rounded-lg hover:from-cyan-600 hover:to-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-cyan-500/20"
                >
                    <span v-if="form.processing" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Đang tạo...
                    </span>
                    <span v-else>Tạo batch ({{ keywordCount }} bài)</span>
                </button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
