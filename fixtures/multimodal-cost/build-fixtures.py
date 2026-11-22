#!/usr/bin/env python3
"""Build original PNG fixtures with known facts and proportional variants."""
import hashlib
import json
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
image = Image.new('RGB', (1600, 1000), '#f8fafc')
draw = ImageDraw.Draw(image)
heading = ImageFont.load_default(size=90)
body = ImageFont.load_default(size=42)
small = ImageFont.load_default(size=24)
draw.text((90, 70), 'HARBOR-731', fill='#0f172a', font=heading)
draw.text((90, 210), 'Fictional inventory card', fill='#475569', font=body)
draw.rounded_rectangle((80, 330, 1520, 670), radius=12, fill='#ffffff', outline='#94a3b8', width=3)
draw.rectangle((80, 330, 1520, 430), fill='#dbeafe')
for x, text in [(130, 'Item'), (750, 'Quantity'), (1150, 'Bay')]:
    draw.text((x, 350), text, fill='#0f172a', font=body)
for y, item, quantity, bay in [(470, 'Rivet', '18', 'North-6'), (570, 'Bracket', '42', 'East-3')]:
    for x, text in [(130, item), (750, quantity), (1150, bay)]:
        draw.text((x, y), text, fill='#0f172a', font=body)
draw.line((80, 550, 1520, 550), fill='#cbd5e1', width=2)
draw.text((90, 820), 'Audit seal: COVE-592', fill='#0f172a', font=small)
draw.text((90, 920), 'Teaching fixture. No real inventory or customer.', fill='#475569', font=small)
variants = []
# Resize changes dimensions. PNG compression changes bytes separately.
# Every variant comes from the same original and keeps its aspect ratio.
for name, width in [('full', 1600), ('medium', 800), ('tiny', 240)]:
    height = round(image.height * width / image.width)
    resized = image.resize((width, height), Image.Resampling.LANCZOS)
    filename = f'{name}.png'
    resized.save(ROOT / filename, compress_level=9)
    raw = (ROOT / filename).read_bytes()
    variants.append({
        'name': name, 'file': filename, 'width': width,
        'height': height, 'sha256': hashlib.sha256(raw).hexdigest(),
    })
manifest = {
    'question': 'Read the attached inventory card. Return only JSON with keys heading, small_label, quantity. The small_label is the audit seal. For quantity, use the Rivet row. Use null if a value is unreadable.',
    'expected': {'heading': 'HARBOR-731', 'small_label': 'COVE-592', 'quantity': 18},
    'exercise_question': 'Read the attached inventory card. Return only JSON with keys heading, small_label, quantity. The small_label is the audit seal. For quantity, use the Bracket row. Use null if a value is unreadable.',
    'exercise_expected': {'heading': 'HARBOR-731', 'small_label': 'COVE-592', 'quantity': 42},
    'variants': variants,
}
(ROOT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
print('Built full.png, medium.png, tiny.png and manifest.json')
