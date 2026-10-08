## Переводы

Переводим при помощи Deepl все unit и property у которых указан need_translate

```bash
wp tools translate-units --post-type=unit --limit=10 --dry-run --allow-root 
wp tools translate-units --post-type=property --limit=10 --dry-run --allow-root 
wp tools translate-units --limit=10 --allow-root        # оба типа, unit и property
wp tools translate-units --post-id=33067 --allow-root   # тип определяется сам
wp tools translate-units --limit=5 --language=ru --allow-root
```

Перевод локаций. Переводы описаний находятся в мета description_<slug>, берется список тех у которых пусто и создается
перевод из английской версии

```bash
wp tools translate-locations --limit=10 --dry-run --allow-root
wp tools translate-locations --language=ru --limit=10 --allow-root
wp tools translate-locations --term-id=141 --force --allow-root
```

Клонировать unit или property для нового языка с пометкой need_translate Источником берётся только пост на языке по
умолчанию

```bash
wp tools clone-language --locale=de_DE --dry-run --allow-root
wp tools clone-language --locale=de --limit=50 --allow-root
wp tools clone-language --locale=de_DE --post-type=property --yes --allow-root
wp tools clone-language --locale=de --post-type=unit --yes --allow-root
wp tools clone-language --locale=de_DE --post-id=12345 --allow-root
```

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