import datetimeConfig from '@/config/datetime.json';

export interface DateTimeConfig {
    formats: {
        datetime: string;
        date: string;
        time: string;
        dateShort: string;
        datetimeFull: string;
    };
    locale: string;
}

const config: DateTimeConfig = datetimeConfig;

/**
 * Format a date/datetime string according to the configured format
 * @param value - ISO date string or Date object
 * @param format - Format type: 'datetime', 'date', 'time', 'dateShort', 'datetimeFull'
 * @returns Formatted date string
 */
export function formatDate(
    value: string | Date | null | undefined,
    format: keyof DateTimeConfig['formats'] = 'date'
): string {
    if (!value) return '';
    
    const date = typeof value === 'string' ? new Date(value) : value;
    
    if (isNaN(date.getTime())) return '';
    
    const formatPattern = config.formats[format];
    
    return formatPattern.replace(/DD|MM|YYYY|YY|HH|hh|mm|ss/g, (match) => {
        switch (match) {
            case 'DD':
                return String(date.getDate()).padStart(2, '0');
            case 'MM':
                return String(date.getMonth() + 1).padStart(2, '0');
            case 'YYYY':
                return String(date.getFullYear());
            case 'YY':
                return String(date.getFullYear()).slice(-2);
            case 'HH':
            case 'hh':
                return String(date.getHours()).padStart(2, '0');
            case 'mm':
                return String(date.getMinutes()).padStart(2, '0');
            case 'ss':
                return String(date.getSeconds()).padStart(2, '0');
            default:
                return match;
        }
    });
}

/**
 * Format a datetime (includes both date and time)
 */
export function formatDateTime(value: string | Date | null | undefined): string {
    return formatDate(value, 'datetime');
}

/**
 * Format just the date part
 */
export function formatDateOnly(value: string | Date | null | undefined): string {
    return formatDate(value, 'date');
}

/**
 * Format just the time part
 */
export function formatTime(value: string | Date | null | undefined): string {
    return formatDate(value, 'time');
}

/**
 * Get the current datetime configuration
 */
export function getDateTimeConfig(): DateTimeConfig {
    return config;
}
