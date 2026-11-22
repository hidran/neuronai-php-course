#!/usr/bin/env python3
"""Build fictional invoices; render actual PDFs with Poppler, then resize."""
import hashlib
import json
import subprocess
from pathlib import Path
from PIL import Image
ROOT = Path(__file__).resolve().parent
cases = {
    'normal': dict(number='0007-A', supplier_name='North Pine Works', issue_date='2026-10-12', currency='EUR', subtotal=38.0, vat_amount=7.6, total=45.6, lines=[dict(description='Cable clips', quantity=3.0, unit_price=4.5, line_total=13.5), dict(description='Mounting plate', quantity=2.0, unit_price=12.25, line_total=24.5)]),
    'alternate': dict(number='INV/0042-B', supplier_name='Amber Dock Studio', issue_date='2026-10-14', currency='GBP', subtotal=45.0, vat_amount=9.0, total=54.0, lines=[dict(description='Panel bracket', quantity=4.0, unit_price=8.25, line_total=33.0), dict(description='Packing sleeve', quantity=5.0, unit_price=2.4, line_total=12.0)]),
    'tax-exempt': dict(number='TAX-0009', supplier_name='Maple Learning Coop', issue_date='2026-10-15', currency='USD', subtotal=45.0, vat_amount=0.0, total=45.0, lines=[dict(description='Workshop handbook', quantity=3.0, unit_price=10.0, line_total=30.0), dict(description='Practice cards', quantity=2.0, unit_price=7.5, line_total=15.0)]),
    'inconsistent': dict(number='CHECK-0011', supplier_name='Cedar Measure Lab', issue_date='2026-10-16', currency='EUR', subtotal=45.0, vat_amount=9.0, total=54.0, lines=[dict(description='Gauge block', quantity=2.0, unit_price=14.0, line_total=28.0), dict(description='Caliper case', quantity=1.0, unit_price=12.0, line_total=12.0)]),
    'exercise': dict(number='EX/0073', supplier_name='Willow Bench Supply', issue_date='2026-10-18', currency='EUR', subtotal=37.0, vat_amount=7.4, total=44.4, lines=[dict(description='Spacer set', quantity=4.0, unit_price=3.75, line_total=15.0), dict(description='Steel guide', quantity=2.0, unit_price=11.0, line_total=22.0)]),
}
def pdf(name, commands):
    pieces = []
    for x, y, size, value in commands:
        escaped = value.replace('\\','\\\\').replace('(','\\(').replace(')','\\)')
        pieces.append(f'BT /F1 {size} Tf {x} {y} Td ({escaped}) Tj ET')
    stream = ('\n'.join(pieces)+'\n').encode('ascii')
    objects = [b'<< /Type /Catalog /Pages 2 0 R >>', b'<< /Type /Pages /Kids [3 0 R] /Count 1 >>', b'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', b'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', f'<< /Length {len(stream)} >>\nstream\n'.encode()+stream+b'endstream']
    out=bytearray(b'%PDF-1.4\n%\xe2\xe3\xcf\xd3\n'); offsets=[0]
    for n,obj in enumerate(objects,1):
        offsets.append(len(out));out+=f'{n} 0 obj\n'.encode()+obj+b'\nendobj\n'
    xref=len(out);out+=f'xref\n0 {len(objects)+1}\n0000000000 65535 f \n'.encode()
    for offset in offsets[1:]:out+=f'{offset:010d} 00000 n \n'.encode()
    out+=f'trailer\n<< /Size {len(objects)+1} /Root 1 0 R >>\nstartxref\n{xref}\n%%EOF\n'.encode()
    (ROOT/f'{name}.pdf').write_bytes(out)
manifest={'cases':{},'files':{}}
for name,inv in cases.items():
    commands=[(45,750,11,'FICTIONAL TEACHING DOCUMENT'),(45,710,24,'INVOICE'),(45,675,16,f"Number: {inv['number']}"),(45,645,16,f"Supplier: {inv['supplier_name']}"),(45,615,14,f"Issue date: {'14 October 2026' if name=='alternate' else inv['issue_date']}"),(45,585,14,f"Currency: {inv['currency']}"),(45,555,11,'All unit prices and line totals exclude tax.')]
    if name=='alternate':
        commands += [(45,505,13,'Line total     Description                    Unit price     Quantity')]
        for i,line in enumerate(inv['lines']):
            commands += [(45,465-i*38,13,f"{line['line_total']:.2f}          {line['description']:<25} {line['unit_price']:.2f}          {line['quantity']:g}")]
    elif name=='tax-exempt':
        commands += [(45,515,18,'TAX EXEMPT: NO VAT'),(45,475,13,'Description / Quantity / Unit price / Line total')]
        for i,line in enumerate(inv['lines']):
            commands += [(45,435-i*38,13,f"{line['description']} / {line['quantity']:g} / {line['unit_price']:.2f} / {line['line_total']:.2f}")]
    else:
        commands += [(45,505,13,'Description                    Quantity     Unit price     Line total')]
        for i,line in enumerate(inv['lines']):
            commands += [(45,465-i*38,13,f"{line['description']:<30} {line['quantity']:g}             {line['unit_price']:.2f}             {line['line_total']:.2f}")]
    commands += [(45,285,16,f"Subtotal: {inv['subtotal']:.2f}"),(45,255,16,f"Tax amount: {inv['vat_amount']:.2f}"),(45,225,18,f"Grand total: {inv['total']:.2f}"),(45,100,10,'Fictional details. No real supplier, customer or transaction.')]
    pdf(name,commands)
    subprocess.run(['pdftoppm','-r','144','-png','-singlefile',str(ROOT/f'{name}.pdf'),str(ROOT/name)],check=True)
    manifest['cases'][name]={'invoice':inv,'status':'review_required' if name=='inconsistent' else 'accepted'}
    for ext in ['pdf','png']:
        path=ROOT/f'{name}.{ext}'
        manifest['files'][path.name]={'case':name,'sha256':hashlib.sha256(path.read_bytes()).hexdigest()}
full=Image.open(ROOT/'normal.png')
full.save(ROOT/'invoice-full.png')
full.resize((612,792),Image.Resampling.LANCZOS).save(ROOT/'invoice-small.png')
for name in ['invoice-full.png','invoice-small.png']:
    manifest['files'][name]={'case':'normal','sha256':hashlib.sha256((ROOT/name).read_bytes()).hexdigest()}
(ROOT/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
print('Built five fictional PDF invoices, actual rendered previews and image variants.')
