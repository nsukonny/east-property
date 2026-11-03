#### Добавление нового языка по шагам
```bash
#### 1. Добавление языка и локали
wp tools add-language --slug=ar --allow-root
wp language core install ar --allow-root
wp rewrite flush --allow-root

#### 2. Клонирование
wp tools clone-language --locale=ar --post-type=page --allow-root
wp tools clone-language --locale=ar --post-type=post --allow-root
wp tools clone-language --locale=ar --post-type=property --limit=3000 --allow-root
wp tools clone-language --locale=ar --post-type=unit --copy-status=draft --limit=5000 --allow-root

#### 3. Перевод
wp tools translate-units --language=ar --post-type=page --limit=20 --allow-root
wp tools translate-units --post-type=post --limit=100 --language=ar --allow-root   # новости
wp tools translate-units --language=ar --post-type=property --limit=3000 --allow-root
wp tools translate-units --language=ar --post-type=unit --limit=5000 --allow-root

wp tools translate-locations --language=ar --limit=10000 --allow-root

### 4. Клонируем меню
wp tools clone-menus --locale=ar --labels=inherit --allow-root

#### 4. Сброс кэшей
wp option delete ep_deepl_cache --allow-root
wp rewrite flush --allow-root
wp w3-total-cache flush all --allow-root
wp cache flush --allow-root
wp tools warm-cache --allow-root
```

## Переводы

### 1. Язык и локаль

```bash
wp tools add-language --slug=ar --dry-run --allow-root   # или --slug=hi, --slug=ar
wp tools add-language --slug=ar --allow-root
wp language core install ar --allow-root              # или hi_IN, ar
wp rewrite flush --allow-root
```

### 2. Чем переводим

Провайдер выбирается в `.env` рядом с `wp-config.php`:

```ini
TRANSLATE_PROVIDER="gemini"          # gemini (по умолчанию) или deepl
GEMINI_API_KEY="…"                   # aistudio.google.com/apikey
GEMINI_MODEL="gemini-3.1-flash-lite" # необязательно
```

Замер 27.10.2026 на содержимом базы: 12 000 юнитов — это ~41 млн символов.
У DeepL это ~€900, у Gemini 3.1 Flash-Lite ~$23, пакетным режимом вдвое
меньше. Поэтому брать самую дешёвую модель смысла нет: разница между
моделями здесь — единицы долларов, а разница в качестве стоит дороже.

Второе, и более важное: DeepL указаний не принимает, а модель принимает.
Отраслевые значения слов лежат в `translation-domain.md` и уходят в системный
промпт — это тот самый список, из-за которого DeepL выдавал `distress` как
«моя печаль», `Post Handover` как «без передачи должности», а `Handover` как
«передача задач». Файл написан по-английски и описывает смысл, а не перевод,
поэтому работает сразу на все языки и проверяется чтением.

Цена за это — модель может потерять тег или дописать «вот перевод». Поэтому
`Gemini_Client` сверяет каждый ответ с исходником: набор тегов, плейсхолдеры
`%s`/`%d`/`%%`, непустота и длина в пределах 0,3–3,5 от исходной. Что не
прошло — перезапрашивается поодиночке, что не прошло дважды — роняет порцию
с ошибкой, а не пишется в базу. Блочные комментарии Гутенберга маскируются,
как и раньше, и возвращаются байт в байт.

Отдельная ловушка: описание юнита — это полсотни строк в одном поле, и модель
принимала перевод строки за границу элемента, возвращая сорок один перевод на
один запрос. Лечится не проверкой, а запросом: в нём названо число элементов и
сказано, что перевод строки элементы не разделяет.

Переменные окружения из шелла сюда не доходят: `core_env()` читает только
`$_ENV`, а `variables_order` и на локалке, и на проде — `GPCS`, без `E`.
Значит `TRANSLATE_PROVIDER=…` перед командой молча ничего не меняет; провайдер
и модель задаются либо в `.env`, либо флагами.

Перед большим прогоном — сравнение на своих же текстах, в базу не пишет:

```bash
wp tools translate-bakeoff --limit=20 --allow-root
wp tools translate-bakeoff --limit=20 --model=gemini-2.5-flash-lite --allow-root
wp tools translate-bakeoff --limit=5 --provider=deepl --allow-root
```

Команда печатает, сколько ответов отклонено проверкой, сколько стоил прогон и
сколько выйдет на 12 000 юнитов, а сами тексты кладёт в файл — термины и
падежи всё равно смотреть глазами. Ответы бейк-оффа мимо кэша, поэтому те же
строки можно тут же отдать другому провайдеру.

### Маркер конца — замерен и отклонён

Идея была закрыть дыру, которую не видит ничто: обрыв внутри элемента при
завершившемся ответе. Массив нужной длины, `finishReason: STOP`, теги и
плейсхолдеры целы, а в поле нет трети текста — отношение длины видит только
грубый обрыв.

