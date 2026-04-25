<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MediaPicker from '@/Components/MediaPicker.vue';

const props = defineProps({
    settings: Object,
});

// Group labels in Vietnamese
const groupLabels = {
    general: 'Thông tin chung',
    appearance: 'Giao diện & Màu sắc',
    contact: 'Liên hệ',
    social: 'Mạng xã hội',
    seo: 'SEO',
    homepage: 'Trang chủ',
    payment: 'Thanh toán',
    shipping: 'Vận chuyển',
    ai: 'AI (ChatGPT / Gemini)',
};

const groupIcons = {
    general: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    appearance: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    contact: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    social: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    seo: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    homepage: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    payment: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    shipping: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
    ai: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
};

// Dimension hints for image fields
const imageDimensionHints = {
    site_logo: 'Khuyến nghị: 200×60px, nền trong suốt (PNG/SVG)',
    site_favicon: 'Khuyến nghị: 32×32px hoặc 64×64px (PNG/ICO)',
    seo_og_image: 'Khuyến nghị: 1200×630px (JPG/PNG)',
};

const groups = computed(() => Object.keys(props.settings || {}));
const activeTab = ref(groups.value[0] || 'general');

// Build form data from settings
function buildFormData() {
    const data = {};
    for (const [group, items] of Object.entries(props.settings || {})) {
        for (const item of items) {
            data[item.key] = item.value ?? '';
        }
    }
    return data;
}

const formData = ref(buildFormData());
const processing = ref(false);
const flash = ref(null);

