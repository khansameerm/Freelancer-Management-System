<?php
session_start();
ini_set('display_errors', 1);

interface UserActions {
    public function login(array $postData): int;
    public function logout(): void;
}

class Action implements UserActions {
    private $db;

    public function __construct() {
        ob_start();
        include 'db_connect.php';
        $this->db = $conn;
    }

    public function __destruct() {
        $this->db->close();
        ob_end_flush();
    }

    // Generic login method for both users and students
    private function loginUser(string $table, string $userField, string $userValue, string $passwordField, string $passwordValue): int {
        $qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM $table WHERE $userField = '$userValue' AND $passwordField = '" . md5($passwordValue) . "'");
        
        if ($qry->num_rows > 0) {
            foreach ($qry->fetch_array() as $key => $value) {
                if ($key !== 'password' && !is_numeric($key)) {
                    $_SESSION['login_' . $key] = $value;
                }
            }
            return 1;
        }
        return 2;
    }

    public function login(array $postData): int {
        extract($postData);
        return $this->loginUser('users', 'email', $email, 'password', $password);
    }

    public function login2(array $postData): int {
        extract($postData);
        return $this->loginUser('students', 'student_code', $student_code, 'password', $password);
    }

    public function logout(): void {
        session_destroy();
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        header("location:login.php");
    }

    // Generic save user method for signup and update_user
    private function saveUserData(string $table, array $postData, string $checkField, string $idField, int $id = null): int {
        $data = "";
        foreach ($postData as $k => $v) {
            if (!in_array($k, array('id', 'cpass', 'password')) && !is_numeric($k)) {
                if ($k === 'password') {
                    if (empty($v)) {
                        continue;
                    }
                    $v = md5($v);
                }
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }

        $check = $this->db->query("SELECT * FROM $table WHERE $checkField ='' " . (!empty($id) ? " AND $idField != {$id} " : ''))->num_rows;
        if ($check > 0) {
            return 2;
        }

        if (empty($id)) {
            $save = $this->db->query("INSERT INTO $table SET $data");
        } else {
            $save = $this->db->query("UPDATE $table SET $data WHERE $idField = $id");
        }

        return $save ? 1 : 0;
    }

    public function signup(array $postData): int {
        return $this->saveUserData('users', $postData, 'email', 'id');
    }

    public function update_user(array $postData): int {
        extract($postData);
        return $this->saveUserData('users', $postData, 'email', 'id', $id);
    }

    public function save_project(array $postData): int {
        return $this->saveGeneric('project_list', $postData, 'id');
    }

    public function save_task(array $postData): int {
        return $this->saveGeneric('task_list', $postData, 'id');
    }

    public function delete_user(int $id): int {
        return $this->deleteById('users', $id);
    }

    // Generic save method for project, task, etc.
    private function saveGeneric(string $table, array $postData, string $idField): int {
        $data = "";
        foreach ($postData as $k => $v) {
            if (!in_array($k, array('id')) && !is_numeric($k)) {
                if ($k === 'description') {
                    $v = htmlentities(str_replace("'", "&#x2019;", $v));
                }
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }

        if (empty($postData['id'])) {
            $save = $this->db->query("INSERT INTO $table SET $data");
        } else {
            $save = $this->db->query("UPDATE $table SET $data WHERE $idField = " . $postData['id']);
        }

        return $save ? 1 : 0;
    }

    // Generic delete method for users, projects, etc.
    private function deleteById(string $table, int $id): int {
        $delete = $this->db->query("DELETE FROM $table WHERE id = $id");
        return $delete ? 1 : 0;
    }

    public function delete_project(int $id): int {
        return $this->deleteById('project_list', $id);
    }

    public function delete_task(int $id): int {
        return $this->deleteById('task_list', $id);
    }
}
?>



Generics Simulation: I've created generic methods (saveGeneric, deleteById) to handle saving and deleting records across different tables. This reduces code repetition for operations that are similar but apply to different tables.
Type Hinting: Functions now use type hinting for better clarity and error checking.
Interfaces: Introduced an interface UserActions for user-specific actions like login and logout, improving code structure and modularity.