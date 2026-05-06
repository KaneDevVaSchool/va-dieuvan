import { listDrivers } from "../api/operational";

/** @type {Record<string, unknown>[] | null} */
let cachedDriverItems = null;

/** @type {Promise<Record<string, unknown>[]> | null} */
let driversInflight = null;

/**
 * Danh mục tài xế (active) — dùng chung ResourcePanel và TripDetail.
 * Không lọc availability_status để xe/tuyến vẫn gán được tài xế đang «busy» theo HR.
 *
 * @param {boolean} [force=false] — ép tải lại (silent refresh không bắt buộc)
 * @returns {Promise<Record<string, unknown>[]>}
 */
export async function fetchDriversCatalog(force = false) {
    if (force) {
        const res = await listDrivers({
            employment_status: "active",
            per_page: 150,
        });
        cachedDriverItems = res.items ?? [];
        return cachedDriverItems;
    }
    if (cachedDriverItems) return cachedDriverItems;
    if (driversInflight) return driversInflight;

    driversInflight = listDrivers({ employment_status: "active", per_page: 150 })
        .then((res) => {
            cachedDriverItems = res.items ?? [];
            return cachedDriverItems;
        })
        .finally(() => {
            driversInflight = null;
        });

    return driversInflight;
}

export function invalidateDriversCatalog() {
    cachedDriverItems = null;
}
