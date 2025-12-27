<script setup lang="ts">
import { ref, computed } from 'vue';
import { Check, X, ChevronDown, Search } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';

interface Option {
    value: any;
    label: string;
    description?: string;
    group?: string;
    imageUrl?: string;
}

interface Props {
    options: Option[];
    modelValue: any[];
    placeholder?: string;
    searchPlaceholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Sélectionner...',
    searchPlaceholder: 'Rechercher...',
});

const emit = defineEmits<{
    'update:modelValue': [value: any[]];
}>();

const isOpen = ref(false);
const searchQuery = ref('');

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => 
        option.label.toLowerCase().includes(query) ||
        option.description?.toLowerCase().includes(query)
    );
});

const groupedOptions = computed(() => {
    const groups: Record<string, Option[]> = {};
    filteredOptions.value.forEach(option => {
        const groupName = option.group || 'Autres';
        if (!groups[groupName]) {
            groups[groupName] = [];
        }
        groups[groupName].push(option);
    });
    return groups;
});

const selectedOptions = computed(() => {
    return props.options.filter(option => props.modelValue.includes(option.value));
});

const toggleOption = (value: any) => {
    const newValue = props.modelValue.includes(value)
        ? props.modelValue.filter(v => v !== value)
        : [...props.modelValue, value];
    emit('update:modelValue', newValue);
};

const removeOption = (value: any) => {
    emit('update:modelValue', props.modelValue.filter(v => v !== value));
};

const clearAll = () => {
    emit('update:modelValue', []);
};
</script>

<template>
    <div class="relative w-full">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="w-full min-h-[40px] px-3 py-2 border rounded-md bg-white text-left flex items-center justify-between gap-2 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#007461]"
        >
            <div class="flex-1 flex flex-wrap gap-1">
                <template v-if="selectedOptions.length > 0">
                    <Badge
                        v-for="option in selectedOptions"
                        :key="option.value"
                        class="bg-[#007461] hover:bg-[#009980] text-white"
                    >
                        {{ option.label }}
                        <button
                            type="button"
                            @click.stop="removeOption(option.value)"
                            class="ml-1 hover:bg-[#005d4e] rounded-full p-0.5"
                        >
                            <X class="w-3 h-3" />
                        </button>
                    </Badge>
                </template>
                <span v-else class="text-gray-500">{{ placeholder }}</span>
            </div>
            <ChevronDown class="w-4 h-4 text-gray-500 flex-shrink-0" />
        </button>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            class="absolute z-50 w-full mt-2 bg-white border rounded-md shadow-lg max-h-[300px] flex flex-col"
        >
            <!-- Search Bar -->
            <div class="p-2 border-b">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-9 pr-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-[#007461]"
                    />
                </div>
            </div>

            <!-- Clear All Button -->
            <div v-if="selectedOptions.length > 0" class="p-2 border-b">
                <button
                    type="button"
                    @click="clearAll"
                    class="w-full text-sm text-red-600 hover:text-red-700 text-left px-2 py-1"
                >
                    Tout désélectionner
                </button>
            </div>

            <!-- Options List -->
            <div class="overflow-y-auto flex-1">
                <template v-for="(groupOptions, groupName) in groupedOptions" :key="groupName">
                    <!-- Group Header -->
                    <div class="px-3 py-2 text-xs font-semibold text-gray-500 bg-gray-50 uppercase tracking-wider sticky top-0">
                        {{ groupName }}
                    </div>
                    <!-- Group Options -->
                    <div
                        v-for="option in groupOptions"
                        :key="option.value"
                        @click="toggleOption(option.value)"
                        class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center gap-2"
                        :class="{ 'bg-gray-50': modelValue.includes(option.value) }"
                    >
                        <div
                            class="w-4 h-4 border rounded flex items-center justify-center flex-shrink-0"
                            :class="modelValue.includes(option.value) ? 'bg-[#007461] border-[#007461]' : 'border-gray-300'"
                        >
                            <Check v-if="modelValue.includes(option.value)" class="w-3 h-3 text-white" />
                        </div>
                        <img
                            v-if="option.imageUrl"
                            :src="option.imageUrl"
                            :alt="option.label"
                            class="w-10 h-10 object-cover rounded flex-shrink-0"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-medium">{{ option.label }}</div>
                            <div v-if="option.description" class="text-xs text-gray-500 truncate">{{ option.description }}</div>
                        </div>
                    </div>
                </template>
                <div v-if="filteredOptions.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">
                    Aucun résultat trouvé
                </div>
            </div>
        </div>

        <!-- Overlay to close dropdown -->
        <div
            v-if="isOpen"
            @click="isOpen = false"
            class="fixed inset-0 z-40"
        />
    </div>
</template>
