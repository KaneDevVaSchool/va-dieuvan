import { createRouter, createWebHistory } from "vue-router";
import { TOKEN_KEY } from "../core/config/authKeys";
import {
    DISPATCH_WEB_BASE,
    buildStaffPrefixedPath,
    shouldRewriteLegacyStaffPath,
} from "../config/dispatchWebBase";
import { useAuthStore } from "../store";
import { applyRouteDocumentTitle } from "../util/routeDocumentTitle";
import { resolvePostLoginTarget } from "../util/loginRedirect";
import {
    CHUNK_RELOAD_SESSION_KEY,
    isStaleChunkMessage,
    reloadOnceForStaleAssets,
} from "../pwa/staleBuildRecovery";

export { CHUNK_RELOAD_SESSION_KEY };

function scrollAppMainToTop() {
    if (typeof document === "undefined") return;
    requestAnimationFrame(() => {
        document.getElementById("app-main-scroll")?.scrollTo({ top: 0, behavior: "auto" });
        document.getElementById("dept-main-scroll")?.scrollTo({ top: 0, behavior: "auto" });
    });
}

function updateDocumentTitle(to) {
    applyRouteDocumentTitle(to.meta, to.name);
}

function pathIsUnderStaffBase(path) {
    return path === DISPATCH_WEB_BASE || path.startsWith(`${DISPATCH_WEB_BASE}/`);
}

function routeHasPortalMeta(to) {
    return to.matched.some((record) => record.meta.portal === true);
}

function routeHasDeptHeadMeta(to) {
    return to.matched.some((record) => record.meta.deptHead === true);
}

