<script setup>
import { ref, watch } from 'vue';
import { router, Link, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ products: Object, categories: Array, brands: Array, filters: Object });
const flash = usePage().props.flash;
const search = ref(props.filters?.search || '');
const categoryId = ref(props.filters?.category_id || '');
const brandId = ref(props.filters?.brand_id || '');
const status = ref(props.filters?.status || '');

// Import modal
const showImportModal = ref(false);
const importForm = useForm({ file: null });
const importFileRef = ref(null);

// ── Quick Edit modal ──
const showQuickEdit = ref(false);
const quickEditTarget = ref(null);
const quickForm = useForm({
    price: 0,
    sale_price: '',
    stock_quantity: 0,
    is_active: true,
    is_featured: false,
});
function openQuickEdit(product) {
    quickEditTarget.value = product;
    quickForm.price = Number(product.price ?? 0);
    quickForm.sale_price = product.sale_price ? Number(product.sale_price) : '';
    quickForm.stock_quantity = Number(product.quantity ?? product.stock_quantity ?? 0);
    quickForm.is_active = Boolean(product.is_active);
    quickForm.is_featured = Boolean(product.is_featured);
    showQuickEdit.value = true;
}
function submitQuickEdit() {
    if (!quickEditTarget.value) return;
    const payload = {
        price: Number(quickForm.price) || 0,
        sale_price: quickForm.sale_price === '' || quickForm.sale_price == null ? null : Number(quickForm.sale_price),
        stock_quantity: Number(quickForm.stock_quantity) || 0,
        is_active: !!quickForm.is_active,
        is_featured: !!quickForm.is_featured,
    };
    router.patch(`/admin/products/${quickEditTarget.value.id}/quick-update`, payload, {
        preserveScroll: true,
        onSuccess: () => {
            showQuickEdit.value = false;
            quickEditTarget.value = null;
        },
    });
}

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

// Export with current filters
function exportExcel() {
    const params = new URLSearchParams();
    if (categoryId.value) params.append('category_id', categoryId.value);
    if (brandId.value) params.append('brand_id', brandId.value);
    if (status.value) params.append('status', status.value);
    if (search.value) params.append('search', search.value);
    window.location.href = '/admin/products/export?' + params.toString();
}

function onImportFileSelect(e) {
    importForm.file = e.target.files[0] || null;
}

