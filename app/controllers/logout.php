<?php

session_start();

// supprimer toutes les variables session
session_unset();

// détruire session
session_destroy();

// redirection vers login
header("Location: ../../index.html");

exit();