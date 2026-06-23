import {
  BriefcaseIcon,
  CubeIcon,
  MapPinIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'

export function dispatchRequestTypeIcon(tt) {
  if (tt === 'door_to_door') return TruckIcon
  if (tt === 'point_to_point') return MapPinIcon
  if (tt === 'business') return BriefcaseIcon
  if (tt === 'cargo') return CubeIcon
  return MapPinIcon
}

export function dispatchRequestTypeIconColor(tt) {
  if (tt === 'door_to_door') return 'text-sky-600'
  if (tt === 'point_to_point') return 'text-emerald-600'
  if (tt === 'business') return 'text-amber-600'
  if (tt === 'cargo') return 'text-orange-600'
  return 'text-slate-600'
}

export function dispatchRequestTypeIconWrap(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100'
  if (tt === 'point_to_point') return 'bg-emerald-100'
  if (tt === 'business') return 'bg-amber-100'
  if (tt === 'cargo') return 'bg-orange-100'
  return 'bg-slate-100'
}

export function dispatchRequestTypeBadgeClass(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 text-sky-800'
  if (tt === 'point_to_point') return 'bg-emerald-100 text-emerald-800'
  if (tt === 'business') return 'bg-amber-100 text-amber-900'
  if (tt === 'cargo') return 'bg-orange-100 text-orange-900'
  return 'bg-slate-100 text-slate-700'
}
