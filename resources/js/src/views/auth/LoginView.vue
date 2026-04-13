<template>
  <div class="login-page">
    <div class="login-container">
      <div class="logo-container">
        <img
          :src="LOGO_2_URL"
          alt="Vietnam America Schools"
          width="400"
          height="120"
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
          :aria-busy="bootstrapping ? 'true' : undefined"
          @click="onGoogleClick"
        >
          <span class="google-icon" aria-hidden="true" />
          <span>Tiếp tục với Google</span>
        </a>
      </div>
    </div>

    <div class="wave-footer" aria-hidden="true" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../store'
import { formatApiError } from '../../api/http'

/** Public asset — `public/images/logo/logo-2.png` */
const LOGO_2_URL = '/images/logo/logo-2.png'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const error = ref('')
const bootstrapping = ref(false)

const googleAuthHref = computed(() => {
  const r = route.query.redirect ?? '/'
  return `/auth/google?redirect=${encodeURIComponent(String(r))}`
})

function onGoogleClick() {
  error.value = ''
}

onMounted(async () => {
  const q = route.query
  if (q.error) {
    error.value = String(q.error)
    const clean = {}
    if (q.redirect != null && q.redirect !== '') {
      clean.redirect = q.redirect
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
    const target = q.redirect != null && q.redirect !== '' ? String(q.redirect) : '/'
    await router.replace(target)
  } catch (e) {
    auth.setToken(null)
    error.value = formatApiError(e, 'Phiên đăng nhập không hợp lệ.')
    await router.replace({ path: '/login', query: { redirect: q.redirect } })
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
  background-color: #9a0036;
  background-image: url('/images/background/background-logo.png');
  background-repeat: no-repeat;
  background-position: center;
  background-size: cover;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow-x: hidden;
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
  width: 100%;
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
  width: 100%;
  min-height: 48px;
  padding: 12px 16px;
  border: 2px solid #ddd;
  border-radius: 8px;
  background: #fff;
  color: #333;
  font-size: clamp(0.875rem, 1.2vw + 0.5rem, 1rem);
  cursor: pointer;
  transition:
    border-color 0.3s ease,
    box-shadow 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  text-decoration: none;
  box-sizing: border-box;
}

@media (prefers-reduced-motion: reduce) {
  .google-login-btn {
    transition: none;
  }
}

.google-login-btn:hover:not(.is-busy) {
  border-color: #9a0036;
  box-shadow: 0 2px 8px rgba(154, 0, 54, 0.2);
}

.google-login-btn.is-busy {
  pointer-events: none;
  opacity: 0.65;
}

.google-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDhjMC0uNjM5LS4wNTctMS4yNTItLjE2NC0xLjg0MUg5djMuNDgxaDQuODQ0Yy0uMjA5IDEuMTI1LS44NDQgMi4wNzgtMS43OTYgMi43MTR2Mi4yNTloMi45MDhjMS43MzQtMS41OTggMi43MzgtMy45NTMgMi43MzgtNi43MTN6IiBmaWxsPSIjNDA5MkZGIi8+PHBhdGggZD0iTTkgMThjMi40MyAwIDQuNDY3LS44MDYgNS45NTYtMi4xOGwtMi45MDgtMi4yNTljLS44MDYuNTQtMS44MzcuODYtMy4wNDguODYtMi4zNDQgMC00LjMyOC0xLjU4NC00LjYzNi0zLjcxMUguOTU3djIuMzMyQzIuNDM4IDE0LjcyIDUuNDgyIDE4IDkgMTh6IiBmaWxsPSIjMzRBODUzIi8+PHBhdGggZD0iTTQuMzY0IDEwLjcxQzQuMTc2IDEwLjE3IDQuMDY4IDkuNTkzIDQuMDY4IDljMC0uNTkzLjEwOC0xLjE3LjI5Ni0xLjcxVjQuOTU3SC45NTdDLjM0NyA2LjE3MyAwIDcuNTQ4IDAgOXMuMzQ3IDIuODI3Ljk1NyA0LjA0M2wzLjQwNy0yLjMzMnoiIGZpbGw9IiNGQkJDMDUiLz48cGF0aCBkPSJNOSAzLjU4Yy0xLjMyMSAwLTIuNTA4LjQ1NC0zLjQ0IDEuMzQ1bDIuNTgyIDIuNTgyQzguODg3IDYuODkxIDguOTI5IDYuNTUgOS4wOSA2LjU1aDMuNDRWMy4xOEgxNi41MTRDMTQuNzI1IDEuMzQgMTIuMDgyIDAgOSAweiIgZmlsbD0iI0VBNDMzNSIvPjwvZz48L3N2Zz4=')
    no-repeat center;
  background-size: contain;
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

/* Màn hình rộng: nền không bị kéo méo quá mức, vẫn phủ full */
@media (min-width: 1600px) {
  .login-page {
    background-size: cover;
    background-position: center 40%;
  }
}

@media (max-width: 380px) {
  .login-page {
    padding-inline: max(12px, env(safe-area-inset-left));
  }
}
</style>
