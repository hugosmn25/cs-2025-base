<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Action_Sur_Commande_Entreprise extends Vue_Composant
{
    private array $infoCommande;
    public function __construct(array $infoCommande)
    {
        $this->infoCommande = $infoCommande;
    }


    function donneTexte(): string
    {
        $str = "";
        $str .= "<H1>Action(s) sur cette commande</H1>";


        switch ($this->infoCommande["etat"]) {
            case 2:
                $str .= "
                <form action='/Gerer_Commande/Signaler_CommandePayee/" . $this->infoCommande["id"] . "' method='post'>
       
        <input type='hidden' name='changementEtatCommande' >
                  <button type='submit'>
                        Commande payée, virement reçu
                  </button>
                  
                  <br>
                  <label>Informations complémentaires</label>
                  <input type='text' placeholder='numéro transaction' value='info' style='width: 400px;height: 80px'>
                </form>
                  ";
                break;
            case 3:
                $str .= "
                 <form action='/Gerer_Commande/Signalee_CommandeEnPreparation/" . $this->infoCommande["id"] . "' method='post'>
       
        <input type='hidden' name='changementEtatCommande' >
                <button type='submit'  >
                    Commande en préparation (QTT OK)
                </button>  
                </form>
                             <br>
                
                
                <br>
                 <form action='/Gerer_Commande/Signalee_CommandeProblemeStock/" . $this->infoCommande["id"] . "' method='post'>
       
        <input type='hidden' name='changementEtatCommande' >
                <button type='submit' >
                    Commande en attente approvisionnement (QTT Pas OK)
                </button>
                
                
                <br>
                  <label>Informations complémentaires</label>
                  <input type='text' placeholder='Estimation réassort' value='info' style='width: 400px;height: 80px'>
            </form>
                  ";
                break;
            case 4:
                $str .= "
                <form action='/Gerer_Commande/Signalee_CommandeEnvoyée/" . $this->infoCommande["id"] . "' method='post'>
       
        <input type='hidden' name='changementEtatCommande' >
                <button type='submit' >
                    Commande expédiée
                </button>
                               
                <br>
                  <label>Informations complémentaires</label>
                  <input type='text' placeholder='Numero de colis' value='info' style='width: 400px;height: 80px'>
                  
                  </form>";

                break;

            case 5:
                $str .= "
                <form action='/Gerer_Commande/Signalee_CommandeEnPreparation/" . $this->infoCommande["id"] . "' method='post'>
       
        <input type='hidden' name='changementEtatCommande' >
                <button type='submit'  >
                        Réassort arrivé
                </button>        
                </form>       
                ";

                break;
            case 6:
                $str .= "Commande expédiée, nous sommes en attente de la réponse du client";
                break;


        }

        return $str;
    }

}