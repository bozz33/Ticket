---
description: Sauvegarder et restaurer les bases PostgreSQL du projet Ticket sous Windows/Laragon
---
1. Vérifie que PostgreSQL répond sur `127.0.0.1:5432` et que les binaires sont disponibles dans `c:\laragon\bin\postgresql\postgresql\bin`.
2. Lance une sauvegarde d'une base avec PowerShell :
   `powershell -ExecutionPolicy Bypass -File c:\laragon\www\Ticket\scripts\backup-postgres.ps1 -Database ticket_central`
3. Pour une base tenant, relance la même commande avec le nom exact de la base tenant.
4. Conserve les dumps dans `c:\laragon\www\Ticket\storage\backups\postgres` et recopie-les hors machine avant toute opération risquée.
5. Pour restaurer un dump custom PostgreSQL :
   `powershell -ExecutionPolicy Bypass -File c:\laragon\www\Ticket\scripts\restore-postgres.ps1 -Database ticket_central -BackupFile <chemin-du-dump> -DropAndRecreate`
6. Pour restaurer un dump SQL plain text, ajoute `-PlainSql`.
7. Après restauration, relance les contrôles applicatifs critiques : `php artisan test`, `cmd /c npm --prefix c:\laragon\www\Ticket\front run build -- --webpack`, puis un smoke test des routes sensibles.
