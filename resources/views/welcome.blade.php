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
    <link rel="icon" type="image/png" href="/icons/pwa-192.png" />
    @php
        $pwaManifest = file_exists(public_path('build/manifest.webmanifest'))
            ? asset('build/manifest.webmanifest')
            : asset('manifest.webmanifest');
    @endphp
    <link rel="manifest" href="{{ $pwaManifest }}" />
    <title>VA Dispatch — Điều vận</title>
   
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>
  <body class="min-h-full antialiased" style="background:#8b1a1a">
    <div id="app" class="min-h-full bg-[#020B0B]"></div>
  </body>
</html>
