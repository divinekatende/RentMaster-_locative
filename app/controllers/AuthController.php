<?php

session_start();
require_once "app/models/User.php";

class AuthController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function login()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $type = $_POST['type']; // admin, bailleur, locataire
            $identifiant = $_POST['identifiant'];
            $password = $_POST['password'];

            if ($type == "admin") {

                $user = $this->user->loginAdmin($identifiant);

                if ($user && password_verify($password, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['id_admin'];
                    $_SESSION['role'] = "admin";

                    header("Location: dashboard_admin.php");
                } else {
                    echo "Login admin incorrect";
                }
            }

            elseif ($type == "bailleur") {

                $user = $this->user->loginBailleur($identifiant);

                if ($user && password_verify($password, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['id_bailleur'];
                    $_SESSION['role'] = "bailleur";

                    header("Location: dashboard_bailleur.php");
                } else {
                    echo "Login bailleur incorrect";
                }
            }

            elseif ($type == "locataire") {

                $user = $this->user->loginLocataire($identifiant);

                if ($user && $password == $user['mot_de_passe']) {

                    $_SESSION['user_id'] = $user['id_locataire'];
                    $_SESSION['role'] = "locataire";

                    // première connexion
                    if ($user['first_login'] == 1) {
                        header("Location: change_password.php");
                    } else {
                        header("Location: dashboard_locataire.php");
                    }

                } else {
                    echo "Login locataire incorrect";
                }
            }
        }
    }

    public function logout()
    {
        session_destroy();
        header("Location: login.php");
    }
}