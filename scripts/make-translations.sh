#!/usr/bin/env bash
#
# Пересборка переводов темы, включая JSON для JavaScript.
#
# Зачем скрипт, а не одна команда wp i18n make-json: тема отдаёт браузеру один
# собранный бандл assets/js/main.min.js, а make-json по умолчанию создаёт по
# JSON-файлу на каждый ИСХОДНЫЙ js-файл и называет их хешем пути исходника.
# WordPress такие имена не ищет и переводы не подхватывает. Ниже — цепочка,
# которая даёт ровно один файл с правильным именем.
#
# Три вещи, на которых легко ошибиться, и все три здесь учтены:
#
#  1. make-json читает .po, а не .pot. Если .po не синхронизирован с шаблоном,
#     ссылки на js-файлы в нём отсутствуют и строки молча теряются: так было
#     с Area, Developer, Unit и sqft — в .pot ссылка на src/js/map/html.js
#     была, в .po её не было. Поэтому сначала update-po.
#
#  2. --use-map подменяет путь исходника на путь бандла, и тогда make-json
#     сливает все исходники в один JSON. Карта строится по факту, из того что
#     лежит в src/js, чтобы не устаревала при добавлении файлов.
#
#  3. WordPress ищет файл по handle РАНЬШЕ, чем по хешу пути
#     (load_script_textdomain в wp-includes/l10n.php). Поэтому старый файл
#     вида {домен}-{локаль}-{handle}.json затеняет свежий и его надо удалять,
#     иначе правки переводов не доходят до браузера.
#
# Имя итогового файла — хеш от пути БЕЗ .min: ядро само приводит
# assets/js/main.min.js к assets/js/main.js перед хешированием, и make-json
# делает то же самое. Совпадение проверено.
#
# Запуск из каталога темы:
#   bash scripts/make-translations.sh
#
# На сервере:
#   WP="wp --allow-root" bash scripts/make-translations.sh
#
set -euo pipefail

# Всё логирование в stderr, чтобы вывод можно было безопасно перенаправлять.
log() { printf '[i18n] %s\n' "$*" >&2; }
die() { printf '[i18n] ОШИБКА: %s\n' "$*" >&2; exit 1; }

WP=${WP:-wp}
THEME_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)
LANG_DIR="$THEME_DIR/languages"
DOMAIN=${DOMAIN:-east-property}
BUNDLE=${BUNDLE:-assets/js/main.min.js}

cd "$THEME_DIR"

[ -d "$LANG_DIR" ] || die "нет каталога $LANG_DIR"
[ -f "$BUNDLE" ] || log "предупреждение: $BUNDLE не собран, но карта путей от этого не зависит"

log "тема:   $THEME_DIR"
log "домен:  $DOMAIN"
log "бандл:  $BUNDLE"

# --- 1. шаблон -------------------------------------------------------------
log 'make-pot: пересобираю шаблон'
$WP i18n make-pot . "languages/$DOMAIN.pot" --domain="$DOMAIN" >&2

# --- 2. синхронизация переводов с шаблоном --------------------------------
# Без этого шага make-json не увидит js-ссылки и потеряет часть строк.
log 'update-po: подтягиваю новые строки и ссылки в .po'
$WP i18n update-po "languages/$DOMAIN.pot" languages >&2

# --- 3. бинарники для PHP -------------------------------------------------
log 'make-mo: собираю .mo'
$WP i18n make-mo languages >&2

# --- 4. карта «исходник -> бандл» -----------------------------------------
# Строится каждый раз заново: добавили js-файл — он попадёт в карту сам.
MAP=$(mktemp -t js-map.XXXXXX.json)
trap 'rm -f "$MAP"' EXIT

python3 - "$MAP" "$BUNDLE" <<'PY'
import glob, json, sys

map_path, bundle = sys.argv[1], sys.argv[2]
sources = sorted(glob.glob('src/js/**/*.js', recursive=True))

if not sources:
    raise SystemExit('в src/js не найдено ни одного .js — карта была бы пустой')

json.dump({s: bundle for s in sources}, open(map_path, 'w', encoding='utf-8'), indent=1)
print(f'исходников в карте: {len(sources)}', file=sys.stderr)
PY

log "карта: $MAP"

# --- 5. снимаем прежние JSON ----------------------------------------------
# Среди них может быть файл по handle, который затеняет всё остальное.
removed=$(find "$LANG_DIR" -maxdepth 1 -name "$DOMAIN-*.json" -print -delete | wc -l | tr -d ' ')
log "удалено прежних json: $removed"

# --- 6. один JSON на бандл ------------------------------------------------
log 'make-json: собираю переводы для js'
$WP i18n make-json languages --no-purge --pretty-print --use-map="$MAP" >&2

# --- 7. проверка ----------------------------------------------------------
# Сверяем то, что реально вызывается в исходниках, с тем, что попало в JSON.
# Дешёвая проверка, которая ловит и забытый update-po, и промах карты.
python3 - "$LANG_DIR" "$DOMAIN" <<'PY'
import glob, json, re, sys

lang_dir, domain = sys.argv[1], sys.argv[2]

used = set()
for path in glob.glob('src/js/**/*.js', recursive=True):
    with open(path, encoding='utf-8') as handle:
        used |= set(re.findall(r"\b(?:__|_x|_n|_nx)\(\s*'([^']+)'", handle.read()))

files = sorted(glob.glob(f'{lang_dir}/{domain}-*.json'))
if not files:
    raise SystemExit('json не создан — смотрите вывод make-json выше')

status = 0
for path in files:
    data = json.load(open(path, encoding='utf-8'))
    messages = data['locale_data']['messages']
    # Ключи с контекстом хранятся как "context\x04msgid".
    have = {key.split('\x04')[-1] for key in messages if key}
    missing = sorted(used - have)

    print(f'{path.split("/")[-1]}: source={data.get("source")} строк={len(have)}', file=sys.stderr)

    if missing:
        status = 1
        print(f'  НЕ ХВАТАЕТ {len(missing)}: {missing}', file=sys.stderr)

if status:
    raise SystemExit('часть строк из js не попала в json')

print(f'все {len(used)} строк из js на месте', file=sys.stderr)
PY

log 'готово'
