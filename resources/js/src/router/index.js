import { createRouter, createWebHistory } from "vue-router";
import { TOKEN_KEY } from "../api/http";
import { useAuthStore } from "../store";

import DashboardView from "../views/DashboardView.vue";
import LoginView from "../views/auth/LoginView.vue";
import RequestsListView from "../views/requests/RequestsListView.vue";
import RequestDetailView from "../views/requests/RequestDetailView.vue";
import DispatchRequestCreateView from "../views/requests/DispatchRequestCreateView.vue";
import AuditLogsView from "../views/audit/AuditLogsView.vue";
import TripsListView from "../views/trips/TripsListView.vue";
import TripDetailView from "../views/trips/TripDetailView.vue";
import CostsListView from "../views/costs/CostsListView.vue";
import PaymentsHubView from "../views/payments/PaymentsHubView.vue";
import CargoListView from "../views/cargo/CargoListView.vue";
import RoutesListView from "../views/d2d/RoutesListView.vue";
import StudentsListView from "../views/d2d/StudentsListView.vue";
import ReportsView from "../views/reports/ReportsView.vue";
import PricingReferenceView from "../views/pricing/PricingReferenceView.vue";
import OperationsHubView from "../views/hub/OperationsHubView.vue";
import ScheduleWeekView from "../views/schedule/ScheduleWeekView.vue";
import ProfileView from "../views/profile/ProfileView.vue";
import HelpGuideView from "../views/help/HelpGuideView.vue";
import RoadmapSuggestionsView from "../views/roadmap/RoadmapSuggestionsView.vue";
import NotificationsHubView from "../views/notifications/NotificationsHubView.vue";
import CalendarMonthView from "../views/calendar/CalendarMonthView.vue";
import SystemRolesView from "../views/system/SystemRolesView.vue";
import SystemPermissionsView from "../views/system/SystemPermissionsView.vue";
import SystemUserRolesView from "../views/system/SystemUserRolesView.vue";
import SystemFeatureTogglesView from "../views/system/SystemFeatureTogglesView.vue";
import ResourcesListView from "../views/resources/ResourcesListView.vue";
import DriverDetailView from "../views/resources/DriverDetailView.vue";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: "/login",
            name: "login",
            component: LoginView,
            meta: {
                public: true,
                title: "Đăng nhập",
                subtitle: "Đăng nhập Google",
            },
        },
        {
            path: "/",
            name: "dashboard",
            component: DashboardView,
            meta: { title: "Tổng quan", subtitle: "Tình trạng vận hành" },
        },
        {
            path: "/hub",
            name: "hub",
            component: OperationsHubView,
            meta: { title: "Trung tâm vận hành", subtitle: "Điểm vào nhanh" },
        },
        {
            path: "/schedule",
            name: "schedule",
            component: ScheduleWeekView,
            meta: { title: "Lịch 7 ngày", subtitle: "Chuyến theo ngày" },
        },
        {
            path: "/calendar",
            name: "calendar",
            component: CalendarMonthView,
            meta: { title: "Lịch tháng", subtitle: "Chuyến theo tháng" },
        },
        {
            path: "/profile",
            name: "profile",
            component: ProfileView,
            meta: { title: "Hồ sơ", subtitle: "Tài khoản" },
        },
        {
            path: "/help",
            name: "help",
            component: HelpGuideView,
            meta: { title: "Hướng dẫn", subtitle: "Quy tắc nghiệp vụ" },
        },
        {
            path: "/roadmap",
            name: "roadmap",
            component: RoadmapSuggestionsView,
            meta: { title: "Đề xuất & lộ trình", subtitle: "Tính năng & UX" },
        },
        {
            path: "/notifications",
            name: "notifications",
            component: NotificationsHubView,
            meta: { title: "Thông báo", subtitle: "Sắp có" },
        },

        {
            path: "/requests",
            name: "requests",
            component: RequestsListView,
            meta: { title: "Yêu cầu điều xe", subtitle: "Danh sách" },
        },
        {
            path: "/requests/:id",
            name: "requestDetail",
            component: RequestDetailView,
            meta: { title: "Chi tiết yêu cầu", subtitle: "" },
        },
        {
            path: "/dispatch-requests/new",
            name: "dispatchRequestNew",
            component: DispatchRequestCreateView,
            meta: { title: "Tạo yêu cầu", subtitle: "BR-001 & phiếu giấy" },
        },

        {
            path: "/trips",
            name: "trips",
            component: TripsListView,
            meta: { title: "Chuyến đi", subtitle: "Danh sách" },
        },
        {
            path: "/trips/:id",
            name: "tripDetail",
            component: TripDetailView,
            meta: { title: "Chi tiết chuyến", subtitle: "" },
        },

        {
            path: "/costs",
            name: "costs",
            component: CostsListView,
            meta: { title: "Chi phí", subtitle: "Danh sách & nhập nhanh" },
        },
        {
            path: "/payments",
            name: "payments",
            component: PaymentsHubView,
            meta: { title: "Đối Soát", subtitle: "Kỳ & payments" },
        },
        {
            path: "/cargo",
            name: "cargo",
            component: CargoListView,
            meta: { title: "Hàng hóa", subtitle: "Cargo & SLA" },
        },
        {
            path: "/routes",
            name: "routes",
            component: RoutesListView,
            meta: { title: "Tuyến D2D", subtitle: "Door-to-door" },
        },
        {
            path: "/students",
            name: "students",
            component: StudentsListView,
            meta: { title: "Học sinh", subtitle: "Danh sách" },
        },
        {
            path: "/resources",
            name: "resources",
            component: ResourcesListView,
            meta: {
                title: "Quản lý nguồn lực",
                subtitle: "Xe, tài xế, nhà cung cấp",
                featureKey: "module.operations",
            },
        },
        {
            path: "/resources/drivers/:id",
            name: "driverDetail",
            component: DriverDetailView,
            meta: {
                title: "Chi tiết tài xế",
                subtitle: "Hồ sơ & giấy tờ",
                featureKey: "module.operations",
            },
        },
        {
            path: "/reports",
            name: "reports",
            component: ReportsView,
            meta: { title: "Báo cáo", subtitle: "Theo khoảng thời gian" },
        },
        {
            path: "/pricing",
            name: "pricing",
            component: PricingReferenceView,
            meta: {
                title: "Bảng giá tham chiếu",
                subtitle: "Xe khách & hàng hóa",
            },
        },

        {
            path: "/audit-logs",
            name: "auditLogs",
            component: AuditLogsView,
            meta: {
                title: "Activity log",
                subtitle: "Quyền hạn",
                permission: "audit_log.view",
                featureKey: "module.system.audit",
            },
        },
        {
            path: "/system/roles",
            name: "systemRoles",
            component: SystemRolesView,
            meta: {
                title: "Quản lý Role",
                subtitle: "Quyền hạn",
                permission: "system.roles.manage",
                featureKey: "module.system.roles",
            },
        },
        {
            path: "/system/permissions",
            name: "systemPermissions",
            component: SystemPermissionsView,
            meta: {
                title: "Quản lý Permission",
                subtitle: "Quyền hạn",
                permission: "system.permissions.manage",
                featureKey: "module.system.permissions",
            },
        },
        {
            path: "/system/user-roles",
            name: "systemUserRoles",
            component: SystemUserRolesView,
            meta: {
                title: "Gán quyền người dùng",
                subtitle: "Quyền hạn",
                permission: "system.user_roles.manage",
                featureKey: "module.system.user_roles",
            },
        },
        {
            path: "/system/feature-toggles",
            name: "systemFeatureToggles",
            component: SystemFeatureTogglesView,
            meta: {
                title: "Feature toggle",
                subtitle: "Quyền hạn",
                permission: "system.feature_toggles.manage",
                featureKey: "module.system.feature_toggles",
            },
        },
    ],
});

router.beforeEach(async (to) => {
    if (to.meta.public) {
        return true;
    }
    const auth = useAuthStore();
    if (!localStorage.getItem(TOKEN_KEY)) {
        return { name: "login", query: { redirect: to.fullPath } };
    }
    try {
        if (!auth.user) {
            await auth.fetchMe();
        }
    } catch {
        auth.setToken(null);
        return { name: "login", query: { redirect: to.fullPath } };
    }
    if (to.meta.featureKey) {
        const ft = auth.user?.feature_toggles;
        const k = to.meta.featureKey;
        if (
            !auth.user?.is_superadmin &&
            ft &&
            typeof ft === "object" &&
            Object.prototype.hasOwnProperty.call(ft, k) &&
            ft[k] !== true
        ) {
            return { name: "dashboard" };
        }
    }
    const need = to.meta.permission;
    if (need) {
        const list = Array.isArray(need) ? need : [need];
        if (!auth.hasAnyPermission(list)) {
            return { name: "dashboard" };
        }
    }
    return true;
});

export default router;
