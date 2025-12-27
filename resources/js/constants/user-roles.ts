export const USER_ROLES = {
  ADMIN: 'admin',
  USER: 'user',
} as const;

export type UserRole = typeof USER_ROLES[keyof typeof USER_ROLES];

export const USER_ROLE_LABELS: Record<UserRole, string> = {
  [USER_ROLES.ADMIN]: 'Administrateur',
  [USER_ROLES.USER]: 'Utilisateur',
};

export const isAdmin = (role: string | undefined): boolean => {
  return role === USER_ROLES.ADMIN;
};

export const isUser = (role: string | undefined): boolean => {
  return role === USER_ROLES.USER;
};
