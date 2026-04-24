<script setup>
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ catalogues: Object });
const flash = usePage().props.flash;
function destroy(id) { if (confirm('Xóa catalogue này?')) router.delete(`/admin/catalogues/${id}`); }
function formatSize(bytes) { if (!bytes) return '—'; if (bytes < 1024) return bytes + ' B'; if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'; return (bytes / 1048576).toFixed(1) + ' MB'; }
</script>
<template>
<AdminLayout title="Quản lý Catalogue">
    <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ flash.success }}</div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-200">Quản lý Catalogue</h3>
            <p class="text-sm text-slate-500 mt-1">Tải lên catalogue, tài liệu để khách hàng download</p>
        </div>
        <Link href="/admin/catalogues/create" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm catalogue
        </Link>
    </div>

    <div v-if="catalogues.data?.length" class="space-y-4">
        <div v-for="c in catalogues.data" :key="c.id" class="bg-slate-900 rounded-lg border border-slate-800/60 overflow-hidden hover:border-slate-700/60 transition-colors">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-48 flex-shrink-0 bg-slate-800/40 flex items-center justify-center p-6">
                    <img v-if="c.cover_image" :src="c.cover_image" class="h-32 object-contain" />
                    <svg v-else class="w-16 h-16 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="flex-1 p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-semibold text-slate-200">{{ c.title }}</h4>
                            <span :class="c.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700/50 text-slate-400'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ c.is_active ? 'Hiện' : 'Ẩn' }}</span>
                        </div>
                        <p v-if="c.description" class="text-xs text-slate-400 line-clamp-2 mb-2">{{ c.description }}</p>
                        <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                            <span v-if="c.file_name">📄 {{ c.file_name }}</span>
                            <span v-if="c.file_size">{{ formatSize(c.file_size) }}</span>
                            <span>📥 {{ c.download_count }} lượt tải</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3">
                        <a v-if="c.file_url" :href="c.file_url" target="_blank" class="px-3 py-1.5 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 rounded text-xs border border-emerald-500/20">Xem file</a>
                        <Link :href="`/admin/catalogues/${c.id}/edit`" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs border border-slate-700/50">Sửa</Link>
                        <button @click="destroy(c.id)" class="px-3 py-1.5 hover:bg-red-600/10 text-red-400 rounded text-xs border border-transparent hover:border-red-500/20">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="bg-slate-900 rounded-lg border border-slate-800/60 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="text-slate-400 mb-3">Chưa có catalogue nào</p>
        <Link href="/admin/catalogues/create" class="inline-block px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium">Thêm catalogue đầu tiên</Link>
    </div>
</AdminLayout>
</template>
