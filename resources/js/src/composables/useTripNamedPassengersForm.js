import { ref, watch } from "vue";
import { usePassengerManager } from "./usePassengerManager";

/**
 * Form lưu danh sách hành khách đặt tên (door_to_door / point_to_point).
 *
 * @param {{
 *   tripRef: import('vue').Ref<Record<string, unknown> | null>,
 *   updateTripPassengerList: (tripId: string|number, payload: Record<string, unknown>) => Promise<unknown>,
 *   load: (opts?: { silent?: boolean }) => Promise<void>,
 *   showAppError: (msg: string) => void,
 *   showAppSuccess: (msg: string) => void,
 *   formatApiMessage: (e: unknown) => string,
 *   t: (key: string) => string,
 * }} ctx
 */
export function useTripNamedPassengersForm(ctx) {
    const { passengerCount, passengers, hydrateFromTrip } =
        usePassengerManager();
    const saving = ref(false);

    watch(
        () => ctx.tripRef.value?.id,
        (id) => {
            if (id != null && ctx.tripRef.value) {
                hydrateFromTrip(ctx.tripRef.value);
            }
        },
        { immediate: true },
    );

    async function submitNamedPassengerList() {
        const trip = ctx.tripRef.value;
        const tid = trip?.id;
        if (tid == null) return false;

        const n = Number(passengerCount.value);
        const list = passengers.value ?? [];
        if (!Number.isFinite(n) || n < 1 || n > 50) {
            ctx.showAppError(ctx.t("trip_detail.passengers.named_invalid_count"));
            return false;
        }
        if (list.length !== n) {
            ctx.showAppError(ctx.t("trip_detail.passengers.named_count_mismatch"));
            return false;
        }

        for (let i = 0; i < list.length; i++) {
            const row = list[i];
            if (!String(row?.name ?? "").trim()) {
                ctx.showAppError(
                    ctx.t("trip_detail.passengers.named_name_required", {
                        n: i + 1,
                    }),
                );
                return false;
            }
        }

        saving.value = true;
        try {
            await ctx.updateTripPassengerList(tid, {
                passenger_count: n,
                passengers: list.map((r) => ({
                    name: String(r.name ?? "").trim(),
                    phone: String(r.phone ?? "").trim() || null,
                    note: String(r.note ?? "").trim() || null,
                })),
            });
            ctx.showAppSuccess(ctx.t("trip_detail.passengers.named_save_ok"));
            await ctx.load({ silent: true });
            return true;
        } catch (e) {
            ctx.showAppError(ctx.formatApiMessage(e));
            return false;
        } finally {
            saving.value = false;
        }
    }

    return {
        passengerCount,
        passengers,
        saving,
        submitNamedPassengerList,
        hydrateFromTrip,
    };
}

export const useTripForm = useTripNamedPassengersForm;