function submit() {
    processing.value = true;
    const allSettings = [];

    for (const [group, items] of Object.entries(props.settings || {})) {
        for (const item of items) {
            allSettings.push({
                key: item.key,
                value: formData.value[item.key],
            });
        }
    }

    const form = useForm({ settings: allSettings });
    form.put('/admin/settings', {
        preserveScroll: true,
        onSuccess: () => {
            flash.value = 'Đã lưu cài đặt thành công!';
            setTimeout(() => { flash.value = null; }, 3000);
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function getSettingsForGroup(group) {
    return props.settings?.[group] || [];
}
</script>

<template>
<AdminLayout title="Cài đặt website">
    <div class="max-w-5xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-200">Cài đặt website</h3>
                <p class="text-sm text-slate-400 mt-1">Quản lý thông tin và cấu hình chung cho website</p>
            </div>
        </div>

        <!-- Success flash -->
        <div v-if="flash" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ flash }}
        </div>

        <div class="flex gap-6">
            <!-- Sidebar tabs -->
            <div class="w-52 flex-shrink-0">
                <nav class="sticky top-20 space-y-1">
                    <button
                        v-for="group in groups"
                        :key="group"
                        @click="activeTab = group"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors text-left',
                            activeTab === group
                                ? 'bg-cyan-500/10 text-cyan-400'
                                : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200'
                        ]"
                    >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="groupIcons[group] || groupIcons.general" />
                        </svg>
                        {{ groupLabels[group] || group }}
                    </button>
                </nav>
            </div>

            <!-- Settings form -->
            <div class="flex-1">
                <form @submit.prevent="submit">
                    <template v-for="group in groups" :key="group">
                        <div v-show="activeTab === group" class="bg-slate-900 rounded-xl shadow-none border border-slate-800/60 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-800/40 bg-slate-800/40">
                                <h4 class="text-base font-semibold text-slate-200">{{ groupLabels[group] || group }}</h4>
                            </div>

                            <div class="p-6 space-y-5">
                                <div v-for="item in getSettingsForGroup(group)" :key="item.key">
                                    <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                        {{ item.label }}
                                        <span v-if="!item.is_public" class="text-xs text-slate-500 ml-1">(nội bộ)</span>
                                    </label>

                                    <!-- Image — use MediaPicker -->
                                    <template v-if="item.type === 'image'">
                                        <div class="space-y-2">
                                            <!-- Preview + Manual URL -->
                                            <div class="flex items-start gap-4">
                                                <div v-if="formData[item.key]" class="w-24 h-16 rounded-lg border border-slate-700/50 bg-slate-800/40 p-1.5 flex items-center justify-center flex-shrink-0">
                                                    <img :src="formData[item.key]" class="max-w-full max-h-full object-contain" />
                                                </div>
                                                <div class="flex-1 space-y-2">
                                                    <input
                                                        v-model="formData[item.key]"
                                                        type="text"
                                                        :placeholder="'URL hình ảnh hoặc chọn từ thư viện...'"
                                                        class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                                    />
                                                    <div class="flex items-center gap-2">
                                                        <MediaPicker
                                                            :modelValue="formData[item.key]"
                                                            @update:modelValue="formData[item.key] = $event"
                                                            :label="''"
                                                        />
                                                        <button v-if="formData[item.key]" type="button" @click="formData[item.key] = ''" class="text-xs text-red-400 hover:text-red-300">Xóa</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Dimension hint -->
                                            <p v-if="imageDimensionHints[item.key]" class="text-xs text-amber-400/70 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ imageDimensionHints[item.key] }}
                                            </p>
                                        </div>
                                    </template>

                                    <!-- Text input -->
                                    <template v-else-if="item.type === 'text'">
                                        <input
                                            v-model="formData[item.key]"
                                            type="text"
                                            :placeholder="item.label"
                                            class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                        />
                                    </template>

                                    <!-- Color picker with hex input -->
                                    <template v-else-if="item.type === 'color'">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <input
                                                    type="color"
                                                    :value="formData[item.key] || '#000000'"
                                                    @input="formData[item.key] = $event.target.value"
                                                    class="w-12 h-10 rounded-lg border border-slate-700/50 cursor-pointer p-0.5"
                                                />
                                            </div>
                                            <input
                                                v-model="formData[item.key]"
                                                type="text"
                                                placeholder="#c8102e"
                                                class="flex-1 border border-slate-700/50 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                                maxlength="7"
                                            />
                                            <div class="w-20 h-10 rounded-lg border border-slate-700/50 flex-shrink-0" :style="{ backgroundColor: formData[item.key] || '#000' }"></div>
                                        </div>
                                    </template>

                                    <!-- Textarea -->
                                    <textarea
                                        v-else-if="item.type === 'textarea'"
                                        v-model="formData[item.key]"
                                        :placeholder="item.label"
                                        rows="3"
                                        class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                    />

                                    <!-- Boolean toggle -->
                                    <label v-else-if="item.type === 'boolean'" class="relative inline-flex items-center cursor-pointer mt-1">
                                        <input
                                            type="checkbox"
                                            :checked="formData[item.key] === '1' || formData[item.key] === true || formData[item.key] === 'true'"
                                            @change="formData[item.key] = $event.target.checked ? '1' : '0'"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-900 after:border-slate-700/50 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                                        <span class="ml-3 text-sm text-slate-400">{{ formData[item.key] === '1' || formData[item.key] === true || formData[item.key] === 'true' ? 'Bật' : 'Tắt' }}</span>
                                    </label>

                                    <!-- Number -->
                                    <input
                                        v-else-if="item.type === 'number'"
                                        v-model="formData[item.key]"
                                        type="number"
                                        :placeholder="item.label"
                                        class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                    />

                                    <!-- Select -->
                                    <select
                                        v-else-if="item.type === 'select' && item.options"
                                        v-model="formData[item.key]"
                                        class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                    >
                                        <option v-for="opt in (typeof item.options === 'string' ? JSON.parse(item.options) : item.options)?.choices || []" :key="opt" :value="opt">
                                            {{ opt }}
                                        </option>
                                    </select>

                                    <!-- Fallback text -->
                                    <input
                                        v-else
                                        v-model="formData[item.key]"
                                        type="text"
                                        :placeholder="item.label"
                                        class="w-full border border-slate-700/50 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50"
                                    />

                                    <p class="text-xs text-slate-500 mt-1">{{ item.key }}</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Save button -->
                    <div class="mt-6 flex justify-end">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-2.5 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 text-sm font-semibold disabled:opacity-50 transition-colors shadow-none"
                        >
                            <span v-if="processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Đang lưu...
                            </span>
                            <span v-else>Lưu cài đặt</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</AdminLayout>
</template>
