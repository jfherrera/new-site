"""Build a 3.9 x 6.7 in (1170 x 2010 px @ 300 dpi) KISS Reviews decal.
Usage: DECAL_PSD=/path/design.psd python3 build_decal.py <review_url> <out_prefix>
"""
import os, sys, math
from psd_tools import PSDImage
from PIL import Image, ImageDraw, ImageFont
import qrcode

PSD = os.environ.get('DECAL_PSD', 'master.psd')  # path to the design PSD (not stored in the repo)
FONT = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'poppins-latin-{}-normal.woff')
W, H, DPI = 1170, 2010, 300
CROP_X0, CROP_X1 = 51, 749          # source px (800 wide) kept -> 698 px, sticker ratio 0.58
SRC_H = 1200
SX = W / (CROP_X1 - CROP_X0)        # 1.676
SY = H / SRC_H                      # 1.675
TEXT_LAYERS_HIDE = {'Group 1', 'Layer 3', 'Rounded Rectangle 2', 'capture-qr-code-mobile-phone', 'capture-qr-code-mobile-phone copy'}
ILLUSTRATION = 'capture-qr-code-mobile-phone copy'
ILLU_X, ILLU_SCALE = 62, 0.9        # target px: left edge and scale of the phone/hand art
CARD_RIGHT_MARGIN = 50              # target px between QR card and sticker edge
CARD_SIZE = 553                     # target px, QR card square

def tx(x): return (x - CROP_X0) * SX
def ty(y): return y * SY

def font(weight, px):
    return ImageFont.truetype(FONT.format(weight), int(round(px)))

def composite_illustration():
    psd = PSDImage.open(PSD)
    for l in psd.descendants():
        l.visible = (l.name == ILLUSTRATION)
    layer = next(l for l in psd.descendants() if l.name == ILLUSTRATION)
    img = psd.composite().convert('RGBA')
    x0, y0, x1, y1 = layer.bbox
    art = img.crop((max(0, x0), max(0, y0), min(img.width, x1), min(img.height, y1)))
    w, h = int(art.width * SX * ILLU_SCALE), int(art.height * SY * ILLU_SCALE)
    return art.resize((w, h), Image.LANCZOS), (x0, y0)

def composite_art():
    psd = PSDImage.open(PSD)
    for l in psd.descendants():
        if l.kind == 'type' or l.name in TEXT_LAYERS_HIDE:
            l.visible = False
    img = psd.composite().convert('RGBA')
    return img.crop((CROP_X0, 0, CROP_X1, SRC_H)).resize((W, H), Image.LANCZOS)

def rich_text(draw, parts, cx, cy, px, color, rotate=0):
    """parts: [(text, weight)] centred horizontally on (cx, cy)."""
    fonts = [(t, font(w, px)) for t, w in parts]
    total = sum(f.getlength(t) for t, f in fonts)
    layer = Image.new('RGBA', (int(total) + 40, int(px * 2.4)), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    x = 20
    for t, f in fonts:
        d.text((x, px * 1.25), t, font=f, fill=color, anchor='ls')
        x += f.getlength(t)
    if rotate:
        layer = layer.rotate(rotate, resample=Image.BICUBIC, expand=True)
    return layer

def paste_centered(base, layer, cx, cy):
    base.alpha_composite(layer, (int(cx - layer.width / 2), int(cy - layer.height / 2)))

def stars(base, cx, cy, span, color=(249, 211, 26, 255)):
    n = 5; slot = span / n; r = slot * 0.45
    d = ImageDraw.Draw(base)
    for i in range(n):
        sx = cx - span / 2 + slot * (i + 0.5)
        pts = []
        for k in range(10):
            ang = -math.pi / 2 + k * math.pi / 5
            rad = r if k % 2 == 0 else r * 0.45
            pts.append((sx + rad * math.cos(ang), cy + rad * math.sin(ang)))
        d.polygon(pts, fill=color)

def qr_card(base, review_url):
    # white rounded card, centred right of the phone illustration (source coords 338..668, 690..1020)
    x1 = W - CARD_RIGHT_MARGIN
    x0 = x1 - CARD_SIZE
    y0, y1 = ty(690), ty(690) + CARD_SIZE
    card = Image.new('RGBA', base.size, (0, 0, 0, 0))
    ImageDraw.Draw(card).rounded_rectangle([x0, y0, x1, y1], radius=40, fill=(255, 255, 255, 255))
    base.alpha_composite(card)
    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_H, border=4)
    qr.add_data(review_url); qr.make(fit=True)
    m = qr.get_matrix(); n = len(m)
    side = int((x1 - x0) - 2 * 40)
    px = side / n
    img = Image.new('RGBA', (side, side), (255, 255, 255, 255))
    d = ImageDraw.Draw(img)
    for r_, row in enumerate(m):
        for c_, on in enumerate(row):
            if on:
                d.rectangle([c_ * px, r_ * px, (c_ + 1) * px - 1, (r_ + 1) * px - 1], fill=(0, 0, 0, 255))
    base.alpha_composite(img, (int(x0 + 40), int(y0 + 40)))

def main(review_url, out):
    base = composite_art()
    art, _ = composite_illustration()
    base.alpha_composite(art, (ILLU_X, int(ty(772))))
    d = ImageDraw.Draw(base)
    # headline: source 70..726 wide, 60pt -> scaled
    hp = 86
    paste_centered(base, rich_text(d, [('WE WOULD APPRECIATE', 400)], tx(398), ty(178), hp, (255, 255, 255, 255)), tx(398), ty(178))
    paste_centered(base, rich_text(d, [('YOUR ', 400), ('GOOGLE REVIEW!', 700)], tx(398), ty(247), hp, (255, 255, 255, 255)), tx(398), ty(247))
    stars(base, tx(400), ty(96), 317)
    ep = 67
    paste_centered(base, rich_text(d, [('Scan', 600), (' to rate your experience', 400)], tx(404), ty(620), ep, (1, 1, 1, 255)), tx(404), ty(620))
    paste_centered(base, rich_text(d, [('SCAN', 600), (' QR CODE', 400)], 0, 0, 56, (1, 1, 1, 255), rotate=12), ILLU_X + int(0.5 * 254 * SX * ILLU_SCALE), ty(745))
    pw = 40
    paste_centered(base, rich_text(d, [('powered by ', 400), ('kissreviews.com', 600)], tx(398), ty(1159), pw, (1, 1, 1, 255)), tx(398), ty(1159))
    qr_card(base, review_url)
    rgb = Image.new('RGB', base.size, (255, 255, 255)); rgb.paste(base, mask=base.split()[3])
    rgb.save(out + '.png', dpi=(DPI, DPI))
    rgb.save(out + '.pdf', resolution=DPI)
    print('wrote', out + '.png', out + '.pdf', rgb.size)

if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
