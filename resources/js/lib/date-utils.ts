/**
 * Format a date to French locale string
 */
export function formatDate(date: string | Date, options?: Intl.DateTimeFormatOptions): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    
    const defaultOptions: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        ...options,
    };
    
    return dateObj.toLocaleDateString('fr-FR', defaultOptions);
}

/**
 * Format a date to short format (dd/mm/yyyy)
 */
export function formatDateShort(date: string | Date): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}

/**
 * Format a time string (HH:mm)
 */
export function formatTime(time: string): string {
    const [hours, minutes] = time.split(':');
    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
}

/**
 * Format a datetime to relative time (Il y a X jours)
 */
export function formatRelativeTime(date: string | Date): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diffMs = now.getTime() - dateObj.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    const diffMonth = Math.floor(diffDay / 30);
    const diffYear = Math.floor(diffDay / 365);

    if (diffSec < 60) return 'À l\'instant';
    if (diffMin < 60) return `Il y a ${diffMin} minute${diffMin > 1 ? 's' : ''}`;
    if (diffHour < 24) return `Il y a ${diffHour} heure${diffHour > 1 ? 's' : ''}`;
    if (diffDay < 30) return `Il y a ${diffDay} jour${diffDay > 1 ? 's' : ''}`;
    if (diffMonth < 12) return `Il y a ${diffMonth} mois`;
    return `Il y a ${diffYear} an${diffYear > 1 ? 's' : ''}`;
}

/**
 * Get day name from date
 */
export function getDayName(date: string | Date): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('fr-FR', { weekday: 'long' });
}

/**
 * Get month name from date
 */
export function getMonthName(date: string | Date): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('fr-FR', { month: 'long' });
}

/**
 * Check if date is today
 */
export function isToday(date: string | Date): boolean {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const today = new Date();
    
    return dateObj.getDate() === today.getDate() &&
           dateObj.getMonth() === today.getMonth() &&
           dateObj.getFullYear() === today.getFullYear();
}

/**
 * Check if date is in the past
 */
export function isPast(date: string | Date): boolean {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    dateObj.setHours(0, 0, 0, 0);
    
    return dateObj < now;
}

/**
 * Check if date is in the future
 */
export function isFuture(date: string | Date): boolean {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    dateObj.setHours(0, 0, 0, 0);
    
    return dateObj > now;
}

/**
 * Get date difference in days
 */
export function getDaysDifference(date1: string | Date, date2: string | Date): number {
    const d1 = typeof date1 === 'string' ? new Date(date1) : date1;
    const d2 = typeof date2 === 'string' ? new Date(date2) : date2;
    
    d1.setHours(0, 0, 0, 0);
    d2.setHours(0, 0, 0, 0);
    
    const diffTime = Math.abs(d2.getTime() - d1.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

/**
 * Format duration between two times (HH:mm - HH:mm) to hours
 */
export function calculateDuration(heureDebut: string, heureFin: string): number {
    const [startHours, startMinutes] = heureDebut.split(':').map(Number);
    const [endHours, endMinutes] = heureFin.split(':').map(Number);
    
    const startTotal = startHours + (startMinutes / 60);
    const endTotal = endHours + (endMinutes / 60);
    
    return endTotal - startTotal;
}

/**
 * Format duration to human readable string
 */
export function formatDuration(hours: number): string {
    if (hours < 1) {
        return `${Math.round(hours * 60)} minutes`;
    }
    
    const fullHours = Math.floor(hours);
    const minutes = Math.round((hours - fullHours) * 60);
    
    if (minutes === 0) {
        return `${fullHours}h`;
    }
    
    return `${fullHours}h${minutes.toString().padStart(2, '0')}`;
}

/**
 * Convert date to ISO format for input[type="date"]
 */
export function toInputDate(date: string | Date): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toISOString().split('T')[0];
}

/**
 * Add days to a date
 */
export function addDays(date: string | Date, days: number): Date {
    const dateObj = typeof date === 'string' ? new Date(date) : new Date(date);
    dateObj.setDate(dateObj.getDate() + days);
    return dateObj;
}

/**
 * Get start of week
 */
export function getStartOfWeek(date: string | Date): Date {
    const dateObj = typeof date === 'string' ? new Date(date) : new Date(date);
    const day = dateObj.getDay();
    const diff = dateObj.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
    return new Date(dateObj.setDate(diff));
}

/**
 * Get end of week
 */
export function getEndOfWeek(date: string | Date): Date {
    const startOfWeek = getStartOfWeek(date);
    return addDays(startOfWeek, 6);
}
