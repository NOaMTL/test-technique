import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useReservations() {
    const reservations = ref<any[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const upcomingReservations = computed(() => {
        const now = new Date();
        return reservations.value.filter(res => {
            const resDate = new Date(res.date);
            return resDate >= now && res.status !== 'cancelled';
        }).sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    });

    const pastReservations = computed(() => {
        const now = new Date();
        return reservations.value.filter(res => {
            const resDate = new Date(res.date);
            return resDate < now || res.status === 'completed';
        }).sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
    });

    const cancelledReservations = computed(() => 
        reservations.value.filter(res => res.status === 'cancelled')
    );

    const fetchUserReservations = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/reservations/user');
            const data = await response.json();
            reservations.value = data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Erreur lors du chargement des réservations';
        } finally {
            loading.value = false;
        }
    };

    const fetchAllReservations = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/reservations');
            const data = await response.json();
            reservations.value = data;
        } catch {
            error.value = 'Erreur lors du chargement des réservations';
        } finally {
            loading.value = false;
        }
    };

    const checkAvailability = async (data: {
        room_id: number;
        date: string;
        heure_debut: string;
        heure_fin: string;
        exclude_reservation_id?: number;
    }): Promise<boolean> => {
        try {
            const response = await fetch('/api/reservations/check-availability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });
            
            const result = await response.json();
            return result.available;
        } catch {
            error.value = 'Erreur lors de la vérification de disponibilité';
            return false;
        }
    };

    const createReservation = (data: any) => {
        router.post('/api/reservations', data as any, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                error.value = 'Erreur lors de la création de la réservation';
            },
        });
    };

    const updateReservation = (id: number, data: any) => {
        router.put(`/api/reservations/${id}`, data as any, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                error.value = 'Erreur lors de la mise à jour de la réservation';
            },
        });
    };

    const cancelReservation = (id: number) => {
        router.delete(`/api/reservations/${id}`, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                error.value = 'Erreur lors de la suppression de la réservation';
            },
        });
    };

    const filterByStatus = (status: string) => {
        return computed(() => 
            reservations.value.filter(res => res.status === status)
        );
    };

    const filterByDateRange = (startDate: string, endDate: string) => {
        return computed(() => {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            return reservations.value.filter(res => {
                const resDate = new Date(res.date);
                return resDate >= start && resDate <= end;
            });
        });
    };

    const filterByRoom = (roomId: number) => {
        return computed(() => 
            reservations.value.filter(res => res.room_id === roomId)
        );
    };

    const stats = computed(() => ({
        total: reservations.value.length,
        upcoming: upcomingReservations.value.length,
        past: pastReservations.value.length,
        cancelled: cancelledReservations.value.length,
        confirmed: reservations.value.filter(r => r.status === 'confirmed').length,
        pending: reservations.value.filter(r => r.status === 'pending').length,
    }));

    return {
        reservations,
        loading,
        error,
        upcomingReservations,
        pastReservations,
        cancelledReservations,
        stats,
        fetchUserReservations,
        fetchAllReservations,
        checkAvailability,
        createReservation,
        updateReservation,
        cancelReservation,
        filterByStatus,
        filterByDateRange,
        filterByRoom,
    };
}
