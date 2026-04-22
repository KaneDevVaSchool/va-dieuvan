<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#78001e" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#78001e" media="(prefers-color-scheme: dark)" />
    <meta name="application-name" content="VA Dispatch" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="VA Dispatch" />
    <meta name="description" content="Hệ thống quản lý vận tải & điều vận nội bộ" />
    <link rel="icon" type="image/png" href="/images/logo/logo_pwa_v1.png" />
    <link rel="apple-touch-icon" href="/images/logo/logo_pwa_v1.png" />
    <link rel="manifest" href="/manifest.webmanifest" />
    <title>VA Dispatch — Điều vận</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>
  <body class="min-h-full antialiased">
    <div id="pwa-splash" class="pwa-splash" aria-hidden="true">
      <div class="pwa-splash__mesh" aria-hidden="true"></div>
      <div class="pwa-splash__glow" aria-hidden="true"></div>
      <div class="pwa-splash__content">
        <div class="pwa-splash__logo-wrap">
          <div class="pwa-splash__ring" aria-hidden="true"></div>
          <img
            class="pwa-splash__logo"
            src="/images/logo/logo_pwa_v1.png"
            width="512"
            height="512"
            alt=""
            decoding="async"
            fetchpriority="high"
          />
        </div>
        <p class="pwa-splash__kicker">Transportation Management</p>
        <p class="pwa-splash__sub">Dispatch Software</p>
        <div class="pwa-splash__track" aria-hidden="true">
          <div class="pwa-splash__bar"></div>
        </div>
        <div class="pwa-splash__dots" aria-hidden="true">
          <span></span><span></span><span></span>
        </div>
        <p class="pwa-splash__hint">Đang khởi tạo hệ thống…</p>
      </div>
    </div>
    <div id="app"></div>
  </body>
</html>
