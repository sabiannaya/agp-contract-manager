import { formatDate, formatDateTime, formatDateOnly, formatTime } from '@/lib/datetime';

/**
 * Composable for consistent date/time formatting throughout the app
 * Uses the configuration from @/config/datetime.json
 */
export function useDateFormat() {
    return {
        /**
         * Format a date with the configured format
         * @param value - ISO date string, Date object, or null/undefined
         * @param format - 'date' (DD/MM/YYYY), 'datetime' (DD/MM/YY HH:mm), 'time', 'dateShort', 'datetimeFull'
         */
        formatDate,
        
        /**
         * Format as datetime (DD/MM/YY HH:mm)
         */
        formatDateTime,
        
        /**
         * Format as date only (DD/MM/YYYY)
         */
        formatDateOnly,
        
        /**
         * Format as time only (HH:mm)
         */
        formatTime,
    };
}
