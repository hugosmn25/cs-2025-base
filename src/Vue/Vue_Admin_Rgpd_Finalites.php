<?php
namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Admin_Rgpd_Finalites extends Vue_Composant
{
    private array $finalites;
    public function __construct(array $finalites)
    {
        $this->finalites = $finalites;
    }

    function donneTexte(): string
    {
        $rows = '';
        foreach ($this->finalites as $f) {
            $id = (int)$f['id'];
            $nom = htmlspecialchars($f['nom']);
            $actif = (int)$f['actif'] === 1;
            $rows .= "<tr>".
                "<td>$id</td>".
                "<td>".
                "<form action='/Admin_Rgpd/finalites/renommer/$id' method='post' style='display: inline-flex; gap:6px;'>".
                "<input type='hidden' name='id' value='$id'>".
                "<input type='text' name='nom' value='$nom' size='28' required>".
                "<button type='submit'>Renommer</button>".
                "</form>".
                "</td>".
                "<td>".
                ($actif
                    ? "<form action='/Admin_Rgpd/finalites/desactiver/$id' method='get' style='display: contents'><button type='submit'>Désactiver</button></form>"
                    : "<form action='/Admin_Rgpd/finalites/activer/$id' method='get' style='display: contents'><button type='submit'>Activer</button></form>")
                ."</td>".
                "</tr>";
        }

        return "
        <section style='padding: 10px;'>
            <h2>Finalités de consentement</h2>
            <div style='margin:10px 0;'>
                <form action='/Admin_Rgpd/finalites/ajouter' method='post' style='display: inline-flex; gap:8px;'>
                    <input type='text' name='nom' placeholder='Nom de la nouvelle finalité' required>
                    <button type='submit'>Ajouter</button>
                </form>
            </div>
            <table border='1' cellpadding='6' cellspacing='0' style='margin: 0 auto;'>
                <thead>
                    <tr><th>ID</th><th>Nom</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    $rows
                </tbody>
            </table>
        </section>
        ";
    }
}
