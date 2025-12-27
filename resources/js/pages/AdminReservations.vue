<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { MultiSelect } from '@/components/ui/multi-select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { toast } from '@/components/ui/toast';
import { Calendar, List, ChevronLeft, ChevronRight, Edit, User, MapPin, Clock, Users, Trash2 } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: 'Réservations',
        href: '/admin/reservations',
    },
];

interface RoomImage {
    id: number;
    path: string;
    order: number;
}

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage?: number;
    images?: RoomImage[];
}

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface Reservation {
    id: number;
    room_id: number;
    user_id: number;
    date: string;
    heure_debut: string;
    heure_fin: string;
    titre: string | null;
    description: string | null;
    nombre_personnes: number | null;
    created_at: string;
    room?: Room;
    user?: User;
    participants?: User[];
}

const reservations = ref<Reservation[]>([]);
const rooms = ref<Room[]>([]);
const loading = ref(true);
const activeTab = ref('list');
const selectedRoomIds = ref<number[]>([]);
const currentDate = ref(new Date());
const selectedReservation = ref<Reservation | null>(null);
const isDialogOpen = ref(false);
const isDayReservationsDialogOpen = ref(false);
const selectedDayReservations = ref<Reservation[]>([]);
const selectedDayDate = ref<string>('');
const currentListPage = ref(1);
const itemsPerPage = 10;

const currentMonth = computed(() => currentDate.value.getMonth() + 1); // 1-12
const currentYear = computed(() => currentDate.value.getFullYear());

const { getInitials } = useInitials();

// Charger les réservations avec filtres
const loadReservations = async () => {
    loading.value = true;
    try {
        const params: any = {
            month: currentMonth.value,
            year: currentYear.value,
        };

        // Si des salles sont sélectionnées, les passer en tableau
        if (selectedRoomIds.value.length > 0) {
            // Laravel accepte room_id[] pour un tableau
            selectedRoomIds.value.forEach((id, index) => {
                params[`room_ids[${index}]`] = id;
            });
        }

        const response = await axios.get('/api/reservations', { params });
        reservations.value = response.data;
        currentListPage.value = 1; // Réinitialiser la page de la liste
    } catch (error) {
        console.error('Erreur lors du chargement des réservations:', error);
    } finally {
        loading.value = false;
    }
};

// Charger les salles une seule fois
onMounted(async () => {
    try {
        const roomsResponse = await axios.get('/api/rooms');
        rooms.value = roomsResponse.data;
        await loadReservations();
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
        loading.value = false;
    }
});

// Recharger quand les filtres changent
watch([selectedRoomIds, currentMonth, currentYear], () => {
    loadReservations();
}, { deep: true });

// Recharger quand on change d'onglet
watch(activeTab, () => {
    currentListPage.value = 1;
});

// Options pour le MultiSelect
const roomOptions = computed(() => {
    return [...rooms.value]
        .sort((a, b) => (a.etage || 0) - (b.etage || 0) || a.nom.localeCompare(b.nom))
        .map(room => ({
            value: room.id,
            label: room.nom,
            description: `Capacité: ${room.capacite} personnes`,
            group: `Étage ${room.etage || 0}`,
            imageUrl: room.images && room.images.length > 0 ? `/storage/${room.images[0].path}` : undefined,
        }));
});

// Pagination frontend pour la vue liste
const paginatedReservations = computed(() => {
    const start = (currentListPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return reservations.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(reservations.value.length / itemsPerPage);
});

// Calendrier: Organiser les réservations par date
const reservationsByDate = computed(() => {
    const grouped: Record<string, Reservation[]> = {};
    reservations.value.forEach(reservation => {
        // Normaliser la date au format YYYY-MM-DD
        const dateStr = typeof reservation.date === 'string' 
            ? reservation.date.split('T')[0] 
            : reservation.date;
        
        if (!grouped[dateStr]) {
            grouped[dateStr] = [];
        }
        grouped[dateStr].push(reservation);
    });
    return grouped;
});

const daysInMonth = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value - 1; // JavaScript months are 0-indexed
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];
    
    // Jours avant le 1er du mois pour remplir la grille
    const firstDayOfWeek = firstDay.getDay();
    const daysFromPrevMonth = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1;
    
    for (let i = daysFromPrevMonth; i > 0; i--) {
        const date = new Date(year, month, 1 - i);
        days.push({ date, isCurrentMonth: false });
    }
    
    // Jours du mois actuel
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        days.push({ date, isCurrentMonth: true });
    }
    
    // Jours après la fin du mois pour compléter la grille
    const remainingDays = 42 - days.length; // Grille de 6 semaines
    for (let i = 1; i <= remainingDays; i++) {
        const date = new Date(year, month + 1, i);
        days.push({ date, isCurrentMonth: false });
    }
    
    return days;
});

