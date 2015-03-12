# Proxy d'interrogation de l'API Sitra 2 v0.1.0

[![Build Status](https://travis-ci.org/CHStudio/sitra2-proxy.svg?branch=develop)](https://travis-ci.org/CHStudio/sitra2-proxy) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/CHStudio/sitra2-proxy/badges/quality-score.png?s=e908698796250470837da1aee3d5f1de58abe42b)](https://scrutinizer-ci.com/g/CHStudio/sitra2-proxy/) [![Code Coverage](https://scrutinizer-ci.com/g/CHStudio/sitra2-proxy/badges/coverage.png?s=458223269fcf1205044aaa271d0bbfc08f1c7f95)](https://scrutinizer-ci.com/g/CHStudio/sitra2-proxy/)

Ce projet a pour objectif de rendre accessible facilement en PHP l'API d'interrogation et de recherche [Sitra2](http://www.sitra-rhonealpes.com/wiki/index.php/API_Sitra_2). Cette API permet aux utilisateurs de Sitra de rechercher dans les objets ou d'accéder à un objet pour consulter tous les détails.

Composer a été utilisé pour décrire le projet et le rendre utilisable comme composant.

### Comment m'en servir? ###

#### Installation ####

Pour pouvoir utiliser cette librairie, il faut simplement l'inclure...

```php
<?php
require_once "[chemin vers le fichier]src/SitraApi.php";
```

#### Ou alors utiliser composer ####

#### Configuration #####

Tous les appels à l'API doivent être authentifiés par deux clés :

* La clé d'API fournie lors de la création du projet dans Sitra
* L'identifiant du projet

Ces informations doivent être définies pour chaque instance du proxy soit à la construction soit en utilisant la méthode **configure**.

```php
<?php
$api = new SitraApi();
$api->configure("APIKEY", "PROJECTID");

//Or to use the API v1
$api = new SitraApi(SitraApi::V001);
$api->configure("APIKEY", "SITEWEBEXPORTIDV1");
```

#### Utilisation ####

Toutes les propriétés utilisables en recherche sont traduites en méthodes. Les valeurs passées pour chaque paramètre sont validées à travers un schéma respectant les règles définies dans la [documentation Sitra](http://www.sitra-rhonealpes.com/wiki/index.php/API_-_services_-_format_de_la_requete).

On démarre une requête en utilisant la méthode **start** et on l'exécute en utilisant la méthode **search**. L'objet utilise une interface chainable, toutes les méthodes sont applicables très rapidement. Pour la liste complète des méthodes, ouvrez le ficher SitraApi, le schéma y est décrit.

```php
<?php
//Récupération d'une liste de 100 objets à partir du 10ème
$results = $api
  ->start()
  ->selectionIds(["25918"])
  ->first(10)
  ->count(100)
  ->search();
```

#### Tests unitaires ####

PHPUnit a été utilisé pour créer les tests unitaires. Pour pouvoir les exécuter, il faut installer les dépendances **composer** en exécutant la commande suivante à la racine du projet :

```Shell

composer install
```

Ensuite le ficher **phpunit.xml** contient déjà tous les éléments nécessaire à l'exécution et à la récupération du rapport de couverture de code. Ce rapport est généré en HTML dans un repertoire **report** à la racine.

#### Documentation ####

PHPDocumentor a été choisit comme outil de génération de documentation. Le fichier **phpdoc.dist.xml** contient déjà la configuration et le résultat est placé dans un répertoire **doc** à la racine.

### Contribution et bugs ###

Vous pouvez me contacter directement pour toute question par email [s.hulard@chstudio.fr]() ou Twitter [@s_hulard](http://twitter.com/s_hulard).
