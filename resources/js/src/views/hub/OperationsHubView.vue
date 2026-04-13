<template>
    <div class="space-y-6">
        <div
            class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-700 p-5 text-white shadow-sm md:p-6"
        >
            <h1 class="text-lg font-semibold md:text-xl">Trung tâm vận hành</h1>
            <p class="mt-1 max-w-2xl text-sm text-white/85">
                Điểm vào nhanh cho điều vận: tạo yêu cầu, duyệt, phân công, theo
                dõi chi phí và hàng. Tối ưu cho cả desktop và mobile.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                <RouterLink
                    class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 shadow hover:bg-slate-100"
                    to="/dispatch-requests/new"
                >
                    + Tạo yêu cầu
                </RouterLink>
                <RouterLink
                    class="rounded-lg border border-white/40 px-4 py-2 text-sm text-white hover:bg-white/10"
                    to="/schedule"
                >
                    Lịch 7 ngày
                </RouterLink>
                <RouterLink
                    class="rounded-lg border border-white/40 px-4 py-2 text-sm text-white hover:bg-white/10"
                    to="/requests?status=pending"
                >
                    Yêu cầu chờ duyệt
                </RouterLink>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatPill
                label="Yêu cầu chờ duyệt"
                :value="stats.pendingRequests"
                :loading="statsLoading"
                tone="amber"
            />
            <StatPill
                label="Chuyến (7 ngày tới)"
                :value="stats.tripsWeek"
                :loading="statsLoading"
                tone="slate"
            />
            <StatPill
                label="Cargo đang xử lý"
                :value="stats.cargoActive"
                :loading="statsLoading"
                tone="emerald"
            />
            <StatPill
                label="Báo cáo tổng quan"
                :value="stats.summaryOk"
                :loading="statsLoading"
                tone="violet"
            />
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <HubCard
                to="/requests"
                title="Yêu cầu điều xe"
                desc="Lọc theo trạng thái, kênh, phiếu giấy."
                tag="Duyệt"
            />
            <HubCard
                to="/trips"
                title="Chuyến đi"
                desc="Phân công xe, tài xế, cập nhật trạng thái."
                tag="Điều phối"
            />
            <HubCard
                to="/cargo"
                title="Hàng hóa"
                desc="SLA, POD, trạng thái shipment."
                tag="Cargo"
            />
            <HubCard
                to="/costs"
                title="Chi phí"
                desc="Ghi nhận, đối soát chi phí chuyến."
                tag="Kế toán"
            />
            <HubCard
                to="/payments"
                title="Đối Soát"
                desc="Kỳ, payment, thực hiện chi."
                tag="KT"
            />
            <HubCard
                to="/routes"
                title="Tuyến D2D"
                desc="Phiên bản tuyến, học sinh, generate trip."
                tag="D2D"
            />
            <HubCard
                to="/pricing"
                title="Bảng giá tham chiếu"
                desc="Xe khách & hàng — tra cứu nội bộ."
                tag="Giá"
            />
            <HubCard
                to="/reports"
                title="Báo cáo"
                desc="Tổng hợp theo khoảng thời gian."
                tag="BC"
            />
            <HubCard
                to="/audit-logs"
                title="Activity log"
                desc="Truy vết thao tác hệ thống."
                tag="Audit"
            />
        </div>

        <Card title="Gợi ý làm việc theo vai trò">
            <ul class="grid gap-3 text-sm md:grid-cols-2">
                <li
                    class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2"
                >
                    <span class="font-medium text-slate-800"
                        >Cán bộ nội bộ:</span
                    >
                    tạo yêu cầu, theo dõi trạng thái, upload scan phiếu giấy.
                </li>
                <li
                    class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2"
                >
                    <span class="font-medium text-slate-800">Điều vận:</span>
                    duyệt yêu cầu, gán chuyến, xem lịch tuần, xử lý gấp.
                </li>
                <li
                    class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2"
                >
                    <span class="font-medium text-slate-800">Tài xế:</span>
                    cập nhật trạng thái chuyến, đính kèm, ghi chi phí (theo
                    quyền).
                </li>
                <li
                    class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2"
                >
                    <span class="font-medium text-slate-800">Kế toán:</span>
                    chi phí, kỳ đối soát, thanh toán NCC.
                </li>
            </ul>
        </Card>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import Card from "../../components/ui/Card.vue";
import HubCard from "./HubCard.vue";
import StatPill from "./StatPill.vue";
import { listRequests } from "../../api/requests";
import { listTrips } from "../../api/trips";
import { getSummary } from "../../api/reports";
import { listCargoShipments } from "../../api/cargo";
import { eachLocalDayKey } from "../../util/dates";

const statsLoading = ref(true);
const stats = reactive({
    pendingRequests: null,
    tripsWeek: null,
    cargoActive: null,
    summaryOk: null,
});

async function loadStats() {
    statsLoading.value = true;
    const today = new Date();
    const keys = eachLocalDayKey(today, 7);
    const from = keys[0];
    const to = keys[keys.length - 1];

    const [rReq, rTrip, rCargo, rSum] = await Promise.allSettled([
        listRequests({ status: "pending", per_page: 1, page: 1 }),
        listTrips({ from, to, per_page: 100, page: 1 }),
        listCargoShipments({ per_page: 50, page: 1 }),
        getSummary(),
    ]);

    stats.pendingRequests =
        rReq.status === "fulfilled" ? (rReq.value.meta?.total ?? 0) : null;
    stats.tripsWeek =
        rTrip.status === "fulfilled" ? (rTrip.value.meta?.total ?? 0) : null;
    if (rCargo.status === "fulfilled") {
        const items = rCargo.value.items ?? [];
        stats.cargoActive = items.filter((s) =>
            ["pending", "picked_up", "in_transit"].includes(s.status),
        ).length;
    } else {
        stats.cargoActive = null;
    }
    stats.summaryOk = rSum.status === "fulfilled" ? "Đồng bộ" : null;

    statsLoading.value = false;
}

onMounted(loadStats);
</script>
