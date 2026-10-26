# Каталог интерфейса на новый язык

Два шага. Первый отправляет `languages/east-property.pot` в DeepL, второй
собирает `.po` из ответа и карты переопределений. Оба повторяемы: перевод
кэшируется в опции `ep_deepl_cache`, так что второй прогон почти бесплатен.

```bash
cd ~/Projects/eastproperty.com/app/public/wp-content/themes/east-property
OUT=/tmp/i18n-zh && mkdir -p "$OUT" && cp scripts/i18n/overrides.json "$OUT/"

EP_OUT="$OUT" EP_TARGET=ZH-HANS wp eval-file scripts/i18n/translate-pot.php
EP_OUT="$OUT" EP_LOCALE=zh_CN wp eval-file scripts/i18n/build-po.php

bash scripts/make-translations.sh
```

## Чем правим ответ DeepL

Две разные вещи, и путать их дорого.

`hints.json` — **подсказка на входе**: msgid подменяется однозначным
английским, а арабский всё равно приходит из API. Так чинится неоднозначность
самого каталога: `Bed` в объявлении значит спальню, `Distress` — категорию
срочной продажи, и DeepL об этом знать неоткуда. Подсказка проверяема —
английский можно прочитать и сверить, в отличие от придуманного руками
перевода.

`overrides.json` — **замена на выходе**, по целому msgid. Здесь остаётся
только то, что переводить нельзя: имя бренда. Ключ с контекстом пишется как
`context\u0004msgid`.

Подсказка, не совпавшая ни с одной строкой `.pot`, молча ничего не делает.
Проверять так:

```bash
wp eval 'require_once ABSPATH."wp-includes/pomo/po.php";
$h=json_decode(file_get_contents("/tmp/i18n-ar/hints.json"),true);
$po=new PO(); $po->import_from_file(get_stylesheet_directory()."/languages/east-property.pot");
$seen=array(); foreach($po->entries as $e){ $l=($e->context?$e->context."\x04":"").$e->singular;
 if(isset($h[$l]))$seen[$l]=1; elseif(isset($h[$e->singular]))$seen[$e->singular]=1; }
foreach($h as $k=>$v){ if($k[0]!=="_" && empty($seen[$k])) echo "мимо каталога: $k\n"; }'
```

## Параметры запроса

`translate-pot.php` шлёт `source_lang`, `model_type` и `context`. Они задаются
через `EP_SOURCE`, `EP_MODEL` и `EP_CONTEXT`; по умолчанию — `EN` и
`quality_optimized`.

Без них запрос уходил с одним `target_lang`, и это стоило каталогу десятков
строк. На автодетекте односложная метка опознаётся как угодно: `Bed` вернулся
как «мир», `Studio` как «исследование», `Handover` как «передача задач».
Старая модель к тому же теряла плейсхолдеры — `-%d%%` приходило как «в
процентах». На `quality_optimized` c `source_lang=EN` по всему каталогу
плейсхолдеры и теги целы.

Опции входят в ключ кэша: прогон с ними не читает ответы, купленные прогоном
без них. Вызов без опций берёт прежний ключ, и оплаченные записи остаются
в деле.

Чего на этом ключе нет:

* **Глоссарий.** Пары `en→ar` API поддерживает, но тарифный лимит на число
  глоссариев уже занят существующим `EastProperty EN-DE`, и второй не
  создаётся — `Too many glossaries`. С глоссарием термины держались бы и
  внутри длинных предложений, а не только в тех строках, где стоит подсказка.
* **Защита бренда в запросе.** `tag_handling=xml` с `ignore_tags` на
  `quality_optimized` отвечает `400`, а с `tag_handling=html` метки не
  соблюдаются: `<x>East Property</x>` всё равно приходит транслитерацией.
  Поэтому имя бренда возвращается латиницей через `overrides.json`.

Ответ модели не детерминирован: одна и та же строка в разных прогонах может
прийти с определённым артиклем и без. На смысл это не влияет, но дословного
совпадения двух прогонов ждать не надо.

Формы множественного числа задаёт `EP_PLURAL`, и скрипт заполняет ровно
столько форм, сколько объявлено в `nplurals`. Китайский — одна форма
(`nplurals=1; plural=0;`), хинди — две (`nplurals=2; plural=(n != 1);`),
вторая берётся из перевода `msgid_plural`.

```bash
EP_OUT="$OUT" EP_TARGET=HI wp eval-file scripts/i18n/translate-pot.php
EP_OUT="$OUT" EP_LOCALE=hi_IN EP_PLURAL="nplurals=2; plural=(n != 1);" \
  wp eval-file scripts/i18n/build-po.php
```

## Арабский

```bash
OUT=/tmp/i18n-ar && mkdir -p "$OUT"
cp scripts/i18n/overrides.ar.json "$OUT/overrides.json"
cp scripts/i18n/hints.ar.json "$OUT/hints.json"

EP_OUT="$OUT" EP_TARGET=AR EP_SOURCE=EN EP_MODEL=quality_optimized \
EP_CONTEXT="User interface of a Dubai real estate website: property listings, apartments, villas, townhouses, developers, payment plans and mortgage calculations." \
  wp eval-file scripts/i18n/translate-pot.php

EP_OUT="$OUT" EP_LOCALE=ar \
  EP_PLURAL="nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);" \
  wp eval-file scripts/i18n/build-po.php

bash scripts/make-translations.sh
```

Шесть форм множественного числа, и единственное лежит **не в нулевом слоте**:
нулевой занят формой для нуля. `build-po.php` вычисляет нужный слот сам,
прогоняя формулу через `Plural_Forms` из ядра. Текстов при этом всего два —
перевод `msgid` и перевод `msgid_plural`, — так что падежи арабского счёта
(2, 3–10, 11+) каталог не различает; где это важно, строку надо править руками.

Запускать `build-po.php` только с переменными окружения в том же вызове: без
`EP_LOCALE` он молча берёт `zh_CN`, а без `EP_OUT` не находит `raw.json` и
перезаписывает чужой каталог пустыми строками.

Имя файлов здесь `*.ar.json`, а не `*.json`, потому что последние заняты
китайским.

Что ловят арабские подсказки: `Distress` как название категории уходило в
эмоцию — `My distress` возвращалось как «что меня тревожит», `+ Add new
distress` как «добавить новую проблему». `Secondary` читалось как ступень
школы, `Townhouse` как «деревенский дом», `Property` отдавалось во
множественном числе. Три написания off-plan в каталоге давали три разных
арабских оборота. `Latitude` и `DLD Transfer` возвращались английскими как
есть.

**Каталог собран машиной и носителем языка не вычитан.** Плейсхолдеры,
теги, формы множественного числа и отраслевые термины сверены, грамматика —
нет.

Свой `overrides.json` нужен каждому языку: ошибки DeepL у них разные. У хинди
это `Properties → गुणधर्म` (свойства-атрибуты), `Secondary → माध्यमिक`
(школьная ступень), `Studio → अध्ययन` (учёба), `Villa → गाँव` (деревня),
`Unit → इकाई` (единица измерения), `Parent X: → माता-पिता` (буквально
«мать-отец»), `Save → बचाएँ` («спасать»). Их стоит перевести в подсказки:
у китайского и хинди прогоны делались на старой модели с автодетектом.
