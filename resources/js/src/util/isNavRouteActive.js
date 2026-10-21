import { isDispatchStaffHomePath } from '../config/dispatchWebBase'
import { flattenNavLeaves } from '../config/nav'

const NAV_TO_PATHS = flattenNavLeaves().map((leaf) => leaf.to)

/**
 * @param {string} navTo
 * @param {string} currentPath
 * @param {string[]} [navPaths]
 */
export function isNavRouteActive(navTo, currentPath, navPaths = NAV_TO_PATHS) {
  if (!navTo) return false

  if (isDispatchStaffHomePath(navTo)) {
    return isDispatchStaffHomePath(currentPath)
  }

  if (navTo === '/driver') {
    return currentPath === '/driver'
  }

  if (navTo === '/driver/schedule') {
    return (
      currentPath === '/driver/schedule' ||
      /^\/driver\/trips\/\d+/.test(currentPath) ||
      currentPath === '/driver/trips'
    )
  }

  if (navTo === '/driver/tp-days') {
    return (
      currentPath === '/driver/tp-days' ||
      currentPath.startsWith('/driver/tp-days/')
    )
  }

  if (navTo === '/driver/costs') {
    return (
      currentPath === '/driver/costs' ||
      currentPath.startsWith('/driver/costs/')
    )
  }

  if (navTo === '/driver/account') {
    return (
      currentPath === '/driver/account' ||
      currentPath.startsWith('/driver/account/')
    )
  }

  if (currentPath === navTo) return true
  if (!currentPath.startsWith(`${navTo}/`)) return false

  const moreSpecific = navPaths.filter(
    (p) => p !== navTo && p.startsWith(`${navTo}/`),
  )
  const hasBetterMatch = moreSpecific.some(
    (p) => currentPath === p || currentPath.startsWith(`${p}/`),
  )
  return !hasBetterMatch
}
