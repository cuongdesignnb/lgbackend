<script setup>
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ banners: Object });
const flash = usePage().props.flash;
function destroy(id) { if (confirm('Bạn có chắc muốn xóa banner này?')) router.delete(`/admin/banners/${id}`); }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('vi-VN') : '—'; }
const posLabel = { hero: 'Hero', sidebar: 'Sidebar', footer: 'Footer', popup: 'Popup', category: 'Danh mục' };
const posColor = { hero: 'bg-cyan-500/10 text-cyan-400', sidebar: 'bg-purple-500/10 text-purple-400', footer: 'bg-amber-500/10 text-amber-400', popup: 'bg-rose-500/10 text-rose-400', category: 'bg-emerald-500/10 text-emerald-400' };
</script>
<template>
<AdminLayout title="Quản lý Banner">
    <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ flash.success }}</div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-200">Quản lý Banner</h3>
            <p class="text-sm text-slate-500 mt-1">Tải lên và quản lý các banner hiển thị trên trang chủ</p>
        </div>
        <Link href="/admin/banners/create" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm banner
        </Link>
    </div>

    <!-- Banner Grid -->
    <div v-if="banners.data?.length" class="space-y-4">
        <div v-for="b in banners.data" :key="b.id" class="bg-slate-900 rounded-lg border border-slate-800/60 overflow-hidden hover:border-slate-700/60 transition-colors">
            <div class="flex flex-col md:flex-row">
                <!-- Image Preview -->
                <div class="md:w-72 lg:w-80 flex-shrink-0 bg-slate-800/40">
                    <div class="relative aspect-[20/7] md:h-full">
                        <img v-if="b.image" :src="b.image" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-slate-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <!-- Status badge -->
                        <div class="absolute top-2 left-2">
                            <span :class="b.is_active ? 'bg-emerald-500' : 'bg-slate-600'" class="w-3 h-3 rounded-full block shadow"></span>
                        </div>
                    </div>
                </div>
                <!-- Info -->
                <div class="flex-1 p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-semibold text-slate-200">{{ b.title || '(Không tiêu đề)' }}</h4>
                            <span :class="posColor[b.position] || 'bg-slate-700/50 text-slate-400'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ posLabel[b.position] || b.position }}</span>
                        </div>
                        <p v-if="b.description" class="text-sm text-slate-400 line-clamp-1 mb-2">{{ b.description }}</p>
                        <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                            <span>Thứ tự: {{ b.sort_order }}</span>
                            <span>Hiệu lực: {{ formatDate(b.starts_at) }} → {{ formatDate(b.ends_at) }}</span>
                            <span v-if="b.link" class="text-cyan-500/70">{{ b.link }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3">
                        <Link :href="`/admin/banners/${b.id}/edit`" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm border border-slate-700/50">Sửa</Link>
                        <button @click="destroy(b.id)" class="px-3 py-1.5 hover:bg-red-600/10 text-red-400 hover:text-red-300 rounded-lg text-sm border border-transparent hover:border-red-500/20">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty state -->
    <div v-else class="bg-slate-900 rounded-lg border border-slate-800/60 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="text-slate-400 mb-3">Chưa có banner nào</p>
        <Link href="/admin/banners/create" class="inline-block px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium">Thêm banner đầu tiên</Link>
    </div>
</AdminLayout>
</template>
