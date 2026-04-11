<template>
  <div class="space-y-4">
    <Card title="Bản đồ &amp; ETA (gợi ý tuyến)">
      <p class="mb-3 text-sm text-slate-600">
        Nhập điểm đi / đến để mở chỉ đường ngoài Google Maps. Bản đồ nhúng OpenStreetMap (TP.HCM). Tọa độ chính xác / ETA thật
        sẽ nối khi có địa chỉ chuẩn hoá hoặc tích hợp routing API.
      </p>
      <div class="grid gap-3 md:grid-cols-2">
        <Input v-model="origin" label="Điểm đi" placeholder="VD: VA Tân Bình" />
        <Input v-model="destination" label="Điểm đến" placeholder="VD: Vũng Tàu" />
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        <a
          :href="googleDirUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
        >
          Mở Google Maps chỉ đường
        </a>
        <a
          class="inline-flex rounded-md border px-4 py-2 text-sm hover:bg-slate-50"
          :href="googleDirUrl"
          target="_blank"
          rel="noopener noreferrer"
        >
          ETA (ước lượng trên Maps)
        </a>
      </div>
      <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
        <iframe
          title="OpenStreetMap"
          class="h-[320px] w-full md:h-[420px]"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          :src="osmEmbedSrc"
        />
      </div>
    </Card>

    <Card title="PWA — Cài lên màn hình chính">
      <p class="text-sm text-slate-600">
        Đã có <code class="rounded bg-slate-100 px-1">manifest.webmanifest</code> và service worker tối thiểu. Trên Chrome/Android:
        menu trình duyệt → “Cài đặt ứng dụng” / “Add to Home screen”. iOS: Safari → Chia sẻ → Thêm vào Màn hình chính.
      </p>
      <p class="mt-2 text-xs text-slate-500">
        Giai đoạn sau: precache asset với vite-plugin-pwa, push notification khi có inbox thật.
      </p>
    </Card>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Input from '../../components/ui/Input.vue'

const origin = ref('Thành phố Hồ Chí Minh')
const destination = ref('Vũng Tàu')

const googleDirUrl = computed(() => {
  const o = encodeURIComponent(origin.value || 'Ho Chi Minh City')
  const d = encodeURIComponent(destination.value || 'Vung Tau')
  return `https://www.google.com/maps/dir/?api=1&origin=${o}&destination=${d}&travelmode=driving`
})

/** Khung TP.HCM — có thể thay bằng bbox từ địa chỉ geocode */
const osmEmbedSrc =
  'https://www.openstreetmap.org/export/embed.html?bbox=106.52%2C10.68%2C106.95%2C11.05&layer=mapnik'
</script>
