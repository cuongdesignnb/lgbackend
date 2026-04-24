<script setup>
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ videos: Object });
const flash = usePage().props.flash;
function destroy(id) { if (confirm('Xóa video này?')) router.delete(`/admin/videos/${id}`); }
</script>
<template>
<AdminLayout title="Quản lý Video">
    <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ flash.success }}</div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-200">Quản lý Video</h3>
            <p class="text-sm text-slate-500 mt-1">Video hiển thị trên trang chủ — click mở popup xem</p>
        </div>
        <Link href="/admin/videos/create" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm video
        </Link>
    </div>

    <div v-if="videos.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="v in videos.data" :key="v.id" class="bg-slate-900 rounded-lg border border-slate-800/60 overflow-hidden">
            <div class="aspect-video bg-slate-800/40 relative">
                <img v-if="v.thumbnail" :src="v.thumbnail" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-600">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="absolute top-2 left-2 flex gap-1">
                    <span :class="v.is_active ? 'bg-emerald-500' : 'bg-slate-600'" class="px-2 py-0.5 text-white text-[10px] rounded-full">{{ v.is_active ? 'Hiện' : 'Ẩn' }}</span>
                    <span v-if="v.is_featured" class="bg-amber-500 px-2 py-0.5 text-white text-[10px] rounded-full">Nổi bật</span>
                </div>
                <span class="absolute top-2 right-2 bg-slate-900/70 text-slate-300 px-2 py-0.5 text-[10px] rounded">{{ v.source === 'embed' ? 'Embed' : 'Upload' }}</span>
            </div>
            <div class="p-4">
                <h4 class="text-sm font-semibold text-slate-200 line-clamp-1">{{ v.title }}</h4>
                <p v-if="v.description" class="text-xs text-slate-400 line-clamp-2 mt-1">{{ v.description }}</p>
                <div class="flex items-center gap-2 mt-3">
                    <Link :href="`/admin/videos/${v.id}/edit`" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs border border-slate-700/50">Sửa</Link>
                    <button @click="destroy(v.id)" class="px-3 py-1.5 hover:bg-red-600/10 text-red-400 rounded text-xs border border-transparent hover:border-red-500/20">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="bg-slate-900 rounded-lg border border-slate-800/60 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-slate-400 mb-3">Chưa có video nào</p>
        <Link href="/admin/videos/create" class="inline-block px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium">Thêm video đầu tiên</Link>
    </div>
</AdminLayout>
</template>
