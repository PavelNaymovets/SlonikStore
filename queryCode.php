<?php
    /**
     * Скрипт возвращает неактивированный код товара(номинала денежных средств) из базы данных(далее БД).
     * 
     * Endpoint для получения неиспользованного кода номинала из БД:
     * 
     * https://slonikstore.ru/queryCode.php?UNiqueCodE=D7265A95E586428C
     * 
     * Параметры GET запроса: uniquecode - уникальный код товара.
     */

    //=====================================================
    // КОД СКРИПТА
    //=====================================================
    
    /* ПОДКЛЮЧЕНИЕ КЛАССОВ РАБОТЫ С БАЗОЙ ДАННЫХ, GET/POST ЗАПРОСАМИ */   
    require_once 'hmnsLib/workWithDataBase/ConnectToDataBase.php';
    require_once 'hmnsLib/workWithDataBase/QueryToDataBase.php';
    require_once 'hmnsLib/getPostHandler/GetPostHandler.php';
    require_once 'hmnsLib/jsonHandler/jsonHandler.php';
    
    /* ПОЛУЧЕНИЕ ПАРАМЕТРОВ ИЗ GET ЗАПРОСА */
    $params = array('uniquecode');
    $getHandler = new GetPostHandler("GET", $params);
    $getData = $getHandler->getDataFromQuery();
    
    /* ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ */
    $connect = new ConnectToDataBase("localhost","a0755408_slonick","ler20PJL","a0755408_slonick");
    $connect->openConnection();
    $mysql = $connect->getConnection();
    $queryToDataBase = new QueryToDataBase($mysql);
    
    /* ОБРАБОТКА ЗАПРОСА ПОЛЬЗОВАТЕЛЯ */
    $uniqueCode = $getData['uniquecode'];
    $token = getToken();
    $paymentData = getPaymentData($uniqueCode, $token); //получаю данные о покупке в формате json
    $paymentDataJson = json_decode($paymentData); //декодирую их
    $nominal = $paymentDataJson->options[0]->value; //получаю значение номинала и обозначение валюты
    
    $userNominal = explode(" ", $nominal, 2)[0]; //убираю валюту
    $intUserNominal = (int)$userNominal; //привожу string к int
    
    /* ЗАПРОС КОДОВ НОМИНАЛОВ */
    $uid = $uniqueCode; //уникальный номер оплаченного счёта в системе учета Digiseller
    $date = $paymentDataJson->date_pay; //дата и время совершения платежа
    $check = checkUserPurchases($uid, $queryToDataBase);
    if($check !=0) { //пользователь уже совершал покупку в магазине
        $result = $check;
        $availableCodes = getCodes($result); //массив с доступными ключами
    } else { //пользователь не совершал покупку в магазине
        $result = getSeparatedNumberCodes($intUserNominal, $queryToDataBase, $uid, $date); //Разбивка числа на бОльшие номиналы
        $availableCodes = getCodes($result); //массив с доступными ключами
    }
    
    /* ВЫВОД ИНФОРМАЦИИ В ФОРМАТЕ JSON НА СТРАНИЦУ */
    // JsonHandler::echoJSON($result);
    
    /* ЗАКРЫВАЮ СОЕДИНЕНИЕ С БД */
    $connect->closeConnection();
    
    //=====================================================
    // МЕТОДЫ
    //=====================================================
    
    /**
     * ПОЛУЧЕНИЕ ТОКЕНА
     *  
     * Параметры получения токена: seller_id - уникальный id продавца;
     *                             apiKey - уникальный ключ продавца;
     *                             timestamp - время в секундах начиная с 1970 г;
     *                             sign - подпись. Получена конкатенацией apiKey и timestamp.
     * 
     * Функция возвращает: $token - уникальный ключ для запроса к API.
     */
    function getToken() {
        $seller_id = 1122342346;
        $apiKey = '73121A4EFA234244218DC405B89495ED2A';
        $timestamp = time();
        $sign = hash('sha256', $apiKey.$timestamp);
        
        $arrayQuery = array
                (
            	'seller_id' => $seller_id,
            	'timestamp' => $timestamp,
            	'sign' => $sign,
                );
                
        $tokenQuery = postTokenQuery($arrayQuery);
        $tokenJson = json_decode($tokenQuery);
        $token = $tokenJson->token;
        
        return $token;
    }
    
    //-----------------------------------------------------
    // API
    //-----------------------------------------------------
    
    /**
     * ПОИСК И ПРОВЕРКА ПЛАТЕЖА ПО УНИКАЛЬНОМУ КОДУ
     *  
     * Параметры: uniqueCode - уникальный код сделки;
     *            token - уникальный ключ для запроса к API;
     * 
     * Функция возвращает: $result - данные о покупке (дата покупки, стоимость, страна, уникальный код операции и пр.).
     */
    function getPaymentData($uniqueCode, $token) {
            $curl = curl_init("https://api.digiseller.ru/api/purchases/unique-code/{$uniqueCode}?token={$token}");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            curl_close($curl);
    
            return $result;
    }

    /**
     * ЗАПРОС ТОКЕНА ОТ api.digiseller.ru
     *  
     * Параметры: arrayQuery - данные для запроса токена.
     * 
     * Функция возвращает: $result - токен в формате json.
     */
    function postTokenQuery($arrayQuery) {
        $headers = array
            (
            "Content-Type: application/json",
            "Accept: application/json"
            );
        $dataJson = json_encode($arrayQuery);
        
        $curl = curl_init("https://api.digiseller.ru/api/apilogin");
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson); //запрос в json формате
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); //заголовки для запроса в json формате
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        curl_close($curl);
    
        return $result;
    }

    //-----------------------------------------------------
    // БАЗА ДАННЫХ
    //-----------------------------------------------------
    
    /**
     * ВОЗВРАТ НЕАКТИВИРОВАННОГО КОДА НОМИНАЛА
     *  
     * Параметры: userNominal - номинал валюты для запроса неактивированного кода из базы данных.
     * 
     * Функция возвращает: $result - токен в формате json.
     */
    function getNotActiveCodes($userNominal, $queryToDataBase) {
        $result = $queryToDataBase->selectQuery("SELECT unCode FROM uniqueKeys WHERE nominal = $userNominal AND isActivated = 0");
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $notActiveCode = $row['unCode'];
            return [
                    'userNominal' => $userNominal,
                    'notActiveCode' => $notActiveCode,
                    'numAvailableCode' => $result->num_rows
                   ];
        }
    }
    
    /**
     * АКТИВИРОВАНИЕ НЕ ЗАНЯТЫХ КОДОВ НОМИНАЛА В НЕОБХОДИМОМ КОЛИЧЕСТВЕ
     *  
     * Параметры: countNominalCodes - необходимое количество кодов одного номинала (например 10 шт. для номинала 200);
     *            nominal - номинал валюты, код которой нужно вернуть;
     *            uid - уникальный номер пользователя = уникальному коду покупки;
     *            date - дата и время совершения платежа
     * 
     * Функция возвращает: $nominalCodes - свободные коды номиналов из базы данных. После возврата занимает их, чтобы не выдать активированные коды другому пользователю.
     */
    function setCodeActivated($countNominalCodes, $nominal, $queryToDataBase, $uid, $date) {
        $uid = "'{$uid}'";
        $date = "'{$date}'";
        $nominalCodes = []; //массив свободных кодов номинала
        for($i = 0; $i < $countNominalCodes; $i++) {
            $result = getNotActiveCodes($nominal, $queryToDataBase); //нахожу свободны код в базе
            $notActiveCode = $result['notActiveCode'];
            
            $result = $queryToDataBase->updateQuery("UPDATE uniqueKeys SET isActivated = 1, uid = $uid, datePay = $date WHERE nominal = $nominal AND unCode = $notActiveCode"); //занимаю его
            $nominalCodes[] = $notActiveCode; //и добавляю в массив
        }
        
        return $nominalCodes;
    }
    
    /**
     * РАЗЛОЖЕНИЕ ЧИСЛА НА СЛАГАЕМЫЕ С ОРИЕНТИРОВКОЙ НА БЛИЖАЙШЕЕ БОЛЬШЕЕ ДОСТУПНОЕ ЗНАЧЕНИЕ
     *  
     * Параметры: number - число, которое нужно разбить на слагаемые(номиналы);
     *            uid - уникальный номер пользователя = уникальному коду покупки;
     *            date - дата и время совершения платежа
     * 
     * Функция возвращает: запрос выполнен:
     *                     status - состояние запроса(1 - выполнен, 0 - не выполнен),
     *                     order - сумма кодов, которые приобрел пользователь,
     *                     uid - уникальный номер пользователя = уникальному коду покупки, 
     *                     datePay - дата и время совершения платежа,
     *                     separatedNumber - слагаемые числа(номиналы), на которые был разложен order,
     *                     availableCodes - массив доступных кодов соответствующих separatedNumber.
     *                     
     *                     запрос не выполнен:
     *                     'Закончились все доступные номиналы. Необходимо пополнить базу кодами.'
     */
    function getSeparatedNumberCodes($number, $queryToDataBase, $uid, $date) {
        
        $availableNominals = array(300, 250, 200, 100, 50, 20); //Доступные номиналы
        
        $status = 1; //состояние запроса
        $separatedNumber = array(); //Слагаемые числа $number
        $codes = []; //Доступные коды слагаемых числа $number
        
        $rest = $number; //Остаток от деления числа $number
        $unit; //Целое число от деления числа $number
        $buf; //Буферная переменная
        
        foreach($availableNominals as $nominal) {
            
            if($rest >= $nominal) {
                
                $buf = $rest; //значение остатка до деления на номинал, кодов которого не хватило для заполнения
                
                $unit = intdiv($rest, $nominal); //целочисленное деление
                $rest = $rest % $nominal; //остаток от деления
                
                $countAvailableNominalCode = getNotActiveCodes($nominal, $queryToDataBase);
                $availableCode = $countAvailableNominalCode['numAvailableCode']; //количество доступных кодов номинала
                
                if ($availableCode >= $unit) { //кладу все доступные коды, но не бельше чем нужно
                    
                    if($nominal == 20 && $rest % 20 != 0) { //коды закончились. Нечетные числа нацело не разменять четными числами
                        $status = 0;
                        break;
                    }
                    
                    $notActiveNominalCode = setCodeActivated($unit, $nominal, $queryToDataBase, $uid, $date); //получаю все свободные коды и занимаю их, чтобы ими не могли воспользоваться другие пользователи
                    $codes[$nominal] = $notActiveNominalCode; //прикладываю коды к номиналу
                    
                    for ($i = 0; $i < $unit; $i++) {
                        $separatedNumber[] = $nominal;
                    }
                    
                } else { //кладу столько кодов, сколько есть
                    
                    $rest = $buf - ($nominal * $availableCode);
                    $unit = $availableCode;
                    
                    if($nominal == 50 && $rest % 20 != 0) { // если число нечетное и 50 не хватает, то нужно уменьшить число на 50, чтобы разложить четный остаток по 20.
                        $unit -= 1;
                        $rest = $buf - ($nominal * ($availableCode - 1));
                    }
                    
                    if($nominal == 20) { //закончились все доступные коды или не хватает 20, чтобы ими разменять номинал
                        $status = 0;
                        break;
                    }
                    
                    if($unit != 0) { //не нужно складывать в массив номиналы, которых нет
                        $notActiveNominalCode = setCodeActivated($unit, $nominal, $queryToDataBase, $uid, $date); //получаю все свободные коды и занимаю их, чтобы ими не могли воспользоваться другие пользователи
                        $codes[$nominal] = $notActiveNominalCode; //прикладываю коды к номиналу
                        
                        for ($i = 0; $i < $unit; $i++) {
                        $separatedNumber[] = $nominal;
                        }
                    }
                    
                }
                
            }
            
            if ($rest == 0) {
                break;
            }
            
        }
        
        if($status == 1) {
            return [
                'status' => $status,
                'order' => $number,
                'uid' => $uid, 
                'datePay' => $date,
                'separatedNumber' => $separatedNumber,
                'availableCodes' => $codes
               ];
        } else {
            return 'Закончились все доступные номиналы. Необходимо пополнить базу кодами.';
        }
    }
    
    /**
     * ПРОВЕРКА ПОКУПАЛ ЛИ ПОЛЬЗОВАТЕЛЬ ЧТО ТО В МАГАЗИНЕ
     *  
     * Параметры: uid - уникальный номер пользователя = уникальному коду покупки;
     * 
     * Функция возвращает: запрос выполнен:
     *                     order - сумма кодов, которые приобрел пользователь,
     *                     uid - уникальный номер пользователя = уникальному коду покупки, 
     *                     datePay - дата и время совершения платежа,
     *                     separatedNumber - слагаемые числа(номиналы), на которые был разложен order,
     *                     availableCodes - массив доступных кодов соответствующих separatedNumber.
     *                     
     *                     запрос не выполнен:
     *                     '0'
     */
    function checkUserPurchases($uid, $queryToDataBase) {
        $uid = "'{$uid}'"; //уникальный номер пользователя = уникальному коду покупки
        $result = $queryToDataBase->selectQuery("SELECT nominal, unCode, datePay FROM uniqueKeys WHERE uid = $uid");
        if($result->num_rows > 0) {
            
            while($row = $result->fetch_assoc()) {
                $separatedNumber[] = $row['nominal'];
                $codes[] = $row['unCode'];
                $date = $row['datePay'];
            }
            
            $number = array_sum($separatedNumber);
            
            return [
                'order' => $number,
                'uid' => $uid, 
                'datePay' => $date,
                'separatedNumber' => $separatedNumber,
                'availableCodes' => $codes
               ];
        } else {
            return 0;
        }
    }
    
    /**
     * ПВОЗВРАТ МАССИВА С КОДАМИ НОМИНАЛОВ
     * 
     * Метод возвращает только доступные коды, без значения номиналов этих кодов.
     *  
     * Параметры: result - результат работы метода getSeparatedNumberCodes($number, $queryToDataBase, $uid, $date);
     * 
     * Функция возвращает: запрос выполнен:
     *                     str - строка с кодами.
     * 
     *                     запрос не выполнен:
     *                     ''
     */
    function getCodes($result) {
        $arrCode = $result['availableCodes'];
        $status = $result['status'];
        
        $availableCodes = []; //доступные коды
        $str; //строка с кодами
        
        if($status == 1) {
            foreach($arrCode as $value) {
                foreach($value as $code) {
                    $availableCodes[] = $code;
                }
            }
            
            foreach($availableCodes as $value) {
                $str = $str. "," .$value;
            }
            
        } else {
            $availableCodes = $arrCode;
            
            foreach($availableCodes as $value) {
                $str = $str. "," .$value;
            }
        }
        
        return $str;
    }
?>