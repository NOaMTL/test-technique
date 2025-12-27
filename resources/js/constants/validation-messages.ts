export const VALIDATION_MESSAGES = {
  // Common
  REQUIRED: 'Ce champ est obligatoire',
  EMAIL_INVALID: 'L\'adresse email est invalide',
  
  // Rooms
  ROOM_NAME_REQUIRED: 'Le nom de la salle est obligatoire',
  ROOM_CAPACITY_REQUIRED: 'La capacité est obligatoire',
  ROOM_CAPACITY_MIN: 'La capacité doit être au moins 1',
  ROOM_FLOOR_REQUIRED: 'L\'étage est obligatoire',
  
  // Reservations
  RESERVATION_ROOM_REQUIRED: 'Veuillez sélectionner une salle',
  RESERVATION_DATE_REQUIRED: 'La date est obligatoire',
  RESERVATION_DATE_PAST: 'La date ne peut pas être dans le passé',
  RESERVATION_START_TIME_REQUIRED: 'L\'heure de début est obligatoire',
  RESERVATION_END_TIME_REQUIRED: 'L\'heure de fin est obligatoire',
  RESERVATION_END_TIME_AFTER_START: 'L\'heure de fin doit être après l\'heure de début',
  
  // Images
  IMAGE_FORMAT_INVALID: 'Le format de l\'image est invalide (jpeg, png, jpg, gif, webp acceptés)',
  IMAGE_SIZE_TOO_LARGE: 'L\'image est trop grande (max 2MB)',
  
  // Auth
  PASSWORD_MIN_LENGTH: 'Le mot de passe doit contenir au moins 8 caractères',
  PASSWORD_CONFIRMATION_MISMATCH: 'Les mots de passe ne correspondent pas',
  
  // Settings
  SETTING_VALUE_REQUIRED: 'La valeur du paramètre est obligatoire',
} as const;

export type ValidationMessage = typeof VALIDATION_MESSAGES[keyof typeof VALIDATION_MESSAGES];

// Helper function to get validation message
export const getValidationMessage = (key: keyof typeof VALIDATION_MESSAGES): string => {
  return VALIDATION_MESSAGES[key];
};
