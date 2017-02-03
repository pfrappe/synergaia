# SynerGaïa
SynerGaïa est un logiciel de développement rapide d'applications WEB destinées aux associations, aux petites entreprise, aux particuliers. Le développement se fait en quelques phrases grâce à un langage simple dont les mots et les verbes s'aggrègent les uns aux autres comme des morceaux de Lego.

Vos idées sont encore floues ? évolutives ? peu prévisibles ?
SynerGaïa fait évoluer votre application au rythme de la discussion.

Parce que vous pouvez faire et défaire aussi vite que vos idées, le travail de groupe devient vraiment créatif !

Parce que vous essayez sans risque, vous tenterez des applications vraiment nouvelles !

Votre charte de travail ? "<b>Ça ne coûte rien d'essayer !</b>"

# Quelques exemples simples
Définir ce qu'est un contact de votre annuaire :
<pre>@Dictionnaire.@DefinirObjet("Contact","Nom","Prenom","Ville","Telephone","Email")</pre>

La ligne du programme pour saisir un nouveau contact :
<pre>@Nouveau("Contact").@Modifier</pre>

La ligne du bouton pour afficher la liste de vos contacts lillois :
<pre>@Bouton("Contacts lillois",@Chercher("Contact","",.Ville.@Egale("Lille")).@Afficher(.Nom,.Prenom,.Telephone,.Email))</pre>

La ligne de programme pour afficher graphiquement la répartition géographique de vos contacts :
<pre>@Chercher("Contact").@GrouperCumuler(.Ville).@Graphique("h",.@Titre,.@Effectif)</pre>

# Documentation
Une documentation détaillée est accessible sur le site http://docum.synergaia.eu

# Installation
Comme la plupart des applications WEB, SynerGaïa est écrit en langage PHP et test spécialement adpaté pour tourner sur un petit serveur Linux. La base de données utilisée pour conserver et manipuler les informations est CouchDB.
Dans la documentation
