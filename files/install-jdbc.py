#!/usr/bin/env python3
"""Seed MariaDB's JDBC library in an isolated IDE profile, off camera."""
import hashlib
import json
import sys
import urllib.request
from pathlib import Path
from xml.etree import ElementTree as ET

profile = Path(sys.argv[1]).resolve()
version = '3.5.10'
jar = profile / 'home/jdbc-drivers' / f'mariadb-java-client-{version}.jar'
url = ('https://repo.maven.apache.org/maven2/org/mariadb/jdbc/'
       f'mariadb-java-client/{version}/{jar.name}')
jar.parent.mkdir(parents=True, exist_ok=True)
if not jar.exists():
    payload = urllib.request.urlopen(url, timeout=60).read()
    expected = urllib.request.urlopen(url + '.sha256', timeout=30).read().decode().split()[0]
    if hashlib.sha256(payload).hexdigest() != expected:
        raise RuntimeError('JDBC checksum mismatch')
    jar.write_bytes(payload)
options = profile / 'config/options/databaseDrivers.xml'
options.parent.mkdir(parents=True, exist_ok=True)
root = ET.parse(options).getroot() if options.exists() else ET.Element('application')
component = root.find("component[@name='LocalDatabaseDriverManager']")
if component is None:
    component = ET.SubElement(root, 'component', name='LocalDatabaseDriverManager', version='201')
old = component.find("driver[@id='mariadb']")
if old is not None:
    component.remove(old)
driver = ET.SubElement(component, 'driver', id='mariadb')
# An empty artifact element clears bundled artifact downloads. This local library replaces them.
ET.SubElement(driver, 'artifact')
library = ET.SubElement(driver, 'library')
ET.SubElement(library, 'url').text = 'jar://' + str(jar) + '!/'
ET.indent(root)
ET.ElementTree(root).write(options, encoding='UTF-8', xml_declaration=True)
# Suppress exactly the one-shot Cloud Database Import onboarding balloon.
# GotItTooltip prefixes its database.cloud.explorer.promo ID with got.it.tooltip.
other = options.with_name('other.xml')
ui_root = ET.parse(other).getroot() if other.exists() else ET.Element('application')
properties = ui_root.find("component[@name='PropertyService']")
if properties is None:
    properties = ET.SubElement(ui_root, 'component', name='PropertyService')
values = json.loads(properties.text or '{}')
values.setdefault('keyToString', {})['got.it.tooltip.database.cloud.explorer.promo'] = '1'
properties.text = json.dumps(values, indent=2)
ET.indent(ui_root)
ET.ElementTree(ui_root).write(other, encoding='UTF-8', xml_declaration=True)
print('JDBC READY:', jar)
print('Driver config:', options)
