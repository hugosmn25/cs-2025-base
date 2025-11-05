<?php

namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Compte_Administration_DeuxiemeFacteur extends Vue_Composant
{
    /** @var array<int, array<string, mixed>> */
    private array $facteurs;
    private ?int $facteurSelectionne;
    private string $message;
    private string $action;
    private string $titre;

    /**
     * @param array<int, array<string, mixed>> $facteurs
     */
    public function __construct(array $facteurs, ?int $facteurSelectionne = null, string $message = "", string $action = "Gerer_monCompte", string $titre = "Configurer le deuxieme facteur d'authentification")
    {
        $this->facteurs = $facteurs;
        $this->facteurSelectionne = $facteurSelectionne;
        $this->message = $message;
        $this->action = $action;
        $this->titre = $titre;
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
            $options = "<option value='' disabled selected>-- Selectionner --</option>" . $options;
        }

        $contenu = "<p>Aucun facteur d'authentification disponible pour le moment.</p>";
        if ($options !== "") {
            $contenu = "
        <form action='/$this->action/definir2FA' method='post'>
            <label for='facteur-authentification'>Choisir mon deuxieme facteur :</label>
            <select id='facteur-authentification' name='idFacteurAuthentification' required>
                $options
            </select>
            <button type='submit'>Enregistrer</button>
        </form>";
        }

        $suppression = "";
        if ($this->facteurSelectionne !== null) {
            $suppression = "
        <form action='/$this->action/supprimer2FA' method='post' style='margin-top: 1rem;'>
            <button type='submit'>Supprimer mon deuxieme facteur</button>
        </form>";
        }

        return "
    <h1>$this->titre</h1>
    $contenu
    $this->message
    $suppression
    ";
    }
}
