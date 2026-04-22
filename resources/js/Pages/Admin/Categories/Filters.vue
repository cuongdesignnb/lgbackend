<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    category: Object,
    allFilters: Array,
    assignedFilterIds: Array,
});

const flash = usePage().props.flash;
const selected = ref([...props.assignedFilterIds]);

function toggle(filterId) {
    const idx = selected.value.indexOf(filterId);
    if (idx > -1) selected.value.splice(idx, 1);
    else selected.value.push(filterId);
}

function moveFilter(index, dir) {
    const newIndex = index + dir;
    if (newIndex < 0 || newIndex >= selected.value.length) return;
    [selected.value[index], selected.value[newIndex]] = [selected.value[newIndex], selected.value[index]];
}

const form = useForm({});
function submit() {
    form.transform(() => ({ filter_ids: selected.value }))
        .put(`/admin/categories/${props.category.id}/filters`);
}
</script>
<template>
<AdminLayout :title="`Bộ lọc: ${category.name}`">
    <div v-if="flash?.success" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ flash.success }}</div>
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Gán bộ lọc cho: {{ category.name }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">Chọn các bộ lọc sẽ hiển thị khi khách vào danh mục này</p>
            </div>
            <Link href="/admin/categories" class="text-sm text-gray-500 hover:text-gray-700">← Quay lại danh mục</Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Available filters -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Tất cả bộ lọc có sẵn</h4>
                <div v-if="allFilters.length" class="space-y-2">
                    <label v-for="f in allFilters" :key="f.id"
                        class="flex items-center gap-3 p-3 rounded-lg border transition-colors cursor-pointer"
                        :class="selected.includes(f.id) ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50'">
                        <input type="checkbox" :checked="selected.includes(f.id)" @change="toggle(f.id)"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900">{{ f.name }}</span>
                            <span class="text-xs text-gray-400 ml-2">({{ f.values_count }} giá trị)</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="f.type === 'price_range' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'">
                            {{ f.type === 'price_range' ? 'Khoảng giá' : 'Checkbox' }}
                        </span>
                    </label>
                </div>
                <p v-else class="text-sm text-gray-400 text-center py-4">
                    Chưa có bộ lọc nào. <Link href="/admin/filters/create" class="text-indigo-600 hover:text-indigo-800">Tạo bộ lọc mới →</Link>
                </p>
            </div>

            <!-- Selected order -->
            <div v-if="selected.length" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Thứ tự hiển thị (kéo để sắp xếp)</h4>
                <div class="space-y-1.5">
                    <div v-for="(fId, idx) in selected" :key="fId"
                        class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-xs text-gray-400 w-6 text-center">{{ idx + 1 }}</span>
                        <span class="text-sm font-medium text-gray-900 flex-1">
                            {{ allFilters.find(f => f.id === fId)?.name || `Filter #${fId}` }}
                        </span>
                        <div class="flex gap-1">
                            <button type="button" @click="moveFilter(idx, -1)" :disabled="idx === 0"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30">↑</button>
                            <button type="button" @click="moveFilter(idx, 1)" :disabled="idx === selected.length - 1"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30">↓</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <Link href="/admin/categories" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">Hủy</Link>
                <button type="submit" :disabled="form.processing"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium disabled:opacity-50">
                    Lưu bộ lọc
                </button>
            </div>
        </form>
    </div>
</AdminLayout>
</template>
