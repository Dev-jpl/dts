
/**
 * Truncate a string to a given length and append ellipsis if needed.
 * @param text - The string to truncate
 * @param length - Maximum length before truncation (default: 30)
 * @returns Truncated string with ellipsis if exceeded
 */
export function truncate(text: string, length: number = 30): string {
    if (!text) return "";
    return text.length > length ? text.slice(0, length) + "..." : text;
}