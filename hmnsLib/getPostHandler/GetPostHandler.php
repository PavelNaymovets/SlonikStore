<?php
    /**
     * Класс выполняет обработку GET или POST запроса. Складывает параметры запроса в ассоциативный массив и возвращает его.
     * 
     * Параметры: $getData - параметры GET запроса.
     *            $postData - параметры POST запроса.
    */
    class GetPostHandler {
        
        private $requestName;
        private $arr;
        private $getData;
        private $postData;
        
        /** 
         * КОНСТРУКТОР КЛАССА.
         * 
         * Параметр $requestName принмиает любой размер букв(большой/маленький).
         */
        public function __construct(string $requestName, $queryParams) {
            $this->requestName = mb_strtolower($requestName);
            $this->arr = $queryParams;
        }

        /* ВОЗВРАЩАЕТ МАССИВ С ПАРАМЕТРАМИ ИЗ GET ИЛИ POST ЗАПРОСА */
        public function getDataFromQuery() {
            if($this->requestName == 'get') {
                foreach($this->arr as $value) {
                    foreach($_GET as $getKey => $getValue) {
                        if(mb_strtolower($getKey) == $value) {
                            $this->getData[$value] = $getValue;
                        }
                    }
                }

                return $this->getData;

            } else if($this->requestName == 'post') {
                foreach($this->arr as $value) {
                    $this->postData[$value] = $_POST[$value];
                }

                return $this->postData;
                
            }
        }
        
    }
?>