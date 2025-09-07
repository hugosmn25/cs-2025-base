<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Entreprise_Information extends Vue_Composant
{
    public int $idEntreprise=-1;
    public string $denomination="";
    public string $rueAdresse="";
    public string $complementAdresse="";
    public string $codePostal="";
    public string $ville="";
    public string $pays="France";
    public string $numCompte="";
    public string $mailContact="";
    public string $siret="";

    public function __construct(int    $idEntreprise=-1, string $denomination="", string $rueAdresse="",
                                string $complementAdresse="", string $codePostal="", string $ville="",
                                string $pays="France", string $numCompte="", string $mailContact="", string $siret="")
    {
        $this->idEntreprise=$idEntreprise;
        $this->denomination=$denomination;
        $this->rueAdresse=$rueAdresse;
        $this->complementAdresse=$complementAdresse;
        $this->codePostal=$codePostal;
        $this->ville=$ville;
        $this->pays=$pays;
        $this->numCompte=$numCompte;
        $this->mailContact=$mailContact;
        $this->siret=$siret;
    }


    function donneTexte() :string
    {
        // vous trouverez des explications sur les paramètres HTML5 des balises INPUT sur ce site :
        // https://darchevillepatrick.info/html/html_form.htm

        $str="<H1>Les informations de mon entreprise</H1>";
        $str .= "<i>Pour tout changement, veuillez nous contacter.</i><br>";

        $str .=  "
<table style='display: inline-block'> 

        <tr>
            <td>
                <label>Numéro de compte : </label>
            </td>
            <td>
                $this->numCompte
            </td>
        </tr>
        <tr>
        
            <td>
                <label>Dénomination (en lettres majuscules) : </label>
            </td>
            <td>
                $this->denomination
            </td>
        </tr>
        <tr>
            <td>
                <label>Rue : </label>
            </td>
            <td>
                $this->rueAdresse
            </td>
        </tr>
        <tr>
            <td>
                <label>Rue (complément)  : </label>
            </td>
            <td>$this->complementAdresse</td>
        </tr>
        <tr>
            <td>
                <label>Code postal : </label>
            </td>
            <td>$this->codePostal
            </td>
        </tr>
        <tr>
            <td>
                <label>Ville : </label>
            </td>
            <td>$this->ville
            </td>
        </tr>
        
        <tr>
            <td>
                <label>Pays : </label>
            </td>
            <td>$this->pays
            </td>
        </tr>
        <tr>
            <td>
                <label>Couriel : </label>
            </td>
            <td>$this->mailContact
            </td>
        </tr>
        <tr>
            <td>
                <label>Siret (14 chiffres) </label>
            </td>
            <td>$this->siret
            </td>
        </tr>


</table>

";
        return $str;
    }
}