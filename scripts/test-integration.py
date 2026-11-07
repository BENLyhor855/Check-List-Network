#!/usr/bin/env python3
"""Run the Go integration suite in a fresh MySQL database, then remove only that database."""
import os
from pathlib import Path
import secrets
import shutil
import subprocess

ROOT = Path(__file__).resolve().parents[1]
settings = {}
for line in (ROOT / '.env').read_text().splitlines():
    if line.strip() and not line.lstrip().startswith('#'):
        key, value = line.split('=', 1)
        settings[key] = value

go = os.environ.get('GO_BINARY') or shutil.which('go')
if not go:
    raise SystemExit('Go is required; set GO_BINARY to the Go compiler.')
subprocess.run([go, 'version'], check=True)
container = subprocess.check_output(
    ['docker', 'compose', 'ps', '-q', 'db'], cwd=ROOT, text=True
).strip()
if not container:
    raise SystemExit('Start the development database with docker compose up -d db first.')

name = 'racnoc_' + secrets.token_hex(8) + '_test'
env = os.environ.copy()
env.update(DB_ADDRESS='127.0.0.1:3306', DB_USER='racnoc',
           DB_PASSWORD=settings['DB_PASSWORD'], DB_NAME=name, TEST_DB='1')

def sql(statement):
    # The container expands its existing credential; it is never printed or put in argv.
    subprocess.run(['docker', 'exec', '-i', container, 'sh', '-c',
                    'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot'],
                   input=statement, text=True, check=True)

created = False
try:
    # CREATE fails on collisions; never reuse or delete an existing test database.
    sql(f'CREATE DATABASE `{name}`;')
    created = True
    sql(f"GRANT ALL ON `{name}`.* TO 'racnoc'@'%';")
    for _ in range(2):
        subprocess.run([go, 'run', './cmd/api', 'migrate'],
                       cwd=ROOT / 'backend', env=env, check=True)
    subprocess.run([go, 'test', '-count=1', '-v', './...'],
                   cwd=ROOT / 'backend', env=env, check=True)
finally:
    if created:
        sql(f"REVOKE ALL ON `{name}`.* FROM 'racnoc'@'%'; DROP DATABASE `{name}`;")
