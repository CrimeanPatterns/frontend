# Frontend development

#### [Важные замечания](#important-notes)
#### [БЭМ](#bem)
#### [Файловая Структура](#filestructure)
#### [Миграция старых ассетов](#migration)
#### [Entry Point](#entrypoint)
#### [Регистрация точки входа](#register-entrypoint)
#### [Как передать данные из твига в js?](#data-attr)
#### [Основные сервисы](#common-services)
#### [Отключение старого js на новых страницах](#js-switcher)
#### [Компиляция ассетов](#compilation)
#### [Тестирование](#testing)
#### [Самые ходовые плюшки использования ES6 и выше](#es6-features)
#### [Lodash](#lodash)
#### [React](#react)
#### [Еще о миграции старого кода](#more-about-migration)
#### [Как добавлять ассеты для кастомных страниц менеджерки](#manager-custom-page)

# <a id='important-notes'></a>Важные замечания

> Обновлено: 2023-05-26

* Все новые ассеты должны быть написаны в виде блоков БЭМ;
* Нейминг директорий и файлов должен быть в стиле **kebab-case** (от нейминга блоков, элементов и модификаторов). Это относится к стилевым файлам, картинкам, шрифтам и т.д.
Исключение составляют разве что React компоненты, которые должны быть в стиле **PascalCase**;
* Пишем js код только в TypeScript;
* Образец написания стилей - блок страницы `page-landing`


# <a id='bem'></a>БЭМ

[БЭМ](https://ru.bem.info/methodology/quick-start/) (Блок, Элемент, Модификатор) — компонентный подход к веб-разработке.
В его основе лежит принцип разделения интерфейса на независимые блоки. Он позволяет легко и быстро разрабатывать интерфейсы 
любой сложности и повторно использовать существующий код, избегая «Copy-Paste».

### Блок

* Функционально независимый компонент страницы, который может быть повторно использован. Это ключевой момент. Если не знаете что выбрать элемент или блок,
то стоит задать вопрос можно ли его переиспользовать в другом контейнере, на другой странице?
  Ощущается ли он самобытным?
* В HTML представлен атрибутом `class`. Название класса равно названию директории, в которой располагаются ассеты.
  Нейминг описан [здесь](#filestructure). Название класса должно быть простым и коротким. Название не должно отвечать на вопрос `Как выглядит?`.
* Блок не должен влиять на свое окружение, т. е. блоку не следует задавать внешнюю геометрию (в виде отступов, границ, влияющих на размеры) и позиционирование.
* В CSS никаких селекторов по тегам и id! Только по классам.
* Блоки можно вкладывать друг в друга. Допустима любая вложенность блоков.
* Если фрагмент кода может использоваться повторно и не зависит от реализации других компонентов страницы, то создавай `блок`!

### Элемент

* Составная часть блока, которая не может использоваться в отрыве от него.
* Название элемента характеризует смысл («что это?» — «пункт»: item, «текст»: text), а не состояние («какой, как выглядит?» — «красный»: red, «большой»: big).
* Структура полного имени элемента соответствует схеме: имя-блока__имя-элемента. Имя элемента отделяется от имени блока двумя подчеркиваниями (__).
* Элементы можно вкладывать друг в друга. Допустима любая вложенность элементов.
* Элемент — всегда часть блока, а не другого элемента. Это означает, что в названии элементов нельзя прописывать иерархию вида block__elem1__elem2, даже если один элемент вложен в другой!
* Если фрагмент кода не может использоваться самостоятельно, без родительской сущности (блока), то создавай `элемент`!

### Модификатор

* Cущность, определяющая внешний вид, состояние или поведение блока либо элемента.
* Название модификатора характеризует внешний вид («какой размер?», «какая тема?» и т. п. — «размер»: size_s, «тема»: theme_islands),
  состояние («чем отличается от прочих?» — «отключен»: disabled, «фокусированный»: focused) и поведение («как ведет себя?», 
«как взаимодействует с пользователем?» — «направление»: directions_left-top). Имя модификатора отделяется от имени блока или элемента двойным дефисом (--).
* Модификаторы есть у блоков и элементов.
* Есть `булевые` модификаторы и `ключ-значение`. Структура полного имени `булевого модификатора` соответствует схеме: `имя-блока--имя-модификатора` или `имя-блока__имя-элемента--имя-модификатора`.
  Структура полного имени `ключ-значение модификатора` соответствует схеме: `имя-блока--имя-модификатора_значение-модификатора` или `имя-блока__имя-элемента--имя-модификатора_значение-модификатора`.
* Модификатор нельзя использовать самостоятельно! Например, блок `button` характеризуется классом `button` и для добавления модификатора
  `size_large` нужно добавить второй класс `button--size_large`. В итоге получится нечто подобное:
  ```html
  <button class="button button--size_large button--color_primary">Нажми меня</button>
  ```
  с модификаторами блоков ситуация аналогична.

### Недостатки БЭМ

1) Длинные названия классов. Придется прописывать во всех тегах классы. Как бы не хотелось в CSS селекторах прописать что то вида
   `.header-landing__logo div {}` или `.some-table tr td {}`. Все это полумеры. Начало новых бесконечных вложенностей стилей только в рамках отдельных блоков.
   Такое делать нельзя. Никаких тегов, id в селекторах! Исключения есть, о них далее.
2) Придется заморачиваться с названиями блоков, элементов. Прикидывать возможности переиспользования. Имена должны говорить сами за себя.

### Достоинства БЭМ

1) Простая понятная структура стилей. Удобство в правке (добавление, изменение и особенно удаление) **Такого безобразия быть не должно**:
   ```scss
   .search-content{
       margin: 15px 0 10px 0;
       &.has-border{
           border-top: 2px solid;
       }
       a,
       .row-blk{
           &:first-child{
               border-top: 1px solid transparent !important;
           }
       }
   }
   ```
   а вот как должно быть:
   ```scss
   // Блок
    .block {
        background-color: #fff;
        
        // Модификаторы блока
        &--size_small {
          font-size: 12px;
        }
        
        &--size_medium {
          font-size: 16px;
        }
        
        &--size_large {
          font-size: 24px;
        }
        
        // Элементы блока
        &__element {
          color: #333;
        
          // Модификаторы элемента
          &--state_active {
            color: #f00;
          }
        }
    }
   ```
