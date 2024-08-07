db.Attachement.chunks.dropIndexes();
db.Attachement.files.dropIndexes();
db.Compte.dropIndexes();
db.Contrat.dropIndexes();
db.Devis.dropIndexes();
db.Etablissement.dropIndexes();
db.Facture.dropIndexes();
db.Paiements.dropIndexes();
db.Passage.dropIndexes();
db.RendezVous.dropIndexes();
db.Societe.dropIndexes();

db.Attachement.chunks.createIndex({"files_id":1,"n":1}, {"unique":true});
db.Attachement.files.createIndex({"etablissement":1}, {"background":true});
db.Attachement.files.createIndex({"societe":1}, {"background":true});
db.Attachement.files.createIndex({"updatedAt":1, "_id":1}, {"background":true});
db.Compte.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"$**":1},"language_override":"language","textIndexVersion":3});
db.Compte.createIndex({"tags.identifiant":1}, {"background":true});
db.Compte.createIndex({"societe":1}, {"background":true});
db.Contrat.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"numeroArchive":1, "markdown":1},"language_override":"language","textIndexVersion":3});
db.Contrat.createIndex({"mouvements.facture":1,"mouvements.facturable":1}, {"background":true});
db.Contrat.createIndex({"societe":1}, {"background":true});
db.Contrat.createIndex({"numeroArchive":1}, {"background":true});
db.Contrat.createIndex({"dateCreation":1,"identifiant":1}, {"background":true});
db.Devis.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"$**":1},"language_override":"language","textIndexVersion":3});
db.Devis.createIndex({"societe":1}, {"background":true});
db.Devis.createIndex({"dateDebut":1}, {"background":true});
db.Etablissement.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"$**":1},"language_override":"language","textIndexVersion":3});
db.Etablissement.createIndex({"societe":1}, {"background":true});
db.Facture.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"destinataire.raisonSociale":1, "destinataire.adresse":1, "destinataire.codePostal":1, "destinataire.commune":1, "destinataire.codeComptable":1, "numeroFacture":1, "numeroDevis":1},"language_override":"language","textIndexVersion":3});
db.Facture.createIndex({"numeroFacture":1,"cloture":1,"montantTTC":1,"dateLimitePaiement":1}, {"background":true});
db.Facture.createIndex({"lignes.origineDocument.$id":1}, {"background":true});
db.Facture.createIndex({"cloture":1,"numeroFacture":1}, {"background":true});
db.Facture.createIndex({"societe":1,"numeroFacture":1,"cloture":1}, {"background":true});
db.Paiements.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"identifiant":4,"paiement.libelle":2,"paiement.moyenPaiement":1,"paiement.typeReglement":1},"language_override":"language","textIndexVersion":3});
db.Paiements.createIndex({"paiement.montant":1});
db.Paiements.createIndex({"paiement.facture":1}, {"background":true});
db.Paiements.createIndex({"paiement.societe":1}, {"background":true});
db.Passage.createIndex({"statut":1,"datePrevision":1,"zone":1}, {"background":true});
db.Passage.createIndex({"etablissement":1}, {"background":true});
db.Passage.createIndex({"dateDebut":1}, {"background":true});
db.Passage.createIndex({"dateDebut":1,"techniciens":1}, {"background":true});
db.Passage.createIndex({"nettoyages":1}, {"background":true});
db.Passage.createIndex({"applications":1}, {"background":true});
db.Passage.createIndex({"numeroArchive":1}, {"background":true});
db.RendezVous.createIndex({"dateDebut":1}, {"background":true});
db.RendezVous.createIndex({"passage":1}, {"background":true});
db.RendezVous.createIndex({"participants":1}, {"background":true});
db.RendezVous.createIndex({"participants":1,"dateDebut":1}, {"background":true});
db.Societe.createIndex({"_fts":"text","_ftsx":1}, {"default_language":"french","weights":{"$**":1},"language_override":"language","textIndexVersion":3});
db.Societe.createIndex({"identifiant":1}, {"background":true});
