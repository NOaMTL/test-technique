<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/toast';
import { Calendar, CalendarCheck, CalendarClock, User } from 'lucide-vue-next';
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ReservationCard from '@/components/reservations/ReservationCard.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard().url,
    },
];

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
    equipement: string[];
    description: string;
}

interface Reservation {
    id: number;
    room_id: number;
    user_id: number;
    date: string;
    heure_debut: string;
    heure_fin: string;
    titre: string | null;
    description?: string | null;
    status?: 'pending' | 'confirmed' | 'cancelled';
    created_at?: string;
    updated_at?: string;
    room: Room;
    user: {
        id: number;
        name: string;
        email: string;
    };
    participants: any[];
}

const reservations = ref<Reservation[]>([]);
const loading = ref(true);

// Obtenir la date locale (pas UTC)
const today = (() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
})();

const myReservations = computed(() => {
    return reservations.value;
});

const todayReservations = computed(() => {
    return reservations.value.filter(r => {
        const reservationDate = r.date.split('T')[0];
        return reservationDate === today;
    });
});

const upcomingReservations = computed(() => {
    const now = new Date();
    
    return reservations.value
        .filter(r => {
            const dateParts = r.date.split('-');
            const year = parseInt(dateParts[0]);
            const month = parseInt(dateParts[1]) - 1;
            const day = parseInt(dateParts[2]);
            
            const timeParts = r.heure_fin.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = parseInt(timeParts[1]);
            
            const reservationEnd = new Date(year, month, day, hours, minutes);
            
            return reservationEnd > now;
        })
        .sort((a, b) => {
            if (a.date !== b.date) {
                return a.date.localeCompare(b.date);
            }
            return a.heure_debut.localeCompare(b.heure_debut);
        });
});

const handleEdit = (reservation: any) => {
    router.visit(`/reservations/${reservation.id}/edit`);
};

const handleDelete = async (reservation: any) => {
    try {
        await axios.delete(`/api/reservations/${reservation.id}`);
        toast.success('Réservation annulée avec succès');
        const response = await axios.get('/api/reservations/user');
        reservations.value = response.data.data || response.data;
    } catch (error: any) {
        console.error('Erreur lors de l\'annulation:', error);
        toast.error(error.response?.data?.message || 'Erreur lors de l\'annulation de la réservation');
    }
};

onMounted(async () => {
    try {
        const response = await axios.get('/api/reservations/user');
        reservations.value = response.data.data || response.data;
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="Mon tableau de bord" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Mon tableau de bord</h1>
                <Button 
                    @click="router.visit('/reservations/create')"
                    class="bg-msl-primary hover:bg-msl-secondary text-white shadow-msl-s"
                >
                    <Calendar class="w-4 h-4 mr-2" />
                    Nouvelle réservation
                </Button>
            </div>

            <!-- Statistiques -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-msl-s">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Mes réservations</div>
                        <div class="p-2 bg-msl-primary/10 rounded-lg">
                            <User class="w-5 h-5 text-msl-primary" />
                        </div>
                    </div>
                    <div class="text-3xl font-bold">{{ myReservations.length }}</div>
                </div>
                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-msl-s">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Aujourd'hui</div>
                        <div class="p-2 bg-msl-primary/10 rounded-lg">
                            <CalendarCheck class="w-5 h-5 text-msl-primary" />
                        </div>
                    </div>
                    <div class="text-3xl font-bold">{{ todayReservations.length }}</div>
                </div>
                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-msl-s">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400">À venir</div>
                        <div class="p-2 bg-msl-primary/10 rounded-lg">
                            <CalendarClock class="w-5 h-5 text-msl-primary" />
                        </div>
                    </div>
                    <div class="text-3xl font-bold">{{ upcomingReservations.length }}</div>
                </div>
            </div>

            <!-- Mes réservations à venir -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-msl-s">
                <h2 class="text-lg font-semibold mb-4">Mes réservations à venir</h2>
                
                <LoadingSpinner v-if="loading" size="md" text="Chargement des réservations..." />
                
                <EmptyState 
                    v-else-if="upcomingReservations.length === 0" 
                    :icon="Calendar"
                    title="Aucune réservation à venir"
                    description="Vous n'avez aucune réservation planifiée pour le moment."
                >
                    <Button 
                        @click="router.visit('/reservations/create')"
                        class="bg-msl-primary hover:bg-msl-secondary text-white"
                    >
                        <Calendar class="w-4 h-4 mr-2" />
                        Créer une réservation
                    </Button>
                </EmptyState>
                
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ReservationCard
                        v-for="reservation in upcomingReservations"
                        :key="reservation.id"
                        :reservation="reservation"
                        :show-actions="true"
                        @edit="handleEdit"
                        @delete="handleDelete"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
