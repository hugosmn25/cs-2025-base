<?php

namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Commande_Liste extends Vue_Composant
{
    private array $listeCommande;
    private string $controleur;
    public function __construct(array $listeCommande, string $controleur = "Gerer_CommandeClient"   )
    {

        $this->listeCommande = $listeCommande;
        $this->controleur = $controleur;
    }

    function donneTexte(): string
    {
        $str = " 
            <h1>Commandes</h1>
                 ";

        //print_r($listeProduits);
        if (count($this->listeCommande) >= 1) {
            $str .= " <table style='padding: 20px; margin-bottom: 50px;   display: inline-block;   border: 1px solid #f1f1f1; box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24); background: #fff; ' >             
 <thead>
                    <tr>
                        <th>Reference Commande</th>
                        <th>Date commande</th>
                        <th>Client</th>
                        <th >Nb d'articles</th>
                        <th >Total HT</th>
                        <th >Montant TVA</th>
                        <th >Total TTC</th>
                        
                        <th>Etat</th>
                    
                    </tr>
                
                </thead>

 
             ";

            //var_dump($listeCommande);
            foreach ($this->listeCommande as $item) {

                $montantTVA = $item["prixTotalTTC"] - $item["prixTotalHT"];
                $str .= "
            <tr style='text-align: center;font-size: ' >
                        <td>$item[id]</td>
                        <td >$item[dateCreation]</td>
                        <td >$item[denomination]</td>
                        <td >$item[nbProduit]</td>
                        <td >" . number_format($item["prixTotalHT"], 2) . " €</td>
                        <td >" . number_format($montantTVA, 2) . " €</td>
                        <td >" . number_format($item["prixTotalTTC"], 2) . " €</td>
                        <td >$item[libEtat]</td>
                        <td >
                            <form action='/$this->controleur/VoirDetailCommande/$item[id]' method='get' style='display: contents'>
                                 
                                 
                                <button type='submit' >
                                 Voir
                                </button>
                            </form>
                        </td>
                    </tr>
            ";

            }


        }
        return $str;
    }
}