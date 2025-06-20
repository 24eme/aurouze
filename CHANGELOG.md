# Changelog

##  Du 23 avril au 20 juin 2025 

#### Global 
    - retrait des techniciens inactifs dans les choix techniciens tout en les gardant sur les anciens contrats et rapports


#### Page retard 
    - possibilité d'envoyer plusieurs email de relance en même temps
    - après la génération de retard en masse on redirige avec les même filtre
    - on ne génère pas de pdf si toutes les factures ont été envoyées par email
    - correction bug commentaire relance 
    - retirer le champ "date de règlement du"


#### Page tournée
    - mise en forme du listing des photos
    - section signature : bouton effacer la signature en petit en bas à droite du cadre de signature et un bouton valider à sa place
    - section ajout de photos : Après l'upload de photos,  retour sur le passage et non sur le listing de tous les passage
    - section ajout de photos : le bouton valider du formulaire d'ajout d'un upload ne fonctionnait pas
    - section signature : correction bug enregistrement du nom signataire
    - section ajout de photos : suppression champs non utilisés dans l'ajout de photos
    - section signature : modifier infos signataire directement au moment de la signature
    - section signature devis: texte et icône pour effacer signature page devis
    - section signature : la signature reste affichée
    - section passage : historiser les passages dans la tournée sans les passages devis
    - section signature passage: texte bouton et icône poubelle pour effacer signature
    - section signature : ajouter le nom signataire modifiable 
    - afficher l'historique des passages
    - section ajout de photos : possibilité d'ajouter plusieurs images 


#### Page facturation 
    - amélioration du calcul du reste du trop perçu
    - amélioration du calcul du montant facturé
    - amélioration du calcul du montant payé (Aggregation builder)
    - section envoi facture : ajout de l'email du destinataire dans le pop up de confirmation 
    - section création facture libre : visualisation de l'adresse de facturation 


#### Page document
    - section ajout document : correction typo et ajout précision type de document
    - possibilité de modifier la visibilité des documents pour les techniciens
    - section ajout : possibilité de lier un numéro de contrat à un document et document exclu du passage si ne correspond pas au numéro de contrat du passage 


#### Page contrat 
    - section annulation contrat : mettre la date de prévision passage à la même date du début du passage si passage après date de résilitation
    - section reconduction massive : ajout onglet zone 75/77
    - ajout montant payé facture dans le contrat
    - section annulation contrat : listing des passages non planifiés
    - alerte si création d'un contrat ponctuel de plus de 12 mois
    - section création contrat : possibilité de créer automatiquement plusieurs factures échelonnées sur la période du contrat


#### Page paiements 
    - ajouter padding (espace) entre derniere ligne en bouton enregistrer


#### Page passage/planif
    - refermer l'affichage tous les contrats 


#### Page devis 
    - section création : ajout du montant total TTC
    - affichage de la date d'acceptation seulement si le devis déjà créé