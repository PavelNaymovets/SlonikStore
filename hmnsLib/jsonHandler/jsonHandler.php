<?php
    /**
     * Класс возвращает переданные ему данные в формате JSON на страницу.
     * 
     * Параметры: $data - данные для преобразования в JSON.
    */
    class JsonHandler {
        public static function echoJSON($data) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            echo $json; //вывод информации в формате json на страницу.
        }
    }
?>