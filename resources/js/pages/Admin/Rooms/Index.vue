<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Pencil } from 'lucide-vue-next';
import { toast } from '@/components/ui/toast';
import { Switch } from '@/components/ui/switch';

interface RoomImage {
    id: number;
    path: string;
    order: number;
}

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
    equipement: string[];
    description: string | null;
    constraints_array?: string[];
    is_active: boolean;
    images?: RoomImage[];
}

interface Props {
    rooms: Room[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/reservations' },
    { title: 'Gestion des salles', href: '' },
];

const filterActive = ref<'all' | 'active' | 'inactive'>('all');
const searchQuery = ref('');
const filterEtage = ref<number | 'all'>('all');
const filterCapacite = ref<number | 'all'>('all');

// Obtenir les étages uniques
const uniqueEtages = computed(() => {
    return [...new Set(props.rooms.map(r => r.etage))].sort((a, b) => a - b);
});

// Obtenir les capacités uniques
const uniqueCapacites = computed(() => {
    return [...new Set(props.rooms.map(r => r.capacite))].sort((a, b) => a - b);
});

const filteredRooms = computed(() => {
    let filtered = props.rooms;
    
    // Filtre actif/inactif
    if (filterActive.value === 'active') {
        filtered = filtered.filter(r => r.is_active);
    } else if (filterActive.value === 'inactive') {
        filtered = filtered.filter(r => !r.is_active);
    }
    
    // Recherche textuelle
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(r => 
            r.nom.toLowerCase().includes(query) ||
            r.description?.toLowerCase().includes(query) ||
            r.equipement.some(eq => eq.toLowerCase().includes(query))
        );
    }
    
    // Filtre par étage
    if (filterEtage.value !== 'all') {
        filtered = filtered.filter(r => r.etage === filterEtage.value);
    }
    
    // Filtre par capacité
    if (filterCapacite.value !== 'all') {
        filtered = filtered.filter(r => r.capacite >= Number(filterCapacite.value));
    }
    
    return filtered;
});

const toggleActive = (room: Room) => {
    const wasActive = room.is_active;
    router.patch(`/admin/rooms/${room.id}/toggle-active`, {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            toast.success(`Salle ${wasActive ? 'désactivée' : 'activée'} avec succès`);
        },
        onError: () => {
            toast.error('Une erreur est survenue');
        },
    });
};

const editRoom = (roomId: number) => {
    router.get(`/admin/rooms/${roomId}/edit`);
};

