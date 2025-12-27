<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Search, X, User as UserIcon } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    modelValue: number[];
    disabled?: boolean;
    currentUserId?: number; // Pour pré-sélectionner le créateur
    maxParticipants?: number; // Capacité maximale de la salle
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const searchQuery = ref('');
const searchResults = ref<User[]>([]);
const selectedUsers = ref<User[]>([]);
const loading = ref(false);
const showDropdown = ref(false);
const searchTimeout = ref<number | null>(null);

// Charger les utilisateurs déjà sélectionnés
const loadSelectedUsers = async () => {
    if (props.modelValue.length === 0) return;
    
    try {
        // Charger les détails des utilisateurs depuis les IDs
        const responses = await Promise.all(
            props.modelValue.map(id => 
                axios.get(`/api/users/${id}`).catch(() => null)
            )
        );
        
        selectedUsers.value = responses
            .filter(r => r !== null)
            .map(r => r!.data);
    } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error);
    }
};

// Rechercher des utilisateurs avec debounce
watch(searchQuery, (newValue) => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
    
    if (newValue.length < 2) {
        searchResults.value = [];
        return;
    }
    
    searchTimeout.value = window.setTimeout(async () => {
        loading.value = true;
        try {
            const response = await axios.get('/api/users/search', {
                params: { q: newValue }
            });
            // Filtrer les utilisateurs déjà sélectionnés
            searchResults.value = response.data.filter((user: User) => 
                !props.modelValue.includes(user.id)
            );
            showDropdown.value = true;
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
        } finally {
            loading.value = false;
        }
    }, 300);
});

// Ajouter un participant
const addParticipant = (user: User) => {
    // Vérifier si on n'atteint pas la capacité max
    if (props.maxParticipants && props.modelValue.length >= props.maxParticipants) {
        return; // Ne pas ajouter si capacité atteinte
    }
    
    if (!props.modelValue.includes(user.id)) {
        selectedUsers.value.push(user);
        emit('update:modelValue', [...props.modelValue, user.id]);
        searchQuery.value = '';
        searchResults.value = [];
        showDropdown.value = false;
    }
};

// Retirer un participant
const removeParticipant = (userId: number) => {
    // Ne pas permettre de retirer le créateur
    if (userId === props.currentUserId) return;
    
    selectedUsers.value = selectedUsers.value.filter(u => u.id !== userId);
    emit('update:modelValue', props.modelValue.filter(id => id !== userId));
};

// Initialiser avec les participants existants
watch(() => props.modelValue, () => {
    if (props.modelValue.length > 0 && selectedUsers.value.length === 0) {
        loadSelectedUsers();
    }
}, { immediate: true });
</script>

<template>
    <div class="space-y-3">
        <!-- Utilisateurs sélectionnés -->
        <div v-if="selectedUsers.length > 0" class="flex flex-wrap gap-2">
            <Badge
                v-for="user in selectedUsers"
                :key="user.id"
                class="flex items-center gap-1.5 px-3 py-1.5 text-sm"
                :class="user.id === currentUserId ? 'bg-[#007461]' : 'bg-gray-600'"
            >
                <UserIcon class="w-3.5 h-3.5" />
                <span>{{ user.name }}</span>
                <button
                    v-if="user.id !== currentUserId && !disabled"
                    @click.prevent="removeParticipant(user.id)"
                    class="ml-1 hover:text-red-200 transition"
                    type="button"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
                <span v-if="user.id === currentUserId" class="text-xs opacity-75">(Créateur)</span>
            </Badge>
        </div>

        <!-- Champ de recherche -->
        <div v-if="!disabled" class="relative">
            <!-- Warning si capacité atteinte -->
            <div v-if="maxParticipants && modelValue.length >= maxParticipants" class="mb-2 p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded text-sm text-orange-600 dark:text-orange-400">
                ⚠️ Capacité maximale atteinte ({{ maxParticipants }} participant(s))
            </div>
            
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher un participant (nom ou email)..."
                    :disabled="!!(maxParticipants && modelValue.length >= maxParticipants)"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#007461] focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                    @focus="showDropdown = searchResults.length > 0"
                />
            </div>

            <!-- Résultats de recherche -->
            <div
                v-if="showDropdown && (searchResults.length > 0 || loading)"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"
            >
                <div v-if="loading" class="px-4 py-3 text-sm text-gray-500">
                    Recherche en cours...
                </div>
                <button
                    v-for="user in searchResults"
                    :key="user.id"
                    type="button"
                    @click="addParticipant(user)"
                    class="w-full px-4 py-2 text-left hover:bg-gray-50 transition flex items-center gap-2"
                >
                    <UserIcon class="w-4 h-4 text-gray-400" />
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm">{{ user.name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
                    </div>
                </button>
                <div v-if="!loading && searchResults.length === 0" class="px-4 py-3 text-sm text-gray-500">
                    Aucun utilisateur trouvé
                </div>
            </div>

            <!-- Overlay pour fermer le dropdown -->
            <div
                v-if="showDropdown"
                class="fixed inset-0 z-40"
                @click="showDropdown = false"
            ></div>
        </div>

        <p class="text-xs text-gray-500">
            Recherchez et ajoutez des participants à cette réservation. Le créateur est automatiquement ajouté.
        </p>
    </div>
</template>
