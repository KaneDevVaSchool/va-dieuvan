<template>
  <div class="login-page">
    <div class="login-container">
      <div class="logo-container">
        <img
          :src="LOGO_PWA_URL"
          alt="VA Dispatch"
          width="512"
          height="512"
          class="h-auto w-full max-w-[min(100%,14rem)] aspect-square object-contain"
          decoding="async"
        />
      </div>

      <div class="login-box">
        <h1 class="login-title">Đăng nhập</h1>
        <p class="login-subtitle">
          Đăng nhập thông qua tài khoản mail do nhà trường cung cấp
        </p>

        <div v-if="error" class="login-error">{{ error }}</div>

        <a
          class="google-login-btn"
          :class="{ 'is-busy': bootstrapping }"
          :href="googleAuthHref"
          aria-label="Đăng nhập bằng Google"
          :aria-busy="bootstrapping ? 'true' : undefined"
          @click="onGoogleClick"
        >
          <img
            :src="GOOGLE_LOGO_URL"
            alt=""
            width="48"
            height="48"
            class="google-login-img"
            decoding="async"
          />
        </a>
      </div>
    </div>

    <div class="wave-footer" aria-hidden="true" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'
import {
  sanitizeLoginRedirect,
  normalizeLoginRouteQuery,
  loginRouteNeedsSanitizeReplace,
} from '../../util/loginRedirect'
import { isDispatchStaffHomePath } from '../../config/dispatchWebBase'

/** `public/images/logo/...` */
const LOGO_PWA_URL = '/images/logo/logo-2.png'
const GOOGLE_LOGO_URL = '/images/logo/google.png'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const error = ref('')
const bootstrapping = ref(false)

const googleAuthHref = computed(() => {
  const r = sanitizeLoginRedirect(route.query.redirect ?? '/')
  return `/auth/google?redirect=${encodeURIComponent(r)}`
})

function onGoogleClick() {
  error.value = ''
}

onMounted(async () => {
  if (loginRouteNeedsSanitizeReplace(route.query)) {
    await router.replace({
      path: '/login',
      query: normalizeLoginRouteQuery(route.query),
    })
  }

  const q = route.query
  if (q.error) {
    error.value = String(q.error)
    const clean = {}
    const safeErrRedirect = sanitizeLoginRedirect(q.redirect ?? '/')
    if (safeErrRedirect !== '/') {
      clean.redirect = safeErrRedirect
    }
    await router.replace({ path: '/login', query: clean })
  }

  if (!q.token) {
    return
  }

  bootstrapping.value = true
  error.value = ''
  try {
    auth.setToken(String(q.token))
    await auth.fetchMe()
    if (!auth.canAccessDispatchWebApp() && !auth.canAccessDriverWebApp()) {
      await router.replace('/portal')
      return
    }
    let target = sanitizeLoginRedirect(q.redirect != null && q.redirect !== '' ? q.redirect : '/')
    if (!auth.canAccessDispatchWebApp() && auth.canAccessDriverWebApp()) {
      if (target === '/profile' || target.startsWith('/profile/')) {
        target = '/driver/account'
      }
      const ok = target.startsWith('/driver')
      if (!ok) {
        target = '/driver'
      }
    }
    if (auth.canAccessDispatchWebApp() && auth.isDeptHeadOnly()) {
      if (target === '/' || target === '/mng' || isDispatchStaffHomePath(target)) {
        target = '/dept'
      } else if (target.startsWith('/mng/')) {
        const rm = target.match(/^\/mng\/requests\/(\d+)/)
        target = rm ? `/dept/requests/${rm[1]}` : '/dept'
      }
    }
    await router.replace(target)
  } catch (e) {
    auth.setToken(null)
    error.value = formatApiError(e, 'Phiên đăng nhập không hợp lệ.')
    const safe = sanitizeLoginRedirect(q.redirect ?? '/')
    await router.replace({
      path: '/login',
      query: safe !== '/' ? { redirect: safe } : {},
    })
  } finally {
    bootstrapping.value = false
  }
})
</script>

