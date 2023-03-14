# SlonikStore

В данном проекте представлена логика работы одного из микросервисов сайта SlonikStore. 

* SlonikStore - сайт, предоставляющий возможность купить коды для пополнения аккаунта в стиме в турецких лирах.
* Микросервис - выдает массив свободных кодов номиналов из базы данных.

## Описание микросервиса
### Парадигма: 

1. ООП

### Структура:

1. hmnsLib - небольшая самописная библиотека. Содержит классы:
* _[GetPostHandler](https://github.com/PavelNaymovets/SlonikStore/blob/master/hmnsLib/getPostHandler/GetPostHandler.php)_;
* _[JsonHandler](https://github.com/PavelNaymovets/SlonikStore/blob/master/hmnsLib/jsonHandler/jsonHandler.php)_;
* _[ConnectToDataBase](https://github.com/PavelNaymovets/SlonikStore/blob/master/hmnsLib/workWithDataBase/ConnectToDataBase.php)_;
* _[QueryToDataBase](https://github.com/PavelNaymovets/SlonikStore/blob/master/hmnsLib/workWithDataBase/QueryToDataBase.php)_;

P.S. Описание назначения класса содержится в файле класса.

2. _[/queryCode.php](https://github.com/PavelNaymovets/SlonikStore/blob/master/queryCode.php)_ - скрипт эндпоинта.

### Логика работы микросервиса:

1. Обработка GET запроса. Получения уникального номера оплаченного счёта.
2. Запрос токена(_[api.digiseller.ru](https://api.digiseller.ru/)_).
3. Запрос данных о покупке(api.digiseller.ru). Токен + уникальный номер оплаченного счета.
4. Извлечение данных из шага 3 о сумме покупки.
5. Проверка данных о покупке. Получал ли пользователь ранее коды из базы данных по текущему номеру оплаченного счета(исключение возможности повторного получения кодов по одному и тому же оплаченному счету)
5. Разложение суммы покупки на доступные большие свободные номиналы. Например: 1 000 = 300 + 300 + 300 + 100.
6. Извлечение не активированных кодов из базы данных для номиналов, полученных из шага 5.
7. Возврат массива кодов из шага 6 на фронт в формате JSON.