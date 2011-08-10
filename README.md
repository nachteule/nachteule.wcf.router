nachteule.wcf.router
====================

Mit diesem Paket kann man Routen anlegen, auf die Requests geroutet werden.
Letztendlich wird aber nur die Adresszeile schöner :)

Technisch betrachtet wird um den gesamten Request ein Output-Buffer gelegt,
der die URLs umschreibt. Zudem wird durch einen EventListener die
best-passendste Route gesucht und deren Parameter in `$_GET` und `$_REQUEST`
übertragen.

Beispiel
--------

```
Route: users/{#$userID}
Parameter: page=User
```

Sämtliche URLs wie `index.php?page=User&userID=1` werden nun umgeschrieben zu
`users/1`. Variablen in der Route gibt man über `{$variable}` oder `{#$variable}` an.
Letzteres steht für eine Zahl, ohne # werden alle Zeichen gematcht. Dadurch kann
beispielsweise `users/{#$userID}` und gleichzeitig `users/{$username}` aufs Profil verweisen.

Weitere Parameter
-----------------

Beim Umschreiben wird immer die am besten passende Route genommen.
Variablen die nicht in der Route vorkommen, werden normal an den Query-String
angehangen. Somit kann man auch eine "leere" Route anlegen, die immer dann
genommen wird, wenn keine andere passt.

### Beispiel

```
Route: thread/{#$threadID}
Parameter: page=Thread

Umzuschreibende URL: index.php?page=Thread&threadID=1&action=lastPost
Umgeschriebene URL: thread/1?action=lastPost

Route:
Parameter:

Umzuschreibende URL: index.php?page=PageWithoutRoute
Umgeschriebene URL: ?page=PageWithoutRoute
```

Weitere Routen
--------------

```
index						->	page=Index
users						->	page=MembersList
users/online				->	page=UsersOnline
users/{#$userID}			->	page=User
users/{#$userID}/friends	->	page=UserFriendList
users/{#$userID}/gallery	->	page=UserGallery
users/{#$userID}/newPM		->	form=PMNew
```

Installation
------------

Nach der Installation muss eine .htaccess angelegt werden, die Requests
auf die `index.php` legt und den Query-String anhängt. Zudem müssen die
Verzeichnisse mit statischen Dateien wie `style/`, `icon/`, `js/`, ...
ausgelassen werden.
