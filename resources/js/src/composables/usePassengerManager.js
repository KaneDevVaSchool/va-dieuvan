import { ref, watch } from "vue";

export function emptyNamedPassenger() {
    return { name: "", phone: "", note: "" };
}

/**
 * passenger_count ⇄ passengers[] — chỉ resize mảng, giữ slot đã có khi giảm/tăng theo luật slice/push.
 */
export function usePassengerManager() {
    /** @type {import('vue').Ref<number>} */
    const passengerCount = ref(1);
    /** @type {import('vue').Ref<{ name: string, phone: string, note: string }[]>} */
    const passengers = ref([emptyNamedPassenger()]);

    /** @param {unknown} newVal */
    function syncPassengers(newVal) {
        const nRaw = Number(newVal);
        const n = Math.min(
            50,
            Math.max(1, Number.isFinite(nRaw) && nRaw > 0 ? nRaw : 1),
        );
        const cur = passengers.value ?? [];
        if (cur.length === n) return;
        if (cur.length < n) {
            const tail = Array.from({ length: n - cur.length }, () =>
                emptyNamedPassenger(),
            );
            passengers.value = [...cur, ...tail];
        } else {
            passengers.value = cur.slice(0, n);
        }
    }

    watch(passengerCount, (v) => syncPassengers(v));

    /**
     * @param {Record<string, unknown> | null | undefined} trip
     */
    function hydrateFromTrip(trip) {
        const dr = trip?.dispatch_request;
        /** @type {Array<Record<string, unknown>> | undefined} */
        const tps = trip?.trip_passengers;

        let n;
        /** @type {{ name: string, phone: string, note: string }[]} */
        let seeds = [];

        const drCountRaw = Number(dr?.passenger_count);
        const drHasCount = Number.isFinite(drCountRaw) && drCountRaw > 0;

        if (Array.isArray(tps) && tps.length > 0) {
            seeds = tps.map((p) => ({
                name: String(p.name ?? "").trim(),
                phone: String(p.phone ?? "").trim(),
                note: String(p.note ?? "").trim(),
            }));
            const target = Math.min(
                50,
                Math.max(
                    seeds.length,
                    drHasCount ? drCountRaw : seeds.length,
                    1,
                ),
            );
            n = target;
            while (seeds.length < n) seeds.push(emptyNamedPassenger());
            seeds = seeds.slice(0, n);
            passengers.value = seeds;
            passengerCount.value = n;
            return;
        }

        const snapRows = Array.isArray(dr?.wizard_snapshot?.passengerRows)
            ? dr.wizard_snapshot.passengerRows
            : [];
        const filled = snapRows.filter(
            (r) =>
                String(r?.person_in_charge ?? "").trim() ||
                String(r?.notes ?? "").trim(),
        );

        if (filled.length) {
            seeds = filled.map((r) => ({
                name: String(r.person_in_charge ?? "").trim(),
                phone: String(r.person_in_charge_phone ?? "").trim(),
                note: String(r.notes ?? "").trim(),
            }));
        }

        n = Math.min(
            50,
            Math.max(1, drHasCount ? drCountRaw : seeds.length || 1),
        );
        seeds = seeds.slice(0, n);
        while (seeds.length < n) seeds.push(emptyNamedPassenger());

        passengers.value = seeds.length
            ? seeds
            : Array.from({ length: n }, () => emptyNamedPassenger());
        passengerCount.value = n;
    }

    return {
        passengerCount,
        passengers,
        syncPassengers,
        hydrateFromTrip,
    };
}