function submitImport() {
    if (!importForm.file) return;
    importForm.post('/admin/products/import', {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
}
</script>
<template>
<AdminLayout title="Sản phẩm">
    <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ flash.success }}</div>
    <div v-if="flash?.error" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg text-sm">{{ flash.error }}</div>

    <!-- Toolbar -->
    <div class="bg-slate-900 rounded-lg shadow-none border border-slate-800/60 p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <Link href="/admin/products/create" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-medium">+ Thêm sản phẩm</Link>

                <!-- Export button -->
                <button @click="exportExcel" class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium flex items-center gap-1.5" title="Xuất Excel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Xuất Excel
                </button>

                <!-- Import button -->
                <button @click="showImportModal = true" class="px-3 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium flex items-center gap-1.5" title="Nhập Excel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Nhập Excel
                </button>
            </div>
            <div class="flex items-center gap-3">
                <input v-model="search" placeholder="Tìm tên, SKU..." class="bg-slate-800/60 text-slate-200 placeholder-slate-500 border border-slate-700/50 rounded-lg px-3 py-2 text-sm w-48 focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                <select v-model="categoryId" @change="applyFilters()" class="bg-slate-800/60 text-slate-200 border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                    <option value="">Tất cả danh mục</option>
                    <option v-for="c in categories" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="brandId" @change="applyFilters()" class="bg-slate-800/60 text-slate-200 border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                    <option value="">Tất cả thương hiệu</option>
                    <option v-for="b in brands" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="status" @change="applyFilters()" class="bg-slate-800/60 text-slate-200 border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang bán</option>
                    <option value="inactive">Ẩn</option>
                    <option value="out_of_stock">Hết hàng</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Product Table -->
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
                        <button @click="openQuickEdit(p)" class="text-amber-400 hover:text-amber-300 text-sm" title="Sửa nhanh">⚡ Sửa nhanh</button>
                        <Link :href="`/admin/products/${p.id}/edit`" class="text-cyan-500 hover:text-cyan-300 text-sm">Sửa</Link>
                        <button @click="destroy(p.id)" class="text-red-400 hover:text-red-300 text-sm">Xóa</button>
                    </td>
                </tr>
                <tr v-if="!products.data?.length"><td colspan="7" class="px-4 py-8 text-center text-slate-500">Chưa có sản phẩm nào</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div v-if="products.last_page > 1" class="mt-4 flex justify-end gap-1">
        <button v-for="link in products.links" :key="link.label" @click="link.url && router.get(link.url, {}, {preserveState:true})"
            :disabled="!link.url" :class="link.active ? 'bg-cyan-600 text-white' : 'bg-slate-900 text-slate-300 hover:bg-slate-800/40'"
            class="px-3 py-1.5 text-sm border border-slate-700/50 rounded disabled:opacity-40" v-html="link.label"/>
    </div>

    <!-- Quick Edit Modal -->
    <Teleport to="body">
        <div v-if="showQuickEdit && quickEditTarget" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" @click="showQuickEdit = false"></div>
            <div class="relative bg-slate-900 rounded-xl border border-slate-800/60 w-full max-w-md mx-4 p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-slate-200 flex items-center gap-2">
                        <span class="text-amber-400">⚡</span>
                        Sửa nhanh sản phẩm
                    </h3>
                    <button @click="showQuickEdit = false" class="text-slate-500 hover:text-slate-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <p class="text-sm text-slate-400 mb-4 truncate">{{ quickEditTarget.name }} <span class="text-xs text-slate-500">— SKU {{ quickEditTarget.sku }}</span></p>

                <form @submit.prevent="submitQuickEdit" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Giá gốc (₫)</label>
                            <input v-model.number="quickForm.price" type="number" min="0" step="1000" class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500/50">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Giá khuyến mãi (₫)</label>
                            <input v-model="quickForm.sale_price" type="number" min="0" step="1000" placeholder="Để trống nếu không khuyến mãi" class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500/50">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tồn kho</label>
                        <input v-model.number="quickForm.stock_quantity" type="number" min="0" class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500/50">
                    </div>
                    <div class="flex items-center gap-6 pt-1">
                        <label class="flex items-center gap-2 text-sm text-slate-200">
                            <input v-model="quickForm.is_active" type="checkbox" class="rounded border-slate-700/50 text-amber-500 focus:ring-amber-500/50">
                            Đang bán
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-200">
                            <input v-model="quickForm.is_featured" type="checkbox" class="rounded border-slate-700/50 text-amber-500 focus:ring-amber-500/50">
                            Nổi bật
                        </label>
                    </div>

                    <p class="text-xs text-slate-500 bg-slate-800/40 rounded p-2">
                        Sửa nhanh chỉ cập nhật các trường trên. Ảnh, mô tả, thông số, gallery sẽ giữ nguyên.
                    </p>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="showQuickEdit = false" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</button>
                        <button type="submit" :disabled="quickForm.processing" class="px-5 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium disabled:opacity-50">
                            {{ quickForm.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Import Modal -->
    <Teleport to="body">
        <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" @click="showImportModal = false"></div>
            <div class="relative bg-slate-900 rounded-xl border border-slate-800/60 w-full max-w-lg mx-4 p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-slate-200 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Nhập sản phẩm từ Excel
                    </h3>
                    <button @click="showImportModal = false" class="text-slate-500 hover:text-slate-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <!-- File Upload -->
                    <div class="border-2 border-dashed border-slate-700/50 rounded-lg p-6 text-center hover:border-amber-500/50 transition-colors">
                        <div v-if="!importForm.file">
                            <svg class="w-10 h-10 mx-auto text-slate-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <label class="cursor-pointer">
                                <span class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium inline-block">Chọn file Excel</span>
                                <input type="file" accept=".xlsx,.xls,.csv" @change="onImportFileSelect" class="hidden" ref="importFileRef" />
                            </label>
                            <p class="text-slate-500 text-xs mt-2">Hỗ trợ: .xlsx, .xls, .csv — tối đa 10MB</p>
                        </div>
                        <div v-else class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-500/10 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm text-slate-200 font-medium">{{ importForm.file.name }}</p>
                                    <p class="text-xs text-slate-500">{{ (importForm.file.size / 1024).toFixed(1) }} KB</p>
                                </div>
                            </div>
                            <button type="button" @click="importForm.file = null" class="text-red-400 hover:text-red-300 text-sm">Xóa</button>
                        </div>
                    </div>

                    <!-- Template download -->
                    <div class="bg-slate-800/40 rounded-lg p-3 flex items-center justify-between">
                        <div class="text-sm text-slate-400">
                            <span class="text-slate-300 font-medium">Chưa có file mẫu?</span>
                            Tải mẫu Excel
                        </div>
                        <a href="/admin/products/import-template" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg text-sm font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Tải mẫu
                        </a>
                    </div>

                    <!-- Error -->
                    <div v-if="importForm.errors.file" class="text-red-400 text-sm">{{ importForm.errors.file }}</div>

                    <!-- Info -->
                    <div class="text-xs text-slate-500 space-y-1">
                        <p>• Cột <strong class="text-slate-400">SKU</strong> là bắt buộc, dùng để phân biệt sản phẩm</p>
                        <p>• Nếu SKU đã tồn tại, sản phẩm sẽ được <strong class="text-slate-400">cập nhật</strong></p>
                        <p>• Nếu SKU mới, sản phẩm sẽ được <strong class="text-slate-400">tạo mới</strong></p>
                        <p>• Danh mục / Thương hiệu cần nhập đúng tên đã có trong hệ thống</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/60 rounded-lg">Hủy</button>
                        <button type="submit" :disabled="!importForm.file || importForm.processing"
                            class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium disabled:opacity-50">
                            {{ importForm.processing ? 'Đang nhập...' : 'Nhập sản phẩm' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</AdminLayout>
</template>
