## Переводы

Переводим при помощи Deepl все unit, property и новости (post) у которых указан need_translate
--dry-run - скрыть вывод в консоль

```bash
wp tools translate-units --post-type=unit --limit=10000 --language=de --allow-root 
wp tools translate-units --post-type=property --limit=10 --language=ru --allow-root 
wp tools translate-units --post-type=post --limit=10 --language=de --allow-root   # новости
wp tools translate-units --limit=10 --language=de --allow-root        # все три типа
wp tools translate-units --post-id=33067 --allow-root   # тип определяется сам
wp tools translate-units --limit=5 --language=ru --allow-root
```

У новостей переводятся заголовок, анонс и текст. Блочная разметка Гутенберга
сохраняется: служебные комментарии `<!-- wp:… -->` на время запроса подменяются
заглушками, потому что с `tag_handling=html` DeepL отклоняет строку с четырьмя и
более HTML-комментариями.

Сбросить кэш deepl

```bash
wp option delete ep_deepl_cache --allow-root
```

Перевод локаций. Переводы описаний находятся в мета description_<slug>, берется список тех у которых пусто и создается
перевод из английской версии

```bash
wp tools translate-locations --limit=10 --allow-root
wp tools translate-locations --language=ru --limit=10 --allow-root
wp tools translate-locations --term-id=141 --force --allow-root
```

Клонировать unit или property для нового языка с пометкой need_translate Источником берётся только пост на языке по
умолчанию

```bash
wp tools clone-language --locale=de_DE --dry-run --allow-root
wp tools clone-language --locale=de --post-type=unit --limit=5 --allow-root
wp tools clone-language --locale=de_DE --post-type=property --yes --allow-root
wp tools clone-language --locale=de --post-type=unit --yes --allow-root
wp tools clone-language --locale=de_DE --post-id=12345 --allow-root
wp tools clone-language --locale=de_DE --post-type=page --dry-run --allow-root
wp tools clone-language --locale=de_DE --post-id=170 --yes --allow-root
```

Связи переносятся на новый язык: юнит привязывается к property этого языка,
property — к developers через `developer_rel`. Чего на языке ещё нет, создаётся
тем же прогоном, поэтому `--post-type=unit` может завести и property, и
developers. `--dry-run` перечисляет всю цепочку.

Страницы и новости берутся только по явному `--post-type=page,post` или
`--post-id`, в обход по умолчанию не попадают. Без перевода страницы листинга Polylang уводит
`/<язык>/projects/` редиректом на английскую версию, и ссылки с главной
перестают работать:

после запускаем `wp tools translate-units`

## Импорт из Geniemap

Обновляем данные из geniemap

```bash
cd /var/www/eastproperty.com/public/wp-content/mu-plugins/geniemap-parser
python3 geniemap_parser.py
```

Запускаем импорт всех Developers, они закэшируются и после уже запускать Properties и Units

```bash
wp geniemap import --limit=5 --type=developers --allow-root
wp geniemap import --limit=5 --type=properties --allow-root
```

## Импорт из файла /uploads/import/properties_list.csv

Загрузить properties из /uploads/import/properties_list.csv Выгрузятся только новые. Для переводов создадутся посты, но
нужно запускать автоперевод

```bash
wp tools import-properties --limit=50 --allow-root

#запускаем перевод
wp tools translate-units --limit=50 --allow-root
```

## Кэширование

Прогрев кэша по самым популярным страницам

```bash
wp tools warm-cache --dry-run --allow-root
wp tools warm-cache --allow-root
wp tools warm-cache --log=/var/log/nginx/access.log --top=100 --allow-root
```

## Удаление Floor

Удаляем floor из slug и title в юнитах

```bash
wp tools strip-unit-floors --dry-run --report=/tmp/unit-floors-plan.csv --allow-root
wp tools strip-unit-floors --limit=10 --allow-root
wp tools strip-unit-floors --yes --report=/tmp/unit-floors.csv --allow-root
```
## Открытие языка (китайский, zh)

Порядок обязателен: язык → страницы → клоны → перевод → публикация. Каждый шаг
повторяем: второй запуск ничего не дублирует.

### 1. Язык и локаль

```bash
wp tools add-language --slug=zh --dry-run --allow-root
wp tools add-language --slug=zh --allow-root
wp language core install zh_CN --allow-root
wp rewrite flush --allow-root
```

Настройки берутся из `Language_Installer::PRESETS` (`zh` / `zh_CN` / 简体中文 /
флаг `cn` / LTR). Повторный запуск печатает «язык уже настроен». Локаль, занятая
другим языком, останавливает команду, второго языка не возникает.

`wp language core install` обязателен: без языкового пакета ядра `zh_CN` не
попадает в `get_available_languages()`, `switch_to_locale()` молча откатывается
на `en_US`, и письма с админкой остаются английскими.

### 2. Страницы листингов

Без них `/zh/projects/` и остальные отдают 301 на английскую версию.

```bash
wp tools clone-language --locale=zh_CN --post-type=page --limit=50 --dry-run --allow-root
wp tools clone-language --locale=zh_CN --post-type=page --limit=50 --copy-status=draft --yes --allow-root
wp tools translate-units --language=zh --post-type=page --limit=20 --allow-root
```

Копии создаются черновиками, переводятся и только потом публикуются — английский
текст под видом китайского на сайт не попадает. Юридические страницы
(`privacy-policy`, `terms-of-use`) машинным переводом не трогаем.

### 3. Каталог

```bash
# сначала пробный прогон на одной записи
wp tools clone-language --locale=zh_CN --post-id=<id> --dry-run --allow-root
wp tools clone-language --locale=zh_CN --post-id=<id> --yes --allow-root
wp tools translate-units --post-id=<копия> --allow-root

# затем пакетами
wp tools clone-language --locale=zh_CN --limit=200 --copy-status=draft --yes --allow-root
wp tools translate-units --language=zh --limit=50 --allow-root
```

Черновики входят в выборку по умолчанию (`Unit_Translator::DEFAULT_STATUSES`),
отдельный флаг не нужен; `--status=publish` наоборот сужает прогон до живых
записей.

`clone-language` сам заводит зависимости: юнит → проект → застройщик, каждый на
своём языке. Группа переводов EN/RU/DE сохраняется, zh в неё добавляется.

Заголовки проектов и застройщиков не переводятся — это зарегистрированные имена
(`Unit_Translator::TITLE_PROTECTED_TYPES`). Переводятся описания, анонсы,
`amenities` и `payment_plans`.

### 4. Продолжение после сбоя и откат

Ошибка API оставляет запись с `need_translate` и прежним текстом: повторный
запуск той же команды берёт её снова, дублей не возникает. Оплаченный кусок
остаётся в кэше (`ep_deepl_cache`), повторная отправка тех же строк бесплатна.

```bash
wp tools translate-units --language=zh --limit=50 --allow-root                   # продолжить
wp option delete ep_deepl_cache --allow-root                                     # сбросить кэш
```

Откат языка целиком — удалить его в «Языки» Polylang: вместе с ним уходят
привязки записей, сами копии остаются черновиками и видны по
`wp post list --post_type=unit --post_status=draft`.

### 5. После миграции

```bash
wp rewrite flush --allow-root
wp cache flush --allow-root
wp tools warm-cache --allow-root
```

На проде ещё W3TC: `wp w3-total-cache flush all`.
