import {
    isPassengerRowFilled,
    isBusinessRowFilled,
} from "../composables/dispatchWizardConstants";
import { parseMoneyVnd } from "./money";

function parseMoney(v) {
    return parseMoneyVnd(v);
}

function rowLineTotal(r) {
    return parseMoney(r.unit_price) + parseMoney(r.extra_fee);
}

function formatCurrency(n) {
    if (n == null || Number.isNaN(Number(n))) return "—";
    try {
        return new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
        }).format(Number(n));
    } catch {
        return `${n} ₫`;
    }
}

/**
 * BM.03 hiển thị từ bản ghi có `wizard_snapshot` trong DB (được lưu khi tạo yêu cầu từ wizard).
 * @param {Record<string, unknown>|null|undefined} snapshot
 */
export function buildBm03BodyFromWizardSnapshot(snapshot) {
    if (!snapshot || typeof snapshot !== "object") return "";
    const f =
        snapshot.form && typeof snapshot.form === "object" ? snapshot.form : {};
    const passengerRows = Array.isArray(snapshot.passengerRows)
        ? snapshot.passengerRows
        : [];
    const businessRows = Array.isArray(snapshot.businessRows)
        ? snapshot.businessRows
        : [];
    const cargoRows = Array.isArray(snapshot.cargoRows)
        ? snapshot.cargoRows
        : [];

    const isCargo = f.trip_type === "cargo";
    const isPointToPoint = f.trip_type === "point_to_point";
    const isBusiness = f.trip_type === "business";
    const basisName =
        typeof f.basisFileName === "string" ? f.basisFileName.trim() : "";

    const lines = [];
    lines.push("=== ĐỀ NGHỊ ĐIỀU VẬN (BM.03/MH.QT.04 — bản điện tử) ===");
    lines.push("");
    lines.push("Người đề nghị");
    lines.push(`- Họ tên: ${f.requester_name || "—"}`);
    lines.push(`- Email: ${f.requester_email || "—"}`);
    lines.push(`- Điện thoại: ${f.requester_phone || "—"}`);
    lines.push(`- Đơn vị: ${f.requester_unit || "—"}`);
    lines.push("");
    lines.push("Mục đích sử dụng");
    lines.push(`- Mục đích: ${f.purpose || "—"}`);
    if (isPointToPoint) {
        const pk =
            f.point_purpose_kind === "extracurricular"
                ? "Hoạt động ngoại khóa"
                : "Điểm — Điểm";
        lines.push(`- Phân loại mục đích: ${pk}`);
    }
    if (basisName) {
        lines.push(`- Căn cứ đề xuất: đính kèm tệp «${basisName}»`);
    } else {
        lines.push("- Căn cứ đề xuất: (chưa đính kèm tệp)");
    }
    lines.push("");
    lines.push("Thời gian");
    lines.push(`- Ngày đề xuất: ${f.proposed_date || "—"}`);
    lines.push(`- Ngày cần sử dụng xe: ${f.date_needed || "—"}`);
    if (f.is_urgent) lines.push(`- GẤP — Lý do: ${f.urgent_reason || "—"}`);
    lines.push("");
    lines.push("Đối tượng / điều phối");
    const targets = Array.isArray(f.targets) ? f.targets : [];
    lines.push(`- Đối tượng: ${targets.length ? targets.join(", ") : "—"}`);
    lines.push(
        `- Điều phối: ${f.coordinator_name || "—"} | ${f.coordinator_email || "—"} | ${f.coordinator_phone || "—"}`,
    );
    lines.push("");

    if (isCargo) {
        lines.push("Nội dung đề nghị vận chuyển");
        lines.push("Nội dung chi tiết");
        let cargoSum = 0;
        cargoRows.forEach((r, i) => {
            if (!r.name?.trim()) return;
            cargoSum += parseMoney(r.cost);
            lines.push(
                `${i + 1}. ${r.name} | SL ${r.qty || "—"} | ${r.dimensions || "—"} | ${r.weight || "—"} | ${r.item_notes || ""}`,
            );
            lines.push(
                `   Lấy: ${r.pickup_at || "—"} @ ${r.pickup_place || "—"} — ${r.pickup_contact || "—"}`,
            );
            lines.push(
                `   Giao: ${r.delivery_at || "—"} @ ${r.delivery_place || "—"} — ${r.delivery_contact || "—"}`,
            );
            lines.push(
                `   Vận chuyển: ${r.transport_note || "—"} | Chi phí: ${r.cost || "0"}`,
            );
        });
        lines.push(`Tổng hàng: ${formatCurrency(cargoSum)}`);
        if (f.cargo_extra_notes?.trim())
            lines.push(`Ghi chú khác: ${f.cargo_extra_notes}`);
        if (f.need_porters) {
            lines.push(
                `- Bốc xếp: SL ${f.porter_qty || "—"} — phát sinh ${f.porter_cost || "0"} VNĐ`,
            );
        }
        if (f.interprovincial) {
            lines.push(
                `- Chành xe tỉnh — phát sinh ${f.interprovincial_cost || "0"} VNĐ`,
            );
        }
        let extra = 0;
        if (f.need_porters) extra += parseMoney(f.porter_cost);
        if (f.interprovincial) extra += parseMoney(f.interprovincial_cost);
        lines.push(`Tổng cộng (ước tính): ${formatCurrency(cargoSum + extra)}`);
    } else {
        lines.push("Nội dung đề nghị vận chuyển");
        if (isBusiness) {
            lines.push("Nội dung đề xuất cho nhân sự đi công tác");
            const e2Total = businessRows.reduce(
                (s, r) => s + (isBusinessRowFilled(r) ? rowLineTotal(r) : 0),
                0,
            );
            businessRows.forEach((r, i) => {
                if (!isBusinessRowFilled(r)) return;
                lines.push(
                    `${i + 1}. Đi: ${r.depart_at || "—"} ${r.pickup || "—"} | Dừng: ${r.waypoint || "—"} | Về: ${r.return_at || "—"} ${r.dropoff || "—"} | ${r.guests || "0"} khách | ĐG+PS: ${formatCurrency(rowLineTotal(r))} | ${r.notes || ""}`,
                );
            });
            lines.push(`Tổng (ước tính): ${formatCurrency(e2Total)}`);
            lines.push("Ghi chú khác (công tác)");
            if (f.e2_door_pickup)
                lines.push(`- Đưa đón tận nhà: ${f.e2_door_cost || "0"}`);
            if (f.e2_driver_self)
                lines.push(`- Tài xế tự túc: ${f.e2_driver_self_cost || "0"}`);
            if (f.e2_after_21h)
                lines.push(`- Xe sau 21h: ${f.e2_after_21h_cost || "0"}`);
            lines.push(`Tổng (ước tính): ${formatCurrency(e2Total)}`);
        } else {
            lines.push(
                "Nội dung đề xuất cho chương trình / sự kiện ngoại khóa",
            );
            if (f.multi_day)
                lines.push(
                    "(Dùng nhiều ngày — chi tiết bổ sung khi điều phối.)",
                );
            const e1Total = passengerRows.reduce(
                (s, r) => s + (isPassengerRowFilled(r) ? rowLineTotal(r) : 0),
                0,
            );
            passengerRows.forEach((r, i) => {
                if (!isPassengerRowFilled(r)) return;
                lines.push(
                    `${i + 1}. Đi: ${r.depart_at || "—"} ${r.pickup || "—"} | Về: ${r.return_at || "—"} ${r.dropoff || "—"} | ${r.guests || "0"} khách | NV: ${r.person_in_charge || "—"} | ĐG ${r.unit_price || "0"} + PS ${r.extra_fee || "0"} | ${r.notes || ""}`,
                );
            });
            lines.push(`Tổng (ước tính): ${formatCurrency(e1Total)}`);
            if (!isPointToPoint) {
                const wd = f.e1_weekdays || {};
                const wdLabels = [];
                if (wd.mon) wdLabels.push("T2");
                if (wd.tue) wdLabels.push("T3");
                if (wd.wed) wdLabels.push("T4");
                if (wd.thu) wdLabels.push("T5");
                if (wd.fri) wdLabels.push("T6");
                if (wd.sat) wdLabels.push("T7");
                if (wd.sun) wdLabels.push("CN");
                lines.push("e.1.1 Ghi chú khác đề xuất");
                if (f.e1_use_3plus_days) {
                    lines.push(
                        `- Xe từ 3 ngày trở lên: ${f.e1_from_date || "—"} → ${f.e1_to_date || "—"} | Tổng ngày: ${f.e1_days_total || "—"} | Phát sinh: ${f.e1_extra_cost || "0"}`,
                    );
                }
                if (wdLabels.length)
                    lines.push(`- Các thứ trong tuần: ${wdLabels.join(", ")}`);
                lines.push("Nội dung đề xuất cho nhân sự đi công tác");
                const e2Total = businessRows.reduce(
                    (s, r) =>
                        s + (isBusinessRowFilled(r) ? rowLineTotal(r) : 0),
                    0,
                );
                businessRows.forEach((r, i) => {
                    if (!isBusinessRowFilled(r)) return;
                    lines.push(
                        `${i + 1}. Đi: ${r.depart_at || "—"} ${r.pickup || "—"} | Dừng: ${r.waypoint || "—"} | Về: ${r.return_at || "—"} ${r.dropoff || "—"} | ${r.guests || "0"} khách | ĐG+PS: ${formatCurrency(rowLineTotal(r))} | ${r.notes || ""}`,
                    );
                });
                lines.push(`Tổng (ước tính): ${formatCurrency(e2Total)}`);
                lines.push("Ghi chú khác (công tác)");
                if (f.e2_door_pickup)
                    lines.push(`- Đưa đón tận nhà: ${f.e2_door_cost || "0"}`);
                if (f.e2_driver_self)
                    lines.push(
                        `- Tài xế tự túc: ${f.e2_driver_self_cost || "0"}`,
                    );
                if (f.e2_after_21h)
                    lines.push(`- Xe sau 21h: ${f.e2_after_21h_cost || "0"}`);
                lines.push(
                    `Tổng (ước tính): ${formatCurrency(e1Total + e2Total)}`,
                );
            }
        }
    }

    lines.push("");
    lines.push(
        "--- Hệ thống: các trường trên được gửi kèm để bộ phận Điều vận xử lý.",
    );
    return lines.join("\n");
}
