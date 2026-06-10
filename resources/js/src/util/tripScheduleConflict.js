/** Cùng logic backend: kết thúc kế hoạch = arrive_by hoặc depart + 2h */
export function tripPlannedEndMs(t) {
    const dr = t?.dispatch_request;
    const endIso = t?.arrive_by ?? dr?.arrive_by;
    if (endIso) return new Date(endIso).getTime();
    const s = new Date(t.depart_at).getTime();
    return s + 2 * 60 * 60 * 1000;
}

export function tripIntervalsOverlap(aStart, aEnd, bStart, bEnd) {
    return aStart < bEnd && bStart < aEnd;
}

function legWindowMs(leg, tripFallback) {
    if (leg?.depart_at) {
        const start = new Date(leg.depart_at).getTime();
        const end = leg.arrive_by
            ? new Date(leg.arrive_by).getTime()
            : start + 2 * 60 * 60 * 1000;
        return { start, end };
    }
    return {
        start: new Date(tripFallback.depart_at).getTime(),
        end: tripPlannedEndMs(tripFallback),
    };
}

/** @returns {{ driver_id: number|null, vehicle_id: number|null, start: number, end: number }[]} */
export function resourceOccupancySlots(trip) {
    const assignments = Array.isArray(trip?.schedule_assignments)
        ? trip.schedule_assignments.filter((x) => x && typeof x === "object")
        : [];
    if (assignments.length) {
        return assignments.map((assign) => ({
            driver_id:
                assign.driver_id != null ? Number(assign.driver_id) : null,
            vehicle_id:
                assign.vehicle_id != null ? Number(assign.vehicle_id) : null,
            ...legWindowMs(assign, trip),
        }));
    }
    return [
        {
            driver_id: trip.driver_id != null ? Number(trip.driver_id) : null,
            vehicle_id: trip.vehicle_id != null ? Number(trip.vehicle_id) : null,
            start: new Date(trip.depart_at).getTime(),
            end: tripPlannedEndMs(trip),
        },
    ];
}

export function collectBusyDriverIds(
    trips,
    window,
    excludeTripId,
    conflictStatuses,
) {
    const busy = new Set();
    if (!window) return busy;
    for (const o of trips) {
        if (!o?.id || o.id === excludeTripId) continue;
        if (!conflictStatuses.includes(o.status)) continue;
        for (const slot of resourceOccupancySlots(o)) {
            if (!slot.driver_id) continue;
            if (
                tripIntervalsOverlap(
                    window.start,
                    window.end,
                    slot.start,
                    slot.end,
                )
            ) {
                busy.add(slot.driver_id);
            }
        }
    }
    return busy;
}

export function collectBusyVehicleIds(
    trips,
    window,
    excludeTripId,
    conflictStatuses,
) {
    const busy = new Set();
    if (!window) return busy;
    for (const o of trips) {
        if (!o?.id || o.id === excludeTripId) continue;
        if (!conflictStatuses.includes(o.status)) continue;
        for (const slot of resourceOccupancySlots(o)) {
            if (!slot.vehicle_id) continue;
            if (
                tripIntervalsOverlap(
                    window.start,
                    window.end,
                    slot.start,
                    slot.end,
                )
            ) {
                busy.add(slot.vehicle_id);
            }
        }
    }
    return busy;
}
