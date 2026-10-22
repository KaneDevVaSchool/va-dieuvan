<template>
  <!-- Overlay che form khi đang xử lý OAuth token (tránh flash nếu fetchMe chậm > 800ms) -->
  <div v-if="bootstrapping" class="fixed inset-0 z-[9998] bg-[#9a0036]" aria-hidden="true" />
  <div class="login-page">
    <main class="login-page__wrapper" role="main">
      <div class="login-page__logo">
        <img
          :src="LOGO_URL"
          alt="Vietnam America Schools"
          width="400"
          height="168"
          decoding="async"
        />
        <h2 class="login-page__system-name">Hệ thống Điều vận</h2>
      </div>

      <div class="login-box" role="region" aria-label="Đăng nhập">
        <h1 class="login-title">Đăng nhập</h1>
        <p class="login-subtitle">
          Đăng nhập thông qua tài khoản mail do nhà trường cung cấp
        </p>

        <div v-if="error" class="login-alert" role="alert">
          <span>{{ error }}</span>
          <button type="button" class="login-alert__dismiss" aria-label="Đóng" @click="error = ''">&times;</button>
        </div>

        <div class="social-login">
          <a
            class="btn-google-icon"
            :class="{ loading: googleLoading || bootstrapping }"
            :href="googleAuthHref"
            aria-label="Đăng nhập bằng Google"
            :aria-busy="googleLoading || bootstrapping ? 'true' : undefined"
            @click="onGoogleClick"
          >
            <img :src="GOOGLE_LOGO_URL" alt="" aria-hidden="true" width="40" height="40" decoding="async" />
          </a>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore, readCachedDefaultHome } from '../../store'
import { TOKEN_KEY } from '../../core/config/authKeys'
import { isUnauthorizedApiError, isTransientSessionError } from '../../util/apiError'
import { formatApiError } from '../../api/http'
import { exchangeOauthCode } from '../../api/auth'
import {
  sanitizeLoginRedirect,
  normalizeLoginRouteQuery,
  loginRouteNeedsSanitizeReplace,
  resolvePostLoginTarget,
} from '../../util/loginRedirect'

/** `public/images/logo/...` */
const LOGO_URL = '/images/logo/logo-2.png'
const GOOGLE_LOGO_URL = '/images/logo/google.png'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const error = ref('')
const bootstrapping = ref(false)
const googleLoading = ref(false)

const googleAuthHref = computed(() => {
  const r = sanitizeLoginRedirect(route.query.redirect ?? '/')
  return `/auth/google?redirect=${encodeURIComponent(r)}`
})

function onGoogleClick() {
  error.value = ''
  googleLoading.value = true
}

/** Quay lại từ Google bằng nút Back (bfcache) → bỏ spinner. */
function onPageShow(e) {
  if (e.persisted) googleLoading.value = false
}

onBeforeUnmount(() => window.removeEventListener('pageshow', onPageShow))

