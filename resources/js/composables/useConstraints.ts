import { ref } from 'vue';
import type { RoomConstraints } from '@/types';
import { 
    TIME_PERIODS, 
    DAY_LABELS,
    TIME_PERIOD_LABELS,
    type TimePeriod 
} from '@/constants';

export function useConstraints() {
    const validationErrors = ref<string[]>([]);

    /**
     * Validate time period constraint
     */
    const validateTimePeriod = (
        timePeriod: TimePeriod | null,
        heureDebut: string,
        heureFin: string
    ): boolean => {
        if (!timePeriod) return true;

        const debut = parseTime(heureDebut);
        const fin = parseTime(heureFin);

        switch (timePeriod) {
            case TIME_PERIODS.MORNING:
                // Morning: before 12:00
                return fin <= 12;
            
            case TIME_PERIODS.AFTERNOON:
                // Afternoon: after 12:00
                return debut >= 12;
            
            case TIME_PERIODS.FULL_DAY:
                // Full day: crosses noon
                return debut < 12 && fin > 12;
            
            default:
                return true;
        }
    };

    /**
     * Validate days allowed constraint
     */
    const validateDaysAllowed = (
        daysAllowed: number[] | null,
        date: string
    ): boolean => {
        if (!daysAllowed || daysAllowed.length === 0) return true;

        const dayOfWeek = new Date(date).getDay();
        // Convert Sunday (0) to 7 for consistency
        const adjustedDay = dayOfWeek === 0 ? 7 : dayOfWeek;
        
        return daysAllowed.includes(adjustedDay);
    };

    /**
     * Validate advance booking days constraint
     */
    const validateAdvanceBookingDays = (
        advanceBookingDays: number | null,
        date: string
    ): boolean => {
        if (!advanceBookingDays) return true;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const bookingDate = new Date(date);
        bookingDate.setHours(0, 0, 0, 0);
        
        const diffTime = bookingDate.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays <= advanceBookingDays;
    };

    /**
     * Validate minimum participants constraint
     */
    const validateMinParticipants = (
        minParticipants: number | null,
        nombrePersonnes: number | null
    ): boolean => {
        if (!minParticipants || !nombrePersonnes) return true;
        
        return nombrePersonnes >= minParticipants;
    };

    /**
     * Validate all constraints for a room
     */
    const validateAllConstraints = (
        constraints: RoomConstraints | null,
        data: {
            date: string;
            heure_debut: string;
            heure_fin: string;
            nombre_personnes?: number;
        }
    ): { valid: boolean; errors: string[] } => {
        validationErrors.value = [];

        if (!constraints) {
            return { valid: true, errors: [] };
        }

        // Time period validation
        if (constraints.time_period) {
            if (!validateTimePeriod(constraints.time_period, data.heure_debut, data.heure_fin)) {
                const label = TIME_PERIOD_LABELS[constraints.time_period];
                validationErrors.value.push(
                    `Cette salle est réservée uniquement pour : ${label}`
                );
            }
        }

        // Days allowed validation
        if (constraints.days_allowed && constraints.days_allowed.length > 0) {
            if (!validateDaysAllowed(constraints.days_allowed, data.date)) {
                const allowedDaysLabels = constraints.days_allowed
                    .map(day => DAY_LABELS[day as keyof typeof DAY_LABELS])
                    .join(', ');
                validationErrors.value.push(
                    `Cette salle ne peut être réservée que les : ${allowedDaysLabels}`
                );
            }
        }

        // Advance booking days validation
        if (constraints.advance_booking_days) {
            if (!validateAdvanceBookingDays(constraints.advance_booking_days, data.date)) {
                validationErrors.value.push(
                    `Cette salle ne peut être réservée que ${constraints.advance_booking_days} jour(s) à l'avance maximum`
                );
            }
        }

        // Minimum participants validation
        if (constraints.min_participants && data.nombre_personnes) {
            if (!validateMinParticipants(constraints.min_participants, data.nombre_personnes)) {
                validationErrors.value.push(
                    `Cette salle nécessite au moins ${constraints.min_participants} participant(s)`
                );
            }
        }

        return {
            valid: validationErrors.value.length === 0,
            errors: validationErrors.value,
        };
    };

    /**
     * Get constraint description for display
     */
    const getConstraintDescription = (constraints: RoomConstraints | null): string[] => {
        if (!constraints) return [];

        const descriptions: string[] = [];

        if (constraints.time_period) {
            descriptions.push(`Période : ${TIME_PERIOD_LABELS[constraints.time_period]}`);
        }

        if (constraints.days_allowed && constraints.days_allowed.length > 0) {
            const days = constraints.days_allowed
                .map(day => DAY_LABELS[day as keyof typeof DAY_LABELS])
                .join(', ');
            descriptions.push(`Jours : ${days}`);
        }

        if (constraints.advance_booking_days) {
            descriptions.push(`Réservation : ${constraints.advance_booking_days} jour(s) à l'avance max`);
        }

        if (constraints.weekly_hours_quota) {
            descriptions.push(`Quota : ${constraints.weekly_hours_quota}h/semaine`);
        }

        if (constraints.daily_booking_limit) {
            descriptions.push(`Limite : ${constraints.daily_booking_limit} réservation(s)/jour`);
        }

        if (constraints.min_participants) {
            descriptions.push(`Min : ${constraints.min_participants} participant(s)`);
        }

        return descriptions;
    };

    /**
     * Parse time string (HH:mm) to hours as decimal
     */
    const parseTime = (time: string): number => {
        const [hours, minutes] = time.split(':').map(Number);
        return hours + (minutes / 60);
    };

    return {
        validationErrors,
        validateTimePeriod,
        validateDaysAllowed,
        validateAdvanceBookingDays,
        validateMinParticipants,
        validateAllConstraints,
        getConstraintDescription,
    };
}
