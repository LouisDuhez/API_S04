Documentation de l'API PHP de Gestion des Utilisateurs et Réservations
Introduction
Cette API permet la gestion des utilisateurs et des réservations à travers des requêtes HTTP. Elle est développée en PHP et utilise une base de données MySQL.
Configuration (à changer quand elle sera hébergé)
L'API se connecte à une base de données MySQL avec les identifiants suivants :
Hôte : localhost
Nom de la base : marie_curie_db
Utilisateur : root
Mot de passe : '' (vide par défaut)
Endpoints et Méthodes
1. Gestion des Utilisateurs (/users)
POST /users
Ajoute un nouvel utilisateur.
Paramètres attendus (form-data) :
email (string) : Email de l'utilisateur
user_name (string) : Prénom de l'utilisateur
user_lastname (string) : Nom de l'utilisateur
Réponse :
{
  "status": 200,
  "message": "User successfully inserted"
}

GET /users
Récupère la liste de tous les utilisateurs.

Réponse :
[
  {
    "user_id": 1,
    "user_email": "test@example.com",
    "user_name": "John",
    "user_lastname": "Doe"
  }
]

GET /users/{id}
Récupère un utilisateur spécifique.
Réponse :
{
  "user_id": 1,
  "user_email": "test@example.com",
  "user_name": "John",
  "user_lastname": "Doe"
}

DELETE /users/{id}
Supprime un utilisateur par son identifiant.

Réponse :
{
  "status": 200,
  "message": "User successfully deleted"
}

2. Gestion des Réservations (/reservations)
POST /reservations
Ajoute une nouvelle réservation.
Paramètres attendus (form-data) :
date (string) : Date de la réservation
student (int) : Nombre d'étudiants
normal (int) : Nombre de personnes normales
user (int) : ID de l'utilisateur
Réponse :
{
  "status": 200,
  "message": "Reservation successfully inserted"
}

GET /reservations
Récupère toutes les réservations.
Réponse :
[
  {
    "reservation_id": 1,
    "reservation_date": "2024-03-07",
    "reservation_nb_student": 2,
    "reservation_nb_normal": 3,
    "reservation_user_fk": 1
  }
]

GET /reservations/{id}
Récupère une réservation spécifique.
Réponse :
{
  "reservation_id": 1,
  "reservation_date": "2024-03-07",
  "reservation_nb_student": 2,
  "reservation_nb_normal": 3,
  "reservation_user_fk": 1
}

PUT /reservations/{id}
Met à jour une réservation.
Réponse :
{
  "status": 200,
  "message": "Reservation successfully updated"
}

DELETE /reservations/{id}
Supprime une réservation.
Réponse :
{
  "status": 200,
  "message": "Reservation successfully deleted"
}

Sécurité et JWT (en cours)
L'API utilise des JSON Web Tokens (JWT) pour sécuriser les échanges.
Remarque
Assurez-vous que config.php et JWT.php sont bien configurés.
Utilisez Postman ou un client API pour tester les endpoints.

