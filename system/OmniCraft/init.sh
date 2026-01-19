#!/usr/bin/env bash

echo "======================================"
echo "      OmniCraft Interactive Wizard      "
echo "======================================"
echo ""

PS3="Choose what you want to generate: "
options=("Module" "Model" "CRUD" "Migration" "Quit")
select opt in "${options[@]}"
do
    case $opt in
        "Module")
            echo ""
            echo "=== Generate Module ==="
            read -p "Module ID (e.g., admin): " moduleID
            CMD="php yii gii/module --moduleID=${moduleID} --moduleClass=\"modules\\${moduleID}\\Module"\"
            echo ""
            echo "Generated Command:"
            echo "$CMD"
            read -p "Run this command? (y/n): " confirm
            [[ "$confirm" == "y" ]] && eval $CMD
            break
            ;;
        "CRUD")
            echo ""
            echo "=== Generate CRUD ==="
            read -p "Designated Module (e.g., admin): " designatedModule
            read -p "Controller Name (e.g., User): " controllerClass
            echo ""
            echo "Model Class options:"
            echo "  1. Simple name → e.g., User"
            echo "  2. With sub-namespace → e.g., iam/models/User   (RECOMMENDED)"
            echo "  3. PHP 8 style → e.g., iam::models::User"
            echo "  NOTE: Do NOT use backslashes (\\) — they can cause issues!"
            echo ""
            read -r -p "Model Class: " modelInput   
            # Now detect raw backslashes correctly
            if [[ "$modelInput" == *\\* ]]; then
                echo ""
                echo "ERROR: You used backslashes (\\) in the input."
                echo "This can break the command due to shell escaping issues."
                echo ""
                echo "Please use one of these safe formats instead:"
                echo "   • iam/models/User"
                echo "   • iam::models::User"
                echo "   • Just the class name: User"
                echo ""
                echo "Aborting CRUD generation."
                echo ""
                break
            fi
            # Normalize input safely
            normalized="${modelInput////\\}"           # / → \
            normalized="${normalized//::/\\}"           # :: → \
            # If no namespace, assume module\models
            if [[ "$normalized" != *\\* ]]; then
                modelClass="${designatedModule}\\models\\${normalized}"
            else
                modelClass="$normalized"
            fi
            # Rest of your code...
            [[ "$controllerClass" != *Controller ]] && controllerClass="${controllerClass}Controller"
            searchClass="${controllerClass%Controller}Search"
            searchModel="${designatedModule}\\models\\searches\\${searchClass}"

            CMD="php yii gii/crud \
                --controllerClass=\"${designatedModule}\\controllers\\${controllerClass}\" \
                --modelClass=\"${modelClass}\" \
                --searchModelClass=\"${searchModel}\" \
                --overwrite=1"
            echo ""
            echo "Generated Command:"
            echo "$CMD"
            echo ""

            read -p "Run this command? (y/n): " confirm
            if [[ "$confirm" =~ ^[Yy]$ ]]; then
                eval "$CMD"
            else
                echo "Cancelled."
            fi

            break
            ;;
        "Model")
            echo ""
            echo "=== Generate Model ==="
            read -p "Table Name (e.g., users): " tableName
            read -p "Designated Module (e.g., admin): " designatedModule
            read -p "Model Class Name (e.g., User): " modelClass
            CMD="php yii gii/model --tableName=${tableName} --modelClass=${modelClass} --ns=\"${designatedModule}\models\""
            echo ""
            echo "Generated Command:"
            echo "$CMD"
            read -p "Run this command? (y/n): " confirm
            [[ "$confirm" == "y" ]] && eval $CMD
            break
            ;;

        "Migration")
            echo ""
            echo "=== Migration Tools ==="
            echo "Choose migration action:"
            echo "1) Create Migration"
            echo "2) Apply Migrations (Up)"
            echo "3) Rollback Last Migration (Down)"
            echo "4) Fresh Migration"
            echo "5) Show Pending Migrations"

            read -p "Select option: " migOpt

            case $migOpt in

                1)
                    echo ""
                    echo "=== Create Migration ==="
                    read -p "Designated Module (blank = app migrations): " designatedModule
                    read -p "Migration Name (e.g., create_users_table): " migrationName

                    # Determine path
                    if [[ "$designatedModule" != "" ]]; then
                        MIG_PATH="modules/${designatedModule}/migrations"
                    else
                        MIG_PATH="modules/website/migrations"
                    fi

                    # Build command
                    CMD="php yii voyage/create ${migrationName} --migrationPath=\"${MIG_PATH}\""

                    echo ""
                    echo "Generated Command:"
                    echo "$CMD"
                    read -p "Run this command? (y/n): " confirm
                    [[ "$confirm" == "y" ]] && eval $CMD
                ;;

                2)
                    echo ""
                    echo "=== Apply All Migrations ==="
                    CMD="php yii voyage/up --interactive=0"
                    echo "Command: $CMD"
                    read -p "Run it? (y/n): " confirm
                    [[ "$confirm" == "y" ]] && eval $CMD
                ;;

                3)
                    echo ""
                    echo "=== Rollback Last Migration (Down) ==="
                    read -p "Migrations to Rollback (e.g.. 1: blank = all): " migrationsRolback

                    # Determine path
                    if [[ "$migrationsRolback" != "" ]]; then
                        MIG_ROLL=${migrationsRolback}
                    else
                        MIG_ROLL="all"
                    fi

                    CMD="php yii voyage/down ${MIG_ROLL} --interactive=0"
                    echo "Command: $CMD"
                    read -p "Run it? (y/n): " confirm
                    [[ "$confirm" == "y" ]] && eval $CMD
                ;;

                4)
                    echo ""
                    echo "=== Run Fresh Migration ==="
                    CMD="php yii voyage/fresh"
                    echo "Command: $CMD"
                    read -p "Run it? (y/n): " confirm
                    [[ "$confirm" == "y" ]] && eval $CMD
                ;;

                5)
                    echo ""
                    echo "=== Show Pending Migrations ==="
                    CMD="php yii voyage/history"
                    echo "Command: $CMD"
                    read -p "Run it? (y/n): " confirm
                    [[ "$confirm" == "y" ]] && eval $CMD
                ;;

                *)
                    echo "Invalid migration option."
                ;;
            esac

            break
            ;;


        "Quit")
            echo "Exiting wizard."
            exit 0
            ;;

        *) echo "Invalid option $REPLY";;
    esac
done
