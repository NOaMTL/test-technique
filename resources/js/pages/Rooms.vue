<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { toast } from '@/components/ui/toast';
import { Star } from 'lucide-vue-next';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import RoomCard from '@/components/rooms/RoomCard.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: 'Salles',
        href: '/rooms',
    },
];

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
    equipement: string[];
    description: string;
    is_favorite?: boolean;
}

const rooms = ref<Room[]>([]);
const loading = ref(true);

const loadRooms = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/rooms');
        rooms.value = response.data;
    } catch (error) {
        console.error('Erreur lors du chargement des salles:', error);
        toast.error('Erreur lors du chargement des salles');
    } finally {
        loading.value = false;
    }
};

const toggleFavorite = async (room: Room) => {
    try {
        const response = await axios.post(`/api/rooms/${room.id}/favorite`);
        room.is_favorite = response.data.is_favorite;
        
        if (response.data.is_favorite) {
            toast.success('Salle ajoutée aux favoris');
        } else {
            toast.success('Salle retirée des favoris');
        }
        
        await loadRooms();
    } catch (error) {
        console.error('Erreur lors de la modification du favori:', error);
        toast.error('Erreur lors de la modification du favori');
    }
};

const favoriteRooms = computed(() => {
    return rooms.value.filter(r => r.is_favorite);
});

const otherRooms = computed(() => {
    return rooms.value.filter(r => !r.is_favorite);
});

onMounted(loadRooms);
</script>

<template>
    <Head title="Salles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Salles de réunion</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ favoriteRooms.length }} salle{{ favoriteRooms.length > 1 ? 's' : '' }} favorite{{ favoriteRooms.length > 1 ? 's' : '' }}
                    </p>
                </div>
            </div>

            <LoadingSpinner v-if="loading" size="lg" text="Chargement des salles..." />

            <template v-else>
                <!-- Salles favorites -->
                <div v-if="favoriteRooms.length > 0">
                    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
                        Mes salles favorites
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <RoomCard
                            v-for="room in favoriteRooms"
                            :key="room.id"
                            :room="room"
                            :show-favorite="true"
                            :compact="true"
                            @toggleFavorite="toggleFavorite(room)"
                        />
                    </div>
                </div>

                <!-- Toutes les salles -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">
                        Toutes les salles ({{ otherRooms.length }})
                    </h2>
                    <div v-if="otherRooms.length === 0" class="text-center py-8 text-gray-500">
                        Toutes les salles sont dans vos favoris !
                    </div>
                    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <RoomCard
                            v-for="room in otherRooms"
                            :key="room.id"
                            :room="room"
                            :show-favorite="true"
                            :compact="true"
                            @toggleFavorite="toggleFavorite(room)"
                        />
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
