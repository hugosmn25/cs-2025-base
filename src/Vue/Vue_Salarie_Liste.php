<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;
class Vue_Salarie_Liste extends Vue_Composant
{
private array $listeSalarie;
private string $msg="";
    public function __construct(array $listeSalarie, string $msg="")
    {
        $this->listeSalarie=$listeSalarie;
        $this->msg=$msg;
    }

    function donneTexte(): string
    {

        $str= '
<H1>Liste des personnes habilitées à passer des commandes</H1>
<br>
Seuls les personnes habilitées peuvent passer des commandes au nom de votre entreprise. <br>
Ce compte d\'entreprise ne permet pas de passer des commandes.
<br>
Les salariés se connecteront avec leur e-mail<br>
    <table style="    display: inline-block;">
         <tr>
            <td colspan="5" style="text-align: center">
                <form action=\'/Gerer_Entreprise/ajouterSalarie\' method=\'get\' style=\'display: contents\'>
                    
                   
 
                        <button type="submit" 
                            onmouseover="this.style.background=\'#FFFF99\';this.style.color=\'#FF0000\';"
                            onmouseout="this.style.background=\'\';this.style.color=\'\';" > 
                                Ajouter un salarié 
                        </button>
                </form>
            </td>
 
        </tr>';
        if (count($this->listeSalarie) <= 0) {
            $str .=  '

         <tr>
            <td colspan="5" style="text-align: center">
                Il n\'y pas encore de salarié. Vous devez en ajouter un.
            </td>

        </tr>';

        } else
            $str .=  '<tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Rôle</th>
            <th>Mail</th>
         
         
        </tr>';

        foreach ($this->listeSalarie as $salarie) {
            $str .=  "
           
            
        <tr >
            <td>$salarie[nom]</td>
            <td>$salarie[prenom]</td>
            <td>$salarie[roleEntreprise]</td>
            <td>$salarie[mail]</td>
            ";
            /* if ($salarie["actif"])
                 $str .=  "Oui";
             else
                 $str .=  "Non";
             $str .=  "</td>";
     */

            $str .=  "
                <td>
                    <form  action='/Gerer_Entreprise/ModiferSalarie/$salarie[idSalarie]' method='get' style='display: contents'>
                    
                        <input type='hidden' name='case' value=''>
                             
                            
                            <button type='submit' 
                                onmouseover=\"this.style.background='#FFFF99';this.style.color='#FF0000';\"
                                onmouseout=\"this.style.background='';this.style.color='';\" >
                                 Modifier 
                            </button>
                    </form>
                </td>
                <!-- Création du bouton Désactiver ou Activer-->
                ";

            switch ($salarie["actif"]) {
                case 0:
                    $str .=  "
                <td>
                    <form action='/Gerer_Entreprise/DesactiverSalarie/$salarie[idSalarie]' style='display: contents'>
                        
                        <input type='hidden' name='case' value=''>
                          
                            
                            <button type='submit' 
                                onmouseover=\"this.style.background= '#FFFF99';this.style.color= '#FF0000';\"   
                                onmouseout=\"this.style.background='';this.style.color='';\" > 
                                    Désactiver 
                            </button>
                    </form>
                </td>";
                    break;
                case 1:
                    $str .=  "
                <td>
                    <form action='/Gerer_Entreprise/ActiverSalarie/$salarie[idSalarie]' method='get' style='display: contents'>
                         
                         
                             
                            
                            <button type='submit' 
                            onmouseover=\"this.style.background ='#FFFF99';this.style.color= '#FF0000';\"
                            onmouseout=\"this.style.background='';this.style.color='';\" >
                                Activer 
                            </button>
                    </form>
                </td>";
                    break;
            }
            $str .=  "</tr>";

        }


        $str .=  "</table>";
        $str .=  $this->msg;
        return $str;
    }
}