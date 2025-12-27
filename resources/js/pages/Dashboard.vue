<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Calendar, Building2, CalendarCheck, CalendarClock, MapPin, Users, Wrench } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard().url,
    },
];

// Fonction pour obtenir les initiales
const getInitials = (name: string): string => {
    const names = name.trim().split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[names.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

// Fonction pour obtenir une couleur d'avatar
const getAvatarColor = (index: number): string => {
    const colors = [
        '#007461', // CA green primary
        '#4F46E5', // Indigo
        '#7C3AED', // Purple
        '#DB2777', // Pink
        '#DC2626', // Red
        '#EA580C', // Orange
        '#059669', // Emerald
        '#0891B2', // Cyan
        '#7C2D12', // Brown
        '#4338CA', // Dark blue
    ];
    return colors[index % colors.length];
};

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
    equipement: string[];
    description: string;
    images?: {
        id: number;
        path: string;
        order: number;
    }[];
}

interface Reservation {
    id: number;
    room_id: number;
    user_id: number;
    date: string;
    heure_debut: string;
    heure_fin: string;
    titre: string | null;
    room: Room;
    user: {
        id: number;
        name: string;
        email: string;
    };
    participants: any[];
}

const rooms = ref<Room[]>([]);
const reservations = ref<Reservation[]>([]);
const loading = ref(true);
const filterCapacite = ref<number | null>(null);
const filterEtage = ref<number | null>(null);
const filterEquipement = ref('');
const selectedRoom = ref<Room | null>(null);
const isRoomDialogOpen = ref(false);

const filteredRooms = computed(() => {
    let filtered = rooms.value;

    if (filterCapacite.value) {
        filtered = filtered.filter(r => r.capacite >= filterCapacite.value!);
    }

    if (filterEtage.value !== null) {
        filtered = filtered.filter(r => r.etage === filterEtage.value);
    }

    if (filterEquipement.value) {
        filtered = filtered.filter(r => 
            r.equipement.some(e => 
                e.toLowerCase().includes(filterEquipement.value.toLowerCase())
            )
        );
    }

    return filtered;
});

// Obtenir la date locale (pas UTC)
const today = (() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
})();

const todayReservations = computed(() => {
    return reservations.value.filter(r => {
        // Extraire la partie date du format ISO (YYYY-MM-DDTHH:MM:SS.000000Z)
        const reservationDate = r.date.split('T')[0];
        return reservationDate === today;
    });
});

const upcomingReservations = computed(() => {
    const now = new Date();
    
    return reservations.value
        .filter(r => {
            // Extraire année, mois, jour de la date (format YYYY-MM-DD)
            const dateParts = r.date.split('-');
            const year = parseInt(dateParts[0]);
            const month = parseInt(dateParts[1]) - 1; // Les mois commencent à 0 en JS
            const day = parseInt(dateParts[2]);
            
            // Extraire heures et minutes de l'heure de fin (format HH:MM)
            const timeParts = r.heure_fin.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = parseInt(timeParts[1]);
            
            // Créer l'objet Date de fin de réservation
            const reservationEnd = new Date(year, month, day, hours, minutes);
            
            // Retourner true si la réservation se termine dans le futur
            return reservationEnd > now;
        })
        .sort((a, b) => {
            // Trier par date puis par heure de début
            if (a.date !== b.date) {
                return a.date.localeCompare(b.date);
            }
            return a.heure_debut.localeCompare(b.heure_debut);
        });
});

const etages = computed(() => {
    return [...new Set(rooms.value.map(r => r.etage))].sort((a, b) => a - b);
});

onMounted(async () => {
    try {
        const [roomsRes, reservationsRes] = await Promise.all([
            axios.get('/api/rooms'),
            axios.get('/api/reservations')
        ]);
        
        rooms.value = roomsRes.data;
        reservations.value = reservationsRes.data;
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    } finally {
        loading.value = false;
    }
});

const clearFilters = () => {
    filterCapacite.value = null;
    filterEtage.value = null;
    filterEquipement.value = '';
};

const openRoomDetails = (room: Room) => {
    selectedRoom.value = room;
    isRoomDialogOpen.value = true;
};
</script>

