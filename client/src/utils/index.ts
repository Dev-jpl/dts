// src/utils/utils.ts

/**
 * Capitalizes the first letter of a string
 */
export function capitalize(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1)
}

/**
 * Checks if a value is an empty object
 */
export function isEmptyObject(obj: object): boolean {
    return Object.keys(obj).length === 0
}

/**
 * Debounce a function (delay execution)
 */
export function debounce<T extends (...args: any[]) => void>(
    fn: T,
    delay = 300
): (...args: Parameters<T>) => void {
    let timer: ReturnType<typeof setTimeout>
    return (...args) => {
        clearTimeout(timer)
        timer = setTimeout(() => fn(...args), delay)
    }
}

/**
 * Format date to YYYY-MM-DD
 */
export function formatDate(date: Date): string {
    return date.toISOString().split('T')[0]
}
