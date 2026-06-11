<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    {{-- Nền tối dưới status bar / tai thỏ (PWA iOS & Chrome); tránh vệt trắng phía trên --}}
    <meta name="theme-color" content="#020B0B" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#020B0B" media="(prefers-color-scheme: dark)" />
    <meta name="application-name" content="VA Dispatch" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="description" content="Hệ thống quản lý vận tải & điều vận nội bộ" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png" />
    <link rel="apple-touch-icon" href="/icons/pwa-192.png" />
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/pwa-192.png" />
    @php
        $pwaManifest = '/manifest.webmanifest';
    @endphp
    <link rel="manifest" href="{{ $pwaManifest }}" />
    <title>VA Dispatch — Điều vận</title>
    {{-- Phục hồi khi shell HTML cũ trỏ tới chunk Vite đã xóa (trước khi app.js chạy). --}}
    <script>
      (function () {
        var key = 'va-dieuvan:bootstrap-reload-once';
        window.addEventListener('error', function (e) {
          var t = e.target;
          if (!t || t.tagName !== 'SCRIPT') return;
          var s = t.src || '';
          if (s.indexOf('/build/assets/') === -1) return;
          if (sessionStorage.getItem(key)) return;
          sessionStorage.setItem(key, '1');
          var reload = function () { location.reload(); };
          var chain = Promise.resolve();
          if ('serviceWorker' in navigator) {
            chain = navigator.serviceWorker.getRegistrations().then(function (regs) {
              return Promise.all(regs.map(function (r) { return r.unregister(); }));
            });
          }
          if ('caches' in window) {
            chain = chain.then(function () {
              return caches.keys().then(function (ks) {
                return Promise.all(ks.map(function (k) { return caches.delete(k); }));
              });
            });
          }
          chain.then(reload, reload);
        }, true);
      })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>
  <body class="h-dvh max-h-dvh overflow-hidden antialiased bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div id="app" class="h-full min-h-0 overflow-hidden bg-slate-50 dark:bg-slate-950"></div>
  </body>
</html>