const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

const formatDate = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getReservationsForDate = (date: Date): Reservation[] => {
    const dateStr = formatDate(date);
    return reservationsByDate.value[dateStr] || [];
};

const previousMonth = () => {
    const newDate = new Date(currentDate.value);
    newDate.setMonth(newDate.getMonth() - 1);
    currentDate.value = newDate;
};

const nextMonth = () => {
    const newDate = new Date(currentDate.value);
    newDate.setMonth(newDate.getMonth() + 1);
    currentDate.value = newDate;
};

const goToListPage = (page: number) => {
    currentListPage.value = page;
};



const getStatusBadgeClass = (reservation: Reservation): string => {
    const now = new Date();
    const resDate = new Date(`${reservation.date}T${reservation.heure_fin}`);
    
    if (resDate < now) {
        return 'bg-gray-400';
    }
    return 'bg-msl-primary hover:bg-msl-secondary';
};

const openReservationDetails = (reservation: Reservation) => {
    selectedReservation.value = reservation;
    isDialogOpen.value = true;
};

const showAllDayReservations = (date: Date, reservations: Reservation[]) => {
    selectedDayReservations.value = reservations;
    selectedDayDate.value = formatFullDate(date.toISOString().split('T')[0]);
    isDayReservationsDialogOpen.value = true;
};

const editReservation = () => {
    if (selectedReservation.value) {
        router.visit(`/reservations/${selectedReservation.value.id}/edit`);
    }
};

const deleteReservation = async () => {
    if (!selectedReservation.value) return;

    try {
        await axios.delete(`/api/reservations/${selectedReservation.value.id}`);
        toast.success('Réservation annulée avec succès');
        isDialogOpen.value = false;
        // Recharger les données
        await loadReservations();
    } catch (error: any) {
        console.error('Erreur lors de l\'annulation:', error);
        toast.error(error.response?.data?.message || 'Erreur lors de l\'annulation de la réservation');
    }
};

const createReservation = () => {
    router.visit('/reservations/create');
};

const formatFullDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

// Couleur principale CA - vert sombre
const CA_GREEN_DARK = getComputedStyle(document.documentElement).getPropertyValue('--msl-bg-brand-primary').trim();

const getAvatarColor = (index: number): string => {
    const colors = [
        CA_GREEN_DARK, // CA green dark
        '#3B82F6', // blue-500
        '#A855F7', // purple-500
        '#EC4899', // pink-500
        '#F97316', // orange-500
        '#14B8A6', // teal-500
        '#6366F1', // indigo-500
        '#EF4444', // red-500
        '#10B981', // emerald-500
        '#F59E0B', // amber-500
        '#8B5CF6', // violet-500
        '#06B6D4', // cyan-500
    ];
    return colors[index % colors.length];
};
</script>

