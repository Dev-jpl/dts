/**
 * Converts byte size into human-readable string.
 * Supports B, KB, and MB with one decimal precision.
 *
 * @param bytes - File size in bytes
 * @returns Formatted string (e.g. "2.1 MB")
 */
export function formatSize(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  while (bytes >= 1024 && i < units.length - 1) {
    bytes /= 1024
    i++
  }
  return `${bytes.toFixed(1)} ${units[i]}`
}