<script setup>
import { ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ posts: Object, filters: Object, categories: Array });
const flash = usePage().props.flash;
const search = ref(props.filters?.search || '');
const filter = ref(props.filters?.status || '');

let timer;
watch([search, filter], () => { clearTimeout(timer); timer = setTimeout(() => router.get('/admin/posts', { search: search.value || undefined, status: filter.value || undefined }, { preserveState: true, replace: true }), 400); });

function destroy(id) { if (confirm('Xóa bài viết?')) router.delete(`/admin/posts/${id}`); }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('vi-VN') : '—'; }
function statusLabel(s) { return s === 'published' ? 'Đã đăng' : s === 'draft' ? 'Nháp' : 'Lưu trữ'; }
function statusClass(s) { return s === 'published' ? 'bg-green-100 text-emerald-400' : s === 'draft' ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-800/60 text-slate-300'; }
</script>
<template>
<AdminLayout title="Bài viết">
    <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ flash.success }}</div>
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-3">
            <input v-model="search" placeholder="Tìm bài viết..." class="border border-slate-700/50 rounded-lg px-3 py-2 text-sm w-48">
            <select v-model="filter" class="border border-slate-700/50 rounded-lg px-3 py-2 text-sm">
                <option value="">Tất cả</option><option value="published">Đã xuất bản</option><option value="draft">Nháp</option><option value="archived">Lưu trữ</option>
            </select>
        </div>
        <Link href="/admin/posts/create" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium">+ Viết bài</Link>
    </div>
    <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-800/40">
            <thead class="bg-slate-800/40"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Tiêu đề</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Danh mục</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-slate-400 uppercase">Trạng thái</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-slate-400 uppercase">Lượt xem</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Ngày đăng</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-slate-400 uppercase">Thao tác</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-800/40">
                <tr v-for="p in posts.data" :key="p.id" class="hover:bg-slate-800/40">
                    <td class="px-4 py-3 text-sm font-medium text-slate-200">{{ p.title }}</td>
                    <td class="px-4 py-3 text-sm text-slate-400">{{ p.category?.name || '—' }}</td>
                    <td class="px-4 py-3 text-center"><span :class="statusClass(p.status)" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ statusLabel(p.status) }}</span></td>
                    <td class="px-4 py-3 text-sm text-slate-400 text-center">{{ p.view_count || 0 }}</td>
                    <td class="px-4 py-3 text-sm text-slate-400">{{ formatDate(p.published_at || p.created_at) }}</td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        <a :href="`/tin-tuc/${p.slug}`" target="_blank" class="inline-flex items-center text-slate-400 hover:text-cyan-400 transition-colors" title="Xem trên website">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <Link :href="`/admin/posts/${p.id}/edit`" class="text-cyan-500 hover:text-cyan-300 text-sm">Sửa</Link>
                        <button @click="destroy(p.id)" class="text-red-400 hover:text-red-300 text-sm">Xóa</button>
                    </td>
                </tr>
                <tr v-if="!posts.data?.length"><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có bài viết</td></tr>
            </tbody>
        </table>
    </div>
</AdminLayout>
</template>