<style scoped>
.login-page {
  margin: 0;
  min-height: 100dvh;
  min-height: 100svh;
  width: 100%;
  background-color: #78001e;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow-x: hidden;
  isolation: isolate;
  padding: max(16px, env(safe-area-inset-top)) max(16px, env(safe-area-inset-right))
    max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
  box-sizing: border-box;
  font-family:
    system-ui,
    -apple-system,
    'Segoe UI',
    Roboto,
    'Helvetica Neue',
    Arial,
    sans-serif;
}

/* Hình nền rồng: phủ trọn viewport, tăng độ sáng/tương phản để nổi trên nền đỏ */
.login-page::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  background-color: transparent;
  background-image: url('/images/background/background-logo.png');
  background-repeat: no-repeat;
  background-position: center center;
  background-size: cover;
  /* Nâng silhouette tối (xám/đen) để thấy rõ trên nền brand */
  filter: brightness(1.45) contrast(1.15) saturate(1.05);
  opacity: 0.92;
  pointer-events: none;
}

.login-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  width: 100%;
  max-width: min(440px, 100%);
  position: relative;
  z-index: 2;
  padding: 0;
}

.logo-container {
  margin-bottom: clamp(20px, 4vw, 40px);
  text-align: center;
  width: 100%;
}

.logo-container img {
  height: auto;
  max-width: min(400px, 100%);
  width: 65%;
  display: block;
  margin-inline: auto;
}

.login-box {
  background: #fff;
  border-radius: clamp(12px, 2vw, 15px);
  padding: clamp(24px, 5vw, 40px) clamp(20px, 4vw, 30px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  text-align: center;
  position: relative;
  box-sizing: border-box;
}

.login-title {
  font-size: clamp(1.375rem, 4.2vw + 0.6rem, 1.75rem);
  color: #333;
  margin: 0 0 8px;
  font-weight: 700;
  line-height: 1.2;
}

.login-subtitle {
  color: #666;
  font-size: clamp(0.8125rem, 1.5vw + 0.35rem, 0.875rem);
  margin: 0 0 clamp(20px, 4vw, 30px);
  line-height: 1.5;
  max-width: 32em;
  margin-inline: auto;
}

.login-error {
  margin-bottom: 16px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 14px;
  text-align: center;
}

.google-login-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 48px;
  min-height: 48px;
  padding: 10px;
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  transition:
    border-color 0.3s ease,
    box-shadow 0.3s ease;
  text-decoration: none;
  box-sizing: border-box;
  vertical-align: middle;
}

.google-login-img {
  display: block;
  width: clamp(40px, 12vw, 52px);
  height: auto;
  aspect-ratio: 1;
  object-fit: contain;
}

@media (prefers-reduced-motion: reduce) {
  .google-login-btn {
    transition: none;
  }
}

.google-login-btn.is-busy {
  pointer-events: none;
  opacity: 0.65;
}

.wave-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-repeat: no-repeat;
  background-position: center bottom;
  background-size: contain;
  z-index: 1;
  pointer-events: none;
}

/* Màn hình thấp / ngang (điện thoại xoay, laptop nhỏ): tránh cắt nội dung */
@media (max-height: 520px) {
  .login-page {
    justify-content: flex-start;
    padding-top: max(12px, env(safe-area-inset-top));
  }

  .login-container {
    justify-content: flex-start;
    padding-block: 8px 16px;
  }

  .logo-container {
    margin-bottom: 12px;
  }

  .logo-container img {
    max-width: min(240px, 55vw);
  }

  .login-box {
    padding: 16px 18px;
  }

  .login-subtitle {
    margin-bottom: 14px;
  }
}

/* Màn hình rộng: căn hình nền hơi lên trên để bố cục cân */
@media (min-width: 1600px) {
  .login-page::before {
    background-position: center 38%;
  }
}

@media (max-width: 380px) {
  .login-page {
    padding-inline: max(12px, env(safe-area-inset-left));
  }
}
</style>
