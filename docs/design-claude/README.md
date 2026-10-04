# Maquettes Claude Design — interface Housekeeping

Référence visuelle uniquement : ces fichiers ne sont ni servis (`public/`) ni utilisés
par les vues (`resources/views/`). `support.js` est le moteur d'affichage des maquettes.

- `Housekeeping Prototype.dc.html` : maquette cliquable (mobile, tablette, ordinateur).
- `Housekeeping Desktop.dc.html` / `Housekeeping Mobile.dc.html` : écrans séparés.

Écrans repris dans l'application :

| Maquette | Vue Blade |
|---|---|
| Accueil agent / gouvernante | `resources/views/dashboards/housekeeping.blade.php` |
| Signalement en 4 étapes | `resources/views/quick-reports/create.blade.php` |
| Confirmation | `resources/views/quick-reports/sent.blade.php` |
