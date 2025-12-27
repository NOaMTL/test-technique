export const TIME_PERIODS = {
  MORNING: 'morning',
  AFTERNOON: 'afternoon',
  FULL_DAY: 'full_day',
} as const;

export type TimePeriod = typeof TIME_PERIODS[keyof typeof TIME_PERIODS];

export const TIME_PERIOD_LABELS: Record<TimePeriod, string> = {
  [TIME_PERIODS.MORNING]: 'Matin',
  [TIME_PERIODS.AFTERNOON]: 'Après-midi',
  [TIME_PERIODS.FULL_DAY]: 'Journée complète',
};

export const DAYS_OF_WEEK = {
  MONDAY: 1,
  TUESDAY: 2,
  WEDNESDAY: 3,
  THURSDAY: 4,
  FRIDAY: 5,
  SATURDAY: 6,
  SUNDAY: 7,
} as const;

export type DayOfWeek = typeof DAYS_OF_WEEK[keyof typeof DAYS_OF_WEEK];

export const DAY_LABELS: Record<DayOfWeek, string> = {
  [DAYS_OF_WEEK.MONDAY]: 'Lundi',
  [DAYS_OF_WEEK.TUESDAY]: 'Mardi',
  [DAYS_OF_WEEK.WEDNESDAY]: 'Mercredi',
  [DAYS_OF_WEEK.THURSDAY]: 'Jeudi',
  [DAYS_OF_WEEK.FRIDAY]: 'Vendredi',
  [DAYS_OF_WEEK.SATURDAY]: 'Samedi',
  [DAYS_OF_WEEK.SUNDAY]: 'Dimanche',
};

export const CONSTRAINT_TYPES = {
  TIME_PERIOD: 'time_period',
  DAYS_ALLOWED: 'days_allowed',
  ADVANCE_BOOKING_DAYS: 'advance_booking_days',
  WEEKLY_HOURS_QUOTA: 'weekly_hours_quota',
  DAILY_BOOKING_LIMIT: 'daily_booking_limit',
  MIN_PARTICIPANTS: 'min_participants',
} as const;

export type ConstraintType = typeof CONSTRAINT_TYPES[keyof typeof CONSTRAINT_TYPES];

export const CONSTRAINT_LABELS: Record<ConstraintType, string> = {
  [CONSTRAINT_TYPES.TIME_PERIOD]: 'Période de réservation',
  [CONSTRAINT_TYPES.DAYS_ALLOWED]: 'Jours autorisés',
  [CONSTRAINT_TYPES.ADVANCE_BOOKING_DAYS]: 'Délai de réservation (jours)',
  [CONSTRAINT_TYPES.WEEKLY_HOURS_QUOTA]: 'Quota hebdomadaire (heures)',
  [CONSTRAINT_TYPES.DAILY_BOOKING_LIMIT]: 'Limite de réservations par jour',
  [CONSTRAINT_TYPES.MIN_PARTICIPANTS]: 'Nombre minimum de participants',
};

export const CONSTRAINT_DESCRIPTIONS: Record<ConstraintType, string> = {
  [CONSTRAINT_TYPES.TIME_PERIOD]: 'Restreindre les réservations à certaines périodes',
  [CONSTRAINT_TYPES.DAYS_ALLOWED]: 'Jours de la semaine où la salle peut être réservée',
  [CONSTRAINT_TYPES.ADVANCE_BOOKING_DAYS]: 'Nombre de jours à l\'avance pour réserver',
  [CONSTRAINT_TYPES.WEEKLY_HOURS_QUOTA]: 'Nombre maximum d\'heures par semaine par utilisateur',
  [CONSTRAINT_TYPES.DAILY_BOOKING_LIMIT]: 'Nombre maximum de réservations par jour',
  [CONSTRAINT_TYPES.MIN_PARTICIPANTS]: 'Nombre minimum de participants requis',
};
