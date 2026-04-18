import { createRouter, createWebHistory } from "vue-router";
import { TOKEN_KEY } from "../api/http";
import { useAuthStore } from "../store";
import { applyRouteDocumentTitle } from "../util/routeDocumentTitle";

/** Tải theo route — giảm bundle trang đầu; giữ các màn hay dùng là import tĩnh. */
import DashboardView from "../views/DashboardView.vue";
import LoginView from "../views/auth/LoginView.vue";
import RequestsListView from "../views/requests/RequestsListView.vue";
import RequestDetailView from "../views/requests/RequestDetailView.vue";
import ProfileView from "../views/profile/ProfileView.vue";

function scrollAppMainToTop() {
    if (typeof document === "undefined") return;
    requestAnimationFrame(() => {
        document.getElementById("app-main-scroll")?.scrollTo({ top: 0, behavior: "auto" });
    });
}

function updateDocumentTitle(to) {
    applyRouteDocumentTitle(to.meta, to.name);
}

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior() {
        return false;
    },
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
            meta: {
                title: "Tổng quan",
                subtitle: "Tình trạng vận hành",
                featureKey: "module.overview",
            },
        },
        {
            path: "/dispatcher",
            name: "dispatcherBoard",
            component: () => import("../views/dispatcher/DispatcherBoardView.vue"),
            meta: {
                title: "Bảng điều vận",
                subtitle: "Hàng đợi & lịch phân công",
                featureKey: "module.overview",
            },
        },
        {
            path: "/profile",
            name: "profile",
            component: ProfileView,
            meta: { title: "Hồ sơ", subtitle: "Tài khoản" },
        },
        {
            path: "/requests",
            name: "requests",
            component: RequestsListView,
            meta: {
                title: "Yêu cầu điều xe",
                subtitle: "Danh sách & trạng thái",
                featureKey: "module.operations",
            },
        },
        {
            path: "/requests/:id",
            name: "requestDetail",
            component: RequestDetailView,
            meta: {
                title: "Chi tiết yêu cầu",
                subtitle: "",
                featureKey: "module.operations",
            },
        },
        {
            path: "/dispatch-requests/new",
            name: "dispatchRequestNew",
            component: () =>
                import("../views/requests/DispatchRequestCreateView.vue"),
            meta: {
                title: "Tạo yêu cầu điều vận",
                subtitle: "BM.03 — luồng 4 bước",
                featureKey: "module.operations",
            },
        },

        {
            path: "/trips",
            name: "trips",
            component: () => import("../views/trips/TripsListView.vue"),
            meta: {
                title: "Chuyến đi",
                subtitle: "Danh sách",
                featureKey: "module.operations",
            },
        },
        {
            path: "/trips/:id",
            name: "tripDetail",
            component: () => import("../views/trips/TripDetailView.vue"),
            meta: {
                title: "Chi tiết chuyến",
                subtitle: "",
                featureKey: "module.operations",
            },
        },

        {
            path: "/costs",
            name: "costs",
            component: () => import("../views/costs/CostsListView.vue"),
            meta: {
                title: "Chi phí",
                subtitle: "Danh sách & nhập nhanh",
                featureKey: "module.operations",
            },
        },
        {
            path: "/payments",
            name: "payments",
            component: () => import("../views/payments/PaymentsHubView.vue"),
            meta: {
                title: "Đối Soát",
                subtitle: "Kỳ & payments",
                featureKey: "module.finance",
            },
        },
        {
            path: "/cargo/:id(\\d+)",
            name: "cargoDetail",
            component: () => import("../views/cargo/CargoDetailView.vue"),
            meta: {
                title: "Chi tiết đơn hàng",
                subtitle: "Cargo",
                featureKey: "module.operations",
            },
        },
        {
            path: "/cargo",
            name: "cargo",
            component: () => import("../views/cargo/CargoListView.vue"),
            meta: {
                title: "Hàng hóa",
                subtitle: "Cargo & SLA",
                featureKey: "module.operations",
            },
        },
        {
            path: "/routes",
            name: "routes",
            component: () => import("../views/d2d/RoutesListView.vue"),
            meta: {
                title: "Tuyến D2D",
                subtitle: "Door-to-door",
                featureKey: "module.operations",
            },
        },
        {
            path: "/resources/dashboard",
            redirect: "/resources",
        },
        {
            path: "/resources/list",
            name: "resourcesList",
            component: () => import("../views/resources/ResourcesListView.vue"),
            meta: {
                title: "Quản lý nguồn lực",
                subtitle: "Xe, tài xế, nhà cung cấp",
                featureKey: "module.operations",
            },
        },
        {
            path: "/resources",
            name: "resources",
            component: () => import("../views/resources/ResourcesDashboardView.vue"),
            meta: {
                title: "Dashboard nguồn lực",
                subtitle: "Lịch & tổng quan",
                featureKey: "module.operations",
            },
        },
        {
            path: "/resources/drivers/:id",
            name: "driverDetail",
            component: () => import("../views/resources/DriverDetailView.vue"),
            meta: {
                title: "Chi tiết tài xế",
                subtitle: "Hồ sơ & giấy tờ",
                featureKey: "module.operations",
            },
        },
        {
            path: "/reports",
            name: "reports",
            component: () => import("../views/reports/ReportsView.vue"),
            meta: {
                title: "Báo cáo",
                subtitle: "Theo khoảng thời gian",
                featureKey: "module.reports",
            },
        },
        {
            path: "/pricing",
            name: "pricing",
            component: () => import("../views/pricing/PricingReferenceView.vue"),
            meta: {
                title: "Bảng giá tham chiếu",
                subtitle: "Xe khách & hàng hóa",
                featureKey: "module.reports",
            },
        },

        {
            path: "/audit-logs",
            name: "auditLogs",
            component: () => import("../views/audit/AuditLogsView.vue"),
            meta: {
                title: "Nhật ký hoạt động",
                subtitle: "Quản trị",
                permission: "audit_log.view",
                featureKey: "module.system.audit",
            },
        },
        {
            path: "/system/roles",
            name: "systemRoles",
            component: () => import("../views/system/SystemRolesView.vue"),
            meta: {
                title: "Vai trò người dùng",
                subtitle: "Quản trị",
                permission: "system.roles.manage",
                featureKey: "module.system.roles",
            },
        },
        {
            path: "/system/permissions",
            name: "systemPermissions",
            component: () => import("../views/system/SystemPermissionsView.vue"),
            meta: {
                title: "Quyền thao tác",
                subtitle: "Quản trị",
                permission: "system.permissions.manage",
                featureKey: "module.system.permissions",
            },
        },
        {
            path: "/system/user-roles",
            name: "systemUserRoles",
            component: () => import("../views/system/SystemUserRolesView.vue"),
            meta: {
                title: "Gán vai trò tài khoản",
                subtitle: "Quản trị",
                permission: "system.user_roles.manage",
                featureKey: "module.system.user_roles",
            },
        },
        {
            path: "/system/feature-toggles",
            name: "systemFeatureToggles",
            component: () => import("../views/system/SystemFeatureTogglesView.vue"),
            meta: {
                title: "Bật tắt tính năng menu",
                subtitle: "Quản trị",
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

router.afterEach((to) => {
    updateDocumentTitle(to);
    scrollAppMainToTop();
});

export default router;