2) Легко найти элемент по всему проекту через поиск. Названия классов из-за своей длины уникальны.
3) Проще понять что те или иные стили делают и в какой части проекта.

### Не все так просто

На практике BEM не всегда просто применим. И вот [статья](https://nicothin.pro/idiomatic-pre-CSS/), которая помогает ответить на бОльшую часть возникших вопросов.
Вот наиболее важные моменты:

> #### Один БЭМ-блок = один файл.
> В файловой системе при работе с CSS-препроцессорами каждый БЭМ-блок должен быть описан в своём отдельном файле.

Очень важный момент. Несмотря на то, что в методологии BEM описываются файловые структуры nested, flat, flex, все они неудобны в поддержке.
Особенно когда нужно вынести элемент в отдельный блок, переименовать и т.д. В общем при рефакторинге. Лучше стили оформлять
одним scss файлом. Но если на странице подключается старый файл стилей в LESS, тогда нужно создать отдельный LESS файл внутри блока, который подключит старые стили.

> Файл со стилизацией БЭМ-блока должен называться так же, как сам блок.

При этом не важно какую вложенность директорий вы используете. Если подключаете старые стили, то название LESS файла будет так же равно имени блока.

> #### Вложения селекторов
> 1) Чем меньше уровней вложенности, тем лучше.
> 2) Не допускайте более 3-х уровней вложенности (псевдоэлемены, псевдоселекторы и медиа-условия не считаются увеличивающими вложенность).
> 3) Осторожно используйте жесткое наследование.
> 4) Всегда оставляйте пустую строку перед вложенным селектором или @media.
> 5) Всегда делайте дополнительный отступ для вложений.

```scss
@use '../../scss/functions' as f;
@use '../../scss/mixins' as m;
@use '../../scss/vars' as v;

.button {
  display: inline-flex;
  // пустая строка для улучшения читаемости
  @include m.theme(light) { // миксин, который определяет цвета для светлой темы
    background: v.$color-science-blue; // цвет вынесен в переменную
    color: v.$color-white;
    // пустая строка для улучшения читаемости
    &:hover { // не увеличивает уровень вложенности
      background: v.$color-azure-radiance;
    }
    
    &:active { // не увеличивает уровень вложенности
      background: v.$color-endeavour;
    }
  }
  // модификатор
  &--type_platform {
    border-radius: 0.5rem;

    @include m.theme(light) {
      background: v.$color-pickled-bluewood;

      &:hover {
        background: v.$color-fiord;
      }
      &:active {
        background: v.$color-mirage;
      }
    }
    // первый уровень вложенности
    img {
      width: 8.25rem;
      height: auto;
    }
  }
}
```

> #### @media
> 1) Вкладывайте `@media` в селекторы, а не наоборот.
> 2) Не вкладывайте `@media` друг в друга.
> 3) Предпочтите путь `mobile-first`, избегайте указания @media-условия max-width в пользу min-width.
> 4) Пишите `@media` рядом, не пишите селекторы между ними.

```scss
.promo {
  display: block;

  // Хорошо: условие очевидно, md - это сокращение от medium, все переменные можно увидеть в /assets/bem/scss/_vars.scss
  @include m.media('>=md') {
    display: none;
  }
  
  // Плохо: условие целиком вынесено в переменную (неочевидность)
  @media ($mobile-width) {
    display: block;
  }
}
```

> #### Амперсанд
> 1) Используйте амперсанд **только** перед:
> * разделителем БЭМ-элемента,
> * разделителем БЭМ-модификатора,
> * псевдоэлементом или псевдоселектором.
> 2) **Никогда** не используйте амперсанд в местах разделения словосочетаний имён блоков, элементов или модификаторов (см. пример).
> 3) **Никогда** не повторяйте написанный с амперсандом селектор внутри одного контекста.

```scss
.promo {

  // Правильно: амперсанд перед псевдоклассом
  &:hover { ... }

  // Правильно: амперсанд перед разделителем элемента
  &__item {

    // НЕПРАВИЛЬНО: амперсанд в месте разделения словосочетания в названии элемента
    &-link { ... }
  }

  // НЕПРАВИЛЬНО: амперсанд в месте разделения словосочетания в названии блока
  &-shover { ... }

  // Правильно: модификаторы нужно писать под элементами
  &--large { ... }

}
```

> #### Очередность написания в контексте селектора
> В контексте селектора используйте следующую очередность:
>
> 1) Стилевые правила для этого селектора.
> 2) @media этого контекста.
> 3) Псевдоселекторы и псевдоэлементы.
> 4) Вложенные сторонние селекторы.
> 5) БЭМ-элементы.
> 6) БЭМ-модификаторы.

```scss
.page-header {
  position: relative;
  display: block;

  @media (min-width: $screen-lg) { ... }

  &:before { ... }

  // Этот блок простилизован в другом файле, тут только каскадная модификация
  .fp-tableCell { ... }

  &__item {
    display: block;

    &:before { ... }

    @media (min-width: $screen-md) { ... }
  }

  &--large {

    .page-header__item { ... }

    @media (min-width: $screen-md) { ... }
  }

}
```

> Выносите в переменные цвета

Нет смысла именовать переменные по месту их использования (прим, `$button-bg`, `$font-color`). В этом случае будет непонятно какие цвета выносить в переменные,
а какие - нет. К тому же переменных станет много и они будут разбросаны по всему проекту.

Не особо помогают primary и secondary colors, так как непонятно где их применять.

Лучшее название переменной цвета - это название цвета. Например, `$color-endeavour` или `$color-pickled-bluewood`.

