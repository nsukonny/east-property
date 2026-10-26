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

`overrides.json` — точные замены по msgid, они всегда побеждают ответ DeepL.
Ключ с контекстом пишется как `context\u0004msgid`.

Зачем они: на отраслевых терминах DeepL уверенно ошибается, и ошибка выглядит
правдоподобно. `Property` приходит как 属性 (свойство объекта в коде),
`Secondary` как 中学 (средняя школа), `Villa` как 村庄 (деревня), `Studio` как
研究 (исследование). При этом `Attributes` в ярлыках типов записей — именно
属性, поэтому замена идёт по целой строке, а не по слову.

После сборки обязательно сверить плейсхолдеры: `-%d%%` DeepL однажды вернул как
«百分比», то есть выкинул оба. Проверка — сравнить наборы `%s`, `%d`, `%%` в
msgid и msgstr по всему файлу.

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
OUT=/tmp/i18n-ar && mkdir -p "$OUT" && cp scripts/i18n/overrides.ar.json "$OUT/overrides.json"

EP_OUT="$OUT" EP_TARGET=AR wp eval-file scripts/i18n/translate-pot.php
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

Имя файла здесь `overrides.ar.json`, а не `overrides.json`, потому что
последний занят китайским. У арабского свои ловушки DeepL: `property` в
значении недвижимости приходит как `خصائص` (характеристики) и `ممتلكات`
(имущество), `Secondary` как школьная ступень, `-%d%%` вернулось как
«في المائة» — оба плейсхолдера выброшены. Отдельно вынесено имя бренда:
`East Property` транслитерировалось в `إيست بروبرتي`, а юрлицо
`East Property Brokerage LLC` было переведено целиком. Бренд оставляем
латиницей внутри арабского текста — в ОАЭ это норма.

**Каталог собран машиной и носителем языка не вычитан.** Плейсхолдеры и
термины сверены, грамматика — нет.

Свой `overrides.json` нужен каждому языку: ошибки DeepL у них разные. У хинди
это `Properties → गुणधर्म` (свойства-атрибуты), `Secondary → माध्यमिक`
(школьная ступень), `Studio → अध्ययन` (учёба), `Villa → गाँव` (деревня),
`Unit → इकाई` (единица измерения), `Parent X: → माता-पिता` (буквально
«мать-отец»), `Save → बचाएँ` («спасать»).
