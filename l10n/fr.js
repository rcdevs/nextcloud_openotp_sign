OC.L10N.register(
    "openotp_sign",
    {
    "OpenOTP Sign Settings" : "Paramètres de OpenOTP Sign",
    "Enter your OpenOTP server settings in the fields below." : "Entrez les paramètres de votre serveur OpenOTP dans les champs ci-dessous.",
    "OpenOTP server URL #" : "URL du serveur OpenOTP #",
    "Test" : "Test",
    "OpenOTP client id" : "ID client OpenOTP",
    "OpenOTP Default Domain" : "Domaine OpenOTP par défaut",
    "OpenOTP User settings" : "Paramètres utilisateur OpenOTP",
    "Proxy Settings" : "Paramètres du proxy",
    "Use a proxy" : "Utiliser un serveur proxy",
    "Proxy Host" : "Hôte du serveur proxy",
    "Proxy Port" : "Port du serveur proxy",
    "Proxy Username" : "Nom d’utilisateur du proxy",
    "Proxy Password" : "Mot de passe du proxy",
    "Signature scope" : "Étendue de la signature",
    "Local: Advanced signature with user certificates issued by internal WebADM CA" : "Locale : Signature avancée avec certificats utilisateurs émis par l’autorité de certification interne de WebADM",
    "Global: Advanced signature with user certificates issued by RCDevs Root CA" : "Globale : Signature avancée avec certificats utilisateurs émis par l’autorité de certification racine de RCDevs",
    "eIDAS: Qualified signature with external eIDAS signing devices (ex. eID Cards)" : "eIDAS : Signature qualifiée avec des dispositifs de signature eIDAS externes (ex. cartes eID)",
    "Signed / sealed PDF File" : "Fichier PDF signé / scellé",
    "Make a signed / sealed copy of the original PDF file" : "Faire une copie signée / scellée du fichier PDF original",
    "Overwrite the original PDF file with its signed / sealed copy" : "Écraser le fichier PDF original avec sa copie signée / scellée",
    "Signature requests time out" : "Délai d’expiration des demandes de signature",
    "Self-signature ({min} - {max} minutes)" : "Auto-signature ({min} - {max} minutes)",
    "Nextcloud user signature ({min} - {max} days)" : "Signature pour un utilisateur Nextcloud ({min} - {max} jours)",
    "Completion check of pending asynchronous signatures" : "Vérification de l’achèvement des signatures asynchrones en attente",
    "Define the execution periodicity of the background job that checks for completed signature requests.\nPlease note that for this periodicity to be honored, it is necessary to configure NextCloud background\njobs setting with 'Cron' value and to define the crontab periodicity accordingly." : "Ce paramètre définit la périodicité d’exécution de la tâche en arrière-plan qui vérifie les demandes de signature terminées.\nVeuillez noter que pour que cette périodicité soit respectée, il est nécessaire de configurer le paramètre des tâches de fond de NextCloud\navec la valeur 'Cron' et de définir la périodicité de la crontab en conséquence.",
    "Background job periodicity ({min} - {max} minutes)" : "Périodicité de la tâche en arrière-plan ({min} - {max} minutes)",
    "Demo mode" : "Mode démonstration",
    "In demo mode, it is only possible to sign or seal PDF files, on which a watermark will be added." : "En mode démonstration, il n’est possible de signer ou de sceller que des fichiers PDF, sur lesquels un filigrane sera ajouté.",
    "Enable demo mode" : "Activer le mode démonstration",
    "Watermark text" : "Texte du filigrane",
    "Save" : "Enregistrer",
    "Your settings have been saved succesfully" : "Vos paramètres ont été enregistrés avec succès",
    "There was an error saving settings" : "Une erreur s’est produite lors de l’enregistrement des paramètres",
    "Pending signature requests" : "Demandes de signature en attente",
    "Completed signature requests" : "Demandes de signature terminées",
    "Failed signature requests" : "Demandes de signature en échec",
    "Are you sure you want to cancel this signature request ?" : "Êtes-vous sûr de vouloir annuler cette demande de signature ?",
    "Date" : "Date",
    "Expiration Date" : "Date d'expiration",
    "Mode" : "Mode",
    "Recipient" : "Destinataire",
    "File" : "Fichier",
    "Cancel request" : "Annuler la demande",
    "There are currently no pending signature requests" : "Il n’y a actuellement aucune demande de signature en attente",
    "No pending signature requests" : "Aucune demande de signature en attente",
    "Previous" : "Précédent",
    "Next" : "Suivant",
    "Type" : "Type",
    "There are currently no completed signature requests" : "Il n’y a actuellement aucune demande de signature terminée",
    "No completed signature requests" : "Aucune demande de signature complétée",
    "Error" : "Erreur",
    "There are currently no failed signature requests" : "Il n’y a actuellement aucune demande de signature en échec",
    "No failed signature requests" : "Aucune demande de signature en échec",
    "Mobile signature is possible only with PDF files" : "La signature mobile n’est possible qu’avec des fichiers PDF",
    "OpenOTP Sign" : "OpenOTP Sign",
    "Digital signature of file <strong>{filename}</strong>" : "Signature numérique du fichier <strong>{filename}</strong>",
    "Self-signature" : "Auto-signature",
    "Signature by a Nextcloud user:" : "Signature par un utilisateur Nextcloud :",
    "Signature by a YumiSign user:" : "Signature par un utilisateur YumiSign :",
    "Mobile signature" : "Signature mobile",
    "Advanced signature" : "Signature avancée",
    "Close" : "Fermer",
    "You have to enter the <strong>OpenOTP server URL</strong> in the <strong>OpenOTP Sign</strong> settings prior to sign any document." : "Vous devez entrer l’URL du <strong>serveur OpenOTP</strong> dans les paramètres <strong>de OpenOTP Sign</strong> avant de signer un document.",
    "Digital seal of file <strong>{filename}</strong>" : "Sceau numérique du fichier <strong>{filename}</strong>",
    "Seal" : "Sceller",
    "You have to enter the <strong>OpenOTP server URL</strong> in the <strong>OpenOTP Sign</strong> settings prior to seal any document." : "Vous devez entrer l’URL du <strong>serveur OpenOTP</strong> dans les paramètres <strong>de OpenOTP Sign</strong> avant de sceller tout document.",
    "Sign with OpenOTP" : "Signer avec OpenOTP",
    "Seal with OpenOTP" : "Sceller avec OpenOTP",
    "Demo mode enabled. It is only possible to sign PDF files." : "Mode démonstration activé. Il est uniquement possible de signer des fichiers PDF.",
    "Sign" : "Signature",
    "Nextcloud app to sign your documents with OpenOTP" : "Application Nextcloud pour signer vos documents avec OpenOTP",
    "advanced" : "avancé",
    "mobile" : "mobile",
    "OpenOTP API key" : "Clé d'API OpenOTP",
    "The cron job is activated" : "La tâche cron est activée",
    "The cron job is activated; the last time the job ran was at %s" : "La tâche cron est activée ; la dernière exécution du processus était à %s",
    "The cron job was disabled at %s" : "La tâche cron a été désactivée à %s",
    "Checking process failed at %s" : "Le processus de vérification a échoué à %s",
    "Checking Cron" : "Vérification du Cron",
    "The cron job has been activated at %s" : "La tâche cron a été activée à %s",
    "Reset cron" : "Réactiver cron"
},
"nplurals=2; plural=(n > 1);");
