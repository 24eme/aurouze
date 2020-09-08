db.Societe.dropIndexes();
db.Societe.createIndex({"$**":"text"}, {"default_language": "french"});

db.Compte.dropIndexes();
db.Compte.createIndex({"$**":"text"}, {"default_language": "french"});

db.Etablissement.dropIndexes();
db.Etablissement.createIndex({"$**":"text"}, {"default_language": "french"});

db.Contrat.dropIndexes();
db.Contrat.createIndex({"$**":"text"}, {"default_language": "french"});

db.Facture.dropIndexes();
db.Facture.createIndex({"$**":"text"}, {"default_language": "french"});

db.Passage.dropIndexes();
db.Passage.createIndex({"$**":"text"}, {"default_language": "french"});

db.Devis.dropIndexes();
db.Devis.createIndex({"$**":"text"}, {"default_language": "french"});
