<?php
    /**
     * Класс выполняет стандартные(select, insert_into, update, delete) и специальные операции в БД.
     * 
     * Параметры: $connection - соединение с БД.
    */
    class QueryToDataBase {

        private $connection;

        public function __construct(&$connection) {
            $this->connection = $connection;
        }
        
        //=====================================================
        // СТАНДАРТНЫЕ ОПЕРАЦИИ
        //=====================================================

        /** 
         * SELECT запрос в БД:
         * 
         * return $result - запрос выполнен и количество выбранных из базы строк > 0.
         * return 0 - запрос не выполнен.
         */
        public function selectQuery(string $query) {
            $sqlSelect = $query;
            $result = $this->connection->query($sqlSelect);
            if($result->num_rows > 0){
                return $result;
            } else {
                return 0;
            }
        }

        /** 
         * INSERT INTO запрос в БД:
         * 
         * return 1 - запрос выполнен.
         * return 0 - запрос не выполнен.
         */
        public function insertIntoQuery(string $query) {
            $sqlInsertInto = $query;
            $result = $this->connection->query($sqlInsertInto);
            if($result == true){
                return 1;
            } else {
                return 0;
            }
        }

        /** 
         * UPDATE запрос в БД:
         * 
         * return 1 - запрос выполнен.
         * return 0 - запрос не выполнен.
         */
        public function updateQuery(string $query) {
            $sqlUpdate = $query;
            $result = $this->connection->query($sqlUpdate);
            if($result == true){
                return 1;
            } else {
                return 0;
            }
        }

        /** 
         * DELETE запрос в БД: 
         * 
         * return 1 - запрос выполнен.
         * return 0 - запрос не выполнен.
         */
        public function deleteQuery(string $query) {
            $sqlDelete = $query;
            $result = $this->connection->query($sqlDelete);
            if($result == true){
                return 1;
            } else {
                return 0;
            }
        }
         
    }
?>