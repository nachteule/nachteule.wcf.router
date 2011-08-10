nachteule.wcf.router
====================

Mit diesem Paket kann man Routen anlegen, auf die Requests geroutet werden.
Letztendlich wird aber nur die Adresszeile schöner :)

Technisch betrachtet wird um den gesamten Request ein Output-Buffer gelegt,
der die URLs umschreibt. Zudem wird durch einen EventListener die
best-passendste Route gesucht und deren Parameter in $_GET und $_REQUEST
übertragen.

Beispiel
--------

```
Route: users/{#$userID}
Parameter: page=User
```

Sämtliche URLs wie `index.php?page=User&userID=1` werden umgeschrieben zu
`users/1`. Variablen in der Route gibt man über {$variable} oder {#$variable} an.
Letzteres steht für eine Zahl, ohne # wird alles gematcht. Dadurch kann
beispielsweise `users/{#$userID}` und `users/{$username}` aufs Profil verweisen.

Beim Umschreiben wird immer die am besten passende Route genommen.
Variablen die nicht in der Route vorkommen, werden normal an den Query-String
angehangen. Somit kann man auch eine "leere" Route anlegen, die immer dann
genommen wird, wenn keine andere passt. 

### Weitere Routen

```
index				->	page=Index
users				->	page=MembersList
users/online		->	page=UsersOnline
users/{#$userID}	->	page=User
```
