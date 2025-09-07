<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Commande_Histo extends Vue_Composant
{
    private $histoEtatCommande;
    public function __construct($histoEtatCommande)
    {
        $this->histoEtatCommande=$histoEtatCommande;
    }

    function donneTexte(): string
    {
        $str="";

        if ($this->histoEtatCommande != false) {
            $str .=  " 
            <h1 > Historique de la commande </h1 >
        ";
            $str .=  "<table style='padding: 20px; margin-bottom: 50px;   display: inline-block;   border: 1px solid #f1f1f1; box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24); background: #fff; ' >             
                <thead >
                    <tr >
                        <th > Date</th >
                        <th > Opérateur</th >
                        <th > Libelle</th >
                        <th > Information</th >
                    </tr >
                </thead > ";


            foreach ($this->histoEtatCommande as $etat) {
                $str .=  "
                <tr >
                        <td > $etat[dateHeure]</td >
                        <td>";
                if($etat["nom"] != null)
                    $str .=  "$etat[nom] $etat[prenom]";
                else
                    $str .=  "$etat[login]";
                $str .=  "
                        </td>
                        <td > $etat[libelle]</td >
                        <td > $etat[infoComplementaire]</td >
                    </tr >
        ";

            }
            $str .=  "</table > ";
        }
        return $str ;
    }

}