<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Clock, MapPin, Users, MoreVertical, Pencil, Trash2 } from 'lucide-vue-next';
import ReservationStatusBadge from './ReservationStatusBadge.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Room {
    id: number;
    nom: string;
    capacite?: number;
    etage?: number;
}

interface User {
    id: number;
    name: string;
    email: string;
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
    nombre_personnes?: number | null;
    status?: 'pending' | 'confirmed' | 'cancelled';
    created_at?: string;
    updated_at?: string;
    room?: Room;
    user?: User;
    participants?: User[];
}

interface Props {
    reservation: Reservation;
    showActions?: boolean;
    clickable?: boolean;
}

withDefaults(defineProps<Props>(), {
    showActions: false,
    clickable: true,
});

const emit = defineEmits<{
    click: [reservation: Reservation];
    edit: [reservation: Reservation];
    delete: [reservation: Reservation];
}>();

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getInitials = (name: string): string => {
    const names = name.trim().split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[names.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const getAvatarColor = (index: number): string => {
    const colors = [
        '#007461', '#4F46E5', '#7C3AED', '#DB2777', '#DC2626',
        '#EA580C', '#059669', '#0891B2', '#7C2D12', '#4338CA',
    ];
    return colors[index % colors.length];
};
</script>

<template>
    <Card
        :class="[
            'hover:shadow-md transition-shadow',
            clickable ? 'cursor-pointer' : '',
        ]"
        @click="clickable && emit('click', reservation)"
    >
        <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="font-semibold text-lg">
                        {{ reservation.titre || 'Réservation sans titre' }}
                    </h3>
                    <ReservationStatusBadge :reservation="reservation" />
                </div>
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-1">
                        <Calendar class="h-4 w-4" />
                        <span>{{ formatDate(reservation.date) }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <Clock class="h-4 w-4" />
                        <span>{{ reservation.heure_debut }} - {{ reservation.heure_fin }}</span>
                    </div>
                </div>
            </div>
            <DropdownMenu v-if="showActions" @click.stop>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon">
                        <MoreVertical class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="emit('edit', reservation)">
                        <Pencil class="h-4 w-4 mr-2" />
                        Modifier
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="text-red-600 focus:text-red-600"
                        @click="emit('delete', reservation)"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Supprimer
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </CardHeader>
        <CardContent class="space-y-3">
            <div v-if="reservation.description" class="text-sm text-gray-600 dark:text-gray-400">
                {{ reservation.description }}
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div v-if="reservation.room" class="flex items-center gap-1">
                    <MapPin class="h-4 w-4 text-gray-500" />
                    <span>{{ reservation.room.nom }}</span>
                    <Badge v-if="reservation.room.etage !== undefined" variant="secondary" class="ml-1">
                        Étage {{ reservation.room.etage }}
                    </Badge>
                </div>
                <div v-if="reservation.participants && reservation.participants.length > 0" class="flex items-center gap-2">
                    <Users class="h-4 w-4 text-gray-500" />
                    <div class="flex -space-x-2">
                        <div
                            v-for="(participant, index) in reservation.participants.slice(0, 3)"
                            :key="participant.id"
                            class="flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-medium border-2 border-white dark:border-gray-800"
                            :style="{ backgroundColor: getAvatarColor(index) }"
                            :title="participant.name"
                        >
                            {{ getInitials(participant.name) }}
                        </div>
                        <div
                            v-if="reservation.participants.length > 3"
                            class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium border-2 border-white dark:border-gray-800"
                        >
                            +{{ reservation.participants.length - 3 }}
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
