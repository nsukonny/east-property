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

Свой `overrides.json` нужен каждому языку: ошибки DeepL у них разные. У хинди
это `Properties → गुणधर्म` (свойства-атрибуты), `Secondary → माध्यमिक`
(школьная ступень), `Studio → अध्ययन` (учёба), `Villa → गाँव` (деревня),
`Unit → इकाई` (единица измерения), `Parent X: → माता-पिता` (буквально
«мать-отец»), `Save → बचाएँ` («спасать»).
