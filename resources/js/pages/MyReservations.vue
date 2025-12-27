<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/toast';
import { Calendar } from 'lucide-vue-next';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ReservationCard from '@/components/reservations/ReservationCard.vue';
import ReservationFilters from '@/components/reservations/ReservationFilters.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: 'Mes réservations',
        href: '/my-reservations',
    },
];

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Reservation {
    id: number;
    user_id: number;
    room_id: number;
    date: string;
    heure_debut: string;
    heure_fin: string;
    titre: string | null;
    description: string | null;
    nombre_personnes: number | null;
    status?: 'pending' | 'confirmed' | 'cancelled';
    created_at?: string;
    updated_at?: string;
    room: Room;
    participants: User[];
}

const reservations = ref<Reservation[]>([]);
const rooms = ref<Room[]>([]);
const loading = ref(true);
const currentPage = ref(1);
const itemsPerPage = 10;
const paginationMeta = ref<PaginationMeta | null>(null);

// Filtres
const filters = ref({
    roomIds: [] as number[],
    search: '',
    dateStart: '',
    dateEnd: '',
});
const showFilters = ref(false);

const loadReservations = async () => {
    loading.value = true;
    try {
        const [roomsRes] = await Promise.all([
            axios.get('/api/rooms')
        ]);
        rooms.value = roomsRes.data;
        
        await loadReservationsWithFilters();
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    } finally {
        loading.value = false;
    }
};

const loadReservationsWithFilters = async () => {
    loading.value = true;
    try {
        const params: any = {
            page: currentPage.value,
            per_page: itemsPerPage,
        };

        // Ajouter les filtres
        if (filters.value.roomIds.length > 0) {
            params.room_ids = filters.value.roomIds;
        }
        if (filters.value.dateStart) {
            params.date_start = filters.value.dateStart;
        }
        if (filters.value.dateEnd) {
            params.date_end = filters.value.dateEnd;
        }
        if (filters.value.search) {
            params.search = filters.value.search;
        }

        const response = await axios.get('/api/reservations/user', { params });
        reservations.value = response.data.data;
        paginationMeta.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to,
        };
    } catch (error) {
        console.error('Erreur lors du chargement des réservations:', error);
    } finally {
        loading.value = false;
    }
};

const totalPages = computed(() => {
    return paginationMeta.value?.last_page ?? 1;
});

const clearFilters = () => {
    filters.value = {
        roomIds: [],
        search: '',
        dateStart: '',
        dateEnd: '',
    };
    currentPage.value = 1;
    loadReservationsWithFilters();
};

const handleEdit = (reservation: any) => {
    router.visit(`/reservations/${reservation.id}/edit`);
};

const handleDelete = async (reservation: any) => {
    try {
        await axios.delete(`/api/reservations/${reservation.id}`);
        toast.success('Réservation supprimée avec succès');
        await loadReservationsWithFilters();
    } catch (error: any) {
        console.error('Erreur lors de la suppression:', error);
        toast.error(error.response?.data?.message || 'Erreur lors de la suppression');
    }
};

// Watchers
watch(filters, () => {
    currentPage.value = 1;
    loadReservationsWithFilters();
}, { deep: true });

watch(currentPage, () => {
    loadReservationsWithFilters();
});

// Lifecycle hooks
onMounted(loadReservations);

</script>

<template>
    <Head title="Mes réservations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Mes réservations</h1>
                <Button 
                    @click="router.visit('/reservations/create')"
                    class="bg-msl-primary hover:bg-msl-secondary text-white"
                >
                    <Calendar class="w-4 h-4 mr-2" />
                    Nouvelle réservation
                </Button>
            </div>

            <Card class="border-0 shadow-msl-s">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Liste des réservations ({{ paginationMeta?.total ?? 0 }})</CardTitle>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showFilters = !showFilters"
                        >
                            Filtres
                        </Button>
                    </div>
                </CardHeader>
                
                <CardContent>
                    <!-- Panneau de filtres -->
                    <div v-if="showFilters" class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <ReservationFilters
                            :rooms="rooms"
                            :filters="filters"
                            :loading="loading"
                            @update:filters="filters = $event"
                            @clear="clearFilters"
                        />
                    </div>

                    <LoadingSpinner v-if="loading" size="md" text="Chargement des réservations..." />
                    
                    <EmptyState 
                        v-else-if="reservations.length === 0" 
                        :icon="Calendar"
                        title="Aucune réservation"
                        description="Aucune réservation ne correspond aux critères de recherche."
                    >
                        <Button @click="clearFilters" variant="outline">
                            Réinitialiser les filtres
                        </Button>
                    </EmptyState>
                    
                    <div v-else>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <ReservationCard
                                v-for="reservation in reservations"
                                :key="reservation.id"
                                :reservation="reservation"
                                :show-actions="true"
                                @edit="handleEdit"
                                @delete="handleDelete"
                            />
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Affichage de {{ paginationMeta?.from ?? 0 }} à {{ paginationMeta?.to ?? 0 }} sur {{ paginationMeta?.total ?? 0 }} réservation{{ (paginationMeta?.total ?? 0) > 1 ? 's' : '' }}
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === 1"
                                    @click="currentPage--"
                                >
                                    Précédent
                                </Button>
                                <Button
                                    v-for="page in Math.min(totalPages, 5)"
                                    :key="page"
                                    :variant="page === currentPage ? 'default' : 'outline'"
                                    size="sm"
                                    @click="currentPage = page"
                                    :class="page === currentPage ? 'bg-msl-primary hover:bg-msl-secondary' : ''"
                                >
                                    {{ page }}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === totalPages"
                                    @click="currentPage++"
                                >
                                    Suivant
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
