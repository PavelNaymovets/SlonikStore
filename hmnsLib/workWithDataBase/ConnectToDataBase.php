<?php
    /**
     * Класс осуществляет подключение к указанной базе данных(далее БД) в СУБД MySQL.
     * 
     * Параметры: $servername - адрес сервера где расположена БД;
     *            $username - имя пользователя;
     *            $password - пароль для входа в БД;
     *            $dbname - имя БД;
     *            $connection - соединение с БД.
    */
    class ConnectToDataBase {

        private $servername;
        private $username;
        private $password;
        private $dbname;
        private $connection;

        public function __construct(string $servername, string $username, string $password, string $dbname) {
            $this->servername = $servername;
            $this->username = $username;
            $this->password = $password;
            $this->dbname = $dbname;
        }

        /** 
         * ОТКРЫВАЕТ СОЕДИНЕНИЕ С БД:
         * 
         * Возвращает "Connection failed: " и причину неудачного подключения, если подключение не выполнено.
         */
        public function openConnection() {
            $mysql = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
            if($mysql->connect_error) {
                echo "Connection failed: ".$mysql->connect_error;
            }
            $this->connection = $mysql;
        }

        /* ВОЗВРАЩАЕТ СОЕДИНЕНИЕ С БД */
        public function getConnection() {
            return $this->connection;
        }
        
        /* ЗАКРЫВАЕТ СОЕДИНЕНИЕ С БД */
        public function closeConnection() {
            $this->connection->close();
        }

    }
?>