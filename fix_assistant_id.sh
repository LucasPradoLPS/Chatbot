#!/bin/bash
# Fix script to update assistant_id in database

# Define paths
DB_PATH="c:\Users\lucas\Downloads\Chatbot-laravel\database\database.sqlite"
CORRECT_ASSISTANT_ID="asst_TK2zcCJXJE7reRvMIY0Vw4im"

# Use sqlite3 to update all records with incorrect assistant_id
sqlite3 "$DB_PATH" "UPDATE agente_gerados SET agente_base_id = '$CORRECT_ASSISTANT_ID' WHERE agente_base_id = '2' OR agente_base_id IS NULL OR agente_base_id = '';"

echo "Updated assistant IDs in database"

# Verify the update
echo "Current records:"
sqlite3 "$DB_PATH" "SELECT id, numero_cliente, funcao, agente_base_id FROM agente_gerados LIMIT 10;"
