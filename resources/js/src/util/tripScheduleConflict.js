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

function occupancySlotsFromApi(trip) {
    const raw = trip?.occupancy_slots;
    if (!Array.isArray(raw) || !raw.length) return null;
    return raw.map((slot) => ({
        driver_id:
            slot.driver_id != null ? Number(slot.driver_id) : null,
        vehicle_id:
            slot.vehicle_id != null ? Number(slot.vehicle_id) : null,
        start: new Date(slot.start).getTime(),
        end: new Date(slot.end).getTime(),
    }));
}

/** @returns {{ driver_id: number|null, vehicle_id: number|null, start: number, end: number }[]} */
export function resourceOccupancySlots(trip) {
    const fromApi = occupancySlotsFromApi(trip);
    if (fromApi) return fromApi;

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

/**
 * Trùng tài xế/xe giữa các lịch trong cùng payload phân công (khớp assertNoInternalLegOverlaps).
 *
 * @param {{ key: string, driver_id?: number|null, vehicle_id?: number|null, depart_at?: string|null, arrive_by?: string|null }[]} legs
 * @param {{ start: number, end: number }} windowFallback
 */
export function internalLegResourceOverlaps(legs, windowFallback) {
    const windows = (legs ?? []).map((leg) => {
        const w = leg?.depart_at
            ? legWindowMs(leg, {
                  depart_at: new Date(leg.depart_at).toISOString(),
              })
            : windowFallback;
        return {
            key: leg?.key ?? "",
            driver_id: leg?.driver_id != null ? Number(leg.driver_id) : null,
            vehicle_id:
                leg?.vehicle_id != null ? Number(leg.vehicle_id) : null,
            ...w,
        };
    });
    for (let i = 0; i < windows.length; i++) {
        for (let j = i + 1; j < windows.length; j++) {
            const a = windows[i];
            const b = windows[j];
            if (
                !tripIntervalsOverlap(a.start, a.end, b.start, b.end)
            ) {
                continue;
            }
            if (
                a.vehicle_id &&
                b.vehicle_id &&
                a.vehicle_id === b.vehicle_id
            ) {
                return { kind: "vehicle" };
            }
            if (
                a.driver_id &&
                b.driver_id &&
                a.driver_id === b.driver_id
            ) {
                return { kind: "driver" };
            }
        }
    }
    return null;
}
