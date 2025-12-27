export const RESERVATION_STATUS = {
  PENDING: 'pending',
  CONFIRMED: 'confirmed',
  CANCELLED: 'cancelled',
  COMPLETED: 'completed',
} as const;

export type ReservationStatus = typeof RESERVATION_STATUS[keyof typeof RESERVATION_STATUS];

export const RESERVATION_STATUS_LABELS: Record<ReservationStatus, string> = {
  [RESERVATION_STATUS.PENDING]: 'En attente',
  [RESERVATION_STATUS.CONFIRMED]: 'Confirmée',
  [RESERVATION_STATUS.CANCELLED]: 'Annulée',
  [RESERVATION_STATUS.COMPLETED]: 'Terminée',
};

export const RESERVATION_STATUS_COLORS: Record<ReservationStatus, string> = {
  [RESERVATION_STATUS.PENDING]: 'warning',
  [RESERVATION_STATUS.CONFIRMED]: 'success',
  [RESERVATION_STATUS.CANCELLED]: 'error',
  [RESERVATION_STATUS.COMPLETED]: 'default',
};
