<?php
include "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer; //Obligatoire pour avoir l’objet phpmailer qui marche
$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = '127.0.0.1';
$mail->Port = 1025; //Port non crypté
$mail->SMTPAuth = false; //Pas d’authentification
$mail->SMTPAutoTLS = false; //Pas de certificat TLS
$mail->setFrom('test@labruleriecomtoise.fr', 'admin');
$mail->addAddress('client@labruleriecomtoise.fr', 'Mon client');
if ($mail->addReplyTo('test@labruleriecomtoise.fr', 'admin')) {
    $mail->Subject = 'Objet : Bonjour !';
    $mail->isHTML(false);
    $mail->Body = "Corps du message pour mon client :)";

    if (!$mail->send()) {
        $msg = 'Désolé, quelque chose a mal tourné. Veuillez réessayer plus tard.';
    } else {
        $msg = 'Message envoyé ! Merci de nous avoir contactés.';
    }
} else {
    $msg = 'Il doit manquer qqc !';
}
echo $msg;
?>