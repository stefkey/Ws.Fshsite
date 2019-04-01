
# Ws.Fshsite

## Importing the old page

The first page was hosted on .... Technologies xyz where used. Data and groups where
imported to Neo using an self developed importer package.

## Updating to Neos 4.0

* cleaned up dirty git
* removed not needed packages
* form-builder -> commit history was broken
    * fallback to forked repository
* "doctrine/collection": "1.5",

TODO

* change to officail repo -> "url": "https://github.com/johannessteu/TYPO3.FormBuilder.git"
* enable redis again -> /Users/florian/src/frauenselbsthilfe/neos/Packages/Sites/Ws.Fshsite/Configuration/Caches.yaml_back

Hi Florian, 

wie ist  das mit dem Update, werden auch die zusätzlichen Pakete mit geupdatet? 

Es ist auch das Paket Lelesys.News installiert. Und zwar "von Hand“, es erscheint daher nicht in der composer.json. Das brauchen wir in jedenfalls aber auch. 

Und ich das Paket JohannesSteu.Neos.NodeTypes ist auch installiert, auch von Hand weil ich es damals per composer require nicht hinbekommen habe. 

Des Weiteren hatte ich damals von Dmitri geholfen bekommen einige hunderte Seiten von dem alten Webauftritt in Neos zu importieren. Das haben (wir)er auch per git gemacht: https://github.com/stefkey/Ws.Fshsite/commits/master?after=54b81429cbce5ca06eb7a6d74e7fc3b5061063a2+69 

Und weiter um die importierten Seiten in einem Menü nach Postleitzahlen sortiert auszugeben. 
https://github.com/stefkey/Ws.Fshsite/commit/8f07b3d4799b3a2154916222680a1c8d4ba9d93f 

Muss man diese Pakete auch anpassen? 


Danke dir und Grüße 
Stefan
        
        
       