const staffChildRoutes = [
    {
        path: "",
        name: "dashboard",
        component: () => import("../views/DashboardView.vue"),
        meta: {
            title: "Tổng quan",
            subtitle: "Tình trạng vận hành",
            featureKey: "module.overview",
        },
    },
    {
        path: "dispatcher",
        name: "dispatcherBoard",
        component: () => import("../views/dispatcher/DispatcherBoardView.vue"),
        meta: {
            title: "Bảng điều vận",
            subtitle: "Hàng đợi & lịch phân công",
            featureKey: "module.overview",
        },
    },
    {
        path: "profile",
        name: "profile",
        component: () => import("../views/profile/ProfileView.vue"),
        meta: { title: "Hồ sơ", subtitle: "Tài khoản" },
    },
    {
        path: "requests",
        name: "requests",
        component: () => import("../views/requests/RequestsListView.vue"),
        meta: {
            title: "Yêu cầu điều xe",
            subtitle: "Danh sách & trạng thái",
            featureKey: "module.operations",
        },
    },
    {
        path: "requests/:id",
        name: "requestDetail",
        component: () => import("../views/requests/RequestDetailView.vue"),
        meta: {
            title: "Chi tiết yêu cầu",
            subtitle: "",
            featureKey: "module.operations",
        },
    },
    {
        path: "dispatch-requests/new",
        name: "dispatchRequestNew",
        component: () => import("../views/requests/DispatchRequestCreateView.vue"),
        meta: {
            title: "Tạo yêu cầu điều vận",
            subtitle: "BM.03 — luồng 4 bước",
            featureKey: "module.operations",
        },
    },
    {
        path: "trips",
        name: "trips",
        component: () => import("../views/trips/TripsListView.vue"),
        meta: {
            title: "Chuyến đi",
            subtitle: "Danh sách",
            featureKey: "module.operations",
        },
    },
    {
        path: "trips/:id",
        name: "tripDetail",
        component: () => import("../views/trips/TripDetailView.vue"),
        meta: {
            title: "Chi tiết chuyến",
            subtitle: "",
            featureKey: "module.operations",
        },
    },
    {
        path: "costs",
        name: "costs",
        component: () => import("../views/costs/CostsListView.vue"),
        meta: {
            title: "Chi phí",
            subtitle: "Danh sách & nhập nhanh",
            featureKey: "module.operations",
        },
    },
    {
        path: "cargo/:id(\\d+)",
        name: "cargoDetail",
        component: () => import("../views/cargo/CargoDetailView.vue"),
        meta: {
            title: "Chi tiết đơn hàng",
            subtitle: "Cargo",
            featureKey: "module.operations",
        },
    },
    {
        path: "cargo",
        name: "cargo",
        component: () => import("../views/cargo/CargoListView.vue"),
        meta: {
            title: "Hàng hóa",
            subtitle: "Cargo & SLA",
            featureKey: "module.operations",
        },
    },
    {
        path: "routes",
        redirect: { name: "p2pPolicyRoutes" },
    },
    {
        path: "p2p-policy",
        name: "p2pPolicyHub",
        redirect: { name: "p2pPolicyTrips" },
    },
    {
        path: "p2p-policy/trips",
        name: "p2pPolicyTrips",
        component: () => import("../views/p2pPolicy/PolicyTripsDashboardView.vue"),
        meta: {
            title: "Chuyến học sinh chính sách",
            subtitle: "Policy P2P",
            featureKey: "module.p2p_policy",
            permission: "policy_trip.view",
        },
    },
    {
        path: "p2p-policy/students",
        name: "p2pPolicyStudents",
        component: () => import("../views/p2pPolicy/StudentPoliciesView.vue"),
        meta: {
            title: "Học sinh chính sách",
            subtitle: "Quản lý danh sách",
            featureKey: "module.p2p_policy",
            permission: "student_policy.manage",
        },
    },
    {
        path: "p2p-policy/calendar",
        name: "p2pPolicyCalendar",
        component: () => import("../views/p2pPolicy/SchoolCalendarView.vue"),
        meta: {
            title: "Lịch học",
            subtitle: "Cấu hình học kỳ",
            featureKey: "module.p2p_policy",
            permission: "school_calendar.manage",
        },
    },
    {
        path: "p2p-policy/routes",
        name: "p2pPolicyRoutes",
        component: () => import("../views/p2pPolicy/PolicyRoutesView.vue"),
        meta: {
            title: "Tuyến Policy",
            subtitle: "P2P",
            featureKey: "module.p2p_policy",
        },
    },
    {
        path: "p2p-policy/absence-report",
        name: "p2pPolicyAbsenceReport",
        component: () => import("../views/p2pPolicy/PolicyAbsenceReportView.vue"),
        meta: {
            title: "Báo cáo vắng",
            subtitle: "Theo tuần",
            featureKey: "module.p2p_policy",
            permission: "policy_trip.view",
        },
    },
    {
        path: "transport-programs",
        name: "tpPrograms",
        component: () => import("../views/transportProgram/TpProgramListView.vue"),
        meta: {
            title: "Chương trình đưa đón",
            subtitle: "Transport Program",
            permission: "tp_program.view",
        },
    },
    {
        path: "transport-programs/create",
        name: "tpProgramCreate",
        component: () => import("../views/transportProgram/TpProgramCreateView.vue"),
        meta: {
            title: "Tạo chương trình",
            subtitle: "Transport Program",
            permission: "tp_program.manage",
        },
    },
    {
        path: "transport-programs/:id",
        name: "tpProgramWorkspace",
        component: () => import("../views/transportProgram/TpProgramWorkspaceView.vue"),
        meta: {
            title: "Chi tiết chương trình",
            subtitle: "Transport Program",
            permission: "tp_program.view",
        },
    },
    {
        path: "transport-programs/:id/enroll",
        name: "tpEnrollStudents",
        component: () => import("../views/transportProgram/TpEnrollStudentsView.vue"),
        meta: {
            title: "Đăng ký học sinh",
            subtitle: "Transport Program",
            permission: "tp_enrollment.manage",
        },
    },
    {
        path: "transport-program-days/:dayId/attendance",
        name: "tpDayAttendance",
        component: () => import("../views/transportProgram/TpDayAttendanceView.vue"),
        meta: {
            title: "Điểm danh ngày",
            subtitle: "Transport Program",
            permission: "tp_attendance.manage",
        },
    },
    {
        path: "transport-students",
        name: "tpStudents",
        component: () => import("../views/tpStudent/TpStudentListView.vue"),
        meta: {
            title: "Học sinh",
            subtitle: "Transport Program",
            permission: "tp_student.view",
        },
    },
    {
        path: "transport-students/import",
        name: "tpImportWizard",
        component: () => import("../views/tpStudent/TpImportWizardView.vue"),
        meta: {
            title: "Nhập học sinh",
            subtitle: "Transport Program",
            permission: "tp_import.manage",
        },
    },
    {
        path: "resources/dashboard",
        redirect: { name: "resources" },
    },
    {
        path: "resources/list",
        name: "resourcesList",
        component: () => import("../views/resources/ResourcesListView.vue"),
        meta: {
            title: "Quản lý nguồn lực",
            subtitle: "Xe, tài xế, nhà cung cấp",
            featureKey: "module.operations",
        },
    },
    {
        path: "resources",
        name: "resources",
        component: () => import("../views/resources/ResourcesDashboardView.vue"),
        meta: {
            title: "Dashboard nguồn lực",
            subtitle: "Lịch & tổng quan",
            featureKey: "module.operations",
        },
    },
    {
        path: "resources/drivers/:id",
        name: "driverDetail",
        component: () => import("../views/resources/DriverDetailView.vue"),
        meta: {
            title: "Chi tiết tài xế",
            subtitle: "Hồ sơ & giấy tờ",
            featureKey: "module.operations",
        },
    },
    {
        path: "resources/vehicles/:id",
        name: "vehicleDetail",
        component: () => import("../views/resources/VehicleDetailView.vue"),
        meta: {
            title: "Chi tiết xe",
            subtitle: "Hồ sơ & giấy tờ",
            featureKey: "module.operations",
        },
    },
    {
        path: "reports",
        name: "reports",
        component: () => import("../views/reports/ReportsView.vue"),
        meta: {
            title: "Báo cáo",
            subtitle: "Theo khoảng thời gian",
            featureKey: "module.reports",
        },
    },
    {
        path: "pricing",
        name: "pricing",
        component: () => import("../views/pricing/PricingReferenceView.vue"),
        meta: {
            title: "Bảng giá tham chiếu",
            subtitle: "Xe khách & hàng hóa",
            featureKey: "module.pricing",
        },
    },
    {
        path: "audit-logs",
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
        path: "system/audit-logs/:id",
        name: "systemAuditDetail",
        component: () => import("../views/system/SystemAuditView.vue"),
        meta: {
            title: "Chi tiết audit",
            subtitle: "Quản trị",
            permission: "audit_log.view",
            featureKey: "module.system.audit",
        },
    },
    {
        path: "system/roles",
        name: "systemRoles",
        component: () => import("../views/system/SystemRolesView.vue"),
        meta: {
            title: "Vai trò & Phân quyền",
            subtitle: "Quản trị",
            permission: "system.roles.manage",
            featureKey: "module.system.roles",
        },
    },
    {
        path: "system/roles/new",
        name: "systemRoleNew",
        component: () => import("../views/system/SystemRoleEditorView.vue"),
        meta: {
            title: "Tạo vai trò mới",
            subtitle: "Quản trị",
            permission: "system.roles.manage",
            featureKey: "module.system.roles",
        },
    },
    {
        path: "system/roles/:id(\\d+)",
        name: "systemRoleDetail",
        component: () => import("../views/system/SystemRoleDetailView.vue"),
        meta: {
            title: "Chi tiết vai trò",
            subtitle: "Quản trị",
            permission: "system.roles.manage",
            featureKey: "module.system.roles",
        },
    },
    {
        path: "system/roles/:id(\\d+)/edit",
        name: "systemRoleEdit",
        component: () => import("../views/system/SystemRoleEditorView.vue"),
        meta: {
            title: "Chỉnh sửa vai trò",
            subtitle: "Quản trị",
            permission: "system.roles.manage",
            featureKey: "module.system.roles",
        },
    },
    {
        path: "system/permissions",
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
        path: "system/user-roles",
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
      path: "system/feature-toggles",
      name: "systemFeatureToggles",
      component: () => import("../views/system/SystemFeatureTogglesView.vue"),
      meta: {
        title: "Bật tắt tính năng menu",
        subtitle: "Quản trị",
        permission: "system.feature_toggles.manage",
        featureKey: "module.system.feature_toggles",
      },
    },
    {
      path: "system/dispatch-settings",
      name: "systemDispatchSettings",
      component: () => import("../views/system/SystemDispatchSettingsView.vue"),
      meta: {
        title: "Ngưỡng Gấp",
        subtitle: "Quản trị",
        permission: "dispatch.settings.manage",
        featureKey: "module.system.dispatch_settings",
      },
    },
    {
      path: "notifications",
      name: "notificationsHub",
      component: () => import("../views/notifications/NotificationsHubView.vue"),
      meta: {
        title: "Thông báo",
        subtitle: "Hộp thư",
      },
    },
];

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior() {
        return false;
    },
    routes: [
        {
            path: "/",
            name: "home",
            component: () => import("../views/auth/LoginView.vue"),
            meta: {
                public: true,
                title: "Đăng nhập",
                subtitle: "Đăng nhập Google",
            },
        },
        {
            path: "/login",
            name: "login",
            component: () => import("../views/auth/LoginView.vue"),
            meta: {
                public: true,
                title: "Đăng nhập",
                subtitle: "Đăng nhập Google",
            },
        },
        {
            path: "/portal",
            component: () => import("../views/portal/PortalLayout.vue"),
            meta: {
                portal: true,
            },
            children: [
                {
                    path: "",
                    name: "portalHome",
                    component: () => import("../views/portal/PortalHomeView.vue"),
                    meta: {
                        portal: true,
                    },
                },
                {
                    path: "new",
                    name: "portalCreate",
                    component: () => import("../views/portal/PortalGeneralCreateView.vue"),
                    meta: {
                        portal: true,
                    },
                },
                {
                    path: "notifications",
                    name: "portalNotifications",
                    component: () => import("../views/portal/PortalNotificationsView.vue"),
                    meta: {
                        portal: true,
                    },
                },
                {
                    path: "requests",
                    name: "portalRequestList",
                    component: () => import("../views/portal/PortalRequestListView.vue"),
                    meta: {
                        portal: true,
                    },
                },
                {
                    path: "requests/:id(\\d+)",
                    name: "portalRequestDetail",
                    component: () => import("../views/portal/PortalRequestDetailView.vue"),
                    meta: {
                        portal: true,
                    },
                },
                {
                    path: "extracurricular",
                    name: "portalExtracurricularHome",
                    component: () =>
                        import("../views/portal/PortalExtracurricularHomeView.vue"),
                    meta: {
                        portal: true,
                        portalExtracurricular: true,
                    },
                },
                {
                    path: "extracurricular/requests",
                    name: "portalExtracurricularList",
                    component: () => import("../views/portal/PortalRequestListView.vue"),
                    meta: {
                        portal: true,
                        portalExtracurricular: true,
                    },
                },
                {
                    path: "extracurricular/new",
                    name: "portalExtracurricularCreate",
                    component: () =>
                        import("../views/portal/PortalExtracurricularCreateView.vue"),
                    meta: {
                        portal: true,
                        portalExtracurricular: true,
                    },
                },
                {
                    path: "extracurricular/requests/:id(\\d+)",
                    name: "portalExtracurricularDetail",
                    component: () => import("../views/portal/PortalRequestDetailView.vue"),
                    meta: {
                        portal: true,
                        portalExtracurricular: true,
                    },
                },
            ],
        },
        {
            path: "/dept",
            component: () => import("../views/dept/DeptLayout.vue"),
            meta: { deptHead: true },
            children: [
                {
                    path: "",
                    name: "deptDashboard",
                    component: () => import("../views/dept/DeptDashboardView.vue"),
                    meta: { deptHead: true },
                },
                {
                    path: "approved",
                    name: "deptApproved",
                    component: () => import("../views/dept/DeptApprovedView.vue"),
                    meta: { deptHead: true },
                },
                {
                    path: "rejected",
                    name: "deptRejected",
                    component: () => import("../views/dept/DeptRejectedView.vue"),
                    meta: { deptHead: true },
                },
                {
                    path: "all",
                    name: "deptAll",
                    component: () => import("../views/dept/DeptAllRequestsView.vue"),
                    meta: { deptHead: true },
                },
                {
                    path: "requests/:id(\\d+)",
                    name: "deptRequestDetail",
                    component: () => import("../views/requests/RequestDetailView.vue"),
                    meta: {
                        deptHead: true,
                    },
                },
            ],
        },
        {
            path: DISPATCH_WEB_BASE,
            component: () => import("../views/layout/StaffRouteOutlet.vue"),
            children: staffChildRoutes,
        },
        /** Chỉ tài xế: hồ sơ / chỉnh sửa không dùng `/profile` — vào `/driver/account`. */
        {
            path: "/profile",
            component: () => import("../views/profile/ProfileView.vue"),
            meta: { title: "Hồ sơ", subtitle: "Tài khoản" },
        },
        {
            path: "/driver",
            name: "driverHome",
            component: () => import("../views/driver/DriverDashboard.vue"),
            meta: {
                title: "Tài xế",
                subtitle: "Trang tài xế",
                driverApp: true,
            },
        },
        {
            path: "/driver/schedule",
            name: "driverSchedule",
            component: () => import("../views/driver/DriverScheduleView.vue"),
            meta: {
                title: "Lịch sử chuyến",
                subtitle: "Tài xế",
                driverApp: true,
            },
        },
        {
            path: "/driver/trips",
            redirect: { name: "driverSchedule" },
        },
        {
            path: "/driver/costs",
            name: "driverCosts",
            component: () => import("../views/driver/DriverCostsView.vue"),
            meta: {
                title: "Chi phí",
                subtitle: "Tài xế",
                driverApp: true,
            },
        },
        {
            path: "/driver/costs/:id",
            name: "driverCostDetail",
            component: () => import("../views/driver/DriverCostDetailView.vue"),
            meta: {
                title: "Chi tiết yêu cầu",
                subtitle: "Tài xế",
                driverApp: true,
            },
        },
        {
            path: "/driver/maintenance",
            redirect: "/driver",
        },
        {
            path: "/driver/maintenance/:id",
            redirect: "/driver",
        },
        {
            path: "/driver/account",
            name: "driverAccount",
            component: () => import("../views/driver/DriverAccountView.vue"),
            meta: {
                title: "Tài khoản",
                subtitle: "Tài xế",
                driverApp: true,
            },
        },
        {
            path: "/driver/trips/:id",
            name: "driverTripDetail",
            component: () => import("../views/driver/DriverTripDetailView.vue"),
            meta: {
                title: "Chi tiết chuyến",
                subtitle: "Tài xế",
                driverApp: true,
            },
        },
        {
            path: "/driver/policy-trips",
            name: "driverPolicyTrips",
            component: () => import("../views/driver/DriverPolicyTripsView.vue"),
            meta: {
                title: "Chuyến đưa đón",
                subtitle: "Học sinh chính sách",
                driverApp: true,
            },
        },
        {
            path: "/driver/policy-trips/:id",
            name: "driverPolicyAttendance",
            component: () => import("../views/driver/DriverPolicyAttendanceView.vue"),
            meta: {
                title: "Điểm danh",
                subtitle: "Học sinh chính sách",
                driverApp: true,
            },
        },
        {
            path: "/driver/tp-days",
            name: "driverTpDays",
            component: () => import("../views/driver/DriverTpDaysView.vue"),
            meta: {
                title: "Chuyến đưa đón",
                subtitle: "Transport Program",
                driverApp: true,
            },
        },
        {
            path: "/driver/tp-days/:dayId",
            name: "driverTpAttendance",
            component: () => import("../views/driver/DriverTpAttendanceView.vue"),
            meta: {
                title: "Điểm danh",
                subtitle: "Transport Program",
                driverApp: true,
            },
        },
    ],
});

