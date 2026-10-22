#!/usr/bin/env python3
"""Build original, fictional media fixtures. Requires Pillow only."""
import json
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
image = Image.new('RGB', (1040, 620), '#f8fafc')
draw = ImageDraw.Draw(image)
heading = ImageFont.load_default(size=40)
body = ImageFont.load_default(size=26)
label = ImageFont.load_default(size=34)
draw.text((45, 35), 'MEDIA FIXTURE', fill='#0f172a', font=heading)
draw.text((45, 95), 'Fictional shapes for attachment checks', fill='#475569', font=body)
draw.polygon([(190, 200), (80, 410), (300, 410)], fill='#16a34a')
draw.ellipse((420, 200, 640, 420), fill='#2563eb')
draw.rectangle((760, 200, 980, 420), fill='#f97316')
for x, text in [(190, 'CEDAR-47'), (530, 'ORBIT-29'), (870, 'SUMMIT-63')]:
    draw.text((x, 485), text, fill='#0f172a', font=label, anchor='mm')
draw.text((45, 575), 'Read the attachment. Do not guess from its filename.', fill='#475569', font=body)
image.save(ROOT / 'shapes.png', optimize=False)


def make_pdf(filename, lines):
    """Write a deterministic, single-page PDF with actual selectable text."""
    text = ['BT', '/F1 22 Tf', '50 745 Td']
    for index, line in enumerate(lines):
        if index:
            text += ['0 -42 Td', '/F1 16 Tf']
        escaped = line.replace('\\', '\\\\').replace('(', '\\(').replace(')', '\\)')
        text.append(f'({escaped}) Tj')
    stream = ('\n'.join(text + ['ET']) + '\n').encode('ascii')
    objects = [
        b'<< /Type /Catalog /Pages 2 0 R >>',
        b'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        b'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        b'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        f'<< /Length {len(stream)} >>\nstream\n'.encode('ascii') + stream + b'endstream',
    ]
    data = bytearray(b'%PDF-1.4\n%\xe2\xe3\xcf\xd3\n')
    offsets = [0]
    for number, item in enumerate(objects, 1):
        offsets.append(len(data))
        data += f'{number} 0 obj\n'.encode('ascii') + item + b'\nendobj\n'
    xref = len(data)
    data += f'xref\n0 {len(objects) + 1}\n0000000000 65535 f \n'.encode('ascii')
    for offset in offsets[1:]:
        data += f'{offset:010d} 00000 n \n'.encode('ascii')
    data += f'trailer\n<< /Size {len(objects) + 1} /Root 1 0 R >>\nstartxref\n{xref}\n%%EOF\n'.encode('ascii')
    (ROOT / filename).write_bytes(data)
    (ROOT / filename.replace('.pdf', '.txt')).write_text('\n'.join(lines) + '\n')


make_pdf('note.pdf', [
    'Fictional packing note',
    'Project: Cedar Relay',
    'Dispatch code: PINE-842',
    'Crates: 17',
    'Dock: West-4',
    'Review word: saffron',
    'Teaching fixture only. No real shipment or customer.',
])
facts = {
    'image': {'triangle_color': 'green', 'triangle_label': 'CEDAR-47', 'circle_label': 'ORBIT-29'},
    'pdf': {'dispatch_code': 'PINE-842', 'crates': 17, 'dock': 'West-4', 'review_word': 'saffron'},
}
(ROOT / 'expected.json').write_text(json.dumps(facts, indent=2) + '\n')
print('Built shapes.png, note.pdf, note.txt and expected.json')
