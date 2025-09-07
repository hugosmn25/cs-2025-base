<?php

//Code à utiliser sur le navigateur, quand une session bloque !
session_start();
unset($_SESSION);
session_destroy();

echo "session détruite";