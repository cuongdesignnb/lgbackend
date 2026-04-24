<script setup>
import { ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ products: Object, categories: Array, brands: Array, filters: Object });
const flash = usePage().props.flash;
const search = ref(props.filters?.search || '');
const categoryId = ref(props.filters?.category_id || '');
const brandId = ref(props.filters?.brand_id || '');
const status = ref(props.filters?.status || '');

let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(applyFilters, 400); });
function applyFilters() {
    router.get('/admin/products', {
        search: search.value || undefined, category_id: categoryId.value || undefined,
        brand_id: brandId.value || undefined, status: status.value || undefined,
    }, { preserveState: true, replace: true });
}

function destroy(id) {
    if (!confirm('Xóa sản phẩm này?')) return;
    router.delete(`/admin/products/${id}`);
}

function formatPrice(p) { return new Intl.NumberFormat('vi-VN').format(p) + '₫'; }
const statusMap = { active: 'Đang bán', inactive: 'Ẩn', out_of_stock: 'Hết hàng' };
const statusColor = { active: 'bg-green-100 text-emerald-400', inactive: 'bg-slate-800/60 text-slate-400', out_of_stock: 'bg-red-100 text-red-700' };
</script>
<template>
<AdminLayout title="Sản phẩm">
    <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ flash.success }}</div>
    <div v-if="flash?.error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ flash.error }}</div>
    <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Link href="/admin/products/create" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium">+ Thêm sản phẩm</Link>
            </div>
            <div class="flex items-center gap-3">
                <input v-model="search" placeholder="Tìm tên, SKU..." class="border border-slate-700/50 rounded-lg px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-cyan-500/50">
                <select v-model="categoryId" @change="applyFilters()" class="border border-slate-700/50 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả danh mục</option>
                    <option v-for="c in categories" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="brandId" @change="applyFilters()" class="border border-slate-700/50 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả thương hiệu</option>
                    <option v-for="b in brands" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="status" @change="applyFilters()" class="border border-slate-700/50 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang bán</option>
                    <option value="inactive">Ẩn</option>
                    <option value="out_of_stock">Hết hàng</option>
                </select>
            </div>
        </div>
    </div>
    <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-800/40">
            <thead class="bg-slate-800/40"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Sản phẩm</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">SKU</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Danh mục</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Giá</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Tồn kho</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Trạng thái</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-slate-400 uppercase">Thao tác</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-800/40">
                <tr v-for="p in products.data" :key="p.id" class="hover:bg-slate-800/40">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-800/60 rounded flex-shrink-0 overflow-hidden">
                                <img v-if="p.images?.[0]" :src="p.images[0].url" class="w-full h-full object-cover">
                            </div>
                            <span class="text-sm font-medium text-slate-200">{{ p.name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-400">{{ p.sku }}</td>
                    <td class="px-4 py-3 text-sm text-slate-400">{{ p.category?.name || '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span v-if="p.sale_price" class="text-red-600 font-medium">{{ formatPrice(p.sale_price) }}</span>
                        <span :class="p.sale_price ? 'line-through text-slate-500 text-xs ml-1' : 'text-slate-200'">{{ formatPrice(p.price) }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm" :class="p.quantity < 5 ? 'text-red-600 font-medium' : 'text-slate-400'">{{ p.quantity }}</td>
                    <td class="px-4 py-3"><span :class="statusColor[p.status]" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ statusMap[p.status] }}</span></td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        <a :href="`/${p.category?.slug || 'san-pham'}/${p.slug}`" target="_blank" class="inline-flex items-center text-slate-400 hover:text-cyan-400 transition-colors" title="Xem trên website">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <Link :href="`/admin/products/${p.id}/edit`" class="text-cyan-500 hover:text-cyan-300 text-sm">Sửa</Link>
                        <button @click="destroy(p.id)" class="text-red-400 hover:text-red-300 text-sm">Xóa</button>
                    </td>
                </tr>
                <tr v-if="!products.data?.length"><td colspan="7" class="px-4 py-8 text-center text-slate-500">Chưa có sản phẩm nào</td></tr>
            </tbody>
        </table>
    </div>
    <div v-if="products.last_page > 1" class="mt-4 flex justify-end gap-1">
        <button v-for="link in products.links" :key="link.label" @click="link.url && router.get(link.url, {}, {preserveState:true})"
            :disabled="!link.url" :class="link.active ? 'bg-cyan-600 text-white' : 'bg-slate-900 text-slate-300 hover:bg-slate-800/40'"
            class="px-3 py-1.5 text-sm border border-slate-700/50 rounded disabled:opacity-40" v-html="link.label"/>
    </div>
</AdminLayout>
</template>
