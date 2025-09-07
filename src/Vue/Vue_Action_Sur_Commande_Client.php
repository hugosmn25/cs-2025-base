<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Action_Sur_Commande_Client extends Vue_Composant
{
    private array $infoCommande;
    private array $etatAct;
    public function __construct(array $infoCommande, array $etatAct)
    {
        $this->infoCommande=$infoCommande;
        $this->etatAct=$etatAct;
    }

    function donneTexte(): string
    {
        $str= "<H1>Action(s) sur cette commande</H1>";
        switch ($this->infoCommande["etat"]) {
            case 2:
                $str .= "
                  Nous attendons la réception du paiement.
                ";
                break;
            case 3:
                $str .= "Nous avons reçu votre paiement. Info sur le virement : ".$this->etatAct["infoComplementaire"].".";
                break;
            case 4:
                $str .= "La commande a été envoyée. Info sur le colis : ".$this->etatAct["infoComplementaire"].".";
                break;

            case 5:
                $str .= "
                Nous avons un problème de stock. Info  : ".$this->etatAct["infoComplementaire"].".";
                break;
            case 6:
                $str .= " 
<form action='/Gerer_CommandeClient/Signalee_CommandeReceptionnee/".$this->infoCommande["id"]."' method='post' >
        
         <input type='hidden' name='changementEtatCommande' >
                    
        <button type='submit'>Commande réceptionnée sans incident'></button><br>
 </form>

 <form action='/Gerer_CommandeClient/Signalee_CommandeReceptionneeIncident/".$this->infoCommande["id"]."' method='post' >
        
        
         
        <input type='hidden' name='changementEtatCommande' >
 <button type='submit' name='action' value='action' value=''>Commande réceptionnée avec incident'</button>
   
                  <br>
                  <label>Informations complémentaires</label>
                  <input type='text' placeholder='info sur la livraison' value='info' style='width: 400px;height: 80px'>
                  </form>";
                break;
            case 7 : $str .= "La commande a été indiquée comme reçue correctement. Nous vous remercions pour votre confiance.";
            case 8 : $str .= "La commande a été indiquée comme ayant eu un incident :"
                .$this->etatAct["infoComplementaire"].". Nous recherchons une solution. Nous vous remercions pour votre confiance.";


        }
        return $str;
    }
}