<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const search = ref(props.filters?.search || '');
let searchTimer = null;

function doSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/admin/users', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
}

// Role badge colors
const roleBadge = {
    super_admin: 'bg-red-500/15 text-red-400 border-red-500/20',
    admin: 'bg-cyan-500/15 text-cyan-400 border-cyan-500/20',
    editor: 'bg-green-500/15 text-green-400 border-green-500/20',
    sales: 'bg-amber-500/15 text-amber-400 border-amber-500/20',
};

const roleLabel = {
    super_admin: 'Super Admin',
    admin: 'Admin',
    editor: 'Biên tập',
    sales: 'Bán hàng',
};

function confirmDelete(user) {
    if (user.id === currentUser.value?.id) {
        alert('Không thể xóa chính tài khoản đang đăng nhập!');
        return;
    }
    if (confirm(`Xóa tài khoản "${user.name}"?`)) {
        router.delete(`/admin/users/${user.id}`);
    }
}
</script>

<template>
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <h1 class="text-xl font-bold text-slate-100">Quản lý tài khoản</h1>
            <Link href="/admin/users/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-sm font-semibold rounded-lg shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tạo tài khoản
            </Link>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input v-model="search" @input="doSearch" type="text" placeholder="Tìm theo tên hoặc email..."
                class="w-full max-w-md px-4 py-2.5 bg-slate-800/60 border border-slate-700/50 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50"/>
        </div>

        <!-- Table -->
        <div class="bg-slate-900/60 border border-slate-800/60 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800/60">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Tài khoản</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Vai trò</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Quyền hệ thống</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white ring-2 ring-violet-500/20">
                                    {{ user.name?.charAt(0)?.toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-200">{{ user.name }}</p>
                                    <p class="text-xs text-slate-500">ID: {{ user.id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-300">{{ user.email }}</td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs font-medium px-2 py-1 rounded-md bg-slate-700/50 text-slate-300 border border-slate-600/30">
                                {{ user.role === 'admin' ? 'Admin' : 'Staff' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="r in user.spatie_roles" :key="r"
                                    :class="roleBadge[r] || 'bg-slate-700/50 text-slate-300 border-slate-600/30'"
                                    class="text-[11px] font-semibold px-2 py-0.5 rounded-md border">
                                    {{ roleLabel[r] || r }}
                                </span>
                                <span v-if="!user.spatie_roles?.length" class="text-xs text-slate-600 italic">Chưa gán</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Link :href="`/admin/users/${user.id}/edit`"
                                    class="p-2 text-slate-400 hover:text-cyan-400 hover:bg-slate-800 rounded-lg transition-colors" title="Sửa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </Link>
                                <button @click="confirmDelete(user)"
                                    :disabled="user.id === currentUser?.id"
                                    class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed" title="Xóa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!users.data?.length">
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">Không tìm thấy tài khoản nào.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="flex justify-center mt-6 gap-1">
            <Link v-for="link in users.links" :key="link.label"
                :href="link.url || '#'"
                :class="[
                    'px-3 py-1.5 text-xs rounded-lg transition-colors',
                    link.active ? 'bg-cyan-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200',
                    !link.url ? 'opacity-30 pointer-events-none' : ''
                ]"
                v-html="link.label" />
        </div>
    </div>
</template>
