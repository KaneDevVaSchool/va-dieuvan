"""Generate favicon assets from public/images/logo/vas-logo.png."""
from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "public" / "images" / "logo" / "vas-logo.png"
PUBLIC = ROOT / "public"


def make_transparent_black(im: Image.Image, threshold: int = 28) -> Image.Image:
    im = im.convert("RGBA")
    px = im.load()
    w, h = im.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if r <= threshold and g <= threshold and b <= threshold:
                px[x, y] = (0, 0, 0, 0)
    return im


def crop_symbol(im: Image.Image) -> Image.Image:
    """Use upper ~52% — creature mark only (text unreadable at favicon size)."""
    w, h = im.size
    top = im.crop((0, 0, w, int(h * 0.52)))
    bbox = top.getbbox()
    if not bbox:
        return top
    return top.crop(bbox)


def square_pad(im: Image.Image, size: int, padding_ratio: float = 0.08) -> Image.Image:
    w, h = im.size
    side = max(w, h)
    pad = int(side * padding_ratio)
    canvas_side = side + pad * 2
    canvas = Image.new("RGBA", (canvas_side, canvas_side), (0, 0, 0, 0))
    ox = (canvas_side - w) // 2
    oy = (canvas_side - h) // 2
    canvas.paste(im, (ox, oy), im)
    return canvas.resize((size, size), Image.Resampling.LANCZOS)


def save_ico(square: Image.Image, path: Path) -> None:
    sizes = [16, 32, 48]
    images = [square.resize((s, s), Image.Resampling.LANCZOS) for s in sizes]
    images[0].save(
        path,
        format="ICO",
        sizes=[(s, s) for s in sizes],
        append_images=images[1:],
    )


def main() -> None:
    if not SRC.is_file():
        raise SystemExit(f"Missing source: {SRC}")

    raw = Image.open(SRC)
    symbol = crop_symbol(make_transparent_black(raw))
    square = square_pad(symbol, 512)

    save_ico(square, PUBLIC / "favicon.ico")
    square_pad(symbol, 32).save(PUBLIC / "favicon-32x32.png", format="PNG")
    square_pad(symbol, 16).save(PUBLIC / "favicon-16x16.png", format="PNG")

    icons_dir = PUBLIC / "icons"
    icons_dir.mkdir(parents=True, exist_ok=True)
    square_pad(symbol, 192).save(icons_dir / "pwa-192.png", format="PNG")
    square_pad(symbol, 512).save(icons_dir / "pwa-512.png", format="PNG")
    # Maskable: extra padding for safe zone
    maskable = square_pad(symbol, 512, padding_ratio=0.22)
    maskable.save(icons_dir / "pwa-512-maskable.png", format="PNG")

    print("Wrote favicon.ico, favicon-16x16.png, favicon-32x32.png, public/icons/pwa-*.png")


if __name__ == "__main__":
    main()
