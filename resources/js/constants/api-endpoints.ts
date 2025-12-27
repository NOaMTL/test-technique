export const API_ENDPOINTS = {
  // Rooms
  ROOMS: '/api/rooms',
  ROOM_BY_ID: (id: number | string) => `/api/rooms/${id}`,
  ROOM_EQUIPMENTS: '/api/rooms/equipments',
  ROOM_FLOORS: '/api/rooms/floors',
  
  // Reservations
  RESERVATIONS: '/api/reservations',
  RESERVATION_BY_ID: (id: number | string) => `/api/reservations/${id}`,
  USER_RESERVATIONS: '/api/reservations/user',
  CHECK_AVAILABILITY: '/api/reservations/check-availability',
  
  // Admin - Rooms
  ADMIN_ROOMS: '/admin/rooms',
  ADMIN_ROOM_BY_ID: (id: number | string) => `/admin/rooms/${id}`,
  ADMIN_ROOM_TOGGLE: (id: number | string) => `/admin/rooms/${id}/toggle-active`,
  ADMIN_ROOM_IMAGES_ORDER: (id: number | string) => `/api/admin/rooms/${id}/images-order`,
  ADMIN_ROOM_IMAGE_DELETE: (roomId: number | string, imageId: number | string) => 
    `/api/admin/rooms/${roomId}/images/${imageId}`,
  
  // Settings
  ADMIN_SETTINGS: '/admin/settings',
  ADMIN_CACHE_CLEAR: '/api/admin/cache/clear',
  ADMIN_CACHE_CLEAR_SPECIFIC: '/api/admin/cache/clear-specific',
  
  // Auth
  LOGIN: '/login',
  LOGOUT: '/logout',
  REGISTER: '/register',
  FORGOT_PASSWORD: '/forgot-password',
  RESET_PASSWORD: '/reset-password',
  
  // User Profile
  PROFILE: '/settings/profile',
  PASSWORD: '/settings/password',
  APPEARANCE: '/settings/appearance',
} as const;

export type ApiEndpoint = typeof API_ENDPOINTS[keyof typeof API_ENDPOINTS];
