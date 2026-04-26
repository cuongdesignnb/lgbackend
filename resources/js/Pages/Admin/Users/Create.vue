<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    roles: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff',
    spatie_roles: [],
});

function submit() {
    form.post('/admin/users');
}

const roleLabel = {
    super_admin: 'Super Admin — Toàn quyền hệ thống',
    admin: 'Admin — Quản lý (trừ user/phân quyền)',
    editor: 'Biên tập — Nội dung & media',
    sales: 'Bán hàng — Đơn hàng & khách hàng',
};
</script>

<template>
    <div class="max-w-2xl">
        <div class="flex items-center gap-3 mb-6">
            <Link href="/admin/users" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <h1 class="text-xl font-bold text-slate-100">Tạo tài khoản mới</h1>
        </div>

        <form @submit.prevent="submit" class="bg-slate-900/60 border border-slate-800/60 rounded-xl p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Họ tên</label>
                <input v-model="form.name" type="text" required class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/50 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50" />
                <p v-if="form.errors.name" class="text-xs text-red-400 mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                <input v-model="form.email" type="email" required class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/50 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50" />
                <p v-if="form.errors.email" class="text-xs text-red-400 mt-1">{{ form.errors.email }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Mật khẩu</label>
                    <input v-model="form.password" type="password" required class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/50 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50" />
                    <p v-if="form.errors.password" class="text-xs text-red-400 mt-1">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Xác nhận mật khẩu</label>
                    <input v-model="form.password_confirmation" type="password" required class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/50 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Loại tài khoản</label>
                <select v-model="form.role" class="w-full px-4 py-2.5 bg-slate-800/60 border border-slate-700/50 rounded-lg text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/50">
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Vai trò phân quyền</label>
                <div class="space-y-2">
                    <label v-for="role in roles" :key="role" class="flex items-center gap-3 p-3 rounded-lg bg-slate-800/40 border border-slate-700/30 hover:border-cyan-500/30 transition-colors cursor-pointer">
                        <input type="checkbox" :value="role" v-model="form.spatie_roles"
                            class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-cyan-500 focus:ring-cyan-500/30" />
                        <div>
                            <p class="text-sm font-medium text-slate-200">{{ roleLabel[role] || role }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" :disabled="form.processing"
                    class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-sm font-semibold rounded-lg shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30 disabled:opacity-50 transition-all">
                    {{ form.processing ? 'Đang lưu...' : 'Tạo tài khoản' }}
                </button>
                <Link href="/admin/users" class="px-6 py-2.5 text-sm text-slate-400 hover:text-white transition-colors">Hủy</Link>
            </div>
        </form>
    </div>
</template>
