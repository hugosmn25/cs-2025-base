<?php

namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_ConsentementRGPD extends Vue_Composant
{
    private string $msgErreur;
    private $utilisateur;
    private ?array $politiqueCourante;
    private array $finalites;
    private array $consentsMap; // [finalite_id => statut]

    public function __construct($utilisateur, ?array $politiqueCourante, array $finalites, array $consentsMap, string $msgErreur = "")
    {
        $this->utilisateur = $utilisateur;
        $this->politiqueCourante = $politiqueCourante;
        $this->finalites = $finalites;
        $this->consentsMap = $consentsMap;
        $this->msgErreur = $msgErreur;
    }

    function donneTexte(): string
    {
        $erreur = $this->msgErreur !== "" ? "<div class='alerte-erreur' style='color:red; font-weight:bold; margin:10px 0;'>" . htmlspecialchars($this->msgErreur) . "</div>" : "";

        $header = "<h1>Politique de confidentialité</h1>";
        $contenu = "";
        $versionInfo = "";
        if ($this->politiqueCourante) {
            $cv = htmlspecialchars($this->politiqueCourante['code_version'] ?? '');
            $pl = htmlspecialchars($this->politiqueCourante['publie_le'] ?? '');
            $contenuBrut = $this->politiqueCourante['contenu'] ?? '';
            $versionInfo = "<div style='margin:8px 0; color:#555;'>Version: <b>$cv</b>, publiée le <b>$pl</b></div>";
            $contenu = "<div class='rgpd-politique' style='white-space:pre-wrap; border:1px solid #ddd; padding:10px; margin:10px 0;'>" . nl2br(htmlspecialchars($contenuBrut)) . "</div>";
        } else {
            $contenu = "<div class='rgpd-politique' style='border:1px solid #ddd; padding:10px; margin:10px 0;'>La politique n'est pas encore publiée.</div>";
        }

        // Construction des lignes de finalités
        $blocFinalites = "<fieldset style='border:1px solid #ccc; padding:10px;'><legend>Vos choix par finalité</legend>";
        if (count($this->finalites) === 0) {
            $blocFinalites .= "<div>Aucune finalité active.</div>";
        } else {
            foreach ($this->finalites as $f) {
                $id = (int)$f['id'];
                $nom = htmlspecialchars($f['nom']);
                $statut = $this->consentsMap[$id] ?? '';
                $checkedAcc = $statut === 'accorde' ? "checked" : "";
                $checkedRef = $statut === 'refuse' ? "checked" : "";
                $blocFinalites .= "<div style='margin:6px 0;'><label><b>$nom</b></label><br>"
                    . "<label style='margin-right:12px;'><input type='radio' name='finalite[$id]' value='accorde' " . ($checkedAcc ?: 'required') . "> J'accepte</label>"
                    . "<label><input type='radio' name='finalite[$id]' value='refuse' $checkedRef> Je refuse</label>"
                    . "</div>";
            }
        }
        $blocFinalites .= "</fieldset>";

        $form = "<form action='/Gerer_Rgpd/validerRGPD' method='post' style='margin-top:10px;'>"
            . ($this->politiqueCourante ? "<input type='hidden' name='version_politique_id' value='" . (int)$this->politiqueCourante['id'] . "'>" : "")
            . $blocFinalites
            . "<fieldset style='border:1px solid #ccc; padding:10px; margin-top:10px;'><legend>Validation globale</legend>"
            . "<p>Veuillez sélectionner une option pour continuer :</p>"
            . "<div style='margin:6px 0;'><label><input type='radio' name='accepterRGPD' value='1' required> J'accepte le traitement tel que décrit</label></div>"
            . "<div style='margin:6px 0;'><label><input type='radio' name='accepterRGPD' value='0'> Je refuse le traitement</label></div>"
            . "<div style='margin-top:10px;'><button type='submit'>Continuer</button></div>"
            . "</fieldset>"
            . "</form>";

        return $header . $versionInfo . $contenu . $erreur . $form;
    }
}
