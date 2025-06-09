Voici la reformulation complète du cahier des charges fonctionnel, intégrant les informations les plus récentes, avec l'ajout du mini-schéma demandé à la fin :

# LocalO'drive – Cahier des charges

## 1. Vision
**LocalO’drive** est une plateforme digitale (site web et application mobile) à but **non lucratif** dédiée à la vente de produits alimentaires locaux en circuit court, initialement dans la région **Auvergne-Rhône-Alpes**. L'objectif principal est de **faciliter l’accès aux produits locaux et de soutenir les producteurs régionaux**, tout en valorisant les circuits courts et en encourageant une consommation responsable. La plateforme vise à connecter directement les producteurs régionaux aux consommateurs. Le projet s'inscrit dans le cadre d'un rapport de deuxième année de Bachelor Informatique à l'ESGI, avec un client fictif nommé Local Network, une entreprise à but non lucratif qui soutient les circuits courts et favorise les produits locaux.

## 2. Acteurs
| Rôle | Besoin clé |
|---|---|
| **Utilisateur (Consommateur)** | Déposer commentaires et notations sur produits/marques/commerçants, proposer de nouveaux produits locaux, accéder à ses factures et historique d'achats, enregistrer des listes de courses, localiser des points de retrait. |
| **Commerçant/Producteur local** | Vendre ses produits au bon prix, élargir son spectre de vente à l'échelle régionale, disposer d'un espace de vente digital, présenter son entreprise/produits, suivre les ventes et gérer les stocks. |
| **Entreprise LocalO’drive (Admin)** | Élargir le spectre de vente des commerçants locaux à l’échelle régional et satisfaire les besoins des utilisateurs habitués des produits alimentaire locaux, gérer les utilisateurs, les produits et les commandes. |
| **G. Morgan** (Resp. Développeur Backend, Expert Cybersécurité) | Développer le backend, mettre en place les mesures de sécurité, gérer les bases de données. Compétences techniques avancées dans le développement des fonctionnalités backend et la sécurisation des données, garantissant la robustesse du système. |
| **F. Dimitri** (Admin Réseau, Développeur Frontend) | Configurer et maintenir les serveurs, optimiser les performances réseau, développer le frontend. Maîtrise de la configuration et de la maintenance des serveurs, assurant une performance optimale de la plateforme. |
| **M. Silvère** (Rédacteur, Développeur Frontend, Resp. RGPD, Coordinateur Projet) | Rédiger le cahier des charges, développer le frontend, assurer la conformité RGPD, coordonner les tâches et les retours utilisateurs. Expertise en conception d’interfaces intuitives et en conformité réglementaire, garantissant la clarté et la sécurité de la plateforme. |

## 3. Fonctions prioritaires (MVP)
1.  **Gestion des Utilisateurs**:
    *   Inscription avec validation par email, Connexion/Déconnexion.
    *   Profils utilisateurs (client, producteur, admin) et gestion des informations personnelles.
2.  **Catalogue de Produits**:
    *   Listage et affichage des produits.
    *   Fonctionnalités de tri : par Nom, Catégorie, Rayon, Type, Marque, Commerçant, Populaire, Prix, Dernier produit ajouté au site/Distance.
    *   Barre de **recherche** (implicite pour le catalogue).
    *   Possibilité de laisser un **commentaire et une note** sur chaque produit, marque et commerçant.
3.  **Panier d'Achat**:
    *   Ajout/Suppression de produits.
    *   Modification des quantités et calcul automatique des totaux.
    *   Enregistrement de listes de courses personnalisées.
4.  **Commande et Paiement**:
    *   Intégration d'une API de **paiement sécurisé** (Stripe).
    *   **Carte interactive des points de retrait** pour localiser les drives les plus proches.
    *   Accès aux factures et historique des produits achetés.
5.  **Interface Producteur**:
    *   Tableau de bord pour gérer les produits, suivre les ventes et gérer les stocks.
6.  **Sécurité**:
    *   Protection contre les attaques DDoS (Cloudflare), injections SQL (OWASP CRS), et failles XSS (OWASP CRS).
    *   Hachage des mots de passe, protection CSRF, sessions sécurisées.
    *   Validation des entrées utilisateur et des emails.

## 4. User stories détaillées
### US-PROD-01 – Soumission de nouveau produit
> En tant qu’**utilisateur engagé**, je veux pouvoir soumettre de nouveaux produits locaux que j'aimerais voir ajoutés sur le site/application, afin que ma demande soit prise en compte et traitée.
>
> **Acceptance :** L'utilisateur reçoit une confirmation de la prise en compte de sa suggestion.

