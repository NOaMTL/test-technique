<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

interface Props {
    reservation: {
        date: string;
        heure_debut: string;
        heure_fin: string;
    };
}

const props = defineProps<Props>();

type ReservationStatus = 'passee' | 'en_cours' | 'a_venir';

const getReservationStatus = (): ReservationStatus => {
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();

    const dateParts = props.reservation.date.split('-');
    const reservationYear = parseInt(dateParts[0]);
    const reservationMonth = parseInt(dateParts[1]) - 1;
    const reservationDay = parseInt(dateParts[2]);

    const [startHours, startMinutes] = props.reservation.heure_debut.split(':').map(Number);
    const [endHours, endMinutes] = props.reservation.heure_fin.split(':').map(Number);

    const reservationStart = new Date(reservationYear, reservationMonth, reservationDay, startHours, startMinutes);
    const reservationEnd = new Date(reservationYear, reservationMonth, reservationDay, endHours, endMinutes);
    const currentDateTime = new Date(currentYear, currentMonth, currentDay, currentHours, currentMinutes);

    if (currentDateTime >= reservationEnd) {
        return 'passee';
    } else if (currentDateTime >= reservationStart && currentDateTime < reservationEnd) {
        return 'en_cours';
    } else {
        return 'a_venir';
    }
};

const status = computed(() => getReservationStatus());

const statusConfig = computed(() => {
    const configs = {
        passee: {
            label: 'Passée',
            class: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
        },
        en_cours: {
            label: 'En cours',
            class: 'bg-orange-100 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400',
        },
        a_venir: {
            label: 'À venir',
            class: 'bg-msl-primary text-white',
        },
    };
    return configs[status.value];
});
</script>

<template>
    <Badge :class="statusConfig.class">
        {{ statusConfig.label }}
    </Badge>
</template>
