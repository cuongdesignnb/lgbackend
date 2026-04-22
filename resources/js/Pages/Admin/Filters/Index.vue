<script setup>
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ filters: Array });
const flash = usePage().props.flash;
function destroy(id) { if (confirm('Xóa bộ lọc này?')) router.delete(`/admin/filters/${id}`); }

const matchFieldLabels = {
    specifications_text: 'Thông số KT',
    product_name: 'Tên SP',
    brand: 'Thương hiệu',
    price: 'Giá',
};
const typeLabels = { checkbox: 'Checkbox', price_range: 'Khoảng giá' };
</script>
<template>
<AdminLayout title="Bộ lọc sản phẩm">
    <div v-if="flash?.success" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ flash.success }}</div>
    <div v-if="flash?.error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ flash.error }}</div>
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Bộ lọc sản phẩm</h3>
            <p class="text-sm text-gray-500 mt-0.5">Tạo bộ lọc rồi gán vào danh mục cần dùng</p>
        </div>
        <Link href="/admin/filters/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">+ Thêm bộ lọc</Link>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match Field</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Giá trị</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Danh mục</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thứ tự</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                <tr v-for="f in filters" :key="f.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ f.name }}</div>
                        <div class="text-xs text-gray-400">{{ f.slug }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="f.type === 'price_range' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'">
                            {{ typeLabels[f.type] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ matchFieldLabels[f.match_field] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 text-center">
                        <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full text-xs font-medium">{{ f.values_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 text-center">
                        <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full text-xs font-medium">{{ f.categories_count }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 text-center">{{ f.sort_order }}</td>
                    <td class="px-4 py-3 text-center">
                        <span :class="f.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded-full text-xs font-medium">
                            {{ f.is_active ? 'Bật' : 'Tắt' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <Link :href="`/admin/filters/${f.id}/edit`" class="text-indigo-600 hover:text-indigo-800 text-sm">Sửa</Link>
                        <button @click="destroy(f.id)" class="text-red-600 hover:text-red-800 text-sm">Xóa</button>
                    </td>
                </tr>
                <tr v-if="!filters?.length"><td colspan="8" class="px-4 py-8 text-center text-gray-400">Chưa có bộ lọc nào</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Preview values for each filter -->
    <div v-if="filters?.length" class="mt-6 space-y-4">
        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Chi tiết giá trị bộ lọc</h4>
        <div v-for="f in filters" :key="'detail-' + f.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h5 class="font-medium text-gray-900">{{ f.name }} <span class="text-xs text-gray-400">({{ f.values?.length || 0 }} giá trị)</span></h5>
                <Link :href="`/admin/filters/${f.id}/edit`" class="text-xs text-indigo-600 hover:text-indigo-800">Chỉnh sửa →</Link>
            </div>
            <div v-if="f.values?.length" class="flex flex-wrap gap-2">
                <span v-for="v in f.values" :key="v.id"
                    :class="v.is_active ? 'bg-gray-100 text-gray-700' : 'bg-red-50 text-red-400 line-through'"
                    class="px-2.5 py-1 rounded-lg text-xs font-medium">
                    {{ v.label }}
                    <span v-if="v.match_value" class="text-gray-400 ml-1">→ {{ v.match_value }}</span>
                    <span v-if="v.price_min || v.price_max" class="text-gray-400 ml-1">
                        → {{ v.price_min ? Number(v.price_min).toLocaleString() : '0' }}₫ - {{ v.price_max ? Number(v.price_max).toLocaleString() : '∞' }}₫
                    </span>
                </span>
            </div>
            <p v-else class="text-sm text-gray-400">Chưa có giá trị</p>
        </div>
    </div>
</AdminLayout>
</template>