### US-COMM-01 – Noter et commenter
> En tant qu’**utilisateur**, je veux pouvoir déposer un commentaire et attribuer une note à un produit, une marque ou un commerçant, et consulter les commentaires des autres utilisateurs, afin de partager mon expérience et d'orienter mes choix.
>
> **Acceptance :** Le commentaire et la note sont visibles pour les autres utilisateurs et peuvent être modérés par l'administrateur.

### US-HIST-01 – Consultation de l'historique d'achats
> En tant qu’**utilisateur**, je veux pouvoir accéder facilement à toutes mes factures et à l'historique de mes produits achetés, afin de suivre ma consommation et mes dépenses.
>
> **Acceptance :** L'utilisateur peut visualiser et télécharger ses factures, et parcourir l'historique de ses commandes passées.

## 5. Non-fonctionnel
*   **Performance** : Maintenir un temps de chargement des pages **inférieur à 3 secondes**. Utilisation de CDN (Cloudflare), compression d'images (ImageKit) et caching avancé (Next.js).
*   **Scalabilité** : Gérer les pics de trafic (jours fériés, week-ends) via Kubernetes avec Google Kubernetes Engine (GKE).
*   **Sécurité** : Protection robuste contre diverses attaques (DDoS, injections SQL, XSS), conformité RGPD pour la gestion des données personnelles. Utilisation de Let's Encrypt pour SSL. Hachage des mots de passe, protection CSRF, sessions sécurisées, validation des entrées utilisateur et des emails.
*   **Expérience Utilisateur (UX/UI)** : L'application doit offrir une interface intuitive, rapide et accessible, respectant les bonnes pratiques de design UX.
*   **Langue** : Le site/application est disponible uniquement en français.
*   **Zone géographique** : Initialement limitée à la région Auvergne-Rhône-Alpes, avec possibilité d'extension future vers d'autres régions en fonction du succès initial et des retours utilisateurs.
*   **Conformité Légale** : Respect des réglementations alimentaires (traçabilité, conformité sanitaire), vérification de l'authenticité des produits locaux (labels, certifications), et conformité avec les règles de la vente en ligne (droit de rétractation, informations claires sur les prix et la livraison).
*   **Gestion des Stocks** : Utilisation d'un système de gestion des stocks en temps réel avec Odoo, intégré aux producteurs locaux pour une mise à jour dynamique des stocks disponibles.
*   **Support Client** : Support limité aux heures de bureau locales, avec une section FAQ détaillée et un chatbot Botpress pour les questions fréquentes.
*   **Technologie** : Application mobile et site web, optimisés pour tous les appareils (Android, PC, Mac et Apple). Utilisation de MySQL pour la base de données.
*   **Modèle économique** : Non lucratif, mais les coûts d'exploitation seront couverts par une légère marge sur les produits vendus (commissions de 5% à 10%), des partenariats locaux, des sponsors et de légers frais de livraison fixes. Les produits peuvent être légèrement plus chers qu'en magasin local en raison des frais de livraison, logistique et commissions.

## 6. Glossaire
*   **Drive alimentaire local** : Plateforme permettant de commander des produits alimentaires directement auprès de producteurs locaux avec un système de retrait ou de livraison.
*   **Circuit court** : Modèle de distribution où il y a un intermédiaire au maximum entre le producteur et le consommateur, favorisant la transparence et le soutien à l'économie locale.
*   **Non lucratif** : Modèle économique où les profits ne sont pas le but principal, visant plutôt à couvrir les coûts d'exploitation et à soutenir une cause (ici, les producteurs locaux et la consommation responsable).
*   **Points de retrait** : Lieux physiques où les consommateurs peuvent venir chercher leurs commandes passées en ligne, souvent chez les producteurs eux-mêmes ou dans des lieux partenaires.
*   **Cahier des Charges Fonctionnel (CCF)** : Document détaillé spécifiant les fonctionnalités attendues d'un projet logiciel, ainsi que les contraintes, le périmètre et les objectifs.
*   **KPI (Key Performance Indicator)** : Indicateur Clé de Performance, utilisé pour mesurer le succès d'un projet ou d'une activité, souvent défini selon la méthode SMART (Spécifique, Mesurable, Atteignable, Réaliste, Temporellement défini).

---
### Mini schéma de la table des produits :
`table products (id, name, category, price, image, stock INT)`