<template>
    <Head title="Tableau de bord" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
            <!-- En-tête -->
            <header class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Tableau de bord</h1>
                <Button 
                    @click="router.visit('/reservations/create')"
                    class="bg-msl-primary hover:bg-msl-secondary text-white shadow-msl-s"
                    aria-label="Créer une nouvelle réservation"
                >
                    <Calendar class="w-4 h-4 mr-2" aria-hidden="true" />
                    Nouvelle réservation
                </Button>
            </header>

            <!-- Statistiques -->
            <section aria-label="Statistiques rapides">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-msl-s" role="article" aria-label="Salles disponibles">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Salles disponibles</div>
                            <div class="p-2 bg-msl-primary/10 rounded-lg" aria-hidden="true">
                                <Building2 class="w-5 h-5 text-msl-primary" />
                            </div>
                        </div>
                        <div class="text-3xl font-bold">{{ rooms.length }}</div>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-msl-s" role="article" aria-label="Réservations aujourd'hui">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Réservations aujourd'hui</div>
                            <div class="p-2 bg-msl-primary/10 rounded-lg" aria-hidden="true">
                                <CalendarCheck class="w-5 h-5 text-msl-primary" />
                            </div>
                        </div>
                        <div class="text-3xl font-bold">{{ todayReservations.length }}</div>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-msl-s" role="article" aria-label="Réservations à venir">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Réservations à venir</div>
                            <div class="p-2 bg-msl-primary/10 rounded-lg" aria-hidden="true">
                                <CalendarClock class="w-5 h-5 text-msl-primary" />
                            </div>
                        </div>
                        <div class="text-3xl font-bold">{{ upcomingReservations.length }}</div>
                    </div>
                </div>
            </section>

            <!-- Layout en 2 colonnes pour desktop -->
            <div class="grid gap-4 lg:grid-cols-2 mt-8">
                <!-- Colonne gauche: Salles (50%) -->
                <section class="bg-white dark:bg-gray-800 rounded-xl p-4" aria-labelledby="salles-heading">
                    <h2 id="salles-heading" class="text-lg font-semibold mb-4">Salles disponibles ({{ filteredRooms.length }})</h2>
                    
                    <!-- Filtres intégrés -->
                    <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700" role="search" aria-label="Filtres de recherche de salles">
                        <div class="grid gap-3 md:grid-cols-4">
                            <div>
                                <label for="filter-capacite" class="block text-xs font-medium mb-1">Capacité min.</label>
                                <input
                                    id="filter-capacite"
                                    v-model.number="filterCapacite"
                                    type="number"
                                    aria-label="Filtrer par capacité minimale"
                                    min="1"
                                    class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:border-gray-600"
                                    placeholder="Ex: 6"
                                />
                            </div>
                            <div>
                                <label for="filter-etage" class="block text-xs font-medium mb-1">Étage</label>
                                <select
                                    id="filter-etage"
                                    v-model.number="filterEtage"
                                    class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:border-gray-600"
                                    aria-label="Filtrer par étage"
                                >
                                    <option :value="null">Tous</option>
                                    <option v-for="etage in etages" :key="etage" :value="etage">
                                        Étage {{ etage }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="filter-equipement" class="block text-xs font-medium mb-1">Équipement</label>
                                <input
                                    id="filter-equipement"
                                    v-model="filterEquipement"
                                    type="text"
                                    class="w-full px-2 py-1.5 text-sm border rounded dark:bg-gray-700 dark:border-gray-600"
                                    placeholder="Rechercher..."
                                    aria-label="Filtrer par équipement"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    @click="clearFilters"
                                    class="w-full px-3 py-1.5 text-sm bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500 transition cursor-pointer"
                                    aria-label="Réinitialiser tous les filtres"
                                >
                                    Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des salles -->
                    <div v-if="loading" class="text-center py-8" role="status" aria-live="polite">Chargement...</div>
                    <div v-else-if="filteredRooms.length === 0" class="text-center py-8 text-gray-500" role="status" aria-live="polite">
                        Aucune salle ne correspond aux critères.
                    </div>
                    <div v-else class="grid gap-3 md:grid-cols-2" role="list" aria-label="Liste des salles disponibles">
                        <article
                            v-for="room in filteredRooms"
                            :key="room.id"
                            @click="openRoomDetails(room)"
                            @keydown.enter="openRoomDetails(room)"
                            @keydown.space.prevent="openRoomDetails(room)"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-msl-s hover:shadow-msl-m transition overflow-hidden cursor-pointer focus:outline-none focus:ring-2 focus:ring-msl-primary focus:ring-offset-2"
                            role="listitem button"
                            tabindex="0"
                            :aria-label="`Voir les détails de la salle ${room.nom}, capacité ${room.capacite} personnes, étage ${room.etage}`"
                        >
                            <!-- Image de la salle -->
                            <div class="h-32 bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="room.images && room.images.length > 0"
                                    :src="`/storage/${room.images[0].path}`"
                                    :alt="room.nom"
                                    class="w-full h-full object-cover"
                                />
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </div>
                            <div class="p-3">
                                <h3 class="font-semibold">{{ room.nom }}</h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ room.description }}</p>
                                <div class="mt-2 space-y-1 text-xs">
                                    <div><strong>Capacité:</strong> {{ room.capacite }} pers. | <strong>Étage:</strong> {{ room.etage }}</div>
                                    <div v-if="room.equipement.length > 0">
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <span
                                                v-for="(eq, idx) in room.equipement"
                                                :key="idx"
                                                class="px-1.5 py-0.5 bg-msl-primary/10 text-msl-primary text-xs rounded"
                                            >
                                                {{ eq }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- Colonne droite: Réservations à venir (50%) -->
                <div class="bg-msl-grey dark:bg-gray-800 rounded-xl p-4">
                    <h2 class="text-lg font-semibold mb-4">Réservations à venir</h2>
                    <div v-if="loading" class="text-center py-8">Chargement...</div>
                    <div v-else-if="upcomingReservations.length === 0" class="text-center py-8 text-gray-500 text-sm">
                        Aucune réservation à venir.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="text-left py-2 px-2">Titre</th>
                                    <th class="text-left py-2 px-2">Date & Heure</th>
                                    <th class="text-left py-2 px-2">Salle</th>
                                    <th class="text-left py-2 px-2">Participants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(reservation, index) in upcomingReservations.slice(0, 15)"
                                    :key="reservation.id"
                                    class="border-b border-gray-100 dark:border-gray-700"
                                >
                                    <td class="py-2 px-2">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ reservation.titre || 'Sans titre' }}
                                        </div>
                                    </td>
                                    <td class="py-2 px-2 whitespace-nowrap text-xs">
                                        <div>{{ new Date(reservation.date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) }}</div>
                                        <div class="text-gray-500">{{ reservation.heure_debut }} - {{ reservation.heure_fin }}</div>
                                    </td>
                                    <td class="py-2 px-2">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-msl-primary/10 text-msl-primary text-center">
                                            {{ reservation.room.nom }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-2">
                                        <div class="flex items-center gap-2">
                                            <!-- Avatar du créateur -->
                                            <div class="relative group">
                                                <div 
                                                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold"
                                                    :style="{ backgroundColor: getAvatarColor(index) }"
                                                >
                                                    {{ getInitials(reservation.user.name) }}
                                                </div>
                                                <!-- Tooltip -->
                                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                                    {{ reservation.user.name }}
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                            <!-- Autres participants -->
                                            <div v-if="reservation.participants && reservation.participants.length > 0" class="flex -space-x-2">
                                                <div 
                                                    v-for="(participant, pIndex) in reservation.participants.slice(0, 2)"
                                                    :key="participant.id"
                                                    class="relative group"
                                                >
                                                    <div 
                                                        class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold border-2 border-white dark:border-gray-800"
                                                        :style="{ backgroundColor: getAvatarColor(index + pIndex + 1) }"
                                                    >
                                                        {{ getInitials(participant.name) }}
                                                    </div>
                                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                                        {{ participant.name }}
                                                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></div>
                                                    </div>
                                                </div>
                                                <div 
                                                    v-if="reservation.participants.length > 2"
                                                    class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold border-2 border-white dark:border-gray-800"
                                                >
                                                    +{{ reservation.participants.length - 2 }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bouton voir toutes les réservations -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a 
                            href="/admin/reservations"
                            class="block w-full text-center px-4 py-2 text-sm font-medium text-msl-primary hover:bg-msl-primary/10 rounded-lg transition"
                        >
                            Voir toutes les réservations →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog de détails de la salle -->
        <Dialog v-model:open="isRoomDialogOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ selectedRoom?.nom }}</DialogTitle>
                    <DialogDescription>
                        Détails de la salle et informations de réservation
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedRoom" class="space-y-4">
                    <!-- Image de la salle -->
                    <div v-if="selectedRoom.images && selectedRoom.images.length > 0" class="rounded-lg overflow-hidden">
                        <img
                            :src="`/storage/${selectedRoom.images[0].path}`"
                            :alt="selectedRoom.nom"
                            class="w-full h-64 object-cover"
                        />
                    </div>

                    <!-- Informations principales -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2 text-sm">
                            <Users class="w-4 h-4 text-msl-primary" />
                            <span><strong>Capacité:</strong> {{ selectedRoom.capacite }} personnes</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <MapPin class="w-4 h-4 text-msl-primary" />
                            <span><strong>Étage:</strong> {{ selectedRoom.etage }}</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="selectedRoom.description" class="text-sm">
                        <strong>Description:</strong>
                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ selectedRoom.description }}</p>
                    </div>

                    <!-- Équipements -->
                    <div v-if="selectedRoom.equipement && selectedRoom.equipement.length > 0">
                        <div class="flex items-center gap-2 mb-2">
                            <Wrench class="w-4 h-4 text-msl-primary" />
                            <strong class="text-sm">Équipements disponibles:</strong>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(eq, idx) in selectedRoom.equipement"
                                :key="idx"
                                class="px-3 py-1 bg-msl-primary/10 text-msl-primary text-sm rounded-full"
                            >
                                {{ eq }}
                            </span>
                        </div>
                    </div>

                    <!-- Bouton de fermeture -->
                    <div class="flex justify-end pt-4 border-t">
                        <Button
                            variant="outline"
                            @click="isRoomDialogOpen = false"
                        >
                            Fermer
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