`--marker` просит модель закрывать каждый перевод `[[end-translate]]` и
считает, как часто она этого не делает. Замеры 29.10.2026, по 30 юнитов,
90 ответов:

| формулировка правила | промахов | как именно |
|---|---|---|
| «exactly once, as the very last characters» | 4 (4.4%) | маркер стоял **16 раз** — после каждого блока |
| «one item gets one terminator however many lines it holds» | 4 (4.4%) | маркер **не поставлен вовсе** |

Вторая формулировка чинит первую: на тех же записях 16 → 1, четыре прогона
из четырёх. Но доля промахов не изменилась — сдвинулись только записи и вид
отказа. Это не баг формулировки, а потолок.

**И промахи детерминированы.** По четыре прогона на каждой упавшей записи
дают один и тот же результат, так что повтор не помогает никогда. В пересчёте
это 2 записи из 30: на 12 000 юнитов около 800 штук, которые при включённой
проверке не перевелись бы вообще.

Поэтому проверка **выключена**. Флаг и код оставлены: когда он выключен,
отпечаток промпта прежний (`8f51acd0`) и оплаченный кэш остаётся в деле.

Стоит помнить и то, ради чего всё затевалось: за все прогоны на этом проекте
тихий обрыв внутри элемента не встретился ни разу. Платить 17% к токенам и
7% нечитаемых отказов за защиту от ненаблюдавшейся беды смысла нет.

Кэш `ep_deepl_cache` общий, но ключи разведены по провайдеру и модели:
оплаченные переводы DeepL остаются в деле, переключение туда и обратно их не
портит.

### 3. Прогон

Переводим все unit, property и новости (post), у которых указан need_translate.
--dry-run - скрыть вывод в консоль

```bash
wp tools translate-units --post-type=unit --limit=10000 --language=de --allow-root 
wp tools translate-units --post-type=property --limit=10 --language=ru --allow-root 
wp tools translate-units --post-type=post --limit=10 --language=de --allow-root   # новости
wp tools translate-units --limit=10 --language=de --allow-root        # все три типа
wp tools translate-units --post-id=33067 --allow-root   # тип определяется сам
wp tools translate-units --limit=5 --language=ru --allow-root
```

Массовый отбор идёт по статусам `publish`, `draft` и `future`: отложенная
новость копируется клонером с пометкой need_translate, и без `future` она
доходила до публикации непереведённой. Сузить набор — через `--status`.

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

Копируются записи в статусах `publish`, `draft` и `future` — копия забирает
статус и дату источника, поэтому отложенный пост остаётся отложенным на тот же
день и получает своё крон-задание. Сузить набор можно через `--status`,
задать статус копиям — через `--copy-status`.

Страницы и новости берутся только по явному `--post-type=page,post` или
`--post-id`, в обход по умолчанию не попадают. Без перевода страницы листинга Polylang уводит
`/<язык>/projects/` редиректом на английскую версию, и ссылки с главной
перестают работать:

после запускаем `wp tools translate-units`

## Привязка юнитов к проекту своего языка

Юнит хранит проект в мете `property` и хранит тот id, с которым его завели:
импортёр пишет во все русские юниты английский проект, а клон, сделанный до
того, как клонер научился перепривязывать, тащит за собой исходный. Листинг на
странице проекта сравнивает эту мету с одним id, поэтому юнит с чужой
привязкой невидим на своём проекте и при этом считается на английском.

Замер 01.11.2026: **5 202 юнита** привязаны к проекту другого языка —
ru→en 4009, de→en 351, hi→en 324, zh→en 324, ar→en 194.

```bash
wp tools relink-units --dry-run --allow-root
wp tools relink-units --dry-run --report=/tmp/relink.csv --allow-root
wp tools relink-units --language=de --allow-root
wp tools relink-units --yes --allow-root
```

Сначала считает и показывает разбивку по парам языков, потом спрашивает
подтверждение. `--dry-run` не спрашивает и не пишет, `--yes` пропускает
вопрос. В конце сбрасывает кэши: выдача проекта лежит в группе `units`.

Чего команда не делает:

* **Не выдумывает связь.** Если у проекта нет перевода на язык юнита,
  запись попадает в «нечем» и остаётся как была — таких 7.
* **Не трогает юниты без привязки вовсе** — их 2, связь взять неоткуда.

Полю `property` синхронизация Polylang не грозит: оно не входит в
`pll_copy_post_metas` (проверено — правка одного перевода не разъезжается по
остальным), поэтому пишется обычным `update_post_meta`.

Связь — только половина беды. Вторая половина — устаревший флаг
`need_translate`: он скрывает запись на всех языках, кроме языка по умолчанию
(`core_is_awaiting_translation_hidden()`), и стоит на 5 551 уже переведённом
юните. Снимает его `flag-untranslated --remove-stale`, но та команда смотрит
**только русские** записи, так что de, hi, zh и ar пока не чинятся ничем.

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

## Пересчитать count_units

```bash
wp tools recount-units --language=en --allow-root
```