const createRoom = () => {
    router.get('/admin/rooms/create');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Gestion des Salles" />

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Gestion des Salles</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Créer, modifier et désactiver les salles de réunion
                    </p>
                </div>
                <button
                    @click="createRoom"
                    class="flex items-center gap-2 px-4 py-2 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition shadow-msl-s"
                >
                    <Plus class="w-5 h-5" />
                    Nouvelle salle
                </button>
            </div>

            <!-- Filtres -->
            <div class="space-y-3">
                <!-- Filtres Actif/Inactif -->
                <div class="flex gap-2">
                    <button
                        @click="filterActive = 'all'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition',
                            filterActive === 'all'
                                ? 'bg-msl-primary text-white'
                                : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Toutes ({{ rooms.length }})
                    </button>
                    <button
                        @click="filterActive = 'active'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition',
                            filterActive === 'active'
                                ? 'bg-msl-primary text-white'
                                : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Actives ({{ rooms.filter(r => r.is_active).length }})
                    </button>
                    <button
                        @click="filterActive = 'inactive'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition',
                            filterActive === 'inactive'
                                ? 'bg-msl-primary text-white'
                                : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        Inactives ({{ rooms.filter(r => !r.is_active).length }})
                    </button>
                </div>
                
                <!-- Recherche et filtres avancés -->
                <div class="flex flex-wrap gap-3">
                    <!-- Recherche -->
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Rechercher une salle..."
                        class="px-4 py-2 border rounded-lg text-sm flex-1 min-w-64 dark:bg-gray-800 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-msl-primary"
                    />
                    
                    <!-- Filtre Étage -->
                    <select
                        v-model="filterEtage"
                        class="px-4 py-2 border rounded-lg text-sm dark:bg-gray-800 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-msl-primary"
                    >
                        <option value="all">Tous les étages</option>
                        <option v-for="etage in uniqueEtages" :key="etage" :value="etage">
                            Étage {{ etage }}
                        </option>
                    </select>
                    
                    <!-- Filtre Capacité -->
                    <select
                        v-model="filterCapacite"
                        class="px-4 py-2 border rounded-lg text-sm dark:bg-gray-800 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-msl-primary"
                    >
                        <option value="all">Toutes capacités</option>
                        <option v-for="capacite in uniqueCapacites" :key="capacite" :value="capacite">
                            {{ capacite }}+ personnes
                        </option>
                    </select>
                    
                    <!-- Badge de résultats -->
                    <div class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-sm">
                        <span class="font-medium">{{ filteredRooms.length }}</span>
                        <span class="ml-1 text-gray-600 dark:text-gray-400">
                            {{ filteredRooms.length > 1 ? 'salles' : 'salle' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Liste des salles -->
            <div class="space-y-3">
                <div
                    v-for="room in filteredRooms"
                    :key="room.id"
                    class="p-4 border-2 rounded-lg transition flex gap-4"
                    :class="room.is_active 
                        ? 'border-gray-200 dark:border-gray-700 hover:border-msl-primary/50 hover:bg-gray-50 dark:hover:bg-gray-700'
                        : 'border-red-300 dark:border-red-900 opacity-60'"
                >
                    <!-- Image placeholder -->
                    <div class="w-24 h-24 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center overflow-hidden">
                        <img
                            v-if="room.images && room.images.length > 0"
                            :src="`/storage/${room.images[0].path}`"
                            :alt="room.nom"
                            class="w-full h-full object-cover"
                        />
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>

                    <!-- Contenu -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold">{{ room.nom }}</h3>
                                    <span
                                        v-if="!room.is_active"
                                        class="px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded flex-shrink-0"
                                    >
                                        Inactive
                                    </span>
                                </div>
                                <p v-if="room.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ room.description }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                <Switch 
                                    :model-value="room.is_active"
                                    @update:model-value="() => toggleActive(room)"
                                />
                                <button
                                    @click="editRoom(room.id)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-msl-primary text-white text-sm rounded hover:bg-msl-secondary transition shadow-msl-s cursor-pointer"
                                >
                                    <Pencil class="w-4 h-4" />
                                    Modifier
                                </button>
                            </div>
                        </div>

                        <div class="text-sm space-y-2">
                            <div class="flex items-center gap-4">
                                <span><strong>Capacité:</strong> {{ room.capacite }} personnes</span>
                                <span><strong>Étage:</strong> {{ room.etage }}</span>
                            </div>
                            <div v-if="room.equipement.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="(eq, idx) in room.equipement"
                                    :key="idx"
                                    class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-xs rounded"
                                >
                                    {{ eq }}
                                </span>
                            </div>
                            <div v-if="room.constraints_array && room.constraints_array.length > 0" class="text-orange-600 dark:text-orange-400 mt-2">
                                <ul class="text-sm space-y-1">
                                    <li v-for="(constraint, idx) in room.constraints_array" :key="idx" class="flex items-start gap-1.5">
                                        <span class="mt-0.5">⚠️</span>
                                        <span>{{ constraint }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message si aucune salle -->
            <div
                v-if="filteredRooms.length === 0"
                class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg"
            >
                <p class="text-gray-600 dark:text-gray-400">
                    {{ filterActive === 'all' ? 'Aucune salle disponible' : `Aucune salle ${filterActive === 'active' ? 'active' : 'inactive'}` }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
