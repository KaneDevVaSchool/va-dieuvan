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
            meta: { title: "Activity log", subtitle: "Truy vết" },
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
    return true;
});

export default router;
