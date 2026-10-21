#!/usr/bin/env python3
"""Create a local reader data source without credentials or opening the IDE."""
import sys
from pathlib import Path
from xml.etree.ElementTree import Element, SubElement, ElementTree, indent

project, port = Path(sys.argv[1]), int(sys.argv[2])
root = Element('project', version='4')
manager = SubElement(root, 'component', name='DataSourceManagerImpl',
                     **{'format': 'xml', 'multifile-model': 'true'})
source = SubElement(manager, 'data-source', source='LOCAL',
                    name='Shop read only',
                    uuid='50631300-5130-4513-8513-000000000031')
SubElement(source, 'driver-ref').text = 'mariadb'
SubElement(source, 'synchronize').text = 'true'
SubElement(source, 'jdbc-driver').text = 'org.mariadb.jdbc.Driver'
SubElement(source, 'jdbc-url').text = (
    f'jdbc:mariadb://127.0.0.1:{port}/neuron_shop'
    '?user=analyst_ro&password=')
SubElement(source, 'auth-provider').text = 'no-auth'
SubElement(source, 'user-name').text = 'analyst_ro'
mapping = SubElement(source, 'schema-mapping')
scope = SubElement(mapping, 'introspection-scope')
SubElement(scope, 'node', kind='schema', qname='neuron_shop')
indent(root)
(project / '.idea').mkdir(exist_ok=True)
ElementTree(root).write(project / '.idea/dataSources.xml',
                        encoding='UTF-8', xml_declaration=True)
local_root = Element('project', version='4')
local_manager = SubElement(local_root, 'component',
                           name='dataSourceStorageLocal')
local_source = SubElement(local_manager, 'data-source',
                          name='Shop read only',
                          uuid=source.get('uuid'))
SubElement(local_source, 'user-name').text = 'analyst_ro'
SubElement(local_source, 'auth-provider').text = 'no-auth'
local_mapping = SubElement(local_source, 'schema-mapping')
local_scope = SubElement(local_mapping, 'introspection-scope')
SubElement(local_scope, 'node', kind='schema', qname='neuron_shop')
indent(local_root)
ElementTree(local_root).write(project / '.idea/dataSources.local.xml',
                              encoding='UTF-8', xml_declaration=True)
# The project dialect keeps the SQL files and the console free of the
# "SQL dialect is not configured" banner and of false syntax counters.
dialect_root = Element('project', version='4')
dialect_component = SubElement(dialect_root, 'component',
                               name='SqlDialectMappings')
SubElement(dialect_component, 'file', url='PROJECT', dialect='MySQL')
indent(dialect_root)
ElementTree(dialect_root).write(project / '.idea/sqldialects.xml',
                                encoding='UTF-8', xml_declaration=True)
