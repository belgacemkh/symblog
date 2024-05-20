#!/bin/bash
LOG_FILE="README.md"

# Ajouter la commande au fichier README.md
echo "\$ $@" >> $LOG_FILE

# Exécuter la commande
"$@"