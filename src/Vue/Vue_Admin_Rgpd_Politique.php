<?php
namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Admin_Rgpd_Politique extends Vue_Composant
{
    private $courante; // array|false
    private string $suggestCode;
    public function __construct($courante, string $suggestCode)
    {
        $this->courante = $courante;
        $this->suggestCode = $suggestCode;
    }

    function donneTexte(): string
    {
        $infoCourante = '';
        $defaultContenu = '';
        if ($this->courante) {
            $cv = htmlspecialchars($this->courante['code_version'] ?? '');
            $pl = htmlspecialchars($this->courante['publie_le'] ?? '');
            $hs = htmlspecialchars($this->courante['hash_contenu'] ?? '');
            $defaultContenu = $this->courante['contenu'] ?? '';
            $infoCourante = "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>".
                "<h3>Version courante</h3>".
                "<p><b>Code version:</b> $cv</p>".
                "<p><b>Publiée le:</b> $pl</p>".
                "<p><b>SHA-256:</b> <code>$hs</code></p>".
                "</div>";
        } else {
            $infoCourante = "<div style='color:#a00; margin:10px 0;'>Aucune version courante.</div>";
        }

        return "
        <section style='padding: 10px;'>
            <div style='max-width: 900px; margin: 0 auto;'>
            <h2>Politique de confidentialité</h2>
            $infoCourante
            <h3>Ajouter une nouvelle version</h3>
            <form action='/Admin_Rgpd/politique/ajouter' method='post' style='display:block; max-width: 900px; margin: 0 auto;'>
                <div style='margin:6px 0;'>
                    <label>Code version (ex: 2025-09-01):<br>
                        <input type='text' name='code_version' required value='".$this->suggestCode."'>
                    </label>
                </div>
                <div style='margin:6px 0;'>
                    <label>Contenu intégral:<br>
                        <textarea name='contenu' rows='12' style='width: 100%;' required>".htmlspecialchars($defaultContenu)."</textarea>
                    </label>
                </div>
                <div style='margin:10px 0;'>
                    <button type='submit'>Publier comme version courante</button>
                </div>
            </form>
            </div>
        </section>
        ";
    }
}
