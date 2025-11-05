<?php

namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Compte_Administration_Gerer extends Vue_Composant
{
    private string $msg;
    private string $action;
    /** @var array<int, array<string, mixed>> */
    private array $facteurs;
    private ?int $facteurSelectionne;

    /**
     * @param array<int, array<string, mixed>> $facteurs
     */
    public function __construct(string $msg = "", string $action = "Gerer_monCompte", array $facteurs = [], ?int $facteurSelectionne = null)
    {
        $this->msg = $msg;
        $this->action = $action;
        $this->facteurs = $facteurs;
        $this->facteurSelectionne = $facteurSelectionne;
    }

    public function donneTexte(): string
    {
        $options = "";
        foreach ($this->facteurs as $facteur) {
            $id = isset($facteur["id"]) ? (int) $facteur["id"] : 0;
            $libelle = htmlspecialchars((string) ($facteur["libelle"] ?? ""), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $selected = ($this->facteurSelectionne !== null && $this->facteurSelectionne === $id) ? "selected" : "";
            $options .= "<option value='$id' $selected>$libelle</option>";
        }

        if ($options !== "" && $this->facteurSelectionne === null) {
            $options = "<option value='' disabled selected>-- Sélectionner --</option>" . $options;
        }

        $section2FA = "";
        if ($options !== "") {
            $section2FA = "
        <tr>
            <td>
                <form action='/$this->action/definir2FA' method='post' style='display: contents'>
                    <label for='facteur-authentification'>Choisir mon deuxième facteur :</label>
                    <select id='facteur-authentification' name='idFacteurAuthentification' required>
                        $options
                    </select>
                    <button type='submit'>Enregistrer</button>
                </form>
            </td>
        </tr>";
        }

        return "
    <H1>Gérer mon compte</H1>
    <table style='display: inline-block'>
        <tr>
            <td>
                <form action='/$this->action/changerMDP' method='get' style='display: contents'>
                    <button type='submit'>Changer mot de passe </button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/$this->action/SeDeconnecter' method='get' style='display: contents'>
                    <button type='submit'>
                        Se déconnecter 
                    </button>
                </form>
            </td>
        </tr>
        $section2FA
    </table>
    $this->msg
    ";
    }
}
