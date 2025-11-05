<?php

namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Connexion_Second_Facteur extends Vue_Composant
{
    private string $login;
    private string $message;

    public function __construct(string $login, string $message = "")
    {
        $this->login = $login;
        $this->message = $message;
    }

    public function donneTexte(): string
    {
        $messageHtml = $this->message !== "" ? "<p>$this->message</p>" : "";

        return "
    <h1>Verification en deux etapes</h1>
    <p>Un code a usage unique vient d'etre envoye a l'adresse <strong>{$this->login}</strong>. Entrez-le ci-dessous pour finaliser votre connexion.</p>
    $messageHtml
    <form action='/visiteur/verifier2FA' method='post'>
        <label for='code2FA'>Code à 6 chiffres :</label>
        <input type='text' id='code2FA' name='code2FA' maxlength='6' pattern='[0-9]{6}' required>
        <button type='submit'>Valider</button>
    </form>
    <form action='/visiteur/SeConnecter' method='get' style='margin-top: 1rem;'>
        <button type='submit'>Annuler</button>
    </form>
    ";
    }
}