Допустим, ты верстаешь страницу и в каком то блоке у тебя есть цвет `#0168CA`. Открываешь [страницу](https://chir.ag/projects/name-that-color/)
и вводишь туда цвет. Находит `Science Blue`. Вот и название переменной - `$color-science-blue`.
Открываешь файл с переменными `/assets/bem/scss/_vars.scss` и ищешь переменную там. Если ее нет, то добавляешь.
Если же переменная уже есть, но код цвета отличается, то нужно спросить дизайнера, какой цвет правильный. Либо обновить текущую переменную, либо использовать ее без обновления.
Если же цвет с полупрозрачностью, то в поиске названия цвета не учитываем альфа-канал. И добавляем к названию переменной модификатор вида `$color-science-blue--10: rgba(1, 104, 202, 0.1)`

# <a id='filestructure'></a>Файловая Структура

С 2023 используем методологию [БЭМ](https://ru.bem.info/methodology/quick-start/) для организации файловой структуры.
Новая структура по БЭМ описывается [тут](https://ru.bem.info/methodology/filestructure/).

Основная идея в отказе от группировки ассетов по типу (ts, less, images, fonts) **в пользу группировки
по смыслу**. Но есть оговорки, о которых ниже.

> **/assets/bem** - местоположение ассетов

Внутри директории располагаются директория для блоков (страницы и глобальные блоки), директория для scss миксинов, функций, переменных, шрифтов,
переводы и TypeScript глобальные библиотеки.

> **/assets/bem/block** - директория для bem блоков

Содержатся глобальные блоки, которые используются на нескольких страницах, так и сами страницы-блоки (entry point'ы).

> **/assets/bem/scss** - директория для базовых шрифтов, переменных, миксинов, функций

Переменные включают css breakpoints (xsm, sm, md, lg, xl, xxl, xxxl, xxxxl), базовый размер шрифта `$font-size-base`, относительно которого
задаются все размеры в css, отступы в HTML (в rem). Наименование цветовых переменных описано выше.

Так же в этой директории присутствует reset.scss, который нужно импортировать на каждой странице.

#### Базовые scss миксины

`theme` - миксин для темизации блока. Применяется к блоку, элементу и модификатору. В зависимости от контекста применения меняется селектор.

Для правильного применения стилей, следует вначале указывать light тему, а затем dark.

```scss
@use '../../scss/functions' as f;
@use '../../scss/mixins' as m;
@use '../../scss/vars' as v;

.button {
  display: inline-flex;

  @include m.theme(light) {
    ...

    &:hover {
      ...
    }
    &:active {
      ...
    }
  }

  @include m.theme(dark) {
    ...
  }

  &--type_login {
    @include m.theme(light) {
      ...
    }
    @include m.theme(dark) {
      ...
    }
  }
}
```

Для показа картинок в зависимости от темы: 
1) Следует добавить в twig шаблоне классы для картинок:
```twig
<div class="{{ bem('logo', null, ['small']) }}">
    <picture class="{{ bem('logo', 'light') }}">
        <img {{ image_src('logo/logo.svg') }} width="178" height="21" alt="logo">
    </picture>
    <picture class="{{ bem('logo', 'dark') }}">
        <img {{ image_src('logo/logo--dark.svg') }} width="178" height="21" alt="logo">
    </picture>
</div>
```
2) Дальше в scss указать, что и когда показывать с помощью миксина theme:
```scss
.logo {
    @include m.theme(light) {
        & .logo__dark {
            display: none;
        }

        & .logo__light {
            display: block;
        }
    }
    @include m.theme(dark) {
        & .logo__dark {
            display: block;
        }

        & .logo__light {
            display: none;
        }
    }
}
```

`media` - миксин для медиа запросов. О его возможностях можно почитать [здесь](https://eduardoboucas.github.io/include-media/).
Breakpoints для медиа запросов описаны в файле `/assets/bem/scss/vars.scss`

```scss
.button {
  @include m.media('>=md') {

  }
}
```

#### Media

Помимо breakpoints есть expressions, которые можно использовать в медиа запросах. Их можно посмотреть в файле `/assets/bem/scss/_vars.scss`.
Вот некоторые из них:

`touchscreen` - медиа запрос для тачскринов любых размеров экрана.

`desktop` - медиа запрос для десктопов (указатель мышь вне зависимости от размера экрана или же ширина экрана больше 1024px даже при наличии тачскрина)

> При верстке придерживаемся принципа Mobile First. То есть сначала верстаем для мобильных устройств, а потом для десктопов.
> Реальный образец написания медиа запросов можно посмотреть в блоке page-landing.

> **/assets/bem/translations** - директория для переводов. Подключаются в твиге как отдельные entry point'ы: `encore_entry_script_tags('trans/' ~ app.request.locale)`

> **/assets/bem/ts** - директория для ts библиотек.

В этой директории находятся библиотеки, которые используются на нескольких страницах. Например, переводчик, раутинг, bem helper для создания классов,
переменные окружения, форматирование дат, чисел, валют.

Так же здесь можно определить declarations при подключении старых и сторонних js библиотек.

`starter.ts` так же нужно подключать на всех страницах. Как и reset.scss.

> **/assets/bem/block/page/{page}** - директория для блоков-страниц

По сути это entry point'ы для страниц, которые подключаются в твиге. Каждый такой entry point подключает внутри себя другие блоки с их ts, scss, картинками.
Так же блок-страница является блоком потому, что может содержать в себе уникальные BEM-элементы, которые очевидно не будут больше нигде
переиспользоваться. В остальном это обычный BEM-блок, только префиксом **page-**.
Блоки-страницы могут быть структурированы (вложенная структура директорий). В таком случае, названием блока страницы
является путь от директории page до директории блока с разделителями "-". 

Например:

**/assets/bem/block/page/user/profile** - bem директория блока страницы **page-user-profile**
**/assets/bem/block/page/timeline** - bem директория блока страницы **page-timeline**
**/assets/bem/block/page/landing** - bem директория блока страницы **page-landing**

В твиге на body нужно повесить класс bem:

```twig
{% block body_class %}
    {{ parent() }} {{ bem('page-landing') }}
{% endblock %}
```

#### Полезные scss функции

`px2rem` - функция для перевода пикселей в ремы. Размер базового шрифта берется дефолтный 16px.
Использовать везде, где указываются размеры шрифтов, отступы, размеры блоков.

```scss
.button {
  font-size: px2rem(16px);
  padding: px2rem(16px) px2rem(20px);
}
```

`fluid` - очень полезная функция для создания fluid блоков (масштабирование в зависимости от ширины/высоты экрана).
Принимает стартовое значение (px/rem) и конечное значение, стартовую ширину (высоту), конечную ширину и единицы измерения.
Стартовая и конечная ширина/высота могут выражаться в виде breakpoints (xsm, sm, md, lg, xl, xxl, xxxl, xxxxl), px, rem.

```scss
.button {
  display: inline-flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  font-family: 'Inter', sans-serif;
  font-weight: 700;
  font-size: f.fluid(15px, 18px, sm, lg); // размер шрифта будет плавно меняться в диапазоне от sm breakpoint до lg. 
                                          // Если ширина экрана более lg, то размер будет 18px, если меньше sm, то 15px.
  border-radius: f.fluid(5px, 6px, sm, lg); // плавное изменение радиуса от 5px до 6px
  height: f.fluid(50px, 60px, sm, lg); // плавное изменение высоты от 50px до 60px
  min-width: f.fluid(148px, 177px, sm, lg); // плавное изменение минимальной ширины от 148px до 177px
  padding: f.fluid(17px, 21px, sm, lg) f.fluid(43px, 52px, sm, lg); // плавное изменение отступов
}
```

#### Twig хэлперы

`bem` - хэлпер для создания классов bem блоков. Принимает название блока, элемент и модификаторы. Модификаторы являются массивом.

```twig
<header class="{{ bem('page-landing', 'header') }}">
    <div class="{{ bem('logo') }} {{ bem('page-landing', 'logo') }}">
        <a href="{{ path('aw_home_v2') }}">
            <img {{ image_src('logo/logo.svg') }} alt="logo">
        </a>
    </div>
    <div class="{{ bem('page-landing', 'header-buttons') }}">
        <a href="{{ path('aw_login_v2') }}" class="{{ bem('button', null, ['type_login']) }}">{{ 'log-in.btn'|trans|desc('Log In') }}</a>
        <a href="{{ path('aw_register_v2') }}" class="{{ bem('button') }}">{{ 'sign-up.btn'|trans|desc('Sign Up') }}</a>
    </div>
</header>
<main class="{{ bem('page-landing', 'main') }}">
    <section class="{{ bem('page-landing', 'section-description') }}">
        ...
    </section>
</main>
```

Этот хэлпер возвращает один или несколько классов в зависимости от переданных параметров. А так же он добавляет классы в случае
форсирования светлой и темной тем оформления. По этому не пытайтесь прописывать классы BEM вручную. **Только через хэлпер!**
Аналогичный хэлпер есть и в ts: `/assets/bem/ts/service/bem.ts`

`image_src` - хэлпер для создания src и srcset для картинок. Если добавляете тэг <img> ан страницу, тогда не нужно вручную прописывать
эти аттрибуты. Он значительно упрощает код:

```twig
<img {{ image_src('my-block/image.jpg') }}> --> <img src="a/blocks/my-block/image.jpg">
<img {{ image_src('my-block/image@2x.jpg') }}> --> <img src="a/blocks/my-block/image@2x.jpg">
<img {{ image_src('my-block/image@{2}x.jpg') }}> --> <img src="a/blocks/my-block/image@2x.jpg" srcset="a/blocks/my-block/image@2x.jpg 2x">
<img {{ image_src('my-block/image@{1-4}x.jpg') }}> --> <img src="a/blocks/my-block/image@1x.jpg" srcset="a/blocks/my-block/image@1x.jpg 1x, a/blocks/my-block/image@2x.jpg 2x, a/blocks/my-block/image@3x.jpg 3x, a/blocks/my-block/image@4x.jpg 4x">
<img {{ image_src('my-block/image@{4-1}x.jpg') }}> --> <img src="a/blocks/my-block/image@4x.jpg" srcset="a/blocks/my-block/image@1x.jpg 1x, a/blocks/my-block/image@2x.jpg 2x, a/blocks/my-block/image@3x.jpg 3x, a/blocks/my-block/image@4x.jpg 4x">
<img {{ image_src('my-block/image@{2,1-4}x.jpg') }}> --> <img src="a/blocks/my-block/image@2x.jpg" srcset="a/blocks/my-block/image@1x.jpg 1x, a/blocks/my-block/image@2x.jpg 2x, a/blocks/my-block/image@3x.jpg 3x, a/blocks/my-block/image@4x.jpg 4x">
<img {{ image_src('my-block/img/test@{2-3}x.png') }}> --> <img src="a/blocks/my-block/img/test@2x.png" srcset="a/blocks/my-block/img/test@2x.png 2x, a/blocks/my-block/img/test@3x.png 3x">
<img {{ image_src('path/path2/test@{200}w.png') }}> --> <img src="a/blocks/path/path2/test@200w.png" srcset="a/blocks/path/path2/test@200w.png 200w">
<img {{ image_src('image@{200,460, 600}w.webp') }}> --> <img src="a/blocks/image@200w.webp" srcset="a/blocks/image@200w.webp 200w, a/blocks/image@460w.webp 460w, a/blocks/image@600w.webp 600w">
```

> Все картинки должны в своем названии иметь суффикс @\d+x или @\d+w. За исключением векторных .svg.

Если собираетесь добавлять изображения на страницу, то делайте как минимум в 2 разрешениях. Одно для ретины. my-img@1x.png и my-img@2x.png.
Внутри блока можете в папку /images закинуть или же вообще обойтись без директорий. Но в любом случае, картинки должны быть внутри блока.

`is_ios` и `is_android` - хэлперы для проверки на какой платформе открыта страница.

```twig
{% if is_ios() %}
    ...
{% elseif is_android() %}
    ...
{% endif %}
```

> **/assets/bem/block/{block-name}** - пример расположения глобального блока.

Глобальные BEM-блоки помимо страниц так же можно структурировать по директориям. Но нужно помнить, что обычные блоки так и страничные
находятся **в одном неймспейсе** и название блока должно быть уникальным. С увеличением вложенности
следить за уникальностью становится сложнее.


> **Как добавить новую страницу? С чего начать?**
>
> Для начала нужно создать директорию внутри `/assets/bem/block/page/`. Например, landing page - **/assets/bem/block/page/landing**.
> Далее создать внутри index.ts, в котором подключаешь стилевой файл page-landing.scss. Его название должно совпадать с названием
> BEM-блока. Если страница не целиком новая и нужно подключить старые стили less, то нужно создать page-landing.less и внутри импортировать необходимые
> стили. После этого можно в твиге подключить через `encore_entry_link_tags('page-landing')` и `encore_entry_script_tags('page-landing')`
> Еще не забыть подключить там же `encore_entry_script_tags('trans/' ~ app.request.locale)` для переводов. Больше ничего в webpack
> прописывать не нужно. Он сам находит entry point'ы внутри директории page/
> 
> Для глобальных блоков нейминг точно такой же касаемо стилей.

> **Можно ли группировать блоки и располагать так `/assets/bem/block/{group_name}{block_name}/`?**
>
> Возможно, но лучше избегать этого, т.к. все названия блоков должны быть уникальны и своим названием раскрывать суть.

> **Есть код в директориях /assets/js, /assets/entry-point, /assets/less. Туда можно что-то писать?**
>
> Они deprecated и в будущем будут удалены.

> **SCSS? Разве не используем LESS?**
>
> Мигрируем на SASS. Он превосходит LESS по многим параметрам.

> **Куда добавлять либы?**
>
> `/assets/bem/ts/service/`

> **Куда добавлять миксины, шрифты, scss функции?**
>
> `/assets/bem/scss/`

> **Как организовать файловую структуру внутри блока? Nested, flat или flex?**
>
> Как показала практика с множеством файлов сложнее работать в плане рефакторинга, подключений. Захотите вынесли элемент в отдельный
> глобальный блок и будете страдать. Nested самый тяжелый вариант.
> Flat не особо проще. Лучше всего `Один БЭМ-блок = один препроцессорный файл`. Он в идеале не будет большим. Если он большой, то
> или блок раздут (нужно разносить), или же это блок-страница. 
> 
> Остальные файлы (картинки, ts) можно организовать как удобно внутри блока.

> **Подходящие ли названия для блоков: button, header, form, input_text, detailPopup?**
>
> Для начала [прочитай соглашение по именованию](https://ru.bem.info/methodology/naming-convention/).
> 
> 1. Имена записываются латиницей в нижнем регистре.
> 2. Для разделения слов в именах используется дефис (-).
> 3. Название блока должно быть уникально для всего сайта и отражать его суть. Иначе говоря, это неймспейс для внутренней структуры элементов и модификаторов.
> 4. Имя элемента отделяется от имени блока двумя подчеркиваниями (`__`).
> 5. Имя модификатора отделяется от имени блока или элемента двумя дефисами (`--`). **Не 1 подчеркиванием (`_`)**. Визуально в scss проще находить модификаторы с 2 дефисами.
> 6. Значение модификатора отделяется от имени модификатора одним подчеркиванием (`_`).
> 7. Значение булевых модификаторов в имени не указывается.
> 
> **button** - подходящее название блока. Он не нуждается в контейнере. Через модификаторы можно дизайнить обычные `<button>`
> так и `<a>` кнопки, с иконкой и без.
> 
> **header** - непонятно речь идет о простом блоке текста (вида `<h1>`) или же блоке с логотипом на главной странице или блоке с логотипом на остальных страницах для авторизованных.
> `header-landing`, `header-page` - вот варианты более подходящие. Для текстового блока - `text-header`. Почему не `header-text`? Лучше в названии идти от общего к конкретному. 
> 
> **form** - подходит. Будут вложенные блоки form-input-text, form-input-textarea, form-button-submit, которые находятся внутри элемента form-row. Почему form-row не блок а элемент? Потому что блок можно переиспользовать, а группу label, input, error вне формы нигде не пристроить.
> 
> **input_text** - неправильно, разделитель слов в именах - дефис. Но даже с дефисом непонятно, что это блок формы.
> 
> **detailPopup** - camelStyle нельзя использовать. Лучшее название тут - `popup-account-details`

> **А имена блоков `button-gray`, `form-input-text-disabled`?**
>
> Некорректно. `gray` и `disabled` являются модификаторами. Кроме того, в названиях блоков и элементов не стоит указывать оформление, местоположение и остальное, что указывает на определенные CSS свойства. Не должен описываться внешний вид.

***
`/web/assets` - старое расположение ассетов, **ничего нового не добавлять сюда**.
***

# <a id='migration'></a>Миграция старых ассетов

Постепенно стоит мигрировать, разбивая страницу на блоки. 
Процесс не быстрый и будет проходить поблочно и постранично.

Есть 3 категории старых скриптов:
1. Написанные программистом для конкретной страницы;
2. Написанные так же нами для многих страниц, библиотеки (прим, `/web/assets/awardwalletnewdesign/js/lib`);
3. Вендоры, подключенные через bower и доступные здесь `/web/assets/common/vendors`.

1 категорию переносим заменяя define/require на export/import.

2 категорию переносим как `1 категорию`, но из старого кода не удаляем (если еще где-то используется). 
В старом коде пишем коммент указывающий путь к новой версии на случай багфиксов.

3 категорию устанавливаем через npm. Bower уже мертв. Как правило, все скрипты из bower 
легко ставятся через `yarn add PACKAGENAME@version`. Но у некоторых зависимостей названия 
могли измениться. Так же стоит ставить последнюю стабильную версию зависимости. После установки js 
зависимости в `/node_modules/` и миграции кода, использующего ее, необходимо удалить ее из bower
(`bower uninstall PACKAGENAME --save`), предварительно убедившись, что она не используется в другом
участке старого кода.

Если времени нет, то мигрировать стоит только 1 категорию. Остальные оставить на своем месте. В
новом коде импортировать их из старого местоположения. Могут возникнуть проблемы с импортом старых
модулей и автоматической инициализацией вида $(function() {}) . Лучше автоматическую инициализацию оборачивать в метод,
который вызывать явно. 

Стили переносить как и js. Выбираем самостоятельный блок, добавляем директорию для него, прописываем классы, стилизуем.
Мигрируем на SCSS. CSS webpack сгенерирует сам. Та же история со шрифтами и картинками.

# <a id='entrypoint'></a>Entry Point

Это точка входа, js файл, который непосредственно подключается на конкретной странице
посредством `encore_entry_script_tags` и `encore_entry_link_tags` функций твига.
Если по-простому, это обособленный кусок js, который мы обычно вставляли в твиг страницы и
оборачивали его в:
```typescript
$(function() { /** код */ });

// или

ReactDOM.render(/** ... */);
```

Это код, который подгружает необходимые для себя зависимости, создает 
подписки на события и т.д. Теперь этот код можно вынести из твига в отдельный файл. 
На одной странице могут быть более 1 точки входа. Но лучше этого избегать. Вторая точка входа как правило это переводы.

Через точку входа webpack находит все зависимости (requirejs, import) переходит по ним, находит у 
зависимостей другие зависимости и все это оформляет в один или несколько бандлов.  

Импорты в точке входа необходимы не только для JS, но и для SCSS, картинок, шрифтов. 
Само собой не обязательно производить импорт непосредственно в файле точки входа. Можно это делать
во вложенных зависимостях.

Как правило, на каждую страницу будет своя точка входа. Нужно в `/assets/bem/block/page` создать bem-блок со стартовой index.ts. 
Для страницы лучше всего выбрать поддиректорию account, timeline, profile и т.д. Например, для страницы 
редактирования LP аккаунта можно выбрать путь `/assets/bem/block/page/account/edit/index.ts`. Внутри него 
импортируем все необходимое из нового и старого кода, стили, картинки.

Более детально про точки входа: https://webpack.js.org/concepts/entry-points/

# <a id='register-entrypoint'></a>Регистрация точки входа

Регистрация точки входа происходит автоматически. Ничего в webpack.config.js не нужно писать.

в твиге
```twig
{% block javascripts %}
    {{ parent() }}
    {{ encore_entry_script_tags('trans/' ~ app.request.locale) }}
    {{ encore_entry_script_tags('page-account-edit') }}
{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    {{ encore_entry_link_tags('page-account-edit') }}
{% endblock %}
```

# <a id='data-attr'></a>Как передать данные из твига в js?

Используйте data атрибуты в html:

```html
<div data-user-profile="{{ app.user ? app.user.profileData|json_encode|e('html_attr') }}">
    <!-- ... -->
</div>
```

По-умолчанию, в `<body>` добавляются data-атрибуты: debug, lang, locale, authorized, aw-plus (имеет ли авторизованный юзер aw plus),
impersonated, business (находится в бизнес интерфейсе), booking (находится на страницах букинга), enable-trans-helper (включен ли
Trans Helper), role-translator (обладание ролю переводчика), theme (текущая тема light или dark).
Если атрибут === false, то он не показывается. true и другие типы, отличные от bool показываются (в т.ч. ассоциативные
массивы).

На конкретных страницах можно добавить свои данные в data-атрибуты:
```html
{% import "@AwardWalletMain/TwigMacros/environment.html.twig" as macros %}

<div {{ macros.dataAttr({
    'myData': {}
}) }}></div>
```

https://symfony.com/doc/current/frontend/encore/server-data.html

# <a id='common-services'></a>Основные сервисы

* `assets/bem/ts/service/axios` - для запросов на сервер. Не пользуемся дефолтным fetch'ем!
* `assets/bem/ts/service/dateTimeDiff.ts` - форматирование временных интервалов. Локаль автоматически берется
из `<body>` аттрибутов
* `assets/bem/ts/service/dialog.js` - сервис показа попапов
* `assets/bem/ts/service/env.ts` - аттрибуты из `<body>`
* `assets/bem/ts/service/formatter.ts` - форматирование чисел, валют, размера файлов
* `assets/bem/ts/service/on-ready.ts` - shortcut для DOMContentLoaded
* `assets/bem/ts/service/router.ts` - роутер, генерирует url по routeName и параметрам
* `assets/bem/ts/service/translator.js` - переводчик

# <a id='js-switcher'></a>Отключение старого js на новых страницах

При создании/переносе страниц на новую структуру ассетов, реакт, webpack, необходимо передавать в твиг 
глобальную переменную. В этом случае все старые `<script>` будут отключены из layout'а. 
```php
$this->get('twig')->addGlobal('webpack', true);
```

# <a id='compilation'></a>Компиляция ассетов

`./node_modules/.bin/encore dev --watch`

Обязательно тестируйте страницу в prod режиме сборки. Особенно это актуально при подключении старого кода с ангуляром, где
минификация кода может сломать зависимости контроллеров, сервисов, директив и т.д.

# <a id='testing'></a>Тестирование

Располагать тесты нужно внутри блоков в директории `__tests__`.

Обратите внимание на тестирование через расширение для jest - react-testing-library. 
Можно писать unit-тесты. Особенно приветствуются тестирование snapshot'ами.

https://testing-library.com/docs/react-testing-library/example-intro

https://jestjs.io/docs/en/getting-started

# <a id='es6-features'></a>Самые ходовые плюшки использования ES6 и выше

* константы
* let/const вместо var
* стрелочные функции
* значения по-умолчанию для параметров функций
* rest параметр и Spread оператор
```typescript
function f (x, y, ...a) {
    return (x + y) * a.length
}
f(1, 2, "hello", true, 7) === 9;
const params = [ "hello", true, 7 ];
f(1, 2, ...params) === 9;
```
* интерполяция строк
```typescript
const customer = { name: "Foo" }
const card = { amount: 7, product: "Bar", unitprice: 42 }
const message = `Hello ${customer.name},
want to buy ${card.amount} ${card.product} for
a total of ${card.amount * card.unitprice} bucks?`
```
* Упрощенное задание методов класса
```typescript
let obj;
obj = {
    foo (a, b) {
        //…
    },
    bar (x, y) {
        //…
    },
    *quux (x, y) {
        //…
    }
}
// вместо
obj = {
    foo: function (a, b) {
        //…
    },
    bar: function (x, y) {
        //…
    },
    //  quux: no equivalent in ES5
    //…
};
```
* Упрощенное задание объекта
```typescript
const x = 0, y = 0;
const obj = { x, y };
```
```typescript
// вместо
var x = 0, y = 0;
const obj = { x: x, y: y };
```
* Деструктурирующее присваивание
```typescript
const list = [ 1, 2, 3 ];
const [ o, , p ] = list;
[ p, o ] = [ o, p ];
const { a, b, c, e: {a: x}, ...rest} = {a: 1, b: 2, c: 3, d: 5, e: {a: 5}};
function f ([ name, val ]) {
    console.log(name, val)
}
function g ({ name: n, val: v }) {
    console.log(n, v)
}
function h ({ name, val }) {
    console.log(name, val)
}
f([ "bar", 42 ]);
g({ name: "foo", val:  7 });
h({ name: "bar", val: 42 });
```
* export/import
```typescript
//  lib/math.js
export function sum (x, y) { return x + y }
export const pi = 3.141593

//  someApp.js
import * as math from "lib/math"
console.log("2π = " + math.sum(math.pi, math.pi))

//  otherApp.js
import { sum, pi } from "lib/math"

//  lib/mathplusplus.js
export * from "lib/math"
export var e = 2.71828182846
export default (x) => Math.exp(x)

//  someApp.js
import exp, { pi, e } from "lib/mathplusplus"
console.log("e^{π} = " + exp(pi))

```
* классы, конструкторы, наследование, статические методы и свойства
```typescript
class Rectangle extends Shape {
    constructor (id, x, y, width, height) {
        super(id, x, y)
        this.width  = width
        this.height = height
    }
}
class Circle extends Shape {
    constructor (id, x, y, radius) {
        super(id, x, y)
        this.radius = radius
    }

    static defaultCircle () {
        return new Circle("default", 0, 0, 100)
    }
}
```
* генераторы
```typescript
function* range (start, end, step) {
    while (start < end) {
        yield start
        start += step
    }
}

for (let i of range(0, 10, 2)) {
    console.log(i) // 0, 2, 4, 6, 8
}
```
* Set, Map
```typescript
let s1 = new Set()
s1.add("hello").add("goodbye").add("hello")
s1.size === 2
s1.has("hello") === true
for (let key of s1.values()) // insertion order
    console.log(key)

let m = new Map()
let s = Symbol()
m.set("hello", 42)
m.set(s, 34)
m.get(s) === 34
m.size === 2
for (let [ key, val ] of m.entries())
    console.log(key + " = " + val)
```
* поиск по строкам вместо indexOf
```typescript
"hello".startsWith("ello", 1) // true
"hello".endsWith("hell", 4)   // true
"hello".includes("ell")       // true
"hello".includes("ell", 1)    // true
"hello".includes("ell", 2)    // false
```
* Промисы. Не нужен jq или angularjs
```typescript
function msgAfterTimeout (msg, who, timeout) {
    return new Promise((resolve, reject) => {
        setTimeout(() => resolve(`${msg} Hello ${who}!`), timeout)
    })
}
msgAfterTimeout("", "Foo", 100).then((msg) =>
    msgAfterTimeout(msg, "Bar", 200)
).then((msg) => {
    console.log(`done after 300ms:${msg}`)
})

function fetchAsync (url, timeout, onData, onError) {
    //…
}
let fetchPromised = (url, timeout) => {
    return new Promise((resolve, reject) => {
        fetchAsync(url, timeout, resolve, reject)
    })
};
Promise.all([
    fetchPromised("http://backend/foo.txt", 500),
    fetchPromised("http://backend/bar.txt", 500),
    fetchPromised("http://backend/baz.txt", 500)
]).then((data) => {
    let [ foo, bar, baz ] = data
    console.log(`success: foo=${foo} bar=${bar} baz=${baz}`)
}, (err) => {
    console.log(`error: ${err}`)
})
```
* замена indexOf для массивов - includes
* Object.values
* Завершающие запятые в параметрах функций
* Конструкция Async/Await
```typescript
async function f() {

  let promise = new Promise((resolve, reject) => {
    setTimeout(() => resolve("готово!"), 1000)
  });

  let result = await promise; // будет ждать, пока промис не выполнится (*)

  alert(result); // "готово!"
}

f();
```
Более детально:
http://es6-features.org/

# <a id='lodash'></a>Lodash

Не нужно придумывать либу на все случаи жизни. Находим нужную функцию тут https://lodash.com/
и импортируем (не импортируем целиком!):
```typescript
import isArray from 'lodash/isArray';
import isMatch from 'lodash/isMatch';

isArray([1, 2, 3]);
// => true

const object = { 'a': 1, 'b': 2 };
 
isMatch(object, { 'b': 2 });
// => true
 
isMatch(object, { 'b': 1 });
// => false
```

# <a id='react'></a>React

Стараемся уходить от jq, angularjs в сторону React. Всю документацию найдете тут: https://ru.reactjs.org/docs/getting-started.html .

Особенно важные моменты:
* Не нужно писать jsx, TypeScript наше все!
* Избегайте наследования компонент react. Пользуйтесь композицией: https://ru.reactjs.org/docs/components-and-props.html#composing-components
* Не пользуйтесь prop-types! Описывайте типы с помощью TypeScript.
https://react-typescript-cheatsheet.netlify.app/docs/basic/getting-started/basic_type_example
* При обработке событий (прим, клик) обработчику нужно привязывать контекст, а еще лучше стрелочную функцию
```tsx
import React, { FunctionComponent } from 'react';

type Props = {};

const Toggle: FunctionComponent<Props> = () => {
    const [isToggleOn, setToggleOn] = React.useState<boolean>(true);

    return (
        <button onClick={() => setToggleOn(!isToggleOn)}>
            {isToggleOn ? 'Включено' : 'Выключено'}
        </button>
    );
};

ReactDOM.render(
  <Toggle />,
  document.getElementById('root')
);
``` 
https://ru.reactjs.org/docs/handling-events.html
* Компоненты высшего порядка. Необходимо ознакомиться https://ru.reactjs.org/docs/higher-order-components.html.
Это поможет создавать "правильные" компоненты, которые можно будет переиспользовать на других страницах.
* React.lazy. Ускорение загрузки мультистраничных приложений. https://ru.reactjs.org/docs/code-splitting.html#route-based-code-splitting
* Не увлекайтесь использованием контекста реакта
* Для тестирования компонент пользуйтесь React.StrictMode https://ru.reactjs.org/docs/strict-mode.html
* Вставка HTML в тег https://ru.reactjs.org/docs/dom-elements.html#dangerouslysetinnerhtml
* Для удобно установки классов html компоненту пользуйтесь `classNames`
```tsx
import React, { FunctionComponent } from 'react';
import classNames from 'classNames';

type Props = {
    label: string
};

const Toggle: FunctionComponent<Props> = ({label}) => {
    const [pressed, setPressed] = React.useState<boolean>(false);
    const [hovered, setHovered] = React.useState<boolean>(false);

    const btnClass = classNames({
        btn: true,
        'btn-pressed': pressed,
        'btn-over': !pressed && hovered
    });

    return <button className={btnClass}>{label}</button>;
};
``` 
* Функциональный подход к написанию имеет более высокий приоритет над классовым.
* Хуки useState и useEffect
https://ru.reactjs.org/docs/hooks-state.html, https://ru.reactjs.org/docs/hooks-effect.html
* Справочник по созданию классовых компонент https://ru.reactjs.org/docs/react-component.html

# <a id='more-about-migration'></a>Еще о миграции старого кода

Старый модуль requirejs можно не переносить в новую директорию а подключить напрямую. Но обязательно тестировать сборку webpack 
в prod режиме. Если ошибка загрузки модуля в прод режиме, то исправлять таким образом: https://docs.angularjs.org/tutorial/step_07#a-note-on-minification

Если есть задача добавить новый функционал на старую страницу с ангуляром, то можно оформить новые элементы верстки страницы
через react. Дла этого не обязательно переносить старый js файл с модулями ангуляра, а добавить в старый код что-то типа:

```js
define(['angular-boot', 'webpack/js/shim/ngReact'], function () {
    angular
        .module('app', ['appConfig', 'react'])
        .directive('helloWorld', ['reactDirective', function(reactDirective) {
            return reactDirective(require('webpack/js/component/HelloWorld').default);
        }]);
});
``` 

алиас `webpack` указывает на новую директорию /assets

```html
<div data-hello-world data-first-name="'John'" data-last-name="'Smith'"></div>
```

```tsx
import React, {FunctionComponent} from 'react';

type Human = { firstName: string, lastName: string };

const HelloWorld: FunctionComponent<Human> = ({firstName, lastName}) => {
    return (
        <div style={{color: 'red'}}>
            {firstName} {lastName}
        </div>
    );
}

export default HelloWorld;
```

# <a id='manager-custom-page'></a>Как добавлять ассеты для кастомных страниц менеджерки

Имеем такой контроллер:
```php
<?php
namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use AwardWallet\Manager\HeaderMenu;

class MyCustomPanelController
{
    /**
     * @Route("/", name="manager_my_custom_panel_index", methods={"GET"}, options={"expose"=false})
     * @Security("is_granted('ROLE_MANAGE_MYCUSTOMPANEL')")
     * @Template("@AwardWalletMain/Manager/MyCustomPanel/index.html.twig")
     */
    public function indexAction(HeaderMenu $headerMenu)
    {
        return [
            'menu' => $headerMenu->getMenu(), // объявить обязательно
            'menuJson' => $headerMenu->getJsonMenu(), // объявить обязательно
            'other' => 'data'
        ];
    }
}
```

Добавляем twig-файл отнаследованный от `@Module/EnhancedAdmin/Template/layout.html.twig` в `bundles/AwardWallet/MainBundle/Resources/views/Manager/MyCustomPanel/index.html.twig`:

```twig
{% extends "@Module/EnhancedAdmin/Template/layout.html.twig" %}
{% set customEntryPoint = 'page-manager/mycustompanel' %}{# объявить обязательно #}
{% set title = "My custom panel" %}{# объявить обязательно #}
{% block content %}
    <div>Data here: {{ other }}</div>
{% endblock %}
```

Ассеты кладем в `assets/bem/block/page/manager/`. Название файлов должно совпадать с `customEntryPoint` (часть после `page-manager`). Например, `assets/bem/block/page/manager/mycustompanel.entry.ts`:

```typescript
enum CacheType {
  Memcached = 'memcached',
  Redis = 'redis',
}

alert(CacheType.Memcached);
```