onMounted(async () => {
  window.addEventListener('pageshow', onPageShow)
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

  // Luồng OAuth mới: đổi mã một-lần (code) lấy token bearer (token không còn nằm trên URL).
  if (q.code) {
    bootstrapping.value = true
    error.value = ''
    try {
      const res = await exchangeOauthCode(String(q.code))
      auth.setToken(res.token)
      if (!localStorage.getItem(TOKEN_KEY)) {
        throw new Error('storage_blocked')
      }
      await auth.fetchMe()
      const target = resolvePostLoginTarget(auth, q.redirect)
      await router.replace(target)
    } catch (e) {
      auth.setToken(null)
      const fallback =
        e?.message === 'storage_blocked'
          ? 'Thiết bị không cho lưu phiên đăng nhập. Tắt chế độ riêng tư hoặc mở app bằng trình duyệt thường, rồi thử lại.'
          : 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.'
      error.value = formatApiError(e, fallback)
      const safe = sanitizeLoginRedirect(q.redirect ?? '/')
      await router.replace({
        path: '/login',
        query: safe !== '/' ? { redirect: safe } : {},
      })
    } finally {
      bootstrapping.value = false
    }
    return
  }

  if (!q.token) {
    if (localStorage.getItem(TOKEN_KEY)) {
      bootstrapping.value = true
      try {
        if (!auth.user) {
          await auth.fetchMe()
        }
        if (auth.user) {
          const target = resolvePostLoginTarget(auth, q.redirect)
          await router.replace(target)
        }
      } catch (e) {
        if (isUnauthorizedApiError(e)) {
          auth.setToken(null)
        } else if (isTransientSessionError(e)) {
          const cached = readCachedDefaultHome()
          if (cached) {
            await router.replace(cached)
          }
        }
      } finally {
        bootstrapping.value = false
      }
    }
    return
  }

  bootstrapping.value = true
  error.value = ''
  try {
    auth.setToken(String(q.token))
    if (!localStorage.getItem(TOKEN_KEY)) {
      throw new Error('storage_blocked')
    }
    await auth.fetchMe()
    const target = resolvePostLoginTarget(auth, q.redirect)
    await router.replace(target)
  } catch (e) {
    auth.setToken(null)
    const fallback =
      e?.message === 'storage_blocked'
        ? 'Thiết bị không cho lưu phiên đăng nhập. Tắt chế độ riêng tư hoặc mở app bằng trình duyệt thường, rồi thử lại.'
        : 'Phiên đăng nhập không hợp lệ.'
    error.value = formatApiError(e, fallback)
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

<style>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap');
</style>

<style scoped>
/* Giao diện đồng bộ với trang đăng nhập SIS */
.login-page {
  font-family: 'Be Vietnam Pro', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  background-color: #9a0036;
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  justify-content: safe center;
  position: relative;
  overflow-x: hidden;
  isolation: isolate;
  box-sizing: border-box;
  padding-top: max(clamp(1.5rem, 5vw, 3.5rem), env(safe-area-inset-top, 0px));
  padding-bottom: max(clamp(1rem, 4vw, 2.5rem), env(safe-area-inset-bottom, 0px));
  padding-left: clamp(0.75rem, 4vw, 1.5rem);
  padding-right: clamp(0.75rem, 4vw, 1.5rem);
}

.login-page::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url('/images/background/background_login_1.png');
  background-size: 195% 203%;
  background-position: 50% 39%;
  background-repeat: no-repeat;
  opacity: 0.05;
  pointer-events: none;
  z-index: 0;
}

.login-page__wrapper {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 720px;
  gap: clamp(1.25rem, 3.5vw, 2.2rem);
  margin-block: auto;
}

.login-page__logo {
  text-align: center;
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: clamp(0.85rem, 2.2vw, 1.4rem);
}

.login-page__logo img {
  width: clamp(220px, 56vw, 345px);
  height: auto;
  max-height: min(148px, 24vh);
  object-fit: contain;
  filter: brightness(0) invert(1);
}

.login-page__system-name {
  font-family: 'Playfair Display', 'Merriweather', 'Noto Serif', 'Times New Roman', serif;
  font-size: clamp(0.92rem, 2.6vw, 1.5rem);
  font-weight: 700;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: clamp(0.025em, 0.22vw, 0.055em);
  line-height: 1.35;
  margin: 0;
  text-align: center;
  white-space: nowrap;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
  user-select: none;
}

.login-box {
  background: #fff;
  border-radius: clamp(12px, 3vw, 20px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 4px 14px rgba(0, 0, 0, 0.1);
  margin-top: 10px;
  padding: clamp(1.4rem, 5vw, 2.4rem) clamp(1.2rem, 6vw, 2.6rem);
  width: min(450px, 100%);
  max-width: 100%;
  box-sizing: border-box;
  text-align: center;
}

.login-title {
  font-size: clamp(1.6rem, 4.5vw, 2.1rem);
  font-weight: 700;
  color: #111827;
  margin: 0 0 clamp(0.3rem, 1.2vw, 0.5rem);
}

.login-subtitle {
  font-size: clamp(0.85rem, 2.8vw, 1rem);
  color: #666;
  line-height: 1.4;
  margin: 0 0 clamp(1rem, 3.5vw, 1.5rem);
}

.login-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  border-radius: 9px;
  padding: 0.75rem 0.95rem;
  margin-bottom: 1rem;
  font-size: clamp(0.76rem, 2.1vw, 0.85rem);
  text-align: left;
  line-height: 1.5;
  background: #fef2f2;
  border: 1px solid #fca5a5;
  color: #991b1b;
}

.login-alert__dismiss {
  margin-left: auto;
  background: none;
  border: none;
  cursor: pointer;
  opacity: 0.45;
  font-size: 1.1rem;
  line-height: 1;
}

.social-login {
  display: flex;
  justify-content: center;
}

.btn-google-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: clamp(50px, 13vw, 62px);
  height: clamp(50px, 13vw, 62px);
  border-radius: 50%;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.09);
  cursor: pointer;
  text-decoration: none;
  transition: box-shadow 0.2s, transform 0.2s;
  position: relative;
}

.btn-google-icon img {
  width: clamp(26px, 7vw, 36px);
  height: clamp(26px, 7vw, 36px);
}

.btn-google-icon:hover:not(.loading) {
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
  transform: translateY(-3px);
}

.btn-google-icon.loading {
  pointer-events: none;
}

.btn-google-icon.loading img {
  opacity: 0;
}

.btn-google-icon.loading::before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  border: 2.5px solid #f3f4f6;
  border-top-color: #8b1a3a;
  border-radius: 50%;
  animation: login-spin 0.65s linear infinite;
}

@keyframes login-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .btn-google-icon {
    transition: none;
  }
}

@media (max-width: 480px) {
  .login-page {
    padding: max(clamp(1rem, 5vw, 1.5rem), env(safe-area-inset-top, 0px)) 1rem
      max(clamp(1rem, 5vw, 1.5rem), env(safe-area-inset-bottom, 0px));
  }

  .login-box {
    padding: 1.5rem 1.2rem;
    border-radius: 14px;
  }

  .btn-google-icon {
    width: 58px;
    height: 58px;
  }

  .login-page__wrapper {
    max-width: 100%;
    padding: 0 1rem;
  }
}

@media (min-width: 481px) and (max-width: 1024px) {
  .login-page__wrapper {
    max-width: 680px;
  }

  .login-box {
    padding: 2rem 2.2rem;
  }
}

@media (min-width: 1025px) {
  .login-page__wrapper {
    max-width: 750px;
  }

  .login-box {
    padding: 2.6rem 3rem;
  }

  .btn-google-icon {
    width: 68px;
    height: 68px;
  }

  .btn-google-icon img {
    width: 40px;
    height: 40px;
  }
}
</style>