router.beforeEach(async (to) => {
    if (to.meta.public) {
        // Redirect user đã đăng nhập ra khỏi trang login (trừ OAuth callback có ?token)
        if ((to.name === "login" || to.name === "home") && !to.query.token) {
            const auth = useAuthStore();
            if (auth.user) {
                return resolvePostLoginTarget(auth, to.query.redirect);
            }
        }
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
    const portalUser =
        !auth.canAccessDispatchWebApp() && !auth.canAccessDriverWebApp();
    const driverOnly =
        !auth.canAccessDispatchWebApp() && auth.canAccessDriverWebApp();

    if (portalUser) {
        if (!routeHasPortalMeta(to)) {
            return { path: "/portal", replace: true };
        }
        return true;
    }

    if (routeHasPortalMeta(to)) {
        if (driverOnly) {
            return { path: "/driver", replace: true };
        }
        if (auth.isDeptHeadOnly()) {
            return { path: "/dept", replace: true };
        }
        return { name: "dashboard" };
    }

    if (routeHasDeptHeadMeta(to) && !auth.isDeptHeadOnly()) {
        return { name: "dashboard" };
    }

    /** Trưởng đơn vị (chỉ dept_head): không dùng ứng dụng `/mng`. */
    if (auth.isDeptHeadOnly() && !portalUser && !driverOnly && pathIsUnderStaffBase(to.path)) {
        const m = to.path.match(/^\/mng\/requests\/(\d+)\/?(?:\?.*)?$/);
        if (m) {
            return {
                path: `/dept/requests/${m[1]}`,
                query: to.query,
                hash: to.hash,
                replace: true,
            };
        }
        return { path: "/dept", replace: true };
    }

    if (driverOnly) {
        const p = to.path;
        if (p === "/profile" || p.startsWith("/profile/")) {
            return { path: "/driver/account", replace: true };
        }
        const allowed = p === "/driver" || p.startsWith("/driver/");
        if (!allowed) {
            return { path: "/driver", replace: true };
        }
    }
    if (
        auth.isDeptHeadOnly() &&
        !portalUser &&
        !driverOnly &&
        to.path !== "/login" &&
        !to.path.startsWith("/auth") &&
        !pathIsUnderStaffBase(to.path) &&
        shouldRewriteLegacyStaffPath(to.path)
    ) {
        if (to.path === "/profile" || to.path.startsWith("/profile/")) {
            return true;
        }
        const idMatch = to.path.match(/^\/requests\/(\d+)\/?/);
        if (idMatch) {
            return {
                path: `/dept/requests/${idMatch[1]}`,
                query: to.query,
                hash: to.hash,
                replace: true,
            };
        }
        if (to.path === "/requests" || to.path.startsWith("/requests?")) {
            return { path: "/dept/all", query: to.query, hash: to.hash, replace: true };
        }
        return { path: "/dept", query: to.query, hash: to.hash, replace: true };
    }

    if (
        auth.canAccessDispatchWebApp() &&
        !driverOnly &&
        !auth.isDeptHeadOnly() &&
        to.path !== "/login" &&
        !to.path.startsWith("/auth") &&
        !pathIsUnderStaffBase(to.path) &&
        shouldRewriteLegacyStaffPath(to.path)
    ) {
        return {
            path: buildStaffPrefixedPath(to.path),
            query: to.query,
            hash: to.hash,
            replace: true,
        };
    }
    if (to.meta.featureKey) {
        const k = to.meta.featureKey;
        if (!auth.isNavFeatureVisible(k)) {
            if (driverOnly) {
                return { path: "/driver", replace: true };
            }
            return { name: "dashboard" };
        }
    }
    const need = to.meta.permission;
    if (need) {
        const list = Array.isArray(need) ? need : [need];
        if (!auth.hasAnyPermission(list)) {
            if (driverOnly) {
                return { path: "/driver", replace: true };
            }
            return { name: "dashboard" };
        }
    }
    return true;
});

router.afterEach((to) => {
    updateDocumentTitle(to);
    scrollAppMainToTop();
});

router.onError((error, to) => {
    const message = error?.message ?? String(error ?? "");
    if (!isStaleChunkMessage(message, error)) return;
    void reloadOnceForStaleAssets(
        to?.fullPath
            ? `${window.location.origin}${to.fullPath}`
            : window.location.href,
    );
});

export default router;