<template>
    <Head title="Réservations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full h-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Réservations</h1>
            </div>

            <Tabs v-model="activeTab" class="w-full">
                <div class="flex items-center justify-between mb-6">
                    <TabsList class="grid grid-cols-2">
                        <TabsTrigger value="list" class="cursor-pointer">
                            <List class="w-4 h-4 mr-2" />
                            Liste
                        </TabsTrigger>
                        <TabsTrigger value="calendar" class="cursor-pointer">
                            <Calendar class="w-4 h-4 mr-2" />
                            Calendrier
                        </TabsTrigger>
                    </TabsList>

                    <Button 
                        @click="createReservation"
                        class="bg-[#007461] hover:bg-[#009980] text-white cursor-pointer"
                    >
                        <Calendar class="w-4 h-4 mr-2" />
                        Créer une réservation
                    </Button>
                </div>

                <!-- Filtres communs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Filtrer par salle</label>
                        <MultiSelect
                            v-model="selectedRoomIds"
                            :options="roomOptions"
                            placeholder="Toutes les salles"
                            search-placeholder="Rechercher une salle..."
                            class="cursor-pointer"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Mois</label>
                        <div class="flex gap-2">
                            <button 
                                @click="previousMonth"
                                class="px-3 py-2 bg-msl-primary text-white rounded hover:bg-msl-secondary transition shadow-msl-s cursor-pointer"
                            >
                                <ChevronLeft class="w-5 h-5" />
                            </button>
                            <div class="flex-1 px-4 py-2 border rounded bg-gray-50 text-center font-medium">
                                {{ monthNames[currentMonth - 1] }} {{ currentYear }}
                            </div>
                            <button 
                                @click="nextMonth"
                                class="px-3 py-2 bg-msl-primary text-white rounded hover:bg-msl-secondary transition shadow-msl-s cursor-pointer"
                            >
                                <ChevronRight class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vue Calendrier -->
                <TabsContent value="calendar">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle class="text-xl">
                                Calendrier - {{ monthNames[currentMonth - 1] }} {{ currentYear }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loading" class="text-center py-8">
                                Chargement...
                            </div>
                            <div v-else>
                                <!-- En-têtes des jours -->
                                <div class="grid grid-cols-7 gap-2 mb-2">
                                    <div v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']" 
                                         :key="day"
                                         class="text-center font-semibold text-sm text-gray-600 py-2"
                                    >
                                        {{ day }}
                                    </div>
                                </div>
                                
                                <!-- Grille du calendrier -->
                                <div class="grid grid-cols-7 gap-2">
                                    <div 
                                        v-for="(day, index) in daysInMonth" 
                                        :key="index"
                                        class="min-h-[140px] p-2 border rounded"
                                        :class="{
                                            'bg-gray-50 invisible': !day.isCurrentMonth,
                                            'bg-white': day.isCurrentMonth,
                                            'border-msl-primary border-2': formatDate(day.date) === formatDate(new Date())
                                        }"
                                    >
                                        <div 
                                            class="text-sm font-medium mb-1 flex justify-between items-center"
                                            :class="{
                                                'text-gray-400': !day.isCurrentMonth,
                                                'text-gray-900': day.isCurrentMonth
                                            }"
                                        >
                                            <span>{{ day.date.getDate() }}</span>
                                            <span v-if="getReservationsForDate(day.date).length > 0" class="text-xs bg-msl-primary text-white rounded-full px-1.5">
                                                {{ getReservationsForDate(day.date).length }}
                                            </span>
                                        </div>
                                        
                                        <div class="space-y-1">
                                            <div 
                                                v-for="reservation in getReservationsForDate(day.date).slice(0, 2)" 
                                                :key="reservation.id"
                                                @click="openReservationDetails(reservation)"
                                                class="text-xs p-1.5 rounded cursor-pointer transition-transform hover:scale-105"
                                                :class="getStatusBadgeClass(reservation)"
                                                :title="`Cliquer pour voir les détails`"
                                            >
                                                <div class="text-white font-semibold truncate text-[11px]">
                                                    {{ reservation.titre || reservation.user?.name }}
                                                </div>
                                                <div class="text-white text-[10px] truncate opacity-90">
                                                    {{ reservation.heure_debut }} - {{ reservation.heure_fin }}
                                                </div>
                                                <div v-if="selectedRoomIds.length === 0" class="text-white text-[10px] truncate opacity-75">
                                                    {{ reservation.room?.nom }}
                                                </div>
                                            </div>
                                            
                                            <!-- Bouton "+ X réservations" -->
                                            <div 
                                                v-if="getReservationsForDate(day.date).length > 2"
                                                @click="showAllDayReservations(day.date, getReservationsForDate(day.date))"
                                                class="text-xs p-1.5 rounded cursor-pointer bg-gray-200 hover:bg-gray-300 transition text-center border border-gray-300"
                                            >
                                                <div class="text-gray-700 font-medium text-[11px]">
                                                    + {{ getReservationsForDate(day.date).length - 2 }} réservation{{ getReservationsForDate(day.date).length - 2 > 1 ? 's' : '' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Vue Liste -->
                <TabsContent value="list">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle>Toutes les réservations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loading" class="text-center py-8">
                                Chargement...
                            </div>
                            <div v-else-if="reservations.length === 0" class="text-center py-8 text-gray-500">
                                Aucune réservation
                            </div>
                            <div v-else>
                                <!-- Tableau des réservations -->
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b-2 border-gray-200">
                                                <th class="text-left py-3 px-4 font-semibold text-sm text-gray-700">Titre / Salle</th>
                                                <th class="text-left py-3 px-4 font-semibold text-sm text-gray-700">Réservé par</th>
                                                <th class="text-left py-3 px-4 font-semibold text-sm text-gray-700">Participants</th>
                                                <th class="text-left py-3 px-4 font-semibold text-sm text-gray-700">Date et horaires</th>
                                                <th class="text-center py-3 px-4 font-semibold text-sm text-gray-700">Personnes</th>
                                                <th class="text-center py-3 px-4 font-semibold text-sm text-gray-700">Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="reservation in paginatedReservations" 
                                                :key="reservation.id"
                                                @click="openReservationDetails(reservation)"
                                                class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition"
                                            >
                                                <td class="py-3 px-4">
                                                    <div class="font-medium text-gray-900">{{ reservation.titre || 'Sans titre' }}</div>
                                                    <div class="text-xs text-gray-500">{{ reservation.room?.nom }} ({{ reservation.room?.capacite }} pers.)</div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex items-center gap-3">
                                                        <!-- Avatar -->
                                                        <div 
                                                            class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0"
                                                            :style="{ backgroundColor: CA_GREEN_DARK }"
                                                        >
                                                            {{ getInitials(reservation.user?.name || '') }}
                                                        </div>
                                                        <!-- Text -->
                                                        <div class="min-w-0 flex-1">
                                                            <div class="font-medium text-gray-900 truncate">{{ reservation.user?.name }}</div>
                                                            <div class="text-xs text-gray-500 truncate">{{ reservation.user?.email }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div v-if="reservation.participants && reservation.participants.length > 0" class="flex items-center">
                                                        <!-- Avatar group stacked -->
                                                        <div class="flex -space-x-2">
                                                            <div
                                                                v-for="(participant, index) in reservation.participants.slice(0, 3)"
                                                                :key="participant.id"
                                                                :style="{ backgroundColor: getAvatarColor(index) }"
                                                                class="relative w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold border-2 border-white shadow-sm cursor-help group"
                                                            >
                                                                {{ getInitials(participant.name) }}
                                                                <!-- Tooltip personnalisé -->
                                                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none z-50">
                                                                    {{ participant.name }}
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                v-if="reservation.participants.length > 3"
                                                                @click.stop="openReservationDetails(reservation)"
                                                                class="relative w-8 h-8 rounded-full flex items-center justify-center bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold border-2 border-white shadow-sm cursor-pointer group transition"
                                                            >
                                                                +{{ reservation.participants.length - 3 }}
                                                                <!-- Tooltip pour les participants supplémentaires -->
                                                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none z-50">
                                                                    Cliquer pour voir tous les participants
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-xs text-gray-400">
                                                        Aucun
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="text-sm text-gray-900">
                                                        {{ new Date(reservation.date).toLocaleDateString('fr-FR', { 
                                                            day: 'numeric', 
                                                            month: 'short', 
                                                            year: 'numeric' 
                                                        }) }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ reservation.heure_debut }} - {{ reservation.heure_fin }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    <div class="text-sm text-gray-900">
                                                        {{ reservation.nombre_personnes || '-' }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    <Badge :class="getStatusBadgeClass(reservation)" class="text-white">
                                                        {{ new Date(`${reservation.date}T${reservation.heure_fin}`) < new Date() ? 'Terminée' : 'Active' }}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination frontend -->
                                <div v-if="totalPages > 1" class="mt-6 flex justify-center">
                                    <Pagination
                                        :total="reservations.length"
                                        :current-page="currentListPage"
                                        :per-page="itemsPerPage"
                                        :sibling-count="1"
                                        @update:current-page="goToListPage"
                                    />
                                </div>
                                
                                <div class="mt-3 text-center text-sm text-gray-600">
                                    {{ reservations.length }} réservation{{ reservations.length > 1 ? 's' : '' }} au total
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- Modale de détails de réservation -->
            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="max-w-lg">
                    <DialogHeader>
                        <DialogTitle class="text-xl font-bold text-[#007461]">
                            Détails de la réservation
                        </DialogTitle>
                        <DialogDescription>
                            Informations complètes sur la réservation sélectionnée
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div v-if="selectedReservation" class="space-y-4 py-4">
                        <!-- Titre -->
                        <div v-if="selectedReservation.titre" class="pb-3 border-b">
                            <h3 class="text-lg font-bold text-gray-900">{{ selectedReservation.titre }}</h3>
                        </div>

                        <!-- Salle -->
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="selectedReservation.room?.images && selectedReservation.room.images.length > 0"
                                    :src="`/storage/${selectedReservation.room.images[0].path}`"
                                    :alt="selectedReservation.room?.nom"
                                    class="w-full h-full object-cover"
                                />
                                <MapPin v-else class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Salle</div>
                                <div class="font-semibold text-lg">{{ selectedReservation.room?.nom }}</div>
                                <div class="text-xs text-gray-500">Capacité: {{ selectedReservation.room?.capacite }} personnes</div>
                            </div>
                        </div>

                        <!-- Utilisateur -->
                        <div class="flex items-start gap-3">
                            <Avatar v-if="selectedReservation.user" class="h-12 w-12 overflow-hidden rounded-lg flex-shrink-0">
                                <AvatarImage v-if="selectedReservation.user.avatar" :src="selectedReservation.user.avatar" :alt="selectedReservation.user.name" />
                                <AvatarFallback class="rounded-lg bg-msl-primary text-white">
                                    {{ getInitials(selectedReservation.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <div class="text-sm text-gray-500">Réservé par</div>
                                <div class="font-semibold">{{ selectedReservation.user?.name }}</div>
                                <div class="text-sm text-gray-600">{{ selectedReservation.user?.email }}</div>
                            </div>
                        </div>

                        <!-- Date et horaires -->
                        <div class="flex items-start gap-3">
                            <Clock class="w-5 h-5 text-msl-primary mt-0.5 flex-shrink-0" />
                            <div>
                                <div class="text-sm text-gray-500">Date et horaires</div>
                                <div class="font-semibold">{{ formatFullDate(selectedReservation.date) }}</div>
                                <div class="text-sm text-gray-600">
                                    De {{ selectedReservation.heure_debut }} à {{ selectedReservation.heure_fin }}
                                </div>
                            </div>
                        </div>

                        <!-- Nombre de personnes -->
                        <div v-if="selectedReservation.nombre_personnes" class="flex items-start gap-3">
                            <Users class="w-5 h-5 text-msl-primary mt-0.5 flex-shrink-0" />
                            <div>
                                <div class="text-sm text-gray-500">Nombre de personnes</div>
                                <div class="font-semibold">{{ selectedReservation.nombre_personnes }}</div>
                            </div>
                        </div>

                        <!-- Participants -->
                        <div v-if="selectedReservation.participants && selectedReservation.participants.length > 0" class="flex items-start gap-3">
                            <Users class="w-5 h-5 text-[#007461] mt-0.5 flex-shrink-0" />
                            <div class="flex-1">
                                <div class="text-sm text-gray-500 mb-2">Participants ({{ selectedReservation.participants.length }})</div>
                                <div class="flex flex-wrap gap-2">
                                    <Badge 
                                        v-for="participant in selectedReservation.participants" 
                                        :key="participant.id"
                                        class="bg-gray-600 text-white"
                                    >
                                        {{ participant.name }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="selectedReservation.description" class="flex items-start gap-3 pt-3 border-t">
                            <div class="w-5 h-5 flex-shrink-0"></div>
                            <div class="flex-1">
                                <div class="text-sm text-gray-500 mb-1">Description</div>
                                <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ selectedReservation.description }}</div>
                            </div>
                        </div>

                        <!-- Statut -->
                        <div class="pt-2 border-t">
                            <Badge :class="getStatusBadgeClass(selectedReservation)" class="text-white">
                                {{ new Date(`${selectedReservation.date}T${selectedReservation.heure_fin}`) < new Date() ? 'Terminée' : 'Active' }}
                            </Badge>
                            <div class="text-xs text-gray-400 mt-2">
                                Créée le {{ new Date(selectedReservation.created_at).toLocaleDateString('fr-FR', { 
                                    day: 'numeric', 
                                    month: 'long', 
                                    year: 'numeric' 
                                }) }}
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button 
                            variant="outline" 
                            @click="isDialogOpen = false"
                        >
                            Fermer
                        </Button>
                        <div class="flex gap-2">
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button 
                                        variant="destructive"
                                        class="bg-red-600 hover:bg-red-700 text-white cursor-pointer"
                                    >
                                        <Trash2 class="w-4 h-4 mr-2" />
                                        Annuler
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Êtes-vous sûr ?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Cette action est irréversible. La réservation sera définitivement annulée.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel as-child>
                                            <Button variant="outline" class="cursor-pointer">
                                                Non, garder
                                            </Button>
                                        </AlertDialogCancel>
                                        <AlertDialogAction as-child>
                                            <Button 
                                                @click="deleteReservation"
                                                class="bg-red-600 hover:bg-red-700 text-white cursor-pointer"
                                            >
                                                Oui, annuler
                                            </Button>
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                            <Button 
                                @click="editReservation"
                                class="bg-[#007461] hover:bg-[#009980] text-white cursor-pointer"
                            >
                                <Edit class="w-4 h-4 mr-2" />
                                Modifier
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Modale des réservations du jour -->
            <Dialog v-model:open="isDayReservationsDialogOpen">
                <DialogContent class="max-w-2xl max-h-[80vh]">
                    <DialogHeader>
                        <DialogTitle class="text-xl font-bold text-[#007461]">
                            Réservations du {{ selectedDayDate }}
                        </DialogTitle>
                        <DialogDescription>
                            {{ selectedDayReservations.length }} réservation{{ selectedDayReservations.length > 1 ? 's' : '' }} ce jour
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="space-y-3 py-4 overflow-y-auto max-h-[50vh]">
                        <div
                            v-for="reservation in selectedDayReservations"
                            :key="reservation.id"
                            @click="openReservationDetails(reservation); isDayReservationsDialogOpen = false"
                            class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-msl-s hover:shadow-msl-m transition cursor-pointer"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 mb-1">
                                        {{ reservation.titre || 'Sans titre' }}
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-0.5">
                                        <div>
                                            <MapPin class="w-3 h-3 inline mr-1" />
                                            {{ reservation.room?.nom }}
                                        </div>
                                        <div>
                                            <Clock class="w-3 h-3 inline mr-1" />
                                            {{ reservation.heure_debut }} - {{ reservation.heure_fin }}
                                        </div>
                                        <div>
                                            <User class="w-3 h-3 inline mr-1" />
                                            {{ reservation.user?.name }}
                                        </div>
                                    </div>
                                </div>
                                <Badge :class="getStatusBadgeClass(reservation)" class="text-white flex-shrink-0">
                                    {{ new Date(`${reservation.date}T${reservation.heure_fin}`) < new Date() ? 'Terminée' : 'Active' }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button 
                            variant="outline" 
                            @click="isDayReservationsDialogOpen = false"
                        >
                            Fermer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
